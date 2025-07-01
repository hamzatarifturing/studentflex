<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No student ID provided";
    header('Location: add-student.php');
    exit;
}

$id = intval($_GET['id']);
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    $status = $_POST['status'];
    
    // Basic validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($class)) {
        $errors[] = "Class is required";
    }
    
    if (empty($section)) {
        $errors[] = "Section is required";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update users table (only full_name and status)
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $full_name, $status, $id);
            $stmt->execute();
            
            // Update students table
            $stmt = $conn->prepare("UPDATE students SET class = ?, section = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("ssi", $class, $section, $id);
            $stmt->execute();
            
            // Check if password is provided and needs update
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate password
                if ($password !== $confirm_password) {
                    throw new Exception("Password and confirmation do not match");
                }
                
                // Password strength validation
                if (strlen($password) < 8) {
                    throw new Exception("Password must be at least 8 characters long");
                }
                
                if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
                    !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                    throw new Exception("Password must include uppercase, lowercase, number and special character");
                }
                
                // Hash password and update
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $id);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = "Student information updated successfully";
            $success = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error updating student: " . $e->getMessage();
        }
    }
}

// Get student details from database
$sql = "SELECT u.*, s.* FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.id = ? AND u.role = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if student exists
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Student not found";
    header('Location: add-student.php');
    exit;
}

// Fetch student data
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - StudentFlex</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
    <?php include_once '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?php echo $_SESSION['success']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?php echo $_SESSION['error']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> Student information updated successfully.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="add-student.php">Students Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Student</li>
            </ol>
        </nav>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Edit Student</h5>
                <a href="view-student.php?id=<?php echo $id; ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Student Details
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Personal Information Section -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Personal Information</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Full Name -->
                                    <div class="form-group">
                                        <label for="full_name">Full Name*</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                    </div>
                                    
                                    <!-- Username (read-only) -->
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($student['username']); ?>" disabled>
                                        <small class="form-text text-muted">Username cannot be changed after creation</small>
                                    </div>
                                    
                                    <!-- Email (read-only) -->
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                                        <small class="form-text text-muted">Email cannot be changed after creation</small>
                                    </div>
                                    
                                    <!-- Status -->
                                    <div class="form-group">
                                        <label for="status">Account Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo $student['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $student['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Change Password (optional)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="password">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="form-text text-muted">Leave blank to keep current password</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    
                                    <div class="password-requirements mt-3">
                                        <p class="text-muted small mb-1">Password must contain:</p>
                                        <ul class="text-muted small">
                                            <li>At least 8 characters</li>
                                            <li>At least one uppercase letter</li>
                                            <li>At least one lowercase letter</li>
                                            <li>At least one number</li>
                                            <li>At least one special character</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Academic Information Section -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Academic Information</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Student ID (display only) -->
                                    <div class="form-group">
                                        <label for="student_id">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
                                        <small class="form-text text-muted">Student ID cannot be changed</small>
                                    </div>
                                    
                                    <!-- Class -->
                                    <div class="form-group">
                                        <label for="class">Class*</label>
                                        <select class="form-control" id="class" name="class" required>
                                            <option value="">Select Class</option>
                                            <option value="ONE" <?php echo $student['class'] == 'ONE' ? 'selected' : ''; ?>>ONE</option>
                                            <option value="TWO" <?php echo $student['class'] == 'TWO' ? 'selected' : ''; ?>>TWO</option>
                                            <option value="THREE" <?php echo $student['class'] == 'THREE' ? 'selected' : ''; ?>>THREE</option>
                                            <option value="FOUR" <?php echo $student['class'] == 'FOUR' ? 'selected' : ''; ?>>FOUR</option>
                                            <option value="FIVE" <?php echo $student['class'] == 'FIVE' ? 'selected' : ''; ?>>FIVE</option>
                                            <option value="SIX" <?php echo $student['class'] == 'SIX' ? 'selected' : ''; ?>>SIX</option>
                                            <option value="SEVEN" <?php echo $student['class'] == 'SEVEN' ? 'selected' : ''; ?>>SEVEN</option>
                                            <option value="EIGHT" <?php echo $student['class'] == 'EIGHT' ? 'selected' : ''; ?>>EIGHT</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Section -->
                                    <div class="form-group">
                                        <label for="section">Section*</label>
                                        <select class="form-control" id="section" name="section" required>
                                            <option value="">Select Section</option>
                                            <option value="A" <?php echo $student['section'] == 'A' ? 'selected' : ''; ?>>A</option>
                                            <option value="B" <?php echo $student['section'] == 'B' ? 'selected' : ''; ?>>B</option>
                                            <option value="C" <?php echo $student['section'] == 'C' ? 'selected' : ''; ?>>C</option>
                                            <option value="D" <?php echo $student['section'] == 'D' ? 'selected' : ''; ?>>D</option>
                                            <option value="E" <?php echo $student['section'] == 'E' ? 'selected' : ''; ?>>E</option>
                                            <option value="F" <?php echo $student['section'] == 'F' ? 'selected' : ''; ?>>F</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Created At (display only) -->
                                    <div class="form-group">
                                        <label>Account Created</label>
                                        <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($student['created_at'])); ?>" disabled>
                                    </div>
                                    
                                    <!-- Updated At (display only) -->
                                    <div class="form-group">
                                        <label>Last Updated</label>
                                        <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($student['updated_at'])); ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="view-student.php?id=<?php echo $id; ?>" class="btn btn-secondary mr-2">
                                            <i class="fas fa-times-circle mr-1"></i> Cancel
                                        </a>
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteStudentModal">
                                            <i class="fas fa-trash-alt mr-1"></i> Delete Student
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" role="dialog" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteStudentModalLabel">Confirm Delete</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this student?</p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All related data will be permanently deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="delete-student.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="redirect" value="add-student">
                        <button type="submit" class="btn btn-danger">Delete Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once '../includes/footer.php'; ?>
    
    <!-- jQuery, Bootstrap JS and other scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Auto hide alerts after 5 seconds
        $(document).ready(function(){
            setTimeout(function(){
                $(".alert").alert('close');
            }, 5000);
            
            // Password match validation
            $('#confirm_password').on('keyup', function() {
                if ($('#password').val() != '' && $('#confirm_password').val() != '') {
                    if ($('#password').val() != $('#confirm_password').val()) {
                        $('#confirm_password').addClass('is-invalid');
                    } else {
                        $('#confirm_password').removeClass('is-invalid').addClass('is-valid');
                    }
                }
            });
            
            // Password strength validation
            $('#password').on('keyup', function() {
                let password = $(this).val();
                let strength = 0;
                
                if (password.length >= 8) {
                    strength += 1;
                }
                if (password.match(/[A-Z]/)) {
                    strength += 1;
                }
                if (password.match(/[a-z]/)) {
                    strength += 1;
                }
                if (password.match(/[0-9]/)) {
                    strength += 1;
                }
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 1;
                }
                
                if (password.length > 0) {
                    if (strength < 3) {
                        $(this).removeClass('is-valid is-warning').addClass('is-invalid');
                    } else if (strength < 5) {
                        $(this).removeClass('is-valid is-invalid').addClass('is-warning');
                    } else {
                        $(this).removeClass('is-invalid is-warning').addClass('is-valid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid is-warning');
                }
            });
        });
    </script>
</body>
</html>