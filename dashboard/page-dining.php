<?php
// /kanav/dashboard/page-dining.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* ---------- HELPERS ---------- */
function upsert_setting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO dining_settings (`key`,`value`) VALUES (?,?)
                            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $stmt->close();
}
function get_setting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT `value` FROM dining_settings WHERE `key`=?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? $row['value'] : $default;
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Intro + 2 blocks
    if ($action === 'save_intro') {
        upsert_setting($conn, 'heading', trim($_POST['heading'] ?? 'DINING'));
        upsert_setting($conn, 'intro_text', trim($_POST['intro_text'] ?? ''));
        upsert_setting($conn, 'b1_title', trim($_POST['b1_title'] ?? ''));
        upsert_setting($conn, 'b1_text', trim($_POST['b1_text'] ?? ''));
        upsert_setting($conn, 'b2_title', trim($_POST['b2_title'] ?? ''));
        upsert_setting($conn, 'b2_text', trim($_POST['b2_text'] ?? ''));
        $flash = 'Intro & cards saved.';
    }

    // Menu blocks (m1..m4) — with sub menu box (m{n}_sub)
    if (strpos($action, 'save_m') === 0) {
        $n = substr($action, -1); // 1..4
        upsert_setting($conn, "m{$n}_title",    trim($_POST["m{$n}_title"] ?? ''));
        upsert_setting($conn, "m{$n}_note",     trim($_POST["m{$n}_note"]  ?? ''));
        upsert_setting($conn, "m{$n}_btn_text", trim($_POST["m{$n}_btn_text"] ?? 'Download Menu'));
        upsert_setting($conn, "m{$n}_btn_href", trim($_POST["m{$n}_btn_href"] ?? ''));
        upsert_setting($conn, "m{$n}_sub",      trim($_POST["m{$n}_sub"] ?? '')); // sub menu box under green heading
        $flash = "Menu block M{$n} saved.";
    }

    // Terms button
    if ($action === 'save_terms_button') {
        upsert_setting($conn, 'terms_note', trim($_POST['terms_note'] ?? ''));
        upsert_setting($conn, 'terms_link', trim($_POST['terms_link'] ?? 'terms&conditions.php'));
        upsert_setting($conn, 'terms_btn_text', trim($_POST['terms_btn_text'] ?? 'Terms & Conditions'));
        $flash = 'Terms button saved.';
    }

    header("Location: page-dining.php?ok=" . urlencode($flash));
    exit;
}

/* ---------- LOAD (defaults aligned with original static HTML) ---------- */
$heading   = get_setting($conn, 'heading', 'DINING');
$intro     = get_setting($conn, 'intro_text', '');

// Feature blocks
$b1_title  = get_setting($conn, 'b1_title', 'Morning Bliss : Begin Your Day with Our Complimentary Offerings');
$b1_text   = get_setting($conn, 'b1_text',
    'Awaken your day with the enticing aromas of a complimentary breakfast, ' .
    'a symphony of flavors carefully crafted to kickstart your morning in the most delightful way.'
);

$b2_title  = get_setting($conn, 'b2_title', 'Custom Cuisine : Tailored Dining at Your Fingertips');
$b2_text   = get_setting($conn, 'b2_text',
    'For those seeking an elevated dining experience, our in-house chef is at your service. ' .
    'Indulge in personalized culinary creations, where each dish is a work of art. ' .
    'Please note that additional charges apply; kindly inform us in advance.'
);

// Menu defaults copied from your original HTML dining page

// M1: Breakfast
$m1_title    = get_setting($conn, 'm1_title', 'COMPLIMENTRY BREAKFAST');
$m1_note     = get_setting($conn, 'm1_note',
    'To ensure delightful dining experience, guests are kindly requested to inform us of their ' .
    'Breakfast preferences by the previous day.'
);
$m1_btn_text = get_setting($conn, 'm1_btn_text', 'Download Menu');
$m1_btn_href = get_setting($conn, 'm1_btn_href', 'https://kanavuheritage.com/img/pdf/BREAKFAST_MENU.pdf');
$m1_sub      = get_setting($conn, 'm1_sub', '');

// M2: Lunch
$m2_title    = get_setting($conn, 'm2_title', 'LUNCH');
$m2_note     = get_setting($conn, 'm2_note',
    'Orders should be placed at least three hours prior to dining, giving our chef the ' .
    'necessary time to prepare your meal to perfection.'
);
$m2_btn_text = get_setting($conn, 'm2_btn_text', 'Download Menu');
$m2_btn_href = get_setting($conn, 'm2_btn_href', 'https://kanavuheritage.com/img/pdf/LUNCH_MENU.pdf');
$m2_sub      = get_setting($conn, 'm2_sub', '');

// M3: Dinner
$m3_title    = get_setting($conn, 'm3_title', 'DINNER');
$m3_note     = get_setting($conn, 'm3_note',
    'Orders should be placed at least three hours prior to dining, giving our chef the ' .
    'ample time to prepare your meal to perfection.'
);
$m3_btn_text = get_setting($conn, 'm3_btn_text', 'Download Menu');
$m3_btn_href = get_setting($conn, 'm3_btn_href', 'https://kanavuheritage.com/img/pdf/DINNER_MENU.pdf');
$m3_sub      = get_setting($conn, 'm3_sub', '');

// M4: Kanavu Specials
$m4_title    = get_setting($conn, 'm4_title', 'KANAVU SPECIALS');
$m4_note     = get_setting($conn, 'm4_note',
    'Orders should be placed at least three hours prior to dining, giving our chef the ' .
    'ample time to prepare your meal to perfection.'
);
$m4_btn_text = get_setting($conn, 'm4_btn_text', 'Download Menu');
$m4_btn_href = get_setting($conn, 'm4_btn_href', 'https://kanavuheritage.com/img/pdf/KANAVU%20SPECIAL_MENU.pdf');
$m4_sub      = get_setting($conn, 'm4_sub', '');

// Terms line
$terms_note    = get_setting(
    $conn,
    'terms_note',
    '- Kindly take a moment to review our terms and conditions before making a reservation to ensure a seamless experience. Your cooperation is valued -'
);
$terms_link    = get_setting($conn, 'terms_link', 'terms&conditions.php');
$terms_btn_txt = get_setting($conn, 'terms_btn_text', 'Terms & Conditions');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard · Dining</title>
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
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:920px){.two{grid-template-columns:1fr}}
    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden;margin-bottom:12px}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s ease}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field input[type="text"], .field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Dining Page Content</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../dining.php" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if (isset($_GET['ok'])): ?>
        <div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- INTRO + 2 CARDS -->
      <details class="card" open>
        <summary>
          <span>Intro &amp; two feature blocks</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post">
            <input type="hidden" name="action" value="save_intro">
            <div class="grid two">
              <div class="field">
                <label>Heading</label>
                <input type="text" name="heading" value="<?= htmlspecialchars($heading) ?>">
              </div>
              <div class="field">
                <label>Intro text</label>
                <textarea name="intro_text" rows="4"><?= htmlspecialchars($intro) ?></textarea>
              </div>

              <div class="field">
                <label>Block 1 Title</label>
                <input type="text" name="b1_title" value="<?= htmlspecialchars($b1_title) ?>">
              </div>
              <div class="field">
                <label>Block 1 Text</label>
                <textarea name="b1_text" rows="4"><?= htmlspecialchars($b1_text) ?></textarea>
              </div>

              <div class="field">
                <label>Block 2 Title</label>
                <input type="text" name="b2_title" value="<?= htmlspecialchars($b2_title) ?>">
              </div>
              <div class="field">
                <label>Block 2 Text</label>
                <textarea name="b2_text" rows="4"><?= htmlspecialchars($b2_text) ?></textarea>
              </div>
            </div>
            <button class="btn">Save Intro</button>
          </form>
        </div>
      </details>

      <!-- MENU BLOCKS -->
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <?php
          $title    = ${"m{$i}_title"};
          $note     = ${"m{$i}_note"};
          $btn_text = ${"m{$i}_btn_text"};
          $btn_href = ${"m{$i}_btn_href"};
          $sub      = ${"m{$i}_sub"};
        ?>
        <details class="card">
          <summary>
            <span>Menu Block M<?= $i ?></span>
            <span class="caret">▾</span>
          </summary>
          <div class="card-bd">
            <form method="post">
              <input type="hidden" name="action" value="save_m<?= $i ?>">
              <div class="grid two">
                <div class="field">
                  <label>Title (accordion heading)</label>
                  <input type="text" name="m<?= $i ?>_title" value="<?= htmlspecialchars($title) ?>">
                </div>
                <div class="field">
                  <label>Note (shows inside card, under &quot;*** PLEASE NOTE ***&quot;)</label>
                  <textarea name="m<?= $i ?>_note" rows="3"><?= htmlspecialchars($note) ?></textarea>
                </div>

                <!-- Sub menu box -->
                <div class="field" style="grid-column:1 / -1">
                  <label>Sub menu box (line under the green heading)</label>
                  <textarea name="m<?= $i ?>_sub" rows="2"><?= htmlspecialchars($sub) ?></textarea>
                  <div class="muted">Example: “South Indian Meals | Kerala Thali Available”</div>
                </div>

                <div class="field">
                  <label>Button text</label>
                  <input type="text" name="m<?= $i ?>_btn_text" value="<?= htmlspecialchars($btn_text) ?>">
                </div>
                <div class="field">
                  <label>Button link (PDF/URL)</label>
                  <input type="text" name="m<?= $i ?>_btn_href" value="<?= htmlspecialchars($btn_href) ?>">
                </div>
              </div>
              <button class="btn">Save Block M<?= $i ?></button>
            </form>
          </div>
        </details>
      <?php endfor; ?>

      <!-- TERMS BUTTON -->
      <details class="card">
        <summary>
          <span>Terms &amp; Conditions button</span>
          <span class="caret">▾</span>
        </summary>
        <div class="card-bd">
          <form method="post">
            <input type="hidden" name="action" value="save_terms_button">
            <div class="grid two">
              <div class="field">
                <label>Note (line above button)</label>
                <input type="text" name="terms_note" value="<?= htmlspecialchars($terms_note) ?>">
              </div>
              <div class="field">
                <label>Button text</label>
                <input type="text" name="terms_btn_text" value="<?= htmlspecialchars($terms_btn_txt) ?>">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>Button link</label>
                <input type="text" name="terms_link" value="<?= htmlspecialchars($terms_link) ?>">
              </div>
            </div>
            <div class="actions">
              <button class="btn">Save Terms Button</button>
              <?php if ($terms_link): ?>
                <a class="btn-lite" href="../<?= htmlspecialchars($terms_link) ?>" target="_blank">Open Terms Page</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </details>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
