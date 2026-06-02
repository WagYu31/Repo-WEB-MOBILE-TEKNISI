<?php
include "conn.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo "Permintaan tidak valid.";
    exit();
}

$teknisi_id = $_POST['id'];

$stmt = $conn->prepare("UPDATE teknisi SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $teknisi_id);

if ($stmt->execute()) {
    echo "success";
} else {
    http_response_code(500);
    echo "Gagal memperbarui data.";
}

$stmt->close();
$conn->close();
?>