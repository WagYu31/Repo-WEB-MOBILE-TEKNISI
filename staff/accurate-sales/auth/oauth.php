<?php
require_once '../config.php';

// Generate OAuth authorization URL
$oauthUrl = 'https://account.accurate.id/oauth/authorize?' . http_build_query([
    'client_id' => ACCURATE_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => OAUTH_CALLBACK_URL,
    'scope' => 'customer_view customer_save customer_delete'
]);

header("Location: $oauthUrl");
exit;
?>