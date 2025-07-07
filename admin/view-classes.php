
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

// Process form submissions for activating/deactivating classes
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['activate_class']) && isset($_POST['class_id'])) {
        $class_id = intval($_POST['class_id']);
        $update_query = "UPDATE classes SET is_active = 'yes' WHERE id = " . $class_id;
        mysqli_query($conn, $update_query);
        // Redirect to avoid resubmission
        header('Location: view-classes.php?msg=activated');
        exit();
    }
    
    if (isset($_POST['deactivate_class']) && isset($_POST['class_id'])) {
        $class_id = intval($_POST['class_id']);
        $update_query = "UPDATE classes SET is_active = 'no' WHERE id = " . $class_id;
        mysqli_query($conn, $update_query);
        // Redirect to avoid resubmission
        header('Location: view-classes.php?msg=deactivated');
        exit();
    }
}

// Display status messages
if (isset($_GET['msg'])) {
    $message = '';
    $message_class = '';
    
    if ($_GET['msg'] == 'activated') {
        $message = 'Class has been activated successfully.';
        $message_class = 'success';
    } else if ($_GET['msg'] == 'deactivated') {
        $message = 'Class has been deactivated successfully.';
        $message_class = 'warning';
    }
}

// Fetch all classes from database
$query = "SELECT * FROM classes ORDER BY class_name ASC";
$result = mysqli_query($conn, $query);
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
                    <a href="add-classes.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i> Add New Class
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($message) && !empty($message)): ?>
                        <div class="alert alert-<?php echo $message_class; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="classesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Code</th>
                                    <th>Class Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $status = ($row['is_active'] == 'yes') ? 'Active' : 'Inactive';
                                        $statusClass = ($row['is_active'] == 'yes') ? 'success' : 'danger';
                                        
                                        // Handle null description in PHP 5 compatible way
                                        $description = isset($row['description']) && $row['description'] != '' ? 
                                            htmlspecialchars($row['description']) : 'No description available';
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['class_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                            <td><?php echo $description; ?></td>
                                            <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                            <td>
                                                <form method="post" action="" class="d-inline">
                                                    <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                                                    <?php if($row['is_active'] == 'yes'): ?>
                                                        <button type="submit" name="deactivate_class" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times-circle mr-1"></i> Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="activate_class" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check-circle mr-1"></i> Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No classes found. <a href="add-classes.php">Add a class</a></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
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