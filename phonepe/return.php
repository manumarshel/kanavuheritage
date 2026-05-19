<?php
// phonepe/return.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/phonepe/phonepe_config.php';
require __DIR__ . '/../includes/phonepe/PhonePeClient.php';

function redirect_to_booking(string $type, string $msg, string $mo = ''): void
{
    // flash message for booking.php
    $_SESSION['booking_flash_type'] = $type; // success | error
    $_SESSION['booking_flash_msg']  = $msg;

    // ✅ Always go back to booking.php (real file)
    $url = BOOKING_PAGE_URL;

    if ($mo !== '') {
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        $url .= $sep . 'mo=' . rawurlencode($mo);
    }

    header('Location: ' . $url);
    exit;
}

$mo = isset($_GET['mo']) ? trim((string)$_GET['mo']) : '';
if ($mo === '') {
    redirect_to_booking('error', 'Missing merchant order id.');
}

try {
    $client = new PhonePeClient();

    // check status from PhonePe
    $statusResp = $client->checkStatus($mo);

    if (empty($statusResp['success'])) {
        $err = (string)($statusResp['error'] ?? 'Unable to verify payment.');

        // store error (optional)
        $upd = $conn->prepare("
            UPDATE bookings
            SET phonepe_last_error=?,
                phonepe_last_checked_at=NOW(),
                updated_at=NOW()
            WHERE phonepe_merchant_order_id=?
            LIMIT 1
        ");
        $upd->bind_param('ss', $err, $mo);
        $upd->execute();
        $upd->close();

        redirect_to_booking('error', 'Payment verification failed. Please contact support.', $mo);
    }

    $state = strtoupper((string)($statusResp['state'] ?? ''));

    // treat these as success
    $isSuccess = in_array($state, ['COMPLETED', 'SUCCESS'], true);

    if ($isSuccess) {
        $paymentStatus = 'Success';
        $bookingStatus = 'approved'; // set to 'pending' if you don't want auto-approve

        $upd = $conn->prepare("
            UPDATE bookings
            SET payment_status=?,
                booking_status=?,
                phonepe_state=?,
                phonepe_last_error=NULL,
                phonepe_last_checked_at=NOW(),
                updated_at=NOW()
            WHERE phonepe_merchant_order_id=?
            LIMIT 1
        ");
        $upd->bind_param('ssss', $paymentStatus, $bookingStatus, $state, $mo);
        $upd->execute();
        $upd->close();

        redirect_to_booking('success', 'Payment successful! Your booking is confirmed.', $mo);
    }

    // failed / pending / cancelled
    $paymentStatus = 'Failed';
    $upd = $conn->prepare("
        UPDATE bookings
        SET payment_status=?,
            phonepe_state=?,
            phonepe_last_checked_at=NOW(),
            updated_at=NOW()
        WHERE phonepe_merchant_order_id=?
        LIMIT 1
    ");
    $upd->bind_param('sss', $paymentStatus, $state, $mo);
    $upd->execute();
    $upd->close();

    redirect_to_booking('error', 'Payment not completed (' . $state . '). If amount got debited, contact support.', $mo);

} catch (Throwable $e) {
    $err = 'Return error: ' . $e->getMessage();

    $upd = $conn->prepare("
        UPDATE bookings
        SET phonepe_last_error=?,
            phonepe_last_checked_at=NOW(),
            updated_at=NOW()
        WHERE phonepe_merchant_order_id=?
        LIMIT 1
    ");
    $upd->bind_param('ss', $err, $mo);
    $upd->execute();
    $upd->close();

    redirect_to_booking('error', 'Something went wrong while verifying payment. Please contact support.', $mo);
}
