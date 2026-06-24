<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today";
$role = $jabatan;

// --- Notifikasi Alert ---
if (isset($_GET['error'])) {
    $error_messages = [
        1 => 'Gagal memproses data. Silakan coba lagi.',
        2 => 'Gagal. Data yang diperlukan tidak lengkap.',
        3 => 'Permintaan tidak valid. Silakan coba lagi.'
    ];
    $error_code = $_GET['error'];
    $message = $error_messages[$error_code] ?? 'Terjadi kesalahan tidak diketahui.';
    echo "<script>alert('$message');</script>";
} elseif (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        echo "<script>alert('Berhasil menambahkan invoice.');</script>";
    } elseif ($_GET['success'] == 2) {
        echo "<script>alert('Status pembayaran berhasil diperbarui.');</script>";
    } elseif ($_GET['success'] == 3) {
        $count = intval($_GET['count'] ?? 0);
        echo "<script>alert('Berhasil menghapus $count kegiatan.');</script>";
    }
}

// --- Logika untuk Tab Aktif ---
$active_tab = $_GET['tab'] ?? 'belum_lunas'; // Default ke 'belum_lunas'

// --- Fetch teknisi list for filter ---
$teknisiFilter = [];
$resTek = mysqli_query($conn, "SELECT DISTINCT t.id, t.nama FROM teknisi t INNER JOIN team_kegiatan tk ON tk.teknisi_id = t.id WHERE t.deleted_at IS NULL AND tk.deleted_at IS NULL ORDER BY t.nama ASC");
while ($rTek = mysqli_fetch_assoc($resTek)) {
    $teknisiFilter[] = $rTek;
}
$selectedTeknisi = intval($_GET['teknisi'] ?? 0);

// --- Month filter ---
$filterBulan = $_GET['bulan'] ?? ''; // format: "2026-06" or "2026-04_3" (3 bulan)
$filterDateStart = '';
$filterDateEnd = '';
if (!empty($filterBulan)) {
    if (str_contains($filterBulan, '_3')) {
        // 3 bulan: ambil bulan awal, hitung 3 bulan
        $baseBulan = str_replace('_3', '', $filterBulan);
        $filterDateStart = $baseBulan . '-01';
        $filterDateEnd = date('Y-m-t', strtotime($baseBulan . '-01 +2 months'));
    } else {
        // 1 bulan
        $filterDateStart = $filterBulan . '-01';
        $filterDateEnd = date('Y-m-t', strtotime($filterBulan . '-01'));
    }
}

// Build month options for last 12 months
$monthOptions = [];
$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
for ($i = 0; $i < 12; $i++) {
    $dt = new DateTime();
    $dt->modify("-$i months");
    $val = $dt->format('Y-m');
    $bln = intval($dt->format('m'));
    $thn = $dt->format('Y');
    $monthOptions[] = ['value' => $val, 'label' => $namaBulan[$bln] . ' ' . $thn];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        /* === Base & Typography === */
        .table th, .table td { vertical-align: middle !important; }
        .table .customer-info h6 { font-size: .925rem; color: #1a2332; letter-spacing: -0.01em; }
        .table .customer-info a { text-decoration: none; }
        .table .customer-info a:hover h6 { color: #3b5998; }

        /* === Table Refinement === */
        .table thead th { 
            font-size: 10.5px; letter-spacing: 0.08em; color: #64748b; 
            border-bottom: 2px solid #e2e8f0; padding: 12px 8px;
        }
        .table tbody tr { transition: background-color 0.15s ease; border-bottom: 1px solid #f1f5f9 !important; }
        .table tbody tr:hover { background-color: #f8fafc !important; }
        .table tbody tr:last-child { border-bottom: none !important; }

        /* === Technician List === */
        .technician-list .technician-item { width: 100%; border-bottom: 1px solid #f1f5f9; padding: 8px 0; }
        .technician-list .technician-item:last-child { border-bottom: none; }

        /* === Invoice Card === */
        .invoice-summary { 
            background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; 
            padding: 12px !important; height: 100%; 
        }
        .lunas-background { 
            background-image: url('assets/img/lunas.png'); 
            background-size: 30%; background-position: center; background-repeat: no-repeat; 
        }

        /* === Nav Tabs (Belum Lunas / Lunas) === */
        .nav-tabs { border-bottom: 2px solid #e2e8f0; gap: 4px; }
        .nav-tabs .nav-link { 
            font-size: 13px; font-weight: 600; color: #64748b; 
            padding: 10px 20px; border: none; border-radius: 6px 6px 0 0;
            transition: all 0.2s ease; 
        }
        .nav-tabs .nav-link:hover { color: #334155; background: #f1f5f9; }
        .nav-tabs .nav-link.active { 
            background-color: #1e293b !important; color: #fff !important; 
            border-bottom: none;
        }

        /* === Action Buttons === */
        .btn-action { 
            width: 32px; height: 32px; padding: 0; display: inline-flex; 
            align-items: center; justify-content: center; border-radius: 6px; 
            border: none; transition: all 0.2s ease; font-size: 14px;
        }
        .btn-action-pay { 
            background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
            font-size: 12px; width: auto; padding: 0 12px; font-weight: 600;
        }
        .btn-action-pay:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
        .btn-action-reset { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .btn-action-reset:hover { background: #d97706; color: #fff; border-color: #d97706; }
        .btn-action-delete { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-action-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* === Checkbox Styling === */
        .form-check-input-custom {
            width: 16px; height: 16px; cursor: pointer;
            accent-color: #1e293b; border-radius: 3px;
        }

        /* === Status Badge === */
        .badge-lunas { 
            display: inline-block; font-size: 10px; font-weight: 700; 
            padding: 3px 8px; border-radius: 4px; letter-spacing: 0.03em;
        }
        .badge-lunas-paid { background: #dcfce7; color: #15803d; }
        .badge-lunas-unpaid { background: #fef2f2; color: #dc2626; }

        /* === Search Input === */
        .search-input { 
            border: 1px solid #e2e8f0 !important; border-radius: 8px !important;
            font-size: 13px; padding: 10px 16px !important; 
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }
        .search-input:focus { 
            border-color: #94a3b8 !important; 
            box-shadow: 0 0 0 3px rgba(148,163,184,0.1) !important;
        }
        .search-btn { 
            border-radius: 0 8px 8px 0 !important; 
            background: #1e293b !important; border: 1px solid #1e293b !important;
        }
        .search-btn:hover { background: #334155 !important; }

        /* === Card === */
        .card { border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; }
        .page-title { font-size: 15px; color: #1e293b; letter-spacing: 0.02em; }

        /* === Modal === */
        .modal-xl { max-width: 80%; }
        @media (max-width: 767px) { .modal-xl { max-width: 95%; } }

        /* === Selected Row === */
        tr:has(.form-check-input-custom:checked) { background-color: #f0f9ff !important; }

        /* === Share Amount Badge === */
        .badge-share-amount {
            font-size: 11px; font-weight: 700; color: #059669;
            background: #ecfdf5; border: 1px solid #a7f3d0;
            padding: 2px 8px; border-radius: 12px;
            margin-left: 8px; font-variant-numeric: tabular-nums;
            display: inline-block;
        }

        /* === Bulk Delete Bar === */
        .bulk-bar {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9999;
            background: #1e293b; color: #fff; padding: 10px 24px; border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); cursor: pointer;
            transition: all 0.2s ease; display: none; font-size: 13px; font-weight: 600;
            animation: barSlideUp 0.25s ease;
        }
        .bulk-bar:hover { background: #dc2626; box-shadow: 0 6px 25px rgba(220,38,38,0.25); }
        @keyframes barSlideUp { from { opacity:0; transform:translateX(-50%) translateY(16px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }

        /* === Premium Modal Styling === */
        .modal-content-premium {
            border: none !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1), 0 30px 60px rgba(0,0,0,0.08) !important;
            overflow: hidden;
            background: #fff;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
        }
        .modal-header-premium {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px !important;
        }
        .modal-title-premium {
            font-size: 17px !important;
            font-weight: 750 !important;
            color: #1e293b !important;
            letter-spacing: -0.01em;
        }
        .modal-body-premium {
            padding: 24px !important;
        }
        .modal-desc-premium {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .modal-label-premium {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: block;
        }
        .modal-input-premium {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            color: #1e293b !important;
            background: #f8fafc !important;
            transition: all 0.2s ease !important;
            font-weight: 600 !important;
            outline: none;
            width: 100%;
        }
        .modal-input-premium:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
            background: #fff !important;
        }
        .modal-footer-premium {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 16px 24px !important;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-modal-cancel {
            background: #fff !important;
            border: 1.5px solid #e2e8f0 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 10px 20px !important;
            border-radius: 12px !important;
            transition: all 0.2s !important;
            cursor: pointer;
            box-shadow: none !important;
            text-transform: none !important;
        }
        .btn-modal-cancel:hover {
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
        }
        .btn-modal-save {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            border: none !important;
            color: #fff !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 10px 24px !important;
            border-radius: 12px !important;
            transition: all 0.2s !important;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
            text-transform: none !important;
        }
        .btn-modal-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3) !important;
        }

        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                    <?php include 'nav-laporan.php'; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 page-title text-uppercase font-weight-bold">Laporan Kegiatan Selesai</h5>
                                <form method="GET" action="" class="w-100 w-md-50">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab); ?>">
                                        <select name="bulan" class="form-select search-input" style="max-width:200px;padding:8px 12px !important;font-size:13px;">
                                            <option value="">Semua Bulan</option>
                                            <optgroup label="Per Bulan">
                                                <?php foreach ($monthOptions as $mo): ?>
                                                    <option value="<?= $mo['value'] ?>" <?= $filterBulan == $mo['value'] ? 'selected' : '' ?>><?= $mo['label'] ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Per 3 Bulan">
                                                <?php 
                                                for ($i = 0; $i < 12; $i += 3) {
                                                    $dt3 = new DateTime();
                                                    $dt3->modify("-$i months");
                                                    $val3 = $dt3->format('Y-m') . '_3';
                                                    $bln3s = intval($dt3->format('m'));
                                                    $dt3e = clone $dt3;
                                                    $dt3e->modify('-2 months');
                                                    $bln3e = intval($dt3e->format('m'));
                                                    $label3 = $namaBulan[$bln3e] . ' - ' . $namaBulan[$bln3s] . ' ' . $dt3->format('Y');
                                                    echo '<option value="' . $dt3e->format('Y-m') . '_3"' . ($filterBulan == $dt3e->format('Y-m') . '_3' ? ' selected' : '') . '>' . $label3 . '</option>';
                                                }
                                                ?>
                                            </optgroup>
                                        </select>
                                        <select name="teknisi" class="form-select search-input" style="max-width:180px;padding:8px 12px !important;font-size:13px;">
                                            <option value="0">Semua Teknisi</option>
                                            <?php foreach ($teknisiFilter as $tek): ?>
                                                <option value="<?= $tek['id'] ?>" <?= $selectedTeknisi == $tek['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tek['nama']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="input-group" style="flex:1;min-width:180px;">
                                            <input type="text" name="cari" class="form-control search-input" placeholder="Cari customer / invoice..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                            <button class="btn search-btn mb-0" type="submit"><i class="material-icons text-sm text-white">search</i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-tabs px-3" id="laporanTab" role="tablist">
                                <?php $tabParams = ($selectedTeknisi > 0 ? '&teknisi=' . $selectedTeknisi : '') . (!empty($filterBulan) ? '&bulan=' . urlencode($filterBulan) : '') . (!empty($_GET['cari']) ? '&cari=' . urlencode($_GET['cari']) : ''); ?>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $active_tab == 'belum_lunas' ? 'active' : '' ?>" href="?tab=belum_lunas<?= $tabParams ?>">Belum Lunas</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $active_tab == 'lunas' ? 'active' : '' ?>" href="?tab=lunas<?= $tabParams ?>">Lunas</a>
                                </li>
                            </ul>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center ps-3" style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input-custom"></th>
                                            <th class="ps-2">CUSTOMER</th>
                                            <th>INVOICE</th>
                                            <th>TEKNISI & ABSENSI</th>
                                            <th class="text-center pe-3">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query Utama dengan filter tab
                                        $search = $_GET['cari'] ?? '';
                                        $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.lunas, c.id AS id_cust, c.nama AS nama_cust
                                                    FROM kegiatan k
                                                    LEFT JOIN customer c ON k.customer_id = c.id
                                                    LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
                                                    LEFT JOIN pendapatan_kegiatan inv ON k.kode = inv.kode AND inv.deleted_at IS NULL
                                                    WHERE k.status != 'waiting' AND k.paid = 'yes' AND k.deleted_at IS NULL AND p.kode IS NOT NULL";

                                        if ($active_tab == 'belum_lunas') {
                                            $sql_main .= " AND (k.lunas IS NULL OR k.lunas = '0000-00-00')";
                                        } else { // tab 'lunas'
                                            $sql_main .= " AND (k.lunas IS NOT NULL AND k.lunas != '0000-00-00')";
                                        }

                                        // Filter by bulan
                                        if (!empty($filterDateStart) && !empty($filterDateEnd)) {
                                            $sql_main .= " AND DATE(k.created_at) >= ? AND DATE(k.created_at) <= ?";
                                        }

                                        // Filter by teknisi
                                        if ($selectedTeknisi > 0) {
                                            $sql_main .= " AND EXISTS (SELECT 1 FROM pelaksanaan_kegiatan px WHERE px.kode = k.kode AND px.teknisi_id = ? AND px.deleted_at IS NULL)";
                                        }

                                        if (!empty($search)) {
                                            $sql_main .= " AND (c.nama LIKE ? OR inv.no_invoice LIKE ?)";
                                        }
                                        $sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";

                                        $stmt_main = $conn->prepare($sql_main);
                                        // Dynamic bind params
                                        $paramTypes = '';
                                        $paramValues = [];
                                        if (!empty($filterDateStart) && !empty($filterDateEnd)) {
                                            $paramTypes .= 'ss';
                                            $paramValues[] = $filterDateStart;
                                            $paramValues[] = $filterDateEnd;
                                        }
                                        if ($selectedTeknisi > 0) {
                                            $paramTypes .= 'i';
                                            $paramValues[] = $selectedTeknisi;
                                        }
                                        if (!empty($search)) {
                                            $search_param = "%$search%";
                                            $paramTypes .= 'ss';
                                            $paramValues[] = $search_param;
                                            $paramValues[] = $search_param;
                                        }
                                        if (!empty($paramTypes)) {
                                            $stmt_main->bind_param($paramTypes, ...$paramValues);
                                        }
                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();

                                        if ($result_main->num_rows > 0) {
                                            while ($row_main = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $idC = $row_main['id_cust'];
                                                $lunas_class = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                                        ?>
                                                <tr>
                                                    <td class="text-center ps-3" style="width:40px;"><input type="checkbox" class="form-check-input-custom row-checkbox" value="<?= $kodeTransaksi; ?>" data-customer="<?= htmlspecialchars($row_main['nama_cust']); ?>"></td>
                                                    <td class="ps-2 customer-info text-wrap">
                                                        <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                            <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                        </a>
                                                        <p class="text-sm text-secondary mb-1">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="text-xs text-muted mb-0">Request: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
                                                    </td>

                                                    <td class="text-wrap <?= $lunas_class ?>">
                                                        <?php
                                                        $sql_invoice = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? AND deleted_at IS NULL LIMIT 1";
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
                                                                        <span class="text-xs text-uppercase font-weight-bold">No. Invoice:</span>
                                                                        <p class="text-sm font-weight-bolder mb-0"><?= htmlspecialchars($invoice_data['no_invoice']); ?></p>
                                                                        <p class="text-xxs text-muted mb-0"><?= date("d M Y", strtotime($invoice_data['tanggal'])); ?></p>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <span class="text-xs text-uppercase font-weight-bold">Nominal:</span>
                                                                        <p class="text-sm font-weight-bolder text-success mb-0">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></p>
                                                                        <p class="mb-0">
                                                                            <span class="badge-lunas <?= $lunas_class ? 'badge-lunas-paid' : 'badge-lunas-unpaid'; ?>">
                                                                                <?= $lunas_class ? date("d M Y", strtotime($row_main['lunas'])) : 'Belum Lunas'; ?>
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            <?php else : ?>
                                                                <p class="text-xs text-center text-danger font-weight-bold mb-0">Tidak ada Invoice</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>

                                                    <td class="technician-list pe-4">
                                                        <?php
                                                         $sql_teknisi = "SELECT t.nama_teknisi, p.teknisi_id, p.kode,
                                                                         (SELECT MIN(waktu_mulai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_mulai_pertama,
                                                                         (SELECT MAX(waktu_selesai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_selesai_terakhir,
                                                                         (SELECT pendapatan FROM pendapatan_kegiatan WHERE kode = p.kode AND teknisi_id = p.teknisi_id AND deleted_at IS NULL LIMIT 1) AS pendapatan
                                                                     FROM pelaksanaan_kegiatan p JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id JOIN kegiatan k ON t.kegiatan_id = k.id
                                                                     WHERE p.kode = ? AND k.customer_id = ? AND p.deleted_at IS NULL GROUP BY p.teknisi_id";
                                                        $stmt_teknisi = $conn->prepare($sql_teknisi);
                                                        $stmt_teknisi->bind_param("si", $kodeTransaksi, $idC);
                                                        $stmt_teknisi->execute();
                                                        $result_teknisi = $stmt_teknisi->get_result();
                                                        if($result_teknisi->num_rows > 0) {
                                                            while($row_teknisi = $result_teknisi->fetch_assoc()) {
                                                        ?>
                                                         <div class="d-flex align-items-center w-100 py-2 technician-item">
                                                             <div class="text-sm font-weight-bold" style="flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                 <?= htmlspecialchars($row_teknisi['nama_teknisi']); ?>
                                                             </div>
                                                             <div style="min-width: 105px; text-align: right; margin-right: 16px; flex-shrink: 0;">
                                                                 <?php if ($row_teknisi['pendapatan'] !== null): ?>
                                                                     <span class="badge-share-amount" style="margin-left: 0;">Rp <?= number_format($row_teknisi['pendapatan'], 0, ',', '.'); ?></span>
                                                                 <?php endif; ?>
                                                             </div>
                                                             <div class="text-end" style="width: 110px; flex-shrink: 0;">
                                                                 <p class="text-xs text-dark mb-0">Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?></p>
                                                                 <p class="text-xs text-dark mb-0">Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?></p>
                                                             </div>
                                                         </div><?php }
                                                        } else { echo "<p class='text-xs text-danger text-center mb-0'>Data teknisi tidak ditemukan.</p>"; }
                                                        $stmt_teknisi->close();
                                                        ?>
                                                    </td>

                                                    <td class="text-center pe-3">
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <?php if ($active_tab == 'belum_lunas') : ?>
                                                                <button class="btn-action btn-action-pay lunasBtn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#lunasModal" 
                                                                    data-kode="<?= $kodeTransaksi; ?>" 
                                                                    data-lunas="<?= !empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00' ? date('Y-m-d', strtotime($row_main['lunas'])) : ''; ?>" 
                                                                    title="Lunas">
                                                                    💸 Lunas
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn-action btn-action-pay lunasBtn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#lunasModal" 
                                                                    data-kode="<?= $kodeTransaksi; ?>" 
                                                                    data-lunas="<?= !empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00' ? date('Y-m-d', strtotime($row_main['lunas'])) : ''; ?>" 
                                                                    title="Edit tgl LUNAS" 
                                                                    style="background:#eff6ff; color:#2563eb; border-color:#bfdbfe; font-size: 11px; font-weight: 700; width: auto; padding: 0 10px;">
                                                                    📝 Edit tgl LUNAS
                                                                </button>
                                                            <?php endif; ?>
                                                            <a class="btn-action btn-action-reset" href="reset_invoice.php?kode=<?= $kodeTransaksi; ?>" title="Reset Invoice">
                                                                <i class="material-icons" style="font-size:15px;">refresh</i>
                                                            </a>
                                                            <button class="btn-action btn-action-delete btn-hapus-laporan" 
                                                                data-kode="<?= $kodeTransaksi; ?>" 
                                                                data-customer="<?= htmlspecialchars($row_main['nama_cust']); ?>" title="Hapus">
                                                                <i class="material-icons" style="font-size:15px;">delete_outline</i>
                                                            </button>
                                                        </div>
                                                    </td>

                                                </tr>
                                        <?php }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center py-5'>Tidak ada data laporan yang ditemukan.</td></tr>";
                                        }
                                        $stmt_main->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <div class="modal fade" style="z-index:99999;" id="lunasModal" tabindex="-1" aria-labelledby="lunasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-premium">
                <form id="lunasForm">
                    <div class="modal-header modal-header-premium">
                        <h5 class="modal-title modal-title-premium" id="lunasModalLabel">Tandai Pelunasan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body modal-body-premium">
                        <p id="lunasModalDesc" class="modal-desc-premium">Anda akan menandai transaksi ini sebagai lunas. Silakan masukkan tanggal pembayaran.</p>
                        <input type="hidden" id="kode_transaksi_lunas" name="kode_transaksi">
                        <div class="form-group">
                            <label for="tanggal_lunas" class="modal-label-premium">Tanggal Pelunasan</label>
                            <input type="date" class="modal-input-premium" id="tanggal_lunas" name="tanggal_lunas" max="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer modal-footer-premium">
                        <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-modal-save">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Handler untuk Modal Pelunasan ---
        const lunasButtons = document.querySelectorAll('.lunasBtn');
        lunasButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const kode_transaksi = this.getAttribute('data-kode');
                const existingLunas = this.getAttribute('data-lunas');
                document.getElementById('kode_transaksi_lunas').value = kode_transaksi;
                
                const titleEl = document.getElementById('lunasModalLabel');
                const descEl = document.getElementById('lunasModalDesc');
                
                if (existingLunas) {
                    document.getElementById('tanggal_lunas').value = existingLunas;
                    if (titleEl) titleEl.textContent = "Edit Tanggal Pelunasan";
                    if (descEl) descEl.textContent = "Silakan ubah tanggal pelunasan untuk transaksi ini.";
                } else {
                    document.getElementById('tanggal_lunas').valueAsDate = new Date();
                    if (titleEl) titleEl.textContent = "Tandai Pelunasan";
                    if (descEl) descEl.textContent = "Anda akan menandai transaksi ini sebagai lunas. Silakan masukkan tanggal pembayaran.";
                }
            });
        });

        const lunasForm = document.getElementById('lunasForm');
        if (lunasForm) {
            lunasForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Mencegah form submit biasa
                
                const formData = new FormData(lunasForm);
                
                // Tutup modal pelunasan agar tidak menghalangi SweetAlert
                const modalEl = document.getElementById('lunasModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    } else if (window.$ || window.jQuery) {
                        $(modalEl).modal('hide');
                    }
                }
                
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang menyimpan status pelunasan',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('proses_update_lunas.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(responseText => {
                    if (responseText.includes('success')) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Status pembayaran berhasil diperbarui.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            // Redirect ke tab 'lunas'
                            window.location.href = 'lap-kegiatan-selesai.php?tab=lunas';
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: 'Gagal memperbarui status: ' + responseText,
                            icon: 'error',
                            confirmButtonText: 'Tutup'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan koneksi: ' + error,
                        icon: 'error',
                        confirmButtonText: 'Tutup'
                    });
                });
            });
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.querySelectorAll('.btn-hapus-laporan').forEach(btn => {
        btn.addEventListener('click', function() {
            const kode = this.dataset.kode;
            const customer = this.dataset.customer;
            Swal.fire({
                title: 'Hapus Kegiatan?',
                html: `<p style="margin:0;color:#666;">Kode: <strong>${kode}</strong></p>
                       <p style="margin:0;color:#666;">Customer: <strong>${customer}</strong></p>
                       <p style="margin-top:10px;color:#e74c3c;font-size:13px;">Data yang dihapus tidak dapat dikembalikan!</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">delete</i> Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete-kegiatan.php?kode=' + kode;
                }
            });
        });
    });
    </script>

    <!-- Floating Bulk Delete Bar -->
    <div id="bulkDeleteBar" class="bulk-bar" onclick="confirmBulkDelete()">
        <i class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:6px;">delete_sweep</i>
        Hapus Terpilih (<span id="selectedCount">0</span>)
    </div>

    <!-- Hidden form for bulk delete -->
    <form id="bulkDeleteForm" method="POST" action="bulk-delete-kegiatan.php" style="display:none;">
        <input type="hidden" name="redirect" value="lap-kegiatan-selesai.php?tab=<?= htmlspecialchars($active_tab); ?>">
    </form>

    <script>
    // Select All checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = this.checked; });
        updateBulkBar();
    });

    // Individual checkbox
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const allBoxes = document.querySelectorAll('.row-checkbox');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            document.getElementById('selectAll').checked = allBoxes.length === checkedBoxes.length;
            updateBulkBar();
        });
    });

    function updateBulkBar() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkDeleteBar').style.display = count > 0 ? 'block' : 'none';
    }

    function confirmBulkDelete() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const count = checked.length;
        const names = [];
        checked.forEach(cb => { names.push(cb.dataset.customer); });
        const nameList = names.slice(0, 5).join(', ') + (names.length > 5 ? ` dan ${names.length - 5} lainnya` : '');

        Swal.fire({
            title: `Hapus ${count} Kegiatan?`,
            html: `<p style="margin:0;color:#666;">Customer: <strong>${nameList}</strong></p>
                   <p style="margin-top:10px;color:#e74c3c;font-size:13px;">Semua data yang dipilih akan dihapus dan tidak dapat dikembalikan!</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `<i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">delete_sweep</i> Ya, Hapus ${count} Data`,
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('bulkDeleteForm');
                // Clear old inputs
                form.querySelectorAll('input[name="kode_list[]"]').forEach(el => el.remove());
                // Add selected kodes
                checked.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'kode_list[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });
                form.submit();
            }
        });
    }
    </script>
</body>
</html>