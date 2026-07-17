<?php
include "conn.php";
include "session.php";

$query = "UPDATE pelaksanaan_sales SET ci_at = NULL, co_at = NULL WHERE status = 'dibatalkan' AND catatan_visit LIKE '%[Reschedule]%'";
if (mysqli_query($conn, $query)) {
    echo "<h3>Sukses membersihkan data uji coba!</h3><p>Silakan refresh halaman detail kegiatan Anda sekarang.</p>";
} else {
    echo "<h3>Gagal:</h3><p>" . mysqli_error($conn) . "</p>";
}
?>
