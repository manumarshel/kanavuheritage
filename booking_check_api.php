<?php
/**
 * booking_check_api.php  (Chatbot API)
 *
 * Checks date availability from `bookings` table.
 * Stateless: no session, no CSRF.
 *
 * Auth: X-KH-API-KEY header
 *
 * Request JSON / form-data:
 *  - check_in (required)  : "DD-MM-YYYY" or "YYYY-MM-DD"
 *  - stay_type (optional) : stay|event|shoot (default stay)
 *  - package_id (optional): required only if stay_type=stay (to calculate nights)
 *
 * Response:
 *  - ok: true/false
 *  - available: true/false
 *  - message: string
 *  - meta: details (optional)
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/includes/connect.php'; // must set $conn (mysqli)

// ---------- CONFIG ----------
define('KH_BOT_API_KEY', 'KH_BOT_2025_9f3c2a1b7d6e5f4a3b2c1d0e9f8a7b6c'); // set a strong key
// ---------------------------

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function json_ok(array $data = []): void {
  echo json_encode(array_merge(['ok' => true], $data));
  exit;
}
function json_fail(string $msg, int $code = 400, array $extra = []): void {
  http_response_code($code);
  echo json_encode(array_merge(['ok' => false, 'message' => $msg], $extra));
  exit;
}
function getv(array $src, string $k, string $def = ''): string {
  return isset($src[$k]) ? trim((string)$src[$k]) : $def;
}
function parse_date_any(string $raw): ?DateTime {
  $raw = trim($raw);
  if ($raw === '') return null;
  foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $fmt) {
    $dt = DateTime::createFromFormat($fmt, $raw);
    if ($dt instanceof DateTime) return $dt;
  }
  return null;
}

// ---------- AUTH (API KEY) ----------
$apiKey = $_SERVER['HTTP_X_KH_API_KEY'] ?? '';
if ($apiKey === '' || !hash_equals(KH_BOT_API_KEY, $apiKey)) {
  json_fail('Unauthorized', 401);
}

// ---------- READ INPUT (JSON or POST) ----------
$data = $_POST;
if (empty($data)) {
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  if (is_array($json)) $data = $json;
}

// ---------- INPUTS ----------
$check_in_raw = getv($data, 'check_in');
$stay_type    = strtolower(getv($data, 'stay_type', 'stay')); // stay|event|shoot
$package_id   = (int)getv($data, 'package_id', '0');

if (!in_array($stay_type, ['stay', 'event', 'shoot'], true)) {
  $stay_type = 'stay';
}

if ($check_in_raw === '') {
  json_fail('check_in is required. Use DD-MM-YYYY.', 422);
}

$dtIn = parse_date_any($check_in_raw);
if (!$dtIn) {
  json_fail('Invalid date format. Use DD-MM-YYYY or YYYY-MM-DD.', 422);
}

$check_in = $dtIn->format('Y-m-d');

// ---------- CALCULATE CHECK_OUT ----------
$nights = 1;

// If stay, nights should come from packages table (if provided)
if ($stay_type === 'stay') {
  if ($package_id <= 0) {
    json_fail('package_id is required for stay bookings.', 422);
  }

  $stmt = $conn->prepare("SELECT nights FROM packages WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $package_id);
  $stmt->execute();
  $pkg = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$pkg) {
    json_fail('Invalid package_id.', 422);
  }

  $nights = (int)$pkg['nights'];
  if ($nights < 1) $nights = 1;
} else {
  // event/shoot: occupy 1 day
  $nights = 1;
}

$dtOut = clone $dtIn;
$dtOut->modify('+' . $nights . ' day');
$check_out = $dtOut->format('Y-m-d');

// ---------- AVAILABILITY QUERY ----------
// Disallow past dates (yesterday or earlier)
$today = (new DateTime())->format('Y-m-d');
if ($check_in < $today) {
  json_ok([
    'available' => false,
    'message'   => 'Cannot book past dates.',
    'meta'      => ['check_in' => $check_in]
  ]);
}

// If booking for today, apply time-based rules
$now = new DateTime();
$nowTime = $now->format('H:i');
$isToday = ($check_in === $today);

// Special rule: package id 5 (Photo Shoots - Half Day) has dynamic limits
if ($package_id === 5) {
  if ($isToday && $nowTime >= '13:00') {
    json_ok([
      'available' => false,
      'message'   => 'Bookings for today are closed after 13:00.',
      'meta'      => ['check_in' => $check_in]
    ]);
  }

  $maxAllowed = 2;
  if ($isToday && $nowTime >= '07:00') {
    $maxAllowed = 1; // after 07:00 allow only one booking
  }

  $stmt = $conn->prepare(
    "SELECT COUNT(*) AS c FROM bookings
     WHERE package_id = ?
       AND booking_status IN ('pending','approved')
       AND check_in = ?"
  );
  $stmt->bind_param('is', $package_id, $check_in);
  $stmt->execute();
  $count = (int)$stmt->get_result()->fetch_assoc()['c'];
  $stmt->close();

  if ($count >= $maxAllowed) {
    json_ok([
      'available' => false,
      'message'   => 'Selected date is NOT available. Booking limit reached for this package.',
      'meta'      => [
        'check_in'  => $check_in,
        'check_out' => $check_out,
        'stay_type' => $stay_type,
        'package_id'=> $package_id,
        'bookings'  => $count,
        'max_allowed'=> $maxAllowed
      ]
    ]);
  }

} else {
  // For non-special packages: if booking for today, disallow after 13:00
  if ($isToday && $nowTime >= '13:00') {
    json_ok([
      'available' => false,
      'message'   => 'Bookings for today are closed after 13:00.',
      'meta'      => ['check_in' => $check_in]
    ]);
  }

  // Overlap if: NOT (existing.check_out <= new.check_in OR existing.check_in >= new.check_out)
  $sql = "
    SELECT COUNT(*) AS c
    FROM bookings
    WHERE booking_status IN ('pending','approved')
      AND NOT (check_out <= ? OR check_in >= ?)
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ss', $check_in, $check_out);
  $stmt->execute();
  $count = (int)$stmt->get_result()->fetch_assoc()['c'];
  $stmt->close();

  if ($count > 0) {
    json_ok([
      'available' => false,
      'message'   => 'Selected date is NOT available. Please choose another date.',
      'meta'      => [
        'check_in'  => $check_in,
        'check_out' => $check_out,
        'stay_type' => $stay_type,
        'package_id'=> $package_id,
        'overlaps'  => $count
      ]
    ]);
  }
}

json_ok([
  'available' => true,
  'message'   => 'Selected date is available.',
  'meta'      => [
    'check_in'  => $check_in,
    'check_out' => $check_out,
    'stay_type' => $stay_type,
    'package_id'=> $package_id
  ]
]);
