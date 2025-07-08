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
$page_title = "Manage Terms";
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-2"></i><?php echo $page_title; ?>
                    </h6>
                    <a href="view-terms.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-list mr-1"></i> View All Terms
                    </a>
                </div>
                <div class="card-body">
                    <form action="" method="POST" id="add-term-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="term_name">Term Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="term_name" name="term_name" required placeholder="e.g., First Term, Second Term">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="term_code">Term Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="term_code" name="term_code" required placeholder="e.g., T1, T2">
                                    <small class="form-text text-muted">Unique identifier for the term.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year">Academic Year <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" required placeholder="e.g., 2025-2026">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Term Status</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_current" name="is_current" value="yes">
                                        <label class="custom-control-label" for="is_current">Set as Current Term</label>
                                        <small class="form-text text-muted">Make this the active term for new enrollments and records.</small>
                                    </div>
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="yes" checked>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                        <small class="form-text text-muted">Inactive terms won't be available for selection.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter additional information about the term (optional)"></textarea>
                        </div>

                        <hr>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary" name="add_term">
                                <i class="fas fa-save mr-2"></i>Add Term
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo mr-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>

<script>
// Client-side validation
document.getElementById('add-term-form').addEventListener('submit', function(e) {
    var startDate = new Date(document.getElementById('start_date').value);
    var endDate = new Date(document.getElementById('end_date').value);
    
    if (endDate < startDate) {
        e.preventDefault();
        alert('End date cannot be earlier than start date.');
    }
    
    var termCode = document.getElementById('term_code').value;
    if (termCode.length < 2 || termCode.length > 10) {
        e.preventDefault();
        alert('Term code should be between 2-10 characters.');
    }
});
</script>