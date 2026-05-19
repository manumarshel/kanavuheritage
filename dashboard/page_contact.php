<?php
// /kanav/dashboard/page_contact
session_start();
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .content-wrapper{padding:24px}
    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;padding:16px;margin-bottom:12px}
    .actions{display:flex;gap:10px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}
    .muted{color:#777}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <div class="content-wrapper">
      <h2>Contact (Admin)</h2>

      <div class="card">
        <p class="muted">This page is for quick access related to Contact. The public contact form lives at <code>/kanav/contact</code> and posts to <code>/kanav/mail/send_mail.php</code>.</p>
        <div class="actions">
          <a class="btn-lite" href="../contact" target="_blank">Open public Contact page</a>
          <a class="btn-lite" href="mailto:mail@kanavuheritage.com">Email: mail@kanavuheritage.com</a>
          <a class="btn-lite" href="mailto:kanavuheritage@gmail.com">Email: kanavuheritage@gmail.com</a>
          <a class="btn" href="tel:+919567047633">Call: +91 95670 47633</a>
        </div>
      </div>

      <div class="card">
        <h3>How it works</h3>
        <ol>
          <li>The visitor fills the form on <code>/kanav/contact</code>.</li>
          <li>The form submits to <code>/kanav/mail/send_mail.php</code>.</li>
          <li>On success, they’re redirected back with <code>?ok=1</code>; on error, the fields are preserved.</li>
        </ol>
      </div>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
