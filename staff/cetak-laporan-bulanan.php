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
    <meta name="description" content="Laporan Kegiatan Lengkap periode <?= $nama_bulan . ' ' . $tahun ?> - Sistem Manajemen Jadwal">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════════════════════
           DESIGN SYSTEM — ISO 9241 Compliant Premium Report
           ══════════════════════════════════════════════════════════════ */

        :root {
            --bg-body: #0f172a;
            --bg-surface: rgba(30, 41, 59, 0.7);
            --bg-card: rgba(30, 41, 59, 0.55);
            --bg-card-hover: rgba(51, 65, 85, 0.6);
            --border-glass: rgba(148, 163, 184, 0.15);
            --border-accent: rgba(99, 102, 241, 0.3);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-indigo: #818cf8;
            --accent-blue: #60a5fa;
            --accent-emerald: #34d399;
            --accent-amber: #fbbf24;
            --accent-rose: #fb7185;
            --accent-violet: #a78bfa;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.25);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.35);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            font-family: var(--font);
            background: var(--bg-body);
            background-image:
                radial-gradient(ellipse 800px 600px at 20% 10%, rgba(99,102,241,0.08), transparent),
                radial-gradient(ellipse 600px 400px at 80% 80%, rgba(59,130,246,0.06), transparent);
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Page Container ── */
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 20px 48px;
        }

        /* ── Header Card ── */
        .report-header {
            background: var(--bg-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-xl);
            padding: 28px 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .report-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .report-header-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-blue));
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        .report-header-icon .material-icons { font-size: 26px; color: #fff; }
        .report-header h1 {
            font-size: 20px; font-weight: 800;
            letter-spacing: -0.03em; margin: 0;
            background: linear-gradient(135deg, #f1f5f9, #cbd5e1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .report-header .period-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(99,102,241,0.12); color: var(--accent-indigo);
            font-size: 12px; font-weight: 600;
            padding: 5px 14px; border-radius: 20px;
            letter-spacing: 0.02em;
            border: 1px solid rgba(99,102,241,0.2);
        }
        .report-header .period-badge .material-icons { font-size: 15px; }
        .report-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── Premium Action Buttons ── */
        .btn-report {
            display: inline-flex; align-items: center; gap: 7px;
            font-family: var(--font);
            font-size: 12.5px; font-weight: 600;
            padding: 9px 18px;
            border: none; border-radius: var(--radius-md);
            cursor: pointer; text-decoration: none;
            transition: all 0.2s ease;
            letter-spacing: 0.01em;
        }
        .btn-report .material-icons { font-size: 17px; }
        .btn-report-print {
            background: linear-gradient(135deg, var(--accent-indigo), #6366f1);
            color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.35);
        }
        .btn-report-print:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,0.4); color: #fff; }
        .btn-report-excel {
            background: linear-gradient(135deg, var(--accent-emerald), #10b981);
            color: #fff; box-shadow: 0 2px 8px rgba(16,185,129,0.3);
        }
        .btn-report-excel:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(16,185,129,0.4); color: #fff; }
        .btn-report-back {
            background: rgba(148,163,184,0.1);
            color: var(--text-secondary);
            border: 1px solid var(--border-glass);
        }
        .btn-report-back:hover { background: rgba(148,163,184,0.18); color: var(--text-primary); }

        /* ── Summary Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: var(--border-accent); }
        .stat-icon {
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon .material-icons { font-size: 20px; }
        .stat-icon-blue { background: rgba(96,165,250,0.12); color: var(--accent-blue); }
        .stat-icon-emerald { background: rgba(52,211,153,0.12); color: var(--accent-emerald); }
        .stat-icon-amber { background: rgba(251,191,36,0.12); color: var(--accent-amber); }
        .stat-icon-rose { background: rgba(251,113,133,0.12); color: var(--accent-rose); }
        .stat-label { font-size: 11px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
        .stat-value { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }

        /* ── Table Header (Column Labels) ── */
        .tbl-report-header {
            display: grid;
            grid-template-columns: 50px 1.2fr 1fr 1.2fr;
            gap: 0;
            padding: 10px 20px;
            margin-bottom: 6px;
        }
        .tbl-report-header span {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ── Data Card (Each Row) ── */
        .report-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .report-card:hover {
            border-color: var(--border-accent);
            box-shadow: var(--shadow-md);
        }
        .report-card-inner {
            display: grid;
            grid-template-columns: 50px 1.2fr 1fr 1.2fr;
            gap: 0;
            padding: 18px 20px;
            align-items: start;
        }
        .report-card.lunas-card {
            border-left: 3px solid var(--accent-emerald);
        }
        .report-card.unpaid-card {
            border-left: 3px solid var(--accent-rose);
        }

        /* ── Row Number ── */
        .row-num {
            font-size: 12px; font-weight: 700;
            color: var(--text-muted);
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(148,163,184,0.08);
            border-radius: var(--radius-sm);
        }

        /* ── Customer Section ── */
        .cust-name {
            font-size: 14px; font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            display: flex; align-items: center; gap: 8px;
        }
        .cust-name .material-icons { font-size: 16px; color: var(--accent-blue); }
        .cust-keterangan {
            font-size: 12px; color: var(--text-secondary);
            background: rgba(148,163,184,0.06);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: var(--radius-sm);
            padding: 6px 10px;
            margin-bottom: 8px;
            line-height: 1.5;
            font-style: italic;
        }
        .cust-meta {
            display: flex; flex-wrap: wrap; gap: 10px;
        }
        .cust-meta-item {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--text-muted);
        }
        .cust-meta-item .material-icons { font-size: 13px; }
        .cust-meta-item strong { color: var(--text-secondary); font-weight: 600; }

        /* ── Invoice Section ── */
        .invoice-box {
            background: rgba(15,23,42,0.5);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 14px 16px;
        }
        .invoice-label {
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-muted);
            margin-bottom: 4px;
        }
        .invoice-number {
            font-size: 13px; font-weight: 700;
            color: var(--accent-blue);
            margin-bottom: 10px;
        }
        .invoice-nominal {
            font-size: 18px; font-weight: 800;
            color: var(--accent-emerald);
            letter-spacing: -0.01em;
        }
        .invoice-nominal-small {
            font-size: 14px; font-weight: 700;
            color: var(--accent-emerald);
        }
        .invoice-divider {
            border: 0; height: 1px;
            background: var(--border-glass);
            margin: 10px 0;
        }
        .payment-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 10px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            letter-spacing: 0.04em;
        }
        .payment-badge .material-icons { font-size: 12px; }
        .badge-lunas {
            background: rgba(52,211,153,0.12);
            color: var(--accent-emerald);
            border: 1px solid rgba(52,211,153,0.2);
        }
        .badge-belum {
            background: rgba(251,113,133,0.12);
            color: var(--accent-rose);
            border: 1px solid rgba(251,113,133,0.2);
        }
        .badge-no-payment {
            background: rgba(251,113,133,0.1);
            color: var(--accent-rose);
            border: 1px solid rgba(251,113,133,0.15);
            font-size: 10px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
        }

        /* ── Technician Section ── */
        .tek-entry {
            background: rgba(15,23,42,0.4);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-bottom: 8px;
        }
        .tek-entry:last-child { margin-bottom: 0; }
        .tek-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 8px;
        }
        .tek-name {
            font-size: 13px; font-weight: 700;
            color: var(--text-primary);
            display: flex; align-items: center; gap: 6px;
        }
        .tek-name .material-icons { font-size: 15px; color: var(--accent-violet); }
        .tek-pendapatan {
            font-size: 12px; font-weight: 700;
            color: var(--accent-emerald);
            background: rgba(52,211,153,0.1);
            border: 1px solid rgba(52,211,153,0.15);
            padding: 3px 10px; border-radius: 16px;
        }
        .tek-timeline { padding-left: 16px; border-left: 2px solid rgba(148,163,184,0.1); }
        .tek-day {
            display: flex; align-items: center; gap: 12px;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(148,163,184,0.08);
        }
        .tek-day:last-child { border-bottom: none; }
        .tek-day-label {
            font-size: 10.5px; font-weight: 600;
            color: var(--text-secondary);
            min-width: 50px;
        }
        .tek-time {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10.5px; font-weight: 600;
            padding: 3px 8px; border-radius: 4px;
        }
        .tek-time .material-icons { font-size: 12px; }
        .tek-time-start {
            background: rgba(52,211,153,0.1);
            color: var(--accent-emerald);
        }
        .tek-time-end {
            background: rgba(251,113,133,0.1);
            color: var(--accent-rose);
        }
        .tek-no-data {
            font-size: 11px; color: var(--text-muted);
            font-style: italic;
            padding: 4px 0;
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state .material-icons { font-size: 48px; margin-bottom: 12px; opacity: 0.4; }
        .empty-state p { font-size: 14px; font-weight: 500; }

        /* ── Lunas Watermark (for print) ── */
        .lunas-bg { position: relative; z-index: 1; }
        .lunas-bg::after {
            content: '';
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('assets/img/lunas.png');
            background-size: 40%; background-position: center;
            background-repeat: no-repeat;
            opacity: 0.08; z-index: -1;
            pointer-events: none;
        }

        /* ═══════════════ PRINT STYLES ═══════════════ */
        @media print {
            body {
                background: #fff !important;
                color: #1e293b !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .report-container { max-width: 100%; padding: 0; }
            .report-header {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                backdrop-filter: none !important;
            }
            .report-header h1 {
                background: none !important;
                -webkit-text-fill-color: #1e293b !important;
                color: #1e293b !important;
            }
            .period-badge {
                background: #eff6ff !important;
                color: #3b82f6 !important;
                border-color: #bfdbfe !important;
            }
            .stat-card, .report-card, .invoice-box, .tek-entry {
                background: #fff !important;
                border: 1px solid #e2e8f0 !important;
                backdrop-filter: none !important;
                box-shadow: none !important;
            }
            .cust-name, .tek-name { color: #1e293b !important; }
            .cust-keterangan { background: #f8fafc !important; color: #475569 !important; border-color: #e2e8f0 !important; }
            .cust-meta-item, .stat-label, .invoice-label, .tbl-report-header span { color: #64748b !important; }
            .cust-meta-item strong { color: #334155 !important; }
            .row-num { background: #f1f5f9 !important; color: #475569 !important; }
            .invoice-number { color: #2563eb !important; }
            .invoice-nominal, .invoice-nominal-small, .tek-pendapatan { color: #059669 !important; }
            .tek-pendapatan { background: #f0fdf4 !important; border-color: #bbf7d0 !important; }
            .tek-timeline { border-left-color: #e2e8f0 !important; }
            .tek-time-start { background: #f0fdf4 !important; color: #059669 !important; }
            .tek-time-end { background: #fff1f2 !important; color: #e11d48 !important; }
            .stat-value { color: #1e293b !important; }
            .lunas-bg::after { opacity: 0.15 !important; }
            .badge-lunas { background: #f0fdf4 !important; color: #059669 !important; border-color: #bbf7d0 !important; }
            .badge-belum, .badge-no-payment { background: #fff1f2 !important; color: #e11d48 !important; border-color: #fecdd3 !important; }
            .report-card { page-break-inside: avoid; }
        }

        /* ═══════════════ RESPONSIVE ═══════════════ */
        @media (max-width: 992px) {
            .report-card-inner {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .tbl-report-header { display: none; }
            .row-num { margin-bottom: 4px; }
            .report-header { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 576px) {
            .report-container { padding: 12px 10px 36px; }
            .report-header { padding: 20px; }
            .stats-row { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="report-container">

        <!-- ══════ HEADER ══════ -->
        <div class="report-header">
            <div class="report-header-left">
                <div class="report-header-icon">
                    <i class="material-icons">assessment</i>
                </div>
                <div>
                    <h1>Laporan Kegiatan Lengkap</h1>
                    <span class="period-badge">
                        <i class="material-icons">calendar_month</i>
                        <?= $nama_bulan . ' ' . $tahun ?>
                    </span>
                </div>
            </div>
            <div class="report-actions no-print">
                <button onclick="window.history.back()" class="btn-report btn-report-back">
                    <i class="material-icons">arrow_back</i> Kembali
                </button>
                <button onclick="window.print()" class="btn-report btn-report-print">
                    <i class="material-icons">print</i> Cetak Laporan
                </button>
                <a href="export-laporan-excel.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn-report btn-report-excel">
                    <i class="material-icons">table_view</i> Export Excel
                </a>
            </div>
        </div>

        <?php
        // ── Pre-fetch all data for summary stats ──
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
        $total_pendapatan_sum = 0;

        while ($r = $result_main->fetch_assoc()) {
            $all_rows[] = $r;
            $total_kegiatan++;
            $is_lunas = (!empty($r['lunas']) && $r['lunas'] != '0000-00-00');
            if ($is_lunas) $total_lunas++;
            else $total_belum_lunas++;
        }
        $stmt_main->close();

        // Calculate total income
        $sql_income = "SELECT COALESCE(SUM(pk.nominal_invoice), 0) as total
                       FROM pendapatan_kegiatan pk
                       JOIN kegiatan k ON pk.kode = k.kode
                       WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL";
        $stmt_income = $conn->prepare($sql_income);
        $stmt_income->bind_param("ii", $bulan, $tahun);
        $stmt_income->execute();
        $income_res = $stmt_income->get_result()->fetch_assoc();
        $total_pendapatan_sum = $income_res['total'] ?? 0;
        $stmt_income->close();
        ?>

        <!-- ══════ SUMMARY STATS ══════ -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i class="material-icons">assignment</i></div>
                <div>
                    <div class="stat-label">Total Kegiatan</div>
                    <div class="stat-value"><?= $total_kegiatan ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-emerald"><i class="material-icons">check_circle</i></div>
                <div>
                    <div class="stat-label">Lunas</div>
                    <div class="stat-value" style="color: var(--accent-emerald);"><?= $total_lunas ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-rose"><i class="material-icons">pending</i></div>
                <div>
                    <div class="stat-label">Belum Lunas</div>
                    <div class="stat-value" style="color: var(--accent-rose);"><?= $total_belum_lunas ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-amber"><i class="material-icons">account_balance_wallet</i></div>
                <div>
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value" style="color: var(--accent-amber); font-size: 18px;">Rp <?= number_format($total_pendapatan_sum, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- ══════ COLUMN HEADERS ══════ -->
        <div class="tbl-report-header no-print">
            <span>No</span>
            <span>Customer & Request</span>
            <span>Invoice & Pembayaran</span>
            <span>Teknisi & Pelaksanaan</span>
        </div>

        <!-- ══════ DATA ROWS ══════ -->
        <?php
        if (!empty($all_rows)) {
            $no = 0;
            foreach ($all_rows as $row_main) {
                $no++;
                $kodeTransaksi = $row_main['kode_transaksi'];
                $is_manual_fee = is_numeric($row_main['paid']);
                $is_lunas = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00');
                $lunas_class = $is_lunas ? 'lunas-bg' : '';
                $card_class = $is_lunas ? 'lunas-card' : ($is_manual_fee ? '' : 'unpaid-card');
        ?>
        <div class="report-card <?= $card_class ?>">
            <div class="report-card-inner">
                <!-- COL 1: Number -->
                <div><span class="row-num"><?= $no ?></span></div>

                <!-- COL 2: Customer & Request -->
                <div>
                    <div class="cust-name">
                        <i class="material-icons">person</i>
                        <?= htmlspecialchars($row_main['nama_cust']); ?>
                    </div>
                    <?php if (!empty($row_main['keterangan'])) : ?>
                    <div class="cust-keterangan">"<?= htmlspecialchars($row_main['keterangan']); ?>"</div>
                    <?php endif; ?>
                    <div class="cust-meta">
                        <span class="cust-meta-item">
                            <i class="material-icons">qr_code_2</i>
                            <strong><?= $kodeTransaksi; ?></strong>
                        </span>
                        <span class="cust-meta-item">
                            <i class="material-icons">event</i>
                            <?= date("d M Y", strtotime($row_main['created_at'])); ?>
                        </span>
                    </div>
                </div>

                <!-- COL 3: Invoice -->
                <div>
                    <?php
                    $sql_invoice = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
                    $stmt_invoice = $conn->prepare($sql_invoice);
                    $stmt_invoice->bind_param("s", $kodeTransaksi);
                    $stmt_invoice->execute();
                    $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
                    $stmt_invoice->close();
                    ?>
                    <div class="invoice-box <?= $lunas_class ?>">
                        <?php if ($invoice_data) : ?>
                            <div class="invoice-label">No. Invoice</div>
                            <div class="invoice-number"><?= htmlspecialchars($invoice_data['no_invoice']); ?></div>
                            <div class="invoice-label">Nominal</div>
                            <div class="invoice-nominal">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></div>
                            <hr class="invoice-divider">
                            <?php if ($is_lunas) : ?>
                                <span class="payment-badge badge-lunas"><i class="material-icons">verified</i> Lunas <?= date("d M Y", strtotime($row_main['lunas'])) ?></span>
                            <?php else : ?>
                                <span class="payment-badge badge-belum"><i class="material-icons">schedule</i> Belum Lunas</span>
                            <?php endif; ?>
                        <?php elseif ($is_manual_fee) : ?>
                            <div class="invoice-label">Status</div>
                            <div class="invoice-number" style="color: var(--text-secondary); margin-bottom: 6px;">Tidak Ada Invoice</div>
                            <div class="invoice-label">Nominal Manual</div>
                            <div class="invoice-nominal-small">Rp 30.000</div>
                        <?php else : ?>
                            <div style="text-align: center; padding: 8px 0;">
                                <span class="badge-no-payment"><i class="material-icons" style="font-size:12px;vertical-align:middle;margin-right:3px;">block</i>NO PAYMENT</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COL 4: Teknisi & Pelaksanaan -->
                <div>
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

                    $has_teknisi = false;
                    while($row_teknisi = $result_teknisi_list->fetch_assoc()) {
                        $has_teknisi = true;
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
                    <div class="tek-entry">
                        <div class="tek-header">
                            <span class="tek-name">
                                <i class="material-icons">engineering</i>
                                <?= htmlspecialchars($row_teknisi['nama_teknisi']); ?>
                            </span>
                            <span class="tek-pendapatan">Rp <?= number_format($pendapatan_tampil, 0, ',', '.'); ?></span>
                        </div>
                        <?php if ($punya_absensi) : ?>
                        <div class="tek-timeline">
                            <?php while($row_absensi = $result_absensi->fetch_assoc()) : ?>
                            <div class="tek-day">
                                <span class="tek-day-label"><?= date("d/m", strtotime($row_absensi['tanggal_kerja'])); ?></span>
                                <span class="tek-time tek-time-start">
                                    <i class="material-icons">login</i>
                                    <?= !empty($row_absensi['jam_masuk']) ? date("H:i", strtotime($row_absensi['jam_masuk'])) : '-'; ?>
                                </span>
                                <span class="tek-time tek-time-end">
                                    <i class="material-icons">logout</i>
                                    <?= !empty($row_absensi['jam_pulang']) ? date("H:i", strtotime($row_absensi['jam_pulang'])) : '-'; ?>
                                </span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else : ?>
                            <p class="tek-no-data">Tidak ada data pelaksanaan.</p>
                        <?php endif; ?>
                    </div>
                    <?php
                        $stmt_absensi->close();
                    }
                    $stmt_teknisi_list->close();

                    if (!$has_teknisi) {
                        echo '<p class="tek-no-data" style="padding: 8px 0;">Belum ada teknisi ditugaskan.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
        ?>
            <div class="empty-state">
                <i class="material-icons">inbox</i>
                <p>Tidak ada data kegiatan ditemukan untuk periode <strong><?= $nama_bulan . ' ' . $tahun ?></strong>.</p>
            </div>
        <?php
        }
        $conn->close();
        ?>

    </div>

    <script>
        // Smooth entrance animation
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.report-card, .stat-card');
            cards.forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 60 * i);
            });
        });
    </script>
</body>
</html>