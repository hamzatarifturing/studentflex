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
<div>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
<h6>
<i class="fas fa-calendar-alt mr-2"></i>

<?php echo $page_title; ?>
</h6>
<a>
<i class="fas fa-plus-circle mr-1"></i>

Add New Term

</a>
</div>
<div>
                <!-- Content will be implemented in future interactions -->
</div>
        </div>
    </div>
</div>
</div> <?php // Include footer 
require_once '../includes/footer.php'; ?>
