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
    $marks = floatval($_POST['marks']);
    $exam_date = $_POST['exam_date'];
    $remarks = trim($_POST['remarks']);
    
    // Validate input
    if (empty($student_id) || empty($subject_id) || empty($marks) || empty($exam_date)) {
        $error = "Please fill in all required fields";
    } elseif ($marks < 0 || $marks > 100) {
        $error = "Marks must be between 0 and 100";
    } else {
        // Check if marks entry already exists for this student and subject
        $stmt = $conn->prepare("SELECT id FROM marks WHERE student_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $student_id, $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing marks
            $marks_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("UPDATE marks SET marks = ?, exam_date = ?, remarks = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("dssi", $marks, $exam_date, $remarks, $marks_id);
            
            if ($stmt->execute()) {
                $success = "Marks updated successfully";
            } else {
                $error = "Error updating marks: " . $conn->error;
            }
        } else {
            // Insert new marks
            $stmt = $conn->prepare("INSERT INTO marks (student_id, subject_id, marks, exam_date, remarks, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->bind_param("iidss", $student_id, $subject_id, $marks, $exam_date, $remarks);
            
            if ($stmt->execute()) {
                $success = "Marks added successfully";
                // Generate result record
                generateResult($conn, $student_id);
            } else {
                $error = "Error adding marks: " . $conn->error;
            }
        }
    }
}

// Process delete marks request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_marks'])) {
    $marks_id = intval($_POST['marks_id']);
    $student_id = intval($_POST['student_id']); // For result recalculation
    
    // Delete the marks entry
    $stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
    $stmt->bind_param("i", $marks_id);
    
    if ($stmt->execute()) {
        $success = "Marks deleted successfully";
        // Recalculate results for this student
        generateResult($conn, $student_id);
    } else {
        $error = "Error deleting marks: " . $conn->error;
    }
}

// Function to generate/update result for a student
function generateResult($conn, $student_id) {
    // Get all marks for the student
    $stmt = $conn->prepare("SELECT subject_id, marks FROM marks WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    
    if ($marks_result->num_rows > 0) {
        $total_marks = 0;
        $subject_count = 0;
        
        // Calculate total and average
        while ($mark = $marks_result->fetch_assoc()) {
            $total_marks += $mark['marks'];
            $subject_count++;
        }
        
        $average = $subject_count > 0 ? ($total_marks / $subject_count) : 0;
        
        // Determine grade and result status based on average
        $grade = '';
        $result_status = '';
        
        if ($average >= 90) {
            $grade = 'A+';
            $result_status = 'PASS';
        } elseif ($average >= 80) {
            $grade = 'A';
            $result_status = 'PASS';
        } elseif ($average >= 70) {
            $grade = 'B';
            $result_status = 'PASS';
        } elseif ($average >= 60) {
            $grade = 'C';
            $result_status = 'PASS';
        } elseif ($average >= 50) {
            $grade = 'D';
            $result_status = 'PASS';
        } else {
            $grade = 'F';
            $result_status = 'FAIL';
        }
        
        // Check if results entry already exists for this student
        $stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing results
            $result_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("UPDATE results SET total_marks = ?, average = ?, grade = ?, result = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ddssi", $total_marks, $average, $grade, $result_status, $result_id);
            $stmt->execute();
        } else {
            // Insert new results
            $stmt = $conn->prepare("INSERT INTO results (student_id, total_marks, average, grade, result, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->bind_param("iddss", $student_id, $total_marks, $average, $grade, $result_status);
            $stmt->execute();
        }
    }
}

// Get all students
$students = [];
$student_query = "SELECT id, student_name, roll_no FROM students WHERE is_active = 'yes' ORDER BY roll_no ASC";
$student_result = $conn->query($student_query);
if ($student_result->num_rows > 0) {
    while ($row = $student_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get all subjects
$subjects = [];
$subject_query = "SELECT id, subject_code, subject_name, class FROM subjects WHERE is_active = 'yes' ORDER BY class ASC, subject_name ASC";
$subject_result = $conn->query($subject_query);
if ($subject_result->num_rows > 0) {
    while ($row = $subject_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get all marks with student and subject details
$marks_data = [];
$marks_query = "SELECT m.id, m.marks, m.exam_date, m.remarks, m.created_at, 
                       s.student_name, s.roll_no, 
                       sub.subject_name, sub.subject_code, sub.class
                FROM marks m
                JOIN students s ON m.student_id = s.id
                JOIN subjects sub ON m.subject_id = sub.id
                ORDER BY s.roll_no ASC, sub.subject_name ASC";
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
<!-- Bootstrap CDNs --> <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
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
<div>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
<h2>
Marks Management

</h2>
<a>
Back to Dashboard

</a>
</div>
        <?php if (!empty($error)): ?>
<div>
                <?php echo $error; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
<div>
                <?php echo $success; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php endif; ?>
<div>
            <div class="card-header">
<h5>
Add/Update Student Marks

</h5>
</div>
<div>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
<label>
Student

<span>
</span>
</label>
<select>
<option>
Select Student

</option>
                                <?php foreach ($students as $student): ?>
<option>
">
<?php echo htmlspecialchars($student['roll_no'] . ' - ' . $student['student_name']); ?>

</option>
                                <?php endforeach; ?>
</select>
</div>
<div>
<label>
Subject

<span>
</span>
</label>
<select>
<option>
Select Subject

</option>
                                <?php foreach ($subjects as $subject): ?>
<option>
">
<?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name'] . ' (' . $subject['class'] . ')'); ?>

</option>
                                <?php endforeach; ?>
</select>
</div>
                    </div>
<div>
                        <div class="form-group col-md-4">
<label>
Marks

<span>
</span>
</label>
                            <input type="number" class="form-control" id="marks" name="marks" min="0" max="100" step="0.01" required>
<small>
Enter marks between 0 and 100

</small>
</div>
<div>
<label>
Exam Date

<span>
</span>
</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" required>
</div>
<div>
<label>
Remarks

</label>
                            <input type="text" class="form-control" id="remarks" name="remarks" placeholder="Optional remarks">
</div>
                    </div>
<button>
<i class="fas fa-save"></i>

Save Marks

</button>
                </form>
            </div>
        </div>
<div>
            <div class="card-header d-flex justify-content-between align-items-center">
<h5>
All Marks

</h5>
                <div>
                    <input type="text" id="marksSearch" class="form-control form-control-sm" placeholder="Search...">
</div>
            </div>
<div>
                <div class="table-responsive">
<table>
<thead>
<tr>
<th>
Roll No.

</th>
<th>
Student Name

</th>
<th>
Subject (Code)

</th>
<th>
Class

</th>
<th>
Marks

</th>
<th>
Exam Date

</th>
<th>
Remarks

</th>
<th>
Actions

</th>
</tr>
</thead>
<tbody>
                            <?php if (count($marks_data) > 0): ?>
                                <?php foreach ($marks_data as $mark): ?>
<tr>
<td>
<?php echo htmlspecialchars($mark['roll_no']); ?>
</td>
<td>
<?php echo htmlspecialchars($mark['student_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($mark['subject_name'] . ' (' . $mark['subject_code'] . ')'); ?>
</td>
<td>
<?php echo htmlspecialchars($mark['class']); ?>
</td>
<td>
                                            <?php 
                                                $mark_value = floatval($mark['marks']);
                                                $badge_class = 'secondary';
                                                if ($mark_value >= 90) {
                                                    $badge_class = 'success';
                                                } elseif ($mark_value >= 70) {
                                                    $badge_class = 'primary';
                                                } elseif ($mark_value >= 50) {
                                                    $badge_class = 'warning';
                                                } elseif ($mark_value < 50) {
                                                    $badge_class = 'danger';
                                                }
                                            ?>
<span>
marks-badge">
<?php echo $mark_value; ?>

</span>
</td>
<td>
<?php echo date('d M Y', strtotime($mark['exam_date'])); ?>
</td>
<td>
<?php echo !empty($mark['remarks']) ? htmlspecialchars($mark['remarks']) : '
<span>
</span>
'; ?>

</td>
<td>
<button>
"
data-toggle="modal"
data-target="#editMarksModal">

<i class="fas fa-edit"></i>

</button>
<button>
"
data-student="<?php echo htmlspecialchars($mark['student_name']); ?>"
data-subject="<?php echo htmlspecialchars($mark['subject_name']); ?>"
data-toggle="modal"
data-target="#deleteMarksModal">

<i class="fas fa-trash"></i>

</button>
</td>
</tr>
                                <?php endforeach; ?>
                            <?php else: ?>
<tr>
<td>
No marks data found

</td>
</tr>
                            <?php endif; ?>
</tbody>
</table>
</div>
            </div>
        </div>
    </div>
</div>
</div> <!-- Edit Marks Modal -->
<div>
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
<h5>
Edit Marks

</h5>
<button>
<span>
×

</span>
</button>
</div>
<div>
            <!-- Edit form will be loaded here via AJAX -->
            <div id="editMarksFormContainer">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
<span>
Loading...

</span>
</div>
<p>
Loading...

</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div> <!-- Delete Marks Modal -->
<div>
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header bg-danger text-white">
<h5>
Confirm Delete

</h5>
<button>
<span>
×

</span>
</button>
</div>
<div>
<p>
Are you sure you want to delete these marks?

</p>
<p>
<strong>
Student:

</strong>
<span id="deleteStudentName"></span>

</p>
<p>
<strong>
Subject:

</strong>
<span id="deleteSubjectName"></span>

</p>
<p>
<strong>
Warning:

</strong>
This action cannot be undone and will affect the student's results.

</p>
            <form id="deleteMarksForm" method="POST" action="">
                <input type="hidden" name="marks_id" id="delete_marks_id" value="">
                <input type="hidden" name="student_id" id="delete_student_id" value="">
                <div class="text-right">
<button>
Cancel

</button>
<button>
Delete

</button>
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
    
    // Handle delete marks button click
    $(".delete-marks-btn").click(function() {
        var marksId = $(this).data('id');
        var studentId = $(this).closest('tr').find('td:eq(0)').text(); // This would need to be adjusted to get the actual student ID
        var studentName = $(this).data('student');
        var subjectName = $(this).data('subject');
        
        $("#delete_marks_id").val(marksId);
        $("#delete_student_id").val(studentId);
        $("#deleteStudentName").text(studentName);
        $("#deleteSubjectName").text(subjectName);
    });
    
    // For a complete implementation, you would add AJAX to load the edit form
    // This is a simplified version for this example
    $(".edit-marks-btn").click(function() {
        var marksId = $(this).data('id');
        // Here, you would typically use AJAX to load the edit form with the marks data
        // For this example, we'll just show a placeholder
        $("#editMarksFormContainer").html("
<p>
Edit form would load here via AJAX with marks ID: " + marksId + "

</p>
");
});
});

</script>
<?php // Include footer include_once '../includes/footer.php'; ?>