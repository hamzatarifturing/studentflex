<?php
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']); // This will be the auto-generated ID
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    
    // Basic validation
    $errors = array();
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($student_id)) {
        // Generate new student ID if empty for some reason
        $student_id_query = "SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) as max_id FROM students";
        $id_result = $conn->query($student_id_query);
        if ($id_result && $id_result->num_rows > 0) {
            $row = $id_result->fetch_assoc();
            $max_id = $row['max_id'];
            $next_id = ($max_id > 0) ? $max_id + 1 : 1;
            $student_id = 'STU' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
        } else {
            $student_id = 'STU0001'; // First student
        }
    }
    
    if (empty($class)) {
        $errors[] = "Class is required";
    }
    
    // Check if username or email already exists
    $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['username'] === $username) {
            $errors[] = "Username already exists";
        }
        if ($user['email'] === $email) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Create students table if it doesn't exist
        $create_students_table = "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            class VARCHAR(50) NOT NULL,
            section VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_students_table);
        
        // Begin transaction
        $conn->autocommit(FALSE);
        $transaction_success = true;
        
        try {
            // Insert into users table
            $insert_user = "INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, 'student', 'active')";
            $stmt = $conn->prepare($insert_user);
            $stmt->bind_param("ssss", $username, $hashed_password, $full_name, $email);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting user: " . $conn->error);
            }
            
            $user_id = $conn->insert_id;
            
            // Insert into students table
            $insert_student = "INSERT INTO students (user_id, student_id, class, section) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_student);
            $stmt->bind_param("isss", $user_id, $student_id, $class, $section);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting student: " . $conn->error);
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Student added successfully!";
            
            // Clear form data
            $username = $password = $confirm_password = $full_name = $email = $student_id = $class = $section = "";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
        
        // Restore autocommit mode
        $conn->autocommit(TRUE);
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Process delete student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $user_id = $_POST['user_id'];
    
    $conn->autocommit(FALSE);
    
    try {
        // Delete user (cascade will delete related student record)
        $delete_user = "DELETE FROM users WHERE id = ? AND role = 'student'";
        $stmt = $conn->prepare($delete_user);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $success_message = "Student deleted successfully!";
        } else {
            throw new Exception("Student not found or could not be deleted");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
    
    // Restore autocommit mode
    $conn->autocommit(TRUE);
}

// Get next student ID
$next_student_id = '';
$student_id_query = "SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) as max_id FROM students";
$id_result = $conn->query($student_id_query);
if ($id_result && $id_result->num_rows > 0) {
    $row = $id_result->fetch_assoc();
    $max_id = $row['max_id'];
    $next_id = ($max_id > 0) ? $max_id + 1 : 1;
    $next_student_id = 'STU' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
} else {
    $next_student_id = 'STU0001'; // First student
}

// Get all students for display
$students_query = "SELECT u.id, u.username, u.full_name, u.email, u.status, s.student_id, s.class, s.section, u.created_at 
                   FROM users u 
                   JOIN students s ON u.id = s.user_id 
                   WHERE u.role = 'student' 
                   ORDER BY s.class, s.section, u.full_name";
$students_result = $conn->query($students_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - StudentFlex</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f9f9f9;
            font-weight: 500;
        }
        
        .table tr:hover {
            background-color: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .close-alert {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
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
            margin: 10% auto;
            padding: 1.5rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #555;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-group {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i> StudentFlex
            </div>
            <nav class="main-nav">
                <ul class="nav-menu">
                    <li class="nav-item"><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="add-student.php" class="active"><i class="fas fa-user-graduate"></i> Students</a></li>
                    <li class="nav-item"><a href="add-teacher.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                    <li class="nav-item"><a href="#"><i class="fas fa-clipboard-list"></i> Results</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <a href="../logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <!-- Mobile menu toggle -->
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    
    <main>
        <div class="container admin-container">
            <h1><i class="fas fa-user-graduate"></i> Student Management</h1>
            
            <?php if($success_message): ?>
            <div class="alert alert-success" id="success-alert">
                <span><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></span>
                <button class="close-alert">&times;</button>
            </div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
            <div class="alert alert-danger" id="error-alert">
                <span><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></span>
                <button class="close-alert">&times;</button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Add New Student</h2>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group" style="width: 50%;">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group" style="width: 50%;">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="width: 33.33%;">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group" style="width: 33.33%;">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group" style="width: 33.33%;">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="width: 33.33%;">
                            <label for="student_id">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($next_student_id); ?>" readonly required>
                            <small class="form-text text-muted">ID is automatically generated</small>
                        </div>
                        
                        <div class="form-group" style="width: 33.33%;">
                            <label for="class">Class/Semester</label>
                            <select class="form-control" id="class" name="class" required>
                                <option value="" disabled <?php echo !isset($class) ? 'selected' : ''; ?>>Select Class</option>
                                <?php 
                                $classes = array('ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT');
                                foreach($classes as $class_option) {
                                    $selected = (isset($class) && $class == $class_option) ? 'selected' : '';
                                    echo "<option value=\"$class_option\" $selected>$class_option</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="width: 33.33%;">
                            <label for="section">Section</label>
                            <select class="form-control" id="section" name="section" required>
                                <option value="" disabled <?php echo !isset($section) ? 'selected' : ''; ?>>Select Section</option>
                                <?php 
                                $sections = array('A', 'B', 'C', 'D', 'E', 'F');
                                foreach($sections as $section_option) {
                                    $selected = (isset($section) && $section == $section_option) ? 'selected' : '';
                                    echo "<option value=\"$section_option\" $selected>Section $section_option</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_student" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Student
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Students List</h2>
                    <div>
                        <input type="text" id="studentSearch" class="form-control" placeholder="Search students..." style="width: 250px;">
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students_result && $students_result->num_rows > 0): ?>
                                <?php while($student = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td><?php echo htmlspecialchars($student['section']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <?php if($student['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="showDeleteModal('<?php echo $student['id']; ?>', '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the student <strong id="studentName"></strong>?</p>
                <p>This action cannot be undone and will remove all associated data.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" id="deleteStudentId" name="user_id">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="delete_student" class="btn btn-danger">Delete Student</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Close alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            for (var i = 0; i < alerts.length; i++) {
                alerts[i].style.display = 'none';
            }
        }, 5000);
        
        // Close alert on button click
        var closeButtons = document.querySelectorAll('.close-alert');
        for (var i = 0; i < closeButtons.length; i++) {
            closeButtons[i].addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        }
        
        // Delete modal functionality
        function showDeleteModal(id, name) {
            var modal = document.getElementById('deleteModal');
            var studentNameElement = document.getElementById('studentName');
            var deleteStudentId = document.getElementById('deleteStudentId');
            
            studentNameElement.textContent = name;
            deleteStudentId.value = id;
            modal.style.display = 'block';
        }
        
        // Close modal when clicking close button or outside the modal
        var closeModalButtons = document.querySelectorAll('.close-modal');
        for (var i = 0; i < closeModalButtons.length; i++) {
            closeModalButtons[i].addEventListener('click', function() {
                document.getElementById('deleteModal').style.display = 'none';
            });
        }
        
        window.addEventListener('click', function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Student search functionality
        document.getElementById('studentSearch').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var table = document.getElementById('studentsTable');
            var rows = table.getElementsByTagName('tr');
            
            for (var i = 1; i < rows.length; i++) {
                var row = rows[i];
                var cells = row.getElementsByTagName('td');
                var found = false;
                
                for (var j = 0; j < cells.length; j++) {
                    var cell = cells[j];
                    if (cell) {
                        var text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(searchValue) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>
    
    <footer>
        <div class="footer-container">
            <div class="footer-section about">
                <h3>About StudentFlex</h3>
                <p>StudentFlex is a comprehensive student result management system designed to simplify academic record keeping for educational institutions.</p>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="add-student.php">Students</a></li>
                    <li><a href="add-teacher.php">Teachers</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Education St, Academic City</p>
                <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                <p><i class="fas fa-envelope"></i> info@studentflex.com</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> StudentFlex. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>