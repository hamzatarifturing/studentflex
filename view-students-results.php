<?php
// view-students-results.php
// Public-facing page for students to view their results without login required

// Include database connection
include 'config/db.php';

// Process form submission
$student = null;
$exams = null;
$marks = null;
$results = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    // Sanitize the input to prevent SQL injection
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    // Query to get student information
    $query = "SELECT s.*, u.full_name, u.email, u.username, u.role, u.status
              FROM students s
              JOIN users u ON s.user_id = u.id
              WHERE s.student_id = '$student_id'";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
        
        // Fetch all exams for this student's class
        $examsQuery = "SELECT * FROM exams WHERE class = '{$student['class']}' AND is_active = 'yes' ORDER BY start_date DESC";
        $examsResult = mysqli_query($conn, $examsQuery);
        
        if ($examsResult && mysqli_num_rows($examsResult) > 0) {
            $exams = [];
            while ($exam = mysqli_fetch_assoc($examsResult)) {
                $exams[] = $exam;
            }
        }
        
        // Fetch all marks for this student
        $marksQuery = "SELECT m.*, s.subject_name, s.subject_code, e.exam_name, e.max_marks as exam_max_marks
                      FROM marks m
                      JOIN subjects s ON m.subject_id = s.id
                      JOIN exams e ON m.exam_id = e.id
                      WHERE m.student_id = {$student['id']}
                      ORDER BY e.id DESC, s.subject_name ASC";
        $marksResult = mysqli_query($conn, $marksQuery);
        
        if ($marksResult && mysqli_num_rows($marksResult) > 0) {
            $marks = [];
            while ($mark = mysqli_fetch_assoc($marksResult)) {
                // Group marks by exam_id
                if (!isset($marks[$mark['exam_id']])) {
                    $marks[$mark['exam_id']] = [
                        'exam_name' => $mark['exam_name'],
                        'subjects' => []
                    ];
                }
                $marks[$mark['exam_id']]['subjects'][] = $mark;
            }
        }
        
        // Fetch overall results for this student
        $resultsQuery = "SELECT r.*, e.exam_name
                        FROM results r
                        JOIN exams e ON r.exam_id = e.id
                        WHERE r.student_id = {$student['id']} AND r.is_published = 'yes'
                        ORDER BY e.start_date DESC";
        $resultsResult = mysqli_query($conn, $resultsQuery);
        
        if ($resultsResult && mysqli_num_rows($resultsResult) > 0) {
            $results = [];
            while ($result = mysqli_fetch_assoc($resultsResult)) {
                $results[$result['exam_id']] = $result;
            }
        }
    } else {
        $error = "No student found with ID: $student_id";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - StudentFlex</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS styling for the results page */
        #result-form-container {
            display: <?php echo (isset($_POST['student_id']) && !$student) ? 'block' : 'none'; ?>;
            transition: all 0.3s ease-in-out;
        }
        .animate-fade {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        /* Print styling */
        @media print {
            body {
                font-size: 12pt;
                background-color: white !important;
            }
            
            nav, footer, .btn, #check-results-btn, #result-form-container, 
            .hide-details-btn, #cancel-search, .row:not(.student-info-row):not(.results-row) {
                display: none !important;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
            
            .card-header {
                background-color: #f8f9fa !important;
                color: #333 !important;
                padding: 10px 15px !important;
            }
            
            .table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .table th, .table td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
            }
            
            .badge {
                border: 1px solid #333;
                padding: 2px 5px;
                font-weight: normal;
            }
            
            .subject-details {
                display: table-row !important;
            }
            
            .bg-success, .bg-primary, .bg-info, .bg-warning, .bg-danger {
                background-color: white !important;
                color: black !important;
                border: 1px solid #ddd !important;
            }
            
            /* Add school letterhead style */
            .print-header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 15px;
                display: block !important;
            }
            
            .print-header-hidden {
                display: block !important;
            }
            
            .print-footer {
                text-align: center;
                border-top: 1px solid #333;
                padding-top: 10px;
                margin-top: 20px;
                font-size: 10pt;
                display: block !important;
            }
        }
        
        /* Hide print-only elements when not printing */
        .print-header-hidden {
            display: none;
        }
        
        .print-footer {
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>StudentFlex
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view-students-results.php">Check Results</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-search me-2"></i>Student Result Portal</h4>
                    </div>
                    <div class="card-body">
                        <div class="print-footer">
                            <p>This is an official result document from StudentFlex. For any inquiries, please contact the administration.</p>
                            <p>© <?php echo date('Y'); ?> StudentFlex - All Rights Reserved</p>
                        </div>
                        
                        <div id="result-form-container" style="display: <?php echo (!$student && !isset($_POST['student_id'])) ? 'block' : 'none'; ?>">
                            <p class="lead">Enter your Student ID to view your academic results.</p>
                            
                            <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="post" class="mt-4">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Student ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your Student ID" required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> View Results
                                        </button>
                                    </div>
                                    <div class="form-text">Example: STU12345 or your registered student identification number</div>
                                </div>
                            </form>
                        </div>
                        
                        <div id="student-found" style="display: <?php echo ($student) ? 'block' : 'none'; ?>">
                            <button id="check-results-btn" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-search me-1"></i> Check Another Student's Result
                            </button>
                            
                            <button class="btn btn-outline-success mb-3 float-end" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Result
                            </button>
                            
                            <!-- Student Information Card -->
                            <?php if ($student): ?>
                            <div class="card mb-4 animate-fade">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-graduate me-2"></i>
                                        Student Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="print-header print-header-hidden">
                                        <h2>StudentFlex Academic Report</h2>
                                        <h4>School/College Name</h4>
                                        <p>Academic Year: 2023-2024</p>
                                    </div>
                                    
                                    <div class="row student-info-row">
                                        <div class="col-md-6">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                            <p><strong>Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-<?php echo ($student['status'] == 'active') ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exam Results Cards -->
                            <?php if (isset($marks) && !empty($marks)): ?>
                                <?php foreach ($marks as $examId => $examData): ?>
                                <div class="card mb-4 animate-fade results-row">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-file-alt me-2"></i>
                                            <?php echo htmlspecialchars($examData['exam_name']); ?> Results
                                        </h5>
                                        <?php if (isset($results[$examId])): ?>
                                        <div class="badge bg-light text-dark">
                                            Overall: <?php echo $results[$examId]['percentage']; ?>% | 
                                            Grade: <?php echo $results[$examId]['grade']; ?> |
                                            Rank: <?php echo $results[$examId]['rank']; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Subject Code</th>
                                                        <th>Subject</th>
                                                        <th>Marks</th>
                                                        <th>Max Marks</th>
                                                        <th>Percentage</th>
                                                        <th>Grade</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($examData['subjects'])): ?>
                                                        <?php $count = 1; $totalMarks = 0; $totalMaxMarks = 0; ?>
                                                        <?php foreach ($examData['subjects'] as $subject): ?>
                                                            <?php 
                                                                $percentage = ($subject['marks_obtained'] / $subject['marks_max']) * 100;
                                                                $totalMarks += $subject['marks_obtained'];
                                                                $totalMaxMarks += $subject['marks_max'];
                                                                
                                                                // Determine status
                                                                $status = ($percentage >= 40) ? 'Pass' : 'Fail';
                                                                $statusClass = ($status == 'Pass') ? 'success' : 'danger';
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $count++; ?></td>
                                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                                <td><?php echo $subject['marks_obtained']; ?></td>
                                                                <td><?php echo $subject['marks_max']; ?></td>
                                                                <td><?php echo number_format($percentage, 2); ?>%</td>
                                                                <td><?php echo $subject['grade']; ?></td>
                                                                <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr class="table-info fw-bold">
                                                            <td colspan="3">Total</td>
                                                            <td><?php echo $totalMarks; ?></td>
                                                            <td><?php echo $totalMaxMarks; ?></td>
                                                            <td><?php echo number_format(($totalMarks / $totalMaxMarks) * 100, 2); ?>%</td>
                                                            <td colspan="2">
                                                                <?php if (isset($results[$examId])): ?>
                                                                    <span class="badge bg-<?php echo ($results[$examId]['result_status'] == 'pass') ? 'success' : 'danger'; ?>">
                                                                        <?php echo ucfirst($results[$examId]['result_status']); ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">Pending</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No subject marks available for this exam</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <?php if (isset($results[$examId]) && !empty($results[$examId]['remarks'])): ?>
                                            <div class="mt-3">
                                                <h6 class="fw-bold">Remarks:</h6>
                                                <div class="p-2 border rounded bg-light">
                                                    <?php echo nl2br(htmlspecialchars($results[$examId]['remarks'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No exam results found for this student. Results may not be published yet.
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    ]<!-- Student Performance Summary Card -->
<div class="">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Performance Summary
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($marks) && !empty($marks)): ?>
                        <?php
                            // Initialize variables to track max and min marks
                            $maxMark = 0;
                            $maxSubject = '';
                            $maxPercentage = 0;
                            $maxExam = '';
                            
                            $minMark = 101; // Start with value higher than possible
                            $minSubject = '';
                            $minPercentage = 100;
                            $minExam = '';
                            
                            $overallTotal = 0;
                            $overallMax = 0;
                            $examCount = 0;
                            
                            // Loop through all exams and subjects to find max and min
                            foreach ($marks as $examId => $examData) {
                                foreach ($examData['subjects'] as $subject) {
                                    $percentage = ($subject['marks_obtained'] / $subject['marks_max']) * 100;
                                    
                                    // Check for max mark
                                    if ($percentage > $maxPercentage) {
                                        $maxMark = $subject['marks_obtained'];
                                        $maxSubject = $subject['subject_name'];
                                        $maxPercentage = $percentage;
                                        $maxExam = $examData['exam_name'];
                                    }
                                    
                                    // Check for min mark
                                    if ($percentage < $minPercentage) {
                                        $minMark = $subject['marks_obtained'];
                                        $minSubject = $subject['subject_name'];
                                        $minPercentage = $percentage;
                                        $minExam = $examData['exam_name'];
                                    }
                                }
                                
                                // Calculate overall statistics for this exam
                                if (isset($results[$examId])) {
                                    $overallTotal += $results[$examId]['percentage'];
                                    $examCount++;
                                }
                            }
                            
                            // Calculate average percentage if we have exams
                            $averagePercentage = ($examCount > 0) ? ($overallTotal / $examCount) : 0;
                        ?>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-award me-2"></i>Highest Performance
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($maxSubject); ?></h5>
                                        <p class="card-text">
                                            <strong>Score:</strong> <?php echo $maxMark; ?><br>
                                            <strong>Percentage:</strong> <?php echo number_format($maxPercentage, 2); ?>%<br>
                                            <strong>Exam:</strong> <?php echo htmlspecialchars($maxExam); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <i class="fas fa-chart-pie me-2"></i>Average Performance
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">Overall Average</h5>
                                        <p class="card-text">
                                            <strong>Average:</strong> <?php echo number_format($averagePercentage, 2); ?>%<br>
                                            <strong>Exams Taken:</strong> <?php echo $examCount; ?><br>
                                            <strong>Performance Level:</strong>
                                            <?php
                                                if ($averagePercentage >= 80) echo '<span class="badge bg-success">Excellent</span>';
                                                elseif ($averagePercentage >= 65) echo '<span class="badge bg-primary">Good</span>';
                                                elseif ($averagePercentage >= 50) echo '<span class="badge bg-info">Satisfactory</span>';
                                                elseif ($averagePercentage >= 40) echo '<span class="badge bg-warning">Average</span>';
                                                else echo '<span class="badge bg-danger">Below Average</span>';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Lowest Performance
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($minSubject); ?></h5>
                                        <p class="card-text">
                                            <strong>Score:</strong> <?php echo $minMark; ?><br>
                                            <strong>Percentage:</strong> <?php echo number_format($minPercentage, 2); ?>%<br>
                                            <strong>Exam:</strong> <?php echo htmlspecialchars($minExam); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6 class="fw-bold">Improvement Areas:</h6>
                            <div class="border rounded p-3 bg-light">
                                <?php if ($minPercentage < 40): ?>
                                    <p class="mb-0">
                                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                        Focus on improving performance in <strong><?php echo htmlspecialchars($minSubject); ?></strong>. 
                                        Consider dedicating more study time to this subject or seeking additional help.
                                    </p>
                                <?php elseif ($minPercentage < 60): ?>
                                    <p class="mb-0">
                                        <i class="fas fa-info-circle text-warning me-2"></i>
                                        Your performance in <strong><?php echo htmlspecialchars($minSubject); ?></strong> could use some improvement.
                                        Review the subject material and practice regularly.
                                    </p>
                                <?php else: ?>
                                    <p class="mb-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        You're performing well across all subjects. Continue maintaining consistent study habits.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No performance data available. Results may not be published yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Student Performance Summary Card -->

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>StudentFlex</h5>
                    <p class="small">An advanced student result management system designed for educational institutions.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="view-students-results.php" class="text-white">Results</a></li>
                        <li><a href="login.php" class="text-white">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i>support@studentflex.com</li>
                        <li><i class="fas fa-phone me-2"></i>(123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <p class="small mb-0">&copy; <?php echo date('Y'); ?> StudentFlex - All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Button to show the search form again
        document.getElementById('check-results-btn')?.addEventListener('click', function() {
            document.getElementById('student-found').style.display = 'none';
            document.getElementById('result-form-container').style.display = 'block';
        });
    });
    </script>
</body>
</html>