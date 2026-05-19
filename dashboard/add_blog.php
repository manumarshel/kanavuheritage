<?php
// /kanav/dashboard/add_blog
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* --- ensure slug column exists (portable) --- */
function col_exists(mysqli $conn, string $table, string $col): bool {
  // `SHOW COLUMNS ... LIKE` does not accept parameter placeholders in many
  // MySQL/MariaDB setups. Build a safe query using escaping and a strict
  // whitelist for the table name to avoid SQL injection.
  $table_safe = preg_replace('/[^0-9A-Za-z_]/', '', $table);
  $col_esc = $conn->real_escape_string($col);
  $sql = "SHOW COLUMNS FROM `{$table_safe}` LIKE '{$col_esc}'";
  $res = $conn->query($sql);
  $ok = ($res && $res->num_rows > 0);
  if ($res) $res->free();
  return $ok;
}
if (!col_exists($conn, 'blog', 'slug')) {
  $conn->query("ALTER TABLE blog ADD COLUMN slug VARCHAR(190) UNIQUE");
}

/* --- helpers --- */
function slugify(string $s): string {
  $s = trim(mb_strtolower($s));
  // replace non-letter/digit with hyphen
  $s = preg_replace('~[^\p{L}\p{Nd}]+~u', '-', $s);
  $s = trim($s, '-');
  if ($s === '') $s = 'post';
  return $s;
}
function ensure_unique_slug(mysqli $conn, string $base): string {
  $slug = $base;
  $i = 1;
  while (true) {
    $stmt = $conn->prepare("SELECT id FROM blog WHERE slug=? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) return $slug;
    $slug = $base . '-' . (++$i);
  }
}

/* --- handle submit --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $blog_type   = trim($_POST['blog_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date        = $_POST['date'] ?? '';
    $author_name = trim($_POST['author_name'] ?? '');
    $created_at  = date('Y-m-d H:i:s');

    if ($title === '' || $blog_type === '' || $description === '' || $date === '' || $author_name === '') {
        echo "<script>alert('All fields are required.'); history.back();</script>";
        exit();
    }

    // build unique slug from title
    $base = slugify($title);
    $slug = ensure_unique_slug($conn, $base);

    // upload image
    $upload_dir = __DIR__ . '/../media/blogs/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $image_name = '';
    if (!empty($_FILES['image']['name'])) {
        // Simple validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) {
            echo "<script>alert('Invalid image type. Use JPG/PNG/GIF/WebP.'); history.back();</script>";
            exit();
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . preg_replace('~[^a-zA-Z0-9_\-\.]+~','', basename($base)) . '.' . strtolower($ext);
        $target = $upload_dir . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            echo "<script>alert('Failed to upload image.'); history.back();</script>";
            exit();
        }
        // optional: set perms on Windows not needed; on Linux: @chmod($target, 0644);
    } else {
        echo "<script>alert('Please choose an image.'); history.back();</script>";
        exit();
    }

    // insert incl. slug (store only filename)
    $stmt = $conn->prepare("
        INSERT INTO blog (title, image, `date`, blog_type, description, author_name, created_at, slug)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssssss', $title, $image_name, $date, $blog_type, $description, $author_name, $created_at, $slug);
    $stmt->execute();
    $stmt->close();

    // --- ensure seo_pages table exists (minimal safe schema) ---
    $conn->query("CREATE TABLE IF NOT EXISTS seo_pages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      page_slug VARCHAR(190) NOT NULL,
      meta_title VARCHAR(255) NOT NULL DEFAULT '',
      meta_description TEXT NOT NULL,
      canonical_url VARCHAR(255) NOT NULL DEFAULT '',
      og_image VARCHAR(255) NOT NULL DEFAULT '',
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_page_slug (page_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Prepare SEO values for this blog
    $page_slug = 'blog/' . $slug;
    $meta_title = $title;
    $meta_description = mb_substr(strip_tags($description), 0, 160);
    $canonical = '/kanav/blog/' . rawurlencode($slug);
    $og = $image_name ? ('/kanav/media/blogs/' . ltrim($image_name, '/')) : '';

    // Upsert into seo_pages (insert or update existing page_slug)
    $upsert = $conn->prepare("INSERT INTO seo_pages (page_slug, meta_title, meta_description, canonical_url, og_image)
      VALUES (?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        meta_title=VALUES(meta_title),
        meta_description=VALUES(meta_description),
        canonical_url=VALUES(canonical_url),
        og_image=VALUES(og_image),
        updated_at=CURRENT_TIMESTAMP");
    $upsert->bind_param('sssss', $page_slug, $meta_title, $meta_description, $canonical, $og);
    $upsert->execute();
    $upsert->close();

    echo "<script>alert('Blog added successfully!'); window.location.href='blog';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Blog</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .form-wrapper { max-width: 900px; margin: 40px auto; background:#fff; padding:25px;
      border:2px solid #b19777; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.1);}
    .form-wrapper h3 { margin-bottom:20px; color:#222; text-align:center;}
    label { display:block; margin-top:15px; font-weight:700; color:#333;}
    input, textarea, select { width:100%; padding:10px; margin-top:5px; border-radius:6px; border:1px solid #ccc; font-size:15px;}
    textarea { min-height:120px; resize:vertical;}
    button { background:#b19777; color:#000; padding:10px 25px; border:none; border-radius:6px; margin-top:20px; cursor:pointer;}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="form-wrapper">
      <h3>Add Blog</h3>
      <form method="POST" enctype="multipart/form-data">
        <label>Blog Type</label>
        <input type="text" name="blog_type" required>

        <label>Blog Title</label>
        <input type="text" name="title" required>

        <label>Description</label>
        <textarea name="description" required></textarea>

        <label>Image</label>
        <input type="file" name="image" accept="image/*" required>

        <label>Date</label>
        <input type="date" name="date" required>

        <label>Author Name</label>
        <input type="text" name="author_name" required>

        <button type="submit">Save Blog</button>
      </form>

      <div style="text-align:center; margin-top:20px;">
        <button type="button" onclick="history.back()" style="background:#6c757d;color:#fff;border:none;padding:10px 20px;border-radius:5px;">Go Back</button>
      </div>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
