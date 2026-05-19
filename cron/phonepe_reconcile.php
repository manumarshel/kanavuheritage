<?php
// /kanav/cron/phonepe_reconcile.php
// Cron job to reconcile pending PhonePe payments (in case webhook fails/delayed).
// Suggested: run every 5 minutes.

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/phonepe/phonepe_config.php';
require __DIR__ . '/../includes/phonepe/PhonePeClient.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$client = new PhonePeClient();

// Find pending bookings created in last 24h with a merchantOrderId
$sql = "SELECT id, phonepe_merchant_order_id, phonepe_state, phonepe_attempts, created_at
        FROM bookings
        WHERE booking_status = 'pending'
          AND payment_status = 'Pending'
          AND phonepe_merchant_order_id IS NOT NULL
          AND created_at >= (NOW() - INTERVAL 1 DAY)
        ORDER BY id DESC
        LIMIT 50";

$res = $conn->query($sql);
$rows = $res->fetch_all(MYSQLI_ASSOC);

foreach ($rows as $b) {
    $id = (int)$b['id'];
    $mo = (string)$b['phonepe_merchant_order_id'];

    try {
        $st = $client->orderStatus($mo, false, true);

        $state = $st['state'] ?? null;
        $orderId = $st['orderId'] ?? null;

        $transactionId = null;
        if (!empty($st['paymentDetails']) && is_array($st['paymentDetails'])) {
            $transactionId = $st['paymentDetails'][0]['transactionId'] ?? null;
        }

        if ($state === 'COMPLETED') {
            $upd = $conn->prepare("UPDATE bookings
                SET booking_status='approved',
                    payment_status='Paid',
                    phonepe_order_id=COALESCE(phonepe_order_id, ?),
                    phonepe_transaction_id=?,
                    phonepe_state=?,
                    phonepe_last_checked_at=NOW(),
                    phonepe_last_error=NULL
                WHERE id=?");
            $upd->bind_param("sssi", $orderId, $transactionId, $state, $id);
            $upd->execute();
            $upd->close();
        } elseif ($state === 'FAILED') {
            $upd = $conn->prepare("UPDATE bookings
                SET payment_status='Failed',
                    phonepe_order_id=COALESCE(phonepe_order_id, ?),
                    phonepe_transaction_id=?,
                    phonepe_state=?,
                    phonepe_last_checked_at=NOW()
                WHERE id=?");
            $upd->bind_param("sssi", $orderId, $transactionId, $state, $id);
            $upd->execute();
            $upd->close();
        } else {
            $upd = $conn->prepare("UPDATE bookings
                SET phonepe_state=?, phonepe_last_checked_at=NOW()
                WHERE id=?");
            $upd->bind_param("si", $state, $id);
            $upd->execute();
            $upd->close();
        }
    } catch (Exception $e) {
        $err = $e->getMessage();
        $upd = $conn->prepare("UPDATE bookings
            SET phonepe_attempts = phonepe_attempts + 1,
                phonepe_last_error = ?,
                phonepe_last_checked_at = NOW()
            WHERE id=?");
        $upd->bind_param("si", $err, $id);
        $upd->execute();
        $upd->close();
    }
}

echo "OK\n";
