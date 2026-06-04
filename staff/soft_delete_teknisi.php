<?php
include "conn.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo "Permintaan tidak valid.";
    exit();
}

$teknisi_id = intval($_POST['id']);

$stmt = $conn->prepare("UPDATE teknisi SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $teknisi_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo "success";
} else {
    http_response_code(500);
    echo "Gagal menonaktifkan teknisi.";
}

$stmt->close();
$conn->close();
?>
