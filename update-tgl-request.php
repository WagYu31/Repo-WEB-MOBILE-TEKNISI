<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $syncDate = $_POST["syncDate"];
    $syncTime = $_POST["syncTime"];
    $location = $_POST["location"];
    $status = "Reschedule";

    $newDatetime = date("Y-m-d H:i:s", strtotime("$syncDate $syncTime"));

    // Update tgl_request pada tabel kegiatan
    $sqlUpdate = "UPDATE kegiatan SET tgl_request = '$newDatetime', status = '$status', tgl_selesai = NOW(), lokasi_selesai = '$location' WHERE id_kegiatan = $kegiatanId";

    if (mysqli_query($conn, $sqlUpdate)) {
        // Ambil seluruh data dari tabel kegiatan berdasarkan id_kegiatan
        $sqlSelect = "SELECT * FROM kegiatan WHERE id_kegiatan = $kegiatanId";
        $result = mysqli_query($conn, $sqlSelect);
        $row = mysqli_fetch_assoc($result);

        // Insert data baru ke tabel kegiatan
        $sqlInsert = "INSERT INTO kegiatan (kode_transaksi, id_teknisi, id_cust, jenis, tgl_update, tgl_request, tgl_resechedule, tgl_mulai, lokasi_mulai, status, req_by)
                      VALUES ('" . $row['kode_transaksi'] . "', '" . $row['id_teknisi'] . "', '" . $row['id_cust'] . "', '" . $row['jenis'] . "','" . $row['tgl_update'] . "', '" . $row['tgl_request'] . "', NOW(), '" . $row['tgl_mulai'] . "', '" . $row['lokasi_mulai'] . "', '$status','" . $row['req_by'] . "'";
        if (mysqli_query($conn, $sqlInsert)) {
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }
}
?>

