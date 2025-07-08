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

// Fetch all terms from database
$query = "SELECT * FROM terms ORDER BY academic_year DESC, start_date DESC";
$result = mysqli_query($conn, $query);
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
                <div class="table-responsive">
<table>
<thead>
<tr>
<th>
ID

</th>
<th>
Term Name

</th>
<th>
Term Code

</th>
<th>
Academic Year

</th>
<th>
Start Date

</th>
<th>
End Date

</th>
<th>
Current Term

</th>
<th>
Status

</th>
</tr>
</thead>
<tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $isCurrent = ($row['is_current'] == 'yes') ? 'Yes' : 'No';
                                    $currentClass = ($row['is_current'] == 'yes') ? 'success' : 'secondary';
                                    
                                    $status = ($row['is_active'] == 'yes') ? 'Active' : 'Inactive';
                                    $statusClass = ($row['is_active'] == 'yes') ? 'success' : 'danger';
                                    
                                    // Format dates for better readability
                                    $start_date = date('M d, Y', strtotime($row['start_date']));
                                    $end_date = date('M d, Y', strtotime($row['end_date']));
                                    ?>
<tr>
<td>
<?php echo $row['id']; ?>
</td>
<td>
<?php echo htmlspecialchars($row['term_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($row['term_code']); ?>
</td>
<td>
<?php echo htmlspecialchars($row['academic_year']); ?>
</td>
<td>
<?php echo $start_date; ?>
</td>
<td>
<?php echo $end_date; ?>
</td>
<td>
<span>
"><?php echo $isCurrent; ?>

</span>
</td>
<td>
<span>
"><?php echo $status; ?>

</span>
</td>
</tr>
                                    <?php
                                }
                            } else {
                                ?>
<tr>
<td>
No terms found.

<a>
Add a term

</a>
</td>
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
</div> <?php // Include footer 
require_once '../includes/footer.php'; ?> <!-- Initialize the DataTable for better user experience -->
<script>
$(document).ready(function() {
$('#termsTable').DataTable({
"order": [[ 3, "desc" ], [ 4, "desc" ]], // Sort by academic year and then start date
"responsive": true
});
});

</script>