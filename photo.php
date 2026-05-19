<?php
// /kanav/photo  — TOP + <head> (SEO-ready)

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

// small esc helper
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- Make relative paths resolve under /kanav/ in localhost -->


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

  <!-- Assets -->
  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="whatsapp_style.css">
</head>

<body>
<?php include('header.php'); ?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

<div class="content-wrapper">
  <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
    <div class="content-lines-inner"><div class="content-lines"></div></div>
  </section>
  <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg"></section>

  <section class="section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12"><h2 class="section-title"><span>Gallery</span></h2></div></div>

      <div class="row">
        <?php
          $rs = $conn->query("SELECT image_path, image_alt FROM gallery WHERE is_active=1 ORDER BY id DESC");
          if ($rs->num_rows):
            while($g=$rs->fetch_assoc()):
              $src = htmlspecialchars($g['image_path']);
              $alt = htmlspecialchars($g['image_alt'] ?? 'Gallery image');
        ?>
          <div class="col-md-6 col-lg-4 gallery-item">
            <a href="<?= $src ?>" title="<?= $alt ?>" class="img-zoom">
              <div class="gallery-box">
                <div class="gallery-img"><img src="<?= $src ?>" class="img-fluid mx-auto d-block" alt="<?= $alt ?>"></div>
              </div>
            </a>
          </div>
        <?php endwhile; else: ?>
          <div class="col-12 text-muted">No images available yet.</div>
        <?php endif; ?>
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
<script>
$(function(){
  $('.img-zoom').magnificPopup({ type:'image', gallery:{enabled:true} });
});
</script>
</body>
</html>
