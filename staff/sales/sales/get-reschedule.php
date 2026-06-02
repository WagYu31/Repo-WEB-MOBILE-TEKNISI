<?php
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data yang dikirim dari form
    $rescheduleDate = $_POST["rescheduleDate"];
    $rescheduleTime = $_POST["rescheduleTime"];
    $kegiatanId = $_POST["kegiatanId"];
    $rescheduleDateTime = $rescheduleDate . " " . $rescheduleTime;
    
    $query = "UPDATE kegiatan SET tgl_request = '$rescheduleDateTime' WHERE id_kegiatan = '$kegiatanId'";
    if (mysqli_query($conn, $query)) {
        header("location: index.php");
    } else {
        echo "Error: " . mysqli_error($conn);
    }

}