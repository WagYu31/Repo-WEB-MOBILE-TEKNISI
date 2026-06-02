<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

if (empty($_GET['id'])) {
    die('Customer ID not specified.');
}

if (empty($_SESSION['accurate_access_token']) && empty($_SESSION['accurate_session'])) {
    die('Not authenticated or database not selected. Please login first.');
}

$customerId = $_GET['id'];
$apiUrl = $_SESSION['accurate_host'] . '/accurate/api/customer/detail.do?id=' . $customerId;
$headers = [
    'Authorization: Bearer ' . (isset($_SESSION['accurate_access_token']) ? $_SESSION['accurate_access_token'] : ACCURATE_API_TOKEN),
    'X-Session-ID: ' . $_SESSION['accurate_session']
];

$response = makeApiRequest($apiUrl, 'GET', $headers);

echo '<h1>Customer Details</h1>';

if ($response && isset($response['s']) && $response['s'] === true) {
    $customer = $response['d'];
    
    echo '<table border="1">';
    foreach ($customer as $key => $value) {
        if (is_array($value)) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars(print_r($value, true)) . '</td></tr>';
        } else {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
    }
    echo '</table>';
    
    echo '<br><a href="list.php">Back to List</a>';
} else {
    echo 'Failed to retrieve customer details.';
    if (isset($response['d'][0])) {
        echo ' Error: ' . htmlspecialchars($response['d'][0]);
    }
}
?>