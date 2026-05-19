<?php
// /kanav/api/booked_dates.php
// Returns JSON list of blocked dates (Y-m-d) where the
// property is occupied (pending or approved bookings).

header('Content-Type: application/json');

require __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Optional window: ?from=YYYY-MM-DD&to=YYYY-MM-DD
$from = $_GET['from'] ?? null;
$to   = $_GET['to']   ?? null;

if ($from === null) {
    $from = date('Y-m-01'); // first day of this month
}
if ($to === null) {
    // end of next month
    $to = date('Y-m-t', strtotime('+2 months'));
}

// Fetch bookings that intersect with [from, to]
$sql = "
  SELECT check_in, check_out
  FROM bookings
  WHERE booking_status IN ('pending','approved')
    AND check_in < ?
    AND check_out > ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $to, $from);
$stmt->execute();
$stmt->bind_result($check_in, $check_out);

$dates = [];

while ($stmt->fetch()) {
    $start = new DateTime($check_in);
    $end   = new DateTime($check_out);

    for ($d = $start; $d < $end; $d->modify('+1 day')) {
        $dates[] = $d->format('Y-m-d');
    }
}
$stmt->close();

$dates = array_values(array_unique($dates));
sort($dates);

echo json_encode([
    'ok'    => true,
    'from'  => $from,
    'to'    => $to,
    'dates' => $dates
]);
