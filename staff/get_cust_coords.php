<?php
header('Content-Type: application/json');
include 'conn.php';

$response = [];

if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode($response);
    exit;
}

$customer_id = (int)$_GET['customer_id'];

$stmt = $conn->prepare("SELECT alias, lat, lon, rad, address FROM cust_coordinate WHERE cust_id = ? AND deleted_at IS NULL ORDER BY id DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>