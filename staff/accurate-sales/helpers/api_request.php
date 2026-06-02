<?php
function makeApiRequest($url, $method = 'GET', $headers = [], $postData = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('CURL error: ' . curl_error($ch));
        return false;
    }
    
    curl_close($ch);
    
    $decodedResponse = json_decode($response, true);
    
    if ($httpCode >= 400) {
        error_log('API request failed: HTTP ' . $httpCode . ' - ' . $response);
        return false;
    }
    
    return $decodedResponse ?: $response;
}

function refreshAccessToken() {
    require_once '../config.php';
    
    if (empty($_SESSION['accurate_refresh_token'])) {
        return false;
    }
    
    $tokenUrl = 'https://account.accurate.id/oauth/token';
    
    $headers = [
        'Authorization: Basic ' . base64_encode(ACCURATE_CLIENT_ID . ':' . ACCURATE_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    $postData = http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $_SESSION['accurate_refresh_token']
    ]);
    
    $response = makeApiRequest($tokenUrl, 'POST', $headers, $postData);
    
    if ($response && isset($response['access_token'])) {
        $_SESSION['accurate_access_token'] = $response['access_token'];
        $_SESSION['accurate_refresh_token'] = $response['refresh_token'];
        $_SESSION['accurate_token_expires'] = time() + (15 * 24 * 60 * 60); // 15 days
        return true;
    }
    
    return false;
}
?>