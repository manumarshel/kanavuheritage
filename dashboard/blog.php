<?php
// /kanav/dashboard/blog
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/**
 * Check whether a column exists, using INFORMATION_SCHEMA (works with prepared stmts)
 */
function col_exists(mysqli $conn, string $table, string $col): bool {
  $sql = "
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = ?
      AND COLUMN_NAME  = ?
    LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ss', $table, $col);
  $stmt->execute();
  $stmt->store_result();
  $ok = $stmt->num_rows > 0;
  $stmt->close();
  return $ok;
}

/* ensure slug column exists */
if (!col_exists($conn, 'blog', 'slug')) {
  $conn->query("ALTER TABLE `blog` ADD COLUMN `slug` VARCHAR(190) UNIQUE");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Blog List | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .content-wrapper{padding:30px}
    .top-header{display:flex;justify-content:space-between;align-items:center}
    .add-btn{background:#b19777;color:#000;padding:10px 16px;border-radius:6px;text-decoration:none;font-weight:700}
    table{width:100%;border-collapse:collapse;margin-top:20px}
    th,td{padding:12px;border:1px solid #ccc;vertical-align:top;text-align:left}
    th{background:#b19777;color:#000}
    img.blog-img{width:100px;height:auto;border-radius:6px;display:block}
    .table-text-limit{max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    td.action-col{white-space:nowrap}
    .icon-btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;margin-right:10px}
    .icon-btn.view{color:green}.icon-btn.edit{color:goldenrod}.icon-btn.delete{color:red}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="top-header">
        <h2>Blog List</h2>
        <a href="add_blog" class="add-btn">+ Add New Blog</a>
      </div>

      <table>
        <thead>
          <tr>
            <th>Sl.no</th>
            <th>Image</th>
            <th>Date</th>
            <th>Blog Type</th>
            <th>Blog Title</th>
            <th>Description</th>
            <th>Author Name</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        $res = $conn->query("SELECT id, title, image, `date`, blog_type, description, author_name, slug, created_at FROM blog ORDER BY created_at DESC");
        while ($blog = $res->fetch_assoc()):
          $img = '../media/blogs/' . htmlspecialchars($blog['image'] ?? '');
          $title = htmlspecialchars($blog['title'] ?? '');
          $type  = htmlspecialchars($blog['blog_type'] ?? '');
          $date  = htmlspecialchars($blog['date'] ?? '');
          $author= htmlspecialchars($blog['author_name'] ?? '');
          $clean_desc = strip_tags($blog['description'] ?? '');
          $descShort  = htmlspecialchars($clean_desc);
          $slug       = trim((string)$blog['slug']);
          // Fallback to id link if slug missing (shouldn’t happen after add)
          $viewUrl = $slug !== '' ? ("../blog_details.php?slug=" . urlencode($slug)) : ("../blog_details.php?id=" . (int)$blog['id']);
        ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?php if(!empty($blog['image'])): ?><img src="<?= $img ?>" class="blog-img" alt=""><?php endif; ?></td>
            <td><?= $date ?></td>
            <td><?= $type ?></td>
            <td><?= $title ?></td>
            <td class="table-text-limit" title="<?= $descShort ?>"><?= $descShort ?></td>
            <td><?= $author ?></td>
            <td class="action-col">
              <a class="icon-btn view" href="<?= $viewUrl ?>" target="_blank" title="View">
                <ion-icon name="open-outline" style="font-size:22px;"></ion-icon>
              </a>
              <a class="icon-btn edit" href="edit_blog?id=<?= (int)$blog['id'] ?>" title="Edit">
                <ion-icon name="create" style="font-size:22px;"></ion-icon>
              </a>
              <a class="icon-btn delete" href="delete_blog?id=<?= (int)$blog['id'] ?>"
                 onclick="return confirm('Delete this blog?');" title="Delete">
                <ion-icon name="trash" style="font-size:22px;"></ion-icon>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="./js/main.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>