<?php
// Include necessary files
require_once '../config/db.php';
require_once '../includes/header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Page title
$page_title = "Manage Classes";
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-graduation-cap mr-2"></i><?php echo $page_title; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Add Class Form -->
                    <div class="row">
                        <div class="col-md-6">
                            <form method="post" action="" id="add-class-form">
                                <div class="form-group">
                                    <label for="class_code"><strong>Class Code*</strong></label>
                                    <input type="text" class="form-control" id="class_code" name="class_code" 
                                           placeholder="Enter unique class code (e.g., CLASS10)" required>
                                    <small class="form-text text-muted">Unique identifier for the class (e.g., CLASS10, 12SCIENCE)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="class_name"><strong>Class Name*</strong></label>
                                    <input type="text" class="form-control" id="class_name" name="class_name" 
                                           placeholder="Enter class name" required>
                                    <small class="form-text text-muted">Full name of the class (e.g., Class 10, 12th Science)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description"><strong>Description</strong></label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3" placeholder="Enter class description"></textarea>
                                    <small class="form-text text-muted">Optional details about the class</small>
                                </div>
                                
                                <div class="form-group">
                                    <label><strong>Status*</strong></label>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="status_active" name="is_active" 
                                               class="custom-control-input" value="yes" checked>
                                        <label class="custom-control-label" for="status_active">Active</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="status_inactive" name="is_active" 
                                               class="custom-control-input" value="no">
                                        <label class="custom-control-label" for="status_inactive">Inactive</label>
                                    </div>
                                    <small class="form-text text-muted">Set the current status of the class</small>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" name="add_class">
                                        <i class="fas fa-plus-circle mr-2"></i>Add Class
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-redo mr-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle mr-2"></i>Adding Classes</h5>
                                <hr>
                                <p>Classes are used to organize students and subjects into specific grade levels or groups.</p>
                                <ul>
                                    <li>Each class must have a unique <strong>Class Code</strong></li>
                                    <li>Provide a descriptive <strong>Class Name</strong> for easier identification</li>
                                    <li>You can add an optional description with additional details</li>
                                    <li>Set the status as active or inactive as needed</li>
                                </ul>
                                <p>Once created, you can assign students and subjects to these classes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>