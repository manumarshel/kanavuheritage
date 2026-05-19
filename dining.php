<?php
// /kanav/dining.php — keep layout static, content from DB

$ROOT = __DIR__;
$INC  = $ROOT . '/includes';

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

/* ---------- Helpers ---------- */
function get_setting($conn, string $key, string $default = ''): string {
    $stmt = $conn->prepare("SELECT `value` FROM dining_settings WHERE `key`=?");
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? (string)$row['value'] : $default;
}

function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------- Load dynamic content ---------- */
$heading = get_setting($conn, 'heading', 'DINING');

$intro = get_setting(
    $conn,
    'intro_text',
    "At Kanavu Heritage, we invite you to experience a culinary journey that tantalizes your taste buds and adds a touch of indulgence to your stay. Our dining options
are designed to offer a delightful blend of freedom and personalization. Immerse yourself in a world where every bite tells a story and every meal is a celebration!"
);

$b1_title = get_setting($conn, 'b1_title', 'Morning Bliss : Begin Your Day with Our Complimentary Offerings');
$b1_text  = get_setting(
    $conn,
    'b1_text',
    'Awaken your day with the enticing aromas of a complimentary breakfast,
a symphony of flavors carefully crafted to kickstart your morning in the
most delightful way.'
);

$b2_title = get_setting($conn, 'b2_title', 'Custom Cuisine : Tailored Dining at Your Fingertips');
$b2_text  = get_setting(
    $conn,
    'b2_text',
    'For those seeking an elevated dining experience, our in-house chef is at
your service. Indulge in personalized culinary creations, where each dish is
a work of art.
Please note that additional charges apply for this exclusive service, and we
kindly request advance notice to ensure an exceptional dining experience
tailored to your preferences.'
);

/* ---------- Menus M1..M4 (title, sub, note, button) ---------- */
$menus = [];
for ($i = 1; $i <= 4; $i++) {
    $menus[$i] = [
        'title'    => get_setting($conn, "m{$i}_title", ''),              // accordion heading
        'sub'      => get_setting($conn, "m{$i}_sub", ''),                // green subtitle (what you edit in dashboard)
        'note'     => get_setting($conn, "m{$i}_note", ''),               // text under *** PLEASE NOTE ***
        'btn_text' => get_setting($conn, "m{$i}_btn_text", 'Download Menu'),
        'btn_href' => get_setting($conn, "m{$i}_btn_href", ''),           // PDF URL
    ];
}

/* ---------- Terms row ---------- */
$terms_note    = get_setting(
    $conn,
    'terms_note',
    '- Kindly take a moment to review to our terms and conditions before making a reservation to ensure a seamless experience. Your cooperation is valued - '
);
$terms_link    = get_setting($conn, 'terms_link', 'terms&conditions.php');
$terms_btn_txt = get_setting($conn, 'terms_btn_text', 'Terms &amp; Conditions');

?>
<!DOCTYPE html>
<html lang="zxx">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

    <!-- SEO -->
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
    <script>
      (function(w,d,s,l,i){
        w[l]=w[l]||[];
        w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;
        j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
        f.parentNode.insertBefore(j,f);
      })(window,document,'script','dataLayer','GTM-NP7DBK64');
    </script>
    <!-- End Google Tag Manager -->

  </head>
  <body>
    <!-- partial:index.partial.html -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <a href="https://wa.link/3b9yly" class="float" target="_blank">
      <i class="fa fa-whatsapp my-float"></i>
    </a>
    <style>
      @media screen and (max-width: 991px){
        .nav-scroll {
          background: #272727;
          height: 108px;
        }
      }
    </style>
    <!-- partial -->

    <!-- Preloader -->
    <!-- <div class="preloader-bg"></div>
    <div id="preloader">
      <div id="preloader-status">
        <div class="preloader-position loader"><span></span></div>
      </div>
    </div>
    <div class="progress-wrap cursor-pointer">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div> -->

    <!-- Navbar -->
<?php include('header.php'); ?>


    <!-- Google Tag Manager (noscript) -->
    <noscript>
      <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64"
              height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Content -->
    <div class="content-wrapper">
      <!-- Lines -->
      <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
        <div class="content-lines-inner">
          <div class="content-lines"></div>
        </div>
      </section>
      <!-- Header Banner -->
      <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg"></section>

      <!-- Dining Section -->
      <section id="services" class="section-padding2 services section-padding">
        <div class="container">
          <div class="row">
            <div class="col-md-12">
                <h2 class="section-title text-center"><?php echo e($heading); ?></h2>
            </div>
          </div>

          <div class="row">
            <p style="font-size:18px;">
              <b><?php echo nl2br(e($intro)); ?></b>
            </p>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="item">
                <?php if ($b1_title): ?>
                  <h5><?php echo e($b1_title); ?></h5>
                <?php endif; ?>
                <div class="line"></div>
                <?php if ($b1_text): ?>
                  <p><?php echo nl2br(e($b1_text)); ?></p>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="item">
                <?php if ($b2_title): ?>
                  <h5><?php echo e($b2_title); ?></h5>
                <?php endif; ?>
                <div class="line"></div>
                <?php if ($b2_text): ?>
                  <p><?php echo nl2br(e($b2_text)); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- FOOD MENU -->
          <div class="row">
            <div class="col-md-12">
              <h2 class="section-title text-center">
                <span><span>FOOD MENU</span></span>
              </h2>
            </div>
            <div class="col-md-12">
              <ul class="accordion-box clearfix">
                <?php
                // M1..M4 in order to preserve layout
                for ($i = 1; $i <= 4; $i++):
                  $m = $menus[$i];
                  if (!trim($m['title'])) continue;
                ?>
                  <li class="accordion block">
                    <div class="acc-btn">
                      <h2 class="section-title2 text-center">
                        <span><?php echo e($m['title']); ?></span>
                      </h2>
                    </div>
                    <div class="acc-content">
                      <div class="content">
                        <div class="row">
                          <div class="col-md-12">
                            <div class="pricing-card">
                              <!-- Green subtitle (from dashboard m{i}_sub; fallback to title if empty) -->
                              <h3 style="text-align: center;">
                                <b style="line-height: 1.5; color: #63c889">
                                  <?php echo e($m['sub'] !== '' ? $m['sub'] : $m['title']); ?>
                                </b>
                              </h3>

                              <?php if (trim($m['note']) !== ''): ?>
                                <p class="pricing-card-name text-center">*** PLEASE NOTE ***</p>
                              <?php endif; ?>

                              <div class="pricing-card-bottom">
                                <?php if (trim($m['note']) !== ''): ?>
                                  <p><?php echo nl2br(e($m['note'])); ?></p>
                                <?php endif; ?>

                                <?php if (trim($m['btn_href']) !== ''): ?>
                                  <div class="butn-pricing">
                                    <a href="<?php echo e($m['btn_href']); ?>" target="_blank">
                                      <span><?php echo e($m['btn_text']); ?></span>
                                    </a>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                <?php endfor; ?>
              </ul>

              <?php if (trim($terms_note) !== ''): ?>
                <p class="pricing-card-name">
                  <?php echo e($terms_note); ?>
                </p>
              <?php endif; ?>
              <?php if (trim($terms_link) !== ''): ?>
                <div class="butn-pricing">
                  <a href="<?php echo e($terms_link); ?>">
                    <span><?php echo e($terms_btn_txt); ?></span>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer class="main-footer dark">
        <div class="sub-footer">
          <div class="container">
            <div class="row">
              <div class="col-md-12 abot">
                <div class="social-icon">
                  <a style="border: 0px solid;font-size: 13px;font-weight: 900;" href="index.php"  target="_blank">Home</a>
                  <a style="border: 0px solid;width: 107px;font-size: 13px;font-weight: 900;" href="celebrations.php"  target="_blank">Celebrations</a>
                  <a style="border: 0px solid;width: 107px;font-size: 13px;font-weight: 900;" href="terms&conditions.php"  target="_blank"> Terms& Conditions</a>
                  <a style="border: 0px solid;width: 107px;font-size: 13px;font-weight: 900;" href="contact.php"  target="_blank">Contact Us</a>
                  <a style="border: 0px solid;width: 107px;font-size: 13px;font-weight: 900;" href="refund_policy.php"  target="_blank">Refund Policy</a>
                  <a style="border: 0px solid;width: 107px;font-size: 13px;font-weight: 900;" href="privacy_policy.php"  target="_blank">Privacy Policy</a>
                </div>
                <div class="social-icon">
                  <a href="https://www.facebook.com/profile.php?id=61553078441435"  target="_blank"><i class="ti-facebook"></i></a>
                  <a href="https://www.youtube.com/watch?v=cutLiSFgjKY"  target="_blank"><i class="ti-youtube"></i></a>
                  <a href="https://www.instagram.com/kanavu.heritage/"  target="_blank"><i class="ti-instagram"></i></a>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="text-left">
                  <p>©  2024 Kanavu Heritage. All rights reserved.</p>
                </div>
              </div>
              <div class="col-md-4 abot"></div>
              <div class="col-md-4">
                <p class="right">Powered By <a href="https://cryoflametechnologies.com/">Cryoflame Technologies</a></p>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>

    <!-- jQuery -->
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
