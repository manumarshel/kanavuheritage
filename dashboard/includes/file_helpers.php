<?php
function safe_filename($name){
  $ext = pathinfo($name, PATHINFO_EXTENSION);
  $base = preg_replace('/[^a-z0-9\-]+/i', '-', pathinfo($name, PATHINFO_FILENAME));
  return strtolower($base . '-' . uniqid() . ($ext?'.'.$ext:''));
}

function upload_image($file, $targetDir){
  if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return [false, "No file uploaded"];
  if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
  $fname = safe_filename($file['name']);
  $dest = rtrim($targetDir,'/') . '/' . $fname;
  if (!move_uploaded_file($file['tmp_name'], $dest)) return [false, "Move failed"];
  return [true, $fname];
}

function delete_image_if_exists($dir, $filename){
  if (!$filename) return;
  $path = rtrim($dir,'/') . '/' . $filename;
  if (is_file($path)) @unlink($path);
}
