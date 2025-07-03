<?php
// view-students-results.php
// Public-facing page for students to view their results without login required
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
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Check Your Results
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coming Soon: Result Search Form -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-tools me-2"></i>
                    <strong>Coming Soon:</strong> Our student result search functionality is under development.
                    Soon you'll be able to search for your results using your student ID or name.
                    Check back later!
                </div>
            </div>
        </div>
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
</body>
</html>
