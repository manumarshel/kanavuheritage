<?php
// /kanav/celebrations.php  — TOP + <head> (SEO-ready)

// 1) DB connect
require __DIR__ . '/includes/connect.php';

// 2) SEO helper loader (prefer /includes, fallback to /dashboard)
$ROOT = __DIR__;
$INC  = $ROOT . '/includes';
$DASH = $ROOT . '/dashboard';
$SEO_OK = false;
if (is_file($INC . '/seo_meta.php')) { require_once $INC . '/seo_meta.php'; $SEO_OK = true; }
elseif (is_file($DASH . '/seo_meta.php')) { require_once $DASH . '/seo_meta.php'; $SEO_OK = true; }
if (!$SEO_OK) {
  // Tiny fallback so page still loads
  function kh_base_url(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($proto.$host, '/');
  }
  function kh_abs_url(string $rel): string {
    if (preg_match('~^https?://~i', $rel)) return $rel;
    return kh_base_url() . '/' . ltrim($rel, '/');
  }
  function kh_build_meta(mysqli $conn, string $slug, string $fb_title='', string $fb_desc='', string $fb_canon=''): array {
    return [
      'title'       => $fb_title ?: 'Celebrate at Kanavu Heritage | Events, Parties & Gatherings in Kerala',
      'description' => $fb_desc  ?: 'Host intimate celebrations at Kanavu Heritage — cozy spaces, warm ambience, and curated experiences for birthdays, anniversaries, and family get-togethers.',
      'canonical'   => kh_abs_url($fb_canon ?: '/kanav/celebrations'),
      'og_image'    => kh_abs_url('img/og-default.jpg'),
    ];
  }
}

// 3) Build SEO meta correctly (slug = celebrations)
$META = kh_build_meta(
  $conn,
  'celebrations', // ✅ use slug, NOT filename
  'Celebrate at Kanavu Heritage | Events, Parties & Gatherings in Kerala',
  'Host intimate celebrations at Kanavu Heritage — cozy spaces, warm ambience, and curated experiences for birthdays, anniversaries, and family get-togethers.',
  '/kanav/celebrations' // ✅ pretty canonical (no .php)
);
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- ✅ Make all relative paths resolve from /kanav/ -->


  <!-- SEO (dynamic via dashboard with safe fallbacks) -->
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
  <link rel="stylesheet" href="whatsapp_style.css" />

  <!-- Analytics -->
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
</head>


<body>
<?php include('header.php');?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

  <!-- partial:index.partial.html -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<a href="https://wa.link/3b9yly" class="float" target="_blank">
<i class="fa fa-whatsapp my-float"></i>
</a>
<!-- partial -->

    <!-- Content -->
    <div class="content-wrapper">
        <!-- Lines -->
        <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
            <div class="content-lines-inner">
                <div class="content-lines"></div>
            </div>
        </section>
        <!-- Header Banner -->
        <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg"> </section>
        <!-- About -->
	 <!-- About -->

    <section class="bauen-blog section-padding2">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
              <h2 class="section-title" style="text-align:center">CELEBRATIONS</span>
            </h2>
            <h2 class="section-title" style="text-align:center"><span>KANAVU : Your Premier Venue for Intimate Celebrations</span>
            </h2>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="item">
              <div class="position-re o-hidden">
                <img src="img/slider/h1.jpg" alt="">
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="item">
              <div class="content">
                <div class="cont">
                  <p style="font-size:20px; ">
                    <b>Whether it's a family reunion, pre/post-wedding events, 
Shoots or corporate retreats, our dedicated team is poised 
to assist you in creating unforgettable events set against the 
backdrop of our heritage homestay.</b>
                  </p>
                  <p style="font-size:16px; ">
                    <b> Bathed in natural sunlight and equipped with camera-friendly lighting, the space is tailored for seamless 
photoshoots. Guests desiring extra lighting can bring their 
own power source such as generator, ensuring flexibility for 
any event.</b>
                  </p>
                  
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="item">
              <div class="content">
                <div class="cont">
                  <p style="font-size:20px; ">
                    <b>Centrally located and well-connected, Kanav ensures easy 
access to all major transportation hubs. </b>
                  </p>
                  <p style="font-size:16px; ">
                    <b> Our prime location provides easy access to essential amenities 
like supermarkets, food joints, Temples, Churches, hospitals, 
Theatre, Bar, Banks, ATMs, and more. </b>
                  </p>
                  <p style="font-size:18px; ">
                    <b>Your convenience is our priority, ensuring every aspect of your 
stay is effortlessly enjoyable.</b>
                  </p>
<!--                  <a href="about" class="more" data-splitting="">-->
<!--                    <span style="-->
<!--    font-weight: 600;-->
<!--    background-color: #b19777;-->
<!--    padding: 5px 10px 5px 10px;-->
<!--    border-radius: 7px;-->
<!--    color: white;-->
<!--">Read More</span>-->
<!--                  </a>-->
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="item">
              <div class="position-re o-hidden">
                <img src="img/slider/h2.jpg" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-padding2">
      <div class="container">
        <div class="row">
          <!--<div class="col-md-12">-->
          <!--  <h2 class="section-title">G<span>allery</span>-->
          <!--  </h2>-->
          <!--</div>-->
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-30">
              
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/1.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/1.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/3.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/3.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
             
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/4.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/4.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/5.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/5.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/6.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/6.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/7.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/7.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/8.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/8.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
               
              <div class="col-md-4 gallery-item">
                <a href="img/celebration/2.jpg" title="Decor Plan" class="img-zoom">
                  <div class="gallery-box">
                    <div class="gallery-img">
                      <img src="img/celebration/2.jpg" class="img-fluid mx-auto d-block" alt="Decor Plan">
                    </div>
                  </div>
                </a>
              </div>
              
            </div>
          </div>
          <!--<div class="butn-dark mt-20">-->
          <!--  <a href="photo">-->
          <!--    <span>view more</span>-->
          <!--  </a>-->
          <!--</div>-->
        </div>
      </div>
    </section>
		                                                                  
   
        <!-- Clients -->
      
        <!-- Footer -->
        <?php include('footer.php');?>
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
	
		    <!-- Vegas Background Slideshow (vegas.slider kenburns) -->
    <script>
        $(document).ready(function () {
            $('#kenburnsSliderContainer').vegas({
                slides: [{
                    src: "img/slider/6.jpg"
                }, {
                    src: "img/slider/7.jpg"
                }, {
                    src: "img/slider/8.jpg"
                }]
                , overlay: true
                , transition: 'fade2'
                , animation: 'kenburnsUpRight'
                , transitionDuration: 1000
                , delay: 10000
                , animationDuration: 20000
            });
        });
    </script>
	
</body>

</html>
