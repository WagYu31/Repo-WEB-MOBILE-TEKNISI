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
                            ?>
                            <tr>
                                <td class="ps-3 font-weight-bold"><?= $row['nama']; ?></td>
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
                                <td class="text-end">Rp <?= number_format($g_inc, 0, ',', '.'); ?></td>
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