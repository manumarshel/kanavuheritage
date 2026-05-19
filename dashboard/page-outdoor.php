<?php
// /kanav/dashboard/page-outdoor

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- FILES DIR ---------- */
$UPLOAD_DIR = __DIR__ . '/../media/outdoor';
if (!is_dir($UPLOAD_DIR)) { @mkdir($UPLOAD_DIR, 0775, true); }

/* ---------- HELPERS ---------- */
function upsert_single_value($conn, $key, $value) {
  $stmt = $conn->prepare(
    "INSERT INTO outdoor_settings (`key`,`value`) VALUES (?,?)
     ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
  );
  $stmt->bind_param('ss', $key, $value);
  $stmt->execute(); $stmt->close();
}
function get_setting($conn, $key, $default='') {
  $stmt = $conn->prepare("SELECT `value` FROM outdoor_settings WHERE `key`=?");
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
  if ($path && strpos($path, 'media/outdoor/') === 0) {
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
        upsert_single_value($conn,'hero_bg','media/outdoor/'.$fn);
      } else {
        $typed = trim($_POST['hero_bg_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn,'hero_bg',$typed);
      }
    }
    $flash = 'Hero banner saved.';
  }

  // Generic saver for outdoor sections s1..s6 (title, p1, p2, img)
  if (preg_match('/^save_s([1-6])$/', $action, $m)) {
    $sec = $m[1];

    foreach (['title','p1','p2'] as $t) {
      $k = "s{$sec}_{$t}";
      if (isset($_POST[$k])) upsert_single_value($conn, $k, trim($_POST[$k]));
    }

    $imgKey = "s{$sec}_img";
    $current = get_setting($conn, $imgKey, '');
    if (!empty($_POST[$imgKey.'_clear'])) {
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn, $imgKey, '');
    } else {
      $fn = save_upload($imgKey, $UPLOAD_DIR);
      if ($fn) {
        unlink_if_local($current, $UPLOAD_DIR);
        upsert_single_value($conn, $imgKey, 'media/outdoor/'.$fn);
      } else {
        $typed = trim($_POST[$imgKey.'_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn, $imgKey, $typed);
      }
    }

    $flash = "Section {$sec} saved.";
  }

  header("Location: page-outdoor?ok=".urlencode($flash));
  exit;
}

/* ---------- LOAD VALUES ---------- */
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');

function g($k,$d=''){ global $conn; return get_setting($conn,$k,$d); }

/* Defaults mirror your static content */
$s1_title = g('s1_title','THULASI THARA & KALVILAKKU');
$s1_p1    = g('s1_p1','');
$s1_p2    = g('s1_p2','');
$s1_img   = g('s1_img','img/slider/s13.jpg'); // image right in your layout

$s2_title = g('s2_title',"KANAVU'S SIGNATURE PHOTO SPOT");
$s2_p1    = g('s2_p1','');
$s2_p2    = g('s2_p2','');
$s2_img   = g('s2_img','img/slider/s12.jpg');

$s3_title = g('s3_title','GAZEBO');
$s3_p1    = g('s3_p1','');
$s3_p2    = g('s3_p2','');
$s3_img   = g('s3_img','img/slider/s9.jpg');

$s4_title = g('s4_title','HAMMOCK');
$s4_p1    = g('s4_p1','');
$s4_p2    = g('s4_p2','');
$s4_img   = g('s4_img','img/slider/s10.jpg');

$s5_title = g('s5_title','ZEN GARDEN');
$s5_p1    = g('s5_p1','');
$s5_p2    = g('s5_p2','');
$s5_img   = g('s5_img','img/slider/s11.jpg');

$s6_title = g('s6_title','LAWN');
$s6_p1    = g('s6_p1','');
$s6_p2    = g('s6_p2','');
$s6_img   = g('s6_img','img/gallery/10.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stay · Outdoor Spaces</title>
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
        <div class="page-title"><h2>Outdoor Spaces</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../outdoor" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- HERO -->
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
                <input type="text" name="hero_bg_text" placeholder="e.g. img/gallery/01.jpg or media/outdoor/hero.jpg">
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
        <?php
        // Render 6 section editors with a small helper:
        function section_editor($n, $title, $p1, $p2, $img){
          $n = (int)$n;
          ob_start(); ?>
          <details class="card" <?= $n===1 ? 'open' : '' ?>>
            <summary><span>Section <?= $n ?></span><span class="caret">▾</span></summary>
            <div class="card-bd">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_s<?= $n ?>">
                <div class="grid two">
                  <div>
                    <div class="field"><label>Title</label><input type="text" name="s<?= $n ?>_title" value="<?= htmlspecialchars($title) ?>"></div>
                    <div class="field"><label>Paragraph 1</label><textarea name="s<?= $n ?>_p1" rows="4"><?= htmlspecialchars($p1) ?></textarea></div>
                    <div class="field"><label>Paragraph 2</label><textarea name="s<?= $n ?>_p2" rows="4"><?= htmlspecialchars($p2) ?></textarea></div>
                  </div>
                  <div>
                    <div class="field"><label>Upload image</label><input type="file" name="s<?= $n ?>_img" accept="image/*"></div>
                    <div class="field"><label>Or image path/URL</label><input type="text" name="s<?= $n ?>_img_text" placeholder="e.g. media/outdoor/s<?= $n ?>.jpg"></div>
                    <?php if($img): ?>
                      <div class="row-actions">
                        <div class="muted">Current:
                          <a href="../<?= htmlspecialchars($img) ?>" target="_blank"><?= htmlspecialchars($img) ?></a>
                        </div>
                        <label><input type="checkbox" name="s<?= $n ?>_img_clear" value="1"> Delete current image</label>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <button class="btn">Save Section <?= $n ?></button>
              </form>
            </div>
          </details>
          <?php
          return ob_get_clean();
        }

        echo section_editor(1, $s1_title, $s1_p1, $s1_p2, $s1_img);
        echo section_editor(2, $s2_title, $s2_p1, $s2_p2, $s2_img);
        echo section_editor(3, $s3_title, $s3_p1, $s3_p2, $s3_img);
        echo section_editor(4, $s4_title, $s4_p1, $s4_p2, $s4_img);
        echo section_editor(5, $s5_title, $s5_p1, $s5_p2, $s5_img);
        echo section_editor(6, $s6_title, $s6_p1, $s6_p2, $s6_img);
        ?>
      </div><!-- /.cards -->

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
