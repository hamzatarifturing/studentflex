<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Process delete marks request if it exists
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
        
        // Placeholder rank (would need a more complex algorithm for actual ranking)
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
            $stmt->bind_param("iidddsiss", $student_id, $exam_id, $total_obtained, 
                             $total_max, $percentage, $grade, $rank, 
                             $result_status, $is_published);
            $stmt->execute();
        }
    } else {
        // If no marks, delete any existing results
        $stmt = $conn->prepare("DELETE FROM results WHERE student_id = ? AND exam_id = ?");
        $stmt->bind_param("ii", $student_id, $exam_id);
        $stmt->execute();
    }
}

// Get the selected student filter if set
$selected_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Set page title
$page_title = 'View Results';

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-dark text-light py-3 sidebar">
            <h5>Admin Panel</h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-light" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="add-student.php">Add Student</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="view-student.php">View Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="add-subject.php">Add Subject</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="add-marks.php">Add Marks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light active" href="view-results.php">View Results</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 py-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Student Marks Entries</h5>
                    <a href="add-marks.php" class="btn btn-primary">Add New Marks</a>
                </div>
                <div class="card-body">
                    <?php if (isset($error) && !empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($success) && !empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- Student Filter Form -->
                    <div class="mb-4">
                        <form method="GET" action="" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="student_id" class="mr-2">Filter by Student:</label>
                                <select class="form-control" id="student_id" name="student_id" onchange="this.form.submit()">
                                    <option value="0">All Students</option>
                                    <?php
                                    // Get all students
                                    $student_query = "SELECT s.id, s.student_id AS roll_number, u.full_name 
                                                     FROM students s 
                                                     JOIN users u ON s.user_id = u.id 
                                                     ORDER BY u.full_name";
                                    $student_result = $conn->query($student_query);
                                    
                                    if ($student_result && $student_result->num_rows > 0) {
                                        while ($student = $student_result->fetch_assoc()) {
                                            $selected = ($selected_student == $student['id']) ? 'selected' : '';
                                            echo '<option value="' . $student['id'] . '" ' . $selected . '>' . 
                                                 htmlspecialchars($student['full_name']) . ' (' . 
                                                 htmlspecialchars($student['roll_number']) . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php if ($selected_student > 0): ?>
                                <a href="view-results.php" class="btn btn-secondary">Clear Filter</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Student Name</th>
                                    <th>Username</th>
                                    <th>Student ID</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks Obtained</th>
                                    <th>Max Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Prepare the base query
                                $base_query = "SELECT m.id, m.student_id, m.subject_id, m.exam_id, 
                                         m.marks_obtained, m.marks_max, m.grade, m.remarks, 
                                         m.created_at, u.full_name AS student_name, u.username,
                                         s.student_id AS student_roll_id, s.class as student_class,
                                         sub.subject_name, e.exam_name 
                                         FROM marks m 
                                         JOIN students s ON m.student_id = s.id 
                                         JOIN users u ON s.user_id = u.id
                                         JOIN subjects sub ON m.subject_id = sub.id 
                                         JOIN exams e ON m.exam_id = e.id";
                                
                                // Add WHERE clause if a student is selected
                                if ($selected_student > 0) {
                                    $query = $base_query . " WHERE m.student_id = " . $selected_student;
                                } else {
                                    $query = $base_query;
                                }
                                
                                // Add ORDER BY clause
                                $query .= " ORDER BY m.created_at DESC";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Calculate percentage
                                        $percentage = ($row['marks_obtained'] / $row['marks_max']) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['student_roll_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['student_class']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo $row['marks_obtained']; ?></td>
                                            <td><?php echo $row['marks_max']; ?></td>
                                            <td><?php echo number_format($percentage, 2); ?>%</td>
                                            <td>
                                                <?php 
                                                $gradeBadgeClass = 'badge-secondary';
                                                if ($row['grade'] == 'A+' || $row['grade'] == 'A') {
                                                    $gradeBadgeClass = 'badge-success';
                                                } elseif ($row['grade'] == 'B') {
                                                    $gradeBadgeClass = 'badge-info';
                                                } elseif ($row['grade'] == 'C') {
                                                    $gradeBadgeClass = 'badge-warning';
                                                } elseif ($row['grade'] == 'D') {
                                                    $gradeBadgeClass = 'badge-secondary';
                                                } else {
                                                    $gradeBadgeClass = 'badge-danger';
                                                }
                                                ?>
                                                <span class="badge <?php echo $gradeBadgeClass; ?>">
                                                    <?php echo $row['grade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['remarks'] ? $row['remarks'] : ''); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="add-marks.php?edit=<?php echo $row['id']; ?>" class="btn btn-primary">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteMarksModal<?php echo $row['id']; ?>">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Marks Modal -->
                                                <div class="modal fade" id="deleteMarksModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteMarksModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteMarksModalLabel<?php echo $row['id']; ?>">Confirm Delete</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete marks for:</p>
                                                                <p><strong>Student:</strong> <?php echo htmlspecialchars($row['student_name']); ?></p>
                                                                <p><strong>Username:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                                                                <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?></p>
                                                                <p><strong>Exam:</strong> <?php echo htmlspecialchars($row['exam_name']); ?></p>
                                                                <p><strong>Marks:</strong> <?php echo $row['marks_obtained']; ?> / <?php echo $row['marks_max']; ?></p>
                                                                <p class="text-danger">This action cannot be undone!</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="marks_id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                                                    <input type="hidden" name="exam_id" value="<?php echo $row['exam_id']; ?>">
                                                                    <button type="submit" name="delete_marks" class="btn btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="14" class="text-center">
                                            <?php 
                                            if ($selected_student > 0) {
                                                echo "No mark entries found for the selected student.";
                                            } else {
                                                echo "No mark entries found.";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>