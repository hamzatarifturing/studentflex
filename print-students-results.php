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
                <form id="resultSearchForm" method="post" action="">
                    <div class="form-group">
                        <label for="studentId">Student ID:</label>
                        <input type="text" id="studentId" name="studentId" placeholder="Enter Student ID" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="resultMonth">Month:</label>
                        <select id="resultMonth" name="resultMonth" required>
                            <option value="">Select Month</option>
                            <option value="January">January</option>
                            <option value="February">February</option>
                            <option value="March">March</option>
                            <option value="April">April</option>
                            <option value="May">May</option>
                            <option value="June">June</option>
                            <option value="July">July</option>
                            <option value="August">August</option>
                            <option value="September">September</option>
                            <option value="October">October</option>
                            <option value="November">November</option>
                            <option value="December">December</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="resultYear">Year:</label>
                        <select id="resultYear" name="resultYear" required>
                            <option value="">Select Year</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Search Results</button>
                    </div>
                </form>
            </section>
            
            <!-- Results display will be added in future updates -->
            
        </main>
        
        <footer>
            <p>&copy; 2025 StudentFlex - Student Result Management System</p>
        </footer>
    </div>
</body>
</html>