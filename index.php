<?php
// Start session
session_start();

// Include the header file
include_once 'includes/header.php';

// Check if there's a welcome message
$welcome_message = '';
if(isset($_SESSION["welcome_message"])) {
    $welcome_message = $_SESSION["welcome_message"];
    unset($_SESSION["welcome_message"]); // Clear the message so it only shows once
}
?>

<?php if(!empty($welcome_message)): ?>
<div class="welcome-alert">
    <div class="container">
        <div class="alert alert-success">
            <?php echo $welcome_message; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container main-content">
    <div class="welcome-section">
        <h1>Welcome to StudentFlex</h1>
        <p class="lead">Student Result Management System</p>
        <div class="welcome-description">
            <p>A comprehensive platform for managing student academic records and results.</p>
        </div>
        
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="user-info mt-4">
                    <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION["full_name"]); ?></strong> (<?php echo ucfirst($_SESSION["role"]); ?>)</p>
                    <?php if($_SESSION["role"] == "admin"): ?>
                        <a href="admin/dashboard.php" class="btn btn-primary mt-2">Admin Dashboard</a>
                    <?php else: ?>
                        <a href="#" class="btn btn-primary mt-2">View My Results</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="login-prompt mt-4">
                    <p>Please <a href="login.php">login</a> to access the system.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="login-prompt mt-4">
                <p>Please <a href="login.php">login</a> to access the system.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>