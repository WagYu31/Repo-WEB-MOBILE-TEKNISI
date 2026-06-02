<?php
include "conn.php"; // Pastikan koneksi database Anda disertakan

// Validasi request adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Validasi data yang diterima
    if (isset($_POST['kode_transaksi']) && !empty($_POST['kode_transaksi']) && isset($_POST['tanggal_lunas']) && !empty($_POST['tanggal_lunas'])) {
        
        $kode_transaksi = $_POST['kode_transaksi'];
        $tanggal_lunas = $_POST['tanggal_lunas'];

        // Siapkan statement untuk keamanan
        $sql = "UPDATE kegiatan SET lunas = ? WHERE kode = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $tanggal_lunas, $kode_transaksi);
            
            // Eksekusi statement
            if ($stmt->execute()) {
                echo "success"; // Kirim respons sukses ke AJAX
            } else {
                echo "Gagal mengeksekusi query: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            echo "Gagal menyiapkan statement: " . $conn->error;
        }

    } else {
        echo "Data tidak lengkap.";
    }

} else {
    // Jika bukan request POST, kirim error
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Metode request tidak valid.";
}

$conn->close();
?>