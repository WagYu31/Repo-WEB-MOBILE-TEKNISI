<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

if (empty($_SESSION['accurate_access_token']) && empty($_SESSION['accurate_host'])) {
    die('Not authenticated. Please login first.');
}

$dbListUrl = 'https://account.accurate.id/api/db-list.do';
$headers = ['Authorization: Bearer ' . $_SESSION['accurate_access_token']];

$response = makeApiRequest($dbListUrl, 'GET', $headers);

if ($response && isset($response['d'])) {
    echo '<h1>Available Databases</h1>';
    echo '<ul>';
    foreach ($response['d'] as $db) {
        echo '<li>';
        echo htmlspecialchars($db['alias']) . ' (ID: ' . $db['id'] . ')';
        echo ' - <a href="open.php?id=' . $db['id'] . '">Select</a>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo 'Failed to get database list.';
    if (isset($response['error'])) {
        echo ' Error: ' . htmlspecialchars($response['error']);
    }
}
?>