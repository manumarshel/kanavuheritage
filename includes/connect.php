<?php
// db.php (server-ready)

$DEBUG = false; // ✅ keep FALSE on live server
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Server DB credentials (fallbacks)
// If your hosting provider gives a different DB host (like mysql.hostfe.com),
// set it in DB_HOST (env) or replace 'localhost' below.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'kanavuheritage';

// Optional: if your host uses a non-default port
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo $DEBUG
        ? "Database connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : "Service temporarily unavailable.";
    exit;
}
