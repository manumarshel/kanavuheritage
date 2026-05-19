<?php
// Database configuration
$servername = "localhost"; // Your server (usually localhost for local dev)
$username = "root";        // Your database username (usually "root" for local dev)
$password = "";            // Your database password (usually empty for local dev)
$dbname = "kanavu";        // Your database name (should match the name in phpMyAdmin)

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
