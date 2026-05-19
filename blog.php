<?php
// /kanav/blog.php (accessed as /kanav/blog via .htaccess)

require __DIR__ . '/includes/connect.php';
$conn->set_charset('utf8mb4');

/**
 * Base path auto-detect (works if site is /kanav or moved to root)
 * - If URL is /kanav/blog      -> BASE = /kanav
 * - If URL is /blog            -> BASE = ''
 */
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($BASE === '/' || $BASE === '\\') $BASE = '';

/* --- Page SEO from dashboard (slug=blog) --- */
$META = [
  'title'       => 'Blog | Kanavu Heritage',
  'description' => 'Stories, tips, and updates from Kanavu Heritage — explore Kerala, plan your stay, and discover local experiences.',
  'canonical'   => $BASE . '/blog',
  'og_image'    => $BASE . '/img/og-default.jpg',
];

$seoStmt = $conn->prepare("SELECT meta_title, meta_description, canonical_url, og_image FROM seo_pages WHERE page_slug='blog' LIMIT 1");
$seoStmt->execute();
$seoRes = $seoStmt->get_result();
if ($r = $seoRes->fetch_assoc()){
  if (!empty($r['meta_title']))       $META['title']       = $r['meta_title'];
  if (!empty($r['meta_description'])) $META['description'] = $r['meta_description'];
  if (!empty($r['canonical_url']))    $META['canonical']   = $r['canonical_url'];
  if (!empty($r['og_image']))         $META['og_image']    = $r['og_image'];
}
$seoStmt->close();

/* --- Pagination --- */
$perPage = 4;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

/* Count */
$totalBlogs = 0;
$resCount = $conn->query("SELECT COUNT(*) AS c FROM blog");
if ($resCount && ($rowC = $resCount->fetch_assoc())) $totalBlogs = (int)$rowC['c'];
$totalPages = max(1, (int)ceil($totalBlogs / $perPage));

/* Fetch current page posts */
$stmt = $conn->prepare("
  SELECT id, title, blog_type, `date`, image, slug
  FROM blog
  ORDER BY `date` DESC
  LIMIT ?, ?
");
$stmt->bind_param('ii', $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();

/* Helpers */
function blog_image_path($image){
  $image = trim((string)$image);
  return $image === '' ? 'img/gallery/01.jpg' : 'media/blogs/' . ltrim($image, '/');
}
function slugify($s){
  $s = mb_strtolower(trim($s));
  $s = preg_replace('~[^\p{L}\p{Nd}]+~u', '-', $s);
  $s = trim($s, '-');
  return $s ?: 'post';
}
function ensure_unique_slug(mysqli $conn, $base, $id){
  $slug = $base;
  $i = 1;
  while (true) {
    $q = $conn->prepare("SELECT id FROM blog WHERE slug=? AND id<>? LIMIT 1");
    $q->bind_param('si', $slug, $id);
    $q->execute();
    $exists = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$exists) return $slug;
    $slug = $base . '-' . (++$i);
  }
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title><?= htmlspecialchars($META['title']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($META['description']) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($META['canonical']) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($META['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($META['description']) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($META['og_image']) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($META['canonical']) ?>">
  <meta property="og:type" content="website">

  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="whatsapp_style.css" />
  <style>
    .bauen-blog .item img{width:100%;height:auto;display:block}
    .bauen-pagination-wrap li a.active{background:#b19777;color:#111}
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

<div class="content-wrapper">
  <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
    <div class="content-lines-inner"><div class="content-lines"></div></div>
  </section>
  <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg"></section>

  <section class="bauen-blog section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12"><h2 class="section-title">Our <span>Blog</span></h2></div></div>

      <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()):
            $id    = (int)$row['id'];
            $title = $row['title'] ?? '';
            $type  = $row['blog_type'] ?? 'Blog';
            $date  = !empty($row['date']) ? date("d.m.Y", strtotime($row['date'])) : '';
            $img   = blog_image_path($row['image'] ?? '');
            $slug  = $row['slug'] ?? '';

            // If slug missing, generate and save
            if (!$slug || trim($slug)==='') {
              $base = slugify($title ?: ('post-' . $id));
              $slug = ensure_unique_slug($conn, $base, $id);
              $up = $conn->prepare("UPDATE blog SET slug=? WHERE id=?");
              $up->bind_param('si', $slug, $id);
              $up->execute();
              $up->close();
            }

            /**
             * ✅ FIX: Use the real details file so it works even when rewrite is not working on live.
             * (Pretty URL /blog/<slug> is optional; this will always load.)
             */
            $detailsUrl = $BASE . '/blog_details.php?slug=' . rawurlencode($slug);

            // Blog list URL
            $blogListUrl = $BASE . '/blog';
          ?>
          <div class="col-md-6">
            <div class="item">
              <div class="position-re o-hidden">
                <a href="<?= htmlspecialchars($detailsUrl) ?>">
                  <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($title) ?>">
                </a>
              </div>
              <div class="con">
                <span class="category"><a href="<?= htmlspecialchars($blogListUrl) ?>"><?= htmlspecialchars($type) ?></a><?= $date ? ' - ' . $date : '' ?></span>
                <h5><a href="<?= htmlspecialchars($detailsUrl) ?>"><?= htmlspecialchars($title) ?></a></h5>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="col-12"><p>No blog posts yet. Please check back soon.</p></div>
        <?php endif; ?>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="row">
        <div class="col-md-12 text-center">
          <ul class="bauen-pagination-wrap align-center mb-30 mt-30">
            <?php if ($page > 1): ?><li><a href="<?= htmlspecialchars($blogListUrl) ?>?page=<?= $page-1 ?>"><i class="ti-angle-left"></i></a></li><?php endif; ?>
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
              <li><a href="<?= htmlspecialchars($blogListUrl) ?>?page=<?= $i ?>" class="<?= $i===$page ? 'active' : '' ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><li><a href="<?= htmlspecialchars($blogListUrl) ?>?page=<?= $page+1 ?>"><i class="ti-angle-right"></i></a></li><?php endif; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery-3.6.3.min.js"></script>
<script src="js/jquery-migrate-3.0.0.min.js"></script>
<script src="js/modernizr-2.6.2.min.js"></script>
<script src="js/imagesloaded.pkgd.min.js"></script>
<script src="js/jquery.isotope.v3.0.2.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/scrollIt.min.js"></script>
<script src="js/jquery.waypoints.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.stellar.min.js"></script>
<script src="js/jquery.magnific-popup.js"></script>
<script src="js/YouTubePopUp.js"></script>
<script src="js/before-after.js"></script>
<script src="js/vegas.slider.min.js"></script>
<script src="js/custom.js"></script>
</body>
</html>
