<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

if (empty($_SESSION['accurate_access_token']) {
    die('Not authenticated. Please login first.');
}

$dbId = isset($_GET['id']) ? $_GET['id'] : (isset($_SESSION['accurate_db_id']) ? $_SESSION['accurate_db_id'] : null);

if (!$dbId) {
    die('Database ID not specified.');
}

$openDbUrl = 'https://account.accurate.id/api/open-db.do?id=' . $dbId;
$headers = ['Authorization: Bearer ' . $_SESSION['accurate_access_token']];

$response = makeApiRequest($openDbUrl, 'GET', $headers);

if ($response && isset($response['s']) && $response['s'] === true) {
    $_SESSION['accurate_session'] = $response['session'];
    
    // For API token method, we already have host from api-token.do
    if (!isset($_SESSION['accurate_host']) {
        $_SESSION['accurate_host'] = $response['host'];
    }
    
    $_SESSION['accurate_db_id'] = $dbId;
    
    echo 'Database opened successfully. Session: ' . htmlspecialchars($response['session']);
    echo '<br><a href="../customer/list.php">View Customers</a>';
} else {
    echo 'Failed to open database.';
    if (isset($response['d'][0])) {
        echo ' Error: ' . htmlspecialchars($response['d'][0]);
    }
}
?>