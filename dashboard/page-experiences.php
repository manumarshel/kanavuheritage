<?php
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Experiences | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .content-wrapper{padding:28px}
    .pillbar{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid #3b2f1b;border-radius:999px;background:#b19777;color:#111;text-decoration:none;font-weight:800}
    .pill:hover{background:#c4ac8b}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <div class="content-wrapper">
      <h2>Experiences</h2>
      <div class="pillbar">
        <!-- FIXED: link goes to page-local-delights.php -->
        <a class="pill" href="page-local-delights.php">Local Delights</a>
        <a class="pill" href="page-vicinity">Vicinity Attractions</a>
      </div>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
