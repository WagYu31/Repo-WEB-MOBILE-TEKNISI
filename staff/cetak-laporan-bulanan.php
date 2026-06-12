<?php
include "conn.php";
include "session.php";

if (!isset($_GET['bulan']) || !isset($_GET['tahun']) || !is_numeric($_GET['bulan']) || !is_numeric($_GET['tahun'])) {
    die("Error: Bulan dan Tahun tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$daftar_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Mengambil nama bulan berdasarkan variabel $bulan (angka 1-12)
$nama_bulan = $daftar_bulan[(int)$bulan];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kegiatan Bulanan - <?= $nama_bulan . ' ' . $tahun ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .table { font-size: 9pt; }
            .table th, .table td { padding: 0.2rem; }
        }
        body { background-color: #f8f9fa; }
        .container { max-width: 1400px; background-color: #fff; padding: 2rem; margin-top: 2rem; border-radius: .5rem; }
        .table th, .table td { vertical-align: middle; }
        .lunas-bg {
          position: relative;
          z-index: 1;
        }
        .lunas-bg::after {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-image: url('assets/img/lunas.png');
          background-size: 50%;
          background-position: center;
          background-repeat: no-repeat;
          opacity: 0.2;
          z-index: -1;
        }
        .technician-daily-item { border-bottom: 1px dashed #e0e0e0; }
        .technician-daily-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="h3">Laporan Kegiatan Lengkap</h1>
            <p class="lead">Periode: <strong><?= $nama_bulan . ' ' . $tahun ?></strong></p>
        </div>
        
        <div class="d-flex justify-content-start mb-4 no-print">
            <button onclick="window.print()" class="btn btn-sm btn-primary me-2 d-flex justify-content-center align-items-center">
                <i class="material-icons text-sm me-1">print</i> Cetak Laporan
            </button>
            <a href="export-laporan-excel.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-sm btn-success d-flex align-items-center justify-content-center">
                <i class="material-icons text-sm me-1">description</i> Export ke Excel
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 35%;">Customer & Request</th>
                        <th style="width: 30%;">Invoice</th>
                        <th style="width: 35%;">Teknisi, Pendapatan & Pelaksanaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.lunas, k.paid, c.nama AS nama_cust
                                FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
                                WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL
                                GROUP BY k.kode ORDER BY k.created_at ASC";

                    $stmt_main = $conn->prepare($sql_main);
                    $stmt_main->bind_param("ii", $bulan, $tahun);
                    $stmt_main->execute();
                    $result_main = $stmt_main->get_result();

                    if ($result_main->num_rows > 0) {
                        while ($row_main = $result_main->fetch_assoc()) {
                            $kodeTransaksi = $row_main['kode_transaksi'];
                            $is_manual_fee = is_numeric($row_main['paid']);
                            $lunas_class = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'lunas-bg' : '';
                    ?>
                            <tr>
                                <td>
                                    <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                    <p class="text-muted small mb-1">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'N/A'; ?>"</p>
                                    <p class="text-muted small mb-0"><strong>Kode:</strong> <?= $kodeTransaksi; ?></p>
                                    <p class="text-muted small mb-0"><strong>Request:</strong> <?= date("d M Y", strtotime($row_main['created_at'])); ?></p>
                                </td>
                                
                                <td class="<?= $lunas_class ?>">
                                    <?php
                                    $sql_invoice = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
                                    $stmt_invoice = $conn->prepare($sql_invoice);
                                    $stmt_invoice->bind_param("s", $kodeTransaksi);
                                    $stmt_invoice->execute();
                                    $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
                                    $stmt_invoice->close();
                                    ?>
                                    <div class="p-2 invoice-summary">
                                        <?php if ($invoice_data) : ?>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <small class="d-block text-uppercase fw-bold">No. Invoice</small>
                                                    <p class="mb-0 fw-bolder"><?= htmlspecialchars($invoice_data['no_invoice']); ?></p>
                                                </div>
                                                <div class="text-end">
                                                    <small class="d-block text-uppercase fw-bold">Nominal</small>
                                                    <p class="mb-0 fw-bolder text-success">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                            <hr class="my-1">
                                            <p class="small mb-0"><strong>Status Bayar:</strong> <span class="<?= $lunas_class ? 'text-primary' : 'text-danger'; ?>"><?= $lunas_class ? 'Lunas '.date("d M Y", strtotime($row_main['lunas'])) : 'Belum Lunas'; ?></span></p>
                                        <?php elseif ($is_manual_fee) : ?>
                                            <div class="text-center">
                                                <p class="small fw-bold mb-0">Tidak ada Invoice</p>
                                                <p class="mb-0 fw-bolder text-success">Rp 30.000</p>
                                            </div>
                                        <?php else : ?>
                                            <p class="small text-center text-danger fw-bold mb-0">Invoice Belum Dibuat</p>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                <?php
                                    $sql_count_active = "SELECT COUNT(DISTINCT teknisi_id) as total_aktif 
                                                        FROM pelaksanaan_kegiatan 
                                                        WHERE kode = ? AND waktu_mulai IS NOT NULL";
                                    $stmt_count = $conn->prepare($sql_count_active);
                                    $stmt_count->bind_param("s", $kodeTransaksi);
                                    $stmt_count->execute();
                                    $res_count = $stmt_count->get_result()->fetch_assoc();
                                    $jumlah_teknisi_aktif = $res_count['total_aktif'] ?? 0;
                                    $stmt_count->close();

                                    $sql_teknisi_list = "SELECT 
                                                            t.id, t.nama AS nama_teknisi,
                                                            (SELECT SUM(pendapatan) FROM pendapatan_kegiatan WHERE kode = ? AND teknisi_id = t.id) as total_pendapatan
                                                        FROM team_kegiatan tk
                                                        JOIN teknisi t ON tk.teknisi_id = t.id
                                                        JOIN kegiatan k ON tk.kegiatan_id = k.id
                                                        WHERE k.kode = ?
                                                        GROUP BY t.id";
                                    $stmt_teknisi_list = $conn->prepare($sql_teknisi_list);
                                    $stmt_teknisi_list->bind_param("ss", $kodeTransaksi, $kodeTransaksi);
                                    $stmt_teknisi_list->execute();
                                    $result_teknisi_list = $stmt_teknisi_list->get_result();

                                    while($row_teknisi = $result_teknisi_list->fetch_assoc()) {
                                        $teknisi_id = $row_teknisi['id'];
                                        $pendapatan_db = $row_teknisi['total_pendapatan'] ?? 0;
                                        
                                        $sql_absensi = "SELECT DATE(waktu_mulai) as tanggal_kerja, MIN(waktu_mulai) as jam_masuk, MAX(waktu_selesai) as jam_pulang
                                                        FROM pelaksanaan_kegiatan
                                                        WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL
                                                        GROUP BY tanggal_kerja ORDER BY tanggal_kerja ASC";
                                        $stmt_absensi = $conn->prepare($sql_absensi);
                                        $stmt_absensi->bind_param("si", $kodeTransaksi, $teknisi_id);
                                        $stmt_absensi->execute();
                                        $result_absensi = $stmt_absensi->get_result();
                                        $punya_absensi = ($result_absensi->num_rows > 0);

                                        $pendapatan_tampil = $pendapatan_db;
                                        if ($pendapatan_db == 0 && $is_manual_fee) {
                                            if ($punya_absensi && $jumlah_teknisi_aktif > 0) {
                                                $pendapatan_tampil = 30000 / $jumlah_teknisi_aktif;
                                            } else {
                                                $pendapatan_tampil = 0;
                                            }
                                        }
                                ?>
                                    <div class="mb-2">
                                        <p class="small mb-1 fw-bold"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?> : <span class="text-success">Rp <?= number_format($pendapatan_tampil, 0, ',', '.'); ?></span></p>
                                <?php
                                    if ($punya_absensi) {
                                        while($row_absensi = $result_absensi->fetch_assoc()) {
                                ?>
                                        <div class="d-flex justify-content-between ps-3 py-1 technician-daily-item">
                                            <p class="small text-muted mb-0">
                                                Mulai: <?= !empty($row_absensi['jam_masuk']) ? date("d/m H:i", strtotime($row_absensi['jam_masuk'])) : '-'; ?>
                                            </p>
                                            <p class="small text-muted mb-0">
                                                Selesai: <?= !empty($row_absensi['jam_pulang']) ? date("d/m H:i", strtotime($row_absensi['jam_pulang'])) : '-'; ?>
                                            </p>
                                        </div>
                                <?php
                                        }
                                    } else {
                                        echo "<p class='small text-danger ps-3 mb-0'>Tidak ada data pelaksanaan.</p>";
                                    }
                                    $stmt_absensi->close();
                                    echo "</div>"; 
                                }
                                $stmt_teknisi_list->close();
                                ?>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='3' class='text-center py-5'>Tidak ada data kegiatan ditemukan untuk periode ini.</td></tr>";
                    }
                    $stmt_main->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>