<?php
// /kanav/dashboard/seo-global.php
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

// ---------- PATHS ----------
$DASH_DIR = __DIR__;
$ROOT_DIR = dirname(__DIR__);
$INC_DIR  = $ROOT_DIR . DIRECTORY_SEPARATOR . 'includes';

// ---------- DB CONNECT ----------
$connected = false;
if (is_file($INC_DIR . DIRECTORY_SEPARATOR . 'connect.php')) {
  require_once $INC_DIR . DIRECTORY_SEPARATOR . 'connect.php'; // sets $conn (mysqli)
  if (isset($conn) && $conn instanceof mysqli) { $connected = true; }
}
if (!$connected && is_file($DASH_DIR . '/includes/db_connect.php')) {
  require_once $DASH_DIR . '/includes/db_connect.php';
  if (isset($conn) && $conn instanceof mysqli) { $connected = true; }
}
if (!$connected) {
  die('DB connect file not found. Expected: /includes/connect.php or /dashboard/includes/db_connect.php');
}
$conn->set_charset('utf8mb4');

// ---------- ENSURE TABLE ----------
$conn->query("
  CREATE TABLE IF NOT EXISTS seo_global (
    id INT PRIMARY KEY DEFAULT 1,
    site_title VARCHAR(255) NOT NULL DEFAULT '',
    site_description TEXT NOT NULL,
    og_image VARCHAR(255) NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$conn->query("INSERT IGNORE INTO seo_global (id, site_title, site_description, og_image)
              VALUES (1,'Kanavu Heritage | Best Heritage Home In Kerala','Experience a serene heritage homestay in Kerala.','')");

// ---------- LOAD CURRENT ----------
$row = $conn->query("SELECT site_title, site_description, og_image FROM seo_global WHERE id=1")->fetch_assoc();
$site_title       = $row['site_title']       ?? '';
$site_description = $row['site_description'] ?? '';
$og_image         = $row['og_image']         ?? '';

// ---------- FORM SUBMIT ----------
$msg_ok = '';
$msg_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_title = trim($_POST['site_title'] ?? '');
  $new_desc  = trim($_POST['site_description'] ?? '');
  $new_img   = $og_image; // keep existing by default

  // handle file upload (optional)
  if (!empty($_FILES['og_image']['name']) && is_uploaded_file($_FILES['og_image']['tmp_name'])) {
    $upload_dir = $ROOT_DIR . '/uploads/seo';
    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0775, true); }
    $ext = strtolower(pathinfo($_FILES['og_image']['name'], PATHINFO_EXTENSION));
    $ok_ext = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $ok_ext, true)) {
      $msg_err = 'Please upload a JPG, PNG or WEBP image.';
    } else {
      $fname = 'og_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $dest  = $upload_dir . '/' . $fname;
      if (move_uploaded_file($_FILES['og_image']['tmp_name'], $dest)) {
        // store only filename; frontend will prepend /uploads/seo/
        $new_img = $fname;
      } else {
        $msg_err = 'Failed to save uploaded image.';
      }
    }
  }

  if (!$msg_err) {
    $stmt = $conn->prepare("UPDATE seo_global SET site_title=?, site_description=?, og_image=? WHERE id=1");
    $stmt->bind_param('sss', $new_title, $new_desc, $new_img);
    if ($stmt->execute()) {
      $msg_ok = 'Saved!';
      $site_title       = $new_title;
      $site_description = $new_desc;
      $og_image         = $new_img;
    } else {
      $msg_err = 'Database update failed.';
    }
    $stmt->close();
  }
}

// ---------- helpers ----------
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$og_image_url = $og_image ? ('/uploads/seo/' . $og_image) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard · Site Meta (Global)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body {font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;background:#f7f8fa}
    .wrap {padding: 24px;}
    .card {background: #fff; border:1px solid #e5e5e5; border-radius:12px; padding:20px; max-width:920px;}
    .row {display:grid; grid-template-columns: 1fr; gap:12px;}
    label {font-weight:600; color:#333;}
    input[type="text"], textarea {width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:8px;}
    textarea {min-height: 120px; resize: vertical;}
    .btn {display:inline-block; background:#1f7a54; color:#fff; border:0; border-radius:10px; padding:10px 16px; cursor:pointer; font-weight:700}
    .btn:hover {background:#219d6b;}
    .note {color:#666; font-size: 13px;}
    .alert {padding:10px 12px; border-radius:10px; margin-bottom:12px;}
    .ok {background:#effaf0; border:1px solid #c6e8cc; color:#20603a;}
    .err{background:#fff5f5; border:1px solid #f3c2c2; color:#8a1f1f;}
    .thumb {margin-top:8px;}
    .thumb img{max-height:120px; border-radius:8px; border:1px solid #eee;}
    .topnav {display:flex; align-items:center; gap:12px; margin-bottom:16px;}
    .topnav a {text-decoration:none; color:#1f7a54; font-weight:700;}
    .topnav .sep {color:#aaa;}
  </style>
</head>
<body>
<div class="container">
  <?php if (is_file($DASH_DIR . '/includes/sidebar.php')) include $DASH_DIR . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php if (is_file($DASH_DIR . '/includes/topbar.php')) include $DASH_DIR . '/includes/topbar.php'; ?>

    <div class="wrap">
      <div class="topnav">
        <a href="index">Dashboard</a><span class="sep">›</span>
        <strong>Site Meta (Global)</strong>
      </div>

      <div class="card">
        <h2 style="margin-top:0">Global Site Meta</h2>

        <?php if ($msg_ok): ?><div class="alert ok"><?= h($msg_ok) ?></div><?php endif; ?>
        <?php if ($msg_err): ?><div class="alert err"><?= h($msg_err) ?></div><?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" class="row">
          <div>
            <label for="site_title">Site Title</label>
            <input type="text" id="site_title" name="site_title" value="<?= h($site_title) ?>" required>
            <div class="note">Default &lt;title&gt; for pages that don’t have specific titles.</div>
          </div>

          <div>
            <label for="site_description">Site Description</label>
            <textarea id="site_description" name="site_description" required><?= h($site_description) ?></textarea>
            <div class="note">Default meta description for pages that don’t have specific descriptions.</div>
          </div>

          <div>
            <label for="og_image">Default OG/Twitter Image (1200×630 recommended)</label>
            <input type="file" id="og_image" name="og_image" accept=".jpg,.jpeg,.png,.webp">
            <?php if ($og_image_url): ?>
              <div class="thumb"><img src="<?= h($og_image_url) ?>" alt="Current OG Image"></div>
              <div class="note">Current file: <?= h($og_image) ?></div>
            <?php else: ?>
              <div class="note">No image uploaded yet.</div>
            <?php endif; ?>
          </div>

          <div>
            <button type="submit" class="btn">Save</button>
          </div>
        </form>
      </div>

      <div style="height:24px"></div>

      <div class="card">
        <h3 style="margin-top:0">How it works</h3>
        <ol>
          <li>Frontend pages include <code>/includes/seo_meta.php</code> and call <code>kh_build_meta()</code>.</li>
          <li>If a page has no row in <code>seo_pages</code>, it falls back to these global values.</li>
          <li>Default OG image is served from <code>/uploads/seo/</code>.</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
