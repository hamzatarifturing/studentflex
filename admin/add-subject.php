<?php
// Start session and include database connection
session_start();
include_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Process form submission for adding a new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    // Get form data
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $class = $_POST['class'];
    $is_active = $_POST['is_active'];
    
    // Validate input
    if (empty($subject_code) || empty($subject_name) || empty($class)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if subject code already exists
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Subject code already exists. Please use a different code.";
        } else {
            // Insert new subject
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, class, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $subject_code, $subject_name, $class, $is_active);
            
            if ($stmt->execute()) {
                $success = "Subject added successfully";
                // Clear form data after successful submission
                $subject_code = $subject_name = '';
            } else {
                $error = "Error adding subject: " . $conn->error;
            }
        }
    }
}

// Process delete subject request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    
    // Get subject details for success message
    $stmt = $conn->prepare("SELECT subject_code, subject_name FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Subject not found";
    } else {
        $subject = $result->fetch_assoc();
        
        // Delete the subject
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subject_id);
        
        if ($stmt->execute()) {
            $success = "Subject '{$subject['subject_name']}' (Code: {$subject['subject_code']}) has been deleted successfully";
        } else {
            $error = "Error deleting subject: " . $conn->error;
        }
    }
}

// Process edit subject request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    $subject_name = trim($_POST['subject_name']);
    $class = $_POST['class'];
    $is_active = $_POST['is_active'];
    
    // Validate input
    if (empty($subject_name) || empty($class)) {
        $error = "Please fill in all required fields";
    } else {
        // Get the current subject code (since it's now read-only in the form)
        $stmt = $conn->prepare("SELECT subject_code FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Subject not found";
        } else {
            $subject_data = $result->fetch_assoc();
            $subject_code = $subject_data['subject_code'];
            
            // Update subject (without changing the code)
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, class = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssi", $subject_name, $class, $is_active, $subject_id);
            
            if ($stmt->execute()) {
                $success = "Subject updated successfully";
            } else {
                $error = "Error updating subject: " . $conn->error;
            }
        }
    }
}

// Get all subjects
$subjects = [];
$result = $conn->query("SELECT * FROM subjects ORDER BY class ASC, subject_name ASC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Include header
include_once '../includes/header.php';
?>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<!-- Bootstrap CDNs -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Subject Management</h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add New Subject</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="addSubjectForm">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="subject_code">Subject Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject_code" name="subject_code" value="<?php echo isset($subject_code) ? htmlspecialchars($subject_code) : ''; ?>" required>
                                <small class="form-text text-muted">Example: MATH101, ENG201, etc.</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="subject_name">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo isset($subject_name) ? htmlspecialchars($subject_name) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="class">Class <span class="text-danger">*</span></label>
                                <select class="form-control" id="class" name="class" required>
                                    <option value="">Select Class</option>
                                    <option value="ONE">Class ONE</option>
                                    <option value="TWO">Class TWO</option>
                                    <option value="THREE">Class THREE</option>
                                    <option value="FOUR">Class FOUR</option>
                                    <option value="FIVE">Class FIVE</option>
                                    <option value="SIX">Class SIX</option>
                                    <option value="SEVEN">Class SEVEN</option>
                                    <option value="EIGHT">Class EIGHT</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="is_active">Status</label>
                                <select class="form-control" id="is_active" name="is_active">
                                    <option value="yes">Active</option>
                                    <option value="no">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manage Subjects</h5>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" id="searchSubject" class="form-control" placeholder="Search subjects...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="subjectsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No subjects found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo $subject['id']; ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['class']); ?></td>
                                            <td>
                                                <?php if ($subject['is_active'] == 'yes'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?php echo $subject['id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                        data-class="<?php echo htmlspecialchars($subject['class']); ?>"
                                                        data-active="<?php echo htmlspecialchars($subject['is_active']); ?>">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                        data-id="<?php echo $subject['id']; ?>" 
                                                        data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Subject code cannot be changed. If you need to modify the subject code, please delete this subject and add a new one.
                    </div>
                    
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    
                    <div class="form-group">
                        <label for="edit_subject_code">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_subject_code" name="subject_code" readonly disabled>
                        <small class="form-text text-muted">Subject code cannot be modified</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_subject_name">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_class">Class <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_class" name="class" required>
                            <option value="">Select Class</option>
                            <option value="ONE">Class ONE</option>
                            <option value="TWO">Class TWO</option>
                            <option value="THREE">Class THREE</option>
                            <option value="FOUR">Class FOUR</option>
                            <option value="FIVE">Class FIVE</option>
                            <option value="SIX">Class SIX</option>
                            <option value="SEVEN">Class SEVEN</option>
                            <option value="EIGHT">Class EIGHT</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_is_active">Status</label>
                        <select class="form-control" id="edit_is_active" name="is_active">
                            <option value="yes">Active</option>
                            <option value="no">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_subject" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSubjectModalLabel">Confirm Delete</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this subject?</p>
                <p><strong>Subject:</strong> <span id="delete_subject_name"></span></p>
                <p><strong>Code:</strong> <span id="delete_subject_code"></span></p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="subject_id" id="delete_subject_id">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_subject" class="btn btn-danger">Delete Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Search functionality
    $("#searchSubject").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#subjectsTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $(".alert").alert('close');
    }, 5000);
    
    // Edit subject modal
    $(".edit-btn").click(function() {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var name = $(this).data('name');
        var classValue = $(this).data('class');
        var isActive = $(this).data('active');
        
        $("#edit_subject_id").val(id);
        $("#edit_subject_code").val(code);
        $("#edit_subject_name").val(name);
        $("#edit_class").val(classValue);
        $("#edit_is_active").val(isActive);
        
        $("#editSubjectModal").modal('show');
    });
    
    // Delete subject modal
    $(".delete-btn").click(function() {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var name = $(this).data('name');
        
        $("#delete_subject_id").val(id);
        $("#delete_subject_code").text(code);
        $("#delete_subject_name").text(name);
        
        $("#deleteSubjectModal").modal('show');
    });
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>