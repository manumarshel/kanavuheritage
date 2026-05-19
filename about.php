<?php
// /kanav/about — full page (SEO-ready + DB-driven)

// 1) DB connect
require __DIR__ . '/includes/connect.php';

// 2) Robust SEO helper loader (prefer /includes, fallback to /dashboard)
$ROOT = __DIR__;
$INC  = $ROOT . '/includes';
$DASH = $ROOT . '/dashboard';
if (is_file($INC . '/seo_meta.php')) {
  require_once $INC . '/seo_meta.php';
} elseif (is_file($DASH . '/seo_meta.php')) {
  require_once $DASH . '/seo_meta.php';
} else {
  // Last-resort tiny fallback so page still works without seo_meta.php
  function kh_base_url(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($proto.$host, '/');
  }
  function kh_abs_url(string $rel): string {
    if (preg_match('~^https?://~i', $rel)) return $rel;
    return kh_base_url() . '/' . ltrim($rel, '/');
  }
  // NOTE: $page_key is a logical slug like 'about', not a filename
  function kh_build_meta(mysqli $conn, string $page_key, string $fb_title='', string $fb_desc='', string $fb_canon=''): array {
    return [
      'title'       => $fb_title ?: 'About Kanavu Heritage | boutique homestay in Kerala',
      'description' => $fb_desc  ?: 'Kanavu Heritage offers luxury homestay in Kerala near Cochin Airport. Enjoy traditional charm & explore the best tourist places in Kochi.',
      // ✅ Site lives under /kanav/, so default canonical includes it
      'canonical'   => kh_abs_url($fb_canon ?: '/kanav/about'),
      'og_image'    => kh_abs_url('img/og-default.jpg'),
    ];
  }
}

// 3) Build SEO meta for this page (use a fixed slug, not basename)
$current_page = 'about';
$META = kh_build_meta(
  $conn,
  $current_page,
  // Fallbacks (used only if no row in seo_pages and no global defaults)
  'About Kanavu Heritage | boutique homestay in Kerala',
  'Kanavu Heritage offers luxury homestay in Kerala near Cochin Airport. Enjoy traditional charm & explore the best tourist places in Kochi.',
  '/kanav/about' // pretty URL fallback
);

// 4) Helpers + content loaders
function get_setting(mysqli $conn, string $key, string $default=''): string {
  $stmt = $conn->prepare("SELECT `value` FROM about_settings WHERE `key`=?");
  $stmt->bind_param('s', $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $default;
}
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* ------- Load page content (safe fallbacks) ------- */
$hero_bg     = get_setting($conn,'about_hero_bg','img/gallery/01.jpg');
$headline    = get_setting($conn,'about_headline','EXPERIENCE THE PERFECT BLEND OF COMFORT <span>AND NATURAL BEAUTY AT KANAVU</span>');

$s1_title    = get_setting($conn,'about_s1_title','Crafting Excellence in Hospitality');
$s1_p1       = get_setting($conn,'about_s1_p1','');
$s1_p2       = get_setting($conn,'about_s1_p2','');
$s1_img      = get_setting($conn,'about_s1_img','img/slider/a2.jpg');

$s2_title    = get_setting($conn,'about_s2_title','Our Vision and Mission');
$s2_p1       = get_setting($conn,'about_s2_p1','');
$s2_p2       = get_setting($conn,'about_s2_p2','');
$s2_img      = get_setting($conn,'about_s2_img','img/slider/a1.jpg');

$kb_title    = get_setting($conn,'about_kb_title','WE INVITE GUESTS TO CELEBRATE LIFE');
$kb_sub      = get_setting($conn,'about_kb_sub','The Nature For Your Better Life');
$kb_btn_text = get_setting($conn,'about_kb_btn_text','Gallery');
$kb_btn_link = get_setting($conn,'about_kb_btn_link','photo');

/* kenburns/vegas slides */
$kb_slides = $conn->query("SELECT image FROM about_slides ORDER BY sort_order, id");
$kb_images = [];
if ($kb_slides && $kb_slides->num_rows) {
  while($r = $kb_slides->fetch_assoc()){ $kb_images[] = (string)$r['image']; }
} else {
  $kb_images = ['img/slider/6.jpg','img/slider/7.jpg','img/slider/8.jpg'];
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- ✅ Make all relative paths load correctly from /kanav/ -->


  <!-- SEO (dynamic via dashboard with safe fallbacks) -->
  <title><?= e($META['title']) ?></title>
  <meta name="description" content="<?= e($META['description']) ?>">
  <link rel="canonical" href="<?= e($META['canonical']) ?>">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= e($META['title']) ?>">
  <meta property="og:description" content="<?= e($META['description']) ?>">
  <meta property="og:image" content="<?= e($META['og_image']) ?>">
  <meta property="og:url" content="<?= e($META['canonical']) ?>">
  <meta property="og:type" content="website">

  <!-- Assets -->
  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="whatsapp_style.css" />

  <!-- Analytics (keep as-is) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1CM66TP69"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date()); gtag('config', 'G-Y1CM66TP69');
  </script>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-NP7DBK64');</script>

  <style>
    /* minimal safety styles; your theme CSS will handle the rest */
    .hero{position:relative;min-height:40vh;display:grid;place-items:center;color:#fff;text-align:center;background-size:cover;background-position:center;border-radius:8px}
    .section{margin:40px auto;max-width:1100px;padding:0 16px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
    @media (max-width:900px){.grid{grid-template-columns:1fr}}
    .btn{display:inline-block;padding:10px 16px;border:1px solid #333;border-radius:8px;text-decoration:none}
    .kb{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px}
    .kb img{width:100%;height:180px;object-fit:cover;border-radius:10px}
  </style>
</head>

<body>
<?php include('header.php'); ?>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- WhatsApp bubble -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

<div class="content-wrapper">
  <!-- Lines -->
  <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
    <div class="content-lines-inner"><div class="content-lines"></div></div>
  </section>

  <!-- Header Banner (same style as home header strip) -->
  <section class="banner-header banner-img valign bg-img bg-fixed"
           data-overlay-darkgray="5"
           data-background="<?= htmlspecialchars($hero_bg) ?>">
  </section>

  <!-- Headline centered -->
  <section class="about section-padding2">
    <div class="container">
      <div class="row">
        <div class="col-md-8 offset-md-2 mb-30 text-center">
          <h2 class="section-title">
            <?= $headline /* trusted HTML with <span> allowed; keep it simple */ ?>
          </h2>
        </div>
      </div>

      <!-- Section 1: Left text / Right image -->
      <div class="row">
        <div class="col-md-6 mb-30 animate-box" data-animate-effect="fadeInUp">
          <h2 class="section-title" style="letter-spacing:normal;"><?= htmlspecialchars($s1_title) ?></h2>
          <?php if($s1_p1): ?><p><?= nl2br(htmlspecialchars($s1_p1)) ?></p><?php endif; ?>
          <?php if($s1_p2): ?><p><?= nl2br(htmlspecialchars($s1_p2)) ?></p><?php endif; ?>
        </div>
        <div class="col-md-6 animate-box" data-animate-effect="fadeInUp">
          <div class="about-img">
            <div class="img"><img src="<?= htmlspecialchars($s1_img) ?>" class="img-fluid" alt=""></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Section 2: Left image / Right text -->
  <section class="about section-padding2">
    <div class="container">
      <div class="row">
        <div class="col-md-6 animate-box" data-animate-effect="fadeInUp">
          <div class="about-img">
            <div class="img"><img src="<?= htmlspecialchars($s2_img) ?>" class="img-fluid" alt=""></div>
          </div>
        </div>
        <div class="col-md-6 mb-30 animate-box" data-animate-effect="fadeInUp">
          <h2 class="section-title" style="letter-spacing:normal;"><?= htmlspecialchars($s2_title) ?></h2>
          <?php if($s2_p1): ?><p><?= nl2br(htmlspecialchars($s2_p1)) ?></p><?php endif; ?>
          <?php if($s2_p2): ?><p><?= nl2br(htmlspecialchars($s2_p2)) ?></p><?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Kenburns / Vegas background with centered title and button -->
  <section class="section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12">
        <aside class="kenburns-section mb-30" id="kenburnsSliderContainer" data-overlay-dark="3">
          <div class="kenburns-inner h-100">
            <div class="v-middle caption text-center">
              <div class="container">
                <div class="row h-100">
                  <div class="col-md-8 offset-md-2 mt-30">
                    <span>
                      <i class="star-rating"></i><i class="star-rating"></i><i class="star-rating"></i><i class="star-rating"></i><i class="star-rating"></i>
                    </span>
                    <?php if($kb_sub): ?><h4><?= htmlspecialchars($kb_sub) ?></h4><?php endif; ?>
                    <h1 style="font-size:35px"><?= htmlspecialchars($kb_title) ?></h1>
                    <div class="butn-light mt-30 mb-30">
                      <a href="<?= htmlspecialchars($kb_btn_link) ?>"><span><?= htmlspecialchars($kb_btn_text) ?></span></a>
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- caption -->
          </div><!-- inner -->
        </aside>

        <h2 class="section-title2">"Kanavu is a boutique heritage homestay"</h2>
      </div></div>
    </div>
  </section>

  <?php include('footer.php'); ?>
</div>

<!-- Scripts (same as home) -->
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

<script>
  // Build vegas slides from PHP array
  (function(){
    var slides = <?=
      json_encode(array_map(function($p){ return ['src'=>$p]; }, $kb_images),
      JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    ?>;

    $(document).ready(function () {
      $('#kenburnsSliderContainer').vegas({
        slides: slides,
        overlay: true,
        transition: 'fade2',
        animation: 'kenburnsUpRight',
        transitionDuration: 1000,
        delay: 10000,
        animationDuration: 20000
      });
    });
  })();
</script>

</body>
</html>
