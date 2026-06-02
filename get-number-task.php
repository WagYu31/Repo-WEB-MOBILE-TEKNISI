<?php

include "conn.php";

$qq = "SELECT * FROM user WHERE id_user = $id_user";
$resultqq = mysqli_query($conn, $qq);
$rqq = mysqli_fetch_assoc($resultqq);
$id_tek = $rqq["id_teknisi"];

// Hitung jumlah data kegiatan dengan status "Waiting"
$sql = "SELECT COUNT(*) AS task_count FROM kegiatan WHERE FIND_IN_SET('$id_tek', id_teknisi) > 0 AND status != 'Clear'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$taskCount = $row['task_count'];

?>