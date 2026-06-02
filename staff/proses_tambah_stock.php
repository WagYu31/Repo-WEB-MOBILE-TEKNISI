<?php
include "conn.php";
include "session.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $qty = $_POST['qty'];
    $now = date('Y-m-d H:i:s');

    // Mendapatkan nama user dari sesi
    $queryUser = "SELECT name FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($queryUser);
    $stmtUser->bind_param("i", $idSesi);  // Pastikan $idSesi berasal dari session
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows > 0) {
        $rowUser = $resultUser->fetch_assoc();
        $namaUser = $rowUser['name'];
    } else {
        echo "Error: User tidak ditemukan dalam sesi.";
        exit();
    }
    $stmtUser->close();

    // Update stok di tabel barang
    $sqlUpdateBarang = "UPDATE barang SET stok = stok + ?, updated_at = ? WHERE id = ?";
    $stmtBarang = $conn->prepare($sqlUpdateBarang);
    $stmtBarang->bind_param("isi", $qty, $now, $id);

    if ($stmtBarang->execute()) {
        // Masukkan log ke tabel log
        $log_sql = "INSERT INTO log (barang_id, nama, log, qty, keterangan, updated_at) VALUES (?, ?, 'masuk', ?, 'Tambah Barang', ?)";
        $stmtLog = $conn->prepare($log_sql);
        $stmtLog->bind_param("isis", $id, $namaUser, $qty, $now);

        if ($stmtLog->execute()) {
            echo "Stok berhasil ditambahkan!";
            header("Location: inventory.php");
            exit();
        } else {
            echo "Terjadi kesalahan saat menambah log: " . $stmtLog->error;
        }

        $stmtLog->close();
    } else {
        echo "Terjadi kesalahan saat memperbarui stok: " . $stmtBarang->error;
    }

    $stmtBarang->close();
}

$conn->close();
