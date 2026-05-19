<?php
session_start();
include '../includes/connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid request'); window.location.href='blog';</script>";
    exit;
}

$id = intval($_GET['id']);

// Fetch blog to get image path
$result = mysqli_query($conn, "SELECT * FROM blog WHERE id=$id");
$blog = mysqli_fetch_assoc($result);

if (!$blog) {
    echo "<script>alert('Blog not found'); window.location.href='blog';</script>";
    exit;
}

// Delete image file if exists
if (!empty($blog['image']) && file_exists($blog['image'])) {
    unlink($blog['image']);
}

// Delete blog record
$delete = mysqli_query($conn, "DELETE FROM blog WHERE id=$id");

if ($delete) {
    echo "<script>alert('Blog deleted successfully!'); window.location.href='blog';</script>";
} else {
    echo "<script>alert('Error deleting blog.'); window.location.href='blog';</script>";
}
?>
