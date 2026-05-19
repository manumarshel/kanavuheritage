<?php


// Ensure the user is logged in as an admin

include('../includes/connect.php'); // Include database connection

// Define the admin username and password
$username = 'kanavheritage'; // The username for the new admin user
$password_plain = 'kanavheritage@123'; // The plain text password for the new admin user

// Encrypt the password using password_hash
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// Check if the database connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and bind the statement to insert the new admin user into the database
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password_hashed);

// Execute the query to insert the new admin user
if ($stmt->execute()) {
    echo "Admin user created successfully.";
} else {
    echo "Error: " . $stmt->error;
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>