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
        /* ── Hero Header ── */
        .view-hero {
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            border-radius: 16px; padding: 24px 28px; margin-bottom: 24px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
            box-shadow: 0 4px 24px rgba(30,41,59,0.18);
        }
        .hero-badge {
            padding: 5px 14px; border-radius: 8px; font-size: 11px;
            font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
        }
        .hero-badge-service { background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
        .hero-badge-survey { background: rgba(251,191,36,0.2); color: #fcd34d; border: 1px solid rgba(251,191,36,0.3); }
        .hero-badge-pasang { background: rgba(34,197,94,0.2); color: #86efac; border: 1px solid rgba(34,197,94,0.3); }
        .hero-badge-default { background: rgba(148,163,184,0.2); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.3); }
        .hero-kode { color: #94a3b8; font-size: 12px; font-weight: 500; }
        .hero-customer { color: #fff; font-size: 20px; font-weight: 700; margin: 0; }
        .hero-meta { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 4px; }
        .hero-meta span { color: #94a3b8; font-size: 12px; display: flex; align-items: center; gap: 4px; }
        .hero-meta a { color: #60a5fa; text-decoration: none; font-size: 12px; display: flex; align-items: center; gap: 4px; }
        .hero-meta a:hover { color: #93c5fd; }

        /* ── Modern Cards ── */
        .vk-card {
            background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s; margin-bottom: 20px;
        }
        .vk-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .vk-card-header {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
        }
        .vk-card-header .icon-circle {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .vk-card-header h6 { margin: 0; font-size: 14px; font-weight: 700; color: #1e293b; }
        .vk-card-body { padding: 16px 20px; }

        /* ── Detail Items ── */
        .detail-row {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid #f8fafc;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em;
            min-width: 80px; flex-shrink: 0; padding-top: 2px;
        }
        .detail-value { font-size: 13px; color: #334155; font-weight: 500; word-break: break-word; }
        .detail-value a { color: #3b82f6; text-decoration: none; }
        .detail-value a:hover { text-decoration: underline; }

        /* ── Tagihan Items ── */
        .tagihan-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 14px; background: #f8fafc; border-radius: 8px;
            margin-bottom: 6px; font-size: 13px; color: #334155;
        }
        .tagihan-qty {
            background: #e0e7ff; color: #4338ca; font-size: 11px;
            font-weight: 700; padding: 3px 10px; border-radius: 20px;
        }

        /* ── Invoice Card ── */
        .invoice-status {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700;
        }
        .invoice-lunas { background: #dcfce7; color: #166534; }
        .invoice-belum { background: #fef2f2; color: #991b1b; }

        /* ── Timeline Modern ── */
        .tl-container { padding: 0; }
        .tl-teknisi-tabs {
            display: flex; gap: 6px; padding: 6px 0 12px 12px; overflow-x: auto;
            border-bottom: 1px solid #e2e8f0; margin-bottom: 14px;
        }
        .tl-tab {
            padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
            border: 1px solid #e2e8f0; background: #fff; color: #64748b;
            cursor: pointer; transition: all 0.15s; white-space: nowrap;
        }
        .tl-tab:hover { border-color: #3b82f6; color: #3b82f6; }
        .tl-tab.active { background: #1e293b; color: #fff; border-color: #1e293b; }

        .tl-entry {
            margin-bottom: 14px; border-radius: 10px;
            border: 1px solid #e2e8f0; overflow: hidden;
            border-left: 3px solid #94a3b8;
        }
        .tl-entry:last-child { margin-bottom: 0; }
        .tl-entry-selesai { border-left-color: #22c55e; }
        .tl-entry-berjalan { border-left-color: #3b82f6; }
        .tl-entry-menunggu { border-left-color: #f59e0b; }
        .tl-entry-lanjut { border-left-color: #ef4444; }
        .tl-entry-lanjutan { border-left-color: #8b5cf6; }

        .tl-header {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px; background: #f8fafc;
            border-bottom: 1px solid #f1f5f9; flex-wrap: wrap;
        }
        .tl-status-badge {
            font-size: 10px; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; text-transform: capitalize;
            display: inline-flex; align-items: center;
        }
        .tl-badge-selesai { background: #dcfce7; color: #166534; }
        .tl-badge-berjalan { background: #dbeafe; color: #1e40af; }
        .tl-badge-menunggu { background: #fef3c7; color: #92400e; }
        .tl-badge-lanjut { background: #fef2f2; color: #991b1b; }
        .tl-badge-lanjutan { background: #ede9fe; color: #5b21b6; }
        .tl-badge-dijadwalkan { background: #f1f5f9; color: #64748b; }
        .tl-date { font-size: 11px; color: #94a3b8; font-weight: 500; }
        .tl-duration {
            font-size: 10px; color: #6366f1; font-weight: 700;
            background: #eef2ff; padding: 2px 8px; border-radius: 4px;
            margin-left: auto;
        }

        .tl-body { padding: 12px 14px; background: #fff; }
        .tl-time-section {
            display: flex; gap: 16px; flex-wrap: wrap;
            padding-bottom: 10px; margin-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .tl-time-item {
            display: flex; align-items: center; gap: 5px; font-size: 12px;
        }
        .tl-time-icon {
            width: 22px; height: 22px; border-radius: 6px; display: flex;
            align-items: center; justify-content: center; font-size: 10px;
        }
        .tl-start-icon { background: #dcfce7; color: #166534; }
        .tl-end-icon { background: #fee2e2; color: #991b1b; }
        .tl-time-val { font-weight: 700; color: #1e293b; font-size: 13px; }
        .tl-loc-link {
            color: #3b82f6; text-decoration: none; font-size: 11px; font-weight: 600;
        }
        .tl-loc-link:hover { text-decoration: underline; }

        .tl-info-row {
            font-size: 12px; padding: 5px 0;
            display: flex; align-items: baseline; gap: 8px;
        }
        .tl-info-label {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            min-width: 78px; flex-shrink: 0;
        }
        .tl-info-val { color: #1e293b; font-weight: 500; flex: 1; }
        .tl-info-empty { color: #d1d5db; font-style: italic; font-weight: 400; }

        .tl-photos {
            display: flex; gap: 6px; margin-top: 10px;
            padding-top: 10px; border-top: 1px solid #f1f5f9;
        }
        .tl-photo {
            width: 48px; height: 48px; border-radius: 8px; object-fit: cover;
            border: 1.5px solid #e2e8f0; transition: all 0.2s;
        }
        .tl-photo:hover { transform: scale(1.08); border-color: #3b82f6; }

        .lunas-background { position: relative; z-index: 1; }
        .lunas-background::after {
            content: ''; position: absolute; top: 30%; left: 40%;
            width: 50%; height: 50%; background-image: url('assets/img/lunas.png');
            background-size: contain; background-position: center;
            background-repeat: no-repeat; opacity: 0.1; z-index: -1;
        }
        <?php include "css/floating-menu2.css"; ?>

        @media (max-width: 768px) {
            .view-hero { padding: 16px; }
            .hero-customer { font-size: 16px; }
            .vk-card-body { padding: 12px 14px; }
            .tl-body { padding: 10px 12px; }
            .tl-header { padding: 8px 12px; }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <?php if ($kegiatan_data) :
                $kegL = strtolower($kegiatan_data['kegiatan']);
                $heroBadge = 'hero-badge-default';
                if (strpos($kegL, 'service') !== false) $heroBadge = 'hero-badge-service';
                elseif (strpos($kegL, 'survey') !== false) $heroBadge = 'hero-badge-survey';
                elseif (strpos($kegL, 'pasang') !== false) $heroBadge = 'hero-badge-pasang';
            ?>

            <!-- ═══ HERO HEADER ═══ -->
            <div class="view-hero">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <span class="hero-badge <?= $heroBadge ?>"><?= htmlspecialchars(ucwords($kegiatan_data['kegiatan'])) ?></span>
                        <span class="hero-kode"><?= htmlspecialchars($kegiatan_data['kode']) ?></span>
                    </div>
                    <p class="hero-customer"><?= htmlspecialchars($kegiatan_data['nama_customer']) ?></p>
                    <div class="hero-meta">
                        <?php
                        $nomorHP = $kegiatan_data['cust_nomor'];
                        if (substr($nomorHP, 0, 1) === '0') $nomorHP = '62' . substr($nomorHP, 1);
                        ?>
                        <a href="https://api.whatsapp.com/send?phone=<?= $nomorHP ?>" target="_blank">
                            <i class="material-icons" style="font-size:14px;">phone</i> <?= htmlspecialchars($kegiatan_data['cust_nomor']) ?>
                        </a>
                        <span><i class="material-icons" style="font-size:14px;">location_on</i> <?= htmlspecialchars($kegiatan_data['alamat']) ?></span>
                        <span><i class="material-icons" style="font-size:14px;">event</i> <?= date("d M Y, H:i", strtotime($kegiatan_data['jadwal'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- ═══ LEFT COLUMN ═══ -->
                <div class="col-lg-4 mb-4">
                    <!-- Detail Kegiatan -->
                    <div class="vk-card">
                        <div class="vk-card-header">
                            <div class="icon-circle" style="background:#eef2ff;"><i class="material-icons" style="font-size:16px;color:#6366f1;">info</i></div>
                            <h6>Detail Kegiatan</h6>
                        </div>
                        <div class="vk-card-body">
                            <div class="detail-row"><span class="detail-label">Kode</span><span class="detail-value"><?= htmlspecialchars($kegiatan_data['kode']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Jenis</span><span class="detail-value" style="text-transform:capitalize;"><?= htmlspecialchars($kegiatan_data['kegiatan']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value"><?= htmlspecialchars($kegiatan_data['nama_customer']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Kontak</span><span class="detail-value"><a href="https://api.whatsapp.com/send?phone=<?= $nomorHP ?>" target="_blank"><?= htmlspecialchars($kegiatan_data['cust_nomor']) ?></a></span></div>
                            <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value"><?= htmlspecialchars($kegiatan_data['alamat']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Ket.</span><span class="detail-value"><?= !empty($kegiatan_data['keterangan']) ? htmlspecialchars($kegiatan_data['keterangan']) : '<span style="color:#cbd5e1;">-</span>' ?></span></div>
                        </div>
                    </div>

                    <!-- Perlu Ditagih -->
                    <div class="vk-card">
                        <div class="vk-card-header">
                            <div class="icon-circle" style="background:#fef3c7;"><i class="material-icons" style="font-size:16px;color:#d97706;">shopping_cart</i></div>
                            <h6>Perlu Ditagih</h6>
                        </div>
                        <div class="vk-card-body">
                            <?php if (!empty($tagihan_items)) : ?>
                                <?php foreach($tagihan_items as $item): ?>
                                    <div class="tagihan-item">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="tagihan-qty"><?= htmlspecialchars($item['qty']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align:center;padding:12px 0;color:#94a3b8;font-size:12px;">
                                    <i class="material-icons" style="font-size:28px;color:#e2e8f0;display:block;margin-bottom:4px;">check_circle</i>
                                    Tidak ada item tambahan
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informasi Keuangan -->
                    <div class="vk-card <?= (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') ? 'lunas-background' : '' ?>">
                        <div class="vk-card-header">
                            <div class="icon-circle" style="background:#dcfce7;"><i class="material-icons" style="font-size:16px;color:#16a34a;">receipt_long</i></div>
                            <h6>Informasi Keuangan</h6>
                        </div>
                        <div class="vk-card-body">
                            <?php if ($invoice_data) : ?>
                                <div class="detail-row">
                                    <span class="detail-label">Invoice</span>
                                    <span class="detail-value" style="font-weight:700;color:#3b82f6;"><?= htmlspecialchars($invoice_data['no_invoice']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Nominal</span>
                                    <span class="detail-value" style="font-weight:700;color:#059669;font-size:16px;">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">
                                        <?php if (!empty($kegiatan_data['lunas']) && $kegiatan_data['lunas'] != '0000-00-00') : ?>
                                            <span class="invoice-status invoice-lunas"><i class="material-icons" style="font-size:12px;">check_circle</i> Lunas <?= date("d M Y", strtotime($kegiatan_data['lunas'])) ?></span>
                                        <?php else : ?>
                                            <span class="invoice-status invoice-belum"><i class="material-icons" style="font-size:12px;">warning</i> Belum Lunas</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php else : ?>
                                <div style="text-align:center;padding:16px 0;">
                                    <i class="material-icons" style="font-size:32px;color:#fca5a5;display:block;margin-bottom:6px;">receipt</i>
                                    <span style="color:#ef4444;font-size:13px;font-weight:600;">Invoice belum dibuat</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ═══ RIGHT COLUMN - RIWAYAT ═══ -->
                <div class="col-lg-8">
                    <div class="vk-card">
                        <div class="vk-card-header">
                            <div class="icon-circle" style="background:#dbeafe;"><i class="material-icons" style="font-size:16px;color:#2563eb;">engineering</i></div>
                            <h6>Riwayat Pelaksanaan Teknisi</h6>
                        </div>
                        <div class="vk-card-body tl-container">
                            <?php if (!empty($pelaksanaan_grouped)) : ?>
                                <!-- Teknisi Tabs -->
                                <div class="tl-teknisi-tabs">
                                    <?php $tab_index = 0; foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks) : $tab_index++; ?>
                                        <button class="tl-tab <?= ($tab_index == 1) ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#panel-<?= $tab_index ?>" type="button"><?= htmlspecialchars(shortenTechnicianName($nama_teknisi)); ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Tab Content -->
                                <div class="tab-content" id="teknisiTabContent">
                                    <?php $tab_index = 0; foreach ($pelaksanaan_grouped as $nama_teknisi => $tasks) : $tab_index++; ?>
                                        <div class="tab-pane fade <?= ($tab_index == 1) ? 'show active' : '' ?>" id="panel-<?= $tab_index ?>" role="tabpanel">
                                            <?php foreach ($tasks as $task) :
                                                $entryClass = 'tl-entry';
                                                $badgeClass = 'tl-badge-dijadwalkan';
                                                if ($task['status'] == 'selesai') { $entryClass .= ' tl-entry-selesai'; $badgeClass = 'tl-badge-selesai'; }
                                                elseif ($task['status'] == 'berjalan') { $entryClass .= ' tl-entry-berjalan'; $badgeClass = 'tl-badge-berjalan'; }
                                                elseif ($task['status'] == 'menunggu laporan') { $entryClass .= ' tl-entry-menunggu'; $badgeClass = 'tl-badge-menunggu'; }
                                                elseif ($task['status'] == 'Lanjut Nanti') { $entryClass .= ' tl-entry-lanjut'; $badgeClass = 'tl-badge-lanjut'; }
                                                elseif ($task['status'] == 'Lanjutan') { $entryClass .= ' tl-entry-lanjutan'; $badgeClass = 'tl-badge-lanjutan'; }
                                                $durationText = '';
                                                $mulaiValid = !empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00';
                                                $selesaiValid = !empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00';
                                                if ($mulaiValid && $selesaiValid) {
                                                    $diffSec = strtotime($task['waktu_selesai']) - strtotime($task['waktu_mulai']);
                                                    if ($diffSec > 0) {
                                                        $h = floor($diffSec / 3600); $m = floor(($diffSec % 3600) / 60);
                                                        $durationText = ($h > 0 ? $h . 'j ' : '') . $m . 'm';
                                                    }
                                                }
                                            ?>
                                                <div class="<?= $entryClass ?>">
                                                    <div class="tl-header">
                                                        <span class="tl-status-badge <?= $badgeClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                                                        <span class="tl-date"><?= date("d M Y", strtotime($task['waktu_mulai'])) ?></span>
                                                        <?php if ($durationText): ?><span class="tl-duration">⏱ <?= $durationText ?></span><?php endif; ?>
                                                    </div>
                                                    <div class="tl-body">
                                                        <div class="tl-time-section">
                                                            <div class="tl-time-item">
                                                                <div class="tl-time-icon tl-start-icon">▶</div>
                                                                <span class="tl-time-val"><?= $mulaiValid ? date("H:i", strtotime($task['waktu_mulai'])) : '--:--' ?></span>
                                                                <?php if(!empty($task['latitude']) && !empty($task['longitude'])): ?>
                                                                    <a href="https://maps.google.com/maps?q=<?= $task['latitude'] ?>,<?= $task['longitude'] ?>" target="_blank" class="tl-loc-link">📍 Lokasi</a>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="tl-time-item">
                                                                <div class="tl-time-icon tl-end-icon">⏹</div>
                                                                <span class="tl-time-val"><?= $selesaiValid ? date("H:i", strtotime($task['waktu_selesai'])) : '--:--' ?></span>
                                                                <?php if(!empty($task['latitude_s']) && !empty($task['longitude_s'])): ?>
                                                                    <a href="https://maps.google.com/maps?q=<?= $task['latitude_s'] ?>,<?= $task['longitude_s'] ?>" target="_blank" class="tl-loc-link">📍 Lokasi</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="tl-info-row"><span class="tl-info-label">Masalah</span> <span class="tl-info-val"><?= !empty($task['permasalahan']) ? htmlspecialchars($task['permasalahan']) : '<span class="tl-info-empty">Belum diisi</span>' ?></span></div>
                                                        <div class="tl-info-row"><span class="tl-info-label">Solusi</span> <span class="tl-info-val"><?= !empty($task['solusi']) ? htmlspecialchars($task['solusi']) : '<span class="tl-info-empty">Belum diisi</span>' ?></span></div>
                                                        <div class="tl-info-row"><span class="tl-info-label">Keterangan</span> <span class="tl-info-val"><?= !empty($task['keterangan']) ? htmlspecialchars($task['keterangan']) : '<span class="tl-info-empty">Belum diisi</span>' ?></span></div>
                                                        <?php
                                                        $hasPhotos = false;
                                                        foreach (['image_1', 'image_2', 'image_3', 'image_4', 'image_5'] as $img) {
                                                            if (!empty($task[$img])) { $hasPhotos = true; break; }
                                                        }
                                                        if ($hasPhotos): ?>
                                                            <div class="tl-photos">
                                                                <?php foreach (['image_1', 'image_2', 'image_3', 'image_4', 'image_5'] as $img) : ?>
                                                                    <?php if (!empty($task[$img])) : ?>
                                                                        <a href="https://api-teknisi.id-giti.com/storage/image/<?= $task[$img] ?>" target="_blank">
                                                                            <img src="https://api-teknisi.id-giti.com/storage/image/<?= $task[$img] ?>" alt="foto" class="tl-photo">
                                                                        </a>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="text-align:center;padding:32px 0;color:#94a3b8;">
                                    <i class="material-icons" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:8px;">engineering</i>
                                    <p style="font-size:13px;margin:0;">Belum ada riwayat pelaksanaan dari teknisi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else : ?>
                <div class="vk-card" style="text-align:center;padding:48px;">
                    <i class="material-icons" style="font-size:56px;color:#fca5a5;display:block;margin-bottom:12px;">error_outline</i>
                    <h5 style="color:#ef4444;margin:0 0 6px;">Data Tidak Ditemukan</h5>
                    <p style="color:#94a3b8;font-size:13px;margin:0;">Kegiatan dengan kode '<strong><?= htmlspecialchars($kode_transaksi) ?></strong>' tidak ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php include "footer.php"; ?>
    </main>
    <?php include "js-include.php"; ?>
    <script>
    // Manual tab handler - fix Bootstrap/Material Dashboard conflict
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.tl-tab').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                // Remove active from all tabs
                document.querySelectorAll('.tl-tab').forEach(function(t) { t.classList.remove('active'); });
                // Add active to clicked tab
                this.classList.add('active');
                // Hide all panels
                document.querySelectorAll('#teknisiTabContent .tab-pane').forEach(function(p) {
                    p.classList.remove('show', 'active');
                });
                // Show target panel
                var target = this.getAttribute('data-bs-target');
                var panel = document.querySelector(target);
                if (panel) {
                    panel.classList.add('show', 'active');
                }
            });
        });
    });
    </script>
</body>
</html>