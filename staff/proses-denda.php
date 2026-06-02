<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan semua data yang dibutuhkan telah dikirim
    if (isset($_POST['idT'], $_POST['kodeTransaksi'], $_POST['nominalDenda'])) {
        
        // Ambil data yang dikirimkan
        $idT = $_POST['idT'];
        $kodeTransaksi = $_POST['kodeTransaksi'];
        $nominalDenda = $_POST['nominalDenda'];
        
        // Persiapkan pernyataan SQL untuk memperbarui data kegiatan
        $sql = "UPDATE kegiatan SET denda = ? WHERE id_teknisi = ? AND kode_transaksi = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "dss", $nominalDenda, $idT, $kodeTransaksi);
            mysqli_stmt_execute($stmt);
            echo "Denda berhasil ditambahkan!";
        } else {
            echo "Gagal memproses permintaan.";
        }
    } else {
        echo "Data yang diperlukan tidak lengkap.";
    }
} else {
    echo "Permintaan tidak valid.";
}
?>
