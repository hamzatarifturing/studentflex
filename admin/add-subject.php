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
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $class = $_POST['class'];
    $is_active = $_POST['is_active'];
    
    // Validate input
    if (empty($subject_code) || empty($subject_name) || empty($class)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if subject code already exists for another subject
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
        $stmt->bind_param("si", $subject_code, $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Subject code already exists. Please use a different code.";
        } else {
            // Update subject
            $stmt = $conn->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, class = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $subject_code, $subject_name, $class, $is_active, $subject_id);
            
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
<div>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
<h2>
Subject Management

</h2>
<a>
Back to Dashboard

</a>
</div>
        <?php if (!empty($error)): ?>
<div>
                <?php echo $error; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
<div>
                <?php echo $success; ?>
<button>
<span>
×

</span>
</button>
</div>
        <?php endif; ?>
<div>
            <div class="card-header">
<h5>
Add New Subject

</h5>
</div>
<div>
                <form method="POST" action="" id="addSubjectForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
<label>
Subject Code

<span>
</span>
</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" value="<?php echo isset($subject_code) ? htmlspecialchars($subject_code) : ''; ?>" required>
<small>
Example: MATH101, ENG201, etc.

</small>
</div>
<div>
<label>
Subject Name

<span>
</span>
</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo isset($subject_name) ? htmlspecialchars($subject_name) : ''; ?>" required>
</div>
                    </div>
<div>
                        <div class="form-group col-md-6">
<label>
Class

<span>
</span>
</label>
<select>
<option>
Select Class

</option>
<option>
Class ONE

</option>
<option>
Class TWO

</option>
<option>
Class THREE

</option>
<option>
Class FOUR

</option>
<option>
Class FIVE

</option>
<option>
Class SIX

</option>
<option>
Class SEVEN

</option>
<option>
Class EIGHT

</option>
</select>
</div>
<div>
<label>
Status

</label>
<select>
<option>
Active

</option>
<option>
Inactive

</option>
</select>
</div>
                    </div>
<button>
Add Subject

</button>
                </form>
            </div>
        </div>
<div>
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
<h5>
Manage Subjects

</h5>
                    <div class="input-group" style="width: 300px;">
<i>
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fa fa-search">
</i>
</span>
</div>
                    </div>
                </div>
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
Subject Code

</th>
<th>
Subject Name

</th>
<th>
Class

</th>
<th>
Status

</th>
<th>
Created At

</th>
<th>
Actions

</th>
</tr>
</thead>
<tbody>
                            <?php if (empty($subjects)): ?>
<tr>
<td>
No subjects found

</td>
</tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
<tr>
<td>
<?php echo $subject['id']; ?>
</td>
<td>
<?php echo htmlspecialchars($subject['subject_code']); ?>
</td>
<td>
<?php echo htmlspecialchars($subject['subject_name']); ?>
</td>
<td>
<?php echo htmlspecialchars($subject['class']); ?>
</td>
<td>
                                            <?php if ($subject['is_active'] == 'yes'): ?>
<span>
Active

</span>
                                            <?php else: ?>
<span>
Inactive

</span>
                                            <?php endif; ?>
</td>
<td>
<?php echo date('M d, Y', strtotime($subject['created_at'])); ?>
</td>
<td>
<button>
"
data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
data-class="<?php echo htmlspecialchars($subject['class']); ?>"
data-active="<?php echo htmlspecialchars($subject['is_active']); ?>">

<i class="fa fa-edit"></i>

Edit

</button>
<button>
"
data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>">

<i class="fa fa-trash"></i>

Delete

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
</div> <!-- Edit Subject Modal -->
<div>
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
<h5>
Edit Subject

</h5>
<button>
<span>
×

</span>
</button>
</div>
<form>
<div>
                <input type="hidden" name="subject_id" id="edit_subject_id">
                
                <div class="form-group">
<label>
Subject Code

<span>
</span>
</label>
                    <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
</div>
<div>
<label>
Subject Name

<span>
</span>
</label>
                    <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
</div>
<div>
<label>
Class

<span>
</span>
</label>
<select>
<option>
Select Class

</option>
<option>
Class ONE

</option>
<option>
Class TWO

</option>
<option>
Class THREE

</option>
<option>
Class FOUR

</option>
<option>
Class FIVE

</option>
<option>
Class SIX

</option>
<option>
Class SEVEN

</option>
<option>
Class EIGHT

</option>
</select>
</div>
<div>
<label>
Status

</label>
<select>
<option>
Active

</option>
<option>
Inactive

</option>
</select>
</div>
            </div>
<div>
<button>
Cancel

</button>
<button>
Save Changes

</button>
</div>
</form>
    </div>
</div>
</div> <!-- Delete Subject Modal -->
<div>
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header bg-danger text-white">
<h5>
Confirm Delete

</h5>
<button>
<span>
×

</span>
</button>
</div>
<div>
<p>
Are you sure you want to delete this subject?

</p>
<p>
<strong>
Subject:

</strong>
<span id="delete_subject_name"></span>

</p>
<p>
<strong>
Code:

</strong>
<span id="delete_subject_code"></span>

</p>
<p>
This action cannot be undone!

</p>
</div>
<div>
<form>
                <input type="hidden" name="subject_id" id="delete_subject_id">
<button>
Cancel

</button>
<button>
Delete Subject

</button>
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
            
(
t
h
i
s
)
.
t
o
g
g
l
e
(
(this).toggle((this).text().toLowerCase().indexOf(value) > -1)
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
<?php // Include footer include_once '../includes/footer.php'; ?>