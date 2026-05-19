<?php
// /kanav/experiences.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$ROOT = __DIR__;
$INC  = $ROOT . '/includes';

// DB connect
if (is_file($INC.'/connect.php')) require_once $INC.'/connect.php';
else die("Missing DB file: /includes/connect.php");

// Ensure settings table
$conn->query("
  CREATE TABLE IF NOT EXISTS experiences_settings (
    `key`   VARCHAR(160) PRIMARY KEY,
    `value` LONGTEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// SEO helper
$seo_loaded=false;
if (is_file($INC.'/seo_meta.php')) { require_once $INC.'/seo_meta.php'; $seo_loaded=true; }
elseif (is_file($ROOT.'/dashboard/seo_meta.php')) { require_once $ROOT.'/dashboard/seo_meta.php'; $seo_loaded=true; }

if (!$seo_loaded) {
  function kh_base_url(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($proto.$host, '/');
  }
  function kh_abs_url(string $rel): string {
    if (preg_match('~^https?://~i', $rel)) return $rel;
    return kh_base_url() . '/' . ltrim($rel, '/');
  }
  function kh_build_meta(mysqli $conn, string $filename, string $fb_title='', string $fb_desc='', string $fb_canon=''): array {
    return [
      'title'       => $fb_title ?: 'Kanavu Heritage Experiences - Natural Pond, Sunsets & Walks',
      'description' => $fb_desc  ?: 'Enjoy a refreshing dip in a natural pond, tranquil nature walks through paddy fields, and stunning sunset views at Thattupara.',
      'canonical'   => kh_abs_url($fb_canon ?: '/experiences.php'),
      'og_image'    => kh_abs_url('/img/og-default.jpg'),
    ];
  }
}

// Canonical path
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($script), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
$CANON_PATH = $basePath . '/' . basename(__FILE__);

$slug = basename(__FILE__, '.php');
if ($slug === 'index') $slug = 'home';

$META = kh_build_meta(
  $conn,
  $slug,
  'Kanavu Heritage Experiences - Natural Pond, Sunsets & Walks',
  'Enjoy a refreshing dip in a natural pond, tranquil nature walks through paddy fields, and stunning sunset views at Thattupara.',
  $CANON_PATH
);

// helpers
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function get_setting(mysqli $conn, string $key, string $default = ''): string {
  $stmt = $conn->prepare("SELECT `value` FROM experiences_settings WHERE `key`=?");
  $stmt->bind_param('s', $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $default;
}

/* ---------- Dynamic Content ---------- */
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');
$page_h2 = get_setting($conn,'page_h2','LOCAL<span>DELIGHTS</span>');

// Block 1
$b1_img   = get_setting($conn,'b1_img','img/slider/s16.jpg');
$b1_title = get_setting($conn,'b1_title','MAROTTIKULAM WATERSCAPE');
$b1_p1    = get_setting($conn,'b1_p1',"While KANAVU itself does not feature a swimming pool, our guests have the delightful option to immerse themselves in the refreshing waters of the nearby 'Marottikulam' natural pond, providing a serene and natural aquatic experience");
$b1_link  = get_setting($conn,'b1_link','gallery.php');

// Image pair row (Thattupara)
$t_img1 = get_setting($conn,'t_img1','img/slider/Thattupara.jpeg');
$t_img2 = get_setting($conn,'t_img2','img/slider/Thattupara3.jpeg');
$t_link = get_setting($conn,'t_link','gallery.php');

// Block 2 (Sunset)
$s_img   = get_setting($conn,'s_img','img/slider/Thattupara4.jpeg');
$s_title = get_setting($conn,'s_title','Thattupara Sunset Wonderland');
$s_p1    = get_setting($conn,'s_p1',"Discover the hidden gem of Thattupara, a serene escape for nature enthusiasts. As the sun gracefully bids adieu, this elevated spot transforms into a canvas for breathtaking sunsets and peaceful evenings. Nestled in tranquility, Thattupara reveals a mesmerizing panorama of the western sky, making each sunset a treat for the eyes. Lush greenery and an ancient church add charm to this haven, inviting you to a peaceful retreat. Whether on a leisurely stroll or seeking introspection, Thattupara offers it all. Explore by vehicle or venture on a trek to this accessible, untouched haven. The west-facing view guarantees an uninterrupted spectacle of the setting sun, perfect for sharing memorable moments with family and friends from 5 pm to 6:30 pm. Thattupara, a hidden sunset extravaganza! Unwind, reconnect, and create lasting memories in nature's retreat.");
$s_link  = get_setting($conn,'s_link','gallery.php');

// Block 3 (Nature Walks)
$n_img   = get_setting($conn,'n_img','img/slider/Paddy_field.jpeg');
$n_title = get_setting($conn,'n_title','NATURE WALKS');
$n_p1    = get_setting($conn,'n_p1',"Embark on invigorating nature walks at Kanav Heritage and uncover the enchanting beauty of Manjapra, fondly known as the 'green village'. Surrounded by lush paddy fields stretching as far as the eye can see, Manjapra resembles a verdant oasis, akin to an emerald island when viewed from above. Its unique geography, with all four entry points leading through Paadam (cultivated paddy fields) and the gentle flow of the Thodu (natural stream of water), adds to its serene charm. As you wander through this picturesque landscape, you'll also encounter vibrant plantations, further enhancing the enchantment of your nature experience.");
$n_link  = get_setting($conn,'n_link','gallery.php');
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <title><?= e($META['title']) ?></title>
  <meta name="description" content="<?= e($META['description']) ?>">
  <link rel="canonical" href="<?= e($META['canonical']) ?>">

  <meta property="og:title" content="<?= e($META['title']) ?>">
  <meta property="og:description" content="<?= e($META['description']) ?>">
  <meta property="og:image" content="<?= e($META['og_image']) ?>">
  <meta property="og:url" content="<?= e($META['canonical']) ?>">
  <meta property="og:type" content="website">

  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="./whatsapp_style.css">

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1CM66TP69"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-Y1CM66TP69');
  </script>

  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-NP7DBK64');</script>
  <!-- End Google Tag Manager -->
</head>
<body>
<?php include('header.php');?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<div class="content-wrapper">
  <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
    <div class="content-lines-inner"><div class="content-lines"></div></div>
  </section>

  <section class="banner-header banner-img valign bg-img bg-fixed"
           data-overlay-darkgray="5"
           data-background="<?= e($hero_bg) ?>"></section>

  <section class="bauen-blog2 section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12">
        <h2 class="section-title text-center"><?= $page_h2 ?></h2>
      </div></div>

      <!-- Block 1 -->
      <div class="row mb-60">
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($b1_link) ?>"><img src="<?= e($b1_img) ?>" alt=""></a>
          </div>
        </div>
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title">
              <span style="font-size: 28px;letter-spacing: normal;"><?= e($b1_title) ?></span>
            </h4>
            <?php if(trim($b1_p1)!==''): ?><p><?= nl2br(e($b1_p1)) ?></p><?php endif; ?>
          </div></div>
        </div>
      </div>

      <!-- Thattupara image pair row -->
      <div class="row mb-60">
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($t_link) ?>"><img src="<?= e($t_img1) ?>" alt=""></a>
          </div>
        </div>
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($t_link) ?>"><img src="<?= e($t_img2) ?>" alt=""></a>
          </div>
        </div>
      </div>

      <!-- Sunset block -->
      <div class="row mb-60">
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title">
              <span style="font-size: 28px;letter-spacing: normal;"><?= e($s_title) ?></span>
            </h4>
            <?php if(trim($s_p1)!==''): ?><p style="font-size:18px;"><?= nl2br(e($s_p1)) ?></p><?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($s_link) ?>"><img src="<?= e($s_img) ?>" alt=""></a>
          </div>
        </div>
      </div>

      <!-- Nature walks -->
      <div class="row mb-60">
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($n_link) ?>"><img src="<?= e($n_img) ?>" alt=""></a>
          </div>
        </div>
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title">
              <span style="font-size: 28px;letter-spacing: normal;"><?= e($n_title) ?></span>
            </h4>
            <?php if(trim($n_p1)!==''): ?><p><?= nl2br(e($n_p1)) ?></p><?php endif; ?>
          </div></div>
        </div>
      </div>

    </div>
  </section>

  <?php include('footer.php'); ?>
</div>

<script src="js/jquery-3.6.3.min.js"></script>
<script src="js/jquery-migrate-3.0.0.min.js"></script>
<script src="js/modernizr-2.6.2.min.js"></script>
<script src="js/imagesloaded.pkgd.min.js"></script>
<script src="js/jquery.isotope.v3.0.2.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/scrollIt.min.js"></script>
<script src="js/jquery.waypoints.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.stellar.min.js"></script>
<script src="js/jquery.magnific-popup.js"></script>
<script src="js/custom.js"></script>
</body>
</html>
