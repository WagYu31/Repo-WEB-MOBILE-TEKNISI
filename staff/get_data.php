<?php
include 'conn.php'; // Sesuaikan dengan file koneksi database Anda

if (isset($_POST['code'])) {
    $code = $_POST['code'];

    // Query untuk mengambil data berdasarkan code
    $query = "SELECT qty_akhir, denda, keterangan, gambar_kembali FROM peminjaman_barang WHERE code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Mengembalikan data dalam format JSON
    echo json_encode($data);
}
?>
