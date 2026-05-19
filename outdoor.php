<?php
// /kanav/outdoor.php
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
  CREATE TABLE IF NOT EXISTS outdoor_settings (
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
      'title'       => $fb_title ?: 'Outdoor Spaces at Kanavu Heritage',
      'description' => $fb_desc  ?: 'Enjoy the peaceful outdoor spaces at Kanavu Heritage with courtyards, gardens, hammocks, and a beautiful lawn for relaxing moments.',
      'canonical'   => kh_abs_url($fb_canon ?: '/outdoor.php'),
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
  'Outdoor Spaces at Kanavu Heritage',
  'Enjoy the peaceful outdoor spaces at Kanavu Heritage with courtyards, gardens, hammocks, and a beautiful lawn for relaxing moments.',
  $CANON_PATH
);

// helpers
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function get_setting(mysqli $conn, string $key, string $default = ''): string {
  $stmt = $conn->prepare("SELECT `value` FROM outdoor_settings WHERE `key`=?");
  $stmt->bind_param('s', $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $default;
}

/* ---------- Dynamic Content ---------- */
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');
$page_h2 = get_setting($conn,'page_h2','SERENE OUTDOOR <span>SPACES</span>');

// S1
$s1_img   = get_setting($conn,'s1_img','img/slider/s13.jpg');
$s1_title = get_setting($conn,'s1_title','THULASI THARA & KALVILAKKU');
$s1_p1    = get_setting($conn,'s1_p1','Discover the enchanting allure of our Thulasithara and Kalvilakku gracing the courtyard. The Thulasithara - adorned with auspicious thulasi leaves and the majestic Kalvilakku - a graceful lamp harmonize to create a cultural luminance.');
$s1_p2    = get_setting($conn,'s1_p2','These luminous beacons, steeped in tradition, bring a sense of sacred warmth to the surroundings, creating an atmosphere of cultural embrace and timeless grace.');
$s1_link  = get_setting($conn,'s1_link','gallery.php');

// S2
$s2_img   = get_setting($conn,'s2_img','img/slider/s12.jpg');
$s2_title = get_setting($conn,'s2_title',"KANAVU'S SIGNATURE PHOTO SPOT");
$s2_p1    = get_setting($conn,'s2_p1','Our courtyard reveals a captivating tableau, where the focal point is a Churulan Vallam – brimming with seasonal blooms, also features a name board of KANAVU .');
$s2_p2    = get_setting($conn,'s2_p2',"This spot isn't just a corner; it's a memory waiting to be captured. Pause, photograph, and make your visit timeless with the beauty of our curated courtyard.");
$s2_link  = get_setting($conn,'s2_link','gallery.php');

// S3
$s3_img   = get_setting($conn,'s3_img','img/slider/s9.jpg');
$s3_title = get_setting($conn,'s3_title','GAZEBO');
$s3_p1    = get_setting($conn,'s3_p1','Whether you choose to enjoy a leisurely afternoon tea, engage in intimate conversations with loved ones, or simply relish the solitude with a good book, our gazebo offers a space where every moment becomes a cherished memory.');
$s3_p2    = get_setting($conn,'s3_p2',"Surrounded by the lush beauty of our heritage estate, the gazebo beckons you to unwind in its graceful ambiance. As the gentle breeze rustles through the surrounding greenery, you'll find yourself enveloped in a sense of peace and seclusion.");
$s3_link  = get_setting($conn,'s3_link','gallery.php');

// S4
$s4_img   = get_setting($conn,'s4_img','img/slider/s10.jpg');
$s4_title = get_setting($conn,'s4_title','HAMMOCK');
$s4_p1    = get_setting($conn,'s4_p1',"Suspended between nature's embrace, the hammock offers a tranquil escape nestled amidst swaying trees. As you recline in its cradle, you'll find yourself gently swaying with the rhythm of nature, a soothing lullaby for the soul.");
$s4_p2    = get_setting($conn,'s4_p2','The floor beneath is paved with smooth river pebbles, meticulously arranged to ensure cleanliness even during the rainy seasons. Here, the earthy touch of nature meets thoughtful design, creating a harmonious space where relaxation knows no bounds.');
$s4_p3    = get_setting($conn,'s4_p3','Gaze up through the rustling leaves, let the soft breeze kiss your skin, and allow the hammock to cradle you into a state of bliss.');
$s4_link  = get_setting($conn,'s4_link','#');

// S5
$s5_img   = get_setting($conn,'s5_img','img/slider/s11.jpg');
$s5_title = get_setting($conn,'s5_title','ZEN GARDEN');
$s5_sub   = get_setting($conn,'s5_sub','Feel the Zen, capture the magic!');
$s5_p1    = get_setting($conn,'s5_p1','Lush with a harmonious blend of flowering and non-flowering plants, it cradles a Thamakarakkulam, featuring the exquisite Sahasradala Padmam (thousand-petal lotus).');
$s5_p2    = get_setting($conn,'s5_p2','An awe-inspiring Buddha head, meticulously carved from a single stone, presides over the space, radiating a sense of peace. As the sun sets, the garden transforms into a magical realm, illuminated by enchanting pole lights that weave a magical aura.');
$s5_p3    = get_setting($conn,'s5_p3',"This oasis isn't just for relaxation; it's a canvas for captivating photoshoots.");
$s5_link  = get_setting($conn,'s5_link','gallery.php');

// S6
$s6_img   = get_setting($conn,'s6_img','img/gallery/10.jpg');
$s6_title = get_setting($conn,'s6_title','LAWN');
$s6_p1    = get_setting($conn,'s6_p1',"Experience the enchantment of our luminous lawn, elegantly\nadorned with vibrant pearl grass and a captivating interplay\nof pole lights and string lights, providing a celestial retreat for\nserene evenings and joyous celebrations.");
$s6_link  = get_setting($conn,'s6_link','gallery.php');
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
      <div class="row">
        <div class="col-md-12">
          <h2 class="section-title" style="text-align:center"><?= $page_h2 ?></h2>
        </div>
      </div>

      <!-- S1 image left -->
      <div class="row mb-60">
        <div class="col-lg-6 order1 animate-box" data-animate-effect="fadeInRight">
          <div class="img">
            <a href="<?= e($s1_link) ?>"><img src="<?= e($s1_img) ?>" alt=""></a>
          </div>
        </div>
        <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s1_title) ?></span></h4>
            <?php if($s1_p1): ?><p><?= nl2br(e($s1_p1)) ?></p><?php endif; ?>
            <?php if($s1_p2): ?><p><?= nl2br(e($s1_p2)) ?></p><?php endif; ?>
          </div></div>
        </div>
      </div>

      <!-- S2 text left / image right -->
      <div class="row mb-60">
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s2_title) ?></span></h4>
            <?php if($s2_p1): ?><p><?= nl2br(e($s2_p1)) ?></p><?php endif; ?>
            <?php if($s2_p2): ?><p><?= nl2br(e($s2_p2)) ?></p><?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left">
            <a href="<?= e($s2_link) ?>"><img src="<?= e($s2_img) ?>" alt=""></a>
          </div>
        </div>
      </div>

      <!-- S3 image left -->
      <div class="row mb-60">
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left"><a href="<?= e($s3_link) ?>"><img src="<?= e($s3_img) ?>" alt=""></a></div>
        </div>
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s3_title) ?></span></h4>
            <?php if($s3_p1): ?><p><?= nl2br(e($s3_p1)) ?></p><?php endif; ?>
            <?php if($s3_p2): ?><p><?= nl2br(e($s3_p2)) ?></p><?php endif; ?>
          </div></div>
        </div>
      </div>

      <!-- S4 text left / image right -->
      <div class="row mb-60">
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s4_title) ?></span></h4>
            <?php if($s4_p1): ?><p><?= nl2br(e($s4_p1)) ?></p><?php endif; ?>
            <?php if($s4_p2): ?><p><?= nl2br(e($s4_p2)) ?></p><?php endif; ?>
            <?php if($s4_p3): ?><p><?= nl2br(e($s4_p3)) ?></p><?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
          <div class="img left"><a href="<?= e($s4_link) ?>"><img src="<?= e($s4_img) ?>" alt=""></a></div>
        </div>
      </div>

      <!-- S5 image left -->
      <div class="row mb-60">
        <div class="col-md-6 order1 animate-box" data-animate-effect="fadeInRight">
          <div class="img"><a href="<?= e($s5_link) ?>"><img src="<?= e($s5_img) ?>" alt=""></a></div>
        </div>
        <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s5_title) ?></span></h4>
            <?php if(trim($s5_sub)!==''): ?><h5><?= e($s5_sub) ?></h5><?php endif; ?>
            <?php if($s5_p1): ?><p><?= nl2br(e($s5_p1)) ?></p><?php endif; ?>
            <?php if($s5_p2): ?><p><?= nl2br(e($s5_p2)) ?></p><?php endif; ?>
            <?php if($s5_p3): ?><p><?= nl2br(e($s5_p3)) ?></p><?php endif; ?>
          </div></div>
        </div>
      </div>

      <!-- S6 text left / image right -->
      <div class="row mb-60">
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInLeft">
          <div class="content"><div class="cont">
            <h4 class="section-title"><span><?= e($s6_title) ?></span></h4>
            <?php if($s6_p1): ?><p><?= nl2br(e($s6_p1)) ?></p><?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6 order1 animate-box" data-animate-effect="fadeInRight">
          <div class="img"><a href="<?= e($s6_link) ?>"><img src="<?= e($s6_img) ?>" alt=""></a></div>
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
