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
$ym = $current_date;
$monthStart = "$tahun_filter-$bulan_filter-01";
$monthEnd = date('Y-m-t', strtotime($monthStart));

// === Batch: all teknisi ===
$teknisiList = [];
$teknisiTargets = [];
$res_tek = mysqli_query($conn, "SELECT id, nama, target FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
while ($r = mysqli_fetch_assoc($res_tek)) {
    $teknisiList[$r['id']] = $r['nama'];
    $teknisiTargets[$r['id']] = floatval($r['target'] ?? 0);
}
$allTekIds = array_keys($teknisiList);

if (!empty($allTekIds)) {
    $placeholders = implode(',', array_fill(0, count($allTekIds), '?'));
    $types = str_repeat('i', count($allTekIds));

    // === Kegiatan count ===
    $kegiatanCount = [];
    $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id IN ($placeholders) AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY) AND k.deleted_at IS NULL AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
    $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$allTekIds); $stmt->execute();
    $res = $stmt->get_result(); while ($r = $res->fetch_assoc()) $kegiatanCount[$r['teknisi_id']] = $r['total']; $stmt->close();

    // === Selesai count ===
    $selesaiCount = [];
    $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id IN ($placeholders) AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY) AND k.status = 'selesai' AND k.deleted_at IS NULL AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
    $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$allTekIds); $stmt->execute();
    $res = $stmt->get_result(); while ($r = $res->fetch_assoc()) $selesaiCount[$r['teknisi_id']] = $r['total']; $stmt->close();

    // === Invoice + Pendapatan ===
    $invCount = []; $pendapatanSum = [];
    $sql = "SELECT pk.teknisi_id, COUNT(DISTINCT pk.kode) as cnt, SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total FROM pendapatan_kegiatan pk JOIN (SELECT kode, COUNT(*) as tek_count FROM pendapatan_kegiatan WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL GROUP BY kode) counts ON pk.kode = counts.kode WHERE pk.teknisi_id IN ($placeholders) AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL GROUP BY pk.teknisi_id";
    $stmt = $conn->prepare($sql); $paramTypes = 's' . $types . 's'; $paramVals = array_merge([$ym], $allTekIds, [$ym]);
    $stmt->bind_param($paramTypes, ...$paramVals); $stmt->execute();
    $res = $stmt->get_result(); while ($r = $res->fetch_assoc()) { $invCount[$r['teknisi_id']] = $r['cnt']; $pendapatanSum[$r['teknisi_id']] = $r['total']; } $stmt->close();

    // === Fee 30k ===
    $feeKodes = [];
    $sql = "SELECT k.kode FROM kegiatan k WHERE k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY) AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode) GROUP BY k.kode";
    $res = mysqli_query($conn, $sql); while ($r = mysqli_fetch_assoc($res)) $feeKodes[] = $r['kode'];
    $feeMap = [];
    if (!empty($feeKodes)) {
        $kodePlaceholders = implode(',', array_fill(0, count($feeKodes), '?'));
        $kodeTypes = str_repeat('s', count($feeKodes));
        $sql = "SELECT DISTINCT kode, teknisi_id FROM pelaksanaan_kegiatan WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
        $stmt = $conn->prepare($sql); $stmt->bind_param($kodeTypes, ...$feeKodes); $stmt->execute();
        $res = $stmt->get_result(); $kodeTeknisi = [];
        while ($r = $res->fetch_assoc()) $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
        $stmt->close();
        foreach ($kodeTeknisi as $kd => $tekIds) { $jml = count($tekIds); if ($jml > 0) { $share = 30000 / $jml; foreach ($tekIds as $tid => $_) { if (!isset($feeMap[$tid])) $feeMap[$tid] = 0; $feeMap[$tid] += $share; } } }
    }
}

// ═══ GRAND TOTAL PENDAPATAN: Match Detail Invoice exactly ═══
$grand_total_pendapatan = 0;
$sqlGrandPend = "SELECT SUM(sub.nominal) as total FROM (
    SELECT nominal_invoice as nominal
    FROM pendapatan_kegiatan
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
    GROUP BY kode
) sub";
$stmtGP = $conn->prepare($sqlGrandPend);
$stmtGP->bind_param('s', $ym);
$stmtGP->execute();
$resGP = $stmtGP->get_result();
$rowGP = $resGP->fetch_assoc();
$grand_total_pendapatan = $rowGP['total'] ?? 0;
$stmtGP->close();

// === Build rows ===
foreach ($teknisiList as $idT => $namaT) {
    $total_k = $kegiatanCount[$idT] ?? 0;
    $total_s = $selesaiCount[$idT] ?? 0;
    $total_i = $invCount[$idT] ?? 0;
    $fee_val = $feeMap[$idT] ?? 0;
    $inc_val = $pendapatanSum[$idT] ?? 0;
    
    $target = $teknisiTargets[$idT] ?? 0;
    $totalEarning = $fee_val + $inc_val;
    $bns_val = ($target > 0 && $totalEarning > $target) ? ($totalEarning - $target) * 0.60 : 0;

    $g_fee += $fee_val; $g_bns += $bns_val;

    echo "<tr>
            <td>$namaT</td>
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
        <td align='right'>$grand_total_pendapatan</td>
        <td align='right'>$g_bns</td>
      </tr>";
echo "</tbody></table>";
?>