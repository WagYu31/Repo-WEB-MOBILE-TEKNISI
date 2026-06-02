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
$apiUrl = $_SESSION['accurate_host'] . '/accurate/api/customer/delete.do';
$headers = [
    'Authorization: Bearer ' . (isset($_SESSION['accurate_access_token']) ? $_SESSION['accurate_access_token'] : ACCURATE_API_TOKEN),
    'X-Session-ID: ' . $_SESSION['accurate_session'],
    'Content-Type: application/x-www-form-urlencoded'
];

$postData = http_build_query(['id' => $customerId]);
$response = makeApiRequest($apiUrl, 'POST', $headers, $postData);

if ($response && isset($response['s']) && $response['s'] === true) {
    $_SESSION['message'] = 'Customer deleted successfully!';
} else {
    $_SESSION['error'] = 'Failed to delete customer.';
    if (isset($response['d'][0])) {
        $_SESSION['error'] .= ' Error: ' . $response['d'][0];
    }
}

header('Location: list.php');
exit;
?>