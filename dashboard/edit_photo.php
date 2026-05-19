<?php
include('../db_connect.php');  // Include database connection

$id = $_GET['id'];  // Get the photo ID

// Fetch the photo data from the database
$sql = "SELECT * FROM gallery WHERE id = $id";
$result = $conn->query($sql);
$photo = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Update photo details in the database
    $sql = "UPDATE gallery SET photo_title = '$title', photo_description = '$description' WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo "Photo updated successfully!";
        header("Location: photo");  // Redirect to gallery page after update
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Photo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Photo</h2>
        <form action="edit_photo?id=<?php echo $id; ?>" method="post">
            <label for="title">Photo Title:</label>
            <input type="text" name="title" id="title" value="<?php echo $photo['photo_title']; ?>" required><br><br>

            <label for="description">Photo Description:</label>
            <textarea name="description" id="description" required><?php echo $photo['photo_description']; ?></textarea><br><br>

            <button type="submit">Update Photo</button>
        </form>
    </div>
</body>
</html>
