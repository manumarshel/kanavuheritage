<?php
// /kanav/booking.php — Website Booking Form (no login)
session_start();

// CSRF token for the booking form
if (empty($_SESSION['booking_csrf'])) {
  $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

// ---------- DB + SEO helper ----------
$conn = null;
$connect_file = __DIR__ . '/includes/connect.php';
if (is_file($connect_file)) {
  require $connect_file;
  if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
    @$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
  } else {
    $conn = null;
  }
}

// tiny esc helper
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Fetch packages for dropdown
$packages = [];
if ($conn) {
  $res = $conn->query("SELECT id, name, price, duration FROM packages ORDER BY id ASC");
  while ($row = $res->fetch_assoc()) {
    $packages[] = $row;
  }
}

// 🔹 Fetch latest 10 booked date ranges (pending/approved) + package name
$bookedDates = [];
if ($conn) {
  $sql = "SELECT b.check_in,
                 b.check_out,
                 b.booking_status,
                 p.name AS package_name
          FROM bookings b
          LEFT JOIN packages p ON p.id = b.package_id
          WHERE b.booking_status IN ('pending','approved')
          ORDER BY b.check_in DESC, b.id DESC
          LIMIT 10";
  if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
      $bookedDates[] = $row;
    }
    $res->free();
  }
}
$totalBookings = count($bookedDates);

// 🔹 Find free gaps between booked dates + “available from” date
$availabilityGaps = [];
$availableFromText = '';

if ($totalBookings > 0) {
    // Sort ascending by check_in to detect gaps
    $sorted = $bookedDates;
    usort($sorted, function($a, $b) {
        return strcmp($a['check_in'], $b['check_in']);
    });

    $prevOut = null;
    foreach ($sorted as $row) {
        $in  = new DateTime($row['check_in']);
        $out = new DateTime($row['check_out']);

        if ($prevOut !== null) {
            // Check if there's a gap: previous checkout < next check-in
            if ($prevOut < $in) {
                // Free range: from prevOut to one day before next check-in.
                $gapStart = clone $prevOut;
                $gapEnd   = (clone $in)->modify('-1 day');

                if ($gapStart <= $gapEnd) {
                    $availabilityGaps[] = [
                        'start' => $gapStart->format('d-m-Y'),
                        'end'   => $gapEnd->format('d-m-Y'),
                    ];
                }
            }

            // Extend prevOut if this booking ends later
            if ($out > $prevOut) {
                $prevOut = clone $out;
            }
        } else {
            // First booking's checkout
            $prevOut = clone $out;
        }
    }

    // From this date, bookings are generally available (subject to older records)
    $today = new DateTime('today');
    $availableFromDT = $prevOut ?: $today;
    if ($availableFromDT < $today) {
        $availableFromDT = $today;
    }
    $availableFromText = $availableFromDT->format('d-m-Y');
}

// basic meta
$META = [
  'title'       => 'Book Your Stay — Kanavu Heritage',
  'description' => 'Book Kanavu Heritage stay, photo shoot, or events directly from the website. You will be redirected to PhonePe to complete payment. Successful payments are auto-approved.',
  'canonical'   => '/kanav/booking',
  'og_image'    => '/kanav/img/og-default.jpg',
];

// Optional SEO override
$seo_helper = __DIR__ . '/includes/seo_meta.php';
if (is_file($seo_helper) && $conn) {
  require_once $seo_helper;
  if (function_exists('kh_build_meta')) {
    $META = kh_build_meta(
      $conn,
      'booking',                 // slug in your SEO table if you add one
      $META['title'],
      $META['description'],
      $META['canonical']
    );
  }
}
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
  <!-- Booking page specific styles -->
  <link rel="stylesheet" href="css/booking.css" />

  <!-- Page-level override for wider container on large screens -->
  <style>
    @media (min-width: 1200px) {
      .container { max-width: 1400px !important; }
    }
    /* Make booking action links look like booking-type pills */
    .btn-booking {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 8px 14px !important;
      border-radius: 24px !important;
      background: #f7f7f7 !important;
      border: 1px solid #e0e0e0 !important;
      color: #222 !important;
      text-decoration: none !important;
      min-width: 140px !important;
    }
    
    /* FullCalendar Dark Theme Overrides */
    #booking-calendar {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid #333;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .fc-scrollgrid-sync-inner {
    color: black !important;
}.fc-event-title.fc-sticky {
    line-height: 1.2;
    font-size: 10px !important;
    word-break: break-all;
}
    .fc-theme-standard th { border-color: #333 !important; }
    .fc-theme-standard td { border-color: #333 !important; }
    .fc-theme-standard .fc-scrollgrid { border-color: #333 !important; border-radius: 6px; overflow: hidden; }
    .fc .fc-toolbar-title {
      color: #d3be7d;
      font-size: 1.25rem;
      font-weight: 600;
    }
    .fc .fc-button-primary {
      background-color: #222 !important;
      border-color: #d3be7d !important;
      color: #d3be7d !important;
      text-transform: capitalize;
    }
    .fc .fc-button-primary:hover {
      background-color: #d3be7d !important;
      color: #111 !important;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active, 
    .fc .fc-button-primary:not(:disabled):active {
      background-color: #d3be7d !important;
      color: #111 !important;
    }
    .fc .fc-daygrid-day-number { color: #eee; text-decoration: none; padding: 4px 8px; }
    .fc-event { border: none !important; border-radius: 4px; padding: 2px 5px; font-size: 0.75rem; text-align: center; white-space: normal !important; word-wrap: break-word; overflow: visible !important; }
    .fc-event-title { white-space: normal !important; overflow: visible !important; }
    .fc .fc-day-today { background: rgba(211, 190, 125, 0.1) !important; }
    .fc .fc-cell-shaded, .fc .fc-day-disabled { background: transparent; }
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
  // header (same as contact.php)
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
    <section class="banner-header banner-img valign bg-img bg-fixed" data-overlay-darkgray="5" data-background="img/gallery/01.jpg">
    </section>

    <section class="section-padding2" id="booking-section">
      <div class="container">
        <div class="row">
          <div class="col-md-12 animate-box" data-animate-effect="fadeInUp">
            <h2 class="section-title">Book Your Stay</h2>
          </div>
        </div>

        <h5>
          Reserve your Kanavu Heritage stay, photoshoot, or exclusive event. After you submit this form,
          you will be redirected to <strong>PhonePe</strong> to complete payment.
          Once payment is successful, your booking will be <strong>auto-approved</strong>.
        </h5>

        <!-- ✅ FLASH MESSAGE (PhonePe return redirect will show here) -->
        <?php if (!empty($_SESSION['booking_flash_msg'])): ?>
          <div style="margin:12px 0; padding:12px 14px; border-radius:10px;
                      background: <?php echo ($_SESSION['booking_flash_type'] ?? '') === 'success' ? '#d1f7d6' : '#ffd6d6'; ?>;
                      color:#111; font-weight:600;">
            <?php echo htmlspecialchars($_SESSION['booking_flash_msg']); ?>
          </div>
          <?php unset($_SESSION['booking_flash_msg'], $_SESSION['booking_flash_type']); ?>
        <?php endif; ?>
        <!-- ✅ END FLASH MESSAGE -->

      </div>

      <!-- Booking Form + Availability -->
      <section class="contact-section py-5">
        <div class="container">
          <div class="row">
            <div class="col-12 booking-form-shell">

              <!-- LEFT: Booked dates (latest 10) -->
              <div class="availability-panel">
                <div class="availability-panel-header">
                  <h3 class="availability-title">Booked / Unavailable Dates</h3>
                  <p class="availability-subtitle">
                    Latest confirmed or pending stays (up to 10 entries).<br>
                    <small>If your preferred date is <strong>not listed below</strong>, it is currently available to request
                    (final confirmation after our manual review).</small>
                  </p>
                </div>
                <div class="availability-body">
                  <?php if ($totalBookings > 0): ?>
                    <div id="booking-calendar"></div>
                  <?php else: ?>
                    <div class="availability-empty">
                      No upcoming bookings found. All dates are currently available to request.
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- RIGHT: Booking Panel -->
              <div class="booking-panel">
                <div class="booking-tabs">
                  <div class="booking-tab active">Booking enquiry</div>
                </div>

                <div class="booking-form-inner">
                  <form id="bookingForm" action="booking_create.php" method="POST" autocomplete="on">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['booking_csrf']) ?>">
                    <input type="hidden" name="booking_source" value="web">
                    <!-- honeypot -->
                    <input type="text" name="hp_url" value="" style="display:none" tabindex="-1" autocomplete="off">

                    <div class="mb-3">
                      <label for="user_name" class="form-label">Full Name*</label>
                      <input type="text" class="form-control" name="user_name" id="user_name"
                             required minlength="2" maxlength="120" autocomplete="name">
                    </div>

                    <div class="mb-3">
                      <label for="user_phone" class="form-label">WhatsApp / Contact Number*</label>
                      <input type="tel" class="form-control" name="user_phone" id="user_phone"
                             pattern="[0-9]{10}" inputmode="numeric" minlength="10" maxlength="10"
                             placeholder="10 digit number" required autocomplete="tel">
                    </div>

                    <div class="mb-3">
                      <label for="user_email" class="form-label">Email (optional)</label>
                      <input type="email" class="form-control" name="user_email" id="user_email"
                             placeholder="you@example.com" maxlength="160" autocomplete="email">
                    </div>

                    <div class="mb-3">
                      <label class="form-label d-block">Booking Type*</label>
                      <div class="booking-type-pills">
                        <label class="booking-type-pill">
                          <input type="radio" name="stay_type" value="stay" checked>
                          <span>Stay</span>
                        </label>
                        <!-- <label class="booking-type-pill" >
                          <input type="radio" name="stay_type" value="shoot">
                          <span>Photo Shoot</span>
                        </label> -->
                        <label class="booking-type-pill">
                          <input type="radio" name="stay_type" value="event">
                          <span>Event</span>
                        </label>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="package_id" class="form-label">Select Package*</label>
                      <select name="package_id" id="package_id" class="form-select" required>
                        <option value="">-- Choose a package --</option>
                        <?php $idx = 0; foreach ($packages as $pkg): $idx++; ?>
                          <?php
                            // keep data-types heuristics for backward compatibility
                            $types = [];
                            $n = strtolower($pkg['name'] ?? '');
                            if (strpos($n, 'stay') !== false || strpos($n, 'room') !== false || strpos($n, 'night') !== false) $types[] = 'stay';
                            if (strpos($n, 'shoot') !== false || strpos($n, 'photo') !== false) $types[] = 'shoot';
                            if (strpos($n, 'event') !== false || strpos($n, 'celebr') !== false || strpos($n, 'wedding') !== false) $types[] = 'event';
                            if (empty($types)) $types = ['stay','shoot','event'];
                          ?>
                          <option value="<?= (int)$pkg['id'] ?>" data-types="<?= e(implode(',', $types)) ?>" data-idx="<?= $idx ?>">
                            <?= e($pkg['name']) ?> — ₹<?= number_format($pkg['price'], 2) ?>
                            (<?= e($pkg['duration']) ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="num_people" class="form-label">Number of people staying*</label>
                      <input type="number" class="form-control" name="num_people" id="num_people"
                             value="1" min="1" max="8" placeholder="Max 8 people per package" required>
                    </div>

                    <div class="mb-3 d-flex" style="gap:8px; flex-wrap:wrap;">
                      <a href="dining.php" class="btn-booking" target="_blank" style="text-decoration:none;">
                        View Food Menu
                      </a>
                      <a href="packages.php" class="btn-booking" target="_blank" style="text-decoration:none;">
                        Extra Bed
                      </a>
                      <a href="terms&conditions.php" class="btn-booking" target="_blank" style="text-decoration:none;">
                        Terms & Conditions
                      </a>
                    </div>

                    <div class="mb-3 small-text">
                      Enquire menu details and extra bed facility by contacting Kanav Heritage using the WhatsApp button below.
                      <br>
                      <a href="https://wa.me/919567047633?text=Hello%20Kanav%20Heritage%2C%20I%20have%20a%20query%20about%20booking" target="_blank" class="btn-booking" style="margin-top:8px; display:inline-flex; align-items:center;">
                        <i class="fa fa-whatsapp" style="margin-right:8px"></i> WhatsApp Us
                      </a>
                    </div>

                    <div class="mb-3">
                      <label for="check_in" class="form-label">Preferred Check-in Date*</label>
                      <input type="date" class="form-control" name="check_in" id="check_in" required>
                    </div>

                    <div id="statusBox" class="alert" style="display:none"></div>

                    <button type="submit" class="btn-booking" id="submitBtn">
                      Submit Booking
                    </button>
                  </form>
                </div>
              </div>

            </div>

            <?php if ($totalBookings > 0): ?>
              <div class="col-12 mt-3">
                <p class="availability-note">
                  <?php if (!empty($availabilityGaps)): ?>
                    <strong>Upcoming free gaps between current bookings:</strong>
                    <?php foreach ($availabilityGaps as $gap): ?>
                      <br>• <?= e($gap['start']) ?> to <?= e($gap['end']) ?>
                    <?php endforeach; ?>
                    <br>
                  <?php endif; ?>

                  <?php if ($availableFromText): ?>
                    <strong>Bookings are generally available from <?= e($availableFromText) ?> onwards</strong>
                    (subject to final confirmation and any earlier reservations not listed here).
                  <?php endif; ?>
                </p>
              </div>
            <?php endif; ?>

          </div>
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
  
  <!-- FullCalendar JS -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var calendarEl = document.getElementById('booking-calendar');
      if (calendarEl) {
        // PHP array to JS array
        var bookedDates = <?php echo json_encode($bookedDates); ?>;
        
        var events = bookedDates.map(function(b) {
          var color = (b.booking_status === 'approved') ? '#d3be7d' : '#888888';
          var textColor = (b.booking_status === 'approved') ? '#111' : '#fff';
          var statusCap = b.booking_status ? (b.booking_status.charAt(0).toUpperCase() + b.booking_status.slice(1)) : 'Pending';
          var titleText = (b.package_name ? b.package_name : 'Booked') + ' - ' + statusCap;
          
          return {
            title: titleText,
            start: b.check_in,
            end: b.check_out, // exclusive end date, perfect for checkouts
            backgroundColor: color,
            textColor: textColor,
            allDay: true
          };
        });
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
          },
          events: events,
          height: 'auto',
          contentHeight: 'auto',
          aspectRatio: 1.35,
          eventDisplay: 'block',
          dayCellDidMount: function(info) {
            // Get cell date as YYYY-MM-DD
            var d = info.date;
            var cellDateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            
            // Check if this date falls within any booking
            for (var i = 0; i < bookedDates.length; i++) {
              var b = bookedDates[i];
              var inDate = b.check_in.substring(0, 10);
              var outDate = b.check_out.substring(0, 10);
              
              if (cellDateStr >= inDate && cellDateStr < outDate) {
                // Change background of the entire cell for booked dates
                info.el.style.backgroundColor = 'rgba(211, 190, 125, 0.15)'; // light gold tint
                break;
              }
            }
          }
        });
        calendar.render();
      }
    });
  </script>
  <script>
    (function(){
      // Filter package <select> options by predefined index ranges per stay_type
      function rebuildOptionsFor(type) {
        var select = document.getElementById('package_id');
        if (!select) return;
        // allOptions captured on init
        var all = window.__allPackageOptions || [];
        var allowedIdx = [];
        var maxIdx = all.reduce(function(m, o){ return Math.max(m, o.idx || 0); }, 0);
        if (type === 'stay') {
          allowedIdx = [1,2,3,4];
        } else if (type === 'shoot') {
          allowedIdx = [5,6,7];
        } else if (type === 'event') {
          // 8th through last
          for (var i = 8; i <= maxIdx; i++) allowedIdx.push(i);
        }

        // rebuild select: keep placeholder (value=="") then append allowed
        select.innerHTML = '';
        // placeholder
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.text = '-- Choose a package --';
        select.appendChild(placeholder);

        var added = 0;
        for (var i = 0; i < all.length; i++) {
          var o = all[i];
          if (allowedIdx.indexOf(o.idx) !== -1) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.text = o.text;
            if (o.types) opt.setAttribute('data-types', o.types);
            opt.setAttribute('data-idx', o.idx);
            select.appendChild(opt);
            added++;
          }
        }

        // choose first allowed if any
        if (added > 0) {
          select.selectedIndex = 1; // first real option
        } else {
          select.selectedIndex = 0; // placeholder
        }
      }

      document.addEventListener('DOMContentLoaded', function(){
        var select = document.getElementById('package_id');
        if (!select) return;
        // capture all options once (including placeholder)
        var raw = Array.prototype.slice.call(select.querySelectorAll('option'));
        window.__allPackageOptions = raw.map(function(opt, i){
          return {
            value: opt.value,
            text: opt.textContent || opt.innerText,
            types: opt.dataset.types || '',
            idx: parseInt(opt.dataset.idx || (i)),
          };
        });

        // attach radio listeners
        var radios = document.querySelectorAll('input[name="stay_type"]');
        radios.forEach(function(r){
          r.addEventListener('change', function(ev){
            rebuildOptionsFor(ev.target.value);
          });
        });

        // initial apply
        var checked = document.querySelector('input[name="stay_type"]:checked');
        if (checked) rebuildOptionsFor(checked.value);
      });
    })();
  </script>
  <!-- Booking page specific script (AJAX flow + PhonePe redirect) -->
  <script src="js/booking.js?v=2"></script>
</body>
</html>
