<?php
error_reporting(0); // Sembunyikan semua PHP warning/notice dari output
ob_start(); // Mulai output buffering
include "conn.php"; // Pastikan koneksi database Anda disertakan
ob_clean(); // Bersihkan output apapun yang dihasilkan oleh conn.php atau include lainnya

// Validasi request adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Validasi data yang diterima
    if (isset($_POST['kode_transaksi']) && !empty($_POST['kode_transaksi']) && isset($_POST['tanggal_lunas']) && !empty($_POST['tanggal_lunas'])) {
        
        $kode_transaksi = $_POST['kode_transaksi'];
        $tanggal_lunas = $_POST['tanggal_lunas'];

        // Tulis log request masuk
        file_put_contents('lunas_debug.log', date('Y-m-d H:i:s') . " - POST: kode=" . $kode_transaksi . ", tanggal_raw=" . $tanggal_lunas . "\n", FILE_APPEND);

        // Normalisasi format tanggal dari DD/MM/YYYY atau DD-MM-YYYY menjadi YYYY-MM-DD
        $date_parsed = DateTime::createFromFormat('d/m/Y', $tanggal_lunas);
        if ($date_parsed) {
            $tanggal_lunas = $date_parsed->format('Y-m-d');
        } else {
            $date_parsed = DateTime::createFromFormat('d-m-Y', $tanggal_lunas);
            if ($date_parsed) {
                $tanggal_lunas = $date_parsed->format('Y-m-d');
            }
        }

        // Tulis log hasil parsing
        file_put_contents('lunas_debug.log', date('Y-m-d H:i:s') . " - Parsed: tanggal_parsed=" . $tanggal_lunas . "\n", FILE_APPEND);

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