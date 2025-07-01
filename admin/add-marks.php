<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Process form submission for adding/updating marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_marks'])) {
    // Get form data
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $exam_id = intval($_POST['exam_id']);
    $marks_obtained = floatval($_POST['marks_obtained']);
    $marks_max = floatval($_POST['marks_max']);
    $remarks = trim($_POST['remarks']);
    
    // Validate input
    if (empty($student_id) || empty($subject_id) || empty($exam_id) || empty($marks_obtained)) {
        $error = "Please fill in all required fields";
    } elseif ($marks_obtained < 0 || $marks_obtained > $marks_max) {
        $error = "Marks must be between 0 and " . $marks_max;
    } else {
        // Calculate grade based on percentage
        $percentage = ($marks_obtained / $marks_max) * 100;
        $grade = '';
        
        if ($percentage >= 90) {
            $grade = 'A+';
        } elseif ($percentage >= 80) {
            $grade = 'A';
        } elseif ($percentage >= 70) {
            $grade = 'B';
        } elseif ($percentage >= 60) {
            $grade = 'C';
        } elseif ($percentage >= 50) {
            $grade = 'D';
        } else {
            $grade = 'F';
        }
        
        // Check if marks entry already exists for this student, subject, and exam
        $stmt = $conn->prepare("SELECT id FROM marks WHERE student_id = ? AND subject_id = ? AND exam_id = ?");
        $stmt->bind_param("iii", $student_id, $subject_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing marks
            $marks_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("UPDATE marks SET marks_obtained = ?, marks_max = ?, grade = ?, remarks = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ddssi", $marks_obtained, $marks_max, $grade, $remarks, $marks_id);
            
            if ($stmt->execute()) {
                $success = "Marks updated successfully";
                // Update result for this student and exam
                generateResult($conn, $student_id, $exam_id);
            } else {
                $error = "Error updating marks: " . $conn->error;
            }
        } else {
            // Insert new marks
            $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to admin if not set
            $stmt = $conn->prepare("INSERT INTO marks (student_id, subject_id, exam_id, marks_obtained, marks_max, 
                                   grade, remarks, created_by, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->bind_param("iiiddssi", $student_id, $subject_id, $exam_id, $marks_obtained, $marks_max, 
                             $grade, $remarks, $created_by);
            
            if ($stmt->execute()) {
                $success = "Marks added successfully";
                // Generate result for this student and exam
                generateResult($conn, $student_id, $exam_id);
            } else {
                $error = "Error adding marks: " . $conn->error;
            }
        }
    }
}

// Process delete marks request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_marks'])) {
    $marks_id = intval($_POST['marks_id']);
    $student_id = intval($_POST['student_id']); 
    $exam_id = intval($_POST['exam_id']);
    
    // Delete the marks entry
    $stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
    $stmt->bind_param("i", $marks_id);
    
    if ($stmt->execute()) {
        $success = "Marks deleted successfully";
        // Recalculate results for this student and exam
        generateResult($conn, $student_id, $exam_id);
    } else {
        $error = "Error deleting marks: " . $conn->error;
    }
}

// Function to generate/update result for a student and exam
function generateResult($conn, $student_id, $exam_id) {
    // Get all marks for the student in this exam
    $stmt = $conn->prepare("SELECT subject_id, marks_obtained, marks_max FROM marks 
                           WHERE student_id = ? AND exam_id = ?");
    $stmt->bind_param("ii", $student_id, $exam_id);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    
    if ($marks_result->num_rows > 0) {
        $total_obtained = 0;
        $total_max = 0;
        $subject_count = 0;
        
        // Calculate total and percentage
        while ($mark = $marks_result->fetch_assoc()) {
            $total_obtained += $mark['marks_obtained'];
            $total_max += $mark['marks_max'];
            $subject_count++;
        }
        
        $percentage = ($total_obtained / $total_max) * 100;
        
        // Determine grade and result status
        $grade = '';
        $result_status = '';
        
        if ($percentage >= 90) {
            $grade = 'A+';
            $result_status = 'pass';
        } elseif ($percentage >= 80) {
            $grade = 'A';
            $result_status = 'pass';
        } elseif ($percentage >= 70) {
            $grade = 'B';
            $result_status = 'pass';
        } elseif ($percentage >= 60) {
            $grade = 'C';
            $result_status = 'pass';
        } elseif ($percentage >= 50) {
            $grade = 'D';
            $result_status = 'pass';
        } else {
            $grade = 'F';
            $result_status = 'fail';
        }
        
        // Calculate rank (simplified approach)
        $rank = 0;
        
        // Check if results entry already exists
        $stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND exam_id = ?");
        $stmt->bind_param("ii", $student_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing results
            $result_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("UPDATE results SET 
                                  total_marks = ?, 
                                  total_max_marks = ?, 
                                  percentage = ?, 
                                  grade = ?, 
                                  result_status = ?, 
                                  rank = ?, 
                                  updated_at = CURRENT_TIMESTAMP 
                                  WHERE id = ?");
            $stmt->bind_param("dddssii", $total_obtained, $total_max, $percentage, 
                            $grade, $result_status, $rank, $result_id);
            $stmt->execute();
        } else {
            // Insert new results
            $is_published = 'no'; // Default to unpublished
            $stmt = $conn->prepare("INSERT INTO results 
                                  (student_id, exam_id, total_marks, total_max_marks, 
                                  percentage, grade, rank, result_status, is_published, 
                                  created_at, updated_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 
                                  CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->bind_param("iidddssss", $student_id, $exam_id, $total_obtained, 
                            $total_max, $percentage, $grade, $rank, 
                            $result_status, $is_published);
            $stmt->execute();
        }
    }
}

// Get all students
$students = [];
$student_query = "SELECT s.id, s.student_id, s.class, s.section, u.full_name as student_name 
                  FROM students s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE u.status = 'active'
                  ORDER BY s.student_id ASC";
$student_result = $conn->query($student_query);
if ($student_result && $student_result->num_rows > 0) {
    while ($row = $student_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get all subjects
$subjects = [];
$subject_query = "SELECT id, subject_code, subject_name, class FROM subjects 
                 WHERE is_active = 'yes' 
                 ORDER BY class ASC, subject_name ASC";
$subject_result = $conn->query($subject_query);
if ($subject_result && $subject_result->num_rows > 0) {
    while ($row = $subject_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get all exams
$exams = [];
$exam_query = "SELECT id, exam_name, description, start_date, end_date, class, max_marks, is_active 
              FROM exams 
              WHERE is_active = 'yes' 
              ORDER BY start_date DESC";
$exam_result = $conn->query($exam_query);
if ($exam_result && $exam_result->num_rows > 0) {
    while ($row = $exam_result->fetch_assoc()) {
        $exams[] = $row;
    }
}

// Get all marks with student and subject details
$marks_data = [];
$marks_query = "SELECT m.id, m.student_id, m.subject_id, m.exam_id, m.marks_obtained, m.marks_max, 
               m.grade, m.remarks, m.created_at, 
               s.student_id as student_roll, s.class, s.section, 
               u.full_name as student_name,
               sub.subject_name, sub.subject_code, sub.class as subject_class,
               e.exam_name, e.start_date, e.end_date, e.class as exam_class, e.max_marks as exam_max_marks
               FROM marks m
               JOIN students s ON m.student_id = s.id
               JOIN users u ON s.user_id = u.id
               JOIN subjects sub ON m.subject_id = sub.id
               JOIN exams e ON m.exam_id = e.id
               ORDER BY e.start_date DESC, s.student_id ASC, sub.subject_name ASC";
$marks_result = $conn->query($marks_query);
if ($marks_result && $marks_result->num_rows > 0) {
    while ($row = $marks_result->fetch_assoc()) {
        $marks_data[] = $row;
    }
}

// Include header
include_once '../includes/header.php';
?>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<!-- Bootstrap CDNs -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Custom styles specific to this page -->
<style>
    .table-marks th, .table-marks td {
        vertical-align: middle;
    }
    .marks-badge {
        font-size: 14px;
    }
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Marks Management</h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add/Update Student Marks</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="student_id">Student <span class="text-danger">*</span></label>
                                <select class="form-control" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['student_name'] . ' (' . $student['class'] . '-' . $student['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="subject_id">Subject <span class="text-danger">*</span></label>
                                <select class="form-control" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name'] . ' (' . $subject['class'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="exam_id">Exam <span class="text-danger">*</span></label>
                                <select class="form-control" id="exam_id" name="exam_id" required>
                                    <option value="">Select Exam</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?php echo $exam['id']; ?>" data-max="<?php echo $exam['max_marks']; ?>">
                                            <?php echo htmlspecialchars($exam['exam_name'] . ' - ' . $exam['class'] . ' (' . 
                                                date('d M Y', strtotime($exam['start_date'])) . 
                                                ($exam['start_date'] != $exam['end_date'] ? ' to ' . date('d M Y', strtotime($exam['end_date'])) : '') . 
                                                ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="marks_obtained">Marks Obtained <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="marks_obtained" name="marks_obtained" min="0" step="0.01" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="marks_max">Maximum Marks <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="marks_max" name="marks_max" min="1" step="0.01" value="100" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional remarks"></textarea>
                        </div>
                        <button type="submit" name="add_marks" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Marks
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Marks</h5>
                    <div>
                        <input type="text" id="marksSearch" class="form-control form-control-sm" placeholder="Search...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-marks" id="marksTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($marks_data) > 0): ?>
                                    <?php foreach ($marks_data as $mark): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($mark['student_roll']); ?></td>
                                            <td><?php echo htmlspecialchars($mark['student_name'] . ' (' . $mark['class'] . '-' . $mark['section'] . ')'); ?></td>
                                            <td><?php echo htmlspecialchars($mark['subject_name'] . ' (' . $mark['subject_code'] . ')'); ?></td>
                                            <td><?php echo htmlspecialchars($mark['exam_name'] . ' - ' . $mark['exam_class'] . ' (' . 
                                                date('d M Y', strtotime($mark['start_date'])) . 
                                                ($mark['start_date'] != $mark['end_date'] ? ' to ' . date('d M Y', strtotime($mark['end_date'])) : '') . 
                                                ')'); ?></td>
                                            <td>
                                                <?php 
                                                    $percentage = ($mark['marks_obtained'] / $mark['marks_max']) * 100;
                                                    $badge_class = 'secondary';
                                                    if ($percentage >= 90) {
                                                        $badge_class = 'success';
                                                    } elseif ($percentage >= 70) {
                                                        $badge_class = 'primary';
                                                    } elseif ($percentage >= 50) {
                                                        $badge_class = 'warning';
                                                    } elseif ($percentage < 50) {
                                                        $badge_class = 'danger';
                                                    }
                                                ?>
                                                <span class="badge badge-<?php echo $badge_class; ?> marks-badge">
                                                    <?php echo $mark['marks_obtained'] . '/' . $mark['marks_max'] . ' (' . number_format($percentage, 1) . '%)'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo $mark['grade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($mark['remarks']) ? htmlspecialchars($mark['remarks']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary edit-marks-btn" 
                                                    data-id="<?php echo $mark['id']; ?>"
                                                    data-toggle="modal" 
                                                    data-target="#editMarksModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-marks-btn" 
                                                    data-id="<?php echo $mark['id']; ?>"
                                                    data-student="<?php echo htmlspecialchars($mark['student_name']); ?>"
                                                    data-subject="<?php echo htmlspecialchars($mark['subject_name']); ?>"
                                                    data-student-id="<?php echo $mark['student_id']; ?>"
                                                    data-exam-id="<?php echo $mark['exam_id']; ?>"
                                                    data-toggle="modal" 
                                                    data-target="#deleteMarksModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No marks data found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Marks Modal -->
<div class="modal fade" id="editMarksModal" tabindex="-1" role="dialog" aria-labelledby="editMarksModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editMarksModalLabel">Edit Marks</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Edit form will be loaded here via AJAX -->
                <div id="editMarksFormContainer">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Marks Modal -->
<div class="modal fade" id="deleteMarksModal" tabindex="-1" role="dialog" aria-labelledby="deleteMarksModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMarksModalLabel">Confirm Delete</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete these marks?</p>
                <p><strong>Student:</strong> <span id="deleteStudentName"></span></p>
                <p><strong>Subject:</strong> <span id="deleteSubjectName"></span></p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will affect the student's results.</p>
                
                <form id="deleteMarksForm" method="POST" action="">
                    <input type="hidden" name="marks_id" id="delete_marks_id" value="">
                    <input type="hidden" name="student_id" id="delete_student_id" value="">
                    <input type="hidden" name="exam_id" id="delete_exam_id" value="">
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_marks" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Search functionality for marks table
        $("#marksSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#marksTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Set max marks based on selected exam
        $("#exam_id").change(function() {
            var selectedOption = $(this).find("option:selected");
            var maxMarks = selectedOption.data("max");
            
            if (maxMarks) {
                $("#marks_max").val(maxMarks);
            } else {
                $("#marks_max").val(100); // Default value
            }
        });
        
        // Validate marks obtained against max marks
        $("#marks_obtained").on("input", function() {
            var obtained = parseFloat($(this).val()) || 0;
            var max = parseFloat($("#marks_max").val()) || 100;
            
            if (obtained > max) {
                $(this).val(max);
                alert("Marks obtained cannot be greater than maximum marks (" + max + ")");
            }
        });
        
        // Handle delete marks button click
        $(".delete-marks-btn").click(function() {
            var marksId = $(this).data('id');
            var studentId = $(this).data('student-id');
            var examId = $(this).data('exam-id');
            var studentName = $(this).data('student');
            var subjectName = $(this).data('subject');
            
            $("#delete_marks_id").val(marksId);
            $("#delete_student_id").val(studentId);
            $("#delete_exam_id").val(examId);
            $("#deleteStudentName").text(studentName);
            $("#deleteSubjectName").text(subjectName);
        });
        
        // For a complete implementation, you would add AJAX to load the edit form
        // This is a simplified version for this example
        $(".edit-marks-btn").click(function() {
            var marksId = $(this).data('id');
            // Here, you would typically use AJAX to load the edit form with the marks data
            // For this example, we'll just show a placeholder
            $("#editMarksFormContainer").html("<p>Edit form would load here via AJAX with marks ID: " + marksId + "</p>");
        });
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>