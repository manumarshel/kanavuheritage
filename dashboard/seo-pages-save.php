<?php
session_start();
if (!isset($_SESSION['id'])) exit('Unauthorized');
require_once __DIR__ . '/includes/db_connect.php';

foreach($_POST['title'] as $id => $title){
  $desc = $_POST['description'][$id] ?? '';
  $stmt = $conn->prepare("UPDATE seo_pages SET title=?, description=? WHERE id=?");
  $stmt->bind_param('ssi',$title,$desc,$id);
  $stmt->execute();
}
header("Location: seo-pages.php?saved=1");
