<?php
/**
 * booking_create.php
 *
 * Handles 2-step flow:
 *  - mode=check  : validates and checks availability
 *  - mode=create : creates booking + creates PhonePe payment + returns redirectUrl
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/connect.php';

// PhonePe helpers
require __DIR__ . '/includes/phonepe/phonepe_config.php';
require __DIR__ . '/includes/phonepe/PhonePeClient.php';
require __DIR__ . '/includes/phonepe/PhonePeLogger.php';

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

try {
    $data = $_POST;

    // CSRF
    $csrf = getv($data, 'csrf');
    if (empty($_SESSION['booking_csrf']) || $csrf === '' || !hash_equals($_SESSION['booking_csrf'], $csrf)) {
        json_fail('Session expired. Please refresh and try again.', 419);
    }

    $mode        = strtolower(getv($data, 'mode', 'check')); // check | create
    $booking_src = getv($data, 'booking_source', 'web');
    // Ensure booking_source matches DB enum values
    if (!in_array($booking_src, ['web', 'whatsapp'], true)) {
        $booking_src = 'web';
    }

    $user_name    = getv($data, 'user_name');
    $user_phone   = preg_replace('/\D+/', '', getv($data, 'user_phone'));
    $user_email   = getv($data, 'user_email');
    $package_id   = (int)getv($data, 'package_id', '0');
    $stay_type    = strtolower(getv($data, 'stay_type', 'stay')); // stay|event|shoot
    $check_in_raw = getv($data, 'check_in');
    $num_people_raw = getv($data, 'num_people', '1');

    if ($user_name === '' || strlen($user_phone) < 10 || $package_id <= 0 || $check_in_raw === '') {
        json_fail('Missing required fields.');
    }

    // Validate number of people: allow 1..8
    $num_people = (int) preg_replace('/\D+/', '', $num_people_raw);
    if ($num_people < 1) $num_people = 1;
    if ($num_people > 8) json_fail('Number of people cannot exceed 8 for selected package.');

    if (!in_array($stay_type, ['stay', 'event', 'shoot'], true)) {
        $stay_type = 'stay';
    }

    $dtIn = parse_date_any($check_in_raw);
    if (!$dtIn) {
        json_fail('Invalid date format. Please use DD-MM-YYYY.', 422);
    }
    $check_in = $dtIn->format('Y-m-d');

    // Package
    $stmt = $conn->prepare('SELECT id, name, price, nights FROM packages WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $package_id);
    $stmt->execute();
    $pkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pkg) json_fail('Invalid package selected.');

    // Force numeric
    $amountRaw = preg_replace('/[^\d.]/', '', (string)$pkg['price']);
    $baseAmount = (float)$amountRaw;
    
    // Extra person charge (₹1500 for each person beyond 4)
    $extraAmount = 0;
    if ($num_people > 4) {
        $extraAmount = ($num_people - 4) * 1500;
    }

    // Apply 18% GST on top of the total
    $totalExclGst = $baseAmount + $extraAmount;
    $amount = $totalExclGst * 1.18;

    $nights = (int)$pkg['nights'];

    // compute check_out (exclusive end date for overlap math)
    $dtOut = clone $dtIn;
    if ($stay_type === 'stay') {
        $dtOut->modify('+' . max(1, $nights) . ' day');
    } else {
        $dtOut->modify('+1 day');
    }
    $check_out = $dtOut->format('Y-m-d');

    // Availability
    // Disallow past dates (yesterday or earlier)
    $today = (new DateTime())->format('Y-m-d');
    if ($check_in < $today) {
        json_fail('Cannot book past dates.');
    }

    // If booking for today, apply time-based rules
    $now = new DateTime();
    $nowTime = $now->format('H:i');
    $isToday = ($check_in === $today);

    // Special handling for package id 5 (Photo Shoots - Half Day)
    if ($package_id === 5) {
        if ($isToday && $nowTime >= '13:00') {
            json_fail('Bookings for today are closed after 13:00.');
        }

        // determine max allowed bookings depending on current time for today's bookings
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
        $over = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        if ($over >= $maxAllowed) {
            json_fail('Selected date is not available. Booking limit reached for this package.');
        }

    } else {
        // For non-special packages: if booking for today, disallow after 13:00
        if ($isToday && $nowTime >= '13:00') {
            json_fail('Bookings for today are closed after 13:00.');
        }

        // Default: disallow any overlap with existing pending/approved bookings
        $overlapSql = "
            SELECT COUNT(*) AS c
            FROM bookings
            WHERE booking_status IN ('pending','approved')
              AND NOT (check_out <= ? OR check_in >= ?)
        ";
        $stmt = $conn->prepare($overlapSql);
        $stmt->bind_param('ss', $check_in, $check_out);
        $stmt->execute();
        $over = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        if ($over > 0) {
            json_fail('Selected date is not available. Please choose another date.');
        }
    }

    if ($mode === 'check') {
        json_ok(['message' => 'Dates are available.']);
    }
    if ($mode !== 'create') {
        json_fail('Invalid mode.');
    }

    // Insert booking
    $merchantOrderId = 'KANAV_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

    $payment_status = 'Pending';
    $booking_status = 'pending';
    $phonepe_state  = 'PENDING';
    $attempts       = 0;

    $insSql = "
        INSERT INTO bookings
        (booking_source, user_name, user_phone, user_email, package_id, num_people, amount,
         check_in, check_out, stay_type, payment_status, booking_status,
         phonepe_merchant_order_id, phonepe_state, phonepe_attempts, created_at, updated_at)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";

    $ins = $conn->prepare($insSql);
    $ins->bind_param(
        'ssssiidsssssssi',
        $booking_src,
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
        $merchantOrderId,
        $phonepe_state,
        $attempts
    );

    $ins->execute();
    $bookingId = (int)$conn->insert_id;
    $ins->close();

    if ($stay_type === 'event') {
        json_ok([
            'is_enquiry' => true,
            'message' => 'Your event booking is confirmed. Our agent will call you back shortly.'
        ]);
    }

    // PhonePe create payment (Standard Checkout)
    $client = new PhonePeClient();

    // MUST be int in paise
    $amountPaisa = (int) round($amount * 100);

    PhonePeLogger::log($conn, $bookingId, $merchantOrderId, 'CREATE_PAYMENT_ATTEMPT', null, 'start', json_encode([
        'merchantOrderId' => $merchantOrderId,
        'amountPaise' => $amountPaisa,
        'customer' => [
            'name' => $user_name,
            'phone' => $user_phone,
            'email' => $user_email,
        ],
    ]), null);

    // ✅ CORRECT PARAM ORDER:
    // createPayment(int $amountPaise, string $merchantOrderId, ...)
    $resp = $client->createPayment($amountPaisa, $merchantOrderId, $user_phone, $user_name, $user_email);

    if (empty($resp['success'])) {
        $err = (string)($resp['error'] ?? 'PhonePe createPayment failed.');
        // store error
        $updErr = $conn->prepare("
            UPDATE bookings
            SET phonepe_last_error=?,
                phonepe_last_checked_at=NOW(),
                updated_at=NOW()
            WHERE id=?
        ");
        $updErr->bind_param('si', $err, $bookingId);
        $updErr->execute();
        $updErr->close();

        PhonePeLogger::log($conn, $bookingId, $merchantOrderId, 'CREATE_PAYMENT_FAILED', 500, 'FAILED', $err, json_encode($resp));
        json_fail('Server error: ' . $err, 500, ['raw' => $resp['raw'] ?? null]);
    }

    $orderId = (string)($resp['orderId'] ?? '');
    $state   = (string)($resp['state'] ?? 'PENDING');
    $redir   = (string)($resp['redirectUrl'] ?? '');

    if ($redir === '') {
        PhonePeLogger::log($conn, $bookingId, $merchantOrderId, 'CREATE_PAYMENT_NO_REDIRECT', 500, $state, 'redirectUrl missing', json_encode($resp));
        json_fail('PhonePe redirectUrl missing in response.', 500, ['raw' => $resp]);
    }

    $upd = $conn->prepare("
        UPDATE bookings
        SET phonepe_order_id=?,
            phonepe_state=?,
            phonepe_redirect_url=?,
            phonepe_last_error=NULL,
            phonepe_last_checked_at=NOW(),
            updated_at=NOW()
        WHERE id=?
    ");
    $upd->bind_param('sssi', $orderId, $state, $redir, $bookingId);
    $upd->execute();
    $upd->close();

    PhonePeLogger::log($conn, $bookingId, $merchantOrderId, 'CREATE_PAYMENT_SUCCESS', 200, $state, null, json_encode($resp));

    json_ok([
        'booking_id' => $bookingId,
        'merchant_order_id' => $merchantOrderId,
        'redirect_url' => $redir,
    ]);

} catch (Throwable $e) {
    json_fail('Server error: ' . $e->getMessage(), 500);
}
