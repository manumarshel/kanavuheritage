<?php
// /dashboard/page-gallery-video.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- ensure upload dir ---------- */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/video');
if (!$UPLOAD_DIR) {
  @mkdir(__DIR__ . '/../media/video', 0775, true);
  $UPLOAD_DIR = realpath(__DIR__ . '/../media/video');
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf(){ return $_SESSION['csrf']; }
function csrf_ok($t){ return hash_equals($_SESSION['csrf']??'', $t??''); }

/* ---------- flash ---------- */
$flash=function($k,$v=null){ if($v!==null){ $_SESSION['flash'][$k]=$v; return; } $m=$_SESSION['flash'][$k]??null; unset($_SESSION['flash'][$k]); return $m; };

/* ---------- helpers ---------- */
function safe_name($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_thumb($field, $destAbsDir){
  if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $mime = mime_content_type($_FILES[$field]['tmp_name']);
  $ok = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if (!isset($ok[$mime])) return null;
  $ext = $ok[$mime];
  $fn  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  @move_uploaded_file($_FILES[$field]['tmp_name'], $destAbsDir . DIRECTORY_SEPARATOR . $fn);
  return 'media/video/' . $fn; // web path
}
function youtube_thumb_from_url($url){
  // supports youtu.be/ID and youtube.com/watch?v=ID
  if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)) return "https://img.youtube.com/vi/{$m[1]}/hqdefault.jpg";
  if (preg_match('#v=([A-Za-z0-9_-]{6,})#', $url, $m)) return "https://img.youtube.com/vi/{$m[1]}/hqdefault.jpg";
  return null;
}

/* ---------- table (create if missing) ---------- */
$conn->query("CREATE TABLE IF NOT EXISTS video_gallery (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) DEFAULT NULL,
  video_url VARCHAR(500) NOT NULL,
  thumb_path VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ---------- actions ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_ok($_POST['csrf']??'')) { $flash('err','Invalid token'); header('Location: page-gallery-video.php'); exit; }
  $action = $_POST['action'] ?? '';

  if ($action==='add') {
    $title = trim($_POST['title'] ?? '');
    $url   = trim($_POST['video_url'] ?? '');
    $thumb = save_thumb('thumb', $UPLOAD_DIR);

    if (!$thumb) {
      $auto = youtube_thumb_from_url($url);
      if ($auto) $thumb = $auto; // external URL
    }
    if ($url) {
      $stmt = $conn->prepare("INSERT INTO video_gallery(title, video_url, thumb_path) VALUES(?,?,?)");
      $stmt->bind_param('sss', $title, $url, $thumb);
      $stmt->execute();
      $flash('ok','Video added.');
    } else {
      $flash('err','Video URL is required.');
    }
    header('Location: page-gallery-video.php'); exit;
  }

  if ($action==='update') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $url   = trim($_POST['video_url'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    $newThumb = save_thumb('thumb', $UPLOAD_DIR);
    if ($newThumb) {
      $stmt = $conn->prepare("UPDATE video_gallery SET title=?, video_url=?, thumb_path=?, is_active=? WHERE id=?");
      $stmt->bind_param('sssii', $title, $url, $newThumb, $active, $id);
    } else {
      $stmt = $conn->prepare("UPDATE video_gallery SET title=?, video_url=?, is_active=? WHERE id=?");
      $stmt->bind_param('ssii', $title, $url, $active, $id);
    }
    $stmt->execute();
    $flash('ok','Video updated.');
    header('Location: page-gallery-video.php'); exit;
  }
}

if (isset($_GET['delete'], $_GET['csrf'])) {
  if (!csrf_ok($_GET['csrf'])) { $flash('err','Invalid token'); header('Location: page-gallery-video.php'); exit; }
  $id = (int)$_GET['delete'];
  $conn->query("DELETE FROM video_gallery WHERE id={$id} LIMIT 1");
  $flash('ok','Deleted.');
  header('Location: page-gallery-video.php'); exit;
}

/* ---------- fetch ---------- */
$list = $conn->query("SELECT * FROM video_gallery ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Video Gallery · Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden;margin-bottom:12px}
    .card-hd{padding:14px 16px;border-bottom:1px solid #eee;font-weight:800}
    .card-bd{padding:16px}
    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:900px){.two{grid-template-columns:1fr}}
    input[type="text"], input[type="url"], input[type="file"]{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
    .thumb{width:140px;height:84px;object-fit:cover;border-radius:8px}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <h2 style="margin:0">Video Gallery</h2>
        <div class="actions">
          <a class="btn-lite" href="../video.php" target="_blank">Open public page</a>
          <a class="btn-lite" href="page-photo">Back to Gallery hub</a>
        </div>
      </div>

      <?php if($m=$flash('ok')): ?><div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if($m=$flash('err')): ?><div style="background:#fff6f6;border:1px solid #f2c1c1;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#9b2d2d"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-hd">Add New Video</div>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="grid two">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="add">
            <div>
              <label style="font-weight:700">Title</label>
              <input type="text" name="title" placeholder="Optional">
            </div>
            <div>
              <label style="font-weight:700">Video URL (YouTube/Vimeo)</label>
              <input type="url" name="video_url" placeholder="https://youtu.be/..." required>
            </div>
            <div>
              <label style="font-weight:700">Custom Thumbnail (optional)</label>
              <input type="file" name="thumb" accept="image/*">
              <div class="muted" style="color:#777;font-size:12px;margin-top:6px">If omitted and URL is YouTube, a default YouTube thumbnail is used.</div>
            </div>
            <div style="align-self:end">
              <button class="btn">Add Video</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-hd">All Videos</div>
        <div class="card-bd">
          <table class="table">
            <thead><tr><th>#</th><th>Thumb</th><th>Title</th><th>URL</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if($list->num_rows): while($r=$list->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td>
                  <?php if($r['thumb_path']): ?>
                    <img src="../<?= htmlspecialchars($r['thumb_path']) ?>" class="thumb" alt="">
                  <?php else: ?>
                    <div class="thumb" style="display:flex;align-items:center;justify-content:center;background:#eee;color:#666">No thumb</div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['title'] ?? '') ?></td>
                <td style="max-width:340px;word-break:break-all"><a href="<?= htmlspecialchars($r['video_url']) ?>" target="_blank"><?= htmlspecialchars($r['video_url']) ?></a></td>
                <td><?= $r['is_active'] ? 'Active' : 'Hidden' ?></td>
                <td>
                  <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;align-items:center">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="text" name="title" value="<?= htmlspecialchars($r['title']) ?>" placeholder="Title">
                    <input type="url" name="video_url" value="<?= htmlspecialchars($r['video_url']) ?>" placeholder="URL">
                    <input type="file" name="thumb" accept="image/*" style="padding:6px">
                    <label style="display:flex;gap:6px;align-items:center;margin:0;font-weight:600">
                      <input type="checkbox" name="is_active" value="1" <?= $r['is_active']?'checked':'' ?>> Active
                    </label>
                    <button class="btn-lite">Save</button>
                  </form>
                  <a class="btn-lite" style="color:#b00020;border-color:#e4b6b6;margin-top:6px;display:inline-block"
                     onclick="return confirm('Delete this video?')"
                     href="page-gallery-video.php?delete=<?= (int)$r['id'] ?>&csrf=<?= csrf() ?>">Delete</a>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="6" style="color:#777">No videos yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
