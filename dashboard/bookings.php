<?php
// /kanav/dashboard/bookings.php — Admin list of all bookings
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// Fetch packages for admin add-booking modal
$packages = [];
$pkgRes = $conn->query("SELECT id, name, price, nights FROM packages ORDER BY id ASC");
if ($pkgRes) {
  while ($r = $pkgRes->fetch_assoc()) $packages[] = $r;
  $pkgRes->free();
}

$status_filter   = $_GET['status'] ?? 'all';
$allowed_status  = ['all','pending','approved','rejected','cancelled'];
if (!in_array($status_filter, $allowed_status, true)) {
    $status_filter = 'all';
}

$sql = "
  SELECT b.*, p.name AS package_name
  FROM bookings b
  LEFT JOIN packages p ON p.id = b.package_id
";
$params = [];
$types  = '';

if ($status_filter !== 'all') {
    $sql .= " WHERE b.booking_status = ? ";
    $params[] = $status_filter;
    $types   .= 's';
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result   = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Build WhatsApp message based on booking status
function buildWhatsappMessage(array $row): string {
    $name    = trim((string)($row['user_name'] ?? 'Guest'));
    $package = trim((string)($row['package_name'] ?? 'your selected'));
    $stay    = ucfirst((string)($row['stay_type'] ?? 'stay'));
    $status  = (string)($row['booking_status'] ?? 'pending');
    $pay     = (string)($row['payment_status'] ?? 'Pending');
  $people  = (int)($row['num_people'] ?? 1);

    $checkIn  = (string)($row['check_in'] ?? '');
    $checkOut = (string)($row['check_out'] ?? '');

    if ($status === 'pending') {
      return "Hi $name, we received your booking request for the $package package ($stay) for $people person(s). Payment status: $pay. We will confirm your booking shortly.";
    }
    if ($status === 'approved') {
      return "Hi $name, your booking is CONFIRMED ✅ for the $package package ($stay) for $people person(s). Dates: $checkIn to $checkOut. Thank you for choosing Kanavu Heritage!";
    }
    if ($status === 'rejected') {
      return "Hi $name, unfortunately we could not confirm your booking for the $package package ($stay) for $people person(s) on the selected dates. Please contact us for alternate dates.";
    }
    if ($status === 'cancelled') {
      return "Hi $name, your booking for the $package package ($stay) for $people person(s) has been CANCELLED. If you need help or want to rebook, please contact us.";
    }

    return "Hi $name, your booking status for the $package package ($stay) is: " . strtoupper($status) . ".";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bookings | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .content-wrapper { padding: 30px; overflow-x: auto; white-space: nowrap; }
    .top-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
    .filter-form { display:flex; align-items:center; gap:8px; font-size:14px; }
    .filter-form select { padding:4px 8px; border-radius:4px; border:1px solid #ccc; }
    table { width:100%; border-collapse:collapse; margin:18px 0; border:1px solid #cfcfcf; box-shadow:0 1px 2px rgba(0,0,0,0.03); display: inline-block; }
    th, td { padding:10px 12px; border:1px solid #e0e0e0; vertical-align:top; text-align:left; }
    th { background:#b19777; color:#000; font-weight:600; }
    tr:nth-child(even) { background:#fafafa; }
    .small-text { font-size:12px; color:#666; }
    .badge { display:inline-block; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600; }
    .badge-pending   { background:#fff3cd; color:#856404; }
    .badge-approved  { background:#d4edda; color:#155724; }
    .badge-rejected  { background:#f8d7da; color:#721c24; }
    .badge-cancelled { background:#e2e3e5; color:#383d41; }
    .badge-source    { background:#e1f5fe; color:#0277bd; }
    .btn-sm { padding:4px 10px; font-size:12px; border-radius:4px; border:none; cursor:pointer; margin-right:4px; }
    .btn-approve { background:#28a745; color:#fff; }
    .btn-reject  { background:#dc3545; color:#fff; }
    .btn-cancel  { background:#6c757d; color:#fff; }
    .no-actions { font-size:12px; color:#888; }
    .msg-banner { margin-bottom:10px; padding:8px 10px; border-radius:6px; font-size:13px; }
    .msg-ok { background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; }
    .msg-err { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
    /* Modal styles matching dashboard */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; }
    .modal-card { width:760px; max-width:96%; background:#fff; border-radius:8px; box-shadow:0 6px 24px rgba(0,0,0,0.12); border:1px solid #e6e6e6; }
    .modal-header { padding:14px 18px; border-bottom:1px solid #f1f1f1; display:flex; align-items:center; justify-content:space-between; }
    .modal-header h3 { margin:0; font-size:16px; }
    .modal-close { background:transparent; border:0; font-size:18px; cursor:pointer; color:#666; }
    .modal-body { padding:16px 18px; }
    .modal-actions { padding:12px 18px; border-top:1px solid #f1f1f1; display:flex; justify-content:flex-end; gap:8px; }
    .modal .form-control { width:100%; padding:8px 10px; border:1px solid #dcdcdc; border-radius:4px; }
    /* Blog-style form wrapper scoped to modal */
    .modal-body .form-wrapper { max-width: 900px; margin: 0 auto; background:#fff; padding:18px; border:2px solid #b19777; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
    .modal-body .form-wrapper h3 { margin-bottom:12px; color:#222; text-align:left; }
    .modal-body label { display:block; margin-top:10px; font-weight:700; color:#333; }
    .modal-body input, .modal-body textarea, .modal-body select { width:100%; padding:8px 10px; margin-top:6px; border-radius:6px; border:1px solid #ccc; font-size:14px; }
    .modal-body textarea { min-height:100px; resize:vertical; }
    .modal-body .btn-primary-custom { background:#b19777; color:#000; padding:10px 20px; border:none; border-radius:6px; }
    .modal-body .btn-back { background:#6c757d; color:#fff; border:none; padding:10px 18px; border-radius:6px; }
    /* Adjust the size of the email icon to match the WhatsApp icon */
    .fa-envelope {
      font-size: 1.75em; /* Match the size of the WhatsApp icon */
    }
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="top-header">
        <h2>Bookings</h2>
        <div>
          <button id="openAddBooking" class="btn btn-primary btn-sm btn-approve">+ Add Booking</button>
        </div>

        <form method="get" action="bookings.php" class="filter-form">
          <label for="status">Status:</label>
          <select name="status" id="status" onchange="this.form.submit()">
            <?php
            $labels = [
              'all'       => 'All',
              'pending'   => 'Pending',
              'approved'  => 'Approved',
              'rejected'  => 'Rejected',
              'cancelled' => 'Cancelled',
            ];
            foreach ($labels as $value => $label):
              $sel = $status_filter === $value ? 'selected' : '';
            ?>
              <option value="<?= e($value) ?>" <?= $sel ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if (isset($_GET['msg'])): 
          $m = $_GET['msg'];
          if ($m === 'updated'): ?>
            <div class="msg-banner msg-ok">Booking status updated successfully.</div>
          <?php elseif ($m === 'added'): ?>
            <div class="msg-banner msg-ok">Manual booking added successfully.</div>
          <?php elseif ($m === 'missing'): ?>
            <div class="msg-banner msg-err">Missing required fields. Please check the form.</div>
          <?php elseif ($m === 'date'): ?>
            <div class="msg-banner msg-err">Invalid date provided. Use YYYY-MM-DD.</div>
          <?php elseif ($m === 'package'): ?>
            <div class="msg-banner msg-err">Invalid package selected.</div>
          <?php elseif ($m === 'unavailable'): ?>
            <div class="msg-banner msg-err">Selected date is not available (overlaps existing bookings).</div>
          <?php elseif ($m === 'error' && !empty($_GET['err'])): ?>
            <div class="msg-banner msg-err">Server error: <?= e($_GET['err']) ?></div>
          <?php elseif ($m === 'csrf'): ?>
            <div class="msg-banner msg-err">Session invalid. Please refresh and try again.</div>
          <?php else: ?>
            <div class="msg-banner msg-err">Invalid request.</div>
          <?php endif; 
      endif; ?>

      <table>
        <thead>
          <tr>
            <th>Sl no.</th>
            <th>Customer</th>
            <th>Package</th>
              <th>Stay Type</th>
              <th>People</th>
            <th>Source</th>
            <th>Dates</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="11">No bookings yet.</td></tr>
        <?php else: ?>
          <?php foreach ($bookings as $index => $row): ?>
            <tr>
              <td><?= e($index + 1) ?></td>

              <td>
                <strong><?= e($row['user_name']) ?></strong><br>
                <?php
                  $message = buildWhatsappMessage($row);
                  $encodedMessage = urlencode($message);
                  // Send to the respective user's WhatsApp number with country code
                  $userPhone = "+91" . e($row['user_phone']); // Assuming 'user_phone' contains the user's phone number
                  $whatsappLink = "https://wa.me/" . $userPhone . "?text=$encodedMessage";
                ?>
                <a href="<?= $whatsappLink ?>" target="_blank">
                  <button type="button" class="btn-sm btn-approve">
                    <i class="fa-brands fa-whatsapp"></i>
                  </button>
                </a>
                <span > <?= e($row['user_phone']) ?></span><br>

                <?php if (!empty($row['user_email'])): ?>
                  <span >
                    <a href="mailto:<?= e($row['user_email']) ?>">
                      <i class="fa-solid fa-envelope" style="color:#0d6efd; margin-right:4px;"></i>
                    </a>
                    <?= e($row['user_email']) ?>
                  </span>
                <?php endif; ?>
              </td>

              <td>
                <?= e($row['package_name'] ?: '—') ?><br>
                <span class="small-text">ID: <?= e($row['package_id']) ?></span>
              </td>

              <td><?= e(ucfirst($row['stay_type'])) ?></td>

              <td><?= e((int)($row['num_people'] ?? 1)) ?></td>

              <td><span class="badge badge-source"><?= e(ucfirst($row['booking_source'])) ?></span></td>

              <td>
                <?= e($row['check_in']) ?> → <?= e($row['check_out']) ?><br>
                <span class="small-text">Created: <?= e($row['created_at']) ?></span>
              </td>

              <td>₹<?= e(number_format((float)$row['amount'], 2)) ?></td>

              <td><?= e($row['payment_status']) ?></td>

              <td>
                <?php
                  $bs  = $row['booking_status'];
                  $cls = 'badge-pending';
                  if ($bs === 'approved')      $cls = 'badge-approved';
                  elseif ($bs === 'rejected')  $cls = 'badge-rejected';
                  elseif ($bs === 'cancelled') $cls = 'badge-cancelled';
                ?>
                <span class="badge <?= $cls ?>"><?= e(ucfirst($bs)) ?></span>
              </td>

              <td>
                <?php if ($row['booking_status'] === 'pending'): ?>
                  <form method="post" action="booking_status_update.php" style="display:inline-block">
                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn-sm btn-approve">Approve</button>
                  </form>
                  <form method="post" action="booking_status_update.php"
                        style="display:inline-block"
                        onsubmit="return confirm('Reject this booking?');">
                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-sm btn-reject">Reject</button>
                  </form>
                <?php elseif ($row['booking_status'] === 'approved'): ?>
                  <form method="post" action="booking_status_update.php"
                        style="display:inline-block"
                        onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn-sm btn-cancel">Cancel</button>
                  </form>
                <?php else: ?>
                  <span class="no-actions">No actions</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>

      <!-- Add Booking Modal -->
      <div id="addBookingModal" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-card">
          <div class="modal-header">
            <h3>Add Booking</h3>
            <button class="modal-close btn btn-light" id="closeAddBooking" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-wrapper">
              <h3>Add Booking</h3>
              <form id="addBookingForm" method="post" action="add_booking.php">
                <input type="hidden" name="_csrf" value="<?= e(session_id()) ?>">
                <label>Full name</label>
                <input name="user_name" required>

                <label>Phone</label>
                <input name="user_phone" required>

                <label>Email</label>
                <input name="user_email">

                <div style="display:flex; gap:12px; margin-top:8px; align-items:flex-end;">
                  <div style="flex:1 1 260px;">
                    <label>Package</label>
                    <select name="package_id" required>
                      <option value="">-- Choose --</option>
                      <?php foreach ($packages as $p): ?>
                        <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?> — ₹<?= number_format($p['price'],2) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div style="width:140px;">
                    <label>People</label>
                    <input type="number" name="num_people" value="1" min="1" max="8" required>
                  </div>
                  <div style="width:160px;">
                    <label>Check-in</label>
                    <input type="date" name="check_in" required>
                  </div>
                  <div style="width:160px;">
                    <label>Status</label>
                    <select name="booking_status">
                      <option value="approved">Approved</option>
                      <option value="pending">Pending</option>
                    </select>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" id="modalCancel" class="btn-back">Close</button>
            <button type="submit" form="addBookingForm" class="btn-primary-custom">Add Booking</button>
          </div>
        </div>
      </div>

      <script>
        (function(){
          var open = document.getElementById('openAddBooking');
          var modal = document.getElementById('addBookingModal');
          var close = document.getElementById('closeAddBooking');
          if(open){ open.addEventListener('click', function(){ modal.style.display='flex'; }); }
          if(close){ close.addEventListener('click', function(){ modal.style.display='none'; }); }
          var modalCancel = document.getElementById('modalCancel');
          if(modalCancel){ modalCancel.addEventListener('click', function(){ modal.style.display='none'; }); }
          // simple submit blocker: ensure num_people <=8
          var form = document.getElementById('addBookingForm');
          if(form){ form.addEventListener('submit', function(ev){
            var people = parseInt((form.querySelector('[name="num_people"]').value||'1'), 10);
            if(isNaN(people) || people < 1 || people > 8){ ev.preventDefault(); alert('Please enter number of people between 1 and 8.'); }
          }); }
        })();
      </script>

    </div>
  </div>
</div>
</body>
</html>
