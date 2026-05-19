<?php
// /kanav/index  — TOP SECTION (safe includes + SEO meta build)

$ROOT = __DIR__;
$INC  = $ROOT . '/includes';

// 1) DB connect (include ONCE)
if (is_file($INC . '/connect.php')) {
  require_once $INC . '/connect.php';   // sets $conn (mysqli)
} else {
  die("Missing DB file: /includes/connect.php");
}

// 2) Load SEO helper (prefer /includes; fallback to dashboard)
$seo_loaded = false;
if (is_file($INC . '/seo_meta.php')) {
  require_once $INC . '/seo_meta.php';
  $seo_loaded = true;
} elseif (is_file($ROOT . '/dashboard/seo_meta.php')) {
  require_once $ROOT . '/dashboard/seo_meta.php';
  $seo_loaded = true;
}

// 3) If helper missing, tiny fallback
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

// 4) Build SEO for HOME page (slug = home)
$META = kh_build_meta(
  $conn,
  'home',
  'Heritage Homestay in Kerala | Best Resort in Cochin',
  'Kanavu Heritage offers luxury homestay in Kerala near Cochin Airport. Enjoy traditional charm & explore the best tourist places in Kochi.',
  '/kanav/'
);

// -------------------------------------

/* HELPER: Get home settings */
function get_setting($conn, $key, $default='') {
  $stmt = $conn->prepare("SELECT `value` FROM home_settings WHERE `key`=?");
  $stmt->bind_param('s',$key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  return $row ? $row['value'] : $default;
}

// load homepage content
$slides = $conn->query("SELECT image FROM home_slider ORDER BY sort_order,id DESC");

$welcome_headline = get_setting($conn,'welcome_headline','');
$welcome_p1       = get_setting($conn,'welcome_p1','');
$welcome_p2       = get_setting($conn,'welcome_p2','');
$welcome_img1     = get_setting($conn,'welcome_img1','');
$welcome_img2     = get_setting($conn,'welcome_img2','');

$cta_left_title    = get_setting($conn,'cta_left_title','KANAVU & BEYOND : A VISUAL JOURNEY');
$cta_left_text     = get_setting($conn,'cta_left_text','');
$cta_left_btn_text = get_setting($conn,'cta_left_btn_text','TAKE A TOUR');
$cta_left_btn_link = get_setting($conn,'cta_left_btn_link','photo.php');

$cta_right_title    = get_setting($conn,'cta_right_title','MAKE YOUR CELEBRATIONS EXTRAORDINARY');
$cta_right_text     = get_setting($conn,'cta_right_text','');
$cta_right_btn_text = get_setting($conn,'cta_right_btn_text','CELEBRATE WITH US');
$cta_right_btn_link = get_setting($conn,'cta_right_btn_link','celebrations');
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- ✅ Base path for pretty URLs -->


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
<meta name="msvalidate.01" content="5156C3D2B61636F2BD0D86B16AA5762C" />
  <!-- Styles -->
  <link rel="shortcut icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/plugins.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/bannerSlider.css" />
  <link rel="stylesheet" href="whatsapp_style.css" />

  <!-- Analytics -->
  <meta name="google-site-verification" content="5VI2sxqNbb1ah-XQzXLtkR02S_IQKq-ijlzQT0x63hM" />
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1CM66TP69"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-Y1CM66TP69');
  </script>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-NP7DBK64');</script>
  <!-- Meta Pixel Code -->
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1251840300077500');
    fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1251840300077500&ev=PageView&noscript=1"/></noscript>
<!-- End Meta Pixel Code -->

<!-- bing -->
 <meta name="msvalidate.01" content="5156C3D2B61636F2BD0D86B16AA5762C" />
<!-- bing -->
</head>



  <?php include('header.php');?>

  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <!-- Slider (DYNAMIC from DB, fallback to your 3 images if empty) -->
  <header class="header ">
    <div class="owl-carousel owl-theme bannerSlider">
      <?php if($slides && $slides->num_rows): ?>
        <?php while($s=$slides->fetch_assoc()): ?>
          <div class="sliderItem">
            <div class="imgBg" style="background-image:url(<?= 'media/home/'.htmlspecialchars($s['image']) ?>)"></div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="sliderItem"><div class="imgBg" style="background-image:url(img/slider/1.jpg)"></div></div>
        <div class="sliderItem"><div class="imgBg" style="background-image:url(img/slider/13.jpg)"></div></div>
        <div class="sliderItem"><div class="imgBg" style="background-image:url(img/slider/3.jpg)"></div></div>
      <?php endif; ?>
    </div>
  </header>

  <!-- WhatsApp bubble -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

  <!-- Content -->
  <div class="content-wrapper">
    <!-- Lines -->
    <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
      <div class="content-lines-inner"><div class="content-lines"></div></div>
    </section>

    <!-- Welcome section (DYNAMIC text + images, keeps your layout) -->
<section class="bauen-blog2 section-padding2">
  <div class="container">
    <br><br>

    <?php if($welcome_headline): ?>
      <p style="text-align:center;font-weight:800;font-size:20px;color:#d3be7d;">
        <?= nl2br(htmlspecialchars($welcome_headline)) ?>
      </p>
    <?php endif; ?>

    <!-- ROW 1: Left image + Right text -->
    <div class="row mb-60">
      <!-- LEFT IMAGE -->
      <div class="col-md-6 order1 animate-box" data-animate-effect="fadeInRight">
        <div class="background bg-img bg-fixed section-padding9 pb-0" style="background-repeat: no-repeat !important;"
             data-background="<?= $welcome_img1 ? 'media/home/'.htmlspecialchars($welcome_img1) : 'img/slider/v3.jpg' ?>"
             data-overlay-dark="3">
          <div class="container"><div class="row">
            <div class="col-md-6">
              <div class="vid-area">
                <div class="vid-icon">
                  <a class="play-button vid" href="https://www.youtube.com/watch?v=hjFlK4rZNbk">
                    <svg class="circle-fill"><circle cx="43" cy="43" r="39" stroke="#fff" stroke-width=".5"></circle></svg>
                    <svg class="circle-track"><circle cx="43" cy="43" r="39" stroke="none" stroke-width="1" fill="none"></circle></svg>
                    <span class="polygon"><i class="ti-control-play"></i></span>
                  </a>
                </div>
                <div class="cont mt-15 mb-30"><h5>View promo video</h5></div>
              </div>
            </div>
          </div></div>
        </div>
      </div>

      <!-- RIGHT TEXT -->
      <div class="col-md-6 valign animate-box" data-animate-effect="fadeInRight">
        <div class="content">
          <div class="cont">
            <?php if($welcome_p1): ?>
              <p><?= nl2br(htmlspecialchars($welcome_p1)) ?></p>
            <?php endif; ?>
            <a href="about.php" class="more" data-splitting=""><span>View More</span></a>
          </div>
        </div>
      </div>
    </div>

    <!-- ROW 2: Left text + Right image -->
    <div class="row mb-60">
      <!-- LEFT TEXT -->
      <div class="col-md-6 order2 valign animate-box" data-animate-effect="fadeInLeft">
        <div class="content">
          <div class="cont">
            <?php if($welcome_p2): ?>
              <p><?= nl2br(htmlspecialchars($welcome_p2)) ?></p>
            <?php else: ?>
              <p>Our unique resort-like ambiance within the warmth of a home offers an unparalleled escape amidst serene surroundings—a treat for nature lovers seeking tranquility.</p>
              <p>Discover a haven where each moment is adorned with the beauty of simplicity…</p>
            <?php endif; ?>
            <a href="about.php" class="more" data-splitting=""><span>View More</span></a>
          </div>
        </div>
      </div>

      <!-- RIGHT IMAGE -->
      <div class="col-md-6 animate-box" style="width: 100vw;" data-animate-effect="fadeInLeft">
        <div class="img left">
          <a href="#">
            <img src="<?= $welcome_img2 ? 'media/home/'.htmlspecialchars($welcome_img2) : 'img/slider/4.jpg' ?>" alt="">
          </a>
        </div>
      </div>
    </div>

  </div>
</section>

    <!-- CTA cards (DYNAMIC text + links) -->
    <section class="bauen-blog section-padding2">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <div class="item" style="padding-right:15px;background-color:#d3be7d36;padding:20px;">
              <div class="content"><div class="cont">
                <h2 class="section-title" style="font-size:23px;letter-spacing:normal;"><?= htmlspecialchars($cta_left_title) ?></h2>
                <p style="font-size:20px;"><b><?= nl2br(htmlspecialchars($cta_left_text)) ?></b></p>
                <a style="width:100%;" href="<?= htmlspecialchars($cta_left_btn_link) ?>">
                  <p style="text-align:center;font-family: revert-layer;font-weight:800;background-color:#b19777;color:#fff;width:35%;border-radius:10px;padding:7px;margin:0 auto;">
                    <?= htmlspecialchars($cta_left_btn_text) ?>
                  </p>
                </a>
              </div></div>
            </div>
          </div>

          <div class="col-md-6" style="background-color:#d3be7d36;padding:20px;margin-bottom:30px;">
            <div class="item" style="padding-left:15px;">
              <div class="content"><div class="cont">
                <h2 class="section-title" style="font-size:23px;letter-spacing:normal;"><?= htmlspecialchars($cta_right_title) ?></h2>
                <p style="font-size:20px;"><b><?= nl2br(htmlspecialchars($cta_right_text)) ?></b></p>
                <a style="width:100%;" href="<?= htmlspecialchars($cta_right_btn_link) ?>">
                  <p style="text-align:center;font-family: revert-layer;font-weight:800;background-color:#b19777;color:#fff;width:35%;border-radius:10px;padding:7px;margin:0 auto;">
                    <?= htmlspecialchars($cta_right_btn_text) ?>
                  </p>
                </a>
              </div></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Your existing static Gallery, Nearby attractions, Testimonials keep as-is -->
    <!-- (you can also convert them to DB later like we did above) -->
<?php
$gallery = $conn->query("SELECT image FROM home_gallery ORDER BY sort_order, id DESC LIMIT 3");
?>
<section class="section-padding2">
  <div class="container">
    <div class="row">
      <div class="col-md-12"><h2 class="section-title">G<span>allery</span></h2></div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="row mb-30">
          <?php if($gallery && $gallery->num_rows): ?>
            <?php while($g=$gallery->fetch_assoc()): ?>
              <div class="col-md-4 gallery-item">
                <a href="<?= 'media/home/'.htmlspecialchars($g['image']) ?>" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="<?= 'media/home/'.htmlspecialchars($g['image']) ?>" class="img-fluid mx-auto d-block" alt="Gallery">
                    </div>
                  </div>
                </a>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Fallback if no images yet -->
            <div class="col-md-4 gallery-item"><img src="img/gallery/11.jpg" class="img-fluid" alt=""></div>
            <div class="col-md-4 gallery-item"><img src="img/gallery/12.jpg" class="img-fluid" alt=""></div>
            <div class="col-md-4 gallery-item"><img src="img/gallery/15_1.jpg" class="img-fluid" alt=""></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="butn-dark mt-20">
        <a href="photo.php"><span>view more</span></a>
      </div>
    </div>
  </div>
</section>


<?php
$attractions = $conn->query("SELECT title, image FROM home_attractions ORDER BY sort_order, id DESC");
?>
<section class="bauen-blog section-padding">
  <div class="container">
    <div class="row"><div class="col-md-12">
      <h2 class="section-title">Nearby <span> attractions</span></h2>
    </div></div>

    <div class="row">
      <div class="col-md-12">
        <div class="owl-carousel owl-theme">
          <?php if($attractions && $attractions->num_rows): ?>
            <?php while($a=$attractions->fetch_assoc()): ?>
              <div class="item">
                <div class="position-re o-hidden">
                  <img src="<?= 'media/home/'.htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
                </div>
                <div class="con">
                  <h5><a><?= htmlspecialchars($a['title']) ?></a></h5>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Fallback items -->
            <div class="item">
              <div class="position-re o-hidden"><img src="img/slider/Periyar.jpg" alt=""></div>
              <div class="con"><h5><a>River Periyar</a></h5></div>
            </div>
            <div class="item">
              <div class="position-re o-hidden"><img src="img/slider/Mahagony.jpg" alt=""></div>
              <div class="con"><h5><a>Mahagony Thottam</a></h5></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="butn-dark mt-20">
      <a href="vicinity.php"><span>view more</span></a>
    </div>
  </div>
</section>


<?php
$testis = $conn->query("SELECT name, quote, photo FROM home_testimonials ORDER BY sort_order, id DESC");
?>
<section class="testimonials">
  <div class="background bg-img bg-fixed section-padding pb-0" data-background="img/slider/2.jpg" data-overlay-dark="3">
    <div class="container">
      <div class="row">
        <!-- Left: promo video (unchanged) -->
        <div class="col-md-6">
          <div class="vid-area">
            <div class="vid-icon">
              <a class="play-button vid" href="https://www.youtube.com/watch?v=cutLiSFgjKY">
                <svg class="circle-fill"><circle cx="43" cy="43" r="39" stroke="#fff" stroke-width=".5"></circle></svg>
                <svg class="circle-track"><circle cx="43" cy="43" r="39" stroke="none" stroke-width="1" fill="none"></circle></svg>
                <span class="polygon"><i class="ti-control-play"></i></span>
              </a>
            </div>
            <div class="cont mt-15 mb-30"><h5>KANAVU HERITAGE FULL VIDEO</h5></div>
          </div>
        </div>

        <!-- Right: testimonials -->
        <div class="col-md-5 offset-md-1">
          <div class="testimonials-box animate-box" data-animate-effect="fadeInUp">
            <div class="head-box"><h4>Guest Testimonials</h4></div>
            <div class="owl-carousel owl-theme">
              <?php if($testis && $testis->num_rows): ?>
                <?php while($t=$testis->fetch_assoc()): ?>
                  <div class="item">
                    <span class="quote"><img src="img/quot.png" alt=""></span>
                    <p><?= nl2br(htmlspecialchars($t['quote'])) ?></p>
                    <div class="info">
                      <div class="author-img">
                        <?php if(!empty($t['photo'])): ?>
                          <img src="<?= 'media/home/'.htmlspecialchars($t['photo']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                        <?php else: ?>
                          <img src="img/team/1.jpg" alt="">
                        <?php endif; ?>
                      </div>
                      <div class="cont">
                        <h6><?= htmlspecialchars($t['name']) ?></h6>
                        <span></span>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <!-- Fallback testimonial -->
                <div class="item">
                  <span class="quote"><img src="img/quot.png" alt=""></span>
                  <p>Welcome to Kanavu — your serene escape.</p>
                  <div class="info">
                    <div class="author-img"><img src="img/team/1.jpg" alt=""></div>
                    <div class="cont"><h6>Guest</h6></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- row -->
    </div><!-- container -->
  </div><!-- bg -->
</section>


    <?php include('footer.php'); ?>
  </div>

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

  <script>
    $(function() {
      $('.bannerSlider').owlCarousel({
        items: 1, loop:true, dots:false, margin:0, autoplay:true, smartSpeed:500,
        nav:true, navText:['<i class="ti-angle-left" aria-hidden="true"></i>','<i class="ti-angle-right" aria-hidden="true"></i>']
      });
      setTimeout(()=>{$('body').css('--headerH',document.querySelector('.bauen-header')?.clientHeight+"px");},100);
    });
  </script>
</body>
</html>
