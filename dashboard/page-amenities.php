<?php
// /kanav/dashboard/page-amenities
// Simple 2-column dashboard editor for Amenities page (robust DB include)

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

/* ---------- Robust DB include (tries multiple locations) ---------- */
$DB_PATHS = [
  __DIR__ . '/../includes/connect.php',     // C:\xampp\htdocs\kanav\includes\connect.php  (correct for most installs)
  __DIR__ . '/includes/connect.php',        // C:\xampp\htdocs\kanav\dashboard\includes\connect.php (if you have a copy here)
  dirname(__DIR__) . '/includes/connect.php'// another way of the first path
];
$found = false;
foreach ($DB_PATHS as $p) {
  if (is_file($p)) { require_once $p; $found = true; break; }
}
if (!$found) {
  die("DB connect file not found.\nTried:\n- " . implode("\n- ", $DB_PATHS));
}
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("DB connection \$conn is missing or invalid.");
}
$conn->set_charset('utf8mb4');

date_default_timezone_set('Asia/Kolkata');

/* ---------- Ensure table exists ---------- */
$conn->query("
  CREATE TABLE IF NOT EXISTS amen_settings (
    `key`   VARCHAR(120) PRIMARY KEY,
    `value` LONGTEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ---------- Upload dir ---------- */
$UPLOAD_DIR = __DIR__ . '/../media/amenities';
if (!is_dir($UPLOAD_DIR)) {
  @mkdir($UPLOAD_DIR, 0775, true);
}

/* ---------- Helpers ---------- */
function upsert(mysqli $conn, string $key, string $value): void {
  $stmt = $conn->prepare("INSERT INTO amen_settings (`key`,`value`) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss',$key,$value);
  $stmt->execute();
  $stmt->close();
}
function getv(mysqli $conn, string $key, string $def=''): string {
  $stmt = $conn->prepare("SELECT `value` FROM amen_settings WHERE `key`=?");
  $stmt->bind_param('s',$key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $def;
}
function safe_name(string $n): string { return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload(string $field, string $UPLOAD_DIR): ?string {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $fn = time().'_'.safe_name($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
  return $fn;
}
function unlink_if_local(?string $path, string $UPLOAD_DIR, string $prefix='media/amenities/'): void {
  if ($path && strpos($path, $prefix) === 0) {
    $full = dirname($UPLOAD_DIR) . '/' . basename($path); // resolve to ../media/amenities/<file>
    if (is_file($full)) @unlink($full);
  }
}

/* ---------- Handle POST ---------- */
$flash = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';

  // HERO
  if ($action==='save_hero') {
    $cur = getv($conn,'hero_bg','');
    if (!empty($_POST['hero_clear'])) {
      unlink_if_local($cur,$UPLOAD_DIR);
      upsert($conn,'hero_bg','');
    } else {
      $fn = save_upload('hero_bg',$UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($cur,$UPLOAD_DIR);
        upsert($conn,'hero_bg','media/amenities/'.$fn);
      } else {
        $t = trim($_POST['hero_bg_text'] ?? '');
        if ($t!=='') upsert($conn,'hero_bg',$t);
      }
    }
    $flash = 'Hero banner saved.';
  }

  // TOP IMAGE (am.png)
  if ($action==='save_top_img') {
    $cur = getv($conn,'top_img','');
    if (!empty($_POST['top_img_clear'])) {
      unlink_if_local($cur,$UPLOAD_DIR);
      upsert($conn,'top_img','');
    } else {
      $fn = save_upload('top_img',$UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($cur,$UPLOAD_DIR);
        upsert($conn,'top_img','media/amenities/'.$fn);
      } else {
        $t = trim($_POST['top_img_text'] ?? '');
        if ($t!=='') upsert($conn,'top_img',$t);
      }
    }
    $flash = 'Top image saved.';
  }

  // Sections (1,2)
  if (in_array($action,['save_s1','save_s2'],true)) {
    $sec = substr($action,-1); // "1" or "2"

    // text fields
    foreach (['title','p1','p2'] as $k) {
      $postK = "s{$sec}_{$k}";
      if (isset($_POST[$postK])) upsert($conn,$postK, trim($_POST[$postK]));
    }

    // image
    $imgK = "s{$sec}_img";
    $cur = getv($conn,$imgK,'');
    if (!empty($_POST[$imgK.'_clear'])) {
      unlink_if_local($cur,$UPLOAD_DIR);
      upsert($conn,$imgK,'');
    } else {
      $fn = save_upload($imgK,$UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($cur,$UPLOAD_DIR);
        upsert($conn,$imgK,'media/amenities/'.$fn);
      } else {
        $t = trim($_POST[$imgK.'_text'] ?? '');
        if ($t!=='') upsert($conn,$imgK,$t);
      }
    }

    $flash = "Section {$sec} saved.";
  }

  header("Location: page-amenities?ok=".urlencode($flash));
  exit;
}

/* ---------- Load values (with sensible defaults) ---------- */
$hero_bg = getv($conn,'hero_bg','img/gallery/01.jpg');
$top_img = getv($conn,'top_img','img/slider/am.png');

// S1 — Outdoor Games
$s1_title = getv($conn,'s1_title','OUTDOOR GAMES');
$s1_p1    = getv($conn,'s1_p1','Whether it\'s a spirited match or a laid-back game, our outdoor activity area offers the perfect space for guests to unwind and create cherished memories.');
$s1_p2    = getv($conn,'s1_p2','Indulge in friendly games of carom board, cards, shuttle, archery and darts amidst the lush surroundings, ensuring a delightful and entertaining stay.');
$s1_img   = getv($conn,'s1_img','img/slider/g1.jpg');

// S2 — Grill & Gather
$s2_title = getv($conn,'s2_title','GRILL & GATHER');
$s2_p1    = getv($conn,'s2_p1','Surrounded by the rustic beauty of heritage homestay, the courtyard BBQ becomes a focal point for evenings filled with laughter, good company, and the irresistible allure of perfectly grilled delights.');
$s2_p2    = getv($conn,'s2_p2','Nestled along the edges of this charming outdoor space, the aroma of grilling delicacies mingles with the gentle breeze, creating an inviting atmosphere for memorable gatherings.');
$s2_img   = getv($conn,'s2_img','img/slider/s14.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stay · Amenities</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
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
    @media(max-width:980px){.two{grid-template-columns:1fr}}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field input[type="text"], .field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
    .row-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .notice{background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <h2>Amenities</h2>
        <div class="actions">
          <a class="btn-lite" href="../amenities" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div class="notice"><?= htmlspecialchars($_GET['ok']) ?></div>
      <?php endif; ?>

      <!-- 2-column layout -->
      <div class="grid two">
        <!-- HERO -->
        <details class="card" open>
          <summary><span>Hero banner</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_hero">
              <div class="grid two">
                <div class="field">
                  <label>Upload hero image</label>
                  <input type="file" name="hero_bg" accept="image/*">
                  <div class="muted">Wide image (e.g. 1920×1080). Used as the page banner.</div>
                </div>
                <div class="field">
                  <label>Or paste image path/URL</label>
                  <input type="text" name="hero_bg_text" placeholder="img/gallery/01.jpg or media/amenities/hero.jpg">
                </div>
              </div>
              <?php if($hero_bg): ?>
                <div class="row-actions">
                  <div class="muted">Current: <a href="../<?= htmlspecialchars($hero_bg) ?>" target="_blank"><?= htmlspecialchars($hero_bg) ?></a></div>
                  <label><input type="checkbox" name="hero_clear" value="1"> Delete current</label>
                </div>
              <?php endif; ?>
              <div class="actions" style="margin-top:8px"><button class="btn">Save Hero</button></div>
            </form>
          </div>
        </details>

        <!-- TOP IMAGE -->
        <details class="card" open>
          <summary><span>Top image (under title)</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_top_img">
              <div class="grid two">
                <div class="field">
                  <label>Upload top image</label>
                  <input type="file" name="top_img" accept="image/*">
                </div>
                <div class="field">
                  <label>Or paste image path/URL</label>
                  <input type="text" name="top_img_text" placeholder="img/slider/am.png or media/amenities/am.png">
                </div>
              </div>
              <?php if($top_img): ?>
                <div class="row-actions">
                  <div class="muted">Current: <a href="../<?= htmlspecialchars($top_img) ?>" target="_blank"><?= htmlspecialchars($top_img) ?></a></div>
                  <label><input type="checkbox" name="top_img_clear" value="1"> Delete current</label>
                </div>
              <?php endif; ?>
              <div class="actions" style="margin-top:8px"><button class="btn">Save Top Image</button></div>
            </form>
          </div>
        </details>

        <!-- SECTION 1 -->
        <details class="card" open>
          <summary><span>Section 1 — Outdoor Games</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s1">
              <div class="grid two">
                <div>
                  <div class="field"><label>Title</label><input type="text" name="s1_title" value="<?= htmlspecialchars($s1_title) ?>"></div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s1_p1" rows="4"><?= htmlspecialchars($s1_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s1_p2" rows="4"><?= htmlspecialchars($s1_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s1_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s1_img_text" placeholder="img/slider/g1.jpg or media/amenities/s1.jpg"></div>
                  <?php if($s1_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s1_img) ?>" target="_blank"><?= htmlspecialchars($s1_img) ?></a></div>
                      <label><input type="checkbox" name="s1_img_clear" value="1"> Delete current</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 1</button>
            </form>
          </div>
        </details>

        <!-- SECTION 2 -->
        <details class="card" open>
          <summary><span>Section 2 — Grill & Gather</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s2">
              <div class="grid two">
                <div>
                  <div class="field"><label>Title</label><input type="text" name="s2_title" value="<?= htmlspecialchars($s2_title) ?>"></div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s2_p1" rows="4"><?= htmlspecialchars($s2_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s2_p2" rows="4"><?= htmlspecialchars($s2_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s2_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s2_img_text" placeholder="img/slider/s14.jpg or media/amenities/s2.jpg"></div>
                  <?php if($s2_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s2_img) ?>" target="_blank"><?= htmlspecialchars($s2_img) ?></a></div>
                      <label><input type="checkbox" name="s2_img_clear" value="1"> Delete current</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 2</button>
            </form>
          </div>
        </details>

      </div><!-- /.grid.two -->
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
