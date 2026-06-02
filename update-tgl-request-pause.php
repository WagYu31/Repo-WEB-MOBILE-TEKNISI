<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $syncDate = $_POST["syncDate"];
    $syncTime = $_POST["syncTime"];
    $status = "Reschedule2";

    $newDatetime = date("Y-m-d H:i:s", strtotime("$syncDate $syncTime"));
    
    $query = "SELECT * FROM reschedule WHERE id_kegiatan = $kegiatanId ORDER BY tanggal DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    $id_resc = $data["id_resc"];
    
    $q = "UPDATE kegiatan SET status = '$status' WHERE id_kegiatan = $kegiatanId";
    if(mysqli_query($conn, $q)){
        // Update tgl_request pada tabel kegiatan
        $sql = "UPDATE reschedule SET tanggal = '$newDatetime', status = '$status' WHERE id_resc = $id_resc";
    
        if (mysqli_query($conn, $sql)) {
            echo 'success';
        } else {
            echo 'error';
        }
    }
    else {
        echo 'error';
    }
}
?>
