<?php
// /kanav/faq.php  — dynamic FAQs with same layout as your HTML

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include __DIR__ . '/includes/connect.php';

function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// --- SEO defaults and optional override via includes/seo_meta.php ---
$META = [
  'title'       => 'Frequently Asked Questions – Kanavu Heritage Kerala',
  'description' => "Curious about what's included in your tariff, kitchen use, nearby amenities, or how to book private events? Get the details in our FAQ.",
  'canonical'   => '/kanav/faq',
  'og_image'    => '/kanav/img/og-default.jpg',
];
if (is_file(__DIR__ . '/includes/seo_meta.php')) {
  require_once __DIR__ . '/includes/seo_meta.php';
  if (function_exists('kh_build_meta')) {
    $META = kh_build_meta($conn, 'faq', $META['title'], $META['description'], $META['canonical']);
  }
}

// Load FAQs from DB (adjust column names if needed)
$faqs = [];
$stmt = $conn->prepare("
    SELECT id, question, answer
    FROM faqs
    WHERE is_active = 1
    ORDER BY sort ASC, id ASC
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $faqs[] = $row;
}
$stmt->close();

$total = count($faqs);
$leftCount = (int)ceil($total / 2);
$leftFaqs  = array_slice($faqs, 0, $leftCount);
$rightFaqs = array_slice($faqs, $leftCount);
?>
<!DOCTYPE html>
<html lang="zxx">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title><?= e($META['title']) ?></title>
    <meta name="description" content="<?= e($META['description']) ?>">

    <!-- Canonical Tag -->
    <link rel="canonical" href="<?= e($META['canonical']) ?>">
    <!-- Open Graph -->
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
    <script>
    (function(w,d,s,l,i){
      w[l]=w[l]||[];
      w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});
      var f=d.getElementsByTagName(s)[0],
          j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
      j.async=true;
      j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
      f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NP7DBK64');
    </script>
    <!-- End Google Tag Manager -->

    <style>
      @media screen and (max-width: 991px){
        .nav-scroll {
          background: #272727;
          height: 108px;
        }
      }
    </style>
  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
    <noscript>
      <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64"
              height="0" width="0"
              style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- WhatsApp Floating Button -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <a href="https://wa.link/3b9yly" class="float" target="_blank">
      <i class="fa fa-whatsapp my-float"></i>
    </a>

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

    <!-- Navbar (same as your HTML) -->
<?php include('header.php'); ?>


    <!-- Content -->
    <div class="content-wrapper">
      <!-- Lines -->
      <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
        <div class="content-lines-inner">
          <div class="content-lines"></div>
        </div>
      </section>

      <!-- Header Banner -->
      <section class="banner-header banner-img valign bg-img bg-fixed"
               data-overlay-darkgray="5"
               data-background="img/gallery/01.jpg"></section>

      <!-- FAQ Section -->
      <section class="section-padding2">
        <div class="container">
          <div class="row">
            <div class="col-md-12">
              <h2 class="section-title">
                <span>Frequently Asked Questions (FAQ)</span>
              </h2>
            </div>

            <?php if ($total > 0): ?>
              <!-- Left column -->
              <div class="col-md-6">
                <ul class="accordion-box clearfix">
                  <?php foreach ($leftFaqs as $i => $f): 
                        $num = $i + 1;
                  ?>
                    <li class="accordion block" id="faq<?= (int)$f['id'] ?>">
                      <div class="acc-btn">
                        <span class="count"><?= $num ?>.</span>
                        <?= e($f['question']) ?>
                      </div>
                      <div class="acc-content">
                        <div class="content">
                          <div class="text"><?= nl2br(e($f['answer'])) ?></div>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- Right column -->
              <div class="col-md-6">
                <ul class="accordion-box clearfix">
                  <?php foreach ($rightFaqs as $i => $f): 
                        $num = $leftCount + $i + 1;
                  ?>
                    <li class="accordion block" id="faq<?= (int)$f['id'] ?>">
                      <div class="acc-btn">
                        <span class="count"><?= $num ?>.</span>
                        <?= e($f['question']) ?>
                      </div>
                      <div class="acc-content">
                        <div class="content">
                          <div class="text"><?= nl2br(e($f['answer'])) ?></div>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <h4 style="line-height: 1.5; color: #63c889">
                Have more questions? Feel free to reach out to us for assistance and clarification.
                Your comfort and satisfaction are our top priorities.
              </h4>
            <?php else: ?>
              <div class="col-md-12">
                <p>No FAQs available at the moment. Please check back soon.</p>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </section>

      <!-- Footer (same as your HTML) -->
      <footer class="main-footer dark">
        <div class="sub-footer">
          <div class="container">
            <div class="row">
              <div class="col-md-12 abot">
                <div class="social-icon">
                  <a style="border:0;font-size:13px;font-weight:900;" href="index.php" target="_blank">Home</a>
                  <a style="border:0;width:107px;font-size:13px;font-weight:900;" href="celebrations.php" target="_blank">Celebrations</a>
                  <a style="border:0;width:107px;font-size:13px;font-weight:900;" href="terms&conditions.php" target="_blank">Terms& Conditions</a>
                  <a style="border:0;width:107px;font-size:13px;font-weight:900;" href="contact.php" target="_blank">Contact Us</a>
                  <a style="border:0;width:107px;font-size:13px;font-weight:900;" href="refund_policy.php" target="_blank">Refund Policy</a>
                  <a style="border:0;width:107px;font-size:13px;font-weight:900;" href="privacy_policy.php" target="_blank">Privacy Policy</a>
                </div>
                <div class="social-icon">
                  <a href="https://www.facebook.com/profile.php?id=61553078441435" target="_blank"><i class="ti-facebook"></i></a>
                  <a href="https://www.youtube.com/watch?v=cutLiSFgjKY" target="_blank"><i class="ti-youtube"></i></a>
                  <a href="https://www.instagram.com/kanavu.heritage/" target="_blank"><i class="ti-instagram"></i></a>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="text-left">
                  <p>© 2024 Kanavu Heritage. All rights reserved.</p>
                </div>
              </div>
              <div class="col-md-4 abot"></div>
              <div class="col-md-4">
                <p class="right">
                  Powered By <a href="https://cryoflametechnologies.com/">Cryoflame Technologies</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </footer>

    </div><!-- /.content-wrapper -->

    <!-- JS -->
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
