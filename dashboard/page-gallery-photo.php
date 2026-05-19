<?php
// /dashboard/page-gallery-photo
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php'; // keep your existing connector
date_default_timezone_set('Asia/Kolkata');

/* --------- FILES DIR --------- */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/gallery');
if (!$UPLOAD_DIR) {
  @mkdir(__DIR__ . '/../media/gallery', 0775, true);
  $UPLOAD_DIR = realpath(__DIR__ . '/../media/gallery');
}

/* --------- CSRF (light) --------- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf() { return $_SESSION['csrf']; }
function csrf_ok($t) { return hash_equals($_SESSION['csrf'] ?? '', $t ?? ''); }

/* --------- HELPERS --------- */
function safe_name($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field, $destAbsDir){
  if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $mime = mime_content_type($_FILES[$field]['tmp_name']);
  $ok = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if (!isset($ok[$mime])) return null;
  $ext = $ok[$mime];
  $fn  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!is_dir($destAbsDir)) @mkdir($destAbsDir, 0775, true);
  $abs = $destAbsDir . DIRECTORY_SEPARATOR . $fn;
  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $abs)) return null;
  return 'media/gallery/' . $fn; // web-relative path
}
function delete_if_local($rel){
  $abs = realpath(__DIR__ . '/../' . $rel);
  if ($abs && is_file($abs)) @unlink($abs);
}

/* --------- FLASH --------- */
$flash = function($k,$v=null){
  if ($v !== null) { $_SESSION['flash'][$k] = $v; return; }
  $m = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $m;
};

/* --------- ACTIONS --------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';
  if (!csrf_ok($_POST['csrf'] ?? '')) { $flash('err','Invalid token.'); header('Location: page-gallery-photo'); exit; }

  // Upload new
  if ($action==='add') {
    $alt  = trim($_POST['image_alt'] ?? '');
    $path = save_upload('image_file', $UPLOAD_DIR);
    if ($path) {
      $stmt = $conn->prepare("INSERT INTO gallery (image_path, image_alt) VALUES (?,?)");
      $stmt->bind_param('ss', $path, $alt); $stmt->execute();
      $flash('ok','Image uploaded.');
    } else {
      $flash('err','Please choose a valid image (jpg/png/webp/gif).');
    }
    header('Location: page-gallery-photo'); exit;
  }

  // Import existing /img/gallery/*
  if ($action==='import_existing') {
    $baseDir   = realpath(__DIR__ . '/../img/gallery');
    $webPrefix = 'img/gallery';
    $added = 0;
    if ($baseDir) {
      foreach (glob($baseDir.'/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $abs) {
        $rel = $webPrefix . '/' . basename($abs);
        $q = $conn->prepare("SELECT id FROM gallery WHERE image_path=? LIMIT 1");
        $q->bind_param('s',$rel); $q->execute();
        if (!$q->get_result()->fetch_assoc()) {
          $alt = pathinfo($rel, PATHINFO_FILENAME);
          $ins = $conn->prepare("INSERT INTO gallery (image_path,image_alt) VALUES (?,?)");
          $ins->bind_param('ss',$rel,$alt); $ins->execute(); $added++;
        }
      }
    }
    $flash('ok', "Imported {$added} image(s) from /img/gallery.");
    header('Location: page-gallery-photo'); exit;
  }
}

// Delete
if (isset($_GET['delete'], $_GET['csrf'])) {
  if (!csrf_ok($_GET['csrf'])) { $flash('err','Invalid token.'); header('Location: page-gallery-photo'); exit; }
  $id = (int)$_GET['delete'];
  $q  = $conn->prepare("SELECT image_path FROM gallery WHERE id=? LIMIT 1");
  $q->bind_param('i',$id); $q->execute();
  if ($row = $q->get_result()->fetch_assoc()) {
    // only delete file if it was uploaded into /media/gallery/
    if (strpos($row['image_path'],'media/gallery/') === 0) delete_if_local($row['image_path']);
    $d = $conn->prepare("DELETE FROM gallery WHERE id=? LIMIT 1");
    $d->bind_param('i',$id); $d->execute();
    $flash('ok','Deleted.');
  }
  header('Location: page-gallery-photo'); exit;
}

/* --------- FETCH --------- */
$list = $conn->query("SELECT * FROM gallery ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gallery · Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}
    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden;margin-bottom:12px}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s ease}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}
    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:920px){.two{grid-template-columns:1fr}}
    .field{display:grid;gap:6px}
    .field input[type="text"], .field input[type="file"]{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
    .g-wrap{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    @media(max-width:1100px){.g-wrap{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:760px){.g-wrap{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:520px){.g-wrap{grid-template-columns:1fr}}
    .g-card{background:#fff;border:1px solid #e7e0d7;border-radius:12px;padding:10px}
    .g-img{width:100%;height:220px;object-fit:cover;border-radius:8px}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Gallery (Photos)</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../photo" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if ($m=$flash('ok')): ?><div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if ($m=$flash('err')): ?><div style="background:#fff6f6;border:1px solid #f2c1c1;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#9b2d2d"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <!-- Upload -->
      <details class="card" open>
        <summary><span>Upload New Photo</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="add">
            <div class="grid two">
              <div class="field">
                <label>Image (jpg/png/webp/gif)</label>
                <input type="file" name="image_file" accept="image/*" required>
                <div class="muted">Stored in <code>/media/gallery/</code></div>
              </div>
              <div class="field">
                <label>Alt (optional)</label>
                <input type="text" name="image_alt" placeholder="Short description for SEO">
              </div>
            </div>
            <div class="actions" style="margin-top:8px"><button class="btn">Upload</button></div>
          </form>
        </div>
      </details>

      <!-- Import from /img/gallery -->
      <details class="card">
        <summary><span>Import images from <code>/img/gallery/</code> (one-time)</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="import_existing">
            <p class="muted">This scans <code>/img/gallery</code> and inserts any jpg/png/webp/gif not already in the DB. Safe to run again—no duplicates.</p>
            <button class="btn">Import Now</button>
          </form>
        </div>
      </details>

      <!-- List -->
      <details class="card" open>
        <summary><span>Current Gallery</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <div class="g-wrap">
            <?php if ($list->num_rows): while($r=$list->fetch_assoc()): ?>
              <div class="g-card">
                <img src="../<?= htmlspecialchars($r['image_path']) ?>" class="g-img" alt="<?= htmlspecialchars($r['image_alt'] ?? '') ?>">
                <div class="muted" style="margin-top:6px"><?= htmlspecialchars($r['image_alt'] ?? '') ?></div>
                <div class="actions" style="margin-top:8px">
                  <a class="btn-lite" href="../<?= htmlspecialchars($r['image_path']) ?>" target="_blank">Open</a>
                  <a class="btn-lite" style="color:#b00020;border-color:#e4b6b6" onclick="return confirm('Delete this image?')" 
                     href="page-gallery-photo?delete=<?= (int)$r['id'] ?>&csrf=<?= csrf() ?>">Delete</a>
                </div>
              </div>
            <?php endwhile; else: ?>
              <div class="muted">No images yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </details>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
