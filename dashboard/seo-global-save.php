<?php
session_start();
if (!isset($_SESSION['id'])) exit('Unauthorized');
require_once __DIR__ . '/includes/db_connect.php';

$title = trim($_POST['site_title'] ?? '');
$desc  = trim($_POST['site_description'] ?? '');
$file  = $_FILES['og_image'] ?? null;

$filename = null;
if ($file && $file['size']>0){
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'og_'.time().'.'.$ext;
  move_uploaded_file($file['tmp_name'], __DIR__."/../uploads/seo/$filename");
}

$exists = $conn->query("SELECT COUNT(*) as c FROM seo_global")->fetch_assoc()['c'] ?? 0;
if($exists){
  $sql = "UPDATE seo_global SET site_title=?, site_description=?".($filename?", og_image=?":"")." WHERE id=1";
  $stmt = $conn->prepare($sql);
  if($filename) $stmt->bind_param('sss',$title,$desc,$filename);
  else $stmt->bind_param('ss',$title,$desc);
} else {
  $stmt = $conn->prepare("INSERT INTO seo_global(site_title,site_description,og_image) VALUES(?,?,?)");
  $stmt->bind_param('sss',$title,$desc,$filename);
}
$stmt->execute();

header("Location: seo-global.php?saved=1");
