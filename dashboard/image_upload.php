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

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4'); // optional, to ensure UTF-8 encoding

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = '';
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is an image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = $targetFile;

            // Insert image into database
            $stmt = $conn->prepare("INSERT INTO gallery (image_path, media_type) VALUES (?, ?)");
            $stmt->bind_param("ss", $imagePath, $media_type = 'photo');
            if ($stmt->execute()) {
                $flashMessage = 'Image uploaded successfully!';
            } else {
                $flashMessage = 'Failed to upload image.';
            }
            $stmt->close();
        } else {
            $flashMessage = 'Sorry, there was an error uploading your file.';
        }
    } else {
        $flashMessage = 'File is not an image.';
    }
}

// Display flash message if any
if (isset($flashMessage)) {
    echo "<div class='alert'>{$flashMessage}</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Image | Dashboard</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/topbar.php'; ?>
        <div class="content-wrapper">
            <h2>Upload New Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="field">
                    <label for="image">Select Image</label>
                    <input type="file" name="image" required>
                </div>
                <button type="submit" class="btn">Upload Image</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
