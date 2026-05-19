<?php
// /kanav/packages.php — safe includes + SEO meta build (same pattern as index.php)

$ROOT = __DIR__;
$INC  = $ROOT . '/includes';

// 1) DB connect (include ONCE)
if (is_file($INC . '/connect.php')) {
  require_once $INC . '/connect.php';   // sets $conn (mysqli)
} else {
  die("Missing DB file: /includes/connect.php");
}

/* Ensure table */
$conn->query("
  CREATE TABLE IF NOT EXISTS packages_settings (
    `key`   VARCHAR(160) PRIMARY KEY,
    `value` LONGTEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ---------------------- Helpers ---------------------- */
function getv($conn, $key, $def=''){
  $stmt = $conn->prepare("SELECT `value` FROM packages_settings WHERE `key`=?");
  $stmt->bind_param('s',$key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? $row['value'] : $def;
}
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* lines_to_li() with icon support */
function lines_to_li($txt, $iconClass=''){
  $out = '';
  foreach (preg_split('/\r\n|\r|\n/',$txt ?? '') as $line){
    $line = trim($line);
    if ($line!==''){
      $icon = $iconClass ? '<i class="'.e($iconClass).'"></i> ' : '';
      $out .= '<li>'.$icon.e($line).'</li>';
    }
  }
  return $out;
}

/* GST formatter */
function render_tariff_small_gst(string $txt): string {
  $safe = htmlspecialchars($txt ?? '', ENT_QUOTES, 'UTF-8');
  return preg_replace(
    '/(G\s*S\s*T\s*\(?\s*\d{1,2}\s*%?\s*\)?)/i',
    '<span class="gst-small">$1</span>',
    $safe
  );
}

/* 2) Load SEO helper (prefer /includes; fallback to dashboard) */
$seo_loaded = false;
if (is_file($INC . '/seo_meta.php')) {
  require_once $INC . '/seo_meta.php';
  $seo_loaded = true;
} elseif (is_file($ROOT . '/dashboard/seo_meta.php')) {
  require_once $ROOT . '/dashboard/seo_meta.php';
  $seo_loaded = true;
}

/* Detect base path for correct canonical on both:
   - localhost (/kanav/packages.php)
   - live domain root (/packages.php)
*/
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($script), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
$CANON_PATH = $basePath . '/packages.php';

/* 3) If helper missing, tiny fallback (same as index, but basePath-aware) */
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
    // If fb_canon is a path like "/kanav/packages.php" it becomes absolute.
    return [
      'title'       => $fb_title ?: 'Kanavu Heritage | Stay Packages & Photo-Shoot Offers',
      'description' => $fb_desc  ?: 'Explore flexible packages at Kanavu Heritage: from overnight family stays to full-day photo shoots and long-stay options—all with welcome drinks, Wi-Fi, games, and more.',
      'canonical'   => kh_abs_url($fb_canon ?: '/packages.php'),
      'og_image'    => kh_abs_url('/img/og-default.jpg'),
    ];
  }
}

/* 4) Build SEO Meta (IMPORTANT: fixed slug = "packages" NOT basename(__FILE__)) */
$META = kh_build_meta(
  $conn,
  'packages',
  'Kanavu Heritage | Stay Packages & Photo-Shoot Offers',
  'Explore flexible packages at Kanavu Heritage: from overnight family stays to full-day photo shoots and long-stay options—all with welcome drinks, Wi-Fi, games, and more.',
  $CANON_PATH
);

/* Dynamic content */
$hero_bg   = getv($conn,'hero_bg','img/gallery/01.jpg');
$intro_h2  = getv($conn,'intro_h2','TARIFF');
$intro_txt = getv($conn,'intro_text',
  "Explore exclusive packages tailored for a truly memorable stay at Kanavu Heritage.\nFrom overnight stays to special events, we offer diverse options to suit your preference.\nEach package ensures a unique and delightful experience."
);

for ($i=1;$i<=5;$i++){
  ${"p{$i}_title"}     = getv($conn,"p{$i}_title",'');
  ${"p{$i}_subtitle"}  = getv($conn,"p{$i}_subtitle",'');
  ${"p{$i}_occupancy"} = getv($conn,"p{$i}_occupancy",'');
  ${"p{$i}_tariff"}    = getv($conn,"p{$i}_tariff",'');
  ${"p{$i}_bullets"}   = getv($conn,"p{$i}_bullets",'');
  ${"p{$i}_reminders"} = getv($conn,"p{$i}_reminders",'');
  ${"p{$i}_cta"}       = getv($conn,"p{$i}_cta_link",'https://wa.link/3b9yly');
  ${"p{$i}_terms"}     = getv($conn,"p{$i}_terms_link",'terms&conditions.php');
}

/* P5 table rows */
$p5_r1_label = getv($conn,'p5_row1_label','');
$p5_r1_time  = getv($conn,'p5_row1_time','');
$p5_r1_tar   = getv($conn,'p5_row1_tariff','');

$p5_r2_label = getv($conn,'p5_row2_label','');
$p5_r2_time  = getv($conn,'p5_row2_time','');
$p5_r2_tar   = getv($conn,'p5_row2_tariff','');

$p5_r3_label = getv($conn,'p5_row3_label','');
$p5_r3_time  = getv($conn,'p5_row3_time','');
$p5_r3_tar   = getv($conn,'p5_row3_tariff','');
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- SEO -->
  <title><?= e($META['title']) ?></title>
  <meta name="description" content="<?= e($META['description']) ?>">
  <link rel="canonical" href="<?= e($META['canonical']) ?>">
  <meta property="og:title" content="<?= e($META['title']) ?>">
  <meta property="og:description" content="<?= e($META['description']) ?>">
  <meta property="og:image" content="<?= e($META['og_image']) ?>">
  <meta property="og:url" content="<?= e($META['canonical']) ?>">
  <meta property="og:type" content="website">

  <!-- Assets -->
  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="./whatsapp_style.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style>
    .gst-small { font-size: 17px; }
  </style>
</head>
<body>
  <?php include('header.php');?>

  <a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

  <div class="content-wrapper">
    <!-- Lines -->
    <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
      <div class="content-lines-inner"><div class="content-lines"></div></div>
    </section>

    <!-- Header Banner -->
    <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="<?= e($hero_bg) ?>"></section>

    <!-- Tariff -->
    <section class="section-padding2">
      <div class="container">
        <div class="row"><div class="col-md-12">
          <h2 class="section-title text-center"><span><?= e($intro_h2) ?></span></h2>
        </div></div>

        <h5 class="text-center" style="margin-bottom:22px; white-space:pre-line;"><?= e($intro_txt) ?></h5>

        <div class="col-md-12">
          <ul class="accordion-box clearfix">
          <?php
          /* Render packages */
          function package_block($idx){
            $title     = $GLOBALS["p{$idx}_title"];
            $subtitle  = $GLOBALS["p{$idx}_subtitle"];
            $occupancy = $GLOBALS["p{$idx}_occupancy"];
            $tariff    = $GLOBALS["p{$idx}_tariff"];
            $bullets   = $GLOBALS["p{$idx}_bullets"];
            $reminders = $GLOBALS["p{$idx}_reminders"];
            $cta       = $GLOBALS["p{$idx}_cta"];
            $terms     = $GLOBALS["p{$idx}_terms"];
            if (trim($title)==='') return;

            echo '<li class="accordion block">';
            echo '  <div class="acc-btn"><h2 class="section-title2 text-center"><span><span>'.e($title).'</span></span></h2></div>';
            echo '  <div class="acc-content"><div class="content"><div class="row">';
            echo '    <div class="col-md-6"><div class="pricing-card">';
            if ($subtitle)  echo '      <h3><b style="line-height:1.5; color:#63c889">'.e($subtitle).'</b></h3>';
            if ($occupancy) echo '      <p class="pricing-card-name"><b>'.e($occupancy).'</b></p>';
            if ($tariff)    echo '      <h3 class="pricing-card-amount">'.render_tariff_small_gst($tariff).'</h3>';
            echo '      <div class="pricing-card-bottom">';
            if (trim($bullets)!=='') echo '        <ul class="list-unstyled pricing-card-list">'.lines_to_li($bullets,'ti-check').'</ul>';
            echo '        <div class="butn-pricing"><a href="'.e($cta).'"><span>Book Now</span></a></div>';
            echo '      </div></div></div>';

            echo '    <div class="col-md-6"><div class="pricing-card">';
            echo '      <p class="pricing-card-name text-center">*** Gentle Reminders ***</p>';
            echo '      <div class="pricing-card-bottom">';
            if (trim($reminders)!=='') echo '        <ul class="list-unstyled pricing-card-list">'.lines_to_li($reminders,'ti-arrow-circle-right').'</ul>';
            echo '        <p class="pricing-card-name">- Kindly review our terms and conditions before booking. -</p>';
            echo '        <div class="butn-pricing"><a href="'.e($terms).'"><span>Terms &amp; Conditions</span></a></div>';
            echo '      </div></div></div>';
            echo '  </div></div></div></li>';
          }

          // Render all dynamic sections
          package_block(1);
          package_block(2);
          package_block(3);
          package_block(4);
          ?>

          <!-- P5 -->
          <?php if (trim($p5_title)!==''): ?>
          <li class="accordion block">
            <div class="acc-btn"><h2 class="section-title2 text-center"><span><span><?= e($p5_title) ?></span></span></h2></div>
            <div class="acc-content"><div class="content">
              <div class="row">
                <div class="col-md-6">
                  <div class="pricing-card">
                    <?php if($p5_subtitle): ?><h3><b style="line-height:1.5; color:#63c889"><?= e($p5_subtitle) ?></b></h3><?php endif; ?>
                    <?php if($p5_occupancy): ?><p class="pricing-card-name"><b><?= e($p5_occupancy) ?></b></p><?php endif; ?>
                    <div class="pricing-card-bottom">
                      <?php if(trim($p5_bullets)!==''): ?>
                        <ul class="list-unstyled pricing-card-list"><?= lines_to_li($p5_bullets,'ti-check') ?></ul>
                      <?php endif; ?>
                      <div class="butn-pricing"><a href="<?= e($p5_cta) ?>"><span>Book Now</span></a></div>
                    </div>
                  </div>

                  <table>
                    <tr><th><b>OCCASION</b></th><th><b>CHECK-IN / CHECK-OUT</b></th><th><b>TARIFF</b></th></tr>
                    <?php if ($p5_r1_label || $p5_r1_time || $p5_r1_tar): ?>
                    <tr><td><?= e($p5_r1_label) ?></td><td><?= e($p5_r1_time) ?></td><td><?= render_tariff_small_gst($p5_r1_tar) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($p5_r2_label || $p5_r2_time || $p5_r2_tar): ?>
                    <tr><td><?= e($p5_r2_label) ?></td><td><?= e($p5_r2_time) ?></td><td><?= render_tariff_small_gst($p5_r2_tar) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($p5_r3_label || $p5_r3_time || $p5_r3_tar): ?>
                    <tr><td><?= e($p5_r3_label) ?></td><td><?= e($p5_r3_time) ?></td><td><?= render_tariff_small_gst($p5_r3_tar) ?></td></tr>
                    <?php endif; ?>
                  </table>
                </div>

                <div class="col-md-6">
                  <div class="pricing-card">
                    <p class="pricing-card-name text-center">*** Gentle Reminders ***</p>
                    <div class="pricing-card-bottom">
                      <?php if(trim($p5_reminders)!==''): ?>
                        <ul class="list-unstyled pricing-card-list"><?= lines_to_li($p5_reminders,'ti-arrow-circle-right') ?></ul>
                      <?php endif; ?>
                      <p class="pricing-card-name">- Kindly review our terms and conditions before booking. -</p>
                      <div class="butn-pricing"><a href="<?= e($p5_terms) ?>"><span>Terms &amp; Conditions</span></a></div>
                    </div>
                  </div>
                </div>
              </div>
            </div></div>
          </li>
          <?php endif; ?>
          </ul>
        </div>
      </div>
    </section>

    <?php include('footer.php'); ?>
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
