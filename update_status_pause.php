<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $status = $_POST["status"];
    $tgl_mulai = $_POST["tgl_mulai"]; // Tambahkan ini untuk tanggal mulai
    $tgl_selesai = $_POST["tgl_selesai"];
    $lokasi_mulai = $_POST["lokasi_mulai"];
    $lokasi_selesai = $_POST["lokasi_selesai"]; // Tambahkan ini untuk tanggal selesai

    
    $rs = "SELECT * FROM reschedule WHERE id_kegiatan = $kegiatanId ORDER BY tanggal DESC LIMIT 1";
    $res = mysqli_query($conn, $rs);
    $data = mysqli_fetch_assoc($res);
    $id_resc = $data["id_resc"];
    
    $query = "UPDATE kegiatan SET status = '$status' WHERE id_kegiatan = $kegiatanId";
    if(mysqli_query($conn, $query)){
        // Lakukan pembaruan data di database
        $sql = "UPDATE reschedule SET status = '$status'";
        
        // Jika Anda ingin memperbarui tgl_mulai, tambahkan ke dalam kueri SQL
        if (!empty($tgl_mulai) && !empty($lokasi_mulai)) {
            $sql .= ", tgl_mulai = '$tgl_mulai', lokasi_mulai = '$lokasi_mulai'";
        }
        
        // Jika Anda ingin memperbarui tgl_selesai, tambahkan ke dalam kueri SQL
        if (!empty($tgl_selesai) && !empty($lokasi_selesai)) {
            $sql .= ", tgl_selesai = '$tgl_selesai', lokasi_selesai = '$lokasi_selesai'";
        }
    
        $sql .= " WHERE id_resc = $id_resc"; // Perbarui data berdasarkan ID kegiatan
    
        if (mysqli_query($conn, $sql)) {
            echo "success";
        } else {
            echo "error: " . mysqli_error($conn);
        }
    }

}

?>