<?php
include "conn.php";
include "session.php";
$pageNow = "View Kegiatan";
include "get-user-data.php";

// Fungsi pembantu
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

// Validasi kode_transaksi
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

$temp_unique = [];

while ($row = $result_pelaksanaan->fetch_assoc()) {
    $nama_teknisi = $row['nama_teknisi'];
    // Ambil tanggal saja (Y-m-d) dari waktu_mulai
    $tanggal_kegiatan = date('Y-m-d', strtotime($row['waktu_mulai']));
    
    $unique_key = $nama_teknisi . '_' . $tanggal_kegiatan;

    if (!isset($temp_unique[$unique_key])) {
        $temp_unique[$unique_key] = $row;
    } else {
        $existing_row = $temp_unique[$unique_key];

        $new_has_end = (!empty($row['waktu_selesai']) && substr($row['waktu_selesai'], 0, 10) != '0000-00-00');
        $old_has_end = (!empty($existing_row['waktu_selesai']) && substr($existing_row['waktu_selesai'], 0, 10) != '0000-00-00');

        if ($new_has_end && !$old_has_end) {
            $temp_unique[$unique_key] = $row;
        } elseif ($new_has_end == $old_has_end) {
            if ($row['waktu_mulai'] > $existing_row['waktu_mulai']) {
                $temp_unique[$unique_key] = $row;
            }
        }
    }
}

// Setelah disaring, masukkan kembali ke array grouping agar format sesuai dengan tampilan HTML
foreach ($temp_unique as $item) {
    $pelaksanaan_grouped[$item['nama_teknisi']][] = $item;
}

$stmt_pelaksanaan->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .lunas-background { position: relative; z-index: 1; }
        .lunas-background::after { content: ''; position: absolute; top: 30%; left: 40%; width: 50%; height: 50%; background-image: url('assets/img/lunas.png'); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.1; z-index: -1; }
        .timeline { position: relative; padding-left: 20px; border-left: 2px solid #e9ecef; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-icon { position: absolute; left: -31px; top: 2px; width: 20px; height: 20px; border-radius: 50%; background-color: #fff; border: 2px solid; text-align: center; }
        .timeline-icon-success { border-color: #2dce89; color: #2dce89; }
        .timeline-icon-info { border-color: #11cdef; color: #11cdef; }
        .timeline-icon-warning { border-color: #fb6340; color: #fb6340; }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <?php if ($kegiatan_data) : ?>
                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card mb-4">
                            <div class="card-header pb-0"><h6><i class="material-icons text-sm me-1">info</i>Detail Kegiatan</h6></div>
                            <div class="card-body pt-0">
                                <ul class="list-group list-group-flush text-sm">
                                    <li class="list-group-item px-0"><strong>Kode:</strong> <?= htmlspecialchars($kegiatan_data['kode']); ?></li>
                                    <li class="list-group-item px-0"><strong>Jenis:</strong> <?= htmlspecialchars(ucwords($kegiatan_data['kegiatan'])); ?></li>
                                    <li class="list-group-item px-0"><strong>Customer:</strong> <?= htmlspecialchars($kegiatan_data['nama_customer']); ?></li>
                                    <li class="list-group-item px-0"><strong>Kontak:</strong> <?php $nomorHandphone = $kegiatan_data['cust_nomor']; if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1); ?><a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone; ?>" target="_blank"><?= htmlspecialchars($kegiatan_data['cust_nomor']); ?></a></li>
                                    <li class="list-group-item px-0"><strong>Alamat:</strong> <?= htmlspecialchars($kegiatan_data['alamat']); ?></li>
                                    <li class="list-group-item px-0"><strong>Keterangan:</strong><p class="mb-0 text-wrap"><?= !empty($kegiatan_data['keterangan']) ? htmlspecialchars($kegiatan_data['keterangan']) : '-'; ?></p></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header pb-0"><h6><i class="material-icons text-sm me-1">add_shopping_cart</i>Perlu Ditagih by Teknisi</h6></div>
                            <div class="card-body pt-2">
                                <?php if (!empty($tagihan_items)) : ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($tagihan_items as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <?= htmlspecialchars($item['name']); ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($item['qty']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-center text-muted small">Tidak ada item tambahan yang perlu ditagih.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card <?= (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') ? 'lunas-background' : ''; ?>">
                             <div class="card-header pb-0" style="background:none !important;"><h6><i class="material-icons text-sm me-1">receipt_long</i>Informasi Keuangan</h6></div>
                            <div class="card-body pt-0">
                                <?php if ($invoice_data) : ?>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item px-0" style="background:none !important;"><strong>No. Invoice:</strong> <?= htmlspecialchars($invoice_data['no_invoice']); ?></li>
                                        <li class="list-group-item px-0" style="background:none !important;"><strong>Nominal:</strong> <span class="text-success font-weight-bold">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></span></li>
                                        <li class="list-group-item px-0" style="background:none !important;"><strong>Status Bayar:</strong>
                                            <?php if (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') : ?>
                                                <span class="badge bg-success" style="font-size: 10px !important">Lunas pada <?= date("d M Y", strtotime($kegiatan_data['lunas'])); ?></span>
                                            <?php else : ?>
                                                <span class="badge bg-danger" style="font-size: 10px !important">Belum Lunas</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                <?php else : ?>
                                    <p class="text-center text-danger">Invoice belum dibuat.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header pb-0"><h6><i class="fa-solid fa-person-digging me-2"></i>Riwayat Pelaksanaan Teknisi</h6></div>
                            <div class="card-body">
                                <?php if (!empty($pelaksanaan_grouped)) : ?>
                                    <ul class="nav nav-tabs" id="teknisiTab" role="tablist">
                                        <?php $tab_index = 0; foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks) : $tab_index++; ?>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link <?= ($tab_index == 1) ? 'active' : '' ?>" id="tab-<?= $tab_index ?>" data-bs-toggle="tab" data-bs-target="#panel-<?= $tab_index ?>" type="button" role="tab"><?= htmlspecialchars(shortenTechnicianName($nama_teknisi)); ?></button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="tab-content bg-white border border-top-0 rounded-bottom p-3" id="teknisiTabContent">
                                        <?php $tab_index = 0; foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks) : $tab_index++; ?>
                                            <div class="tab-pane fade <?= ($tab_index == 1) ? 'show active' : '' ?>" id="panel-<?= $tab_index ?>" role="tabpanel">
                                                <div class="timeline">
                                                    <?php foreach ($tasks as $task) :
                                                        $status_class = 'info';
                                                        if ($task['status'] == 'selesai') $status_class = 'success';
                                                        if ($task['status'] == 'Lanjut Nanti') $status_class = 'warning';
                                                    ?>
                                                        <div class="timeline-item">
                                                            <div class="timeline-icon d-flex align-items-center justify-content-center timeline-icon-<?= $status_class ?>"><i class="material-icons text-xs">check</i></div>
                                                            <h6 class="text-dark text-sm font-weight-bold mb-1 text-capitalize"><?= htmlspecialchars($task['status']); ?> <span class="font-weight-normal text-muted">- <?= date("d M Y", strtotime($task['waktu_mulai'])); ?></span></h6>
                                                            <div class="card card-body shadow-none border p-3">
                                                                <div class="d-flex justify-content-start text-sm gap-4 mb-2">
                                                                    <div>
                                                                        <strong>Mulai:</strong>
                                                                        <?= (!empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00') ? date("H:i", strtotime($task['waktu_mulai'])) : 'Belum Absen' ?>
                                                                        <?php if(!empty($task['latitude']) && !empty($task['longitude'])) echo "<a href='http://maps.google.com/maps?q={$task['latitude']},{$task['longitude']}' target='_blank' class='text-info ms-1'>[Lokasi]</a>"; ?>
                                                                    </div>
                                                                    <div>
                                                                        <strong>Selesai:</strong>
                                                                        <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? date("H:i", strtotime($task['waktu_selesai'])) : 'Belum Absen' ?>
                                                                        <?php if(!empty($task['latitude_s']) && !empty($task['longitude_s'])) echo "<a href='http://maps.google.com/maps?q={$task['latitude_s']},{$task['longitude_s']}' target='_blank' class='text-info ms-1'>[Lokasi]</a>"; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-between text-wrap">
                                                                    <div>
                                                                        <p class="text-sm mb-0 text-wrap"><strong>Permasalahan :</strong> <?= !empty($task['permasalahan']) ? htmlspecialchars($task['permasalahan']) : '-'; ?></p>
                                                                        <p class="text-sm mb-0 text-wrap"><strong>Solusi :</strong> <?= !empty($task['solusi']) ? htmlspecialchars($task['solusi']) : '-'; ?></p>
                                                                        <p class="text-sm mb-0 text-wrap"><strong>Keterangan :</strong> <?= !empty($task['keterangan']) ? htmlspecialchars($task['keterangan']) : '-'; ?></p>
                                                                    </div>
                                                                    <div>
                                                                        <?php foreach (['image_1', 'image_2', 'image_3'] as $img) : ?>
                                                                            <?php if (!empty($task[$img])) : ?>
                                                                                <a href="https://api-teknisi.id-giti.com/storage/image/<?= $task[$img] ?>" target="_blank" class="me-1">
                                                                                    <img src="https://api-teknisi.id-giti.com/storage/image/<?= $task[$img] ?>" alt="bukti foto" style="width: 50px; height: 50px; object-fit: cover; border-radius: .3rem;">
                                                                                </a>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="text-center">Tidak ditemukan riwayat pelaksanaan dari teknisi.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="alert alert-danger text-white text-center">Data kegiatan dengan kode '<?= htmlspecialchars($kode_transaksi); ?>' tidak ditemukan.</div>
            <?php endif; ?>
        </div>
        <?php include "footer.php"; ?>
    </main>
    <?php include "js-include.php"; ?>
</body>
</html>