<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}


include '../includes/connect.php';
$id = intval($_GET['id']);
$blog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM blog WHERE id=$id"));

if (isset($_POST['submit'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $blog_type = mysqli_real_escape_string($conn, $_POST['blog_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = $_POST['date'];
    $author_name = mysqli_real_escape_string($conn, $_POST['author_name']);
    $img = $blog['image']; // keep old filename

    // Handle image upload
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../media/blogs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Image must be JPG/PNG'); window.history.back();</script>";
            exit;
        }

        // ✅ save only filename in DB
        $new_img_name = time() . "_blog.$ext";
        $new_img_path = $upload_dir . $new_img_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_img_path)) {
            // delete old file if exists
            if (!empty($img) && file_exists($upload_dir . $img)) {
                unlink($upload_dir . $img);
            }
            $img = $new_img_name; // ✅ only filename in DB
        } else {
            echo "<script>alert('Failed to upload image'); window.history.back();</script>";
            exit;
        }
    }

    // Update query
    mysqli_query($conn, "UPDATE blog 
        SET title='$title', blog_type='$blog_type', description='$description', date='$date', author_name='$author_name', image='$img' 
        WHERE id=$id");

    echo "<script>alert('Blog updated successfully!'); window.location.href='blog';</script>";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Blog</title>
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .form-wrapper {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border: 2px solid #b19777;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
        }
        .form-wrapper h3 { text-align:center; margin-bottom:20px; color:#222; }
        label { display:block; margin-top:15px; font-weight:bold; color:#333; }
        input, textarea, select {
            width:100%; padding:10px; margin-top:5px;
            border-radius:6px; border:1px solid #ccc; font-size:15px;
        }
        textarea { min-height:120px; resize:vertical; }
        button {
            background:#b19777; color:black; padding:10px 25px;
            border:none; border-radius:6px; margin-top:20px; cursor:pointer;
        }
        button:hover { background:#1ab9de; }
    </style>
</head>
<body>
<div class="container">
    <?php include('includes/sidebar.php'); ?>
    <div class="main">
        <?php include('includes/topbar.php'); ?>

        <div class="form-wrapper">
            <h3>Edit Blog</h3>
            <form method="POST" enctype="multipart/form-data">

                <label>Blog Type:</label>
                <input type="text" name="blog_type" value="<?= htmlspecialchars($blog['blog_type']) ?>" required>
                <label>Blog Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($blog['title']) ?>" required>


                <label>Description:</label>
                <textarea name="description" required><?= htmlspecialchars($blog['description']) ?></textarea>

                <label>Image (leave empty to keep existing):</label>
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($blog['image'])): ?>
                    <img src="../media/blogs/<?= htmlspecialchars($blog['image']) ?>" width="120" style="margin-top:10px; border:1px solid #ddd; border-radius:6px; padding:3px;">

                <?php endif; ?>

                <label>Date:</label>
                <input type="date" name="date" value="<?= $blog['date'] ?>" required>

                <label>Author Name:</label>
                <input type="text" name="author_name" value="<?= htmlspecialchars($blog['author_name']) ?>" required>

                <br><br>
                <button type="submit" name="submit">Update Blog</button>
            </form>

            <div style="text-align:center; margin-top:20px;">
                <button type="button" onclick="history.back()" style="background-color:#6c757d; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">
                    Go Back
                </button>
            </div>
        </div>
    </div>
</div>

<script src="./js/main.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
