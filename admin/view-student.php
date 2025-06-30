<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No student ID provided";
    header('Location: add-student.php');
    exit;
}

// Get student details from database
$id = intval($_GET['id']);
$sql = "SELECT u.*, s.* FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.id = ? AND u.role = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if student exists
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Student not found";
    header('Location: add-student.php');
    exit;
}

// Fetch student data
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
View Student Details - StudentFlex

</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
<?php include_once '../includes/header.php'; ?>
<div>
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
<strong>
Success!

</strong>
<?php echo $_SESSION['success']; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
<div>
<strong>
Error!

</strong>
<?php echo $_SESSION['error']; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Breadcrumb Navigation -->
<nav>
<ol>
<li>
<a>
Dashboard

</a>
</li>
<li>
<a>
Students Management

</a>
</li>
<li>
View Student

</li>
</ol>
</nav>
<div>
        <div class="card-header d-flex justify-content-between align-items-center">
<h5>
<i class="fas fa-user-graduate mr-2"></i>

Student Details

</h5>
<a>
<i class="fas fa-arrow-left mr-1"></i>

Back to Students

</a>
</div>
<div>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
<h5>
Personal Information

</h5>
</div>
<div>
                            <div class="text-center mb-3">
                                <div class="profile-avatar">
<i class="fas fa-user-circle fa-5x text-secondary"></i>

</div>
<h4>
<?php echo htmlspecialchars($student['full_name']); ?>
</h4>
<span>
">
<?php echo ucfirst($student['status']); ?>

</span>
                            </div>
                            <hr>
<div>
                                <div class="info-item">
<span>
Username:

</span>
<span>
<?php echo htmlspecialchars($student['username']); ?>
</span>
</div>
<div>
<span>
Email:

</span>
<span>
<?php echo htmlspecialchars($student['email']); ?>
</span>
</div>
<div>
<span>
Account Created:

</span>
<span>
<?php echo date('M d, Y', strtotime($student['created_at'])); ?>
</span>
</div>
<div>
<span>
Last Updated:

</span>
<span>
<?php echo date('M d, Y', strtotime($student['updated_at'])); ?>
</span>
</div>
                            </div>
                        </div>
                    </div>
                </div>
<div>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
<h5>
Academic Information

</h5>
</div>
<div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-block">
<h6>
Student ID

</h6>
                                        <div class="d-flex align-items-center">
<i class="fas fa-id-card fa-2x text-primary mr-2"></i>

<span>
<?php echo htmlspecialchars($student['student_id']); ?>
</span>
</div>
                                    </div>
                                </div>
<div>
                                    <div class="info-block">
<h6>
Class

</h6>
                                        <div class="d-flex align-items-center">
<i class="fas fa-chalkboard fa-2x text-primary mr-2"></i>

<span>
Class <?php echo htmlspecialchars($student['class']); ?>

</span>
</div>
                                    </div>
                                </div>
                            </div>
<div>
                                <div class="col-md-6">
                                    <div class="info-block">
<h6>
Section

</h6>
                                        <div class="d-flex align-items-center">
<i class="fas fa-users fa-2x text-primary mr-2"></i>

<span>
Section <?php echo htmlspecialchars($student['section']); ?>

</span>
</div>
                                    </div>
                                </div>
<div>
                                    <div class="info-block">
<h6>
Status

</h6>
                                        <div class="d-flex align-items-center">
<i class="fas fa-user-check fa-2x text-primary mr-2"></i>

<span>
                                                <span class="badge badge-<?php echo $student['status'] == 'active' ? 'success' : 'danger'; ?> p-2">
<i>
mr-1">

</i>
                                                    <?php echo ucfirst($student['status']); ?>
</span>
                                            </span>
</div>
                                    </div>
                                </div>
                            </div>

                            <hr>
<div>
                                <div class="col-12">
<h6>
Student Actions

</h6>
<a>
" class="btn btn-warning">

<i class="fas fa-edit mr-1"></i>

Edit Student

</a>
<button>
<i class="fas fa-trash mr-1"></i>

Delete Student

</button>
<a>
" class="btn btn-info">

<i class="fas fa-chart-bar mr-1"></i>

View Results

</a>
<button>
<i class="fas fa-plus-circle mr-1"></i>

Add Result

</button>
</div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional details or tabs can be added here -->
<div>
                        <div class="card-header bg-primary text-white">
<h5>
Recent Activity

</h5>
</div>
<div>
<p>
No recent activity found for this student.

</p>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Student Modal -->
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
Are you sure you want to delete the following student?

</p>
<p>
<strong>
Name:

</strong>
<?php echo htmlspecialchars($student['full_name']); ?>
</p>
<p>
<strong>
Student ID:

</strong>
<?php echo htmlspecialchars($student['student_id']); ?>
</p>
<p>
<strong>
Warning:

</strong>
This action cannot be undone. All related data will be permanently deleted.

</p>
</div>
<div>
<button>
Cancel

</button>
<form>
                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
<button>
Delete Student

</button>
</form>
</div>
        </div>
    </div>
</div>

<!-- Add Result Modal -->
<div>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
<h5>
Add New Result

</h5>
<button>
<span>
×

</span>
</button>
</div>
<div>
<p>
Add a new result for

<strong>
<?php echo htmlspecialchars($student['full_name']); ?>
</strong>
</p>
<p>
Fill in the details below to add a new academic result.

</p>
                <!-- Form will be implemented in the future -->
                <form action="add-result.php" method="POST" id="addResultForm">
                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                    
                    <div class="form-group">
<label>
Exam Type

</label>
<select>
<option>
Select Exam Type

</option>
<option>
Midterm

</option>
<option>
Final

</option>
<option>
Quiz

</option>
<option>
Assignment

</option>
</select>
</div>
<div>
<label>
Subject

</label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject name" required>
</div>
<div>
<label>
Marks Obtained

</label>
                        <input type="number" class="form-control" id="marks_obtained" name="marks_obtained" placeholder="Enter marks obtained" required>
</div>
<div>
<label>
Total Marks

</label>
                        <input type="number" class="form-control" id="total_marks" name="total_marks" placeholder="Enter total marks" required>
</div>
                </form>
            </div>
<div>
<button>
Cancel

</button>
<button>
Add Result

</button>
</div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<!-- jQuery, Bootstrap JS and other scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Auto hide alerts after 5 seconds
    $(document).ready(function(){
        setTimeout(function(){
            $(".alert").alert('close');
        }, 5000);
    });
</script>
</body>
</html>