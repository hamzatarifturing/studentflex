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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Begin transaction for data integrity
    $conn->begin_transaction();
    
    try {
        // Get student details before deletion (for success message)
        $stmt = $conn->prepare("SELECT u.full_name, s.student_id FROM users u 
                                JOIN students s ON u.id = s.user_id 
                                WHERE u.id = ? AND u.role = 'student'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Delete from students table first (need to delete manually due to possible constraints)
        $stmt = $conn->prepare("DELETE FROM students WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Then delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete student");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success'] = "Student " . htmlspecialchars($student['full_name']) . " (ID: " . htmlspecialchars($student['student_id']) . ") has been deleted successfully";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
    
    // Determine redirect location
    if (isset($_POST['redirect']) && $_POST['redirect'] === 'add-student') {
        header('Location: add-student.php');
    } else {
        header('Location: view-student.php?id=' . $id);
    }
    exit;
} else {
    $_SESSION['error'] = "Invalid request";
    header('Location: add-student.php');
    exit;
}
?>