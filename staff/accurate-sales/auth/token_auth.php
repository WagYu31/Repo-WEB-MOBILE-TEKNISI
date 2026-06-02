<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

// Verify API token and get database host
$apiTokenUrl = 'https://account.accurate.id/api/api-token.do';
$timestamp = date('d/m/Y H:i:s');

// Generate signature
$signature = hash_hmac('sha256', $timestamp, ACCURATE_SIGNATURE_SECRET);
$signature = base64_encode($signature);

$headers = [
    'Authorization: Bearer ' . ACCURATE_API_TOKEN,
    'X-Api-Timestamp: ' . $timestamp,
    'X-Api-Signature: ' . $signature
];

$response = makeApiRequest($apiTokenUrl, 'POST', $headers);

if ($response && isset($response['s']) && $response['s'] === true) {
    $_SESSION['accurate_host'] = $response['d']['data usaha']['host'];
    $_SESSION['accurate_db_id'] = $response['d']['data usaha']['id'];
    $_SESSION['auth_method'] = 'api_token';
    
    // Now open the database to get session
    header('Location: ../database/open.php');
    exit;
} else {
    die('Failed to authenticate with API Token: ' . print_r($response, true));
}
?>