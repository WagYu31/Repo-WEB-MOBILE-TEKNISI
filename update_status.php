<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $status = $_POST["status"];
    $tgl_mulai = $_POST["tgl_mulai"]; // Tambahkan ini untuk tanggal mulai
    $tgl_selesai = $_POST["tgl_selesai"];
    $lokasi_mulai = $_POST["lokasi_mulai"];
    $lokasi_selesai = $_POST["lokasi_selesai"]; // Tambahkan ini untuk tanggal selesai
    $ket_finish  = $_POST["ket_finish"];
    $gambarFinish = $_POST["gambar_finish"];
    // $ket_mulai  = $_POST["ket_start"];
    // $gambarMulai = $_POST["gambar_start"];

    // Lakukan validasi data jika diperlukan

    // Lakukan pembaruan data di database
    $sql = "UPDATE kegiatan SET status = '$status'";
    
    // Jika Anda ingin memperbarui tgl_mulai, tambahkan ke dalam kueri SQL
    if (!empty($tgl_mulai) && !empty($lokasi_mulai)) {
        $sql .= ", tgl_mulai = '$tgl_mulai', lokasi_mulai = '$lokasi_mulai'";
    }
    
    // Jika Anda ingin memperbarui tgl_selesai, tambahkan ke dalam kueri SQL
    if (!empty($tgl_selesai) && !empty($lokasi_selesai) && !empty($ket_finish)) {
        $sql .= ", tgl_selesai = '$tgl_selesai', lokasi_selesai = '$lokasi_selesai', ket_finish = '$ket_finish', gambar_finish = '$gambarFinish'";
    }

    $sql .= " WHERE id_kegiatan = $kegiatanId"; // Perbarui data berdasarkan ID kegiatan

    if (mysqli_query($conn, $sql)) {
        echo "success"; 
    } else {
        echo "error: " . mysqli_error($conn);
    }
}

?>