<?php
// Database configuration
$host = 'localhost';
$dbname = 'studentflex';
$username = 'root';
$password = '';

// Establish database connection
try {
    $conn = mysqli_connect($host, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    // Set character set to utf8
    mysqli_set_charset($conn, "utf8");
    
} catch (Exception $e) {
    // Log error to a file instead of displaying it
    error_log("Database Connection Error: " . $e->getMessage(), 0);
    
    // Display user-friendly message
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}

// Function to sanitize input data
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Helper function to handle database errors
function handleDBError($conn) {
    error_log("Database Error: " . mysqli_error($conn), 0);
    return "An error occurred during the database operation.";
}