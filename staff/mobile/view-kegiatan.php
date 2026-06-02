<?php
include "../conn.php";
include "../session.php";
$pageNow = "View Kegiatan";
include "../get-user-data.php";

// Fungsi pembantu (TIDAK ADA PERUBAHAN)
function shortenTechnicianName($fullName) {
    if (empty($fullName)) return '-';
    $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
    $words = explode(" ", $fullName);
    if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
    $shortenedName = implode(" ", $words);
    if (strlen($shortenedName) > 15 && count($words) > 2) {
        $lastWordIndex = count($words) - 1;
        if (isset($words[$lastWordIndex][0])) $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
        $shortenedName = implode(" ", $words);
    }
    return $shortenedName;
}

// Validasi dan semua Kueri Data (TIDAK ADA PERUBAHAN)
if (!isset($_GET["kode_transaksi"]) || empty($_GET["kode_transaksi"])) {
    die("Error: Kode transaksi tidak valid.");
}
$kode_transaksi = $_GET["kode_transaksi"];

// 1. Query data utama kegiatan & customer
$sql_main = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat
             FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
             WHERE k.kode = ? AND k.deleted_at IS NULL LIMIT 1";
$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("s", $kode_transaksi);
$stmt_main->execute();
$kegiatan_data = $stmt_main->get_result()->fetch_assoc();
$stmt_main->close();

// 2. Query data invoice
$sql_invoice = "SELECT no_invoice, nominal_invoice, tanggal FROM pendapatan_kegiatan WHERE kode = ? AND deleted_at IS NULL LIMIT 1";
$stmt_invoice = $conn->prepare($sql_invoice);
$stmt_invoice->bind_param("s", $kode_transaksi);
$stmt_invoice->execute();
$invoice_data = $stmt_invoice->get_result()->fetch_assoc();
$stmt_invoice->close();

// 3. Query data item tagihan tambahan dari tabel 'invoice'
$sql_tagihan = "SELECT name, qty FROM invoice WHERE kegiatan_kode = ?";
$stmt_tagihan = $conn->prepare($sql_tagihan);
$stmt_tagihan->bind_param("s", $kode_transaksi);
$stmt_tagihan->execute();
$result_tagihan = $stmt_tagihan->get_result();
$tagihan_items = [];
while ($row = $result_tagihan->fetch_assoc()) {
    $tagihan_items[] = $row;
}
$stmt_tagihan->close();

// 4. Query semua data pelaksanaan, dikelompokkan dalam PHP
$pelaksanaan_grouped = [];
$sql_pelaksanaan = "SELECT p.*, t.nama AS nama_teknisi
                    FROM pelaksanaan_kegiatan p JOIN teknisi t ON p.teknisi_id = t.id
                    WHERE p.kode = ? AND p.deleted_at IS NULL
                    ORDER BY t.nama ASC, p.waktu_mulai ASC";
$stmt_pelaksanaan = $conn->prepare($sql_pelaksanaan);
$stmt_pelaksanaan->bind_param("s", $kode_transaksi);
$stmt_pelaksanaan->execute();
$result_pelaksanaan = $stmt_pelaksanaan->get_result();
while ($row = $result_pelaksanaan->fetch_assoc()) {
    $pelaksanaan_grouped[$row['nama_teknisi']][] = $row;
}
$stmt_pelaksanaan->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Kegiatan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header-icon {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 600;
            color: #344767;
        }
        .card-header-icon i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
            color: #0d6efd;
        }
        .lunas-background { position: relative; z-index: 1; }
        .lunas-background::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background-image: url('assets/img/lunas.png'); /* Pastikan path ini benar */
            background-size: 50%;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.08;
            z-index: -1;
        }
        .timeline { position: relative; padding-left: 25px; border-left: 3px solid #e9ecef; }
        .timeline-item { position: relative; margin-bottom: 1.5rem; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-icon {
            position: absolute;
            left: -37.5px; /* Disesuaikan agar pas dengan garis */
            top: 0px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background-color: #fff;
            border: 3px solid;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .timeline-icon-success { border-color: #2dce89; color: #2dce89; }
        .timeline-icon-info    { border-color: #11cdef; color: #11cdef; }
        .timeline-icon-warning { border-color: #fb6340; color: #fb6340; }
        .list-group-item { padding: 0.75rem 0; } /* Spasi lebih baik untuk mobile */
    </style>
</head>
<body class="bg-light">
    <?php include "bottom-navbar.php"; ?>
    <main class="main-content mb-5">
        <div class="container-fluid py-3">
            <?php if ($kegiatan_data) : ?>
                
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <h5 class="mb-1 text-primary"><?= htmlspecialchars($kegiatan_data['nama_customer']); ?></h5>
                        <p class="text-muted mb-1"><?= htmlspecialchars(ucwords($kegiatan_data['kegiatan'])); ?></p>
                        <span class="badge bg-secondary fw-normal"><?= htmlspecialchars($kegiatan_data['kode']); ?></span>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-white card-header-icon">
                        <i class="fa-solid fa-timeline"></i>Riwayat Pelaksanaan
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pelaksanaan_grouped)) : ?>
                            <?php foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks) : ?>
                                <div class="mb-4">
                                    <h6 class="text-info border-bottom pb-2 mb-3"><i class="fa-solid fa-user-gear me-2"></i><?= htmlspecialchars(shortenTechnicianName($nama_teknisi)); ?></h6>
                                    <div class="timeline">
                                        <?php foreach ($tasks as $task) :
                                            $status_class = 'info';
                                            if ($task['status'] == 'selesai') $status_class = 'success';
                                            if ($task['status'] == 'Lanjut Nanti') $status_class = 'warning';
                                        ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon timeline-icon-<?= $status_class ?>"><i class="fa-solid fa-check fa-xs"></i></div>
                                            <h6 class="text-dark text-sm fw-bold mb-1 text-capitalize"><?= htmlspecialchars($task['status']); ?> <span class="fw-normal text-muted">- <?= date("d M Y", strtotime($task['waktu_mulai'])); ?></span></h6>
                                            
                                            <div class="text-sm text-muted mb-2">
                                                <i class="fa-regular fa-clock"></i> <?= date("H:i", strtotime($task['waktu_mulai'])); ?> s/d <?= date("H:i", strtotime($task['waktu_selesai'])); ?>
                                            </div>

                                            <p class="text-sm mb-1"><strong>Masalah:</strong> <?= !empty($task['permasalahan']) ? htmlspecialchars($task['permasalahan']) : '-'; ?></p>
                                            <p class="text-sm mb-1"><strong>Solusi:</strong> <?= !empty($task['solusi']) ? htmlspecialchars($task['solusi']) : '-'; ?></p>
                                            
                                            <?php if (!empty($task['image_1']) || !empty($task['image_2']) || !empty($task['image_3'])) : ?>
                                                <div class="mt-2">
                                                    <?php foreach (['image_1', 'image_2', 'image_3'] as $img) : if (!empty($task[$img])) : ?>
                                                        <a href="https://grav-tech.com/jadwal-3/api/storage/app/image/<?= $task[$img] ?>" target="_blank" class="me-1">
                                                            <img src="https://grav-tech.com/jadwal-3/api/storage/app/image/<?= $task[$img] ?>" alt="bukti foto" style="width: 60px; height: 60px; object-fit: cover; border-radius: .3rem;">
                                                        </a>
                                                    <?php endif; endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="text-center text-muted">Belum ada riwayat pelaksanaan dari teknisi.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-3">
                     <div class="card-header bg-white card-header-icon">
                        <i class="fa-solid fa-circle-info"></i>Detail Tambahan
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-group list-group-flush">
                           <li class="list-group-item"><strong>Kontak:</strong> <?php $nomorHandphone = $kegiatan_data['cust_nomor']; if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1); ?><a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone; ?>" target="_blank"> <?= htmlspecialchars($kegiatan_data['cust_nomor']); ?> <i class="fa-brands fa-whatsapp text-success"></i></a></li>
                           <li class="list-group-item"><strong>Alamat:</strong> <?= htmlspecialchars($kegiatan_data['alamat']); ?></li>
                           <li class="list-group-item"><strong>Keterangan:</strong><p class="mb-0 text-wrap"><?= !empty($kegiatan_data['keterangan']) ? htmlspecialchars($kegiatan_data['keterangan']) : '-'; ?></p></li>
                        </ul>
                         <?php if (!empty($tagihan_items)) : ?>
                            <hr>
                            <h6 class="mb-2">Item Perlu Ditagih:</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach($tagihan_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($item['name']); ?>
                                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($item['qty']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4 <?= (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') ? 'lunas-background' : ''; ?>">
                    <div class="card-header bg-white card-header-icon">
                        <i class="fa-solid fa-file-invoice-dollar"></i>Informasi Keuangan
                    </div>
                    <div class="card-body pt-2">
                        <?php if ($invoice_data) : ?>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between"><span>No. Invoice:</span> <strong><?= htmlspecialchars($invoice_data['no_invoice']); ?></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Nominal:</span> <strong class="text-success fs-5">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></strong></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Status:</span>
                                    <?php if (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') : ?>
                                        <span class="badge bg-success">Lunas pada <?= date("d M Y", strtotime($kegiatan_data['lunas'])); ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-danger">Belum Lunas</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        <?php else : ?>
                            <p class="text-center text-danger mb-0">Invoice belum dibuat.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else : ?>
                <div class="alert alert-danger text-center">Data kegiatan dengan kode '<?= htmlspecialchars($kode_transaksi); ?>' tidak ditemukan.</div>
            <?php endif; ?>
        </div> </main>
    </body>
</html>