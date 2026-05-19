<?php
// /kanav/dashboard/page-packages.php — DASHBOARD EDITOR · CORRECTED

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- Ensure table exists ---------- */
$conn->query("
  CREATE TABLE IF NOT EXISTS packages_settings (
    `key`   VARCHAR(160) PRIMARY KEY,
    `value` LONGTEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ---------- Upload dir ---------- */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/packages');
if (!$UPLOAD_DIR) {
  @mkdir(__DIR__ . '/../media/packages', 0775, true);
  $UPLOAD_DIR = realpath(__DIR__ . '/../media/packages');
}

/* ---------- Helpers ---------- */
function upsert($conn, $key, $value){
  $stmt = $conn->prepare("INSERT INTO packages_settings (`key`,`value`) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss',$key,$value);
  $stmt->execute();
  $stmt->close();
}
function getv($conn, $key, $def=''){
  $stmt = $conn->prepare("SELECT `value` FROM packages_settings WHERE `key`=?");
  $stmt->bind_param('s',$key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? $row['value'] : $def;
}
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function safe_name($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field, $UPLOAD_DIR){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $fn = time().'_'.safe_name($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $UPLOAD_DIR . DIRECTORY_SEPARATOR . $fn);
  return $fn;
}
function unlink_if_local($path, $UPLOAD_DIR, $prefix='media/packages/'){
  if ($path && strpos($path, $prefix) === 0) {
    $full = $UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($path);
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
        upsert($conn,'hero_bg','media/packages/'.$fn);
      } else {
        $t = trim($_POST['hero_bg_text'] ?? '');
        if ($t!=='') upsert($conn,'hero_bg',$t);
      }
    }
    $flash = 'Hero banner saved.';
  }

  // INTRO
  if ($action==='save_intro') {
    foreach (['intro_h2','intro_text'] as $k) {
      if (isset($_POST[$k])) upsert($conn,$k, trim($_POST[$k]));
    }
    $flash = 'Intro saved.';
  }

  // Generic saver for a package section (p1..p4..p5)
  if (in_array($action, ['save_p1','save_p2','save_p3','save_p4','save_p5'], true)) {
    $sec = substr($action,-1); // 1..5
    foreach (['title','subtitle','occupancy','tariff','bullets','reminders','cta_link','terms_link'] as $k) {
      $key = "p{$sec}_{$k}";
      if (isset($_POST[$key])) upsert($conn, $key, trim($_POST[$key]));
    }
    $flash = "Section {$sec} saved.";
  }

  // Special table (Other shoots) rows
  if ($action==='save_p5_table') {
    for ($i=1;$i<=3;$i++){
      foreach (['label','time','tariff'] as $col){
        $key = "p5_row{$i}_{$col}";
        if (isset($_POST[$key])) upsert($conn,$key, trim($_POST[$key]));
      }
    }
    $flash = 'Other Shoots table saved.';
  }

  header("Location: page-packages?ok=".urlencode($flash));
  exit;
}

/* ---------- Load values (defaults aligned with static page) ---------- */
$hero_bg   = getv($conn,'hero_bg','img/gallery/01.jpg');
$intro_h2  = getv($conn,'intro_h2','TARIFF');
$intro_txt_default = "Explore exclusive packages tailored for a truly memorable stay at Kanavu Heritage.\nFrom overnight stays to special events, we offer diverse options to suit your preference.\nEach package ensures a unique and delightful experience.";
$intro_text = getv($conn,'intro_text',$intro_txt_default);

/* P1 — One Night */
$p1_title     = getv($conn,'p1_title','One Night');
$p1_subtitle  = getv($conn,'p1_subtitle','OVER NIGHT STAY (01 NIGHT & 02 DAYS) Check in /Check-out : 13:00 Hr/11:00 Hr');
$p1_occupancy = getv($conn,'p1_occupancy','Adults – 4 , Children (0-10 yrs) – 4');
$p1_tariff    = getv($conn,'p1_tariff','Tariff: Rs. 15000 + GST(18%)');
$p1_bullets   = getv($conn,'p1_bullets', "Welcome drink on arrival\nComplementary Breakfast\nEvening tea/coffee with snacks\nTea station in each room\nAccess to Kitchen (Optional*)\nAdditional Beds – Max.2 (Optional*)\nToiletries\nFree Wi-Fi\nCovered parking for 2 cars\nFree use of outdoor games");
$p1_reminders = getv($conn,'p1_reminders', "Bookings are restricted to families only\nPets are not allowed\nFor special events or shoots, exclusive reservations are necessary; separate tariff applies\nAn extra bed incurs a charge of Rs. 2500 per person\nAn additional charge of Rs.1000/Night applies for kitchen usage/self-cooking (bring your own groceries)\nMaximum Occupancy limit is 10 per booking including children");
$p1_cta       = getv($conn,'p1_cta_link','https://wa.link/3b9yly');
$p1_terms     = getv($conn,'p1_terms_link','terms&conditions.php');

/* P2 — 7 Days */
$p2_title     = getv($conn,'p2_title','7 Days Package');
$p2_subtitle  = getv($conn,'p2_subtitle','WEEKLY PACKAGE (7 DAYS) Check-in /Check-out : 13:00 Hr/11:00 Hr');
$p2_occupancy = getv($conn,'p2_occupancy','Adults – 4 , Children (0-10 yrs) – 4');
$p2_tariff    = getv($conn,'p2_tariff','Tariff: Rs. 91000 + GST(18%)');
$p2_bullets   = getv($conn,'p2_bullets', "Welcome drink on arrival\nComplementary Breakfast\nEvening tea/coffee with snacks\nTea station in each room\nAccess to Kitchen (Optional*)\nToiletries\nFree Wi-Fi\nCovered parking for 2 cars\nFree use of outdoor games");
$p2_reminders = getv($conn,'p2_reminders', "Bookings are restricted to families only\nFor special events or shoots, exclusive reservations are necessary; separate tariff applies\nPets are not allowed\nAn additional charge of Rs.1000/Night applies for kitchen usage/self-cooking (bring your own groceries)\nMaximum Occupancy limit is 8 per booking including children");
$p2_cta       = getv($conn,'p2_cta_link','https://wa.link/3b9yly');
$p2_terms     = getv($conn,'p2_terms_link','terms&conditions.php');

/* P3 — Monthly */
$p3_title     = getv($conn,'p3_title','Monthly Package');
$p3_subtitle  = getv($conn,'p3_subtitle','MONTHLY PACKAGE (30 DAYS) Check-in /Check-out : 13:00 Hr/11:00 Hr');
$p3_occupancy = getv($conn,'p3_occupancy','Adults – 4 , Children (0-10 yrs) – 4');
$p3_tariff    = getv($conn,'p3_tariff','Tariff: Rs. 300000 + GST(18%)');
$p3_bullets   = getv($conn,'p3_bullets', "Welcome drink on arrival\nComplementary Breakfast\nEvening tea/coffee with snacks\nTea station in each room\nAccess to Kitchen (Optional*)\nAdditional Beds – Max.2 (Optional*)\nToiletries\nFree Wi-Fi\nCovered parking for 2 cars\nFree use of outdoor games");
$p3_reminders = getv($conn,'p3_reminders', "Bookings are restricted to families only\nFor special events or shoots, exclusive reservations are necessary; separate tariff applies\nPets are not allowed\nAn additional charge of Rs.1000/Night applies for kitchen usage/self-cooking (bring your own groceries)\nMaximum Occupancy limit is 8 per booking including children");
$p3_cta       = getv($conn,'p3_cta_link','https://wa.link/3b9yly');
$p3_terms     = getv($conn,'p3_terms_link','terms&conditions.php');

/* P4 — Full Day / Overnight Photo Shoot */
$p4_title     = getv($conn,'p4_title','Full Day Photo Shoot');
$p4_subtitle  = getv($conn,'p4_subtitle','OVER NIGHT STAY WITH PHOTO SHOOT (01 NIGHT & 02 DAYS) Check-in /Check-out : 13:00 Hr/11:00 Hr');
$p4_occupancy = getv($conn,'p4_occupancy','Adults – 4 , Children (0-10 yrs) – 4');
$p4_tariff    = getv($conn,'p4_tariff','Tariff: Rs. 15000 + GST(18%)');
$p4_bullets   = getv($conn,'p4_bullets', "Welcome drink on arrival\nComplementary Breakfast\nEvening tea/coffee with snacks\nTea station in each room\nAccess to Kitchen (Optional*)\nAdditional Beds – Max.2 (Optional*)\nToiletries\nFree Wi-Fi\nCovered parking for 2 cars\nFree use of outdoor games");
$p4_reminders = getv($conn,'p4_reminders', "Photo shoot allowed only with overnight stay in this package\nRefer to category-specific rates and terms for other shoots and events\nPets are not allowed\nPrior approval is mandatory for extra lighting or any additional arrangements (bring your own power source)\nAn extra bed incurs a charge of Rs. 2500 per person\nAn additional charge of Rs.1000/Night applies for kitchen usage/self-cooking (bring your own groceries)\nMaximum Occupancy limit is 10 per booking including children");
$p4_cta       = getv($conn,'p4_cta_link','https://wa.link/3b9yly');
$p4_terms     = getv($conn,'p4_terms_link','terms&conditions.php');

/* P5 — Other Shoots & Events + table */
$p5_title     = getv($conn,'p5_title','Other Shoots & Events');
$p5_subtitle  = getv($conn,'p5_subtitle','PHOTO SHOOTS , EVENTS & OTHER SPECIAL OCCASIONS');
$p5_occupancy = getv($conn,'p5_occupancy','Maximum Occupancy - 50');
$p5_bullets   = getv($conn,'p5_bullets', "Free Wi-Fi\nParking for 6-8 cars\nFree use of outdoor games\nDedicated washroom & Handwash counter");
$p5_cta       = getv($conn,'p5_cta_link','https://wa.link/3b9yly');
$p5_terms     = getv($conn,'p5_terms_link','terms&conditions.php');
$p5_reminders = getv($conn,'p5_reminders', "Early check-ins or late checkouts are not allowed\nEntry is only permitted in the outdoor area of the property. Entry to indoor areas is strictly prohibited\nPets are not allowed\nPrior approval is mandatory for extra lighting or any additional arrangements (bring your own power source)\nGuests must remove all accumulated waste before checkout\nMaximum occupancy limit is 50 per booking including children\nAll bookings are subject to availability");

/* table rows */
$p5_r1_label = getv($conn,'p5_row1_label','PHOTO SHOOTS - HALF DAY');
$p5_r1_time  = getv($conn,'p5_row1_time','Morning - 07:00 Hr / 12:00 Hr | Afternoon - 13:00 Hr / 18:00 Hr');
$p5_r1_tar   = getv($conn,'p5_row1_tariff','Rs. 5000 + GST(18%)');

$p5_r2_label = getv($conn,'p5_row2_label','PHOTO SHOOTS – FULL DAY');
$p5_r2_time  = getv($conn,'p5_row2_time','07:00 Hr / 18:00 Hr');
$p5_r2_tar   = getv($conn,'p5_row2_tariff','Rs. 10000 + GST(18%)');

$p5_r3_label = getv($conn,'p5_row3_label','AD FILM / SHORT FILM SHOOTS, EVENTS AND OTHER SPECIAL OCCASIONS');
$p5_r3_time  = getv($conn,'p5_row3_time','06:00 Hr / 21:30 Hr');
$p5_r3_tar   = getv($conn,'p5_row3_tariff','Rs. 20000 + GST(18%)');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stay · Packages</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}
    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:980px){.two{grid-template-columns:1fr}}
    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s ease}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}
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
        <h2>Packages</h2>
        <div class="actions">
          <a class="btn-lite" href="../packages.php" target="_blank" rel="noopener">Open page</a>
          <a class="btn-lite" href="../" target="_blank" rel="noopener">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div class="notice"><?= e($_GET['ok']) ?></div>
      <?php endif; ?>

      <div class="grid two">
        <!-- HERO -->
        <details class="card" open>
          <summary><span>Hero banner (top)</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_hero">
              <div class="grid two">
                <div class="field">
                  <label>Upload hero image</label>
                  <input type="file" name="hero_bg" accept="image/*">
                  <div class="muted">Wide image (e.g. 1920×1080). Used as page banner.</div>
                </div>
                <div class="field">
                  <label>Or paste image path/URL</label>
                  <input type="text" name="hero_bg_text" placeholder="img/gallery/01.jpg or media/packages/hero.jpg">
                </div>
              </div>
              <?php if($hero_bg): ?>
                <div class="row-actions">
                  <div class="muted">Current: <a href="../<?= e($hero_bg) ?>" target="_blank" rel="noopener"><?= e($hero_bg) ?></a></div>
                  <label><input type="checkbox" name="hero_clear" value="1"> Delete current</label>
                </div>
              <?php endif; ?>
              <div class="actions" style="margin-top:8px"><button class="btn">Save Hero</button></div>
            </form>
          </div>
        </details>

        <!-- INTRO -->
        <details class="card" open>
          <summary><span>Intro (heading + small text)</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_intro">
              <div class="field"><label>Heading (e.g. TARIFF)</label><input type="text" name="intro_h2" value="<?= e($intro_h2) ?>"></div>
              <div class="field"><label>Intro text (multi-line)</label><textarea name="intro_text" rows="5"><?= e($intro_text) ?></textarea></div>
              <button class="btn">Save Intro</button>
            </form>
          </div>
        </details>

        <!-- P1 -->
        <details class="card" open>
          <summary><span>Section 1 — One Night</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_p1">
              <div class="field"><label>Title</label><input type="text" name="p1_title" value="<?= e($p1_title) ?>"></div>
              <div class="field"><label>Subtitle</label><input type="text" name="p1_subtitle" value="<?= e($p1_subtitle) ?>"></div>
              <div class="field"><label>Occupancy line</label><input type="text" name="p1_occupancy" value="<?= e($p1_occupancy) ?>"></div>
              <div class="field"><label>Tariff (text)</label><input type="text" name="p1_tariff" value="<?= e($p1_tariff) ?>"></div>
              <div class="field"><label>Bullets (one per line)</label><textarea rows="8" name="p1_bullets"><?= e($p1_bullets) ?></textarea></div>
              <div class="field"><label>Reminders (one per line)</label><textarea rows="8" name="p1_reminders"><?= e($p1_reminders) ?></textarea></div>
              <div class="grid two">
                <div class="field"><label>CTA link</label><input type="text" name="p1_cta_link" value="<?= e($p1_cta) ?>"></div>
                <div class="field"><label>Terms link</label><input type="text" name="p1_terms_link" value="<?= e($p1_terms) ?>"></div>
              </div>
              <button class="btn">Save Section 1</button>
            </form>
          </div>
        </details>

        <!-- P2 -->
        <details class="card" open>
          <summary><span>Section 2 — 7 Days Package</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_p2">
              <div class="field"><label>Title</label><input type="text" name="p2_title" value="<?= e($p2_title) ?>"></div>
              <div class="field"><label>Subtitle</label><input type="text" name="p2_subtitle" value="<?= e($p2_subtitle) ?>"></div>
              <div class="field"><label>Occupancy line</label><input type="text" name="p2_occupancy" value="<?= e($p2_occupancy) ?>"></div>
              <div class="field"><label>Tariff (text)</label><input type="text" name="p2_tariff" value="<?= e($p2_tariff) ?>"></div>
              <div class="field"><label>Bullets (one per line)</label><textarea rows="8" name="p2_bullets"><?= e($p2_bullets) ?></textarea></div>
              <div class="field"><label>Reminders (one per line)</label><textarea rows="8" name="p2_reminders"><?= e($p2_reminders) ?></textarea></div>
              <div class="grid two">
                <div class="field"><label>CTA link</label><input type="text" name="p2_cta_link" value="<?= e($p2_cta) ?>"></div>
                <div class="field"><label>Terms link</label><input type="text" name="p2_terms_link" value="<?= e($p2_terms) ?>"></div>
              </div>
              <button class="btn">Save Section 2</button>
            </form>
          </div>
        </details>

        <!-- P3 -->
        <details class="card">
          <summary><span>Section 3 — Monthly Package</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_p3">
              <div class="field"><label>Title</label><input type="text" name="p3_title" value="<?= e($p3_title) ?>"></div>
              <div class="field"><label>Subtitle</label><input type="text" name="p3_subtitle" value="<?= e($p3_subtitle) ?>"></div>
              <div class="field"><label>Occupancy line</label><input type="text" name="p3_occupancy" value="<?= e($p3_occupancy) ?>"></div>
              <div class="field"><label>Tariff (text)</label><input type="text" name="p3_tariff" value="<?= e($p3_tariff) ?>"></div>
              <div class="field"><label>Bullets (one per line)</label><textarea rows="8" name="p3_bullets"><?= e($p3_bullets) ?></textarea></div>
              <div class="field"><label>Reminders (one per line)</label><textarea rows="8" name="p3_reminders"><?= e($p3_reminders) ?></textarea></div>
              <div class="grid two">
                <div class="field"><label>CTA link</label><input type="text" name="p3_cta_link" value="<?= e($p3_cta) ?>"></div>
                <div class="field"><label>Terms link</label><input type="text" name="p3_terms_link" value="<?= e($p3_terms) ?>"></div>
              </div>
              <button class="btn">Save Section 3</button>
            </form>
          </div>
        </details>

        <!-- P4 -->
        <details class="card">
          <summary><span>Section 4 — Full Day / Overnight Photo Shoot</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_p4">
              <div class="field"><label>Title</label><input type="text" name="p4_title" value="<?= e($p4_title) ?>"></div>
              <div class="field"><label>Subtitle</label><input type="text" name="p4_subtitle" value="<?= e($p4_subtitle) ?>"></div>
              <div class="field"><label>Occupancy line</label><input type="text" name="p4_occupancy" value="<?= e($p4_occupancy) ?>"></div>
              <div class="field"><label>Tariff (text)</label><input type="text" name="p4_tariff" value="<?= e($p4_tariff) ?>"></div>
              <div class="field"><label>Bullets (one per line)</label><textarea rows="8" name="p4_bullets"><?= e($p4_bullets) ?></textarea></div>
              <div class="field"><label>Reminders (one per line)</label><textarea rows="8" name="p4_reminders"><?= e($p4_reminders) ?></textarea></div>
              <div class="grid two">
                <div class="field"><label>CTA link</label><input type="text" name="p4_cta_link" value="<?= e($p4_cta) ?>"></div>
                <div class="field"><label>Terms link</label><input type="text" name="p4_terms_link" value="<?= e($p4_terms) ?>"></div>
              </div>
              <button class="btn">Save Section 4</button>
            </form>
          </div>
        </details>

        <!-- P5 Other Shoots -->
        <details class="card">
          <summary><span>Section 5 — Other Shoots & Events</span><span class="caret">▾</span></summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_p5">
              <div class="field"><label>Title</label><input type="text" name="p5_title" value="<?= e($p5_title) ?>"></div>
              <div class="field"><label>Subtitle</label><input type="text" name="p5_subtitle" value="<?= e($p5_subtitle) ?>"></div>
              <div class="field"><label>Occupancy line</label><input type="text" name="p5_occupancy" value="<?= e($p5_occupancy) ?>"></div>
              <div class="field"><label>Bullets (one per line)</label><textarea rows="6" name="p5_bullets"><?= e($p5_bullets) ?></textarea></div>
              <div class="grid two">
                <div class="field"><label>CTA link</label><input type="text" name="p5_cta_link" value="<?= e($p5_cta) ?>"></div>
                <div class="field"><label>Terms link</label><input type="text" name="p5_terms_link" value="<?= e($p5_terms) ?>"></div>
              </div>
              <div class="field"><label>Reminders (one per line)</label><textarea rows="8" name="p5_reminders"><?= e($p5_reminders) ?></textarea></div>
              <button class="btn">Save Section 5 (text)</button>
            </form>

            <hr style="margin:10px 0;border:none;border-top:1px solid #eee">

            <form method="post">
              <input type="hidden" name="action" value="save_p5_table">
              <div class="grid two">
                <div class="field"><label>Row 1 — Occasion</label><input type="text" name="p5_row1_label" value="<?= e($p5_r1_label) ?>"></div>
                <div class="field"><label>Row 1 — Time</label><input type="text" name="p5_row1_time" value="<?= e($p5_r1_time) ?>"></div>
                <div class="field"><label>Row 1 — Tariff</label><input type="text" name="p5_row1_tariff" value="<?= e($p5_r1_tar) ?>"></div>

                <div class="field"><label>Row 2 — Occasion</label><input type="text" name="p5_row2_label" value="<?= e($p5_r2_label) ?>"></div>
                <div class="field"><label>Row 2 — Time</label><input type="text" name="p5_row2_time" value="<?= e($p5_r2_time) ?>"></div>
                <div class="field"><label>Row 2 — Tariff</label><input type="text" name="p5_row2_tariff" value="<?= e($p5_r2_tar) ?>"></div>

                <div class="field"><label>Row 3 — Occasion</label><input type="text" name="p5_row3_label" value="<?= e($p5_r3_label) ?>"></div>
                <div class="field"><label>Row 3 — Time</label><input type="text" name="p5_row3_time" value="<?= e($p5_r3_time) ?>"></div>
                <div class="field"><label>Row 3 — Tariff</label><input type="text" name="p5_row3_tariff" value="<?= e($p5_r3_tar) ?>"></div>
              </div>
              <button class="btn">Save Table</button>
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
