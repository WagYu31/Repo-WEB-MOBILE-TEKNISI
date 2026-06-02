<?php
include "conn.php";
include "session.php";

$current_date = $_GET['cariBulanTahun'] ?? date("Y-m");
$bulan_filter = date('m', strtotime($current_date));
$tahun_filter = date('Y', strtotime($current_date));

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Pendapatan_Teknisi_" . $current_date . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<thead>
        <tr style='background-color:#007bff; color:#ffffff;'>
            <th>Teknisi</th>
            <th>Jumlah Kegiatan</th>
            <th>Jumlah Kegiatan Selesai</th>
            <th>Jumlah Invoice</th>
            <th>Total Fee (30k)</th>
            <th>Total Pendapatan</th>
            <th>Total Bonus</th>
        </tr>
      </thead>";
echo "<tbody>";

$g_fee = $g_inc = $g_bns = 0;
$sql_tek = "SELECT id, nama FROM teknisi ORDER BY nama ASC";
$res_tek = mysqli_query($conn, $sql_tek);

while ($row = mysqli_fetch_assoc($res_tek)) {
    $idT = $row['id'];

    $sql_k = "SELECT COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id = ? AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL";
    $st = $conn->prepare($sql_k); $st->bind_param("isi", $idT, $bulan_filter, $tahun_filter); $st->execute();
    $total_k = $st->get_result()->fetch_assoc()['total'] ?? 0;

    $sql_s = "SELECT COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id = ? AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.status = 'selesai' AND k.deleted_at IS NULL";
    $st = $conn->prepare($sql_s); $st->bind_param("isi", $idT, $bulan_filter, $tahun_filter); $st->execute();
    $total_s = $st->get_result()->fetch_assoc()['total'] ?? 0;

    $sql_i = "SELECT COUNT(DISTINCT kode) as cnt, SUM(pendapatan) as inc FROM pendapatan_kegiatan WHERE teknisi_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL";
    $st = $conn->prepare($sql_i); $st->bind_param("is", $idT, $current_date); $st->execute();
    $res_i = $st->get_result()->fetch_assoc();
    $total_i = $res_i['cnt'] ?? 0;
    $inc_val = $res_i['inc'] ?? 0;

    $fee_val = 0;
    $sql_f = "SELECT k.kode FROM kegiatan k WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode) GROUP BY k.kode";
    $st_f = $conn->prepare($sql_f); $st_f->bind_param("si", $bulan_filter, $tahun_filter); $st_f->execute();
    $res_f = $st_f->get_result();
    while ($f = $res_f->fetch_assoc()) {
        $kd = $f['kode'];
        $sql_a = "SELECT COUNT(DISTINCT teknisi_id) as jml FROM pelaksanaan_kegiatan WHERE kode = ? AND waktu_mulai IS NOT NULL";
        $st_a = $conn->prepare($sql_a); $st_a->bind_param("s", $kd); $st_a->execute();
        $jml_a = $st_a->get_result()->fetch_assoc()['jml'] ?? 0;
        if ($jml_a > 0) {
            $sql_me = "SELECT 1 FROM pelaksanaan_kegiatan WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL LIMIT 1";
            $st_me = $conn->prepare($sql_me); $st_me->bind_param("si", $kd, $idT); $st_me->execute();
            if ($st_me->get_result()->num_rows > 0) { $fee_val += (30000 / $jml_a); }
        }
    }

    $sql_b = "SELECT SUM(bonus) as total FROM pendapatan_fix WHERE teknisi_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL";
    $st = $conn->prepare($sql_b); $st->bind_param("is", $idT, $current_date); $st->execute();
    $bns_val = $st->get_result()->fetch_assoc()['total'] ?? 0;

    $g_fee += $fee_val; $g_inc += $inc_val; $g_bns += $bns_val;

    echo "<tr>
            <td>{$row['nama']}</td>
            <td align='center'>$total_k</td>
            <td align='center'>$total_s</td>
            <td align='center'>$total_i</td>
            <td align='right'>$fee_val</td>
            <td align='right'>$inc_val</td>
            <td align='right'>$bns_val</td>
          </tr>";
}

echo "<tr style='background-color:#ddd; font-weight:bold;'>
        <td>TOTAL KESELURUHAN</td>
        <td colspan='3'></td>
        <td align='right'>$g_fee</td>
        <td align='right'>$g_inc</td>
        <td align='right'>$g_bns</td>
      </tr>";
echo "</tbody></table>";
?>