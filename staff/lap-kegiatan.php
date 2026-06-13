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
        /* ═══ PREMIUM HORIZONTAL CARD DESIGN ═══ */
        .lk-summary { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .lk-summary-item { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 14px; flex: 1; min-width: 160px; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .lk-summary-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .lk-summary-icon.si-total { background: linear-gradient(135deg, #6366f1, #818cf8); }
        .lk-summary-icon.si-today { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .lk-summary-icon i { color: #fff; font-size: 16px; }
        .lk-summary-num { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .lk-summary-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }
        .search-box { border-radius: 8px; border: 1px solid #e2e8f0; padding: 8px 12px; font-size: 13px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
        .search-box:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); outline: none; }
        .btn-search { background: #1e293b; border: none; border-radius: 8px; padding: 8px 14px; }
        .btn-search:hover { background: #334155; }

        /* Horizontal Scroll */
        .lk-scroll-wrap { position: relative; margin: 0 -4px; }
        .lk-scroll-container {
            display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch; padding: 8px 8px 20px 8px;
            scrollbar-width: none; cursor: grab; scroll-behavior: smooth;
        }
        .lk-scroll-container::-webkit-scrollbar { display: none; }
        .lk-scroll-container.dragging { cursor: grabbing; scroll-snap-type: none; scroll-behavior: auto; }
        .lk-scroll-container.dragging .lk-item * { pointer-events: none; }
        .lk-scroll-nav {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 36px; height: 36px; border-radius: 50%; background: #fff; border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 5; transition: all 0.2s; color: #475569;
        }
        .lk-scroll-nav:hover { background: #6366f1; color: #fff; border-color: #6366f1; box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
        .lk-scroll-nav.nav-left { left: -6px; }
        .lk-scroll-nav.nav-right { right: -6px; }
        .lk-scroll-dots { display: flex; justify-content: center; gap: 6px; margin-top: 4px; }
        .lk-scroll-dot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
        .lk-scroll-dot.active { background: #6366f1; width: 20px; border-radius: 10px; }

        /* Card Item */
        .lk-item {
            min-width: 340px; max-width: 380px; flex-shrink: 0; scroll-snap-align: start;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; overflow: hidden;
        }
        .lk-item:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .lk-item-accent { height: 4px; width: 100%; }
        .lk-item-accent.acc-survey { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .lk-item-accent.acc-service { background: linear-gradient(90deg, #6366f1, #818cf8); }
        .lk-item-accent.acc-pasang { background: linear-gradient(90deg, #10b981, #34d399); }
        .lk-item-accent.acc-default { background: linear-gradient(90deg, #94a3b8, #cbd5e1); }
        .lk-item-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .lk-badges { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
        .lk-badge-jenis { font-size: 9px; padding: 3px 10px; border-radius: 20px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
        .lk-badge-jenis.jenis-survey { background: #fef3c7; color: #92400e; }
        .lk-badge-jenis.jenis-service { background: #e0e7ff; color: #3730a3; }
        .lk-badge-jenis.jenis-pasang { background: #d1fae5; color: #065f46; }
        .lk-badge-jenis.jenis-default { background: #f1f5f9; color: #475569; }
        .lk-badge-kode { font-size: 10px; color: #818cf8; font-family: 'SF Mono','Consolas',monospace; font-weight: 700; background: #eef2ff; padding: 2px 8px; border-radius: 4px; }
        .lk-cust-name { font-size: 15px; font-weight: 700; color: #1e293b; text-decoration: none; display: block; margin-bottom: 4px; transition: color 0.15s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lk-cust-name:hover { color: #6366f1; }
        .lk-cust-desc { font-size: 11px; color: #94a3b8; font-style: italic; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .lk-cust-date { font-size: 10px; color: #cbd5e1; display: flex; align-items: center; gap: 4px; }
        .lk-divider { height: 1px; background: #f1f5f9; margin: 12px 0; }
        .lk-section-label { font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .lk-tek-list { display: flex; flex-direction: column; gap: 6px; }
        .lk-tek-item { display: flex; align-items: center; gap: 10px; padding: 6px 8px; border-radius: 8px; transition: background 0.15s; }
        .lk-tek-item:hover { background: #f8fafc; }
        .lk-tek-avatar { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #a78bfa); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; color: #fff; flex-shrink: 0; }
        .lk-tek-info { flex: 1; min-width: 0; }
        .lk-tek-name { font-size: 12px; font-weight: 600; color: #1e293b; }
        .lk-tek-time { display: flex; gap: 8px; font-size: 10px; color: #64748b; margin-top: 2px; }
        .lk-tek-time .t-start { color: #16a34a; font-weight: 600; }
        .lk-tek-time .t-end { color: #dc2626; font-weight: 600; }
        .lk-tek-time i { font-size: 10px; vertical-align: middle; margin-right: 1px; }
        .lk-tek-empty { font-size: 11px; color: #cbd5e1; padding: 6px 8px; }
        .lk-status-pill { font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px; }
        .lk-status-pill.st-ok { background: #d1fae5; color: #065f46; }
        .lk-status-pill.st-warn { background: #fef3c7; color: #92400e; }
        .lk-item-footer { padding: 12px 16px; background: #fafbfc; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px; }
        .lk-act-row { display: flex; gap: 6px; }
        .lk-act-btn { font-size: 10px; padding: 6px 10px; border-radius: 8px; font-weight: 700; border: 1.5px solid transparent; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; flex: 1; justify-content: center; }
        .lk-act-btn i { font-size: 13px; }
        .lk-act-btn.act-invoice { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .lk-act-btn.act-invoice:hover { background: #16a34a; color: #fff; border-color: #16a34a; box-shadow: 0 4px 12px rgba(22,163,74,0.2); }
        .lk-act-btn.act-nopay { background: #fef3c7; color: #d97706; border-color: #fde68a; }
        .lk-act-btn.act-nopay:hover { background: #d97706; color: #fff; border-color: #d97706; }
        .lk-act-btn.act-invalid { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .lk-act-btn.act-invalid:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        .lk-act-btn.act-note { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .lk-act-btn.act-note:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
        .lk-catatan-box { margin: 0 16px 12px; padding: 8px 12px; background: #f8fafc; border-left: 3px solid #6366f1; border-radius: 0 8px 8px 0; font-size: 10px; color: #475569; font-style: italic; }
        .lk-empty { text-align: center; padding: 60px 20px; }
        .lk-empty-icon { color: #e2e8f0; margin-bottom: 16px; }
        .lk-empty-text { font-size: 14px; color: #94a3b8; font-weight: 600; }
        .lk-empty-sub { font-size: 12px; color: #cbd5e1; margin-top: 4px; }
        .modal-xl { max-width: 80%; }
        @media (max-width: 992px) { .lk-summary { flex-direction: column; } .lk-item { min-width: 300px; } }
        @media (max-width: 767px) { .modal-xl { max-width: 95%; } .lk-item { min-width: 280px; max-width: 320px; } .lk-scroll-nav { display: none; } }
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

                        <!-- Horizontal Card Scroll -->
                        <div class="lk-scroll-wrap" id="scrollWrap">
                            <div class="lk-scroll-nav nav-left" id="scrollLeft" onclick="scrollCards(-1)">
                                <i class="material-icons">chevron_left</i>
                            </div>
                            <div class="lk-scroll-nav nav-right" id="scrollRight" onclick="scrollCards(1)">
                                <i class="material-icons">chevron_right</i>
                            </div>
                            <div class="lk-scroll-container" id="scrollContainer">
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
                                                     AND NOT EXISTS (
                                                         SELECT 1 FROM pelaksanaan_kegiatan px
                                                         WHERE px.kode = k.kode AND px.deleted_at IS NULL
                                                         AND px.status IN ('Lanjut Nanti', 'Lanjutan', 'berjalan', 'dijadwalkan')
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
                                            ) AND EXISTS (SELECT 1 FROM pelaksanaan_kegiatan px3 WHERE px3.kode = k.kode AND px3.deleted_at IS NULL)";
                                        } elseif ($filterPelaksanaan === 'tidak_lengkap') {
                                            $sql_main .= " AND EXISTS (
                                                SELECT 1 FROM pelaksanaan_kegiatan px2
                                                WHERE px2.kode = k.kode AND px2.deleted_at IS NULL
                                                AND (px2.waktu_mulai IS NULL OR px2.waktu_selesai IS NULL)
                                            )";
                                        }

                                        // Filter: Search
                                        if (!empty($search)) {
                                            $sql_main .= " AND (c.nama LIKE ? OR k.kode LIKE ? OR k.keterangan LIKE ?)";
                                            $bindTypes .= 'sss';
                                            $searchParam = "%$search%";
                                            $bindValues[] = $searchParam;
                                            $bindValues[] = $searchParam;
                                            $bindValues[] = $searchParam;
                                        }

                                        $sql_main .= " ORDER BY k.created_at DESC";

                                        // Get teknisi data
                                        $sqlTek = "SELECT pk.kode, t.nama AS nama_teknisi, MIN(pk.waktu_mulai) AS waktu_mulai_pertama, MAX(pk.waktu_selesai) AS waktu_selesai_terakhir
                                                   FROM pelaksanaan_kegiatan pk
                                                   JOIN teknisi t ON pk.teknisi_id = t.id
                                                   WHERE pk.deleted_at IS NULL
                                                   GROUP BY pk.kode, pk.teknisi_id
                                                   ORDER BY pk.kode, t.nama ASC";
                                        $resTek = mysqli_query($conn, $sqlTek);
                                        $teknisiByKode = [];
                                        while ($rowT = mysqli_fetch_assoc($resTek)) {
                                            $teknisiByKode[$rowT['kode']][] = $rowT;
                                        }

                                        $stmtMain = mysqli_prepare($conn, $sql_main);
                                        if (!empty($bindTypes)) {
                                            mysqli_stmt_bind_param($stmtMain, $bindTypes, ...$bindValues);
                                        }
                                        mysqli_stmt_execute($stmtMain);
                                        $result_main = mysqli_stmt_get_result($stmtMain);
                                        $rowCount = 0;

                                        if ($result_main && mysqli_num_rows($result_main) > 0) {
                                            while ($row_main = mysqli_fetch_assoc($result_main)) {
                                                $rowCount++;
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $jenisLower = strtolower($row_main['kegiatan']);
                                                $jenisClass = 'jenis-default';
                                                $accentClass = 'acc-default';
                                                if (strpos($jenisLower, 'survey') !== false) { $jenisClass = 'jenis-survey'; $accentClass = 'acc-survey'; }
                                                elseif (strpos($jenisLower, 'service') !== false) { $jenisClass = 'jenis-service'; $accentClass = 'acc-service'; }
                                                elseif (strpos($jenisLower, 'pasang') !== false || strpos($jenisLower, 'install') !== false) { $jenisClass = 'jenis-pasang'; $accentClass = 'acc-pasang'; }
                                        ?>
                                                <!-- Card Item -->
                                                <div class="lk-item">
                                                    <div class="lk-item-accent <?= $accentClass ?>"></div>
                                                    <div class="lk-item-body">
                                                        <!-- Badges -->
                                                        <div class="lk-badges">
                                                            <span class="lk-badge-jenis <?= $jenisClass ?>"><?= strtoupper(htmlspecialchars($row_main['kegiatan'])); ?></span>
                                                            <span class="lk-badge-kode"><?= htmlspecialchars($kodeTransaksi); ?></span>
                                                        </div>
                                                        <!-- Customer -->
                                                        <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank" class="lk-cust-name" title="<?= htmlspecialchars($row_main['nama_cust']); ?>">
                                                            <?= htmlspecialchars($row_main['nama_cust']); ?>
                                                        </a>
                                                        <div class="lk-cust-desc">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</div>
                                                        <div class="lk-cust-date">
                                                            <i class="material-icons" style="font-size:11px;">schedule</i>
                                                            <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?>
                                                        </div>

                                                        <div class="lk-divider"></div>

                                                        <!-- Teknisi -->
                                                        <div class="lk-section-label">Teknisi & Absensi</div>
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
                                                                <div class="lk-tek-info">
                                                                    <div class="lk-tek-name"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></div>
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
                                                            </div>
                                                        <?php
                                                            }
                                                            if ($allComplete) {
                                                                echo '<div class="lk-status-pill st-ok"><i class="material-icons" style="font-size:12px;">check_circle</i> Pelaksanaan Lengkap</div>';
                                                            } else {
                                                                echo '<div class="lk-status-pill st-warn"><i class="material-icons" style="font-size:12px;">warning</i> Belum Lengkap</div>';
                                                            }
                                                        } else {
                                                            echo '<div class="lk-tek-empty"><i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;">person_off</i>Belum ada teknisi</div>';
                                                        }
                                                        ?>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($row_main['catatan_admin'])): ?>
                                                    <div class="lk-catatan-box">📝 <?= htmlspecialchars($row_main['catatan_admin']); ?></div>
                                                    <?php endif; ?>

                                                    <!-- Actions Footer -->
                                                    <div class="lk-item-footer">
                                                        <div class="lk-act-row">
                                                            <button class="lk-act-btn act-invoice detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?= $kodeTransaksi; ?>">
                                                                <i class="material-icons">receipt</i> Invoice
                                                            </button>
                                                            <a href="proses_set_no_invoice.php?kode=<?= $kodeTransaksi; ?>" class="lk-act-btn act-nopay" onclick="return confirm('Tandai kegiatan ini Tidak memiliki Payment?')">
                                                                <i class="material-icons">money_off</i> No Pay
                                                            </a>
                                                        </div>
                                                        <div class="lk-act-row">
                                                            <a href="proses_set_tidak_valid.php?kode=<?= $kodeTransaksi; ?>" class="lk-act-btn act-invalid" onclick="return confirm('Tandai kegiatan ini sebagai Tidak Valid?')">
                                                                <i class="material-icons">block</i> Invalid
                                                            </a>
                                                            <button class="lk-act-btn act-note catatanBtn" data-kode="<?= $kodeTransaksi; ?>" data-catatan="<?= htmlspecialchars($row_main['catatan_admin'] ?? '', ENT_QUOTES); ?>">
                                                                <i class="material-icons">edit_note</i> Catatan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                            <div style="flex:1;min-width:100%;">
                                                <div class="lk-empty">
                                                    <div class="lk-empty-icon"><i class="material-icons" style="font-size:48px;">inbox</i></div>
                                                    <div class="lk-empty-text">Tidak ada data laporan yang ditemukan</div>
                                                    <div class="lk-empty-sub">Coba ubah filter atau kata pencarian</div>
                                                </div>
                                            </div>
                                        <?php } ?>

                            </div><!-- end scroll-container -->
                            <div class="lk-scroll-dots" id="scrollDots"></div>
                        </div><!-- end scroll-wrap -->
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
    // ═══ DRAG-TO-SCROLL & NAVIGATION ═══
    (function() {
        var container = document.getElementById('scrollContainer');
        if (!container) return;

        // Drag to scroll
        var isDown = false, startX, scrollLeft;
        container.addEventListener('mousedown', function(e) {
            if (e.target.closest('.lk-act-btn, a, button')) return;
            isDown = true;
            container.classList.add('dragging');
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });
        container.addEventListener('mouseleave', function() { isDown = false; container.classList.remove('dragging'); });
        container.addEventListener('mouseup', function() { isDown = false; container.classList.remove('dragging'); });
        container.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - container.offsetLeft;
            var walk = (x - startX) * 1.5;
            container.scrollLeft = scrollLeft - walk;
        });

        // Update dots
        function updateDots() {
            var dotsEl = document.getElementById('scrollDots');
            if (!dotsEl) return;
            var cards = container.querySelectorAll('.lk-item');
            var total = Math.ceil(cards.length / 3) || 1;
            var current = Math.round(container.scrollLeft / (container.scrollWidth - container.clientWidth) * (total - 1));
            var html = '';
            for (var i = 0; i < total; i++) {
                html += '<div class="lk-scroll-dot' + (i === current ? ' active' : '') + '"></div>';
            }
            dotsEl.innerHTML = html;
        }
        container.addEventListener('scroll', updateDots);
        updateDots();

        // Arrow navigation
        window.scrollCards = function(dir) {
            var cardW = 356;
            container.scrollBy({ left: dir * cardW, behavior: 'smooth' });
        };
    })();
    </script>

    <script>
    window.addEventListener('load', function() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery not loaded!');
            return;
        }

        // Invoice detail modal
        $(document).on('click', '.detailBtn', function() {
            var kode_transaksi = $(this).data('kode');
            var modalBody = $('#dataDetailTek');
            
            modalBody.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            $.ajax({
                url: 'get-detail-pekerjaan-new.php',
                type: 'POST',
                data: { kode_transaksi: kode_transaksi },
                timeout: 15000,
                success: function(response) {
                    modalBody.html(response);
                },
                error: function(xhr, status, error) {
                    var msg = 'Gagal memuat detail pekerjaan.';
                    if (status === 'timeout') msg = 'Request timeout - coba lagi.';
                    else if (xhr.responseText) msg += '<br><small>' + xhr.responseText.substring(0, 200) + '</small>';
                    modalBody.html('<div class="alert alert-danger">' + msg + '</div>');
                    console.error("AJAX Error:", status, error, xhr.responseText);
                }
            });
        });

        // Catatan modal
        $(document).on('click', '.catatanBtn', function() {
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