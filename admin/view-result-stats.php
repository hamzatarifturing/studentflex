<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get total number of students
$studentQuery = "SELECT COUNT(*) as student_count FROM students";
$studentResult = $conn->query($studentQuery);
$studentData = $studentResult->fetch_assoc();
$totalStudents = $studentData['student_count'];

// Get total number of exams conducted
$examQuery = "SELECT COUNT(DISTINCT exam_id) as exam_count FROM marks";
$examResult = $conn->query($examQuery);
$examData = $examResult->fetch_assoc();
$totalExams = $examData['exam_count'];

// Get the page title
$title = "Result Statistics";
include_once '../includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Result Statistics</h1>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <!-- Students Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exams Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Exams Conducted
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalExams; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Box -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Results Overview</h6>
                </div>
                <div class="card-body">
                    <p>Welcome to the Result Statistics Dashboard. Here you can see the current state of your academic records.</p>
                    <p>This dashboard provides an overview of the total number of students registered in the system and the total number of exams conducted to date.</p>
                    <p>For more detailed analysis, use the other reports available in the admin section.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<?php include_once '../includes/footer.php'; ?>