<?php
include "conn.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $now = date('Y-m-d H:i:s');

    // Query untuk memperbarui tabel barang
    $sql = "UPDATE barang SET updated_at = ?, deleted_at = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $now, $now, $id);

    if ($stmt->execute()) {
        // Query untuk memperbarui tabel log dengan barang_id yang sama
        $log_sql = "UPDATE log SET updated_at = ?, deleted_at = ? WHERE barang_id = ?";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("ssi", $now, $now, $id);

        if ($log_stmt->execute()) {
            echo "Barang dan log berhasil dihapus!";
            header("Location: inventory.php");
            exit();
        } else {
            echo "Terjadi kesalahan saat memperbarui log: " . $log_stmt->error;
        }

        // Tutup statement log
        $log_stmt->close();
    } else {
        echo "Terjadi kesalahan: " . $stmt->error;
    }

    // Tutup statement barang
    $stmt->close();
} else {
    echo "ID barang tidak ditemukan.";
}

// Tutup koneksi
$conn->close();
?>
