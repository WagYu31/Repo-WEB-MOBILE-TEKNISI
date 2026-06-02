<?php
include "../../conn.php";
// update_status_tagihan.php

// Lakukan koneksi ke database jika belum terkoneksi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $idTagihan = $_POST["idTagihan"];
  $newStatus = $_POST["newStatus"];

  // Lakukan query untuk memperbarui status tagihan di database
  $queryUpdateStatus = "UPDATE tagihan SET status = '$newStatus' WHERE id_tagihan = '$idTagihan'";
  $resultUpdateStatus = mysqli_query($conn, $queryUpdateStatus);

  if ($resultUpdateStatus) {
    echo "success";
  } else {
    echo "error";
  }
}
?>
