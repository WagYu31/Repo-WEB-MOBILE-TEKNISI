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
    echo "<script>alert('Berhasil melakukan perubahan invoice.');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        /* ═══ PREMIUM LAP KEGIATAN REDESIGN ═══ */
        
        /* Card Container */
        .lk-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .lk-card-header {
            padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }
        
        /* Summary Bar */
        .lk-summary {
            display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
        }
        .lk-summary-item {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-radius: 12px; flex: 1; min-width: 160px;
            border: 1px solid #e5e7eb; background: #fff;
        }
        .lk-summary-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .lk-summary-icon.si-total { background: linear-gradient(135deg, #6366f1, #818cf8); }
        .lk-summary-icon.si-today { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .lk-summary-icon i { color: #fff; font-size: 16px; }
        .lk-summary-num { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .lk-summary-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

        /* Filter Toggle */
        .search-box { border-radius: 8px; border: 1px solid #e2e8f0; padding: 8px 12px; font-size: 13px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
        .search-box:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); outline: none; }
        .btn-search { background: #1e293b; border: none; border-radius: 8px; padding: 8px 14px; }
        .btn-search:hover { background: #334155; }
        
        /* Table */
        .lk-table-wrap { overflow-x: auto; }
        .lk-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .lk-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 20px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .lk-table tbody tr { transition: all 0.15s; border-bottom: 1px solid #f1f5f9; }
        .lk-table tbody tr:hover { background: #fafbfc; }
        .lk-table tbody td { padding: 16px 20px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
        
        /* Customer Info */
        .lk-cust-badges { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; }
        .lk-badge-jenis {
            font-size: 9px; padding: 3px 10px; border-radius: 20px; font-weight: 800;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .lk-badge-jenis.jenis-survey { background: #fef3c7; color: #92400e; }
        .lk-badge-jenis.jenis-service { background: #e0e7ff; color: #3730a3; }
        .lk-badge-jenis.jenis-pasang { background: #d1fae5; color: #065f46; }
        .lk-badge-jenis.jenis-default { background: #f1f5f9; color: #475569; }
        .lk-badge-kode {
            font-size: 10px; color: #818cf8; font-family: 'SF Mono', 'Consolas', monospace;
            font-weight: 700; background: #eef2ff; padding: 2px 8px; border-radius: 4px;
        }
        .lk-cust-name {
            font-size: 15px; font-weight: 700; color: #1e293b; text-decoration: none;
            display: inline-block; margin-bottom: 4px; transition: color 0.15s;
        }
        .lk-cust-name:hover { color: #6366f1; }
        .lk-cust-desc { font-size: 12px; color: #94a3b8; font-style: italic; margin-bottom: 3px; }
        .lk-cust-date { font-size: 11px; color: #cbd5e1; display: flex; align-items: center; gap: 4px; }
        
        /* Technician Timeline */
        .lk-tek-list { display: flex; flex-direction: column; gap: 0; }
        .lk-tek-item {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 8px 12px; border-radius: 8px; transition: background 0.15s;
            position: relative;
        }
        .lk-tek-item:hover { background: #f8fafc; }
        .lk-tek-item + .lk-tek-item { border-top: 1px dashed #f1f5f9; }
        .lk-tek-avatar {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg, #6366f1, #a78bfa);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .lk-tek-name { font-size: 13px; font-weight: 600; color: #1e293b; flex: 1; }
        .lk-tek-time {
            display: flex; gap: 10px; font-size: 11px; color: #64748b; white-space: nowrap;
        }
        .lk-tek-time .t-start { color: #16a34a; font-weight: 600; }
        .lk-tek-time .t-end { color: #dc2626; font-weight: 600; }
        .lk-tek-time i { font-size: 10px; vertical-align: middle; margin-right: 2px; }
        .lk-tek-empty { font-size: 12px; color: #cbd5e1; padding: 8px 12px; }
        
        /* Status Pelaksanaan */
        .lk-status-pill {
            font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;
        }
        .lk-status-pill.st-ok { background: #d1fae5; color: #065f46; }
        .lk-status-pill.st-warn { background: #fef3c7; color: #92400e; }
        
        /* Action Buttons */
        .lk-actions { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
        .lk-act-row { display: flex; gap: 6px; flex-wrap: wrap; }
        .lk-act-btn {
            font-size: 11px; padding: 6px 14px; border-radius: 8px; font-weight: 700;
            border: 1.5px solid transparent; transition: all 0.2s; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
            white-space: nowrap;
        }
        .lk-act-btn i { font-size: 13px; }
        .lk-act-btn.act-invoice { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .lk-act-btn.act-invoice:hover { background: #16a34a; color: #fff; border-color: #16a34a; box-shadow: 0 4px 12px rgba(22,163,74,0.2); }
        .lk-act-btn.act-nopay { background: #fef3c7; color: #d97706; border-color: #fde68a; }
        .lk-act-btn.act-nopay:hover { background: #d97706; color: #fff; border-color: #d97706; box-shadow: 0 4px 12px rgba(217,119,6,0.2); }
        .lk-act-btn.act-invalid { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .lk-act-btn.act-invalid:hover { background: #dc2626; color: #fff; border-color: #dc2626; box-shadow: 0 4px 12px rgba(220,38,38,0.2); }
        .lk-act-btn.act-note { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .lk-act-btn.act-note:hover { background: #2563eb; color: #fff; border-color: #2563eb; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        
        /* Catatan Display */
        .lk-catatan-box {
            margin-top: 8px; padding: 8px 12px; background: #f8fafc;
            border-left: 3px solid #6366f1; border-radius: 0 8px 8px 0;
            font-size: 11px; color: #475569; font-style: italic;
        }
        
        /* Empty State */
        .lk-empty { text-align: center; padding: 60px 20px; }
        .lk-empty-icon { font-size: 48px; color: #e2e8f0; margin-bottom: 16px; }
        .lk-empty-text { font-size: 14px; color: #94a3b8; font-weight: 600; }
        .lk-empty-sub { font-size: 12px; color: #cbd5e1; margin-top: 4px; }
        
        /* Modal */
        .modal-xl { max-width: 80%; }
        @media (max-width: 992px) {
            .lk-summary { flex-direction: column; }
            .lk-tek-time { flex-direction: column; gap: 2px; }
        }
        @media (max-width: 767px) {
            .modal-xl { max-width: 95%; }
            .lk-table thead th { padding: 10px 12px; }
            .lk-table tbody td { padding: 12px; }
            .lk-act-row { flex-direction: column; }
            .lk-cust-name { font-size: 13px; }
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
                    <!-- Summary Bar -->
                    <?php
                    // Count total and today's items (pre-compute before table)
                    $totalItems = count($allRows ?? []);
                    $todayCount = 0;
                    $todayStr = date('Y-m-d');
                    if (!empty($allRows)) {
                        foreach ($allRows as $r) {
                            if (date('Y-m-d', strtotime($r['created_at'])) === $todayStr) $todayCount++;
                        }
                    }
                    ?>
                    <div class="lk-summary">
                        <div class="lk-summary-item">
                            <div class="lk-summary-icon si-total"><i class="material-icons">assignment</i></div>
                            <div>
                                <div class="lk-summary-num"><?= $totalItems ?></div>
                                <div class="lk-summary-label">Total Belum Invoice</div>
                            </div>
                        </div>
                        <div class="lk-summary-item">
                            <div class="lk-summary-icon si-today"><i class="material-icons">today</i></div>
                            <div>
                                <div class="lk-summary-num"><?= $todayCount ?></div>
                                <div class="lk-summary-label">Masuk Hari Ini</div>
                            </div>
                        </div>
                    </div>

                    <div class="lk-card">
                        <div class="lk-card-header">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-10 mb-3 mb-md-0">
                                    <h5 style="margin:0;font-size:16px;font-weight:800;color:#1e293b;letter-spacing:0.02em;">Laporan Kegiatan</h5>
                                    <span style="font-size:11px;font-weight:700;color:#6366f1;background:#eef2ff;padding:3px 12px;border-radius:20px;margin-left:10px;"><?= $totalItems ?> kegiatan</span>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-sm mb-0" id="toggleFilterBtn" onclick="toggleFilters()" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;">
                                        <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:2px;">filter_list</i> Filter
                                        <?php 
                                        $activeFilters = 0;
                                        if (!empty($_GET['tahun'])) $activeFilters++;
                                        if (!empty($_GET['bulan'])) $activeFilters++;
                                        if (!empty($_GET['cari'])) $activeFilters++;
                                        if (!empty($_GET['jenis'])) $activeFilters++;
                                        if (!empty($_GET['teknisi'])) $activeFilters++;
                                        if (!empty($_GET['status_pelaksanaan'])) $activeFilters++;
                                        if ($activeFilters > 0) echo "<span style='background:#6366f1;color:#fff;border-radius:50%;padding:1px 6px;font-size:10px;margin-left:4px;'>$activeFilters</span>";
                                        ?>
                                    </button>
                                    <?php if ($activeFilters > 0): ?>
                                    <a href="lap-kegiatan.php" class="btn btn-sm mb-0" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:7px 12px;font-size:11px;font-weight:600;text-decoration:none;">
                                        <i class="material-icons" style="font-size:13px;vertical-align:middle;">close</i> Reset
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Filter Panel (Collapsible) -->
                            <div id="filterPanel" style="display:<?= $activeFilters > 0 ? 'block' : 'none' ?>;margin-top:16px;padding:16px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                                <form method="GET" action="">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-6 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Tahun</label>
                                            <select name="tahun" class="search-box w-100" onchange="this.form.submit()">
                                                <option value="">Semua</option>
                                                <?php for($y=date('Y'); $y>=2025; $y--): ?>
                                                <option value="<?=$y?>" <?= ($_GET['tahun'] ?? '')==$y ? 'selected' : '' ?>><?=$y?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Bulan</label>
                                            <select name="bulan" class="search-box w-100" onchange="this.form.submit()">
                                                <option value="">Semua</option>
                                                <?php
                                                $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                                for($m=1; $m<=12; $m++): ?>
                                                <option value="<?=$m?>" <?= ($_GET['bulan'] ?? '')==$m ? 'selected' : '' ?>><?=$namaBulan[$m]?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Jenis</label>
                                            <select name="jenis" class="search-box w-100" onchange="this.form.submit()">
                                                <option value="">Semua Jenis</option>
                                                <?php
                                                $sqlJenis = "SELECT DISTINCT kegiatan FROM kegiatan WHERE deleted_at IS NULL AND kegiatan IS NOT NULL AND kegiatan != '' ORDER BY kegiatan ASC";
                                                $resJenis = $conn->query($sqlJenis);
                                                while ($rj = $resJenis->fetch_assoc()):
                                                ?>
                                                <option value="<?= htmlspecialchars($rj['kegiatan']) ?>" <?= ($_GET['jenis'] ?? '') == $rj['kegiatan'] ? 'selected' : '' ?>><?= ucwords(htmlspecialchars($rj['kegiatan'])) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Teknisi</label>
                                            <select name="teknisi" class="search-box w-100" onchange="this.form.submit()">
                                                <option value="">Semua Teknisi</option>
                                                <?php
                                                $sqlTeknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
                                                $resTeknisi = $conn->query($sqlTeknisi);
                                                while ($rt = $resTeknisi->fetch_assoc()):
                                                ?>
                                                <option value="<?= $rt['id'] ?>" <?= ($_GET['teknisi'] ?? '') == $rt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rt['nama']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Pelaksanaan</label>
                                            <select name="status_pelaksanaan" class="search-box w-100" onchange="this.form.submit()">
                                                <option value="">Semua</option>
                                                <option value="lengkap" <?= ($_GET['status_pelaksanaan'] ?? '') == 'lengkap' ? 'selected' : '' ?>>✅ Lengkap</option>
                                                <option value="tidak_lengkap" <?= ($_GET['status_pelaksanaan'] ?? '') == 'tidak_lengkap' ? 'selected' : '' ?>>⚠️ Tidak Lengkap</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Cari</label>
                                            <div class="d-flex gap-1">
                                                <input type="text" name="cari" class="search-box flex-grow-1" placeholder="Customer / kode..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                                <button class="btn btn-search mb-0 text-white" type="submit"><i class="material-icons" style="font-size:16px;vertical-align:middle;">search</i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($activeFilters > 0): ?>
                                    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:6px;">
                                        <?php if (!empty($_GET['tahun'])): ?>
                                        <span style="font-size:11px;background:#e0e7ff;color:#3730a3;padding:4px 10px;border-radius:20px;font-weight:600;">Tahun: <?= htmlspecialchars($_GET['tahun']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($_GET['bulan'])): ?>
                                        <span style="font-size:11px;background:#e0e7ff;color:#3730a3;padding:4px 10px;border-radius:20px;font-weight:600;">Bulan: <?= $namaBulan[intval($_GET['bulan'])] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($_GET['jenis'])): ?>
                                        <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;font-weight:600;">Jenis: <?= ucwords(htmlspecialchars($_GET['jenis'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($_GET['teknisi'])): ?>
                                        <?php $selTeknisi = $conn->query("SELECT nama FROM teknisi WHERE id = " . intval($_GET['teknisi']))->fetch_assoc(); ?>
                                        <span style="font-size:11px;background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:20px;font-weight:600;">Teknisi: <?= htmlspecialchars($selTeknisi['nama'] ?? '') ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($_GET['status_pelaksanaan'])): ?>
                                        <span style="font-size:11px;background:#fce7f3;color:#9d174d;padding:4px 10px;border-radius:20px;font-weight:600;">Pelaksanaan: <?= $_GET['status_pelaksanaan'] == 'lengkap' ? 'Lengkap' : 'Tidak Lengkap' ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($_GET['cari'])): ?>
                                        <span style="font-size:11px;background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:20px;font-weight:600;">Cari: "<?= htmlspecialchars($_GET['cari']) ?>"</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <script>
                            function toggleFilters() {
                                var panel = document.getElementById('filterPanel');
                                panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
                            }
                            </script>
                        </div>

                        <!-- Table -->
                        <div class="lk-table-wrap">
                            <table class="lk-table">
                                <thead>
                                    <tr>
                                        <th style="padding-left:24px; width:30%;">Customer</th>
                                        <th style="width:38%;">Teknisi & Absensi</th>
                                        <th style="width:32%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php
                                        $search = $_GET['cari'] ?? '';
                                        $filterTahun = $_GET['tahun'] ?? '';
                                        $filterBulan = $_GET['bulan'] ?? '';
                                        $filterJenis = $_GET['jenis'] ?? '';
                                        $filterTeknisi = $_GET['teknisi'] ?? '';
                                        $filterPelaksanaan = $_GET['status_pelaksanaan'] ?? '';
                                        
                                        $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.catatan_admin, k.kegiatan, k.created_at, k.status AS status_kegiatan, c.id AS id_cust, c.nama AS nama_cust
                                                     FROM kegiatan k
                                                     INNER JOIN (SELECT kode, MAX(id) AS max_id FROM kegiatan WHERE deleted_at IS NULL GROUP BY kode) latest ON k.id = latest.max_id
                                                     LEFT JOIN customer c ON k.customer_id = c.id
                                                     WHERE k.status != 'waiting' AND (k.paid IS NULL OR k.paid = '')
                                                     AND k.deleted_at IS NULL
                                                     AND EXISTS (
                                                         SELECT 1 FROM pelaksanaan_kegiatan px
                                                         WHERE px.kode = k.kode AND px.deleted_at IS NULL
                                                         AND px.status NOT IN ('Lanjut Nanti', 'Lanjutan', 'berjalan', 'dijadwalkan')
                                                     )";

                                        $bindTypes = '';
                                        $bindValues = [];

                                        if (!empty($filterTahun)) {
                                            $sql_main .= " AND YEAR(k.created_at) = ?";
                                            $bindTypes .= 'i';
                                            $bindValues[] = intval($filterTahun);
                                        }
                                        if (!empty($filterBulan)) {
                                            $sql_main .= " AND MONTH(k.created_at) = ?";
                                            $bindTypes .= 'i';
                                            $bindValues[] = intval($filterBulan);
                                        }

                                        // Filter: Jenis Kegiatan
                                        if (!empty($filterJenis)) {
                                            $sql_main .= " AND k.kegiatan = ?";
                                            $bindTypes .= 's';
                                            $bindValues[] = $filterJenis;
                                        }

                                        // Filter: Teknisi
                                        if (!empty($filterTeknisi)) {
                                            $sql_main .= " AND EXISTS (SELECT 1 FROM team_kegiatan tk WHERE tk.kegiatan_id = k.id AND tk.teknisi_id = ?)";
                                            $bindTypes .= 'i';
                                            $bindValues[] = intval($filterTeknisi);
                                        }

                                        // Filter: Status Pelaksanaan (lengkap / tidak lengkap)
                                        if ($filterPelaksanaan === 'lengkap') {
                                            $sql_main .= " AND NOT EXISTS (
                                                SELECT 1 FROM pelaksanaan_kegiatan px2
                                                WHERE px2.kode = k.kode AND px2.deleted_at IS NULL
                                                AND (px2.waktu_mulai IS NULL OR px2.waktu_selesai IS NULL)
                                            )";
                                        } elseif ($filterPelaksanaan === 'tidak_lengkap') {
                                            $sql_main .= " AND EXISTS (
                                                SELECT 1 FROM pelaksanaan_kegiatan px2
                                                WHERE px2.kode = k.kode AND px2.deleted_at IS NULL
                                                AND (px2.waktu_mulai IS NULL OR px2.waktu_selesai IS NULL)
                                            )";
                                        }

                                        if (!empty($search)) {
                                            $sql_main .= " AND (c.nama LIKE ? OR k.kode LIKE ? OR k.kegiatan LIKE ? OR k.keterangan LIKE ?)";
                                            $bindTypes .= 'ssss';
                                            $searchParam = "%$search%";
                                            $bindValues[] = $searchParam;
                                            $bindValues[] = $searchParam;
                                            $bindValues[] = $searchParam;
                                            $bindValues[] = $searchParam;
                                        }

                                        $sql_main .= " ORDER BY k.created_at DESC";


                                        $stmt_main = $conn->prepare($sql_main);

                                        if (!empty($bindTypes)) {
                                            $stmt_main->bind_param($bindTypes, ...$bindValues);
                                        }

                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();
                                        
                                        // Collect all rows first
                                        $allRows = [];
                                        while ($row_main = $result_main->fetch_assoc()) {
                                            $allRows[] = $row_main;
                                        }
                                        $stmt_main->close();

                                        // ═══ BATCH LOAD ALL TEKNISI + ABSENSI IN 1 QUERY ═══
                                        $teknisiByKode = [];
                                        if (!empty($allRows)) {
                                            $allKodes = array_unique(array_column($allRows, 'kode_transaksi'));
                                            $placeholders = implode(',', array_fill(0, count($allKodes), '?'));
                                            $typesStr = str_repeat('s', count($allKodes));
                                            
                                            $sqlBatch = "SELECT p.kode, t.nama_teknisi,
                                                         MIN(p.waktu_mulai) AS waktu_mulai_pertama,
                                                         MAX(p.waktu_selesai) AS waktu_selesai_terakhir
                                                         FROM pelaksanaan_kegiatan p
                                                         JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id AND t.kode = p.kode
                                                         WHERE p.kode IN ($placeholders) AND p.deleted_at IS NULL
                                                         GROUP BY p.kode, p.teknisi_id";
                                            
                                            $stmtBatch = $conn->prepare($sqlBatch);
                                            if ($stmtBatch) {
                                                $kodeArr = array_values($allKodes);
                                                $stmtBatch->bind_param($typesStr, ...$kodeArr);
                                                $stmtBatch->execute();
                                                $resBatch = $stmtBatch->get_result();
                                                while ($r = $resBatch->fetch_assoc()) {
                                                    $teknisiByKode[$r['kode']][] = $r;
                                                }
                                                $stmtBatch->close();
                                            }
                                        }

                                        // Recompute summary after query
                                        $totalItems = count($allRows);
                                        $todayCount = 0;
                                        $todayStr = date('Y-m-d');
                                        foreach ($allRows as $r) {
                                            if (date('Y-m-d', strtotime($r['created_at'])) === $todayStr) $todayCount++;
                                        }

                                        if (!empty($allRows)) {
                                            foreach ($allRows as $row_main) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $idC = $row_main['id_cust'];
                                                $jenisLower = strtolower($row_main['kegiatan']);
                                                $jenisClass = 'jenis-default';
                                                if (strpos($jenisLower, 'survey') !== false) $jenisClass = 'jenis-survey';
                                                elseif (strpos($jenisLower, 'service') !== false) $jenisClass = 'jenis-service';
                                                elseif (strpos($jenisLower, 'pasang') !== false || strpos($jenisLower, 'install') !== false) $jenisClass = 'jenis-pasang';
                                        ?>
                                                <tr>
                                                    <!-- Customer Column -->
                                                    <td style="padding-left:24px;">
                                                        <div class="lk-cust-badges">
                                                            <span class="lk-badge-jenis <?= $jenisClass ?>"><?= strtoupper(htmlspecialchars($row_main['kegiatan'])); ?></span>
                                                            <span class="lk-badge-kode"><?= htmlspecialchars($kodeTransaksi); ?></span>
                                                        </div>
                                                        <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank" class="lk-cust-name">
                                                            <?= htmlspecialchars($row_main['nama_cust']); ?>
                                                        </a>
                                                        <div class="lk-cust-desc">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</div>
                                                        <div class="lk-cust-date">
                                                            <i class="material-icons" style="font-size:12px;">schedule</i>
                                                            <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?>
                                                        </div>
                                                    </td>

                                                    <!-- Teknisi Column -->
                                                    <td>
                                                        <div class="lk-tek-list">
                                                        <?php
                                                        $tekList = $teknisiByKode[$kodeTransaksi] ?? [];
                                                        $allComplete = true;
                                                        if (!empty($tekList)) {
                                                            foreach ($tekList as $row_teknisi) {
                                                                $initials = strtoupper(substr($row_teknisi['nama_teknisi'], 0, 1));
                                                                $hasStart = !empty($row_teknisi['waktu_mulai_pertama']);
                                                                $hasEnd = !empty($row_teknisi['waktu_selesai_terakhir']);
                                                                if (!$hasStart || !$hasEnd) $allComplete = false;
                                                        ?>
                                                            <div class="lk-tek-item">
                                                                <div class="lk-tek-avatar"><?= $initials ?></div>
                                                                <span class="lk-tek-name"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></span>
                                                                <div class="lk-tek-time">
                                                                    <span class="t-start">
                                                                        <i class="material-icons">play_arrow</i>
                                                                        <?= $hasStart ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?>
                                                                    </span>
                                                                    <span class="t-end">
                                                                        <i class="material-icons">stop</i>
                                                                        <?= $hasEnd ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            }
                                                            // Status pill
                                                            if ($allComplete) {
                                                                echo '<div class="lk-status-pill st-ok"><i class="material-icons" style="font-size:12px;">check_circle</i> Pelaksanaan Lengkap</div>';
                                                            } else {
                                                                echo '<div class="lk-status-pill st-warn"><i class="material-icons" style="font-size:12px;">warning</i> Pelaksanaan Belum Lengkap</div>';
                                                            }
                                                        } else {
                                                            echo '<div class="lk-tek-empty"><i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">person_off</i>Data teknisi tidak ditemukan</div>';
                                                        }
                                                        ?>
                                                        </div>
                                                    </td>

                                                    <!-- Aksi Column -->
                                                    <td>
                                                        <div class="lk-actions">
                                                            <div class="lk-act-row">
                                                                <button class="lk-act-btn act-invoice detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?= $kodeTransaksi; ?>">
                                                                    <i class="material-icons">receipt</i> Input Invoice
                                                                </button>
                                                                <a href="proses_set_no_invoice.php?kode=<?= $kodeTransaksi; ?>" class="lk-act-btn act-nopay" onclick="return confirm('Tandai kegiatan ini Tidak memiliki Payment?')">
                                                                    <i class="material-icons">money_off</i> No Payment
                                                                </a>
                                                            </div>
                                                            <div class="lk-act-row">
                                                                <a href="proses_set_tidak_valid.php?kode=<?= $kodeTransaksi; ?>" class="lk-act-btn act-invalid" onclick="return confirm('Tandai kegiatan ini sebagai Tidak Valid?')">
                                                                    <i class="material-icons">block</i> Tidak Valid
                                                                </a>
                                                                <button class="lk-act-btn act-note catatanBtn" data-kode="<?= $kodeTransaksi; ?>" data-catatan="<?= htmlspecialchars($row_main['catatan_admin'] ?? '', ENT_QUOTES); ?>">
                                                                    <i class="material-icons">edit_note</i> Catatan
                                                                </button>
                                                            </div>
                                                            <?php if (!empty($row_main['catatan_admin'])): ?>
                                                            <div class="lk-catatan-box">
                                                                📝 <?= htmlspecialchars($row_main['catatan_admin']); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                            <tr>
                                                <td colspan="3">
                                                    <div class="lk-empty">
                                                        <div class="lk-empty-icon"><i class="material-icons" style="font-size:48px;">inbox</i></div>
                                                        <div class="lk-empty-text">Tidak ada data laporan yang ditemukan</div>
                                                        <div class="lk-empty-sub">Coba ubah filter atau kata pencarian</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
        
        <?php
// --- Blok Logika untuk Modal Pengumuman ---
$show_modal = false;
// Set zona waktu ke Jakarta
$timezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTime('now', $timezone);
// Modal akan berhenti muncul pada tanggal 5 Agustus 2025
$expiry_date = new DateTime('2025-08-05', $timezone);

// Hanya aktifkan modal jika hari ini sebelum tanggal kedaluwarsa
if ($today < $expiry_date) {
    $show_modal = true;
}

// Jika flag $show_modal aktif, maka render HTML dan JavaScript untuk modal
if ($show_modal) :
?>

<div class="modal fade" id="infoUpdateModal" tabindex="-1" aria-labelledby="infoUpdateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title text-white" id="infoUpdateModalLabel"><i class="material-icons opacity-10 me-2">campaign</i>Psstt... Ada yang Baru, lho!</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fs-6">
                <p class="text-center mb-3">✨✨✨</p>
                <p>Ehem! Tampilan halaman ini sekarang lebih <em>user friendly</em>, tapi tenang... cara kerjanya <strong>99% masih sama kok.</strong></p>
                <p>Yuk, budayakan <strong>kepo dan klik-klik mandiri</strong> dulu. Siapa tahu nemu harta karun! 😉</p>
                <hr>
                <p class="text-muted small">Kalau sudah keluar asap dari kepala, baru deh lambaikan tangan ke developer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Oke, Saya Mengerti!</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fungsi untuk mendapatkan tanggal hari ini dalam format YYYY-MM-DD
    const getTodayDate = () => {
        const d = new Date();
        // Menyesuaikan dengan zona waktu lokal untuk akurasi
        const offset = d.getTimezoneOffset();
        const adjustedDate = new Date(d.getTime() - (offset*60*1000));
        return adjustedDate.toISOString().split('T')[0];
    }
    
    const today = getTodayDate();
    const lastShown = localStorage.getItem('updateModalLastShown');

    // Jika modal belum pernah ditampilkan atau terakhir ditampilkan bukan hari ini
    if (lastShown !== today) {
        var myModal = new bootstrap.Modal(document.getElementById('infoUpdateModal'));
        myModal.show();

        // Saat modal ditutup, simpan tanggal hari ini ke localStorage
        document.getElementById('infoUpdateModal').addEventListener('hidden.bs.modal', function () {
            localStorage.setItem('updateModalLastShown', today);
        });
    }
});
</script>

<?php
endif; // Akhir dari blok if ($show_modal)
// --- Akhir Blok Logika Modal ---
?>
    </main>

    <div class="modal fade" style="z-index:99999;" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Rincian & Input Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dataDetailTek">
                    <div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Catatan -->
    <div class="modal fade" id="catatanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 8px 32px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:14px 14px 0 0;padding:16px 20px;">
                    <h5 class="modal-title" style="color:#fff;font-size:15px;font-weight:700;"><i class="material-icons" style="font-size:18px;vertical-align:middle;margin-right:6px;">edit_note</i>Tambah / Edit Catatan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" id="catatan_kode">
                    <label style="font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;display:block;">Catatan Admin</label>
                    <textarea id="catatan_text" rows="4" style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:12px 14px;font-size:13px;color:#1e293b;background:#f8fafc;transition:all 0.2s;font-family:inherit;resize:vertical;" placeholder="Tulis catatan untuk kegiatan ini..."></textarea>
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button type="button" class="ep-btn-cancel" data-bs-dismiss="modal" style="flex:1;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">Batal</button>
                        <button type="button" id="btnSimpanCatatan" style="flex:2;padding:10px;border:none;border-radius:10px;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(37,99,235,0.25);">Simpan Catatan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
    $(document).ready(function() {
        // Invoice detail modal
        $('.detailBtn').click(function() {
            var kode_transaksi = $(this).data('kode');
            var modalBody = $('#dataDetailTek');
            
            modalBody.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            $.ajax({
                url: 'get-detail-pekerjaan-new.php',
                type: 'POST',
                data: { kode_transaksi: kode_transaksi },
                success: function(response) {
                    modalBody.html(response);
                },
                error: function(xhr, status, error) {
                    modalBody.html('<div class="alert alert-danger text-white">Gagal memuat detail pekerjaan. Silakan coba lagi.</div>');
                    console.error("AJAX Error: " + status + " - " + error);
                }
            });
        });

        // Catatan modal
        $('.catatanBtn').click(function() {
            var kode = $(this).data('kode');
            var catatan = $(this).data('catatan');
            $('#catatan_kode').val(kode);
            $('#catatan_text').val(catatan);
            var modal = new bootstrap.Modal(document.getElementById('catatanModal'));
            modal.show();
        });

        // Simpan catatan
        $('#btnSimpanCatatan').click(function() {
            var btn = $(this);
            var kode = $('#catatan_kode').val();
            var catatan = $('#catatan_text').val();
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: 'proses_update_catatan.php',
                type: 'POST',
                data: { kode: kode, keterangan: catatan },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert('Catatan berhasil disimpan!');
                        location.reload();
                    } else {
                        alert('Gagal: ' + (res.message || 'Terjadi kesalahan'));
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan koneksi.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Simpan Catatan');
                }
            });
        });
    });
    </script>
</body>
</html>