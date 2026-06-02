<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

if (empty($_SESSION['accurate_access_token']) && empty($_SESSION['accurate_session'])) {
    die('Not authenticated or database not selected. Please login first.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiUrl = $_SESSION['accurate_host'] . '/accurate/api/customer/save.do';
    $headers = [
        'Authorization: Bearer ' . (isset($_SESSION['accurate_access_token']) ? $_SESSION['accurate_access_token'] : ACCURATE_API_TOKEN),
        'X-Session-ID: ' . $_SESSION['accurate_session'],
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    // Prepare customer data
    $postData = [
        'name' => $_POST['name'],
        'transDate' => date('d/m/Y'),
        'email' => $_POST['email'],
        'workPhone' => $_POST['workPhone'],
        'billStreet' => $_POST['billStreet'] ?? '',
        'billCity' => $_POST['billCity'] ?? '',
        'billProvince' => $_POST['billProvince'] ?? '',
        'billCountry' => $_POST['billCountry'] ?? '',
        'billZipCode' => $_POST['billZipCode'] ?? ''
    ];
    
    // For update, include the ID
    if (!empty($_POST['id'])) {
        $postData['id'] = $_POST['id'];
    }
    
    $response = makeApiRequest($apiUrl, 'POST', $headers, http_build_query($postData));
    
    if ($response && isset($response['s']) && $response['s'] === true) {
        $_SESSION['message'] = 'Customer saved successfully!';
        header('Location: list.php');
        exit;
    } else {
        $error = 'Failed to save customer.';
        if (isset($response['d'][0])) {
            $error .= ' Error: ' . $response['d'][0];
        }
    }
}

// For edit, load existing data
$customer = [];
if (isset($_GET['id'])) {
    $apiUrl = $_SESSION['accurate_host'] . '/accurate/api/customer/detail.do?id=' . $_GET['id'];
    $headers = [
        'Authorization: Bearer ' . (isset($_SESSION['accurate_access_token']) ? $_SESSION['accurate_access_token'] : ACCURATE_API_TOKEN),
        'X-Session-ID: ' . $_SESSION['accurate_session']
    ];
    
    $response = makeApiRequest($apiUrl, 'GET', $headers);
    
    if ($response && isset($response['s']) && $response['s'] === true) {
        $customer = $response['d'];
    }
}

// Display form
echo '<h1>' . (empty($customer) ? 'Add New Customer' : 'Edit Customer') . '</h1>';

if (isset($error)) {
    echo '<div style="color:red;">' . htmlspecialchars($error) . '</div>';
}

echo '<form method="POST" action="save.php">';
if (!empty($customer['id'])) {
    echo '<input type="hidden" name="id" value="' . htmlspecialchars($customer['id']) . '">';
}

echo '
    <div>
        <label>Name:</label>
        <input type="text" name="name" value="' . htmlspecialchars($customer['name'] ?? '') . '" required>
    </div>
    <div>
        <label>Email:</label>
        <input type="email" name="email" value="' . htmlspecialchars($customer['email'] ?? '') . '">
    </div>
    <div>
        <label>Phone:</label>
        <input type="text" name="workPhone" value="' . htmlspecialchars($customer['workPhone'] ?? '') . '">
    </div>
    <h3>Billing Address</h3>
    <div>
        <label>Street:</label>
        <input type="text" name="billStreet" value="' . htmlspecialchars($customer['billStreet'] ?? '') . '">
    </div>
    <div>
        <label>City:</label>
        <input type="text" name="billCity" value="' . htmlspecialchars($customer['billCity'] ?? '') . '">
    </div>
    <div>
        <label>Province:</label>
        <input type="text" name="billProvince" value="' . htmlspecialchars($customer['billProvince'] ?? '') . '">
    </div>
    <div>
        <label>Country:</label>
        <input type="text" name="billCountry" value="' . htmlspecialchars($customer['billCountry'] ?? '') . '">
    </div>
    <div>
        <label>Zip Code:</label>
        <input type="text" name="billZipCode" value="' . htmlspecialchars($customer['billZipCode'] ?? '') . '">
    </div>
    <button type="submit">Save</button>
    <a href="list.php">Cancel</a>
</form>';
?>