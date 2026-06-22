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
$nama_bulan = $daftar_bulan[(int)$bulan];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kegiatan Bulanan - <?= $nama_bulan . ' ' . $tahun ?></title>
    <meta name="description" content="Laporan Kegiatan Lengkap periode <?= $nama_bulan . ' ' . $tahun ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --white: #ffffff;
            --bg: #f1f5f9;
            --surface: #ffffff;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --text-dark: #0f172a;
            --text-body: #334155;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --blue-50: #eff6ff; --blue-500: #3b82f6; --blue-600: #2563eb; --blue-700: #1d4ed8;
            --emerald-50: #ecfdf5; --emerald-500: #10b981; --emerald-600: #059669; --emerald-700: #047857;
            --amber-50: #fffbeb; --amber-500: #f59e0b; --amber-600: #d97706;
            --rose-50: #fff1f2; --rose-500: #f43f5e; --rose-600: #e11d48;
            --violet-50: #f5f3ff; --violet-500: #8b5cf6; --violet-600: #7c3aed;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --radius: 12px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.04);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text-body);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .page-wrap {
            max-width: 1360px;
            margin: 0 auto;
            padding: 24px 24px 56px;
        }

        /* ═══════════ HEADER ═══════════ */
        .page-header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px 28px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-xs);
        }
        .page-header-left {
            display: flex; align-items: center; gap: 14px;
        }
        .header-icon {
            width: 44px; height: 44px;
            background: var(--blue-50);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            color: var(--blue-600);
        }
        .header-icon .material-icons-round { font-size: 24px; }
        .page-header h1 {
            font-size: 18px; font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            margin-bottom: 2px;
        }
        .period-tag {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; font-weight: 600;
            color: var(--blue-600);
            background: var(--blue-50);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .period-tag .material-icons-round { font-size: 14px; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* ═══════════ BUTTONS ═══════════ */
        .btn-act {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: var(--font);
            font-size: 12.5px; font-weight: 600;
            padding: 8px 16px;
            border: none; border-radius: var(--radius-sm);
            cursor: pointer; text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-act .material-icons-round { font-size: 16px; }
        .btn-act-back {
            background: var(--surface); color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-act-back:hover { background: var(--bg); color: var(--text-body); }
        .btn-act-print {
            background: var(--blue-600); color: #fff;
            box-shadow: 0 1px 3px rgba(37,99,235,0.3);
        }
        .btn-act-print:hover { background: var(--blue-700); color: #fff; }
        .btn-act-excel {
            background: var(--emerald-600); color: #fff;
            box-shadow: 0 1px 3px rgba(5,150,105,0.3);
        }
        .btn-act-excel:hover { background: var(--emerald-700); color: #fff; }

        /* ═══════════ STATS ═══════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            box-shadow: var(--shadow-xs);
            display: flex; align-items: center; gap: 14px;
        }
        .stat-dot {
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-dot .material-icons-round { font-size: 20px; }
        .stat-dot-blue { background: var(--blue-50); color: var(--blue-600); }
        .stat-dot-emerald { background: var(--emerald-50); color: var(--emerald-600); }
        .stat-dot-rose { background: var(--rose-50); color: var(--rose-600); }
        .stat-dot-amber { background: var(--amber-50); color: var(--amber-600); }
        .stat-title {
            font-size: 11px; font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .stat-num {
            font-size: 22px; font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        /* ═══════════ TABLE HEADER ═══════════ */
        .col-labels {
            display: grid;
            grid-template-columns: 42px 1.2fr 1fr 1.3fr;
            gap: 12px;
            padding: 0 20px 10px;
        }
        .col-labels span {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        /* ═══════════ DATA CARDS ═══════════ */
        .data-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 10px;
            box-shadow: var(--shadow-xs);
            transition: box-shadow 0.2s, border-color 0.2s;
            overflow: hidden;
        }
        .data-card:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }
        .data-card-grid {
            display: grid;
            grid-template-columns: 42px 1.2fr 1fr 1.3fr;
            gap: 12px;
            padding: 20px;
            align-items: start;
        }

        /* left accent line */
        .data-card.is-lunas { border-left: 3px solid var(--emerald-500); }
        .data-card.is-unpaid { border-left: 3px solid var(--rose-500); }
        .data-card.is-manual { border-left: 3px solid var(--amber-500); }

        /* Row Number */
        .rnum {
            width: 30px; height: 30px;
            border-radius: var(--radius-xs);
            background: var(--bg);
            color: var(--text-muted);
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── Customer Section ── */
        .c-name {
            font-size: 14px; font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        .c-ket {
            font-size: 12px; color: var(--text-secondary);
            background: var(--bg);
            border-radius: var(--radius-xs);
            padding: 6px 10px;
            margin-bottom: 8px;
            font-style: italic;
            line-height: 1.5;
            border-left: 3px solid var(--border);
        }
        .c-tags {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .c-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; color: var(--text-muted); font-weight: 500;
        }
        .c-tag .material-icons-round { font-size: 13px; }
        .c-tag strong { color: var(--text-secondary); font-weight: 600; }

        /* ── Invoice Section ── */
        .inv-card {
            background: var(--bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
        }
        .inv-lbl {
            font-size: 9px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 3px;
        }
        .inv-no {
            font-size: 13px; font-weight: 700;
            color: var(--blue-600);
            margin-bottom: 8px;
        }
        .inv-amount {
            font-size: 18px; font-weight: 800;
            color: var(--emerald-600);
            letter-spacing: -0.01em;
        }
        .inv-amount-sm {
            font-size: 15px; font-weight: 700;
            color: var(--emerald-600);
        }
        .inv-sep {
            border: 0; height: 1px;
            background: var(--border);
            margin: 10px 0;
        }
        .inv-no-text {
            font-size: 12.5px; font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        /* Payment Badges */
        .pay-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10.5px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            letter-spacing: 0.02em;
        }
        .pay-badge .material-icons-round { font-size: 13px; }
        .pay-lunas {
            background: var(--emerald-50); color: var(--emerald-600);
            border: 1px solid #a7f3d0;
        }
        .pay-belum {
            background: var(--rose-50); color: var(--rose-600);
            border: 1px solid #fecdd3;
        }
        .pay-none {
            background: var(--rose-50); color: var(--rose-600);
            border: 1px solid #fecdd3;
            font-size: 10.5px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
        }

        /* ── Teknisi Section ── */
        .tek-block {
            background: var(--bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 8px;
        }
        .tek-block:last-child { margin-bottom: 0; }
        .tek-top {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 8px;
        }
        .tek-nm {
            font-size: 13px; font-weight: 700;
            color: var(--text-dark);
            display: flex; align-items: center; gap: 5px;
        }
        .tek-nm .material-icons-round { font-size: 15px; color: var(--violet-500); }
        .tek-pay {
            font-size: 11.5px; font-weight: 700;
            color: var(--emerald-600);
            background: var(--emerald-50);
            padding: 3px 10px; border-radius: 16px;
            border: 1px solid #a7f3d0;
        }

        .tek-rows { padding-left: 14px; border-left: 2px solid var(--border); }
        .tek-row {
            display: flex; align-items: center; gap: 10px;
            padding: 4px 0;
            border-bottom: 1px dashed var(--border-light);
        }
        .tek-row:last-child { border-bottom: none; }
        .tek-date {
            font-size: 11px; font-weight: 600;
            color: var(--text-secondary);
            min-width: 42px;
        }
        .tek-t {
            display: inline-flex; align-items: center; gap: 3px;
            font-size: 11px; font-weight: 600;
            padding: 2px 8px; border-radius: 4px;
        }
        .tek-t .material-icons-round { font-size: 12px; }
        .tek-in { background: var(--emerald-50); color: var(--emerald-600); }
        .tek-out { background: var(--rose-50); color: var(--rose-600); }
        .tek-none {
            font-size: 11px; color: var(--text-muted);
            font-style: italic; padding: 4px 0;
        }

        /* Lunas watermark */
        .lunas-overlay { position: relative; z-index: 1; }
        .lunas-overlay::after {
            content: '';
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('assets/img/lunas.png');
            background-size: 40%; background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1; z-index: -1; pointer-events: none;
        }

        /* Empty */
        .empty-msg {
            text-align: center; padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-msg .material-icons-round { font-size: 48px; opacity: 0.3; margin-bottom: 12px; }
        .empty-msg p { font-size: 14px; font-weight: 500; }

        /* ═══════════ PRINT ═══════════ */
        @media print {
            body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .page-wrap { max-width: 100%; padding: 0; }
            .data-card { page-break-inside: avoid; box-shadow: none !important; }
            .stat-box, .data-card, .inv-card, .tek-block { box-shadow: none !important; }
            .lunas-overlay::after { opacity: 0.15 !important; }
        }

        /* ═══════════ RESPONSIVE ═══════════ */
        @media (max-width: 1024px) {
            .data-card-grid { grid-template-columns: 1fr; gap: 14px; }
            .col-labels { display: none; }
            .rnum { margin-bottom: 2px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .page-wrap { padding: 12px 10px 36px; }
            .page-header { padding: 18px; flex-direction: column; align-items: flex-start; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page-wrap">

        <!-- ═══ HEADER ═══ -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="header-icon">
                    <i class="material-icons-round">assessment</i>
                </div>
                <div>
                    <h1>Laporan Kegiatan Lengkap</h1>
                    <span class="period-tag">
                        <i class="material-icons-round">calendar_month</i>
                        <?= $nama_bulan . ' ' . $tahun ?>
                    </span>
                </div>
            </div>
            <div class="header-actions no-print">
                <button onclick="window.history.back()" class="btn-act btn-act-back">
                    <i class="material-icons-round">arrow_back</i> Kembali
                </button>
                <button onclick="window.print()" class="btn-act btn-act-print">
                    <i class="material-icons-round">print</i> Cetak Laporan
                </button>
                <a href="export-laporan-excel.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn-act btn-act-excel">
                    <i class="material-icons-round">table_view</i> Export Excel
                </a>
            </div>
        </div>

        <?php
        // ── Pre-fetch data ──
        $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.lunas, k.paid, c.nama AS nama_cust
                    FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
                    WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL
                    GROUP BY k.kode ORDER BY k.created_at ASC";
        $stmt_main = $conn->prepare($sql_main);
        $stmt_main->bind_param("ii", $bulan, $tahun);
        $stmt_main->execute();
        $result_main = $stmt_main->get_result();

        $all_rows = [];
        $total_kegiatan = 0;
        $total_lunas = 0;
        $total_belum_lunas = 0;
        while ($r = $result_main->fetch_assoc()) {
            $all_rows[] = $r;
            $total_kegiatan++;
            if (!empty($r['lunas']) && $r['lunas'] != '0000-00-00') $total_lunas++;
            else $total_belum_lunas++;
        }
        $stmt_main->close();

        $sql_income = "SELECT COALESCE(SUM(sub.nominal), 0) as total
                       FROM (
                           SELECT pk.kode, MAX(pk.nominal_invoice) as nominal
                           FROM pendapatan_kegiatan pk
                           JOIN kegiatan k ON pk.kode = k.kode
                           WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL
                           GROUP BY pk.kode
                       ) sub";
        $stmt_income = $conn->prepare($sql_income);
        $stmt_income->bind_param("ii", $bulan, $tahun);
        $stmt_income->execute();
        $total_income = $stmt_income->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt_income->close();
        ?>

        <!-- ═══ STATS ═══ -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-dot stat-dot-blue"><i class="material-icons-round">assignment</i></div>
                <div><div class="stat-title">Total Kegiatan</div><div class="stat-num"><?= $total_kegiatan ?></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-dot stat-dot-emerald"><i class="material-icons-round">check_circle</i></div>
                <div><div class="stat-title">Lunas</div><div class="stat-num" style="color:var(--emerald-600)"><?= $total_lunas ?></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-dot stat-dot-rose"><i class="material-icons-round">pending</i></div>
                <div><div class="stat-title">Belum Lunas</div><div class="stat-num" style="color:var(--rose-600)"><?= $total_belum_lunas ?></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-dot stat-dot-amber"><i class="material-icons-round">account_balance_wallet</i></div>
                <div><div class="stat-title">Total Pendapatan</div><div class="stat-num" style="font-size:17px;color:var(--text-dark)">Rp <?= number_format($total_income, 0, ',', '.') ?></div></div>
            </div>
        </div>

        <!-- ═══ COLUMN LABELS ═══ -->
        <div class="col-labels no-print">
            <span>No</span>
            <span>Customer & Request</span>
            <span>Invoice & Pembayaran</span>
            <span>Teknisi & Pelaksanaan</span>
        </div>

        <!-- ═══ DATA ═══ -->
        <?php
        if (!empty($all_rows)) {
            $no = 0;
            foreach ($all_rows as $row_main) {
                $no++;
                $kode = $row_main['kode_transaksi'];
                $is_manual = is_numeric($row_main['paid']);
                $is_lunas = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00');
                $card_type = $is_lunas ? 'is-lunas' : ($is_manual ? 'is-manual' : 'is-unpaid');
                $overlay = $is_lunas ? 'lunas-overlay' : '';
        ?>
        <div class="data-card <?= $card_type ?>">
            <div class="data-card-grid">

                <!-- NUM -->
                <div><span class="rnum"><?= $no ?></span></div>

                <!-- CUSTOMER -->
                <div>
                    <div class="c-name"><?= htmlspecialchars($row_main['nama_cust']); ?></div>
                    <?php if (!empty($row_main['keterangan'])) : ?>
                        <div class="c-ket"><?= htmlspecialchars($row_main['keterangan']); ?></div>
                    <?php endif; ?>
                    <div class="c-tags">
                        <span class="c-tag"><i class="material-icons-round">tag</i> <strong><?= $kode; ?></strong></span>
                        <span class="c-tag"><i class="material-icons-round">event</i> <?= date("d M Y", strtotime($row_main['created_at'])); ?></span>
                    </div>
                </div>

                <!-- INVOICE -->
                <div>
                    <?php
                    $sql_inv = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
                    $stmt_inv = $conn->prepare($sql_inv);
                    $stmt_inv->bind_param("s", $kode);
                    $stmt_inv->execute();
                    $inv = $stmt_inv->get_result()->fetch_assoc();
                    $stmt_inv->close();
                    ?>
                    <div class="inv-card <?= $overlay ?>">
                        <?php if ($inv) : ?>
                            <div class="inv-lbl">No. Invoice</div>
                            <div class="inv-no"><?= htmlspecialchars($inv['no_invoice']); ?></div>
                            <div class="inv-lbl">Nominal</div>
                            <div class="inv-amount">Rp <?= number_format($inv['nominal_invoice'], 0, ',', '.'); ?></div>
                            <hr class="inv-sep">
                            <?php if ($is_lunas) : ?>
                                <span class="pay-badge pay-lunas"><i class="material-icons-round">verified</i> Lunas <?= date("d M Y", strtotime($row_main['lunas'])) ?></span>
                            <?php else : ?>
                                <span class="pay-badge pay-belum"><i class="material-icons-round">schedule</i> Belum Lunas</span>
                            <?php endif; ?>
                        <?php elseif ($is_manual) : ?>
                            <div class="inv-lbl">Status</div>
                            <div class="inv-no-text">Tidak Ada Invoice</div>
                            <div class="inv-lbl">Nominal Manual</div>
                            <div class="inv-amount-sm">Rp 30.000</div>
                        <?php else : ?>
                            <div style="text-align:center;padding:10px 0;">
                                <span class="pay-none"><i class="material-icons-round" style="font-size:12px;vertical-align:middle;margin-right:2px;">block</i> NO PAYMENT</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TEKNISI -->
                <div>
                    <?php
                    $sql_cnt = "SELECT COUNT(DISTINCT teknisi_id) as tot FROM pelaksanaan_kegiatan WHERE kode = ? AND waktu_mulai IS NOT NULL";
                    $stmt_cnt = $conn->prepare($sql_cnt);
                    $stmt_cnt->bind_param("s", $kode);
                    $stmt_cnt->execute();
                    $jml_aktif = $stmt_cnt->get_result()->fetch_assoc()['tot'] ?? 0;
                    $stmt_cnt->close();

                    $sql_tek = "SELECT t.id, t.nama AS nama_teknisi,
                                (SELECT SUM(pendapatan) FROM pendapatan_kegiatan WHERE kode = ? AND teknisi_id = t.id) as total_pendapatan
                                FROM team_kegiatan tk
                                JOIN teknisi t ON tk.teknisi_id = t.id
                                JOIN kegiatan k ON tk.kegiatan_id = k.id
                                WHERE k.kode = ? GROUP BY t.id";
                    $stmt_tek = $conn->prepare($sql_tek);
                    $stmt_tek->bind_param("ss", $kode, $kode);
                    $stmt_tek->execute();
                    $res_tek = $stmt_tek->get_result();

                    $has_tek = false;
                    while ($rt = $res_tek->fetch_assoc()) {
                        $has_tek = true;
                        $tid = $rt['id'];
                        $pdb = $rt['total_pendapatan'] ?? 0;

                        $sql_abs = "SELECT DATE(waktu_mulai) as tgl, MIN(waktu_mulai) as masuk, MAX(waktu_selesai) as pulang
                                    FROM pelaksanaan_kegiatan
                                    WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL
                                    GROUP BY tgl ORDER BY tgl ASC";
                        $stmt_abs = $conn->prepare($sql_abs);
                        $stmt_abs->bind_param("si", $kode, $tid);
                        $stmt_abs->execute();
                        $res_abs = $stmt_abs->get_result();
                        $ada_abs = ($res_abs->num_rows > 0);

                        $ptampil = $pdb;
                        if ($pdb == 0 && $is_manual) {
                            $ptampil = ($ada_abs && $jml_aktif > 0) ? 30000 / $jml_aktif : 0;
                        }
                    ?>
                    <div class="tek-block">
                        <div class="tek-top">
                            <span class="tek-nm"><i class="material-icons-round">engineering</i> <?= htmlspecialchars($rt['nama_teknisi']); ?></span>
                            <span class="tek-pay">Rp <?= number_format($ptampil, 0, ',', '.'); ?></span>
                        </div>
                        <?php if ($ada_abs) : ?>
                        <div class="tek-rows">
                            <?php while ($ra = $res_abs->fetch_assoc()) : ?>
                            <div class="tek-row">
                                <span class="tek-date"><?= date("d/m", strtotime($ra['tgl'])); ?></span>
                                <span class="tek-t tek-in"><i class="material-icons-round">login</i> <?= $ra['masuk'] ? date("H:i", strtotime($ra['masuk'])) : '-'; ?></span>
                                <span class="tek-t tek-out"><i class="material-icons-round">logout</i> <?= $ra['pulang'] ? date("H:i", strtotime($ra['pulang'])) : '-'; ?></span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else : ?>
                            <p class="tek-none">Tidak ada data pelaksanaan.</p>
                        <?php endif; ?>
                    </div>
                    <?php
                        $stmt_abs->close();
                    }
                    $stmt_tek->close();
                    if (!$has_tek) echo '<p class="tek-none">Belum ada teknisi ditugaskan.</p>';
                    ?>
                </div>

            </div>
        </div>
        <?php
            }
        } else {
        ?>
        <div class="empty-msg">
            <i class="material-icons-round">inbox</i>
            <p>Tidak ada data kegiatan untuk periode <strong><?= $nama_bulan . ' ' . $tahun ?></strong>.</p>
        </div>
        <?php } $conn->close(); ?>

    </div>
</body>
</html>