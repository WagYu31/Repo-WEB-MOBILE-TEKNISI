<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

if (isset($_GET['code'])) {
    // Exchange authorization code for access token
    $tokenUrl = 'https://account.accurate.id/oauth/token';
    
    $headers = [
        'Authorization: Basic ' . base64_encode(ACCURATE_CLIENT_ID . ':' . ACCURATE_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    $postData = http_build_query([
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => OAUTH_CALLBACK_URL
    ]);
    
    $response = makeApiRequest($tokenUrl, 'POST', $headers, $postData);
    
    if ($response && isset($response['access_token'])) {
        // Store tokens in session
        $_SESSION['accurate_access_token'] = $response['access_token'];
        $_SESSION['accurate_refresh_token'] = $response['refresh_token'];
        $_SESSION['accurate_token_expires'] = time() + (15 * 24 * 60 * 60); // 15 days
        
        // Redirect to database selection
        header('Location: ../database/list.php');
        exit;
    } else {
        die('Failed to get access token: ' . print_r($response, true));
    }
} elseif (isset($_GET['error'])) {
    die('OAuth error: ' . $_GET['error']);
} else {
    die('Invalid callback request');
}
?>