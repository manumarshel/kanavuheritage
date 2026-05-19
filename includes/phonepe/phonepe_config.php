<?php
/**
 * PhonePe Standard Checkout configuration
 * - Separate AUTH base URL and PG base URL (IMPORTANT)
 */

if (!defined('PHONEPE_ENABLED')) {
    define('PHONEPE_ENABLED', true);
}

/**
 * ENV:
 * - 'SANDBOX' for Test Mode
 * - 'PROD' for Live Mode (only after PhonePe enables production)
 */
if (!defined('PHONEPE_ENV')) {
    define('PHONEPE_ENV', 'PROD'); // change to 'SANDBOX' while testing
}

/** Your Standard Checkout credentials (make sure these match ENV) */
if (!defined('PHONEPE_CLIENT_ID')) {
    define('PHONEPE_CLIENT_ID', 'SU2512121931090721706249');
}
if (!defined('PHONEPE_CLIENT_SECRET')) {
    define('PHONEPE_CLIENT_SECRET', '149c6be9-3780-4aa1-8bd0-1bad66424fc3');
}
if (!defined('PHONEPE_CLIENT_VERSION')) {
    define('PHONEPE_CLIENT_VERSION', '1');
}

/**
 * ✅ Correct base URLs as per PhonePe docs:
 * Sandbox:
 *  - Auth: https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token
 *  - Pay:  https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay
 *
 * Production:
 *  - Auth: https://api.phonepe.com/apis/identity-manager/v1/oauth/token
 *  - Pay:  https://api.phonepe.com/apis/pg/checkout/v2/pay
 */
if (PHONEPE_ENV === 'SANDBOX') {
    if (!defined('PHONEPE_AUTH_BASE_URL')) define('PHONEPE_AUTH_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox');
    if (!defined('PHONEPE_PG_BASE_URL'))   define('PHONEPE_PG_BASE_URL',   'https://api-preprod.phonepe.com/apis/pg-sandbox');
} else {
    if (!defined('PHONEPE_AUTH_BASE_URL')) define('PHONEPE_AUTH_BASE_URL', 'https://api.phonepe.com/apis/identity-manager');
    if (!defined('PHONEPE_PG_BASE_URL'))   define('PHONEPE_PG_BASE_URL',   'https://api.phonepe.com/apis/pg');
}

/** API paths */
if (!defined('PHONEPE_AUTH_PATH')) {
    define('PHONEPE_AUTH_PATH', '/v1/oauth/token');
}
if (!defined('PHONEPE_PAY_PATH')) {
    define('PHONEPE_PAY_PATH', '/checkout/v2/pay');
}
if (!defined('PHONEPE_STATUS_PATH')) {
    define('PHONEPE_STATUS_PATH', '/checkout/v2/order');
}

/**
 * ✅ APP BASE PATH
 * Live is domain root: https://kanavuheritage.com/booking.php
 */
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', '/');
}

if (!defined('APP_BASE_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == '443');
    $scheme  = $isHttps ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $basePath = '/' . trim(APP_BASE_PATH, '/') . '/';
    if ($basePath === '//') $basePath = '/';

    define('APP_BASE_URL', $scheme . '://' . $host . $basePath);
}

/** URLs */
if (!defined('BOOKING_PAGE_URL')) {
    define('BOOKING_PAGE_URL', APP_BASE_URL . 'booking.php');
}
if (!defined('PHONEPE_RETURN_URL')) {
    define('PHONEPE_RETURN_URL', APP_BASE_URL . 'phonepe/return.php');
}
if (!defined('PHONEPE_CALLBACK_URL')) {
    // Webhook URL
    define('PHONEPE_CALLBACK_URL', APP_BASE_URL . 'phonepe/webhook.php');
}

/** Webhook credentials (same values in PhonePe dashboard webhook modal) */
if (!defined('PHONEPE_WEBHOOK_USERNAME')) {
    define('PHONEPE_WEBHOOK_USERNAME', 'kanavu_webhook');
}
if (!defined('PHONEPE_WEBHOOK_PASSWORD')) {
    define('PHONEPE_WEBHOOK_PASSWORD', 'kanavu123');
}
