<?php

class PhonePeLogger
{
    /**
     * Safe logger: writes to `phonepe_logs` table if it exists.
     * If table is missing, it silently ignores.
     */
    public static function log(mysqli $conn, int $bookingId, string $merchantOrderId, string $event, $httpCode = null, ?string $state = null, ?string $req = null, ?string $res = null, ?string $err = null): void
    {
        try {
            // Check table existence quickly
            $chk = $conn->query("SHOW TABLES LIKE 'phonepe_logs'");
            if (!$chk || $chk->num_rows === 0) {
                return;
            }
            $stmt = $conn->prepare(
                "INSERT INTO phonepe_logs (booking_id, merchant_order_id, event_name, http_code, state, request_payload, response_payload, error_message, created_at)\n                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('ississss', $bookingId, $merchantOrderId, $event, $httpCode, $state, $req, $res, $err);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            // Intentionally swallow logging errors
        }
    }
}
