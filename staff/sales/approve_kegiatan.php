<?php
include "conn.php";
include "session.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID kegiatan tidak valid.";
    exit();
}

$id = intval($_GET['id']);

// Update status to 'dijadwalkan'
$stmt = $conn->prepare("UPDATE kegiatan_sales SET status = 'dijadwalkan', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index-sa.php");
    exit();
} else {
    echo "Gagal menyetujui kegiatan: " . $conn->error;
}
?>
