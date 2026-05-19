<?php
// /phonepe/webhook.php (LIVE)
// PhonePe S2S webhook endpoint.
// Configure this URL + username/password in PhonePe dashboard webhook settings.

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/phonepe/phonepe_config.php';
require __DIR__ . '/../includes/phonepe/PhonePeLogger.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/**
 * ✅ Read Authorization header safely (Cloudways-safe)
 */
$authHeader = '';
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = (string)$_SERVER['HTTP_AUTHORIZATION'];
} else {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}
$authHeader = trim((string)$authHeader);

/**
 * ✅ Verify webhook auth
 * Support both methods:
 * A) SHA256 hash method: Authorization: SHA256(<hash>)
 * B) Basic auth method: Authorization: Basic base64(username:password)
 */
$expectedSha = hash('sha256', PHONEPE_WEBHOOK_USERNAME . ':' . PHONEPE_WEBHOOK_PASSWORD);
$isAuthorized = false;

if ($authHeader !== '') {
    // A) SHA256(...) mode
    $shaVal = preg_replace('/^SHA256\s*/i', '', $authHeader);
    if ($shaVal !== $authHeader) {
        // header started with SHA256
        if (hash_equals($expectedSha, trim($shaVal))) {
            $isAuthorized = true;
        }
    }

    // B) Basic auth mode
    if (!$isAuthorized && stripos($authHeader, 'basic ') === 0) {
        $decoded = base64_decode(substr($authHeader, 6));
        if ($decoded && strpos($decoded, ':') !== false) {
            [$u, $p] = explode(':', $decoded, 2);
            if (hash_equals(PHONEPE_WEBHOOK_USERNAME, (string)$u) && hash_equals(PHONEPE_WEBHOOK_PASSWORD, (string)$p)) {
                $isAuthorized = true;
            }
        }
    }
}

// Also allow PHP_AUTH_USER/PW (if server provides them)
if (!$isAuthorized) {
    $u = $_SERVER['PHP_AUTH_USER'] ?? '';
    $p = $_SERVER['PHP_AUTH_PW'] ?? '';
    if ($u !== '' && $p !== '') {
        if (hash_equals(PHONEPE_WEBHOOK_USERNAME, (string)$u) && hash_equals(PHONEPE_WEBHOOK_PASSWORD, (string)$p)) {
            $isAuthorized = true;
        }
    }
}

if (!$isAuthorized) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

/** Parse JSON */
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

$event = $payload['event'] ?? '';
$p = $payload['payload'] ?? [];

$merchantOrderId = $p['merchantOrderId'] ?? null;
$state           = $p['state'] ?? null;
$orderId         = $p['orderId'] ?? null;

$transactionId = null;
if (!empty($p['paymentDetails']) && is_array($p['paymentDetails'])) {
    $transactionId = $p['paymentDetails'][0]['transactionId'] ?? null;
}

// Log webhook payload
PhonePeLogger::log($conn, null, $merchantOrderId ?: '-', 'WEBHOOK:' . $event, 200, (string)$state, null, json_encode($payload), null);

if (!$merchantOrderId) {
    http_response_code(200);
    echo "OK";
    exit;
}

// Find booking
$stmt = $conn->prepare("SELECT id, booking_status, payment_status FROM bookings WHERE phonepe_merchant_order_id=? LIMIT 1");
$stmt->bind_param("s", $merchantOrderId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(200);
    echo "OK";
    exit;
}

$bookingId = (int)$row['id'];

if ($state === 'COMPLETED') {
    $bs = 'approved';
    $ps = 'Paid';

    $upd = $conn->prepare("UPDATE bookings
        SET booking_status=?, payment_status=?,
            phonepe_order_id=COALESCE(phonepe_order_id, ?),
            phonepe_transaction_id=?,
            phonepe_state=?,
            phonepe_last_checked_at=NOW(),
            phonepe_last_error=NULL
        WHERE id=?");
    $upd->bind_param("sssssi", $bs, $ps, $orderId, $transactionId, $state, $bookingId);
    $upd->execute();
    $upd->close();

} elseif ($state === 'FAILED') {
    $ps = 'Failed';

    $upd = $conn->prepare("UPDATE bookings
        SET payment_status=?,
            phonepe_order_id=COALESCE(phonepe_order_id, ?),
            phonepe_transaction_id=?,
            phonepe_state=?,
            phonepe_last_checked_at=NOW()
        WHERE id=?");
    $upd->bind_param("ssssi", $ps, $orderId, $transactionId, $state, $bookingId);
    $upd->execute();
    $upd->close();

} else {
    // PENDING / INITIATED / etc.
    $upd = $conn->prepare("UPDATE bookings
        SET payment_status='Pending',
            phonepe_state=?,
            phonepe_last_checked_at=NOW()
        WHERE id=?");
    $upd->bind_param("si", $state, $bookingId);
    $upd->execute();
    $upd->close();
}

http_response_code(200);
echo "OK";
