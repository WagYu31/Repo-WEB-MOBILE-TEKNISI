<?php
include "conn.php";
include "session.php";

$filterBulan = $_GET['bulan'] ?? '';
$filterTeknisiId = intval($_GET['ftek'] ?? 0);

if (!empty($filterBulan)) {
    if (str_contains($filterBulan, '_3')) {
        $filterPeriode = '3';
        $current_date = str_replace('_3', '', $filterBulan);
        $endDt = new DateTime($current_date . '-01');
        $endDt->modify('+2 months');
        $current_date = $endDt->format('Y-m');
    } else {
        $filterPeriode = '1';
        $current_date = $filterBulan;
    }
} else {
    $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
    $filterPeriode = '1';
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Target_Tercapai_Teknisi_" . $current_date . ".xls");
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
            <th>Total Target Tercapai</th>
            <th>Total Bonus</th>
        </tr>
      </thead>";
echo "<tbody>";

$daftar_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$timestamp = strtotime($current_date);
$bulan = $daftar_bulan[(int)date('m', $timestamp)];
$tahun = date('Y', $timestamp);
$bulan_filter = date('m', $timestamp);
$tahun_filter = date('Y', $timestamp);
$ym = $current_date;

if ($filterPeriode == '3') {
    $dtStart = new DateTime($current_date . '-01');
    $dtStart->modify('-2 months');
    $monthStart = $dtStart->format('Y-m-d');
    $monthEnd = date('Y-m-t', $timestamp);
    $ymList = [];
    $dtTmp = clone $dtStart;
    for ($mi = 0; $mi < 3; $mi++) {
        $ymList[] = $dtTmp->format('Y-m');
        $dtTmp->modify('+1 month');
    }
    $ymCondition = implode(',', array_map(function($v) { return "'$v'"; }, $ymList));
} else {
    $monthStart = "$tahun_filter-$bulan_filter-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $ymList = [$ym];
    $ymCondition = "'$ym'";
}

// === Batch: all teknisi ===
$teknisiList = [];
$teknisiTargets = [];
$res_tek = mysqli_query($conn, "SELECT id, nama, target FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
while ($r = mysqli_fetch_assoc($res_tek)) {
    $teknisiList[$r['id']] = $r['nama'];
    $teknisiTargets[$r['id']] = floatval($r['target'] ?? 0);
}
$allTekIds = array_keys($teknisiList);

$tableRows = [];
$grand_total_fee = 0;
$grand_total_pendapatan = 0;
$grand_total_bonus = 0;

if (!empty($allTekIds)) {
    $placeholders = implode(',', array_fill(0, count($allTekIds), '?'));
    $types = str_repeat('i', count($allTekIds));

    // === Kegiatan count ===
    $kegiatanCount = [];
    $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total 
            FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id 
            WHERE tk.teknisi_id IN ($placeholders) 
            AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
            AND k.deleted_at IS NULL AND tk.deleted_at IS NULL
            GROUP BY tk.teknisi_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$allTekIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $kegiatanCount[$r['teknisi_id']] = $r['total'];
    $stmt->close();

    // === Selesai count ===
    $selesaiCount = [];
    $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total 
            FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id 
            WHERE tk.teknisi_id IN ($placeholders)
            AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
            AND k.status = 'selesai' AND k.deleted_at IS NULL AND tk.deleted_at IS NULL
            GROUP BY tk.teknisi_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$allTekIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $selesaiCount[$r['teknisi_id']] = $r['total'];
    $stmt->close();

    // === Invoice + Pendapatan ===
    $invCount = [];
    $pendapatanSum = [];
    $sql = "SELECT pk.teknisi_id, 
                   COUNT(DISTINCT pk.kode) as cnt, 
                   SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total
            FROM pendapatan_kegiatan pk
            JOIN (
                SELECT kode, COUNT(*) as tek_count 
                FROM pendapatan_kegiatan 
                WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($ymCondition) AND deleted_at IS NULL
                GROUP BY kode
            ) counts ON pk.kode = counts.kode
            WHERE pk.teknisi_id IN ($placeholders) 
            AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($ymCondition) 
            AND pk.deleted_at IS NULL
            GROUP BY pk.teknisi_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$allTekIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $invCount[$r['teknisi_id']] = $r['cnt'];
        $pendapatanSum[$r['teknisi_id']] = $r['total'];
    }
    $stmt->close();

    // === Fee 30k ===
    $feeKodes = [];
    $sql = "SELECT k.kode FROM kegiatan k 
            WHERE k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
            AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
            AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
            GROUP BY k.kode";
    $res = mysqli_query($conn, $sql);
    while ($r = mysqli_fetch_assoc($res)) $feeKodes[] = $r['kode'];

    $feeMap = [];
    if (!empty($feeKodes)) {
        $kodePlaceholders = implode(',', array_fill(0, count($feeKodes), '?'));
        $kodeTypes = str_repeat('s', count($feeKodes));
        $sql = "SELECT DISTINCT kode, teknisi_id FROM pelaksanaan_kegiatan WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($kodeTypes, ...$feeKodes);
        $stmt->execute();
        $res = $stmt->get_result();
        $kodeTeknisi = [];
        while ($r = $res->fetch_assoc()) $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
        $stmt->close();
        foreach ($kodeTeknisi as $kd => $tekIds) {
            $jml = count($tekIds);
            if ($jml > 0) {
                $share = 30000 / $jml;
                foreach ($tekIds as $tid => $_) {
                    if (!isset($feeMap[$tid])) $feeMap[$tid] = 0;
                    $feeMap[$tid] += $share;
                }
            }
        }
    }

    // === Grand Total Pendapatan ===
    $sqlGrandPend = "SELECT SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total
        FROM pendapatan_kegiatan pk
        JOIN (
            SELECT kode, COUNT(*) as tek_count
            FROM pendapatan_kegiatan
            WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($ymCondition) AND deleted_at IS NULL
            GROUP BY kode
        ) counts ON pk.kode = counts.kode
        WHERE DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($ymCondition) AND pk.deleted_at IS NULL";
    $resGP = mysqli_query($conn, $sqlGrandPend);
    $rowGP = mysqli_fetch_assoc($resGP);
    $grand_total_pendapatan = $rowGP['total'] ?? 0;
}

// Build rows
foreach ($teknisiList as $idT => $namaT) {
    if ($filterTeknisiId > 0 && $idT != $filterTeknisiId) continue;

    $keg = $kegiatanCount[$idT] ?? 0;
    $sel = $selesaiCount[$idT] ?? 0;
    $inv = $invCount[$idT] ?? 0;
    $fee = $feeMap[$idT] ?? 0;
    $pend = $pendapatanSum[$idT] ?? 0;
    $target = $teknisiTargets[$idT] ?? 0;
    $totalEarning = $fee + $pend;
    $bon = ($target > 0 && $totalEarning > $target) ? ($totalEarning - $target) * 0.60 : 0;
    $total = $fee + $pend + $bon;

    $grand_total_fee += $fee;
    $grand_total_bonus += $bon;

    $tableRows[] = compact('idT', 'namaT', 'keg', 'sel', 'inv', 'fee', 'pend', 'bon', 'total');
}

if ($filterTeknisiId > 0) {
    $grand_total_pendapatan = $pendapatanSum[$filterTeknisiId] ?? 0;
}
$grand_total_all = $grand_total_fee + $grand_total_pendapatan + $grand_total_bonus;

foreach ($tableRows as $tr) {
    echo "<tr>
            <td>" . htmlspecialchars($tr['namaT']) . "</td>
            <td align='center'>{$tr['keg']}</td>
            <td align='center'>{$tr['sel']}</td>
            <td align='center'>{$tr['inv']}</td>
            <td align='right'>{$tr['fee']}</td>
            <td align='right'>{$tr['pend']}</td>
            <td align='right'>{$tr['bon']}</td>
          </tr>";
}

echo "<tr style='background-color:#ddd; font-weight:bold;'>
        <td>TOTAL KESELURUHAN</td>
        <td colspan='3'></td>
        <td align='right'>$grand_total_fee</td>
        <td align='right'>$grand_total_pendapatan</td>
        <td align='right'>$grand_total_bonus</td>
      </tr>";
echo "</tbody></table>";
?>