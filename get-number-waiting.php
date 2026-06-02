<?php

include "conn.php";
// Hitung jumlah data kegiatan dengan status "Waiting"
$sql = "SELECT COUNT(*) AS waiting_count FROM kegiatan WHERE status = 'Waiting'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$waitingCount = $row['waiting_count'];

?>