<?php
require_once __DIR__ . '/phonepe_config.php';

/**
 * PhonePe Standard Checkout client (ClientId / ClientSecret flow)
 * Uses O-Bearer access token
 */
class PhonePeClient
{
    // ✅ NEW: separate base URLs
    private string $authBaseUrl;
    private string $pgBaseUrl;

    // kept for backward compatibility if some code passes baseUrl
    private string $baseUrl;

    private string $clientId;
    private string $clientSecret;
    private string $clientVersion;

    private string $authPath;
    private string $payPath;
    private string $statusPath;

    private string $tokenCacheFile;

    public function __construct(array $opts = [])
    {
        /**
         * ✅ Base URLs (important)
         * - AUTH should go to identity-manager (prod) OR pg-sandbox (sandbox)
         * - PG should go to pg (prod) OR pg-sandbox (sandbox)
         */
        $this->authBaseUrl = rtrim($opts['authBaseUrl'] ?? (defined('PHONEPE_AUTH_BASE_URL') ? PHONEPE_AUTH_BASE_URL : ''), '/');
        $this->pgBaseUrl   = rtrim($opts['pgBaseUrl']   ?? (defined('PHONEPE_PG_BASE_URL')   ? PHONEPE_PG_BASE_URL   : ''), '/');

        // fallback: if old config only has PHONEPE_BASE_URL, use it for PG (not for AUTH)
        $this->baseUrl     = rtrim($opts['baseUrl'] ?? (defined('PHONEPE_BASE_URL') ? PHONEPE_BASE_URL : ''), '/');
        if ($this->pgBaseUrl === '' && $this->baseUrl !== '') {
            $this->pgBaseUrl = $this->baseUrl;
        }

        if ($this->authBaseUrl === '') {
            // last fallback for safety (won't be correct for PROD auth if missing)
            $this->authBaseUrl = $this->pgBaseUrl;
        }

        $this->clientId      = (string)($opts['clientId'] ?? PHONEPE_CLIENT_ID);
        $this->clientSecret  = (string)($opts['clientSecret'] ?? PHONEPE_CLIENT_SECRET);
        $this->clientVersion = (string)($opts['clientVersion'] ?? PHONEPE_CLIENT_VERSION);

        $this->authPath   = (string)($opts['authPath'] ?? PHONEPE_AUTH_PATH);
        $this->payPath    = (string)($opts['payPath'] ?? PHONEPE_PAY_PATH);
        $this->statusPath = (string)($opts['statusPath'] ?? PHONEPE_STATUS_PATH);

        $cacheDir = sys_get_temp_dir();
        $this->tokenCacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'phonepe_token_cache_' . md5($this->clientId) . '.json';
    }

    // ✅ NEW: build url for AUTH endpoints
    private function authUrl(string $path): string
    {
        return $this->authBaseUrl . '/' . ltrim($path, '/');
    }

    // ✅ NEW: build url for PG endpoints
    private function pgUrl(string $path): string
    {
        return $this->pgBaseUrl . '/' . ltrim($path, '/');
    }

    // kept for older usage (not used for auth now)
    private function url(string $path): string
    {
        return $this->pgUrl($path);
    }

    private function appendQuery(string $url, array $params): string
    {
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . http_build_query($params);
    }

    private function curlRequest(string $method, string $url, array $headers = [], $body = null): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 45,
        ]);

        if (!empty($headers)) {
            $flat = [];
            foreach ($headers as $k => $v) {
                $flat[] = $k . ': ' . $v;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $flat);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL error: ' . $err);
        }

        $json = json_decode($raw, true);
        return [
            'http_code' => $code,
            'raw'       => $raw,
            'json'      => $json,
        ];
    }

    private function loadCachedToken(): ?array
    {
        if (!is_file($this->tokenCacheFile)) return null;

        $data = json_decode((string)@file_get_contents($this->tokenCacheFile), true);
        if (!is_array($data)) return null;

        $expiresAt = (int)($data['expires_at'] ?? 0);
        if ($expiresAt > (time() + 30) && !empty($data['access_token'])) {
            return $data;
        }
        return null;
    }

    private function saveCachedToken(string $accessToken, int $expiresInSeconds): void
    {
        $payload = [
            'access_token' => $accessToken,
            'expires_at'   => time() + max(0, $expiresInSeconds),
        ];
        @file_put_contents($this->tokenCacheFile, json_encode($payload));
    }

    /** Get merchant auth token (O-Bearer) */
    public function getAccessToken(): string
    {
        if (!PHONEPE_ENABLED) {
            throw new RuntimeException('PhonePe is disabled (PHONEPE_ENABLED=false).');
        }

        $cached = $this->loadCachedToken();
        if ($cached && !empty($cached['access_token'])) {
            return (string)$cached['access_token'];
        }

        // ✅ IMPORTANT: token must come from AUTH base URL (not PG base URL in PROD)
        $url  = $this->authUrl($this->authPath);

        $body = http_build_query([
            'grant_type'     => 'client_credentials',
            'client_id'      => $this->clientId,
            'client_secret'  => $this->clientSecret,
            'client_version' => $this->clientVersion,
        ]);

        $resp = $this->curlRequest('POST', $url, [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $body);

        if ($resp['http_code'] < 200 || $resp['http_code'] >= 300) {
            $msg = 'Auth failed. HTTP ' . $resp['http_code'];
            $msg .= ' | ' . (is_array($resp['json']) ? json_encode($resp['json']) : $resp['raw']);
            throw new RuntimeException($msg);
        }

        $j = $resp['json'];
        $accessToken = $j['access_token'] ?? ($j['data']['access_token'] ?? null);
        $expiresIn   = (int)($j['expires_in'] ?? ($j['data']['expires_in'] ?? 900));

        if (!$accessToken) {
            throw new RuntimeException('Auth succeeded but access_token not found. Response: ' . $resp['raw']);
        }

        $this->saveCachedToken((string)$accessToken, $expiresIn);
        return (string)$accessToken;
    }

    /**
     * Create a payment and get redirect URL (Standard Checkout)
     */
    public function createPayment(
        int $amountPaise,
        string $merchantOrderId,
        string $customerMobile = '',
        string $customerName = '',
        string $customerEmail = ''
    ): array {

        // ✅ Even in "disabled" mode return to a REAL page (return.php)
        if (!PHONEPE_ENABLED) {
            $fakeRedirect = $this->appendQuery(PHONEPE_RETURN_URL, ['mo' => $merchantOrderId]);
            return [
                'success' => true,
                'merchantOrderId' => $merchantOrderId,
                'redirectUrl' => $fakeRedirect,
                'note' => 'PHONEPE_ENABLED=false: skipping gateway call',
            ];
        }

        $token = $this->getAccessToken();

        // ✅ Ensure redirect/callback go to existing files
        $redirectUrl = $this->appendQuery(PHONEPE_RETURN_URL, ['mo' => $merchantOrderId]);
        $callbackUrl = $this->appendQuery(PHONEPE_CALLBACK_URL, ['mo' => $merchantOrderId]);

        $payload = [
            'merchantOrderId' => $merchantOrderId,
            'amount'          => $amountPaise,
            'expireAfter'     => 1200,
            'metaInfo'        => [
                'udf1' => (string)$customerName,
                'udf2' => (string)$customerMobile,
                'udf3' => (string)$customerEmail,
            ],
            'paymentFlow'     => [
                'type' => 'PG_CHECKOUT',
                'merchantUrls' => [
                    'redirectUrl' => $redirectUrl,
                    'callbackUrl' => $callbackUrl,
                ],
            ],
        ];

        // ✅ IMPORTANT: payment API must go to PG base URL
        $url  = $this->pgUrl($this->payPath);

        $resp = $this->curlRequest('POST', $url, [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ], json_encode($payload));

        if ($resp['http_code'] < 200 || $resp['http_code'] >= 300) {
            $msg = 'PhonePe createPayment failed. HTTP ' . $resp['http_code'];
            $msg .= ' | ' . (is_array($resp['json']) ? json_encode($resp['json']) : $resp['raw']);
            return [
                'success' => false,
                'merchantOrderId' => $merchantOrderId,
                'error' => $msg,
                'raw' => $resp['raw'],
                'json' => $resp['json'],
            ];
        }

        $j = $resp['json'] ?? [];

        // Redirect URL can appear in different keys depending on response shape
        $redirectFromResp =
            $j['redirectUrl']
            ?? ($j['data']['redirectUrl'] ?? null)
            ?? ($j['data']['instrumentResponse']['redirectInfo']['url'] ?? null)
            ?? ($j['data']['instrumentResponse']['redirectInfo']['redirectUrl'] ?? null)
            ?? null;

        $state   = $j['state'] ?? ($j['data']['state'] ?? null) ?? 'PENDING';
        $orderId = $j['orderId'] ?? ($j['data']['orderId'] ?? null) ?? null;

        return [
            'success'     => true,
            'merchantOrderId' => $merchantOrderId,
            'orderId'     => $orderId,
            'state'       => $state,
            'redirectUrl' => (string)$redirectFromResp,
            'raw'         => $resp['raw'],
            'json'        => $j,
        ];
    }

    /** Status API: /checkout/v2/order/{merchantOrderId}/status */
    public function checkStatus(string $merchantOrderId): array
    {
        if (!PHONEPE_ENABLED) {
            return [
                'success' => true,
                'merchantOrderId' => $merchantOrderId,
                'state' => 'SKIPPED',
                'note' => 'PHONEPE_ENABLED=false',
            ];
        }

        $token = $this->getAccessToken();

        // ✅ IMPORTANT: status API must go to PG base URL
        $url = $this->pgUrl(rtrim($this->statusPath, '/') . '/' . urlencode($merchantOrderId) . '/status');

        $resp = $this->curlRequest('GET', $url, [
            'Accept'        => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ]);

        if ($resp['http_code'] < 200 || $resp['http_code'] >= 300) {
            return [
                'success' => false,
                'merchantOrderId' => $merchantOrderId,
                'error' => 'Status check failed. HTTP ' . $resp['http_code'],
                'raw' => $resp['raw'],
            ];
        }

        $j = $resp['json'] ?? [];
        $state = $j['state'] ?? ($j['data']['state'] ?? null) ?? null;

        return [
            'success' => true,
            'merchantOrderId' => $merchantOrderId,
            'state' => $state,
            'json' => $j,
            'raw' => $resp['raw'],
        ];
    }
}
