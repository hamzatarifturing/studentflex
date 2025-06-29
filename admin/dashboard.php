<?php
// Start session
session_start();

// Check if user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Check if user is an admin
if($_SESSION["role"] !== "admin") {
    // Set error message 
    $error_message = "Access Denied: You don't have permission to access this page.";
}

// Include database connection
require_once "../config/db.php";

// Define page title
$page_title = "Admin Dashboard";

// Include header
include_once "../includes/header.php";
?>

<div class="container main-content">
    <?php if(isset($error_message)): ?>
        <!-- Error message for non-admin users -->
        <div class="alert alert-danger mt-4">
            <h4><i class="fas fa-exclamation-triangle"></i> Error</h4>
            <p><?php echo $error_message; ?></p>
            <a href="../index.php" class="btn btn-secondary mt-3">Back to Home</a>
        </div>
    <?php else: ?>
        <!-- Dashboard content for admin users -->
        <div class="admin-dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <p class="lead">Welcome to the StudentFlex administration panel.</p>
            </div>

            <div class="dashboard-welcome mt-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h4>
                        <p class="card-text">You are logged in as an administrator. From here, you can manage students, courses, results, and system settings.</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-stats mt-4">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <i class="fas fa-user-graduate stat-icon"></i>
                                <h5>Students</h5>
                                <p class="stat-number">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <i class="fas fa-book stat-icon"></i>
                                <h5>Courses</h5>
                                <p class="stat-number">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <i class="fas fa-clipboard-list stat-icon"></i>
                                <h5>Exams</h5>
                                <p class="stat-number">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-body">
                                <i class="fas fa-users stat-icon"></i>
                                <h5>Users</h5>
                                <p class="stat-number">1</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-actions mt-4">
                <h4>Quick Actions</h4>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <a href="#" class="action-link">
                            <div class="action-card">
                                <i class="fas fa-user-plus"></i>
                                <span>Add New Student</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="action-link">
                            <div class="action-card">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add New Result</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="action-link">
                            <div class="action-card">
                                <i class="fas fa-file-alt"></i>
                                <span>Generate Reports</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>