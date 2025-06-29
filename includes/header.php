<?php
// Start PHP session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine if we're in a subdirectory
$root_path = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $root_path = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentFlex - <?php echo isset($page_title) ? $page_title : 'Student Result Management System'; ?></title>
    <!-- Include CSS -->
    <link rel="stylesheet" href="<?php echo $root_path; ?>assets/css/styles.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo $root_path; ?>index.php">
                    <i class="fas fa-graduation-cap"></i> StudentFlex
                </a>
            </div>
            <nav class="main-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?php echo $root_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] == "admin"): ?>
                        <li class="nav-item">
                            <a href="<?php echo $root_path; ?>admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="#"><i class="fas fa-user-graduate"></i> Students</a></li>
                    <li class="nav-item"><a href="#"><i class="fas fa-clipboard-list"></i> Results</a></li>
                    <li class="nav-item"><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li class="nav-item"><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    <a href="<?php echo $root_path; ?>logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?php echo $root_path; ?>login.php" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </div>
            <!-- Mobile menu toggle -->
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    <main>
