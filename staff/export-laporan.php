<?php
include 'conn.php'; // Pastikan file koneksi database sudah benar

if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
    $current_date = $_GET['cariBulanTahun'];
} else {
    $current_date = date('Y-m');
}

// Set header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Kegiatan_{$current_date}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Query untuk mengambil data laporan
$sql = "SELECT k.*, k.kode AS kode_transaksi, t.nama_teknisi, c.id AS id_cust, c.nama AS nama_cust, i.no_invoice AS invoice, i.nominal_invoice
        FROM kegiatan k
        LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
        LEFT JOIN customer c ON k.customer_id = c.id
        LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
        INNER JOIN pelaksanaan_kegiatan pk ON k.kode = pk.kode
        WHERE k.status != 'waiting' AND (k.paid IS NULL OR k.paid = '') AND k.deleted_at IS NULL
        AND DATE_FORMAT(pk.waktu_selesai, '%Y-%m') = ?
        GROUP BY k.kode ORDER BY k.jadwal DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr>
        <th>Customer</th>
        <th>Status</th>
        <th>Teknisi</th>
        <th>Mulai</th>
        <th>Selesai</th>
    </tr>";

while ($row = $result->fetch_assoc()) {
    $sqlLapTek = "SELECT p.*, t.*, p.kode AS kode_pelaksanaan, c.nama AS nama_customer,
                  MIN(p.waktu_mulai) AS waktu_mulai_pertama, MAX(p.waktu_selesai) AS waktu_selesai_terakhir
                  FROM pelaksanaan_kegiatan p
                  JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                  JOIN kegiatan k ON t.kegiatan_id = k.id
                  JOIN customer c ON k.customer_id = c.id
                  WHERE p.kode = ? AND k.customer_id = ?
                  GROUP BY p.teknisi_id";
    $stmtLapTek = $conn->prepare($sqlLapTek);
    $stmtLapTek->bind_param("si", $row['kode_transaksi'], $row['id_cust']);
    $stmtLapTek->execute();
    $resLapTek = $stmtLapTek->get_result();
    $rows = $resLapTek->fetch_all(MYSQLI_ASSOC);

    if (!empty($rows)) {
        foreach ($rows as $index => $rowLT) {
            echo "<tr>";
            if ($index === 0) {
                echo "<td rowspan='" . count($rows) . "'>" . $rowLT['nama_customer'] . "</td>";
            }
            echo "<td>" . $rowLT['status'] . "</td>";
            echo "<td>" . $rowLT['nama_teknisi'] . "</td>";
            echo "<td>" . (
                ($rowLT['waktu_mulai_pertama'] && $rowLT['waktu_mulai_pertama'] != '0000-00-00 00:00:00')
                    ? date('d-m-Y H:i', strtotime($rowLT['waktu_mulai_pertama']))
                    : '-' 
            ) . "</td>";
            echo "<td>" . (
                ($rowLT['waktu_selesai_terakhir'] && $rowLT['waktu_selesai_terakhir'] != '0000-00-00 00:00:00')
                    ? date('d-m-Y H:i', strtotime($rowLT['waktu_selesai_terakhir']))
                    : '-' 
            ) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>Tidak ada kegiatan.</td></tr>";
    }
}

echo "</table>";
exit();
?>
