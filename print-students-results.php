<?php
// Include database connection
require_once 'config/db.php';

// Initialize variables
$studentData = null;
$resultData = null;
$examData = null;
$errorMessage = "";
$showResults = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $studentId = isset($_POST['studentId']) ? trim($_POST['studentId']) : '';
    $resultMonth = isset($_POST['resultMonth']) ? trim($_POST['resultMonth']) : '';
    $resultYear = isset($_POST['resultYear']) ? trim($_POST['resultYear']) : '';
    
    // Validate inputs - only studentId is required
    if (empty($studentId)) {
        $errorMessage = "Student ID is required!";
    } else {
        // Fetch student information (join with users table to get the full name)
        $studentQuery = "SELECT s.*, u.full_name, u.email 
                         FROM students s 
                         JOIN users u ON s.user_id = u.id 
                         WHERE s.student_id = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        
        if ($studentResult->num_rows > 0) {
            $studentData = $studentResult->fetch_assoc();
            
            // Determine if we should filter by month and year
            $filterByMonthYear = !empty($resultMonth) && !empty($resultYear);
            
            if ($filterByMonthYear) {
                // Find exams that match the month and year for this student's class
                $examQuery = "SELECT * FROM exams 
                            WHERE MONTH(start_date) = ? 
                            AND YEAR(start_date) = ? 
                            AND class = ?";
                
                // Convert month name to number - PHP 5 compatible
                $monthNumber = date('m', strtotime($resultMonth . ' 1, ' . $resultYear));
                
                $stmt = $conn->prepare($examQuery);
                $stmt->bind_param("sss", $monthNumber, $resultYear, $studentData['class']);
            } else {
                // Get all exams for this student's class
                $examQuery = "SELECT * FROM exams 
                            WHERE class = ? 
                            ORDER BY start_date DESC";
                
                $stmt = $conn->prepare($examQuery);
                $stmt->bind_param("s", $studentData['class']);
            }
            
            $stmt->execute();
            $examResult = $stmt->get_result();
            
            if ($examResult->num_rows > 0) {
                // We'll store all exam data with their respective results
                $allExams = array();
                $allResults = array();
                $showResults = true;
                
                // Process each exam
                while ($examRow = $examResult->fetch_assoc()) {
                    $examData = $examRow;
                    
                    // Fetch subject marks for this student and exam
                    $marksQuery = "SELECT m.*, s.subject_name, s.subject_code 
                                FROM marks m 
                                JOIN subjects s ON m.subject_id = s.id 
                                WHERE m.student_id = ? 
                                AND m.exam_id = ?";
                    
                    $stmt = $conn->prepare($marksQuery);
                    $stmt->bind_param("ii", $studentData['id'], $examData['id']);
                    $stmt->execute();
                    $resultData = $stmt->get_result();
                    
                    // Check if overall result exists for this student and exam
                    $overallQuery = "SELECT * FROM results 
                                    WHERE student_id = ? 
                                    AND exam_id = ? 
                                    AND is_published = 'yes'";
                    
                    $stmt = $conn->prepare($overallQuery);
                    $stmt->bind_param("ii", $studentData['id'], $examData['id']);
                    $stmt->execute();
                    $overallResult = $stmt->get_result();
                    
                    if ($resultData->num_rows > 0 && $overallResult->num_rows > 0) {
                        // Store this exam's data and results
                        $allExams[] = $examData;
                        
                        // Fetch all subject marks and store them
                        $subjectResults = array();
                        while ($markRow = $resultData->fetch_assoc()) {
                            $subjectResults[] = $markRow;
                        }
                        
                        $allResults[$examData['id']] = array(
                            'marks' => $subjectResults,
                            'overall' => $overallResult->fetch_assoc()
                        );
                    }
                }
                
                if (empty($allExams)) {
                    $errorMessage = "No published results found for this student.";
                    $showResults = false;
                }
            } else {
                $errorMessage = $filterByMonthYear ? 
                                "No exam found for the selected month and year." : 
                                "No exams found for this student's class.";
            }
        } else {
            $errorMessage = "Student ID not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Student Results - StudentFlex</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .search-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .error-message {
            color: #f44336;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .results-section {
            margin-top: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: white;
        }
        
        .student-info {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .student-info h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .info-row {
            display: flex;
            flex-wrap: wrap;
        }
        
        .info-item {
            margin-right: 30px;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .results-table th, 
        .results-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .results-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .results-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .print-btn {
            margin-top: 20px;
            background-color: #2196F3;
            color: white;
        }
        
        .print-btn:hover {
            background-color: #0b7dda;
        }
        
        .remarks-section, .rank-section, .published-date {
            margin-top: 20px;
        }
        
        .remarks-section h4, .rank-section h4 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .published-date {
            font-style: italic;
            color: #666;
            font-size: 0.9em;
            text-align: right;
        }
        
        .result-summary {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .failed-subject {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        @media print {
            .search-section, .print-btn, header, footer {
                display: none;
            }
            
            .results-section {
                border: none;
                padding: 0;
                margin-top: 0;
            }
            
            body {
                font-size: 12px;
            }
            
            .student-info {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            
            .info-item {
                margin-right: 20px;
                margin-bottom: 5px;
            }
            
            /* Ensure failed subjects remain highlighted in print */
            .failed-subject {
                background-color: #ffebee !important;
                color: #d32f2f !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            @page {
                margin: 1cm;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>StudentFlex - Student Result Management System</h1>
        </header>
        
        <main>
        <section class="welcome-section">
                <h2>Welcome to Student Results Portal</h2>
                <p>This page allows you to access and print academic results for any student.</p>
                <p>You can search for a student's results using their student ID, and optionally filter by month and year.</p>
                <div class="info-box">
                    <h3>How to use this page:</h3>
                    <p>1. Enter the student's ID in the search field (required)</p>
                    <p>2. Optionally select the month and year to filter results</p>
                    <p>3. Leave month and year empty to see ALL results for the student</p>
                    <p>4. Click the "Search" button to find the results</p>
                    <p>5. Review the displayed results and print as needed</p>
                </div>
            </section>
            
            <section class="search-section">
                <h3>Search Student Results</h3>
                
                <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <?php echo $errorMessage; ?>
                </div>
                <?php endif; ?>
                
                <form id="resultSearchForm" method="post" action="">
                <div class="form-group">
                        <label for="studentId">Student ID:</label>
                        <input type="text" id="studentId" name="studentId" placeholder="Enter Student ID" required value="<?php 
                            if (isset($_POST['studentId'])) {
                                echo htmlspecialchars($_POST['studentId']);
                            } else {
                                echo '';
                            }
                        ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="resultMonth">Month (Optional):</label>
                        <select id="resultMonth" name="resultMonth">
                            <option value="">All Months</option>
                            <?php
                            $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
                            foreach ($months as $month) {
                                $selected = (isset($_POST['resultMonth']) && $_POST['resultMonth'] == $month) ? 'selected' : '';
                                echo "<option value=\"" . $month . "\" " . $selected . ">" . $month . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="resultYear">Year (Optional):</label>
                        <select id="resultYear" name="resultYear">
                            <option value="">All Years</option>
                            <?php
                            $years = array('2023', '2024', '2025');
                            foreach ($years as $year) {
                                $selected = (isset($_POST['resultYear']) && $_POST['resultYear'] == $year) ? 'selected' : '';
                                echo "<option value=\"" . $year . "\" " . $selected . ">" . $year . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Search Results</button>
                    </div>
                </form>
            </section>
            <!-- Real-time Clock -->
<div class="real-time-clock" style="text-align: right; font-size: 14px; margin: 10px 0; font-weight: bold;">
    <span id="current-date"><?php echo date('l, F d, Y'); ?></span>
    <span id="current-time"><?php echo date('h:i:s A'); ?></span>
</div>

<script type="text/javascript">
    // Update the clock every second
    function updateClock() {
        var now = new Date();
        
        // Format date: Weekday, Month Day, Year
        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        var dayName = days[now.getDay()];
        var monthName = months[now.getMonth()];
        var day = now.getDate();
        var year = now.getFullYear();
        
        var dateString = dayName + ', ' + monthName + ' ' + day + ', ' + year;
        
        // Format time: Hours:Minutes:Seconds AM/PM
        var hours = now.getHours();
        var minutes = now.getMinutes();
        var seconds = now.getSeconds();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // Convert 0 to 12
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        var timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
        
        // Update DOM elements
        document.getElementById('current-date').textContent = dateString;
        document.getElementById('current-time').textContent = timeString;
        
        // Call this function again in 1 second
        setTimeout(updateClock, 1000);
    }
    
    // Start the clock
    window.onload = function() {
        updateClock();
    };
</script>
            <?php if ($showResults && $studentData && !empty($allExams)): ?>
            <section class="results-section" id="results">
                <div class="student-info">
                    <h3>Student Information</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Student ID:</span>
                            <span><?php echo htmlspecialchars($studentData['student_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span><?php echo htmlspecialchars($studentData['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span><?php echo htmlspecialchars($studentData['email']); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Class:</span>
                            <span><?php echo htmlspecialchars($studentData['class']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Section:</span>
                            <span><?php echo htmlspecialchars($studentData['section']); ?></span>
                        </div>
                    </div>
                </div>
                
                <button onclick="window.print();" class="btn print-btn">Print All Results</button>
                
                <?php foreach ($allExams as $exam): 
                    // Get the results for this exam
                    $currentExamResults = $allResults[$exam['id']];
                    $examMarks = $currentExamResults['marks'];
                    $examOverallData = $currentExamResults['overall'];
                ?>
                
                <div class="exam-result-container" style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                    <h3>Exam: <?php echo htmlspecialchars($exam['exam_name']); ?></h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Exam Period:</span>
                            <span><?php echo htmlspecialchars(date('d M, Y', strtotime($exam['start_date']))) . ' - ' . 
                                        htmlspecialchars(date('d M, Y', strtotime($exam['end_date']))); ?></span>
                        </div>
                    </div>
                    
                    <h4 style="margin-top: 15px;">Results</h4>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject</th>
                                <th>Marks Obtained</th>
                                <th>Max Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                           foreach ($examMarks as $row): 
                            $percentage = ($row['marks_obtained'] / $row['marks_max']) * 100;
                            $grade = $row['grade'];
                            $isFailedSubject = ($grade === 'F' || $grade === 'f');
                        ?>
                        <tr class="<?php echo $isFailedSubject ? 'failed-subject' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['marks_obtained']); ?></td>
                            <td><?php echo htmlspecialchars($row['marks_max']); ?></td>
                            <td><?php echo number_format($percentage, 2) . '%'; ?></td>
                            <td><?php echo htmlspecialchars($grade); ?></td>
                            <td><?php echo htmlspecialchars(isset($row['remarks']) ? $row['remarks'] : ''); ?></td>
                        </tr>
                            <?php endforeach; ?>
                            
                            <!-- Overall Result Summary -->
                            <?php 
                            $overallGrade = $examOverallData['grade'];
                            $isOverallFailed = ($examOverallData['result_status'] === 'fail' || $overallGrade === 'F' || $overallGrade === 'f');
                            $rowClasses = "result-summary" . ($isOverallFailed ? ' failed-subject' : '');
                            ?>
                            <tr class="<?php echo $rowClasses; ?>">
                                <td colspan="2"><strong>Overall Result</strong></td>
                                <td><strong><?php echo number_format($examOverallData['total_marks'], 2); ?></strong></td>
                                <td><strong><?php echo number_format($examOverallData['total_max_marks'], 2); ?></strong></td>
                                <td><strong><?php echo number_format($examOverallData['percentage'], 2) . '%'; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($overallGrade); ?></strong></td>
                                <td>
                                    <strong>
                                        <?php 
                                        $statusText = '';
                                        switch($examOverallData['result_status']) {
                                            case 'pass':
                                                $statusText = 'PASSED';
                                                break;
                                            case 'fail':
                                                $statusText = 'FAILED';
                                                break;
                                            case 'absent':
                                                $statusText = 'ABSENT';
                                                break;
                                            default:
                                                $statusText = 'PENDING';
                                        }
                                        echo $statusText; 
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php if (isset($examOverallData['remarks']) && trim($examOverallData['remarks']) != ''): ?>
                    <div class="remarks-section">
                        <h4>Remarks:</h4>
                        <p><?php echo nl2br(htmlspecialchars($examOverallData['remarks'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($examOverallData['rank']) && $examOverallData['rank'] > 0): ?>
                    <div class="rank-section">
                        <h4>Rank in Class: <?php echo htmlspecialchars($examOverallData['rank']); ?></h4>
                    </div>
                    <?php endif; ?>
                    
                    <div class="published-date">
                        <p>Result published on: <?php echo date('d F, Y', strtotime($examOverallData['published_date'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 20px;">
                    <button onclick="window.print();" class="btn print-btn">Print All Results</button>
                </div>
            </section>
            <?php endif; ?>
            
        </main>
        
        <footer>
            <p>&copy; 2025 StudentFlex - Student Result Management System</p>
        </footer>
    </div>
</body>
</html>