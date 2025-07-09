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

// Process term deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_term']) && isset($_POST['term_id'])) {
    $term_id = intval($_POST['term_id']);

    // Check if the term is being used in exams
    $check_query = "SELECT COUNT(*) as count FROM exams WHERE term_id = $term_id";
    $check_result = mysqli_query($conn, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);

    if ($check_row['count'] > 0) {
        $error_message = "Cannot delete this term as it is being used in exams. Please reassign those exams to another term first.";
        $message_class = 'danger';
    } else {
        $delete_query = "DELETE FROM terms WHERE id = $term_id";
        if (mysqli_query($conn, $delete_query)) {
            $success_message = "Term deleted successfully.";
            $message_class = 'success';
        } else {
            $error_message = "Error deleting term: " . mysqli_error($conn);
            $message_class = 'danger';
        }
    }
}

// Fetch all terms from the database
$query = "SELECT * FROM terms ORDER BY academic_year DESC, start_date DESC";
$result = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo $page_title; ?>
                    </h6>
                    <a href="add_term.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Add New Term
                    </a>
                </div>

                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <table id="termsTable" class="table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Term Name</th>
                                <th>Term Code</th>
                                <th>Academic Year</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Current Term</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                        $isCurrent = ($row['is_current'] == 'yes') ? 'Yes' : 'No';
                                        $status = ($row['is_active'] == 'yes') ? 'Active' : 'Inactive';
                                        $start_date = date('M d, Y', strtotime($row['start_date']));
                                        $end_date = date('M d, Y', strtotime($row['end_date']));
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['term_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['term_code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                        <td><?php echo $start_date; ?></td>
                                        <td><?php echo $end_date; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($isCurrent == 'Yes') ? 'success' : 'secondary'; ?>">
                                                <?php echo $isCurrent; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo ($status == 'Active') ? 'success' : 'danger'; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-sm"
                                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['term_name'])); ?>')">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>

                                            <!-- Hidden form for deletion -->
                                            <form id="delete-form-<?php echo $row['id']; ?>" method="post" action="" style="display: none;">
                                                <input type="hidden" name="term_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="delete_term" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        No terms found. <a href="add_term.php">Add a term</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- JavaScript for confirmation dialog -->
<script>
function confirmDelete(termId, termName) {
    if (confirm('Are you sure you want to delete the term "' + termName + '"?\n\nThis action cannot be undone. If this term is associated with any exams or results, the deletion will fail.')) {
        document.getElementById('delete-form-' + termId).submit();
    }
}
</script>

<!-- Initialize DataTable -->
<script>
$(document).ready(function () {
    $('#termsTable').DataTable({
        "order": [[3, "desc"], [4, "desc"]],
        "responsive": true
    });
});
</script>
