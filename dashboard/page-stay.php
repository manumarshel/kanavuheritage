<?php
// /dashboard/page-stay.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';

// ---- Safe counters (will not error if table is missing) ----
function table_count_safe(mysqli $conn, string $table): int {
  try {
    $res = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($table)}'");
    if ($res && $res->num_rows) {
      $q = $conn->query("SELECT COUNT(*) AS c FROM {$table}");
      if ($q && ($row = $q->fetch_assoc())) return (int)$row['c'];
    }
  } catch (Throwable $e) { /* swallow */ }
  return 0;
}

/*
  Adjust table names below to whatever you actually use.
  These are common choices—change if yours differ:
  - accommodation  : rooms/cottages listing
  - outdoor        : outdoor spaces gallery/sections
  - amenities      : amenities list / icons
  - packages       : package offers / deals
*/
$accCount     = table_count_safe($conn, 'accommodation');
$outdoorCount = table_count_safe($conn, 'outdoor');
$amenCount    = table_count_safe($conn, 'amenities');
$packCount    = table_count_safe($conn, 'packages');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stay | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:28px}
    .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .page-title h2{margin:0}

    .pillbar{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid #3b2f1b;border-radius:999px;background:#b19777;color:#111;text-decoration:none;font-weight:800}
    .pill:hover{background:#c4ac8b}

    .grid{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}

    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
    .card-hd{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #eee}
    .card-tt{font-weight:800}
    .card-bd{padding:16px}
    .muted{color:#777}
    .count{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:10px;background:#f3efe9;border:1px solid #e7e0d7;font-weight:700}

    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Stay</h2></div>
        <div class="pillbar">
          <!-- quick jumps to child pages -->
          <a class="pill" href="page-accommodation">Accommodation</a>
          <a class="pill" href="page-outdoor">Outdoor Spaces</a>
          <a class="pill" href="page-amenities">Amenities &amp; Facilities</a>
          <a class="pill" href="page-packages">Packages</a>
        </div>
      </div>

      <div class="grid">
        <!-- Accommodation -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Accommodation</div>
            <span class="count"><?= (int)$accCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Manage rooms/cottages, photos, and details that appear on the Accommodation section.</p>
            <div class="actions">
              <a class="btn" href="page-accommodation">Manage</a>
              <a class="btn btn-lite" href="../accommodation" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>

        <!-- Outdoor -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Outdoor Spaces</div>
            <span class="count"><?= (int)$outdoorCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Photos and content for courtyards, walkways, gardens and open areas.</p>
            <div class="actions">
              <a class="btn" href="page-outdoor">Manage</a>
              <a class="btn btn-lite" href="../outdoor" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>

        <!-- Amenities -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Amenities &amp; Facilities</div>
            <span class="count"><?= (int)$amenCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Icons, features and short descriptions for amenities and facilities.</p>
            <div class="actions">
              <a class="btn" href="page-amenities">Manage</a>
              <a class="btn btn-lite" href="../amenities" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>

        <!-- Packages -->
        <div class="card">
          <div class="card-hd">
            <div class="card-tt">Packages</div>
            <span class="count"><?= (int)$packCount ?> items</span>
          </div>
          <div class="card-bd">
            <p class="muted">Add seasonal offers or curated experiences as packages.</p>
            <div class="actions">
              <a class="btn" href="page-packages">Manage</a>
              <a class="btn btn-lite" href="../packages" target="_blank">Open Public Page</a>
            </div>
          </div>
        </div>
      </div><!-- /grid -->

      <p class="muted" style="margin-top:10px">Tip: counts are read from tables:
        <code>accommodation</code>, <code>outdoor</code>, <code>amenities</code>, <code>packages</code>.
        If your table names differ, change them at the top of this file.</p>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
