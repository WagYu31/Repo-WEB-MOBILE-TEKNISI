<?php
include 'conn.php';
include 'session.php';

if (isset($_GET['idpeminjaman'])) {
    $idpeminjaman = $_GET['idpeminjaman'];

    // Query untuk mengambil data peminjaman berdasarkan idpeminjaman
    $sql = "SELECT code, keterangan, qty_akhir, denda FROM peminjaman_barang WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idpeminjaman);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
    
    $stmt->close();
}
?>
