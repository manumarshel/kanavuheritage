<?php
// /kanav/accommodation.php  (public page)
// Turn on errors while fixing; disable later
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


$ROOT = __DIR__;
$INC = $ROOT . '/includes';

// DB connect
if (is_file($INC.'/connect.php')) require_once $INC.'/connect.php'; else die("Missing DB file: /includes/connect.php");

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
      'title'       => $fb_title ?: 'Kanavu Heritage | Best Heritage Home In Kerala',
      'description' => $fb_desc  ?: 'Experience a serene heritage homestay in Kerala.',
      'canonical'   => kh_abs_url('/kanav/'),  // ensure trailing slash
      'og_image'    => kh_abs_url('/kanav/img/og-default.jpg'),
    ];
  }
}

$script = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($script), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
$CANON_PATH = $basePath . '/' . basename(__FILE__);   // keeps correct path

$slug = basename(__FILE__, '.php');
if ($slug === 'index') $slug = 'home';
$META = kh_build_meta($conn, $slug, '', '', $CANON_PATH);

// helpers
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function get_setting(mysqli $conn, string $key, string $default = ''): string {
  $stmt = $conn->prepare("SELECT `value` FROM accom_settings WHERE `key`=?");
  $stmt->bind_param('s', $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $default;
}

// Load dynamic values (fallbacks preserve your current content)
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');

// S1
$s1_title = get_setting($conn,'s1_title','ENCHANTING VINTAGE VERANDAH');
$s1_p1    = get_setting($conn,'s1_p1',
  "Step onto the verandah at KANAVU and be transported into the embrace of traditional Kerala architecture."
);
$s1_p2    = get_setting($conn,'s1_p2',
  "The verandah reveals a captivating blend of Moroccan tiles, wooden pillars, laterite claddings and hanging lights casting a spell of timeless vintage charm."
);
$s1_img   = get_setting($conn,'s1_img','img/slider/s1.jpg');

// S2
$s2_p1    = get_setting($conn,'s2_p1',
  "Whether hosting semi-indoor dining or indulging in a serene tea/coffee experience with a garden view, the verandah invites you to shape moments."
);
$s2_p2    = get_setting($conn,'s2_p2',
  "Enhanced by the cultural richness of charu kasera and thookku vilakku, this space transcends time."
);
$s2_img   = get_setting($conn,'s2_img','img/slider/8.jpg');

// S3
$s3_title = get_setting($conn,'s3_title','LUXE BED ROOMS');
$s3_p1    = get_setting($conn,'s3_p1',
  "Discover the perfect synthesis of comfort and luxury at our premium home stay featuring two elegantly curated bedrooms."
);
$s3_p2    = get_setting($conn,'s3_p2',
  "Each room features premium bath amenities and a blend of heritage and western influences."
);
$s3_img   = get_setting($conn,'s3_img','img/slider/s3.jpg');

// S4
$s4_p1    = get_setting($conn,'s4_p1',
  "Each room is equipped with a study table, a 4-shutter wardrobe, a full-length mirror, a secure locker, and a tea/coffee station."
);
$s4_p2    = get_setting($conn,'s4_p2',
  "All wooden furnishings are crafted from solid teak wood."
);
$s4_img   = get_setting($conn,'s4_img','img/slider/s4.jpg');

// S5
$s5_title = get_setting($conn,'s5_title','GRACEFUL LIVING SPACE');
$s5_p1    = get_setting($conn,'s5_p1',
  "Indulge in comfort in our meticulously designed living room with ample seating."
);
$s5_p2    = get_setting($conn,'s5_p2',
  "A smart TV with OTT access and a beautiful bay window view of the lush lawn."
);
$s5_img   = get_setting($conn,'s5_img','img/slider/s5.jpg');

// S6
$s6_p1    = get_setting($conn,'s6_p1',
  "A curated reading nook beckons book enthusiasts with bestsellers in English and Malayalam."
);
$s6_p2    = get_setting($conn,'s6_p2','');
$s6_img   = get_setting($conn,'s6_img','img/slider/s6.jpg');

// S7
$s7_p1    = get_setting($conn,'s7_p1',
  "While the walls are in a calming palette of white, a vibrant acrylic painting of Kandanar Kelan Theyyam takes center stage."
);
$s7_p2    = get_setting($conn,'s7_p2',
  "Every detail ensures your stay is not just a visit but a memorable experience."
);
$s7_img   = get_setting($conn,'s7_img','img/slider/s7.jpg');

// S8
$s8_title = get_setting($conn,'s8_title','SOPHISTICATED CULINARY RETREAT');
$s8_p1    = get_setting($conn,'s8_p1',
  "Explore the functionality of our fully equipped kitchen: gas connection, stove, fridge, oven, mixer grinder, cookware and cutlery."
);
$s8_p2    = get_setting($conn,'s8_p2',
  "Optional kitchen access available (additional charges apply)."
);
$s8_img   = get_setting($conn,'s8_img','img/slider/s8.jpg');

// Meta (fix encoding: replace stray special chars)
$meta_desc = "Explore our heritage-style accommodation at Kanavu Heritage — featuring vintage verandah, luxe bedrooms, cozy living space, and a fully equipped kitchen in Kerala.";
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

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($META['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($META['description']) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($META['og_image']) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($META['canonical']) ?>">
  <meta property="og:type" content="website">

  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="whatsapp_style.css" />

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

  <style>
    @media screen and (max-width: 991px){
      .nav-scroll { background:#272727; height:108px; }
    }
    .section-title { letter-spacing: normal; }
  </style>
</head>
<body>
  <?php include('header.php');?>
  <!-- WhatsApp -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

  <!-- Preloader -->
  <!-- <div class="preloader-bg"></div>
  <div id="preloader"><div id="preloader-status"><div class="preloader-position loader"><span></span></div></div></div> -->

  <!-- Progress scroll totop -->
  <!-- <div class="progress-wrap cursor-pointer">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
      <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
    </svg>
  </div> -->

  <!-- Navbar (kept as your original) -->

  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <!-- Content -->
  <div class="content-wrapper">
    <!-- Lines -->
    <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
      <div class="content-lines-inner"><div class="content-lines"></div></div>
    </section>

    <!-- Header Banner (dynamic) -->
    <section class="banner-header banner-img valign bg-img bg-fixed"
             data-overlay-darkgray="5"
             data-background="<?= e($hero_bg) ?>"></section>

    <!-- Services -->
    <section class="bauen-blog2 section-padding2">
      <div class="container">

        <div class="row">
          <div class="col-md-12">
            <h2 class="section-title">WHAT WE <span>OFFER</span></h2>
          </div>
        </div>

        <!-- S1: Image Left / Text Right -->
        <div class="row mb-60">
          <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
            <div class="img left"><a href="#"><img src="<?= e($s1_img) ?>" alt=""></a></div>
          </div>
          <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
            <div class="content"><div class="cont">
              <h4 class="section-title"><span style="font-size:28px;letter-spacing:normal;"><?= e($s1_title) ?></span></h4>
              <?php if($s1_p1): ?><p><?= nl2br(e($s1_p1)) ?></p><?php endif; ?>
              <?php if($s1_p2): ?><p><?= nl2br(e($s1_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
        </div>

        <!-- S2: Text Left / Image Right -->
        <div class="row mb-60">
          <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
            <div class="content"><div class="cont">
              <?php if($s2_p1): ?><p><?= nl2br(e($s2_p1)) ?></p><?php endif; ?>
              <?php if($s2_p2): ?><p><?= nl2br(e($s2_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
          <div class="col-md-6 order1 animate-box" data-animate-effect="fadeInRight">
            <div class="img"><a href="gallery.php"><img src="<?= e($s2_img) ?>" alt=""></a></div>
          </div>
        </div>

        <!-- S3: Image Left / Text Right -->
        <div class="row mb-60">
          <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
            <div class="img left"><a href="gallery.php"><img src="<?= e($s3_img) ?>" alt=""></a></div>
          </div>
          <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
            <div class="content"><div class="cont">
              <h4 class="section-title"><span style="font-size:28px;letter-spacing:normal;"><?= e($s3_title) ?></span></h4>
              <?php if($s3_p1): ?><p><?= nl2br(e($s3_p1)) ?></p><?php endif; ?>
              <?php if($s3_p2): ?><p><?= nl2br(e($s3_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
        </div>

        <!-- S4: Text Left / Image Right -->
        <div class="row mb-60">
          <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
            <div class="content"><div class="cont">
              <?php if($s4_p1): ?><p><?= nl2br(e($s4_p1)) ?></p><?php endif; ?>
              <?php if($s4_p2): ?><p><?= nl2br(e($s4_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
          <div class="col-lg-6 order1 animate-box" data-animate-effect="fadeInRight">
            <div class="img"><a href="gallery.php"><img src="<?= e($s4_img) ?>" alt=""></a></div>
          </div>
        </div>

        <!-- S5: Image Left / Text Right -->
        <div class="row mb-60">
          <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
            <div class="img left"><a href="gallery.php"><img src="<?= e($s5_img) ?>" alt=""></a></div>
          </div>
          <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
            <div class="content"><div class="cont">
              <h4 class="section-title"><span style="font-size:28px;letter-spacing:normal;"><?= e($s5_title) ?></span></h4>
              <?php if($s5_p1): ?><p><?= nl2br(e($s5_p1)) ?></p><?php endif; ?>
              <?php if($s5_p2): ?><p><?= nl2br(e($s5_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
        </div>

        <!-- S6: Text Left / Image Right -->
        <div class="row mb-60">
          <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
            <div class="content"><div class="cont">
              <?php if($s6_p1): ?><p><?= nl2br(e($s6_p1)) ?></p><?php endif; ?>
              <?php if($s6_p2): ?><p><?= nl2br(e($s6_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
          <div class="col-lg-6 order1 animate-box" data-animate-effect="fadeInRight">
            <div class="img"><a href="gallery.php"><img src="<?= e($s6_img) ?>" alt=""></a></div>
          </div>
        </div>

        <!-- S7: Image Left / Text Right -->
        <div class="row mb-60">
          <div class="col-md-6 animate-box" data-animate-effect="fadeInLeft">
            <div class="img left"><a href="gallery.php"><img src="<?= e($s7_img) ?>" alt=""></a></div>
          </div>
          <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
            <div class="content"><div class="cont">
              <?php if($s7_p1): ?><p><?= nl2br(e($s7_p1)) ?></p><?php endif; ?>
              <?php if($s7_p2): ?><p><?= nl2br(e($s7_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
        </div>

        <!-- S8: Text Left / Image Right -->
        <div class="row mb-60">
          <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
            <div class="content"><div class="cont">
              <h4 class="section-title"><span style="font-size:28px;letter-spacing:normal;"><?= e($s8_title) ?></span></h4>
              <?php if($s8_p1): ?><p><?= nl2br(e($s8_p1)) ?></p><?php endif; ?>
              <?php if($s8_p2): ?><p><?= nl2br(e($s8_p2)) ?></p><?php endif; ?>
            </div></div>
          </div>
          <div class="col-lg-6 order1 animate-box" data-animate-effect="fadeInRight">
            <div class="img"><a href="gallery.php"><img src="<?= e($s8_img) ?>" alt=""></a></div>
          </div>
        </div>

      </div>
    </section>

      <!-- Footer --> <?php include('footer.php');?>

  </div>

  <!-- Scripts -->
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