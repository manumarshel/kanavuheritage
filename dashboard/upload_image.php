<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kanav";  // Replace with your database name
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4'); // optional, to ensure UTF-8 encoding

// Define upload directory
$uploadDir = 'uploads/gallery/'; // Folder to store uploaded images

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;

        // Validate image type (only allow photos)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Insert the file path into the database
                $stmt = $conn->prepare("INSERT INTO gallery (image_path, media_type) VALUES (?, ?)");
                $stmt->bind_param('ss', $targetFile, $mediaType);
                $mediaType = 'photo';  // Set default media type to photo
                $stmt->execute();
                $stmt->close();
                header("Location: page-gallery-photo?upload_success=1");
                exit();
            } else {
                echo "Error uploading image.";
            }
        } else {
            echo "Only images are allowed.";
        }
    } else {
        echo "No image uploaded or upload error.";
    }
}

$conn->close();
?>
