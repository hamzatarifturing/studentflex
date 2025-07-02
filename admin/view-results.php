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
        // Assuming generateResult function exists elsewhere in the application
        if (function_exists('generateResult')) {
            generateResult($conn, $student_id, $exam_id);
        }
    } else {
        $error = "Error deleting marks: " . $conn->error;
    }
}

// Set page title
$page_title = 'View Results';

// Include header
include_once '../includes/header.php';
?>
<div>
<div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 bg-dark text-light py-3 sidebar">
<h5>
Admin Panel

</h5>
<ul>
<li>
<a>
Dashboard

</a>
</li>
<li>
<a>
Add Student

</a>
</li>
<li>
<a>
View Students

</a>
</li>
<li>
<a>
Add Subject

</a>
</li>
<li>
<a>
Add Marks

</a>
</li>
<li>
<a>
View Results

</a>
</li>
</ul>
</div>
    <!-- Main Content -->
<div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
<h5>
Student Marks Entries

</h5>
<a>
Add New Marks

</a>
</div>
<div>
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?>
</div>
                <?php endif; ?>

                <?php if (isset($success) && !empty($success)): ?>
<div>
<?php echo $success; ?>
</div>
                <?php endif; ?>
<div>
                    <table class="table table-bordered table-hover">
<thead>
<tr>
<th>
ID

</th>
<th>
Student Name

</th>
<th>
Student ID

</th>
<th>
Subject

</th>
<th>
Exam

</th>
<th>
Marks Obtained

</th>
<th>
Max Marks

</th>
<th>
Percentage

</th>
<th>
Grade

</th>
<th>
Remarks

</th>
<th>
Date Added

</th>
<th>
Actions

</th>
</tr>
</thead>
                        <tbody>
                            <?php
                            // Fetch all marks with student and subject information
                            $query = "SELECT m.id, m.student_id, m.subject_id, m.exam_id, 
                                      m.marks_obtained, m.marks_max, m.grade, m.remarks, 
                                      m.created_at, s.name AS student_name, s.student_id AS student_roll_id, 
                                      sub.name AS subject_name, e.name AS exam_name 
                                      FROM marks m 
                                      JOIN students s ON m.student_id = s.id 
                                      JOIN subjects sub ON m.subject_id = sub.id 
                                      JOIN exams e ON m.exam_id = e.id 
                                      ORDER BY m.created_at DESC";

                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Calculate percentage
                                    $percentage = ($row['marks_obtained'] / $row['marks_max']) * 100;
                                    ?>
                                    <tr>
<td>
<?php echo $row['id']; ?>
</td>
<td>
<?php echo htmlspecialchars($row['student_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($row['student_roll_id']); ?>
</td>
<td>
<?php echo htmlspecialchars($row['subject_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($row['exam_name']); ?>
</td>
<td>
<?php echo $row['marks_obtained']; ?>
</td>
<td>
<?php echo $row['marks_max']; ?>
</td>
<td>
<?php echo number_format($percentage, 2); ?>%
</td>
<td>
<span>
">
<?php echo $row['grade']; ?>

</span>
</td>
<td>
<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>
</td>
<td>
<?php echo date('d-m-Y', strtotime($row['created_at'])); ?>
</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
<a>
" class="btn btn-primary">

<i class="fa fa-edit"></i>

Edit

</a>
<button>
">

<i class="fa fa-trash"></i>

Delete

</button>
</div>
                                            <!-- Delete Marks Modal -->
<div>
" tabindex="-1" role="dialog" aria-labelledby="deleteMarksModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
<div class="modal-header">

<h5>
">Confirm Delete

</h5>
<button>
<span>
×

</span>
</button>
</div>
<div>
<p>
Are you sure you want to delete marks for:

</p>
<p>
<strong>
Student:

</strong>
<?php echo htmlspecialchars($row['student_name']); ?>
</p>
<p>
<strong>
Subject:

</strong>
<?php echo htmlspecialchars($row['subject_name']); ?>
</p>
<p>
<strong>
Exam:

</strong>
<?php echo htmlspecialchars($row['exam_name']); ?>
</p>
<p>
<strong>
Marks:

</strong>
<?php echo $row['marks_obtained']; ?> / <?php echo $row['marks_max']; ?>
</p>
<p>
This action cannot be undone!

</p>
</div>
<div>
<button>
Cancel

</button>
<form>
                                                                <input type="hidden" name="marks_id" value="<?php echo $row['id']; ?>">
                                                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                                                <input type="hidden" name="exam_id" value="<?php echo $row['exam_id']; ?>">
<button>
Delete

</button>
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
<td>
No mark entries found

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
</div> <?php include_once '../includes/footer.php'; ?>