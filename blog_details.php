<?php
// /kanav/blog_details.php
require __DIR__ . '/includes/connect.php';
$conn->set_charset('utf8mb4');

/* Read slug or id */
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id   = isset($_GET['id'])   ? (int)($_GET['id'] ?? 0) : 0;

$post = null;
if ($slug !== '') {
  $stmt = $conn->prepare("SELECT id, title, blog_type, `date`, image, author_name, description, slug
                          FROM blog WHERE slug = ? LIMIT 1");
  $stmt->bind_param('s', $slug);
  $stmt->execute();
  $res = $stmt->get_result();
  $post = $res->fetch_assoc();
  $stmt->close();
}

/* Backward compatibility: allow ?id=123 and 301-redirect to pretty URL */
if (!$post && $id > 0) {
  $stmt = $conn->prepare("SELECT id, title, blog_type, `date`, image, author_name, description, slug
                          FROM blog WHERE id = ? LIMIT 1");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $post = $res->fetch_assoc();
  $stmt->close();

  if ($post) {
    // create slug if missing
    if (!$post['slug'] || trim($post['slug']) === '') {
      $base = mb_strtolower(trim($post['title'] ?: ('post-' . $post['id'])));
      $base = preg_replace('~[^\p{L}\p{Nd}]+~u', '-', $base);
      $base = trim($base, '-') ?: ('post-' . $post['id']);
      // ensure unique
      $uniq = $base; $i = 1;
      while (true) {
        $q = $conn->prepare("SELECT id FROM blog WHERE slug=? AND id<>? LIMIT 1");
        $q->bind_param('si', $uniq, $post['id']);
        $q->execute();
        $exist = $q->get_result()->fetch_assoc();
        $q->close();
        if (!$exist) break;
        $uniq = $base . '-' . (++$i);
      }
      $up = $conn->prepare("UPDATE blog SET slug=? WHERE id=?");
      $up->bind_param('si', $uniq, $post['id']);
      $up->execute(); $up->close();
      $post['slug'] = $uniq;
    }
    // Redirect to pretty URL under /kanav/
    header("Location: /kanav/blog/" . urlencode($post['slug']), true, 301);
    exit;
  }
}

function blog_image_src($file) {
  $file = trim((string)$file);
  return $file !== '' ? ('media/blogs/' . ltrim($file, '/')) : 'img/gallery/01.jpg';
}

/* SEO defaults (canonical uses pretty URL if we have a post) */
if ($post) {
  // Try to load SEO overrides from `seo_pages` if available.
  $seo = null;
  $variants = [
    'blog/' . $post['slug'],
    $post['slug'],
    'blog-' . $post['slug']
  ];
  $q = $conn->prepare("SELECT meta_title, meta_description, canonical_url, og_image FROM seo_pages WHERE page_slug = ? LIMIT 1");
  if ($q) {
    foreach ($variants as $v) {
      if (!$v) continue;
      $q->bind_param('s', $v);
      $q->execute();
      $r = $q->get_result()->fetch_assoc();
      if ($r) { $seo = $r; break; }
    }
    $q->close();
  }

  if ($seo) {
    $canonPretty = $seo['canonical_url'] ?: ('/kanav/blog/' . rawurlencode($post['slug']));
    $rawDesc = trim((string)($seo['meta_description'] ?? $post['description'] ?? ''));
    $META = [
      'title'       => ($seo['meta_title'] ? $seo['meta_title'] : ($post['title'] ? $post['title'].' | Blog | Kanavu Heritage' : 'Blog Details | Kanavu Heritage')),
      'description' => $rawDesc !== '' ? mb_substr(strip_tags($rawDesc), 0, 160) : 'Read the latest story from Kanavu Heritage.',
      'canonical'   => $canonPretty,
      'og_image'    => !empty($seo['og_image']) ? $seo['og_image'] : (!empty($post['image']) ? ('/kanav/media/blogs/' . ltrim($post['image'], '/')) : '/kanav/img/og-default.jpg')
    ];
  } else {
    $canonPretty = '/kanav/blog/' . rawurlencode($post['slug']);
    $rawDesc = trim((string)($post['description'] ?? ''));
    $META = [
      'title'       => ($post['title'] ? $post['title'].' | Blog | Kanavu Heritage' : 'Blog Details | Kanavu Heritage'),
      'description' => $rawDesc !== '' ? mb_substr(strip_tags($rawDesc), 0, 160) : 'Read the latest story from Kanavu Heritage.',
      'canonical'   => $canonPretty,
      'og_image'    => !empty($post['image']) ? ('/kanav/media/blogs/' . ltrim($post['image'], '/')) : '/kanav/img/og-default.jpg'
    ];
  }
} else {
  http_response_code(404);
  $META = [
    'title'       => 'Post not found | Kanavu Heritage',
    'description' => 'The blog post could not be found.',
    'canonical'   => '/kanav/blog',
    'og_image'    => '/kanav/img/og-default.jpg'
  ];
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
  <meta property="og:type" content="article">

  <!-- ✅ Make all relative URLs resolve from /kanav/ -->


  <link rel="shortcut icon" href="img/favicon.png" />
  <!-- Keep these relative; <base> makes them load from /kanav/... -->
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="whatsapp_style.css" />
  <style>.post-hero img{width:100%;height:auto;display:block}</style>
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

  <section class="section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12">
        <?php if (!$post): ?>
          <h2 class="section-title2">Post not found</h2>
          <p><a href="blog" class="butn-dark"><span>Back to Blog</span></a></p>
        <?php else: ?>
          <div class="post-hero"><img src="<?= htmlspecialchars(blog_image_src($post['image'])) ?>" alt="<?= htmlspecialchars($post['title'] ?: 'Blog Image') ?>"></div>
          <h2 class="section-title2"><?= htmlspecialchars($post['title']) ?></h2>
          <p>
            <strong><?= htmlspecialchars($post['blog_type'] ?? 'Blog') ?></strong>
            <?php if (!empty($post['date'])): ?> | <?= date("F d, Y", strtotime($post['date'])) ?><?php endif; ?>
            <?php if (!empty($post['author_name'])): ?> | By <?= htmlspecialchars($post['author_name']) ?><?php endif; ?>
          </p>
          <div><?= nl2br(htmlspecialchars((string)$post['description'])) ?></div>
          <p style="margin-top:20px;"><a href="blog" class="butn-dark"><span>Back to Blog</span></a></p>
        <?php endif; ?>
      </div></div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>
<!-- Keep scripts relative; <base> fixes their paths on pretty URLs -->
<script src="js/jquery-3.6.3.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/custom.js"></script>
</body>
</html>
