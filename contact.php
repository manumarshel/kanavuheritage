<?php
// /kanav/contact.php — hardened version (no hard failure if DB/SEO helpers are missing)
session_start();

// CSRF token for the form
if (empty($_SESSION['contact_csrf'])) {
  $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

// Preserve previously entered values (when redirected back with ?err=…)
$err    = isset($_GET['err']) ? trim($_GET['err']) : '';
$values = [
  'name'    => isset($_GET['v_name'])    ? htmlspecialchars($_GET['v_name'],    ENT_QUOTES, 'UTF-8') : '',
  'email'   => isset($_GET['v_email'])   ? htmlspecialchars($_GET['v_email'],   ENT_QUOTES, 'UTF-8') : '',
  'phone'   => isset($_GET['v_phone'])   ? htmlspecialchars($_GET['v_phone'],   ENT_QUOTES, 'UTF-8') : '',
  'city'    => isset($_GET['v_city'])    ? htmlspecialchars($_GET['v_city'],    ENT_QUOTES, 'UTF-8') : '',
  'enquiry' => isset($_GET['v_enquiry']) ? htmlspecialchars($_GET['v_enquiry'], ENT_QUOTES, 'UTF-8') : '',
  'message' => isset($_GET['v_message']) ? htmlspecialchars($_GET['v_message'], ENT_QUOTES, 'UTF-8') : '',
];

// ---------- Optional DB + SEO helper (graceful fallback) ----------
$conn = null;
$connect_file = __DIR__ . '/includes/connect.php';
if (is_file($connect_file)) {
  // If connect.php fatals in your environment, comment the next line:
  require $connect_file;
  if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
    @$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
  } else {
    $conn = null; // don’t break the page if connect fails
  }
}

$META = [
  'title'       => 'Contact Kanavu Heritage — Reach Us in Kerala',
  'description' => 'Get in touch with Kanavu Heritage for inquiries and reservations via email at mail@kanavuheritage.com or call +91-95670-47633.',
  'canonical'   => '/kanav/contact', // pretty canonical
  'og_image'    => '/kanav/img/og-default.jpg',
];

// Try to load SEO overrides if helper exists
$seo_helper = __DIR__ . '/includes/seo_meta.php';
if (is_file($seo_helper)) {
  require_once $seo_helper;
  if (function_exists('kh_build_meta') && $conn) {
    $META = kh_build_meta(
      $conn,
      'contact',                 // slug used in your dashboard seo_pages
      $META['title'],
      $META['description'],
      $META['canonical']
    );
  }
}

// tiny esc helper
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

  <!-- Make all relative paths load correctly from /kanav/ -->


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

  <style>
    .alert {padding:10px 12px;border-radius:10px;margin:10px 0}
    .alert-err{background:#fff5f5;border:1px solid #f3c2c2;color:#8a1f1f}
  </style>

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
  <?php
  // Include your site header/nav if present; this file should NOT output <html> or <head> again.
  $hdr = __DIR__ . '/header.php';
  if (is_file($hdr)) include $hdr;
  ?>

  <!-- GTM noscript -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NP7DBK64" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <!-- WhatsApp FAB -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <a href="https://wa.link/3b9yly" class="float" target="_blank"><i class="fa fa-whatsapp my-float"></i></a>

  <div class="content-wrapper">
    <!-- Lines -->
    <section class="content-lines-wrapper" data-background="img/gallery/01.jpg">
      <div class="content-lines-inner"><div class="content-lines"></div></div>
    </section>

    <!-- Header Banner -->
    <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg"></section>

    <section class="section-padding2">
      <div class="container">

        <?php if($err): ?>
          <div class="alert alert-err"><?= e($err) ?></div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-12 animate-box" data-animate-effect="fadeInUp">
            <h2 class="section-title">Contact Us</h2>
          </div>
        </div>

        <h5>For swift correspondence, the contact information for Kanavu Heritage is provided below. Plan your perfect getaway and unwind at our peaceful sanctuary.</h5>

        <div class="bauen-blog5 left animate-box" data-animate-effect="fadeInUp">
          <figure>
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15706.15093506045!2d76.4457697!3d10.2181437!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b0807f4398e3a11%3A0xa449b16ba72338a4!2sKanavu%20Heritage!5e0!3m2!1sen!2sin!4v1707354825057!5m2!1sen!2sin"
              width="100%" height="500" style="border:0;" allowfullscreen="" loading="lazy" class="map"></iframe>
          </figure>
          <br>
          <h5>Note: <b>Kanavu is just 20 minutes away from Cochin International Airport &amp; Angamaly Railway Station</b></h5>
          <div class="caption">
            <h4><a href="#">GET IN TOUCH</a></h4>
            <h5><b>Phone :</b> <a href="tel:+919567047633">+91 95670 47633</a></h5>
            <h5><b>Best time to call :</b> 10:00 AM – 5:30 PM</h5>
            <h5><b>Email :</b>
              <a href="mailto:mail@kanavuheritage.com">mail@kanavuheritage.com</a>
              &nbsp;&nbsp;<a href="mailto:kanavuheritage@gmail.com">kanavuheritage@gmail.com</a>
            </h5>
            <hr class="border-2">
            <h4><a href="#">Kanavu Heritage</a></h4>
            <h5><b>11/21/A, Mariyapuram, Manjapra, Angamaly<br>Ernakulam, Kerala — India</b></h5>
          </div>
        </div>
      </div>

      <!-- Enquiry Form -->
      <section class="contact-section py-5">
        <div class="container">
          <div class="row"><div class="col-12">
            <h2 class="text-center mb-4" style="color:#b19777;font-weight:bold;">Enquire Now</h2>

            <!-- IMPORTANT: use relative action; <base> will handle /kanav/ -->
            <form id="contactForm" action="mail/send_mail.php" method="POST" class="p-4 border rounded bg-light" autocomplete="on">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['contact_csrf']) ?>">
              <!-- honeypot -->
              <input type="text" name="hp_url" value="" style="display:none" tabindex="-1" autocomplete="off">

              <div class="mb-3">
                <label for="name" class="form-label">Name*</label>
                <input type="text" class="form-control" name="name" id="name"
                       value="<?= $values['name'] ?>" required minlength="2" maxlength="120" autocomplete="name">
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email*</label>
                <input type="email" class="form-control" name="email" id="email"
                       placeholder="you@example.com" value="<?= $values['email'] ?>" required maxlength="160" autocomplete="email">
              </div>

              <div class="mb-3">
                <label for="phone" class="form-label">Phone Number*</label>
                <input type="tel" class="form-control" name="phone" id="phone"
                       pattern="[0-9]{10}" inputmode="numeric" minlength="10" maxlength="10"
                       placeholder="10 digit number" value="<?= $values['phone'] ?>" required autocomplete="tel">
              </div>

              <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" name="city" id="city"
                       value="<?= $values['city'] ?>" maxlength="100" autocomplete="address-level2">
              </div>

              <div class="mb-3">
                <label for="enquiry" class="form-label">Type of Enquiry*</label>
                <select name="enquiry" id="enquiry" class="form-select" required>
                  <option value="">-- Select --</option>
                  <option value="One Day Stay"  <?= $values['enquiry']==='One Day Stay'?'selected':''; ?>>One Day Stay</option>
                  <option value="One Week Stay" <?= $values['enquiry']==='One Week Stay'?'selected':''; ?>>One Week Stay</option>
                  <option value="Photoshoot"    <?= $values['enquiry']==='Photoshoot'?'selected':''; ?>>Photoshoot</option>
                  <option value="Others"        <?= $values['enquiry']==='Others'?'selected':''; ?>>Others</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="message" class="form-label">Message / Enquiry Details</label>
                <textarea name="message" id="message" class="form-control" rows="4" maxlength="2000"><?= $values['message'] ?></textarea>
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-primary px-4" id="submitBtn">Submit</button>
              </div>
            </form>
          </div></div>
        </div>
      </section>
    </section>

    <?php
      $ftr = __DIR__ . '/footer.php';
      if (is_file($ftr)) include $ftr;
    ?>
  </div>

  <!-- JS (relative; <base> fixes paths) -->
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
    // Prevent double-submit UX nicety
    (function () {
      var form = document.getElementById('contactForm');
      var btn  = document.getElementById('submitBtn');
      if (!form || !btn) return;
      form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerText = 'Submitting...';
      });
    })();
  </script>
</body>
</html>
