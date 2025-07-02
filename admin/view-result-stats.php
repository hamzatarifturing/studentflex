<?php // Start session and include database connection session_start(); include_once '../config/db.php'; // Check if user is logged in and is admin if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; } // Get total number of students $studentQuery = "SELECT COUNT(*) as student_count FROM students"; $studentResult = $conn->query($studentQuery); $studentData = $studentResult->fetch_assoc(); $totalStudents = $studentData['student_count']; // Get total number of exams conducted $examQuery = "SELECT COUNT(DISTINCT exam_id) as exam_count FROM marks"; $examResult = $conn->query($examQuery); $examData = $examResult->fetch_assoc(); $totalExams = $examData['exam_count']; // Get average percentage across all students and exams $avgQuery = "SELECT AVG((marks_obtained/marks_max)*100) as avg_percentage FROM marks"; $avgResult = $conn->query($avgQuery); $avgData = $avgResult->fetch_assoc(); $avgPercentage = number_format($avgData['avg_percentage'], 2); // Get count of students who have failed any exam (assuming grade 'F' means fail) $failQuery = "SELECT COUNT(DISTINCT student_id) as failed_count FROM marks WHERE grade = 'F'"; $failResult = $conn->query($failQuery); $failData = $failResult->fetch_assoc(); $failedStudents = $failData['failed_count']; // Get the page title $title = "Result Statistics"; include_once '../includes/header.php'; ?> <!-- Main Content -->
<div>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1>
Result Statistics

</h1>
</div>
<!-- Stats Cards -->
<div>
    <!-- Students Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Students
</div>
<div>
<?php echo $totalStudents; ?>
</div>
                    </div>
<div>
<i class="fas fa-users fa-2x text-gray-300"></i>

</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exams Card -->
<div>
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Exams Conducted
</div>
<div>
<?php echo $totalExams; ?>
</div>
                    </div>
<div>
<i class="fas fa-clipboard-list fa-2x text-gray-300"></i>

</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Average Percentage Card -->
<div>
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Average Percentage
</div>
<div>
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $avgPercentage; ?>%
</div>
                            </div>
<div>
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $avgPercentage; ?>%" 
                                         aria-valuenow="<?php echo $avgPercentage; ?>" aria-valuemin="0" 
                                         aria-valuemax="100">
</div>
                                </div>
                            </div>
                        </div>
                    </div>
<div>
<i class="fas fa-percent fa-2x text-gray-300"></i>

</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Failed Students Card -->
<div>
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Students with Failed Exams
</div>
<div>
<?php echo $failedStudents; ?>
</div>
                    </div>
<div>
<i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>

</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Information Box -->
<div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
<h6>
Results Overview

</h6>
</div>
<div>
<p>
Welcome to the Result Statistics Dashboard. Here you can see the current state of your academic records.

</p>
<p>
This dashboard provides an overview of the total number of students registered in the system, the total number of exams conducted to date, average performance metrics, and failure statistics.

</p>
<p>
The average percentage is calculated across all subjects and all students, providing a broad overview of overall academic performance.

</p>
<p>
The failed students card shows the number of unique students who have received an 'F' grade in at least one exam.

</p>
<p>
For more detailed analysis, use the other reports available in the admin section.

</p>
</div>
        </div>
    </div>
</div>
</div> <!-- End of Main Content --> <?php include_once '../includes/footer.php'; ?>
<!-- Interaction 4 Code but same because the models could not provide it in the correct format -->