<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Pendapatan";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Rekap Pendapatan Teknisi</title>
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
                $current_date = $_GET['cariBulanTahun'] ?? date("Y-m");
                ?>
                <a href="export-laporan-x.php?cariBulanTahun=<?= $current_date; ?>" class="btn btn-success d-flex align-items-center">
                    <i class="material-icons text-sm me-1">description</i> Export Excel
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="text-center mb-1 font-weight-bold">REKAPITULASI PENDAPATAN TEKNISI</h5>
                <p class="text-center text-muted mb-0">Periode: <strong><?= date('F Y', strtotime($current_date)); ?></strong></p>
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
                                <th class="text-end">Pendapatan</th>
                                <th class="text-end pe-3">Bonus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $g_fee = $g_inc = $g_bns = 0;
                            $bulan_filter = date('m', strtotime($current_date));
                            $tahun_filter = date('Y', strtotime($current_date));
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

                                // === Invoice + Pendapatan (SAME as dashboard: nominal_invoice / tek_count) ===
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
                            ?>
                            <tr>
                                <td class="ps-3 font-weight-bold"><?= $namaT; ?></td>
                                <td class="text-center"><?= $total_k; ?></td>
                                <td class="text-center"><?= $total_s; ?></td>
                                <td class="text-center"><?= $total_i; ?></td>
                                <td class="text-end">Rp <?= number_format($fee_val, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?= number_format($inc_val, 0, ',', '.'); ?></td>
                                <td class="text-end pe-3">Rp <?= number_format($bns_val, 0, ',', '.'); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row text-dark">
                                <td class="ps-3">TOTAL KESELURUHAN</td>
                                <td colspan="3"></td>
                                <td class="text-end">Rp <?= number_format($g_fee, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.'); ?></td>
                                <td class="text-end pe-3">Rp <?= number_format($g_bns, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>