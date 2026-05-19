<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function redirect_msg(string $code){
    header('Location: bookings.php?msg=' . urlencode($code));
    exit();
}

try {

    $user_name  = trim((string)($_POST['user_name'] ?? ''));
    $user_phone = preg_replace('/\D+/', '', (string)($_POST['user_phone'] ?? ''));
    $user_email = trim((string)($_POST['user_email'] ?? ''));
    $package_id = (int)($_POST['package_id'] ?? 0);
    $num_people = (int)($_POST['num_people'] ?? 1);
    $check_in_raw = trim((string)($_POST['check_in'] ?? ''));
    $booking_status = in_array($_POST['booking_status'] ?? 'approved', ['approved','pending','rejected','cancelled'], true) ? $_POST['booking_status'] : 'approved';

    if ($user_name === '' || strlen($user_phone) < 10 || $package_id <= 0 || $check_in_raw === '') {
        redirect_msg('missing');
    }

    // Validate people 1..8
    $num_people = max(1, min(8, $num_people));

    // parse date (accept Y-m-d or d-m-Y)
    $dt = DateTime::createFromFormat('Y-m-d', $check_in_raw) ?: DateTime::createFromFormat('d-m-Y', $check_in_raw);
    if (!$dt) redirect_msg('date');
    $check_in = $dt->format('Y-m-d');

    // fetch package details
    $stmt = $conn->prepare('SELECT id, name, price, nights FROM packages WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $package_id);
    $stmt->execute();
    $pkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pkg) redirect_msg('package');

    $amount = (float)preg_replace('/[^\d.]/','', (string)$pkg['price']);
    $nights = (int)($pkg['nights'] ?? 1);

    // compute check_out similar to booking_create
    $dtOut = clone $dt;
    $dtOut->modify('+' . max(1, $nights) . ' day');
    $check_out = $dtOut->format('Y-m-d');

    // Availability rules (mirror booking_create.php)
    $today = (new DateTime())->format('Y-m-d');
    if ($check_in < $today) {
        redirect_msg('unavailable');
    }
    $now = new DateTime();
    $nowTime = $now->format('H:i');
    $isToday = ($check_in === $today);

    if ($package_id === 5) {
        if ($isToday && $nowTime >= '13:00') redirect_msg('unavailable');
        $maxAllowed = 2;
        if ($isToday && $nowTime >= '07:00') $maxAllowed = 1;
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS c FROM bookings
             WHERE package_id = ?
               AND booking_status IN ('pending','approved')
               AND check_in = ?"
        );
        $stmt->bind_param('is', $package_id, $check_in);
        $stmt->execute();
        $over = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($over >= $maxAllowed) redirect_msg('unavailable');
    } else {
        if ($isToday && $nowTime >= '13:00') redirect_msg('unavailable');
        $overlapSql = "SELECT COUNT(*) AS c FROM bookings WHERE booking_status IN ('pending','approved') AND NOT (check_out <= ? OR check_in >= ?)";
        $stmt = $conn->prepare($overlapSql);
        $stmt->bind_param('ss', $check_in, $check_out);
        $stmt->execute();
        $over = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($over > 0) redirect_msg('unavailable');
    }

    // Insert booking (admin-created) - use allowed DB value
    $booking_source = 'web';
    $payment_status = 'NA';
    $phonepe_state = null;
    $phonepe_attempts = 0;

    $ins = $conn->prepare("INSERT INTO bookings
        (booking_source, user_name, user_phone, user_email, package_id, num_people, amount,
         check_in, check_out, stay_type, payment_status, booking_status,
         phonepe_merchant_order_id, phonepe_state, phonepe_attempts, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, NOW(), NOW())");

    $stay_type = 'stay';
    $ins->bind_param('ssssiidssssssi',
        $booking_source,
        $user_name,
        $user_phone,
        $user_email,
        $package_id,
        $num_people,
        $amount,
        $check_in,
        $check_out,
        $stay_type,
        $payment_status,
        $booking_status,
        $phonepe_state,
        $phonepe_attempts
    );

    $ins->execute();
    $ins->close();

    header('Location: bookings.php?msg=added');
    exit();

} catch (Throwable $e) {
    // log error for debugging
    error_log('[add_booking] ' . $e->getMessage());
    $msg = substr($e->getMessage(), 0, 200);
    header('Location: bookings.php?msg=error&err=' . urlencode($msg));
    exit();
}
