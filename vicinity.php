<?php
// /kanav/vicinity.php  (public page)
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
  CREATE TABLE IF NOT EXISTS vicinity_settings (
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
      'title'       => $fb_title ?: 'Nearby Attractions at Kanavu Heritage - River Periyar & More',
      'description' => $fb_desc  ?: 'Discover scenic nearby spots like River Periyar, Mahagony Thottam, Thumboormozhi Dam, and Kurishumdi—just moments from your serene homestay.',
      'canonical'   => kh_abs_url($fb_canon ?: '/vicinity.php'),
      'og_image'    => kh_abs_url('/img/og-default.jpg'),
    ];
  }
}

// Canonical path (works for /kanav/vicinity.php too)
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($script), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
$CANON_PATH = $basePath . '/' . basename(__FILE__);

// Slug
$slug = basename(__FILE__, '.php');
if ($slug === 'index') $slug = 'home';

// Build meta (fallbacks are OK; SEO helper may override)
$META = kh_build_meta(
  $conn,
  $slug,
  'Nearby Attractions at Kanavu Heritage - River Periyar & More',
  'Discover scenic nearby spots like River Periyar, Mahagony Thottam, Thumboormozhi Dam, and Kurishumdi—just moments from your serene homestay.',
  $CANON_PATH
);

// helpers
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function get_setting(mysqli $conn, string $key, string $default = ''): string {
  $stmt = $conn->prepare("SELECT `value` FROM vicinity_settings WHERE `key`=?");
  $stmt->bind_param('s', $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? (string)$row['value'] : $default;
}

/* ---------- Dynamic Content (fallbacks = your current HTML) ---------- */
$hero_bg = get_setting($conn,'hero_bg','img/gallery/01.jpg');
$page_h2 = get_setting($conn,'page_h2','Vicinity <span> Attractions</span>');

// V1
$v1_img   = get_setting($conn,'v1_img','img/slider/Mahagony.jpg');
$v1_title = get_setting($conn,'v1_title','Mahagony Thottam');
$v1_p1    = get_setting($conn,'v1_p1',"Originally Known as Mulankuzhy, Mahagony Thottam as the name suggests is a forest of huge, black Mahogany trees and tall wild jack trees, including the oldest one that dates back to 1820 (the display board says it approximately weights 25000 kg).");
$v1_p2    = get_setting($conn,'v1_p2',"The area where the wilderness and the Periyar merge is a calm spot for travelers, especially for those who love some quiet time.");

// V2
$v2_img   = get_setting($conn,'v2_img','img/slider/Malayattoor_Church.jpg');
$v2_title = get_setting($conn,'v2_title','MALAYATOOR INTERNATIONAL SHRINE');
$v2_p1    = get_setting($conn,'v2_p1',"One of the most important Christian pilgrim centres in Kerala.This is one of the few places visited by Saint Thomas (an apostle of Jesus Christ ). The Church has a life-size statue of St. Thomas and the imprint of the feet of the Apostle on a rock.");

// Card items
$c1_img   = get_setting($conn,'c1_img','img/slider/Kurishumdi.jpg');
$c1_title = get_setting($conn,'c1_title','HILL OF HOLY CROSS');
$c1_p1    = get_setting($conn,'c1_p1','Kurishumdi, or the Hill of Holy Cross, a religious and tourist attraction');

$c2_img   = get_setting($conn,'c2_img','img/slider/Trees.jpg');
$c2_title = get_setting($conn,'c2_title','KALADY PLANTATION FOREST');
$c2_p1    = get_setting($conn,'c2_p1','Kalady Plantations rich in Oil Palm and Rubber Trees, under the Plantation Corporation of Kerala Ltd.');

// V3
$v3_img   = get_setting($conn,'v3_img','img/slider/Paniyeli_Poru.jpg');
$v3_title = get_setting($conn,'v3_title','Paniyeli Poru');
$v3_p1    = get_setting($conn,'v3_p1',"Caressed and nourished by the River Periyar, which flows between Malayattoor in the north and Paneli in the south, the place obtained its name from the routine fight of raftsmen with the wild waves of the river. The river also offers one with a panoramic view of this pristine spot. Paniyeli Poru is just 20 km away from Perumbavoor and 35 km from Aluva and 22 km from Kanavu.");

// V4
$v4_img   = get_setting($conn,'v4_img','img/slider/Iringole_Kavu.jpg');
$v4_title = get_setting($conn,'v4_title','Iringole Kavu');
$v4_p1    = get_setting($conn,'v4_p1',"Iringole Kavu is a forest temple dedicated to Goddess Durga, situated in Kunnathunad Taluk of Ernakulam district, 2.5 km from Perumbavoor. This is one of the 108 Durga Temples in Kerala believed to have been consecrated by Lord Parasurama, the sixth avatar of Lord Vishnu.");

// V5
$v5_img   = get_setting($conn,'v5_img','img/slider/Kodanad.jpg');
$v5_title = get_setting($conn,'v5_title','ELEPHANT TRAINING CENTRES');
$v5_p1    = get_setting($conn,'v5_p1','Abhayaranyam Kaprikadu Ecotourism, near Kodanad, with one of the largest Elephant Training Centres in Kerala');

// Kallil section
$k1_img   = get_setting($conn,'k1_img','img/slider/k1.jpg');
$k1_title = get_setting($conn,'k1_title','KALLIL BHAGAVATI TEMPLE');
$k1_p1    = get_setting($conn,'k1_p1',"Kallil temple ( rock-cut temple) is carved out of a huge granite block on the top of a hill, and a climb of 120 steps leads to temple.");
$k1_p2    = get_setting($conn,'k1_p2',"This natural structure situated right in the middle of a jungle as wide as 28 acres is considered one among prime ancient Jain temples in Kerala. Bhagavathi’s(Goddess’) shrine or ‘prathishta’ is in a cave beneath the massive rock that stands sans a support on the ground.");

$k2_img   = get_setting($conn,'k2_img','img/slider/k2.jpg');
$k2_p1    = get_setting($conn,'k2_p1',"This giant of a monolith measuring a whooping 75 feet length, 45 feet width and 25 feet height stands tall as nothing less than enigma before the keepers of history and the very scientific community.");

// Abhayaranyam block
$a_main_img = get_setting($conn,'a_main_img','img/slider/l1.jpg');
$a_title    = get_setting($conn,'a_title','ABHAYARANYAM ECO – TOURISM');
$a_p1       = get_setting($conn,'a_p1','Abhayaranyam is a nature rehabilitation centre for orphaned wildlife. Kodanad Elephant Training Centre, one of the largest elephant training centres in Kerala situated inside the Abhayaranyam premises let you spend some quality time in the company of the giant tuskers and watch the activities involved in their daily life.');
$a_p2       = get_setting($conn,'a_p2','Besides the pretty Jumbos, numerous varieties of deers, and a smorgasbord of birdies can be seen here roaming freely.');
$a_p3       = get_setting($conn,'a_p3','The highlighted programme here is the trekking to Panamkuzhi from Kaprikad or Abhayaranyam. The place also has a Mahagani plantation, bathing ghat in Periyar, tree houses, bamboo hut and many more.');
$a_img2     = get_setting($conn,'a_img2','img/slider/l2.jpg');
$a_img3     = get_setting($conn,'a_img3','img/slider/l3.jpg');

// Waterfalls
$w1_img   = get_setting($conn,'w1_img','img/slider/Water_Falls.jpg');
$w1_title = get_setting($conn,'w1_title','ATHIRAPPALLY WATERFALLS');
$w1_p1    = get_setting($conn,'w1_p1',"It is Kerala's most famous and largest waterfall at over 80 ft high. The sight of the water crashing onto the ground leaves you with a sense of wonder at the sheer power and magnificence of nature. Lying at the entrance to the Sholayar forest ranges, it is a part of the Chalakudy River which calls the Western Ghats its home.");

$w2_img   = get_setting($conn,'w2_img','img/slider/EZHATTUMUGHAM.jpg');
$w2_title = get_setting($conn,'w2_title','EZHATTUMUGHAM');
$w2_p1    = get_setting($conn,'w2_p1',"Ezhattumugham is known for Thumboormozhi dam, Hanging bridge and oil palm plantation. It has recently become the location of many movie sets, in part due to the beauty of the Chalakudy river. This villlage has been promoted as a tourism vilage known as 'Prakriti Gramam' (Nature Village).");
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

  <section class="blog4 section-padding2">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <h2 class="section-title"><?= $page_h2 ?></h2>
        </div>
      </div>

      <div class="row"><div class="col-md-12">

        <!-- V1 -->
        <div class="bauen-blog4 animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($v1_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($v1_title) ?></a></h4>
            <?php if(trim($v1_p1)!==''): ?><p><b><?= nl2br(e($v1_p1)) ?></b></p><?php endif; ?>
            <?php if(trim($v1_p2)!==''): ?><p><b><?= nl2br(e($v1_p2)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

        <!-- V2 -->
        <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($v2_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($v2_title) ?></a></h4>
            <?php if(trim($v2_p1)!==''): ?><p><b><?= nl2br(e($v2_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

      </div></div>
    </div>
  </section>

  <section class="bauen-blog section-padding2">
    <div class="container">
      <div class="row">

        <!-- Card 1 -->
        <div class="col-md-6">
          <div class="item">
            <div class="position-re o-hidden"><img src="<?= e($c1_img) ?>" alt=""></div>
            <div class="con">
              <h4><a href="#"><?= e($c1_title) ?></a></h4>
              <?php if(trim($c1_p1)!==''): ?><p><b><?= nl2br(e($c1_p1)) ?></b></p><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="col-md-6">
          <div class="item">
            <div class="position-re o-hidden"><img src="<?= e($c2_img) ?>" alt=""></div>
            <div class="con">
              <h4><a href="#"><?= e($c2_title) ?></a></h4>
              <?php if(trim($c2_p1)!==''): ?><p><b><?= nl2br(e($c2_p1)) ?></b></p><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- V3 -->
        <div class="bauen-blog4 animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($v3_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($v3_title) ?></a></h4>
            <?php if(trim($v3_p1)!==''): ?><p><b><?= nl2br(e($v3_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

        <!-- V4 -->
        <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($v4_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($v4_title) ?></a></h4>
            <?php if(trim($v4_p1)!==''): ?><p><b><?= nl2br(e($v4_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

        <!-- V5 -->
        <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($v5_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($v5_title) ?></a></h4>
            <?php if(trim($v5_p1)!==''): ?><p><b><?= nl2br(e($v5_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Kallil -->
  <section class="blog4 section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12">

        <div class="bauen-blog4 animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($k1_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($k1_title) ?></a></h4>
            <?php if(trim($k1_p1)!==''): ?><p><b><?= nl2br(e($k1_p1)) ?></b></p><?php endif; ?>
            <?php if(trim($k1_p2)!==''): ?><p><b><?= nl2br(e($k1_p2)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

        <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($k2_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <?php if(trim($k2_p1)!==''): ?><p><b><?= nl2br(e($k2_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

      </div></div>
    </div>
  </section>

  <!-- Abhayaranyam -->
  <section class="bauen-blog section-padding2">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="item"><div class="position-re o-hidden"><img src="<?= e($a_main_img) ?>" alt=""></div></div>
        </div>
        <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
          <div class="content"><div class="cont">
            <h2 class="section-title"><span><?= e($a_title) ?></span></h2>
            <?php if(trim($a_p1)!==''): ?><p><?= nl2br(e($a_p1)) ?></p><?php endif; ?>
            <?php if(trim($a_p2)!==''): ?><p><?= nl2br(e($a_p2)) ?></p><?php endif; ?>
            <?php if(trim($a_p3)!==''): ?><p><?= nl2br(e($a_p3)) ?></p><?php endif; ?>
          </div></div>
        </div>

        <div class="row">
          <div class="col-md-6"><div class="item"><div class="position-re o-hidden"><img src="<?= e($a_img2) ?>" alt=""></div></div></div>
          <div class="col-md-6"><div class="item"><div class="position-re o-hidden"><img src="<?= e($a_img3) ?>" alt=""></div></div></div>
        </div>

      </div>
    </div>
  </section>

  <!-- Waterfalls -->
  <section class="blog4 section-padding2">
    <div class="container">
      <div class="row"><div class="col-md-12">

        <div class="bauen-blog4 animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($w1_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($w1_title) ?></a></h4>
            <?php if(trim($w1_p1)!==''): ?><p><b><?= nl2br(e($w1_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

        <div class="bauen-blog4 left animate-box" data-animate-effect="fadeInUp">
          <figure><img src="<?= e($w2_img) ?>" alt="" class="img-fluid"></figure>
          <div class="caption">
            <h4><a><?= e($w2_title) ?></a></h4>
            <?php if(trim($w2_p1)!==''): ?><p><b><?= nl2br(e($w2_p1)) ?></b></p><?php endif; ?>
            <hr class="border-2">
          </div>
        </div>

      </div></div>
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
