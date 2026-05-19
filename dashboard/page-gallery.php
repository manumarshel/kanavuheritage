<?php
// /dashboard/photo
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/../includes/connect.php'; // keep your DB connection

// --- Safe counters (won't crash if table missing) ---
$photoCount = 0;
$videoCount = 0;

// photos
if ($conn) {
  try {
    $res = $conn->query("SHOW TABLES LIKE 'gallery'");
    if ($res && $res->num_rows) {
      $q = $conn->query("SELECT COUNT(*) AS c FROM gallery");
      if ($q && ($row = $q->fetch_assoc())) $photoCount = (int)$row['c'];
    }
  } catch (Throwable $e) { /* ignore */ }
}

// videos (adjust table name/columns to yours if different)
if ($conn) {
  try {
    $res = $conn->query("SHOW TABLES LIKE 'video_gallery'");
    if ($res && $res->num_rows) {
      $q = $conn->query("SELECT COUNT(*) AS c FROM video_gallery");
      if ($q && ($row = $q->fetch_assoc())) $videoCount = (int)$row['c'];
    }
  } catch (Throwable $e) { /* ignore */ }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gallery | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:28px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}

    .pillbar{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid #3b2f1b;border-radius:999px;background:#b19777;color:#111;text-decoration:none;font-weight:800}
    .pill:hover{background:#c4ac8b}

    .grid{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}

    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
    .card-hd{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #eee}
    .card-tt{font-weight:800}
    .card-bd{padding:16px}
    .muted{color:#777}
    .count{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:10px;background:#f3efe9;border:1px solid #e7e0d7;font-weight:700}

    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Gallery</h2></div>
        <div class="pillbar">
          <!-- Dashboard inner pages -->
          <a class="pill" href="page-gallery-photo">Photo Gallery</a>
          <a class="pill" href="page-gallery-video.php">Video Gallery</a>
        </div>
      </div>

      <div class="grid">
        <!-- Photos card -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Photos</div>
            <span class="count"><?= (int)$photoCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Manage all images shown on the public Gallery page. Upload, preview, and delete photos safely.</p>
            <div class="actions">
              <a class="btn" href="page-gallery-photo">Manage Photos</a>
              <a class="btn btn-lite" href="../photo" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>

        <!-- Videos card -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Videos</div>
            <span class="count"><?= (int)$videoCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Add YouTube/Vimeo links or upload thumbnails that appear on the public Video Gallery page.</p>
            <div class="actions">
              <a class="btn" href="page-gallery-video.php">Manage Videos</a>
              <a class="btn btn-lite" href="../video.php" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>
      </div><!-- /grid -->
    </div><!-- /content-wrapper -->
  </div>
</div>

<script src="./js/main.js"></script>
</body>
</html>
