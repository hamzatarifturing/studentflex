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
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>