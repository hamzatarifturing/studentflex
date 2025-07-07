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
<div>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
<h6>
<i class="fas fa-graduation-cap mr-2"></i>

<?php echo $page_title; ?>
</h6>
<a>
<i class="fas fa-plus-circle mr-1"></i>

Add New Class

</a>
</div>
<div>
                <!-- Class list will be displayed here in future implementations -->
                <div class="alert alert-info">
<i class="fas fa-info-circle mr-2"></i>

This page will display the list of classes. Implementation coming soon.

</div>
            </div>
        </div>
    </div>
</div>
</div> <?php // Include footer 
require_once '../includes/footer.php'; ?>