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
    
    // Validate inputs
    if (empty($studentId) || empty($resultMonth) || empty($resultYear)) {
        $errorMessage = "All fields are required!";
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
            
            // Find exam that matches the month and year
            $examQuery = "SELECT * FROM exams 
                          WHERE MONTH(start_date) = ? 
                          AND YEAR(start_date) = ? 
                          AND class = ?";
            
            // Convert month name to number
            $monthNumber = date('m', strtotime("$resultMonth 1, $resultYear"));
            
            $stmt = $conn->prepare($examQuery);
            $stmt->bind_param("sss", $monthNumber, $resultYear, $studentData['class']);
            $stmt->execute();
            $examResult = $stmt->get_result();
            
            if ($examResult->num_rows > 0) {
                $examData = $examResult->fetch_assoc();
                
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
                    $overallData = $overallResult->fetch_assoc();
                    $showResults = true;
                } else {
                    $errorMessage = "No published results found for the selected month and year.";
                }
            } else {
                $errorMessage = "No exam found for the selected month and year.";
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
                <p>You can search for a student's results using their student ID, select the month and year, and then print a well-formatted report card.</p>
                <div class="info-box">
                    <h3>How to use this page:</h3>
                    <p>1. Enter the student's ID in the search field</p>
                    <p>2. Select the month and year for the results</p>
                    <p>3. Click the "Search" button to find the results</p>
                    <p>4. Review the displayed results and print as needed</p>
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
                        <input type="text" id="studentId" name="studentId" placeholder="Enter Student ID" required value="<?php echo isset($_POST['studentId']) ? htmlspecialchars($_POST['studentId']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="resultMonth">Month:</label>
                        <select id="resultMonth" name="resultMonth" required>
                            <option value="">Select Month</option>
                            <?php
                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach ($months as $month) {
                                $selected = (isset($_POST['resultMonth']) && $_POST['resultMonth'] == $month) ? 'selected' : '';
                                echo "<option value=\"$month\" $selected>$month</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="resultYear">Year:</label>
                        <select id="resultYear" name="resultYear" required>
                            <option value="">Select Year</option>
                            <?php
                            $years = ['2023', '2024', '2025'];
                            foreach ($years as $year) {
                                $selected = (isset($_POST['resultYear']) && $_POST['resultYear'] == $year) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Search Results</button>
                    </div>
                </form>
            </section>
            
            <?php if ($showResults && $studentData && $resultData && $examData): ?>
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
                        <div class="info-item">
                            <span class="info-label">Exam:</span>
                            <span><?php echo htmlspecialchars($examData['exam_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Exam Period:</span>
                            <span><?php echo htmlspecialchars(date('d M, Y', strtotime($examData['start_date']))) . ' - ' . 
                                           htmlspecialchars(date('d M, Y', strtotime($examData['end_date']))); ?></span>
                        </div>
                    </div>
                </div>
                
                <h3>Exam Results</h3>
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
                        $totalObtained = 0;
                        $totalMaxMarks = 0;
                        
                        while ($row = $resultData->fetch_assoc()): 
                            $percentage = ($row['marks_obtained'] / $row['marks_max']) * 100;
                            $totalObtained += $row['marks_obtained'];
                            $totalMaxMarks += $row['marks_max'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['marks_obtained']); ?></td>
                            <td><?php echo htmlspecialchars($row['marks_max']); ?></td>
                            <td><?php echo number_format($percentage, 2) . '%'; ?></td>
                            <td><?php echo htmlspecialchars($row['grade']); ?></td>
                            <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <!-- Overall Result Summary -->
                        <tr class="result-summary">
                            <td colspan="2"><strong>Overall Result</strong></td>
                            <td><strong><?php echo number_format($overallData['total_marks'], 2); ?></strong></td>
                            <td><strong><?php echo number_format($overallData['total_max_marks'], 2); ?></strong></td>
                            <td><strong><?php echo number_format($overallData['percentage'], 2) . '%'; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($overallData['grade']); ?></strong></td>
                            <td>
                                <strong>
                                    <?php 
                                    $statusText = '';
                                    switch($overallData['result_status']) {
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
                
                <?php if (!empty($overallData['remarks'])): ?>
                <div class="remarks-section">
                    <h4>Remarks:</h4>
                    <p><?php echo nl2br(htmlspecialchars($overallData['remarks'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($overallData['rank'])): ?>
                <div class="rank-section">
                    <h4>Rank in Class: <?php echo htmlspecialchars($overallData['rank']); ?></h4>
                </div>
                <?php endif; ?>
                
                <div class="published-date">
                    <p>Result published on: <?php echo date('d F, Y', strtotime($overallData['published_date'])); ?></p>
                </div>
                
                <button onclick="window.print();" class="btn print-btn">Print Result</button>
            </section>
            <?php endif; ?>
            
        </main>
        
        <footer>
            <p>&copy; 2025 StudentFlex - Student Result Management System</p>
        </footer>
    </div>
</body>
</html>