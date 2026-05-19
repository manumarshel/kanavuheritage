<?php
// /kanav/dashboard/page-home.php
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }
include '../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- FILES DIR ---------- */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/home');
if (!$UPLOAD_DIR) { @mkdir(__DIR__ . '/../media/home', 0775, true); $UPLOAD_DIR = realpath(__DIR__ . '/../media/home'); }

/* ---------- HELPERS ---------- */
function upsert_single_value($conn, $key, $value) {
  $stmt = $conn->prepare("INSERT INTO home_settings (`key`,`value`) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss', $key, $value);
  $stmt->execute(); $stmt->close();
}
function get_setting($conn, $key, $default='') {
  $stmt = $conn->prepare("SELECT `value` FROM home_settings WHERE `key`=?");
  $stmt->bind_param('s', $key); $stmt->execute();
  $res=$stmt->get_result(); $row=$res->fetch_assoc(); $stmt->close();
  return $row ? $row['value'] : $default;
}
function safe_basename($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function move_and_replace($field, $oldPathOrNull, $UPLOAD_DIR){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK) return $oldPathOrNull;
  $fn = time().'_'.safe_basename($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
  if ($oldPathOrNull && is_file($UPLOAD_DIR . DIRECTORY_SEPARATOR . $oldPathOrNull)) {
    @unlink($UPLOAD_DIR . DIRECTORY_SEPARATOR . $oldPathOrNull);
  }
  return $fn;
}
function unlink_file($UPLOAD_DIR, $name){
  if ($name && is_file($UPLOAD_DIR . DIRECTORY_SEPARATOR . $name)) @unlink($UPLOAD_DIR . DIRECTORY_SEPARATOR . $name);
}

/* ---------- TABLES (run once in DB) ----------
CREATE TABLE IF NOT EXISTS home_settings (`key` VARCHAR(100) PRIMARY KEY, `value` LONGTEXT);
CREATE TABLE IF NOT EXISTS home_slider (id INT AUTO_INCREMENT PRIMARY KEY, image VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0);
CREATE TABLE IF NOT EXISTS home_gallery (id INT AUTO_INCREMENT PRIMARY KEY, image VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0);
CREATE TABLE IF NOT EXISTS home_attractions (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, image VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0);
CREATE TABLE IF NOT EXISTS home_testimonials (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, quote TEXT NOT NULL, photo VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0);
------------------------------------------------ */

/* ---------- ACTIONS (UNCHANGED) ---------- */
$flash = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (($_POST['action'] ?? '') === 'save_welcome') {
    $headline = trim($_POST['welcome_headline'] ?? '');
    $p1 = trim($_POST['welcome_p1'] ?? '');
    $p2 = trim($_POST['welcome_p2'] ?? '');
    $img1_old = get_setting($conn,'welcome_img1', '');
    $img2_old = get_setting($conn,'welcome_img2', '');
    $img1_new = move_and_replace('welcome_img1', $img1_old, $UPLOAD_DIR);
    $img2_new = move_and_replace('welcome_img2', $img2_old, $UPLOAD_DIR);
    upsert_single_value($conn, 'welcome_headline', $headline);
    upsert_single_value($conn, 'welcome_p1', $p1);
    upsert_single_value($conn, 'welcome_p2', $p2);
    upsert_single_value($conn, 'welcome_img1', $img1_new);
    upsert_single_value($conn, 'welcome_img2', $img2_new);
    $flash = 'Welcome section updated.';
  }

  if (($_POST['action'] ?? '') === 'save_cta') {
    upsert_single_value($conn, 'cta_left_title', trim($_POST['cta_left_title'] ?? ''));
    upsert_single_value($conn, 'cta_left_text', trim($_POST['cta_left_text'] ?? ''));
    upsert_single_value($conn, 'cta_left_btn_text', trim($_POST['cta_left_btn_text'] ?? ''));
    upsert_single_value($conn, 'cta_left_btn_link', trim($_POST['cta_left_btn_link'] ?? ''));
    upsert_single_value($conn, 'cta_right_title', trim($_POST['cta_right_title'] ?? ''));
    upsert_single_value($conn, 'cta_right_text', trim($_POST['cta_right_text'] ?? ''));
    upsert_single_value($conn, 'cta_right_btn_text', trim($_POST['cta_right_btn_text'] ?? ''));
    upsert_single_value($conn, 'cta_right_btn_link', trim($_POST['cta_right_btn_link'] ?? ''));
    $flash = 'CTA cards updated.';
  }

  if (($_POST['action'] ?? '') === 'slider_add') {
    if (isset($_FILES['slide']) && $_FILES['slide']['error']===UPLOAD_ERR_OK) {
      $fn = time().'_'.safe_basename($_FILES['slide']['name']);
      @move_uploaded_file($_FILES['slide']['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
      $stmt = $conn->prepare("INSERT INTO home_slider (image, sort_order) VALUES (?,0)");
      $stmt->bind_param('s', $fn); $stmt->execute(); $stmt->close();
      $flash = 'Slide added.';
    }
  }
  if (($_POST['action'] ?? '') === 'slider_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $conn->query("SELECT image FROM home_slider WHERE id=".$id);
    if ($row=$res->fetch_assoc()) { unlink_file($UPLOAD_DIR, $row['image']); }
    $conn->query("DELETE FROM home_slider WHERE id=".$id);
    $flash = 'Slide deleted.';
  }

  if (($_POST['action'] ?? '') === 'gallery_add') {
    if (isset($_FILES['gimg']) && $_FILES['gimg']['error']===UPLOAD_ERR_OK) {
      $fn = time().'_'.safe_basename($_FILES['gimg']['name']);
      @move_uploaded_file($_FILES['gimg']['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
      $conn->query("INSERT INTO home_gallery (image, sort_order) VALUES ('".$conn->real_escape_string($fn)."',0)");
      $flash = 'Gallery image added.';
    }
  }
  if (($_POST['action'] ?? '') === 'gallery_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $conn->query("SELECT image FROM home_gallery WHERE id=".$id);
    if ($row=$res->fetch_assoc()) { unlink_file($UPLOAD_DIR, $row['image']); }
    $conn->query("DELETE FROM home_gallery WHERE id=".$id);
    $flash = 'Gallery image deleted.';
  }

  if (($_POST['action'] ?? '') === 'attraction_add') {
    $title = trim($_POST['title'] ?? '');
    if ($title && isset($_FILES['aimg']) && $_FILES['aimg']['error']===UPLOAD_ERR_OK) {
      $fn = time().'_'.safe_basename($_FILES['aimg']['name']);
      @move_uploaded_file($_FILES['aimg']['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
      $stmt = $conn->prepare("INSERT INTO home_attractions (title, image, sort_order) VALUES (?,?,0)");
      $stmt->bind_param('ss',$title,$fn); $stmt->execute(); $stmt->close();
      $flash = 'Attraction added.';
    }
  }
  if (($_POST['action'] ?? '') === 'attraction_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $conn->query("SELECT image FROM home_attractions WHERE id=".$id);
    if ($row=$res->fetch_assoc()) { unlink_file($UPLOAD_DIR, $row['image']); }
    $conn->query("DELETE FROM home_attractions WHERE id=".$id);
    $flash = 'Attraction deleted.';
  }

  if (($_POST['action'] ?? '') === 'testi_add') {
    $name = trim($_POST['name'] ?? '');
    $quote = trim($_POST['quote'] ?? '');
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error']===UPLOAD_ERR_OK) {
      $photo = time().'_'.safe_basename($_FILES['photo']['name']);
      @move_uploaded_file($_FILES['photo']['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $photo);
    }
    if ($name && $quote) {
      $stmt = $conn->prepare("INSERT INTO home_testimonials (name, quote, photo, sort_order) VALUES (?,?,?,0)");
      $stmt->bind_param('sss',$name,$quote,$photo); $stmt->execute(); $stmt->close();
      $flash = 'Testimonial added.';
    }
  }
  if (($_POST['action'] ?? '') === 'testi_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $conn->query("SELECT photo FROM home_testimonials WHERE id=".$id);
    if ($row=$res->fetch_assoc()) { unlink_file($UPLOAD_DIR, $row['photo']); }
    $conn->query("DELETE FROM home_testimonials WHERE id=".$id);
    $flash = 'Testimonial deleted.';
  }

  header("Location: page-home.php?ok=".urlencode($flash));
  exit;
}

/* ---------- LOAD DATA ---------- */
$welcome_headline = get_setting($conn,'welcome_headline');
$welcome_p1 = get_setting($conn,'welcome_p1');
$welcome_p2 = get_setting($conn,'welcome_p2');
$welcome_img1 = get_setting($conn,'welcome_img1');
$welcome_img2 = get_setting($conn,'welcome_img2');

$cta_left_title = get_setting($conn,'cta_left_title','KANAVU & BEYOND : A VISUAL JOURNEY');
$cta_left_text = get_setting($conn,'cta_left_text');
$cta_left_btn_text = get_setting($conn,'cta_left_btn_text','TAKE A TOUR');
$cta_left_btn_link = get_setting($conn,'cta_left_btn_link','photo');

$cta_right_title = get_setting($conn,'cta_right_title','MAKE YOUR CELEBRATIONS EXTRAORDINARY');
$cta_right_text = get_setting($conn,'cta_right_text');
$cta_right_btn_text = get_setting($conn,'cta_right_btn_text','CELEBRATE WITH US');
$cta_right_btn_link = get_setting($conn,'cta_right_btn_link','celebrations.php');

$slider = $conn->query("SELECT * FROM home_slider ORDER BY sort_order, id DESC");
$gallery = $conn->query("SELECT * FROM home_gallery ORDER BY sort_order, id DESC");
$attractions = $conn->query("SELECT * FROM home_attractions ORDER BY sort_order, id DESC");
$testimonials = $conn->query("SELECT * FROM home_testimonials ORDER BY sort_order, id DESC");

/* counts for tiles */
$slider_count = (int)$conn->query("SELECT COUNT(*) c FROM home_slider")->fetch_assoc()['c'];
$gallery_count = (int)$conn->query("SELECT COUNT(*) c FROM home_gallery")->fetch_assoc()['c'];
$attractions_count = (int)$conn->query("SELECT COUNT(*) c FROM home_attractions")->fetch_assoc()['c'];
$testi_count = (int)$conn->query("SELECT COUNT(*) c FROM home_testimonials")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pages · Home</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
    .grid{display:grid;gap:14px}
    .tiles{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
    .tile{background:#fff;border:1px solid #e7e0d7;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:4px}
    .tile .k{color:#666;font-size:12px}
    .tile .v{font-size:22px;font-weight:800}
    /* Accordion cards */
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
  </style>
</head>
<body>
<div class="container">
  <?php include('includes/sidebar.php'); ?>
  <div class="main">
    <?php include('includes/topbar.php'); ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Home Page</h2></div>
        <div class="actions">
          <a class="btn-lite" href="/kanav/" target="_blank">Visit site</a>
          <a class="btn-lite" href="/kanav/index" target="_blank">Open Home</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div class="notice"><?= htmlspecialchars($_GET['ok']) ?></div>
      <?php endif; ?>

      <!-- quick tiles -->
      <div class="grid tiles" style="margin-bottom:14px">
        <div class="tile">
          <div class="k">Slider images</div>
          <div class="v"><?= $slider_count ?></div>
        </div>
        <div class="tile">
          <div class="k">Gallery teaser</div>
          <div class="v"><?= $gallery_count ?></div>
        </div>
        <div class="tile">
          <div class="k">Nearby attractions</div>
          <div class="v"><?= $attractions_count ?></div>
        </div>
        <div class="tile">
          <div class="k">Testimonials</div>
          <div class="v"><?= $testi_count ?></div>
        </div>
      </div>

      <!-- HERO SLIDER -->
      <details class="card">
        <summary>
          <span>Hero Slider</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:10px">
            <input type="hidden" name="action" value="slider_add">
            <input type="file" name="slide" accept="image/*" required>
            <button class="btn">+ Add Slide</button>
          </form>
          <div class="thumbs">
            <?php while($s=$slider->fetch_assoc()): ?>
              <div class="thumb">
                <img src="../media/home/<?= htmlspecialchars($s['image']) ?>" alt="">
                <form method="post" onsubmit="return confirm('Delete this slide?')">
                  <input type="hidden" name="action" value="slider_delete">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn-lite">Delete</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
          <div class="muted">Tip: 1920×1080 or similar wide images.</div>
        </div>
      </details>

<!-- WELCOME -->
<details class="card" open>
  <summary>
    <span>Welcome Block (headline + Left/Right image & paragraph)</span>
    <span class="caret">▾</span>
  </summary>

  <div class="card-bd">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_welcome">

      <!-- Headline -->
      <div class="field" style="margin-bottom:16px">
        <label>Headline (gold message)</label>
        <textarea name="welcome_headline" rows="3"><?= htmlspecialchars($welcome_headline) ?></textarea>
      </div>

      <!-- Two columns -->
      <div class="grid two" style="gap:18px">
        <!-- LEFT SIDE -->
        <div class="card" style="border:1px dashed #e1d9cf">
          <div class="card-hd"><strong>Left Side</strong></div>
          <div class="card-bd">
            <div class="field">
              <label>Image Left</label>
              <?php if($welcome_img1): ?>
                <div class="thumb" style="margin-bottom:8px">
                  <img src="../media/home/<?= htmlspecialchars($welcome_img1) ?>" alt="">
                  <div class="muted">Current: <a href="../media/home/<?= htmlspecialchars($welcome_img1) ?>" target="_blank"><?= htmlspecialchars($welcome_img1) ?></a></div>
                </div>
              <?php endif; ?>
              <input type="file" name="welcome_img1" accept="image/*">
              <div class="muted">Recommended ~1200×800 JPG/PNG.</div>
            </div>

            <div class="field">
              <label>Paragraph 1 (left)</label>
              <textarea name="welcome_p1" rows="6"><?= htmlspecialchars($welcome_p1) ?></textarea>
            </div>
          </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="card" style="border:1px dashed #e1d9cf">
          <div class="card-hd"><strong>Right Side</strong></div>
          <div class="card-bd">
            <div class="field">
              <label>Image Right</label>
              <?php if($welcome_img2): ?>
                <div class="thumb" style="margin-bottom:8px">
                  <img src="../media/home/<?= htmlspecialchars($welcome_img2) ?>" alt="">
                  <div class="muted">Current: <a href="../media/home/<?= htmlspecialchars($welcome_img2) ?>" target="_blank"><?= htmlspecialchars($welcome_img2) ?></a></div>
                </div>
              <?php endif; ?>
              <input type="file" name="welcome_img2" accept="image/*">
              <div class="muted">Recommended ~1200×800 JPG/PNG.</div>
            </div>

            <div class="field">
              <label>Paragraph 2 (right)</label>
              <textarea name="welcome_p2" rows="6"><?= htmlspecialchars($welcome_p2) ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="actions" style="margin-top:12px">
        <button class="btn">Save Welcome</button>
      </div>
    </form>
  </div>
</details>


      <!-- CTA CARDS -->
      <details class="card">
        <summary>
          <span>CTA Cards (“Kanavu & Beyond” / “Celebrations”)</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd grid two">
          <form method="post" style="grid-column:1/-1">
            <input type="hidden" name="action" value="save_cta">
            <div class="grid two">
              <div class="field"><label>Left Title</label><input type="text" name="cta_left_title" value="<?= htmlspecialchars($cta_left_title) ?>"></div>
              <div class="field"><label>Right Title</label><input type="text" name="cta_right_title" value="<?= htmlspecialchars($cta_right_title) ?>"></div>
              <div class="field"><label>Left Text</label><textarea name="cta_left_text" rows="4"><?= htmlspecialchars($cta_left_text) ?></textarea></div>
              <div class="field"><label>Right Text</label><textarea name="cta_right_text" rows="4"><?= htmlspecialchars($cta_right_text) ?></textarea></div>
              <div class="field"><label>Left Button Text</label><input type="text" name="cta_left_btn_text" value="<?= htmlspecialchars($cta_left_btn_text) ?>"></div>
              <div class="field"><label>Left Button Link</label><input type="text" name="cta_left_btn_link" value="<?= htmlspecialchars($cta_left_btn_link) ?>"></div>
              <div class="field"><label>Right Button Text</label><input type="text" name="cta_right_btn_text" value="<?= htmlspecialchars($cta_right_btn_text) ?>"></div>
              <div class="field"><label>Right Button Link</label><input type="text" name="cta_right_btn_link" value="<?= htmlspecialchars($cta_right_btn_link) ?>"></div>
            </div>
            <button class="btn">Save CTA</button>
          </form>
        </div>
      </details>

      <!-- GALLERY -->
      <details class="card">
        <summary>
          <span>Gallery Teaser (3 images)</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:10px">
            <input type="hidden" name="action" value="gallery_add">
            <input type="file" name="gimg" accept="image/*" required>
            <button class="btn">+ Add Image</button>
          </form>
          <div class="thumbs">
            <?php while($g=$gallery->fetch_assoc()): ?>
              <div class="thumb">
                <img src="../media/home/<?= htmlspecialchars($g['image']) ?>" alt="">
                <form method="post" onsubmit="return confirm('Delete this image?')">
                  <input type="hidden" name="action" value="gallery_delete">
                  <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                  <button class="btn-lite">Delete</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </details>

      <!-- ATTRACTIONS -->
      <details class="card">
        <summary>
          <span>Nearby Attractions (image + title)</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:10px">
            <input type="hidden" name="action" value="attraction_add">
            <input type="text" name="title" placeholder="Title" required>
            <input type="file" name="aimg" accept="image/*" required>
            <button class="btn">+ Add</button>
          </form>
          <div class="thumbs">
            <?php while($a=$attractions->fetch_assoc()): ?>
              <div class="thumb">
                <img src="../media/home/<?= htmlspecialchars($a['image']) ?>" alt="">
                <div style="min-width:140px;font-weight:700"><?= htmlspecialchars($a['title']) ?></div>
                <form method="post" onsubmit="return confirm('Delete this attraction?')">
                  <input type="hidden" name="action" value="attraction_delete">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button class="btn-lite">Delete</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </details>

      <!-- TESTIMONIALS -->
      <details class="card">
        <summary>
          <span>Testimonials (photo optional)</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:10px">
            <input type="hidden" name="action" value="testi_add">
            <input type="text" name="name" placeholder="Name" required>
            <input type="file" name="photo" accept="image/*">
            <input type="text" name="quote" placeholder="Short quote" style="min-width:260px" required>
            <button class="btn">+ Add</button>
          </form>
          <div class="thumbs">
            <?php while($t=$testimonials->fetch_assoc()): ?>
              <div class="thumb">
                <?php if($t['photo']): ?>
                  <img src="../media/home/<?= htmlspecialchars($t['photo']) ?>" alt="">
                <?php else: ?>
                  <div style="height:54px;width:90px;display:grid;place-items:center;background:#f2f2f2;border-radius:6px;color:#999">No photo</div>
                <?php endif; ?>
                <div><strong><?= htmlspecialchars($t['name']) ?></strong><br><span class="muted"><?= htmlspecialchars($t['quote']) ?></span></div>
                <form method="post" onsubmit="return confirm('Delete this testimonial?')">
                  <input type="hidden" name="action" value="testi_delete">
                  <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                  <button class="btn-lite">Delete</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </details>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
