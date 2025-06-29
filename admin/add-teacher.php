<?php
session_start();
// Check if user is logged in as admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config/db.php';

// Initialize variables
$username = '';
$full_name = '';
$email = '';
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Basic validation
    $errors = array();
    
    if(empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        $stmt->close();
    }
    
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }
    
    // If no errors, insert the teacher into database
    if(empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set default values
        $role = 'teacher';
        $status = 'active';
        $created_at = date('Y-m-d H:i:s');
        
        // Prepare and execute the insert query
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $role, $status, $created_at);
        
        if($stmt->execute()) {
            // Success
            $message = "Teacher added successfully!";
            $message_type = "success";
            
            // Clear form data
            $username = '';
            $full_name = '';
            $email = '';
        } else {
            // Error
            $message = "Error: " . $stmt->error;
            $message_type = "error";
        }
        
        $stmt->close();
    } else {
        // Display validation errors
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
Add Teacher - StudentFlex

</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .alert-error {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-control:focus {
        border-color: #3498db;
        outline: none;
    }
    
    .btn-primary {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
    }
    
    .required {
        color: red;
    }
</style>
</head>
<body>
<header>
<div>
        <div class="logo">
<i class="fas fa-graduation-cap"></i>

StudentFlex

</div>
<nav>
<ul>
<li>
<a>
<i class="fas fa-tachometer-alt"></i>

Dashboard

</a>
</li>
<li>
<a>
<i class="fas fa-user-graduate"></i>

Students

</a>
</li>
<li>
<a>
<i class="fas fa-chalkboard-teacher"></i>

Teachers

</a>
</li>
<li>
<a>
<i class="fas fa-clipboard-list"></i>

Results

</a>
</li>
<li>
<a>
<i class="fas fa-cog"></i>

Settings

</a>
</li>
</ul>
</nav>
<div>
<a>
<i class="fas fa-sign-out-alt"></i>

Logout

</a>
</div>
        <!-- Mobile menu toggle -->
<div>
<i class="fas fa-bars"></i>

</div>
    </div>
</header>
<main>
<div>
<h2>
<i class="fas fa-user-plus"></i>

Add New Teacher

</h2>
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
</div>
        <?php endif; ?>
<form>
">

<div>
<label>
Username

<span>
</span>
</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
</div>
<div>
<label>
Password

<span>
</span>
</label>
                <input type="password" id="password" name="password" class="form-control" required>
</div>
<div>
<label>
Confirm Password

<span>
</span>
</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
</div>
<div>
<label>
Full Name

<span>
</span>
</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
</div>
<div>
<label>
Email

<span>
</span>
</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
</div>
<div>
<button>
<i class="fas fa-save"></i>

Add Teacher

</button>
</div>
</form>
    </div>
</main>
<footer>
<div>
        <div class="footer-section about">
<h3>
About StudentFlex

</h3>
<p>
StudentFlex is a comprehensive student result management system designed to simplify academic record keeping for educational institutions.

</p>
</div>
<div>
<h3>
Quick Links

</h3>
<ul>
<li>
<a>
Home

</a>
</li>
<li>
<a>
About

</a>
</li>
<li>
<a>
Contact

</a>
</li>
<li>
<a>
Privacy Policy

</a>
</li>
</ul>
</div>
<div>
<h3>
Contact Us

</h3>
<p>
<i class="fas fa-map-marker-alt"></i>

123 Education St, Academic City

</p>
<p>
<i class="fas fa-phone"></i>

(123) 456-7890

</p>
<p>
<i class="fas fa-envelope"></i>

info@studentflex.com

</p>
            <div class="social-icons">
<a>
<i class="fab fa-facebook"></i>

</a>
<a>
<i class="fab fa-twitter"></i>

</a>
<a>
<i class="fab fa-instagram"></i>

</a>
<a>
<i class="fab fa-linkedin"></i>

</a>
</div>
        </div>
    </div>
<div>
<p>
© <?php echo date('Y'); ?> StudentFlex. All Rights Reserved.

</p>
</div>
</footer>
<!-- Include JavaScript -->
<script src="../assets/js/scripts.js"></script>
<script>
    // Auto-hide alert messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(function() {
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        }
    });
</script>
</body>
</html>