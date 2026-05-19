<?php
// /kanav/amenities — dynamic, no undefined vars, dual table support

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require __DIR__ . '/includes/connect.php';
$conn->set_charset('utf8mb4');

/* ---------- Helpers ---------- */
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/**
 * Reads from amenities_settings; if missing, falls back to amen_settings.
 */
function getv(mysqli $conn, string $key, string $def=''): string {
  // Try amenities_settings first
  try {
    $stmt = $conn->prepare("SELECT `value` FROM amenities_settings WHERE `key`=?");
    $stmt->bind_param('s',$key); $stmt->execute();
    $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row && isset($row['value'])) return (string)$row['value'];
  } catch (Throwable $e) {
    // table might not exist; fall back below
  }
  // Fallback: amen_settings (older name)
  try {
    $stmt = $conn->prepare("SELECT `value` FROM amen_settings WHERE `key`=?");
    $stmt->bind_param('s',$key); $stmt->execute();
    $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row && isset($row['value'])) return (string)$row['value'];
  } catch (Throwable $e) { /* ignore */ }
  return $def;
}

/* ---------- SEO loader (prefer includes/, then dashboard/, else fallback) ---------- */
$SEO_OK = false;
if (is_file(__DIR__.'/includes/seo_meta.php')) { require_once __DIR__.'/includes/seo_meta.php'; $SEO_OK = true; }
elseif (is_file(__DIR__.'/dashboard/seo_meta.php')) { require_once __DIR__.'/dashboard/seo_meta.php'; $SEO_OK = true; }
if (!$SEO_OK) {
  function kh_base_url(): string {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $proto = $https ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($proto.$host, '/');
  }
  function kh_abs_url(string $rel): string {
    if (preg_match('~^https?://~i',$rel)) return $rel;
    return kh_base_url().'/'.ltrim($rel,'/');
  }
  function kh_build_meta(mysqli $conn, string $slug, string $fb_title='', string $fb_desc='', string $fb_canon=''): array {
    return [
      'title'       => $fb_title ?: 'Amenities & Facilities — Kanavu Heritage',
      'description' => $fb_desc  ?: 'Thoughtful amenities for a calm, comfortable stay — gazebo, hammock, lawn, photo spots and more.',
      'canonical'   => kh_abs_url($fb_canon ?: '/kanav/amenities'),
      'og_image'    => kh_abs_url('/kanav/img/og-default.jpg'),
    ];
  }
}

/* ---------- SEO values ---------- */
$META = kh_build_meta(
  $conn,
  'amenities',
  'Amenities & Facilities — Kanavu Heritage',
  'Thoughtful amenities for a calm, comfortable stay — gazebo, hammock, lawn, photo spots and more.',
  '/kanav/amenities'
);

/* ---------- Dynamic fields (define EVERYTHING with defaults) ---------- */
$hero_bg = getv($conn,'hero_bg','img/gallery/01.jpg');

/* Optional big H2 split title (was undefined in your file) */
$h2_title_left  = getv($conn,'h2_title_left','AMENITIES & ');
$h2_title_right = getv($conn,'h2_title_right','FACILITIES');

/* Optional top image (was referenced but not defined) */
$top_img = getv($conn,'top_img','');

/* Sections */
$s1_title = getv($conn,'s1_title','SPACIOUS COURTYARD');
$s1_p1    = getv($conn,'s1_p1','A serene central space perfect for gatherings, quiet reading, and golden-hour photos.');
$s1_p2    = getv($conn,'s1_p2','Traditional elements bring warmth and character across the day.');
$s1_img   = getv($conn,'s1_img','img/slider/s13.jpg');

$s2_title = getv($conn,'s2_title','GAZEBO SEATING');
$s2_p1    = getv($conn,'s2_p1','Cozy shaded spot for tea-time chats and lazy afternoon breaks.');
$s2_p2    = getv($conn,'s2_p2','Surrounded by greenery and birdsong for a slow, restful mood.');
$s2_img   = getv($conn,'s2_img','img/slider/s9.jpg');

/* You can continue with s3..s6 if the page needs them; not strictly required for the errors you saw */
// $s3_title = getv($conn,'s3_title','HAMMOCK & PEBBLE FLOOR'); ... etc.
?>
<!DOCTYPE html>
<html lang="zxx">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

    <!-- Make all relative paths work on /kanav/pretty-urls -->


    <!-- SEO -->
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
  </head>

  <body>
    <!-- WhatsApp FAB (unchanged) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

    <!-- Navbar (your existing header.php would be better, but keeping your direct markup is fine too) -->
    <?php if (is_file(__DIR__.'/header.php')) { include __DIR__.'/header.php'; } ?>

    <div class="content-wrapper">
      <!-- Lines -->
      <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
        <div class="content-lines-inner"><div class="content-lines"></div></div>
      </section>

      <!-- Header Banner (dynamic + inline fallback so BG always shows) -->
      <section
        class="banner-header banner-img valign bg-img bg-fixed"
        data-overlay-darkgray="5"
        data-background="<?= e($hero_bg) ?>"
        style="background-image:url('<?= e($hero_bg) ?>')"
      ></section>

      <!-- Body -->
      <section class="bauen-blog2 section-padding2">
        <div class="container">
          <div class="row"><div class="col-md-12">
            <h2 class="section-title"><?= e($h2_title_left) ?><span><?= e($h2_title_right) ?></span></h2>
          </div></div>

          <?php if ($top_img !== ''): ?>
            <div class="row"><div class="col-md-12">
              <img src="<?= e($top_img) ?>" alt="" class="img-fluid" />
            </div></div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-12">
              <!-- Block 1 -->
              <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
                <figure><img src="<?= e($s1_img) ?>" alt="" class="img-fluid"></figure>
                <div class="caption">
                  <h4><a href="#"><?= e($s1_title) ?></a></h4>
                  <?php if($s1_p1!==''): ?><p><?= nl2br(e($s1_p1)) ?></p><?php endif; ?>
                  <?php if($s1_p2!==''): ?><p><?= nl2br(e($s1_p2)) ?></p><?php endif; ?>
                  <hr class="border-2">
                </div>
              </div>

              <!-- Block 2 -->
              <div class="bauen-blog4 animate-box" data-animate-effect="fadeInUp">
                <figure><img src="<?= e($s2_img) ?>" alt="" class="img-fluid"></figure>
                <div class="caption">
                  <h4><a href="#"><?= e($s2_title) ?></a></h4>
                  <?php if($s2_p1!==''): ?><p><?= nl2br(e($s2_p1)) ?></p><?php endif; ?>
                  <?php if($s2_p2!==''): ?><p><?= nl2br(e($s2_p2)) ?></p><?php endif; ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section>

      <?php if (is_file(__DIR__.'/footer.php')) { include __DIR__.'/footer.php'; } ?>
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

    <script>
      // In case theme JS relies on data-background only,
      // we already set inline style above. This is just a sanity sync.
      (function(){
        var el = document.querySelector('.banner-header');
        if (el && el.getAttribute('data-background')) {
          var bg = el.getAttribute('data-background');
          el.style.backgroundImage = "url('"+bg+"')";
        }
      })();
    </script>
  </body>
</html>
