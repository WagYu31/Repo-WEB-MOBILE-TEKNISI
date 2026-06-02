<?php
include "conn.php";
include "session.php";

if (!isset($_GET["kode"]) || empty($_GET["kode"])) {
    die("Error: Kode kegiatan tidak disediakan.");
}
$kode_kegiatan = $_GET["kode"];

$kegiatan_data = null;
$invoice_data = null;
$pelaksanaan_grouped = [];

$sql_main = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat
             FROM kegiatan k 
             LEFT JOIN customer c ON k.customer_id = c.id
             WHERE k.kode = ? AND k.deleted_at IS NULL 
             ORDER BY k.id DESC LIMIT 1";
$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("s", $kode_kegiatan);
$stmt_main->execute();
$kegiatan_data = $stmt_main->get_result()->fetch_assoc();
$stmt_main->close();

if ($kegiatan_data) {
    $sql_invoice = "SELECT no_invoice, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? AND deleted_at IS NULL LIMIT 1";
    $stmt_invoice = $conn->prepare($sql_invoice);
    $stmt_invoice->bind_param("s", $kode_kegiatan);
    $stmt_invoice->execute();
    $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
    $stmt_invoice->close();

    $sql_pelaksanaan = "SELECT p.*, t.nama AS nama_teknisi
                        FROM pelaksanaan_kegiatan p 
                        JOIN teknisi t ON p.teknisi_id = t.id
                        WHERE p.kode = ? AND p.deleted_at IS NULL
                        ORDER BY t.nama ASC, p.waktu_mulai ASC";
    $stmt_pelaksanaan = $conn->prepare($sql_pelaksanaan);
    $stmt_pelaksanaan->bind_param("s", $kode_kegiatan);
    $stmt_pelaksanaan->execute();
    $result_pelaksanaan = $stmt_pelaksanaan->get_result();
    while ($row = $result_pelaksanaan->fetch_assoc()) {
        $pelaksanaan_grouped[$row['nama_teknisi']][] = $row;
    }
    $stmt_pelaksanaan->close();
}

$jumlah_teknisi = 0;
if (!empty($pelaksanaan_grouped)) {
    foreach ($pelaksanaan_grouped as $tasks) {
        $has_complete_task = false;
        foreach ($tasks as $task) {
            if (!empty($task['waktu_mulai']) && !empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') {
                $has_complete_task = true;
                break;
            }
        }
        if ($has_complete_task) {
            $jumlah_teknisi++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Kegiatan - <?= htmlspecialchars($kode_kegiatan) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .timeline .timeline-item { position: relative; padding-left: 20px; border-left: 2px solid #e9ecef; }
        .timeline .timeline-item:last-child { border-left-color: transparent; }
        .timeline .timeline-item::before { content: ''; position: absolute; left: -6px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background-color: #adb5bd; }
    </style>
</head>
<body>
    <div class="container my-4">
        <?php if ($kegiatan_data): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 text-primary text-capitalize"><?= htmlspecialchars($kegiatan_data['kegiatan']) ?></h5>
                        <small class="text-muted">Kode: <?= htmlspecialchars($kegiatan_data['kode']) ?></small>
                    </div>
                    <span class="badge text-bg-light"><?= date("d M Y", strtotime($kegiatan_data['jadwal'])) ?></span>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Customer</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($kegiatan_data['nama_customer']) ?></dd>
                        <dt class="col-sm-3">Kontak</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($kegiatan_data['cust_nomor']) ?></dd>
                        <dt class="col-sm-3">Alamat</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($kegiatan_data['alamat']) ?></dd>
                        <dt class="col-sm-3">Keterangan</dt>
                        <dd class="col-sm-9"><?= !empty($kegiatan_data['keterangan']) ? nl2br(htmlspecialchars($kegiatan_data['keterangan'])) : '-' ?></p></dd>
                        <dt class="col-sm-3 border-top pt-3">Teknisi</dt>
                        <dd class="col-sm-9 border-top pt-3"><?= $jumlah_teknisi ?> Orang</dd>
                    </dl>
                </div>
                <?php if ($invoice_data): ?>
                <div class="card-footer bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-4"><strong>No. Invoice:</strong> <?= htmlspecialchars($invoice_data['no_invoice']) ?></div>
                        <div class="col-md-4"><strong>Nominal:</strong> <span class="fw-bold text-success">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.') ?></span></div>
                        <div class="col-md-4"><strong>Status:</strong> 
                            <?php if(!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00'): ?>
                                <span class="badge text-bg-success">LUNAS</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">BELUM LUNAS</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa-solid fa-person-digging me-2"></i>Laporan Pelaksanaan Teknisi</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pelaksanaan_grouped)): ?>
                        <?php foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks): ?>
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3"><?= htmlspecialchars($nama_teknisi) ?></h6>
                                <div class="timeline">
                                    <?php foreach ($tasks as $task): ?>
                                        <div class="timeline-item pb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge text-bg-secondary text-capitalize fw-normal"><?= htmlspecialchars($task['status']) ?></span>
                                                <small class="text-muted"><?= date("d M Y", strtotime($task['waktu_mulai'])) ?></small>
                                            </div>
                                            <p class="mb-1 small text-muted">
                                                <i class="fa-regular fa-clock"></i> <?= date("H:i", strtotime($task['waktu_mulai'])) ?> - <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? date("H:i", strtotime($task['waktu_selesai'])) : '<span class="text-danger fw-bold">Belum Selesai</span>' ?>
                                            </p>
                                            <p class="mb-1"><strong>Masalah:</strong> <?= htmlspecialchars($task['permasalahan'] ?: '-') ?></p>
                                            <p><strong>Solusi:</strong> <?= htmlspecialchars($task['solusi'] ?: '-') ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">Belum ada data pelaksanaan dari teknisi.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">
                <h4>Data Tidak Ditemukan</h4>
                <p>Kegiatan dengan kode <strong><?= htmlspecialchars($kode_kegiatan) ?></strong> tidak dapat ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>