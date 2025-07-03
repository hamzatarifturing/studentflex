<?php
// view-students-results.php
// Public-facing page for students to view their results without login required

// Include database connection
include 'config/db.php';

// Process form submission
$student = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    // Sanitize the input to prevent SQL injection
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    // Query to get student information
    $query = "SELECT s.*, u.name, u.email, u.phone
              FROM students s
              JOIN users u ON s.user_id = u.id
              WHERE s.student_id = '$student_id'";
    echo $query ;
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
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
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <h1 class="display-4 text-primary">Welcome to StudentFlex</h1>
                        <p class="lead my-4">Your trusted platform for academic result management</p>
                        <hr class="my-4">
                        <p class="mb-4">
                            StudentFlex provides an easy way for students and parents to view academic results online.
                            Our platform ensures secure and accurate reporting of academic performance throughout
                            the school year, making it simple to track progress and achievements.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            About StudentFlex
                        </h3>
                        <p class="card-text">
                            StudentFlex is a comprehensive Student Result Management System designed to streamline
                            the process of managing and accessing academic records. Our system provides:
                        </p>
                        <ul>
                            <li>Easy access to semester/term results</li>
                            <li>Detailed subject-wise performance analysis</li>
                            <li>Secure and private result viewing</li>
                            <li>Mobile-friendly interface for on-the-go access</li>
                            <li>Simplified result searching using student ID or name</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-users text-primary me-2"></i>
                            For Students & Parents
                        </h3>
                        <p class="card-text">
                            StudentFlex makes it easy for students and parents to stay informed about academic progress:
                        </p>
                        <ul>
                            <li>View comprehensive result reports</li>
                            <li>Track performance trends across subjects</li>
                            <li>Access previous semester/term results for comparison</li>
                            <li>Print result sheets for offline reference</li>
                            <li>Get timely notifications when new results are published</li>
                        </ul>
                        <p class="mt-3">
                            <button id="check-results-btn" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Check Your Results
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Result Form -->
        <div class="row mt-4" id="result-form-container">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm animate-fade">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-search me-2"></i>Search Your Results</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <form id="result-search-form" method="post" action="">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       placeholder="Enter your student ID" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                                       required>
                                <small class="form-text text-muted">
                                    Enter your student ID to view your academic results.
                                </small>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-search me-2"></i> View Results
                                </button>
                                <button type="button" id="cancel-search" class="btn btn-secondary px-4 ms-2">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Information -->
        <?php if ($student): ?>
        <div class="row mt-4">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm animate-fade">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>Student Information
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                                <p><strong><i class="fas fa-id-card me-2"></i>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                <p><strong><i class="fas fa-user me-2"></i>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                                <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                                <p><strong><i class="fas fa-phone me-2"></i>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Academic Information</h5>
                                <p><strong><i class="fas fa-school me-2"></i>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                <p><strong><i class="fas fa-users me-2"></i>Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                                <p><strong><i class="fas fa-calendar me-2"></i>Registered On:</strong> <?php echo date('F d, Y', strtotime($student['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Academic results will be shown here in future updates.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div id="results-container" class="mt-4"></div>
    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>StudentFlex</h5>
                    <p>Student Result Management System</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; <?php echo date('Y'); ?> StudentFlex. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // JavaScript for handling form display toggle
        document.addEventListener('DOMContentLoaded', function() {
            const checkResultsBtn = document.getElementById('check-results-btn');
            const resultFormContainer = document.getElementById('result-form-container');
            const cancelSearchBtn = document.getElementById('cancel-search');
            
            // Show form when button is clicked
            checkResultsBtn.addEventListener('click', function() {
                resultFormContainer.style.display = 'block';
                // Scroll to the form
                resultFormContainer.scrollIntoView({ behavior: 'smooth' });
            });
            
            // Hide form when cancel button is clicked
            cancelSearchBtn.addEventListener('click', function() {
                resultFormContainer.style.display = 'none';
            });
        });
    </script>
</body>
</html>