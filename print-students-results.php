<?php
// Include database connection
require_once 'config/db.php';

// Initialize variables
$studentData = null;
$resultData = null;
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
        // Fetch student information
        $studentQuery = "SELECT * FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        
        if ($studentResult->num_rows > 0) {
            $studentData = $studentResult->fetch_assoc();
            
            // Fetch exam results for the student
            $resultsQuery = "SELECT m.*, s.subject_name 
                             FROM marks m 
                             JOIN subjects s ON m.subject_id = s.id 
                             WHERE m.student_id = ? 
                             AND m.exam_month = ? 
                             AND m.exam_year = ?";
            
            $stmt = $conn->prepare($resultsQuery);
            $stmt->bind_param("sss", $studentId, $resultMonth, $resultYear);
            $stmt->execute();
            $resultData = $stmt->get_result();
            
            if ($resultData->num_rows > 0) {
                $showResults = true;
            } else {
                $errorMessage = "No results found for the selected month and year.";
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
        
        @media print {
            .search-section, .print-btn, header {
                display: none;
            }
            
            .results-section {
                border: none;
                padding: 0;
            }
            
            body {
                font-size: 14px;
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
            
            <?php if ($showResults && $studentData && $resultData): ?>
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
                            <span><?php echo htmlspecialchars($studentData['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Class:</span>
                            <span><?php echo htmlspecialchars($studentData['class']); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Exam Period:</span>
                            <span><?php echo htmlspecialchars($resultMonth . ' ' . $resultYear); ?></span>
                        </div>
                    </div>
                </div>
                
                <h3>Exam Results</h3>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalObtained = 0;
                        $totalMarks = 0;
                        
                        while ($row = $resultData->fetch_assoc()): 
                            $percentage = ($row['marks_obtained'] / $row['total_marks']) * 100;
                            $totalObtained += $row['marks_obtained'];
                            $totalMarks += $row['total_marks'];
                            
                            // Determine grade
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
                            
                            // Determine status
                            $status = ($percentage >= 40) ? 'Pass' : 'Fail';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['marks_obtained']); ?></td>
                            <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
                            <td><?php echo number_format($percentage, 2) . '%'; ?></td>
                            <td><?php echo $grade; ?></td>
                            <td><?php echo $status; ?></td>
                        </tr>
                        <?php endwhile; 
                        
                        // Calculate overall percentage
                        $overallPercentage = ($totalMarks > 0) ? ($totalObtained / $totalMarks) * 100 : 0;
                        
                        // Determine overall grade
                        $overallGrade = '';
                        if ($overallPercentage >= 90) {
                            $overallGrade = 'A+';
                        } elseif ($overallPercentage >= 80) {
                            $overallGrade = 'A';
                        } elseif ($overallPercentage >= 70) {
                            $overallGrade = 'B';
                        } elseif ($overallPercentage >= 60) {
                            $overallGrade = 'C';
                        } elseif ($overallPercentage >= 50) {
                            $overallGrade = 'D';
                        } else {
                            $overallGrade = 'F';
                        }
                        
                        // Determine overall status
                        $overallStatus = ($overallPercentage >= 40) ? 'Pass' : 'Fail';
                        ?>
                        <tr class="result-summary">
                            <td colspan="2"><strong>Overall Result</strong></td>
                            <td><strong><?php echo $totalObtained . ' / ' . $totalMarks; ?></strong></td>
                            <td><strong><?php echo number_format($overallPercentage, 2) . '%'; ?></strong></td>
                            <td><strong><?php echo $overallGrade; ?></strong></td>
                            <td><strong><?php echo $overallStatus; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
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