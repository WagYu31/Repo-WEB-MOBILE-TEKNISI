<?php
include "conn.php";

echo "<h3>1. Mengecek isi kolom req_invoice_at pada tabel kegiatan:</h3>";
$q = mysqli_query($conn, "SELECT id, kode, kegiatan, status, req_invoice_at, deleted_at FROM kegiatan WHERE req_invoice_at IS NOT NULL ORDER BY id DESC LIMIT 10");
if ($q && mysqli_num_rows($q) > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Kode/ID Kegiatan</th><th>Jenis</th><th>Status</th><th>Req Invoice At</th></tr>";
    while ($row = mysqli_fetch_assoc($q)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['kode'] . "</td>";
        echo "<td>" . $row['kegiatan'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['req_invoice_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Tidak ada data kegiatan dengan req_invoice_at (NULL atau kosong semua).</p>";
}

echo "<h3>2. Mengecek rincian tugas PT.YAMAHA WJ (efxYE9):</h3>";
$q2 = mysqli_query($conn, "SELECT id, kode, kegiatan, status, req_invoice_at, deleted_at FROM kegiatan WHERE kode = 'efxYE9'");
if ($q2 && mysqli_num_rows($q2) > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Kode/ID Kegiatan</th><th>Jenis</th><th>Status</th><th>Req Invoice At</th><th>Deleted At</th></tr>";
    while ($row = mysqli_fetch_assoc($q2)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['kode'] . "</td>";
        echo "<td>" . $row['kegiatan'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['req_invoice_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['deleted_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Tugas dengan kode efxYE9 tidak ditemukan.</p>";
}
?>
