<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Target Tercapai";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Rekap Target Tercapai Teknisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .table th { background-color: #f1f4f8 !important; color: #333; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; }
        .table td { font-size: 13px; vertical-align: middle; border-bottom: 1px solid #eee; }
        .total-row { background-color: #e9ecef !important; font-weight: bold; }
        
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; margin: 0; padding: 0; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; }
            .card-body { padding: 0 !important; }
            .table { width: 100% !important; margin: 0 !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row no-print mb-3">
            <div class="col-12 d-flex justify-content-start gap-2">
                <button onclick="window.print()" class="btn btn-primary d-flex align-items-center">
                    <i class="material-icons text-sm me-1">print</i> Cetak PDF
                </button>
                <?php 
                $filterBulan = $_GET['bulan'] ?? '';
                $filterTeknisiId = intval($_GET['ftek'] ?? 0);

                $daftar_bulan = [
                    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];

                if (!empty($filterBulan) && str_contains($filterBulan, '_to_')) {
                    $filterPeriode = 'custom';
                    $parts = explode('_to_', $filterBulan);
                    $startMonth = $parts[0];
                    $endMonth = $parts[1] ?? $parts[0];
                    $dtStart = new DateTime($startMonth . '-01');
                    $dtEnd = new DateTime($endMonth . '-01');
                    if ($dtStart > $dtEnd) {
                        $tmp = $dtStart; $dtStart = $dtEnd; $dtEnd = $tmp;
                        $startMonth = $dtStart->format('Y-m');
                        $endMonth = $dtEnd->format('Y-m');
                    }
                    $monthStart = $dtStart->format('Y-m-01');
                    $monthEnd = $dtEnd->format('Y-m-t');
                    
                    $ymList = [];
                    $dtTmp = clone $dtStart;
                    while ($dtTmp <= $dtEnd) {
                        $ymList[] = $dtTmp->format('Y-m');
                        $dtTmp->modify('+1 month');
                    }
                    $ymCondition = implode(',', array_map(function($v) { return "'$v'"; }, $ymList));
                    $blnStartName = $daftar_bulan[intval($dtStart->format('m'))];
                    $blnEndName = $daftar_bulan[intval($dtEnd->format('m'))];
                    if ($dtStart->format('Y') == $dtEnd->format('Y')) {
                        $periodeLabel = $blnStartName . ' - ' . $blnEndName . ' ' . $dtStart->format('Y');
                    } else {
                        $periodeLabel = $blnStartName . ' ' . $dtStart->format('Y') . ' - ' . $blnEndName . ' ' . $dtEnd->format('Y');
                    }
                    $current_date = $endMonth;
                } elseif (!empty($filterBulan) && str_contains($filterBulan, '_3')) {
                    $filterPeriode = '3';
                    $baseStart = str_replace('_3', '', $filterBulan);
                    $dtStart = new DateTime($baseStart . '-01');
                    $dtEnd = clone $dtStart;
                    $dtEnd->modify('+2 months');
                    $monthStart = $dtStart->format('Y-m-01');
                    $monthEnd = $dtEnd->format('Y-m-t');
                    
                    $ymList = [];
                    $dtTmp = clone $dtStart;
                    for ($mi = 0; $mi < 3; $mi++) {
                        $ymList[] = $dtTmp->format('Y-m');
                        $dtTmp->modify('+1 month');
                    }
                    $ymCondition = implode(',', array_map(function($v) { return "'$v'"; }, $ymList));
                    $periodeLabel = $daftar_bulan[intval($dtStart->format('m'))] . ' - ' . $daftar_bulan[intval($dtEnd->format('m'))] . ' ' . $dtEnd->format('Y');
                    $current_date = $dtEnd->format('Y-m');
                } elseif (!empty($filterBulan)) {
                    $filterPeriode = '1';
                    $current_date = $filterBulan;
                    $timestamp = strtotime($current_date . '-01');
                    $bulan = $daftar_bulan[(int)date('m', $timestamp)];
                    $tahun = date('Y', $timestamp);
                    $monthStart = date('Y-m-01', $timestamp);
                    $monthEnd = date('Y-m-t', $timestamp);
                    $ymList = [$current_date];
                    $ymCondition = "'$current_date'";
                    $periodeLabel = $bulan . ' ' . $tahun;
                } else {
                    $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
                    $filterPeriode = '1';
                    $timestamp = strtotime($current_date . '-01');
                    $bulan = $daftar_bulan[(int)date('m', $timestamp)];
                    $tahun = date('Y', $timestamp);
                    $monthStart = date('Y-m-01', $timestamp);
                    $monthEnd = date('Y-m-t', $timestamp);
                    $ymList = [$current_date];
                    $ymCondition = "'$current_date'";
                    $periodeLabel = $bulan . ' ' . $tahun;
                }
                ?>
                <a href="export-laporan-x.php?cariBulanTahun=<?= $current_date; ?>&bulan=<?= urlencode($filterBulan); ?>&ftek=<?= $filterTeknisiId; ?>" class="btn btn-success d-flex align-items-center">
                    <i class="material-icons text-sm me-1">description</i> Export Excel
                </a>
            </div>
        </div>

        <div class="card">
            <?php
            // Date logic handled above
            ?>
            <div class="card-header bg-white py-3 border-0">
                <h5 class="text-center mb-1 font-weight-bold">REKAPITULASI TARGET TERCAPAI TEKNISI</h5>
                <p class="text-center text-muted mb-0">Periode: <strong><?= $periodeLabel; ?></strong></p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama Teknisi</th>
                                <th class="text-center">Kegiatan</th>
                                <th class="text-center">Selesai</th>
                                <th class="text-center">Invoice</th>
                                <th class="text-end">Fee (30k)</th>
                                <th class="text-end">Target Tercapai</th>
                                <th class="text-end pe-3">Bonus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
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
                            ?>
                            <tr>
                                <td class="ps-3 font-weight-bold"><?= htmlspecialchars($tr['namaT']); ?></td>
                                <td class="text-center"><?= $tr['keg']; ?></td>
                                <td class="text-center"><?= $tr['sel']; ?></td>
                                <td class="text-center"><?= $tr['inv']; ?></td>
                                <td class="text-end">Rp <?= number_format($tr['fee'], 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?= number_format($tr['pend'], 0, ',', '.'); ?></td>
                                <td class="text-end pe-3">Rp <?= number_format($tr['bon'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row text-dark">
                                <td class="ps-3">TOTAL KESELURUHAN</td>
                                <td colspan="3"></td>
                                <td class="text-end">Rp <?= number_format($grand_total_fee, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.'); ?></td>
                                <td class="text-end pe-3">Rp <?= number_format($grand_total_bonus, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>