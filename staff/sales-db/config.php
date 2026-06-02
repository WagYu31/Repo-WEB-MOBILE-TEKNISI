<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Jakarta');

define('CLIENT_ID', '475a9a8e-49c2-415b-86a8-677b104c2c92');
define('CLIENT_SECRET', '7b1adb8861d5a6f5709a31dd7ec5440a');
define('REDIRECT_URI', 'https://jadwal.grav-tech.com/staff/sales-db/aol-oauth-callback.php');
define('AUTHORIZATION_ENDPOINT', 'https://account.accurate.id/oauth/authorize');
define('TOKEN_ENDPOINT', 'https://account.accurate.id/oauth/token');
define('SCOPES', 'item_view item_save customer_view customer_save');

define('API_ACCOUNT_URL', 'https://account.accurate.id/api');
define('API_ACCURATE_URL', '');
define('ITEMS_PER_PAGE', 50);

session_set_cookie_params([
    'lifetime' => 3600 * 24 * 14,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAuthorizationHeader(): string {
    return 'Basic ' . base64_encode(CLIENT_ID . ':' . CLIENT_SECRET);
}

function isAuthenticated(): bool {
    return !empty($_SESSION['accurate_access_token']);
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: ' . getAuthorizationUrl());
        exit();
    }
}

function getAuthorizationUrl(): string {
    $params = [
        'client_id' => CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri' => REDIRECT_URI,
        'scope' => SCOPES,
        'state' => bin2hex(random_bytes(16))
    ];
    return AUTHORIZATION_ENDPOINT . '?' . http_build_query($params);
}
?>