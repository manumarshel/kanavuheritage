<?php
include('../db_connect.php');  // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the data from the form
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Handle the uploaded file
    $target_dir = "../img/gallery/";
    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if the image file is valid
    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        // Insert data into the database
        $sql = "INSERT INTO gallery (photo_title, photo_description, photo_path)
                VALUES ('$title', '$description', '$target_file')";
        
        if ($conn->query($sql) === TRUE) {
            echo "New photo uploaded successfully!";
            header("Location: photo");  // Redirect to gallery page after upload
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload New Photo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Upload New Photo</h2>
        <form action="upload_photo" method="post" enctype="multipart/form-data">
            <label for="title">Photo Title:</label>
            <input type="text" name="title" id="title" required><br><br>

            <label for="description">Photo Description:</label>
            <textarea name="description" id="description" required></textarea><br><br>

            <label for="photo">Select Photo:</label>
            <input type="file" name="photo" id="photo" required><br><br>

            <button type="submit">Upload Photo</button>
        </form>
    </div>
</body>
</html>
