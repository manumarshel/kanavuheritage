<?php
include('../db_connect.php');  // Include database connection

$id = $_GET['id'];  // Get the photo ID

// Delete photo from the database
$sql = "DELETE FROM gallery WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    echo "Photo deleted successfully!";
    header("Location: photo");  // Redirect to gallery page after deletion
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
