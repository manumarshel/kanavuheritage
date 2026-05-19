<?php
// /kanav/dashboard/page-about  (simplified, section-by-section)
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include '../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- FILES DIR ---------- */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/about');
if (!$UPLOAD_DIR) {
  @mkdir(__DIR__ . '/../media/about', 0775, true);
  $UPLOAD_DIR = realpath(__DIR__ . '/../media/about');
}

/* ---------- HELPERS ---------- */
function upsert_single_value($conn, $key, $value) {
  $stmt = $conn->prepare("INSERT INTO about_settings (`key`,`value`) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss', $key, $value);
  $stmt->execute(); $stmt->close();
}
function get_setting($conn, $key, $default='') {
  $stmt = $conn->prepare("SELECT `value` FROM about_settings WHERE `key`=?");
  $stmt->bind_param('s', $key); $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? $row['value'] : $default;
}
function safe_basename($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field, $UPLOAD_DIR){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $fn = time().'_'.safe_basename($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
  return $fn;
}
function unlink_if_local($path, $UPLOAD_DIR){
  // delete only if path starts with "media/about/"
  if ($path && strpos($path, 'media/about/') === 0) {
    $fn = basename($path);
    $full = $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn;
    if (is_file($full)) @unlink($full);
  }
}

/* ---------- (Run once) TABLES ----------
CREATE TABLE IF NOT EXISTS about_settings (`key` VARCHAR(120) PRIMARY KEY, `value` LONGTEXT);
CREATE TABLE IF NOT EXISTS about_slides (id INT AUTO_INCREMENT PRIMARY KEY, image VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0);
---------------------------------------- */

$flash = '';

/* ---------- ACTIONS (each section has its own tiny form) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';

  /* HERO + HEADLINE */
  if ($action === 'save_hero') {
    $headline = trim($_POST['headline'] ?? '');
    upsert_single_value($conn, 'about_headline', $headline);

    // upload image
    $current = get_setting($conn,'about_hero_bg','');
    if (!empty($_POST['hero_clear'])) {
      // delete old local file if ours
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn, 'about_hero_bg', '');
    } else {
      $newFn = save_upload('hero_bg', $UPLOAD_DIR);
      if ($newFn) {
        unlink_if_local($current, $UPLOAD_DIR); // replace old file
        upsert_single_value($conn, 'about_hero_bg', 'media/about/'.$newFn);
      } else {
        // user typed a path/url
        $typed = trim($_POST['hero_bg_text'] ?? '');
        if ($typed !== '') {
          upsert_single_value($conn, 'about_hero_bg', $typed);
        }
      }
    }
    $flash = 'Hero & headline saved.';
  }

  /* SECTION 1 (Left text / Right image) */
  if ($action === 'save_s1') {
    upsert_single_value($conn, 'about_s1_title', trim($_POST['s1_title'] ?? ''));
    upsert_single_value($conn, 'about_s1_p1', trim($_POST['s1_p1'] ?? ''));
    upsert_single_value($conn, 'about_s1_p2', trim($_POST['s1_p2'] ?? ''));

    $current = get_setting($conn,'about_s1_img','');
    if (!empty($_POST['s1_img_clear'])) {
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn, 'about_s1_img', '');
    } else {
      $newFn = save_upload('s1_img', $UPLOAD_DIR);
      if ($newFn) {
        unlink_if_local($current, $UPLOAD_DIR);
        upsert_single_value($conn, 'about_s1_img', 'media/about/'.$newFn);
      } else {
        $typed = trim($_POST['s1_img_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn, 'about_s1_img', $typed);
      }
    }
    $flash = 'Section 1 saved.';
  }

  /* SECTION 2 (Left image / Right text) */
  if ($action === 'save_s2') {
    upsert_single_value($conn, 'about_s2_title', trim($_POST['s2_title'] ?? ''));
    upsert_single_value($conn, 'about_s2_p1', trim($_POST['s2_p1'] ?? ''));
    upsert_single_value($conn, 'about_s2_p2', trim($_POST['s2_p2'] ?? ''));

    $current = get_setting($conn,'about_s2_img','');
    if (!empty($_POST['s2_img_clear'])) {
      unlink_if_local($current, $UPLOAD_DIR);
      upsert_single_value($conn, 'about_s2_img', '');
    } else {
      $newFn = save_upload('s2_img', $UPLOAD_DIR);
      if ($newFn) {
        unlink_if_local($current, $UPLOAD_DIR);
        upsert_single_value($conn, 'about_s2_img', 'media/about/'.$newFn);
      } else {
        $typed = trim($_POST['s2_img_text'] ?? '');
        if ($typed !== '') upsert_single_value($conn, 'about_s2_img', $typed);
      }
    }
    $flash = 'Section 2 saved.';
  }

  /* KENBURNS TEXT */
  if ($action === 'save_kb_text') {
    upsert_single_value($conn,'about_kb_sub', trim($_POST['kb_sub'] ?? ''));
    upsert_single_value($conn,'about_kb_title', trim($_POST['kb_title'] ?? ''));
    upsert_single_value($conn,'about_kb_btn_text', trim($_POST['kb_btn_text'] ?? ''));
    upsert_single_value($conn,'about_kb_btn_link', trim($_POST['kb_btn_link'] ?? ''));
    $flash = 'Kenburns text saved.';
  }

  /* KENBURNS SLIDES: add & delete */
  if ($action === 'kb_add') {
    $fn = save_upload('kb_img', $UPLOAD_DIR);
    if ($fn) {
      $imgPath = 'media/about/'.$fn;
    } else {
      $imgPath = trim($_POST['kb_img_text'] ?? '');
    }
    if ($imgPath !== '') {
      $stmt = $conn->prepare("INSERT INTO about_slides (image, sort_order) VALUES (?,0)");
      $stmt->bind_param('s', $imgPath); $stmt->execute(); $stmt->close();
      $flash = 'Kenburns slide added.';
    }
  }
  if ($action === 'kb_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $conn->query("SELECT image FROM about_slides WHERE id=".$id);
    if ($row = $res->fetch_assoc()) unlink_if_local($row['image'], $UPLOAD_DIR);
    $conn->query("DELETE FROM about_slides WHERE id=".$id);
    $flash = 'Kenburns slide deleted.';
  }

  header("Location: page-about?ok=".urlencode($flash));
  exit;
}

/* ---------- LOAD DATA FOR VIEW ---------- */
$headline    = get_setting($conn,'about_headline');
$hero_bg     = get_setting($conn,'about_hero_bg');

$s1_title    = get_setting($conn,'about_s1_title');
$s1_p1       = get_setting($conn,'about_s1_p1');
$s1_p2       = get_setting($conn,'about_s1_p2');
$s1_img      = get_setting($conn,'about_s1_img');

$s2_title    = get_setting($conn,'about_s2_title');
$s2_p1       = get_setting($conn,'about_s2_p1');
$s2_p2       = get_setting($conn,'about_s2_p2');
$s2_img      = get_setting($conn,'about_s2_img');

$kb_title    = get_setting($conn,'about_kb_title');
$kb_sub      = get_setting($conn,'about_kb_sub');
$kb_btn_text = get_setting($conn,'about_kb_btn_text');
$kb_btn_link = get_setting($conn,'about_kb_btn_link');

$kb_slides   = $conn->query("SELECT * FROM about_slides ORDER BY sort_order, id DESC");
$kb_count    = (int)$conn->query("SELECT COUNT(*) c FROM about_slides")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pages · About</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}
    .grid{display:grid;gap:14px}
    .tiles{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
    .tile{background:#fff;border:1px solid #e7e0d7;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:4px}
    .tile .k{color:#666;font-size:12px}
    .tile .v{font-size:22px;font-weight:800}
    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s ease}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field label{font-weight:700}
    .field input[type="text"], .field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .thumbs{display:flex;flex-wrap:wrap;gap:10px}
    .thumb{border:1px solid #eee;border-radius:10px;padding:6px;display:flex;align-items:center;gap:8px;background:#fafafa}
    .thumb img{height:54px;width:90px;object-fit:cover;border-radius:6px}
    .muted{color:#777;font-size:12px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:920px){.two{grid-template-columns:1fr}}
    .notice{background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:10px;color:#205d34}
    .row-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    hr.sep{margin:14px 0;border:none;border-top:1px solid #eee}
  </style>
</head>
<body>
<div class="container">
  <?php include('includes/sidebar.php'); ?>
  <div class="main">
    <?php include('includes/topbar.php'); ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>About Page</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../about" target="_blank">Open About</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div class="notice"><?= htmlspecialchars($_GET['ok']) ?></div>
      <?php endif; ?>

      <!-- quick tiles -->
      <div class="grid tiles" style="margin-bottom:14px">
        <div class="tile">
          <div class="k">Kenburns slides</div>
          <div class="v"><?= $kb_count ?></div>
        </div>
      </div>

      <!-- HERO & HEADLINE -->
      <details class="card" open>
        <summary><span>Hero & Headline</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_hero">
            <div class="field">
              <label>Headline (centered). You can include &lt;span&gt; for gold words.</label>
              <textarea name="headline" rows="3"><?= htmlspecialchars($headline) ?></textarea>
            </div>
            <div class="grid two">
              <div class="field">
                <label>Upload hero background (optional)</label>
                <input type="file" name="hero_bg" accept="image/*">
                <div class="muted">Recommended wide image (e.g. 1920×1080)</div>
              </div>
              <div class="field">
                <label>Or set path/URL directly</label>
                <input type="text" name="hero_bg_text" placeholder="e.g. img/gallery/01.jpg or media/about/hero.jpg">
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

      <!-- SECTION 1 -->
      <details class="card">
        <summary><span>Section 1 (Left text / Right image)</span><span class="caret">▾</span></summary>
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
                <div class="field"><label>Upload right image</label><input type="file" name="s1_img" accept="image/*"></div>
                <div class="field"><label>Or image path/URL</label><input type="text" name="s1_img_text" placeholder="e.g. media/about/a2.jpg"></div>
                <?php if($s1_img): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s1_img) ?>" target="_blank"><?= htmlspecialchars($s1_img) ?></a></div>
                    <label><input type="checkbox" name="s1_img_clear" value="1"> Delete current image</label>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <div class="actions" style="margin-top:8px"><button class="btn">Save Section 1</button></div>
          </form>
        </div>
      </details>

      <!-- SECTION 2 -->
      <details class="card">
        <summary><span>Section 2 (Left image / Right text)</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s2">
            <div class="grid two">
              <div>
                <div class="field"><label>Upload left image</label><input type="file" name="s2_img" accept="image/*"></div>
                <div class="field"><label>Or image path/URL</label><input type="text" name="s2_img_text" placeholder="e.g. media/about/a1.jpg"></div>
                <?php if($s2_img): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s2_img) ?>" target="_blank"><?= htmlspecialchars($s2_img) ?></a></div>
                    <label><input type="checkbox" name="s2_img_clear" value="1"> Delete current image</label>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <div class="field"><label>Title</label><input type="text" name="s2_title" value="<?= htmlspecialchars($s2_title) ?>"></div>
                <div class="field"><label>Paragraph 1</label><textarea name="s2_p1" rows="4"><?= htmlspecialchars($s2_p1) ?></textarea></div>
                <div class="field"><label>Paragraph 2</label><textarea name="s2_p2" rows="4"><?= htmlspecialchars($s2_p2) ?></textarea></div>
              </div>
            </div>
            <div class="actions" style="margin-top:8px"><button class="btn">Save Section 2</button></div>
          </form>
        </div>
      </details>

      <!-- KENBURNS TEXT -->
      <details class="card">
        <summary><span>Kenburns Text</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post">
            <input type="hidden" name="action" value="save_kb_text">
            <div class="grid two">
              <div class="field"><label>Subtitle (small)</label><input type="text" name="kb_sub" value="<?= htmlspecialchars($kb_sub) ?>"></div>
              <div class="field"><label>Title (big)</label><input type="text" name="kb_title" value="<?= htmlspecialchars($kb_title) ?>"></div>
              <div class="field"><label>Button text</label><input type="text" name="kb_btn_text" value="<?= htmlspecialchars($kb_btn_text) ?>"></div>
              <div class="field"><label>Button link</label><input type="text" name="kb_btn_link" value="<?= htmlspecialchars($kb_btn_link) ?>"></div>
            </div>
            <div class="actions" style="margin-top:8px"><button class="btn">Save Kenburns Text</button></div>
          </form>
        </div>
      </details>

      <!-- KENBURNS SLIDES -->
      <details class="card">
        <summary><span>Kenburns Slides</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:10px;flex-wrap:wrap">
            <input type="hidden" name="action" value="kb_add">
            <div class="field" style="margin:0">
              <label style="display:block;margin-bottom:6px">Upload image</label>
              <input type="file" name="kb_img" accept="image/*">
            </div>
            <div class="field" style="margin:0">
              <label style="display:block;margin-bottom:6px">OR image path/URL</label>
              <input type="text" name="kb_img_text" placeholder="e.g. media/about/slide.jpg or img/slider/6.jpg">
            </div>
            <button class="btn">+ Add Slide</button>
          </form>

          <div class="thumbs">
            <?php while($s=$kb_slides->fetch_assoc()): ?>
              <div class="thumb">
                <img src="../<?= htmlspecialchars($s['image']) ?>" alt="">
                <div class="muted" style="min-width:220px"><?= htmlspecialchars($s['image']) ?></div>
                <form method="post" onsubmit="return confirm('Delete this slide?')">
                  <input type="hidden" name="action" value="kb_delete">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn-lite">Delete</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
          <div class="muted">Tip: Use wide images (1920×1080). You can upload or paste a path.</div>
        </div>
      </details>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
