<?php
// /kanav/dashboard/seo-pages.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* 1) DB CONNECT (robust search) */
$pathsToTry = [
  __DIR__ . '/includes/db_connect.php',
  __DIR__ . '/includes/connect.php',
  dirname(__DIR__) . '/includes/db_connect.php',
  dirname(__DIR__) . '/includes/connect.php',
  __DIR__ . '/../includes/db_connect.php',
  __DIR__ . '/../includes/connect.php',
];
$connected = false;
foreach ($pathsToTry as $p) { if (is_file($p)) { require_once $p; $connected = true; break; } }
if (!$connected) {
  die("DB connection file not found.\nLooked in:\n- " . implode("\n- ", $pathsToTry));
}
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
  die('DB include loaded, but $conn is missing or not a mysqli connection.');
}
$conn->set_charset('utf8mb4');

/* 2) Pages list (pretty URLs under /kanav) */
$PAGES = [
  ['slug'=>'home',           'label'=>'Home',              'file'=>'../index',                 'canonical'=>'/kanav/'],
  ['slug'=>'about',          'label'=>'About',             'file'=>'../about',                 'canonical'=>'/kanav/about'],
  ['slug'=>'accommodation',  'label'=>'Accommodation',     'file'=>'../accommodation',         'canonical'=>'/kanav/accommodation'],
  ['slug'=>'outdoor',        'label'=>'Outdoor',           'file'=>'../outdoor',               'canonical'=>'/kanav/outdoor'],
  ['slug'=>'amenities',      'label'=>'Amenities',         'file'=>'../amenities',             'canonical'=>'/kanav/amenities'],
  ['slug'=>'packages',       'label'=>'Packages',          'file'=>'../packages',              'canonical'=>'/kanav/packages'],
  ['slug'=>'dining',         'label'=>'Dining',            'file'=>'../dining',                'canonical'=>'/kanav/dining'],
  ['slug'=>'experiences',    'label'=>'Experiences',       'file'=>'../experiences',           'canonical'=>'/kanav/experiences'],
  ['slug'=>'vicinity',       'label'=>'Vicinity',          'file'=>'../vicinity',              'canonical'=>'/kanav/vicinity'],
  ['slug'=>'gallery',        'label'=>'Photo Gallery',     'file'=>'../photo',                 'canonical'=>'/kanav/photo'],
  ['slug'=>'video',          'label'=>'Video Gallery',     'file'=>'../video',                 'canonical'=>'/kanav/video'],
  ['slug'=>'faq',            'label'=>'FAQ',               'file'=>'../faq',                   'canonical'=>'/kanav/faq'],
  ['slug'=>'contact',        'label'=>'Contact',           'file'=>'../contact',               'canonical'=>'/kanav/contact'],
  ['slug'=>'terms',          'label'=>'Terms & Conditions','file'=>'../terms-and-conditions',  'canonical'=>'/kanav/terms-and-conditions'],
  ['slug'=>'blog',           'label'=>'Blog',              'file'=>'../blog',                  'canonical'=>'/kanav/blog'],
];
function page_find($pages,$slug){ foreach($pages as $p){ if($p['slug']===$slug) return $p; } return null; }
function open_file_for_slug($pages,$slug){ $p=page_find($pages,$slug); return $p ? $p['file'] : '../index'; }

/* 3) SAFE AUTO-MIGRATION */
$conn->query("CREATE TABLE IF NOT EXISTS seo_pages ( id INT AUTO_INCREMENT PRIMARY KEY ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

function col_exists($conn,$table,$col){
  $col = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $res && $res->num_rows > 0;
}
function index_exists($conn,$table,$indexName){
  $idx = $conn->real_escape_string($indexName);
  $res = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name='$idx'");
  return $res && $res->num_rows > 0;
}

if (!col_exists($conn,'seo_pages','page_slug'))         $conn->query("ALTER TABLE seo_pages ADD COLUMN page_slug VARCHAR(190) NULL");
if (!col_exists($conn,'seo_pages','meta_title'))        $conn->query("ALTER TABLE seo_pages ADD COLUMN meta_title VARCHAR(255) NOT NULL DEFAULT ''");
if (!col_exists($conn,'seo_pages','meta_description'))  $conn->query("ALTER TABLE seo_pages ADD COLUMN meta_description TEXT NOT NULL");
if (!col_exists($conn,'seo_pages','canonical_url'))     $conn->query("ALTER TABLE seo_pages ADD COLUMN canonical_url VARCHAR(255) NOT NULL DEFAULT ''");
if (!col_exists($conn,'seo_pages','og_image'))          $conn->query("ALTER TABLE seo_pages ADD COLUMN og_image VARCHAR(255) NOT NULL DEFAULT ''");
if (!col_exists($conn,'seo_pages','updated_at'))        $conn->query("ALTER TABLE seo_pages ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

/* Handle uniqueness + null slugs */
$conn->query("UPDATE seo_pages SET page_slug = CONCAT('page-', id) WHERE page_slug IS NULL OR page_slug = ''");
if (!index_exists($conn,'seo_pages','uniq_page_slug')) {
  $conn->query("ALTER TABLE seo_pages ADD UNIQUE KEY `uniq_page_slug` (page_slug)");
}
$conn->query("ALTER TABLE seo_pages MODIFY page_slug VARCHAR(190) NOT NULL");

/* Helpers */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function normalize_canonical(string $u): string {
  $u = trim($u);
  if ($u === '') return '';
  if (preg_match('~^https?://~i', $u)) return $u;       // allow absolute
  return '/' . ltrim($u, '/');                          // force site-relative
}

/* 4) CSRF */
if (empty($_SESSION['seo_csrf'])) { $_SESSION['seo_csrf'] = bin2hex(random_bytes(32)); }
function check_csrf(){
  if (empty($_POST['csrf']) || empty($_SESSION['seo_csrf']) || !hash_equals($_SESSION['seo_csrf'], $_POST['csrf'])) {
    die('Invalid session token.');
  }
}

/* 5) CRUD */
$flash = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $slug  = trim($_POST['page_slug'] ?? '');
    $title = trim($_POST['meta_title'] ?? '');
    $desc  = trim($_POST['meta_description'] ?? '');
    $canon = normalize_canonical(trim($_POST['canonical_url'] ?? ''));
    $og    = trim($_POST['og_image'] ?? '');

    if ($slug==='') {
      $flash = 'Please choose a page.';
    } else {
      // keep slugs lowercase a-z0-9- only
      $slug = strtolower(preg_replace('~[^a-z0-9\-]+~', '-', $slug));

      // simple length guards
      if (mb_strlen($title) > 255) $title = mb_substr($title, 0, 255);
      if (mb_strlen($canon) > 255) $canon = mb_substr($canon, 0, 255);
      if (mb_strlen($og) > 255)    $og    = mb_substr($og, 0, 255);

      if ($id > 0) {
        $stmt = $conn->prepare("UPDATE seo_pages
          SET page_slug=?, meta_title=?, meta_description=?, canonical_url=?, og_image=?
          WHERE id=?");
        $stmt->bind_param('sssssi', $slug,$title,$desc,$canon,$og,$id);
        $stmt->execute(); $stmt->close();
        $flash = 'Page meta updated.';
      } else {
        $stmt = $conn->prepare("INSERT INTO seo_pages (page_slug, meta_title, meta_description, canonical_url, og_image)
                                VALUES (?,?,?,?,?)
                                ON DUPLICATE KEY UPDATE
                                  meta_title=VALUES(meta_title),
                                  meta_description=VALUES(meta_description),
                                  canonical_url=VALUES(canonical_url),
                                  og_image=VALUES(og_image)");
        $stmt->bind_param('sssss', $slug,$title,$desc,$canon,$og);
        $stmt->execute(); $stmt->close();
        $flash = 'Page meta saved.';
      }
    }
  }

  if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      $stmt = $conn->prepare("DELETE FROM seo_pages WHERE id=?");
      $stmt->bind_param('i',$id);
      $stmt->execute(); $stmt->close();
      $flash = 'Deleted.';
    }
  }

  header("Location: seo-pages.php?ok=".urlencode($flash));
  exit;
}

/* 6) LOAD LIST / EDIT */
$list = [];
$res = $conn->query("SELECT id, page_slug, meta_title, meta_description, canonical_url, og_image, updated_at
                     FROM seo_pages ORDER BY page_slug ASC");
while ($row = $res->fetch_assoc()) { $list[] = $row; }
$res->close();

$editRow = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  foreach ($list as $r) { if ((int)$r['id']===$editId) {$editRow=$r; break;} }
}
$ok = isset($_GET['ok']) ? $_GET['ok'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SEO · Page Meta</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .wrap{padding:24px}
    .flex{display:flex;gap:16px;flex-wrap:wrap}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    @media(max-width:960px){.two{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;padding:16px}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field input[type=text], .field textarea, .field select{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
    .muted{color:#777;font-size:12px}
    .alert{padding:10px 12px;border-radius:10px;margin-bottom:12px}
    .ok{background:#f1fff3;border:1px solid #bfe3c6;color:#205d34}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="wrap">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
        <h2 style="margin:0">SEO · Page Meta (per page)</h2>
        <div class="flex">
          <a class="btn-lite" href="seo-global.php">Global Meta</a>
        </div>
      </div>

      <?php if($ok): ?>
        <div class="alert ok"><?= e($ok) ?></div>
      <?php endif; ?>

      <div class="two">
        <!-- Editor -->
        <div class="card">
          <h3 style="margin-top:0"><?= $editRow?'Edit page meta':'Add / Upsert page meta' ?></h3>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['seo_csrf']) ?>">
            <input type="hidden" name="action" value="save">
            <?php if($editRow): ?>
              <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
            <?php endif; ?>

            <div class="field">
              <label>Choose Page</label>
              <select name="page_slug" id="page_slug" required>
                <option value="">-- Select a page --</option>
                <?php
                $curSlug = $editRow['page_slug'] ?? '';
                // First output the static site pages
                $present = [];
                foreach($PAGES as $p){
                  $present[$p['slug']] = true;
                  $sel = ($curSlug === $p['slug']) ? 'selected' : '';
                  echo '<option value="'.e($p['slug']).'" '.$sel.'>'.e($p['label']).'</option>';
                }
                // Then append any seo_pages entries (e.g. blog/<slug>) that aren't in the static list
                foreach ($list as $r) {
                  $ps = $r['page_slug'];
                  if ($ps === null || $ps === '') continue;
                  if (isset($present[$ps])) continue;
                  $label = trim((string)($r['meta_title'] ?: $ps));
                  $sel = ($curSlug === $ps) ? 'selected' : '';
                  echo '<option value="'.e($ps).'" '.$sel.'>'.e($label).'</option>';
                }
                ?>
              </select>
              <div class="muted">Pick the page you want to edit SEO for.</div>
            </div>

            <div class="field">
              <label>Meta Title</label>
              <input type="text" name="meta_title" maxlength="255" value="<?= e($editRow['meta_title'] ?? '') ?>">
            </div>

            <div class="field">
              <label>Meta Description</label>
              <textarea name="meta_description" rows="4"><?= e($editRow['meta_description'] ?? '') ?></textarea>
            </div>

            <div class="field">
              <label>Canonical URL</label>
              <input type="text" id="canonical_url" name="canonical_url" placeholder="https://your-domain/page or /path"
                     value="<?= e($editRow['canonical_url'] ?? '') ?>">
              <div class="muted">Auto-fills from the chosen page; you can override it. Absolute (http/https) or site-relative (/path) allowed.</div>
            </div>

            <div class="field">
              <label>OG Image (URL or path)</label>
              <input type="text" name="og_image" placeholder="img/og/about.jpg"
                     value="<?= e($editRow['og_image'] ?? '') ?>">
            </div>

            <button class="btn">Save</button>
          </form>

          <script>
            (function(){
              const PAGES = <?= json_encode($PAGES, JSON_UNESCAPED_SLASHES) ?>;
              const sel   = document.getElementById('page_slug');
              const canon = document.getElementById('canonical_url');

              function find(slug){ return PAGES.find(p => p.slug === slug); }

              // ✅ If user types, mark as custom so we don't overwrite later
              canon?.addEventListener('input', () => {
                canon.dataset.autofilled = '0';
              });

              function setCanon(){
                const opt = find(sel?.value || '');
                if (!opt) return;
                // ✅ Only auto-fill if empty OR still in auto-filled mode
                if (!canon.value || canon.dataset.autofilled === '1') {
                  canon.value = opt.canonical || '';
                  canon.dataset.autofilled = '1';
                }
              }

              sel?.addEventListener('change', setCanon);

              // First load: if empty, auto-fill once
              if (!canon.value) { setCanon(); }
            })();
          </script>
        </div>

        <!-- List -->
        <div class="card">
          <h3 style="margin-top:0">Saved Pages</h3>
          <?php if(!$list): ?>
            <div class="muted">No pages yet. Add one on the left.</div>
          <?php else: ?>
            <table>
              <thead>
                <tr><th>Page</th><th>Meta Title</th><th>Updated</th><th>Actions</th></tr>
              </thead>
              <tbody>
              <?php foreach($list as $row): ?>
                <?php
                  $friendly = page_find($PAGES, $row['page_slug']);
                  $label = $friendly ? $friendly['label'] : $row['page_slug'];

                  // ✅ Use saved canonical if present; else fallback to mapped file
                  $openFile = trim((string)$row['canonical_url']) !== ''
                    ? $row['canonical_url']
                    : open_file_for_slug($PAGES, $row['page_slug']);
                ?>
                <tr>
                  <td style="min-width:180px">
                    <div><b><?= e($label) ?></b></div>
                    <div class="muted" style="margin-top:4px;word-break:break-all"><?= e($row['canonical_url']) ?></div>
                  </td>
                  <td><?= e($row['meta_title']) ?></td>
                  <td class="muted"><?= e($row['updated_at']) ?></td>
                  <td style="white-space:nowrap">
                    <a class="btn-lite" href="seo-pages.php?edit=<?= (int)$row['id'] ?>">Edit</a>
                    <a class="btn-lite" href="<?= e($openFile) ?>" target="_blank" rel="noopener">Open page</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this entry?')">
                      <input type="hidden" name="csrf" value="<?= e($_SESSION['seo_csrf']) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn-lite">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
