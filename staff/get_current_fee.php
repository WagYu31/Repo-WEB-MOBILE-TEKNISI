<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

$response = ['success' => false, 'fee' => null];

$stmt = $conn->prepare("SELECT nilai FROM noinv WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['fee'] = $row['nilai'];
    }
    $stmt->close();
}

echo json_encode($response);
?>