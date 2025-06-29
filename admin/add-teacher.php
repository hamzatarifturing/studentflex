<?php
session_start();
// Check if user is logged in as admin
if(!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
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

// Process delete request
if(isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['teacher_id'])) {
    $teacher_id = intval($_POST['teacher_id']);
    
    // Get teacher data for confirmation message
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_name = ($result->num_rows > 0) ? $result->fetch_assoc()['full_name'] : 'Unknown';
    $stmt->close();
    
    // Delete the teacher
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND (role = 'teacher' OR role = 'student')");
    $stmt->bind_param("i", $teacher_id);
    
    if($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Teacher '" . htmlspecialchars($teacher_name) . "' has been deleted from the system permanently.";
        $message_type = "success";
    } else {
        $message = "Error deleting teacher. Please try again.";
        $message_type = "error";
    }
    
    $stmt->close();
}

// Process form submission for adding a teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
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
        
        // First, try to modify the table to include 'teacher' role if not already present
        $check_query = "SHOW COLUMNS FROM users LIKE 'role'";
        $check_result = $conn->query($check_query);
        if($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if(strpos($row['Type'], 'teacher') === false) {
                // Role field exists but doesn't have 'teacher' enum option
                $alter_query = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'student', 'teacher') NOT NULL";
                $conn->query($alter_query);
            }
        }
        
        // Set default values
        $role = 'teacher'; // Now using teacher role
        $status = 'active';
        
        // Prepare and execute the insert query
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $role, $status);
        
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

// Get all teachers
$teachers = array();
$query = "SELECT id, username, full_name, email, status, created_at FROM users WHERE role='teacher' OR role='student' ORDER BY id DESC";
$result = $conn->query($query);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $teachers[] = $row;
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
    
    .btn-danger {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 6px 12px;
        font-size: 14px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
    }
    
    .required {
        color: red;
    }
    
    /* Table styles */
    .teacher-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
    }
    
    .teacher-table th, 
    .teacher-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .teacher-table th {
        background-color: #f8f9fa;
        font-weight: 500;
        color: #333;
    }
    
    .teacher-table tr:hover {
        background-color: #f1f1f1;
    }
    
    .teacher-table .badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .badge-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .section-title {
        margin: 30px 0 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
        color: #2c3e50;
    }
    
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 5px;
        width: 80%;
        max-width: 500px;
    }
    
    .modal-header {
        padding-bottom: 15px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }
    
    .modal-title {
        margin: 0;
        color: #e74c3c;
    }
    
    .modal-body {
        margin-bottom: 20px;
    }
    
    .modal-footer {
        text-align: right;
    }
    
    .close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: #e74c3c;
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
<h3>
<i class="fas fa-list"></i>

Existing Teachers

</h3>
        <?php if(count($teachers) > 0): ?>
<div>
<table>
<thead>
<tr>
<th>
</th>
<th>
Username

</th>
<th>
Full Name

</th>
<th>
Email

</th>
<th>
Status

</th>
<th>
Registered Date

</th>
<th>
Actions

</th>
</tr>
</thead>
<tbody>
                        <?php foreach($teachers as $index => $teacher): ?>
<tr>
<td>
<?php echo $index + 1; ?>
</td>
<td>
<?php echo htmlspecialchars($teacher['username']); ?>
</td>
<td>
<?php echo htmlspecialchars($teacher['full_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($teacher['email']); ?>
</td>
<td>
                                    <?php if($teacher['status'] == 'active'): ?>
<span>
Active

</span>
                                    <?php else: ?>
<span>
Inactive

</span>
                                    <?php endif; ?>
</td>
<td>
<?php echo date('M d, Y', strtotime($teacher['created_at'])); ?>
</td>
<td>
<button>
, '<?php echo htmlspecialchars(addslashes($teacher['full_name'])); ?>')">

<i class="fas fa-trash"></i>

Delete

</button>
</td>
</tr>
                        <?php endforeach; ?>
</tbody>
</table>
</div>
        <?php else: ?>
<p>
No teachers found in the system.

</p>
        <?php endif; ?>
    </div>
</main>
<!-- Delete Confirmation Modal -->
<div>
    <div class="modal-content">
        <div class="modal-header">
<span>
×

</span>
<h3>
<i class="fas fa-exclamation-triangle"></i>

Confirm Delete

</h3>
</div>
<div>
<p>
Are you sure you want to delete

<strong id="teacher-name"></strong>

?

</p>
<p>
This action cannot be undone and will permanently remove the teacher from the system.

</p>
</div>
<div>
<form>
                <input type="hidden" name="teacher_id" id="teacher-id" value="">
                <input type="hidden" name="action" value="delete">
<button>
Cancel

</button>
<button>
Delete

</button>
</form>
</div>
    </div>
</div>
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
    
    // Delete confirmation modal
    const modal = document.getElementById('deleteModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    
    function confirmDelete(teacherId, teacherName) {
        document.getElementById('teacher-id').value = teacherId;
        document.getElementById('teacher-name').textContent = teacherName;
        modal.style.display = 'block';
    }
    
    function closeModal() {
        modal.style.display = 'none';
    }
    
    closeBtn.onclick = function() {
        closeModal();
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
</body>
</html>