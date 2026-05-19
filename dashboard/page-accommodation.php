<?php
// /kanav/dashboard/page-accommodation

// (optional, helpful in dev)
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- FILES DIR ---------- */
$UPLOAD_DIR = __DIR__ . '/../media/accommodation';
if (!is_dir($UPLOAD_DIR)) {
  @mkdir($UPLOAD_DIR, 0775, true);
}

/* ---------- HELPERS ---------- */
function upsert_single_value($conn, $key, $value) {
  $stmt = $conn->prepare(
    "INSERT INTO accom_settings (`key`,`value`) VALUES (?,?)
     ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
  );
  $stmt->bind_param('ss', $key, $value);
  $stmt->execute(); $stmt->close();
}
function get_setting($conn, $key, $default='') {
  $stmt = $conn->prepare("SELECT `value` FROM accom_settings WHERE `key`=?");
  $stmt->bind_param('s', $key); $stmt->execute();
  $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? $row['value'] : $default;
}
function safe_basename($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field, $upload_dir){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $fn = time().'_'.safe_basename($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . DIRECTORY_SEPARATOR . $fn);
  return $fn;
}
function unlink_if_local($path, $upload_dir){
  // Only delete if it’s in our managed folder (media/accommodation/…)
  if ($path && strpos($path, 'media/accommodation/') === 0) {
    $full = $upload_dir . DIRECTORY_SEPARATOR . basename($path);
    if (is_file($full)) @unlink($full);
  }
}

/* ---------- ACTIONS ---------- */
$flash = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';

  // HERO
  if ($action === 'save_hero') {
    $current = get_setting($conn,'hero_bg','');
    if (!empty($_POST['hero_clear'])) {
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn,'hero_bg','');
    } else {
      $fn = save_upload('hero_bg', $UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($current, $UPLOAD_DIR);
        upsert_single_value($conn,'hero_bg','media/accommodation/'.$fn);
      } else {
        $typed = trim($_POST['hero_bg_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn,'hero_bg',$typed);
      }
    }
    $flash = 'Hero banner saved.';
  }

  // Generic saver for any section s1..s8
  // Text keys allowed: title (only for 1,3,5,8), p1, p2
  if (preg_match('/^save_s([1-8])$/', $action, $m)) {
    $sec = $m[1]; // "1".."8"

    // Texts
    $allow_title = in_array((int)$sec, [1,3,5,8], true);
    foreach (['p1','p2'] as $pkey) {
      $k = "s{$sec}_{$pkey}";
      if (isset($_POST[$k])) upsert_single_value($conn, $k, trim($_POST[$k]));
    }
    if ($allow_title && isset($_POST["s{$sec}_title"])) {
      upsert_single_value($conn, "s{$sec}_title", trim($_POST["s{$sec}_title"]));
    }

    // Image
    $imgKey = "s{$sec}_img";
    $current = get_setting($conn, $imgKey, '');
    if (!empty($_POST[$imgKey.'_clear'])) {
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn, $imgKey, '');
    } else {
      $fn = save_upload($imgKey, $UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($current, $UPLOAD_DIR);
        upsert_single_value($conn, $imgKey, 'media/accommodation/'.$fn);
      } else {
        $typed = trim($_POST[$imgKey.'_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn, $imgKey, $typed);
      }
    }

    $flash = "Section {$sec} saved.";
  }

  header("Location: page-accommodation?ok=".urlencode($flash));
  exit;
}

/* ---------- LOAD VALUES ---------- */
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');

function g($k,$d=''){ global $conn; return get_setting($conn,$k,$d); }

// Defaults similar to your public page
$s1_title = g('s1_title','ENCHANTING VINTAGE VERANDAH');
$s1_p1    = g('s1_p1','');
$s1_p2    = g('s1_p2','');
$s1_img   = g('s1_img','img/slider/s1.jpg');

$s2_p1    = g('s2_p1','');
$s2_p2    = g('s2_p2','');
$s2_img   = g('s2_img','img/slider/8.jpg');

$s3_title = g('s3_title','LUXE BED ROOMS');
$s3_p1    = g('s3_p1','');
$s3_p2    = g('s3_p2','');
$s3_img   = g('s3_img','img/slider/s3.jpg');

$s4_p1    = g('s4_p1','');
$s4_p2    = g('s4_p2','');
$s4_img   = g('s4_img','img/slider/s4.jpg');

$s5_title = g('s5_title','GRACEFUL LIVING SPACE');
$s5_p1    = g('s5_p1','');
$s5_p2    = g('s5_p2','');
$s5_img   = g('s5_img','img/slider/s5.jpg');

$s6_p1    = g('s6_p1','');
$s6_p2    = g('s6_p2','');
$s6_img   = g('s6_img','img/slider/s6.jpg');

$s7_p1    = g('s7_p1','');
$s7_p2    = g('s7_p2','');
$s7_img   = g('s7_img','img/slider/s7.jpg');

$s8_title = g('s8_title','SOPHISTICATED CULINARY RETREAT');
$s8_p1    = g('s8_p1','');
$s8_p2    = g('s8_p2','');
$s8_img   = g('s8_img','img/slider/s8.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stay · Accommodation</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}

    .cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    @media (max-width: 980px){ .cards{grid-template-columns:1fr} }

    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s ease}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}

    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:920px){.two{grid-template-columns:1fr}}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field input[type="text"], .field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
    .row-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  </style>
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Accommodation</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../accommodation" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- HERO (full width) -->
      <details class="card" open>
        <summary><span>Hero banner (top image)</span><span class="caret">▾</span></summary>
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
                <label>Or paste an image path/URL</label>
                <input type="text" name="hero_bg_text" placeholder="e.g. img/gallery/01.jpg or media/accommodation/hero.jpg">
              </div>
            </div>
            <?php if($hero_bg): ?>
              <div class="row-actions">
                <div class="muted">Current: <a href="../<?= htmlspecialchars($hero_bg) ?>" target="_blank"><?= htmlspecialchars($hero_bg) ?></a></div>
                <label><input type="checkbox" name="hero_clear" value="1"> Delete current image</label>
              </div>
            <?php endif; ?>
            <div class="actions" style="margin-top:8px"><button class="btn">Save Hero</button></div>
          </form>
        </div>
      </details>

      <!-- 2-column cards -->
      <div class="cards">

        <!-- S1 -->
        <details class="card" open>
          <summary><span>Section 1 — Image Left / Text Right</span><span class="caret">▾</span></summary>
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
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s1_img_text" placeholder="e.g. media/accommodation/s1.jpg or img/slider/s1.jpg"></div>
                  <?php if($s1_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s1_img) ?>" target="_blank"><?= htmlspecialchars($s1_img) ?></a></div>
                      <label><input type="checkbox" name="s1_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 1</button>
            </form>
          </div>
        </details>

        <!-- S2 -->
        <details class="card">
          <summary><span>Section 2 — Text Left / Image Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s2">
              <div class="grid two">
                <div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s2_p1" rows="4"><?= htmlspecialchars($s2_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s2_p2" rows="4"><?= htmlspecialchars($s2_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s2_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s2_img_text" placeholder="e.g. media/accommodation/s2.jpg"></div>
                  <?php if($s2_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s2_img) ?>" target="_blank"><?= htmlspecialchars($s2_img) ?></a></div>
                      <label><input type="checkbox" name="s2_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 2</button>
            </form>
          </div>
        </details>

        <!-- S3 -->
        <details class="card">
          <summary><span>Section 3 — Image Left / Text Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s3">
              <div class="grid two">
                <div>
                  <div class="field"><label>Title</label><input type="text" name="s3_title" value="<?= htmlspecialchars($s3_title) ?>"></div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s3_p1" rows="4"><?= htmlspecialchars($s3_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s3_p2" rows="4"><?= htmlspecialchars($s3_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s3_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s3_img_text" placeholder="e.g. media/accommodation/s3.jpg"></div>
                  <?php if($s3_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s3_img) ?>" target="_blank"><?= htmlspecialchars($s3_img) ?></a></div>
                      <label><input type="checkbox" name="s3_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 3</button>
            </form>
          </div>
        </details>

        <!-- S4 -->
        <details class="card">
          <summary><span>Section 4 — Text Left / Image Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s4">
              <div class="grid two">
                <div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s4_p1" rows="4"><?= htmlspecialchars($s4_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s4_p2" rows="4"><?= htmlspecialchars($s4_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s4_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s4_img_text" placeholder="e.g. media/accommodation/s4.jpg"></div>
                  <?php if($s4_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s4_img) ?>" target="_blank"><?= htmlspecialchars($s4_img) ?></a></div>
                      <label><input type="checkbox" name="s4_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 4</button>
            </form>
          </div>
        </details>

        <!-- S5 -->
        <details class="card">
          <summary><span>Section 5 — Image Left / Text Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s5">
              <div class="grid two">
                <div>
                  <div class="field"><label>Title</label><input type="text" name="s5_title" value="<?= htmlspecialchars($s5_title) ?>"></div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s5_p1" rows="4"><?= htmlspecialchars($s5_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s5_p2" rows="4"><?= htmlspecialchars($s5_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s5_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s5_img_text" placeholder="e.g. media/accommodation/s5.jpg"></div>
                  <?php if($s5_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s5_img) ?>" target="_blank"><?= htmlspecialchars($s5_img) ?></a></div>
                      <label><input type="checkbox" name="s5_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 5</button>
            </form>
          </div>
        </details>

        <!-- S6 -->
        <details class="card">
          <summary><span>Section 6 — Text Left / Image Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s6">
              <div class="grid two">
                <div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s6_p1" rows="4"><?= htmlspecialchars($s6_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s6_p2" rows="4"><?= htmlspecialchars($s6_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s6_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s6_img_text" placeholder="e.g. media/accommodation/s6.jpg"></div>
                  <?php if($s6_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s6_img) ?>" target="_blank"><?= htmlspecialchars($s6_img) ?></a></div>
                      <label><input type="checkbox" name="s6_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 6</button>
            </form>
          </div>
        </details>

        <!-- S7 -->
        <details class="card">
          <summary><span>Section 7 — Image Left / Text Right</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s7">
              <div class="grid two">
                <div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s7_p1" rows="5"><?= htmlspecialchars($s7_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s7_p2" rows="5"><?= htmlspecialchars($s7_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s7_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s7_img_text" placeholder="e.g. media/accommodation/s7.jpg"></div>
                  <?php if($s7_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s7_img) ?>" target="_blank"><?= htmlspecialchars($s7_img) ?></a></div>
                      <label><input type="checkbox" name="s7_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 7</button>
            </form>
          </div>
        </details>

        <!-- S8 -->
        <details class="card">
          <summary><span>Section 8 — Text Left / Image Right (Kitchen)</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_s8">
              <div class="grid two">
                <div>
                  <div class="field"><label>Title</label><input type="text" name="s8_title" value="<?= htmlspecialchars($s8_title) ?>"></div>
                  <div class="field"><label>Paragraph 1</label><textarea name="s8_p1" rows="4"><?= htmlspecialchars($s8_p1) ?></textarea></div>
                  <div class="field"><label>Paragraph 2</label><textarea name="s8_p2" rows="4"><?= htmlspecialchars($s8_p2) ?></textarea></div>
                </div>
                <div>
                  <div class="field"><label>Upload image</label><input type="file" name="s8_img" accept="image/*"></div>
                  <div class="field"><label>Or image path/URL</label><input type="text" name="s8_img_text" placeholder="e.g. media/accommodation/s8.jpg"></div>
                  <?php if($s8_img): ?>
                    <div class="row-actions">
                      <div class="muted">Current: <a href="../<?= htmlspecialchars($s8_img) ?>" target="_blank"><?= htmlspecialchars($s8_img) ?></a></div>
                      <label><input type="checkbox" name="s8_img_clear" value="1"> Delete current image</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn">Save Section 8</button>
            </form>
          </div>
        </details>

      </div><!-- /.cards -->

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
