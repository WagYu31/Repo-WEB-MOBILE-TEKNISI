<?php
require_once '../config.php';
require_once '../helpers/api_request.php';

// Check authentication and database session
if (empty($_SESSION['accurate_access_token']) && empty($_SESSION['accurate_session'])) {
    die('Not authenticated or database not selected. Please login first.');
}

// Prepare API URL and headers
$apiUrl = $_SESSION['accurate_host'] . '/accurate/api/customer/list.do';
$headers = [
    'Authorization: Bearer ' . (isset($_SESSION['accurate_access_token']) ? $_SESSION['accurate_access_token'] : ACCURATE_API_TOKEN),
    'X-Session-ID: ' . $_SESSION['accurate_session']
];

// Optional filters
$params = [];
if (isset($_GET['search'])) {
    $params['filter.keywords.val[0]'] = $_GET['search'];
}

// Fields to retrieve
$params['fields'] = 'id,name,customerNo,email,workPhone';

// Make the API request
$response = makeApiRequest($apiUrl . '?' . http_build_query($params), 'GET', $headers);

// Display results
echo '<h1>Customer List</h1>';

// Search form
echo '<form method="GET" action="list.php">
    <input type="text" name="search" placeholder="Search customers..." value="' . (isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '') . '">
    <button type="submit">Search</button>
</form>';

if ($response && isset($response['s']) && $response['s'] === true) {
    echo '<table border="1">
        <tr>
            <th>ID</th>
            <th>Customer No</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>';
    
    foreach ($response['d'] as $customer) {
        echo '<tr>
            <td>' . htmlspecialchars($customer['id']) . '</td>
            <td>' . htmlspecialchars($customer['customerNo'] ?? '') . '</td>
            <td>' . htmlspecialchars($customer['name']) . '</td>
            <td>' . htmlspecialchars($customer['email'] ?? '') . '</td>
            <td>' . htmlspecialchars($customer['workPhone'] ?? '') . '</td>
            <td>
                <a href="detail.php?id=' . $customer['id'] . '">View</a> | 
                <a href="save.php?id=' . $customer['id'] . '">Edit</a> | 
                <a href="delete.php?id=' . $customer['id'] . '" onclick="return confirm(\'Are you sure?\')">Delete</a>
            </td>
        </tr>';
    }
    
    echo '</table>';
    
    // Pagination info
    if (isset($response['sp'])) {
        echo '<div>Page ' . $response['sp']['page'] . ' of ' . $response['sp']['pageCount'] . '</div>';
    }
    
    // Add new customer button
    echo '<br><a href="save.php">Add New Customer</a>';
} else {
    echo 'Failed to retrieve customer list.';
    if (isset($response['d'][0])) {
        echo ' Error: ' . htmlspecialchars($response['d'][0]);
    }
}
?>