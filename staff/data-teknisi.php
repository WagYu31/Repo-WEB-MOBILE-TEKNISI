<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Teknisi";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Data & Kinerja Teknisi</title>
    <?php include "head.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* ═══ PREMIUM ANALYTICS DASHBOARD ═══ */
        .tek-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px; margin-bottom: 20px;
        }
        .tek-title-left { display: flex; align-items: center; gap: 14px; }
        .tek-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(245,158,11,0.3);
        }
        .tek-icon i { color: #fff; font-size: 18px; }
        .tek-title-left h4 { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; }
        .tek-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }
        .tek-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .tek-btn {
            padding: 9px 18px; border: none; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .tek-btn:hover { transform: translateY(-1px); }
        .tek-btn-fee { background: #eef2ff; color: #6366f1; border: 1.5px solid #c7d2fe; }
        .tek-btn-fee:hover { background: #6366f1; color: #fff; }
        .tek-btn-xls { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
        .tek-btn-xls:hover { background: #22c55e; color: #fff; }
        .tek-btn-add {
            background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }
        .tek-btn-add:hover { color: #fff; }

        /* Filter card */
        .tek-filter-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 16px 20px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .tek-filter-label { font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; }
        .tek-month-input {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px;
            font-size: 13px; font-weight: 600; color: #1e293b; background: #f8fafc;
            transition: all 0.2s;
        }
        .tek-month-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.08); outline: none; }
        .tek-filter-btn {
            padding: 9px 24px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #1e293b, #334155); color: #fff;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .tek-filter-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30,41,59,0.2); }
        .tek-compare-label { font-size: 11px; color: #94a3b8; font-weight: 600; margin-left: auto; }
        .tek-compare-toggle {
            padding: 7px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
            background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 600;
            cursor: pointer; transition: all 0.15s;
        }
        .tek-compare-toggle.active { background: #eef2ff; color: #6366f1; border-color: #c7d2fe; }

        /* Range pills */
        .tek-range-pills {
            display: flex; gap: 0; border-radius: 10px; overflow: hidden;
            border: 1.5px solid #e5e7eb;
        }
        .tek-range-pill {
            padding: 8px 16px; font-size: 12px; font-weight: 700;
            border: none; background: #fff; color: #64748b;
            cursor: pointer; transition: all 0.2s; white-space: nowrap;
            border-right: 1px solid #e5e7eb;
        }
        .tek-range-pill:last-child { border-right: none; }
        .tek-range-pill:hover { background: #f8fafc; }
        .tek-range-pill.active {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff;
        }
        .tek-range-info {
            font-size: 11px; font-weight: 600; color: #64748b;
            background: #f1f5f9; padding: 6px 12px; border-radius: 8px;
            display: none; align-items: center; gap: 4px;
        }
        .tek-range-info.show { display: inline-flex; }

        /* Stat cards */
        .tek-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
        .tek-stat-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            position: relative; overflow: hidden;
        }
        .tek-stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        }
        .tek-stat-card.s1::before { background: linear-gradient(180deg, #f59e0b, #ea580c); }
        .tek-stat-card.s2::before { background: linear-gradient(180deg, #22c55e, #16a34a); }
        .tek-stat-card.s3::before { background: linear-gradient(180deg, #6366f1, #4f46e5); }
        .tek-stat-card.s4::before { background: linear-gradient(180deg, #06b6d4, #0891b2); }
        .tek-stat-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 10px;
        }
        .tek-stat-icon.si1 { background: #fef3c7; color: #d97706; }
        .tek-stat-icon.si2 { background: #dcfce7; color: #16a34a; }
        .tek-stat-icon.si3 { background: #eef2ff; color: #6366f1; }
        .tek-stat-icon.si4 { background: #cffafe; color: #0891b2; }
        .tek-stat-icon i { font-size: 14px; }
        .tek-stat-num { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .tek-stat-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }
        .tek-stat-sub { font-size: 11px; font-weight: 600; margin-top: 6px; }
        .tek-stat-sub.up { color: #16a34a; }
        .tek-stat-sub.down { color: #ef4444; }

        /* Charts grid */
        .tek-charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px; }
        .tek-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .tek-card-header { display: flex; align-items: center; gap: 10px; padding: 18px 20px 0; }
        .tek-card-hicon {
            width: 30px; height: 30px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .tek-card-hicon i { color: #fff; font-size: 12px; }
        .tek-card-hicon.h-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .tek-card-hicon.h-orange { background: linear-gradient(135deg, #f59e0b, #ea580c); }
        .tek-card-hicon.h-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .tek-card-hicon.h-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .tek-card-header h6 { margin: 0; font-size: 13px; font-weight: 800; color: #1e293b; }
        .tek-card-body { padding: 10px 18px 14px; }

        /* Donut card */
        .donut-section { display: flex; flex-direction: column; align-items: center; gap: 14px; }
        .donut-wrap { position: relative; display: flex; align-items: center; justify-content: center; }
        .donut-center { position: absolute; text-align: center; }
        .donut-center .pct { font-size: 26px; font-weight: 900; color: #1e293b; line-height: 1; }
        .donut-center .lbl { font-size: 9px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }
        .donut-legend { display: flex; gap: 16px; justify-content: center; }
        .donut-legend-item { display: flex; align-items: center; gap: 6px; }
        .donut-legend-dot { width: 10px; height: 10px; border-radius: 3px; }
        .donut-legend-text { font-size: 11px; color: #64748b; font-weight: 600; }
        .donut-legend-num { font-weight: 800; color: #1e293b; }

        /* Top performer list */
        .top-list { list-style: none; padding: 0; margin: 0; }
        .top-item {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; margin-bottom: 6px;
            border-radius: 12px; background: #f8fafc;
            transition: all 0.2s;
        }
        .top-item:hover { background: #f1f5f9; transform: translateX(2px); }
        .top-item.gold { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fde68a; }
        .top-item.silver { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0; }
        .top-item.bronze { background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fed7aa; }
        .top-rank {
            width: 28px; height: 28px; border-radius: 8px; font-size: 11px; font-weight: 900;
            display: flex; align-items: center; justify-content: center;
            background: #e2e8f0; color: #64748b; flex-shrink: 0;
        }
        .top-rank.r1 { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 2px 8px rgba(245,158,11,0.3); }
        .top-rank.r2 { background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff; box-shadow: 0 2px 8px rgba(100,116,139,0.2); }
        .top-rank.r3 { background: linear-gradient(135deg, #f97316, #c2410c); color: #fff; box-shadow: 0 2px 8px rgba(249,115,22,0.2); }
        .top-info { flex: 1; min-width: 0; }
        .top-name { font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top-sub { font-size: 10px; color: #94a3b8; display: flex; align-items: center; gap: 4px; }
        .top-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
        .top-val { font-size: 12px; font-weight: 800; color: #1e293b; }
        .top-achieve { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
        .top-achieve.above { background: #dcfce7; color: #16a34a; }
        .top-achieve.below { background: #fef3c7; color: #92400e; }

        /* Table card */
        .tek-table-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .tek-table-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 24px; border-bottom: 1px solid #f1f5f9;
        }
        .tek-table-left { display: flex; align-items: center; gap: 10px; }
        .tek-table-hicon {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            display: flex; align-items: center; justify-content: center;
        }
        .tek-table-hicon i { color: #fff; font-size: 13px; }
        .tek-table-left h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .tek-period-badge {
            font-size: 11px; font-weight: 700; color: #f59e0b; background: #fffbeb;
            padding: 5px 14px; border-radius: 20px; border: 1px solid #fde68a;
        }
        /* Perf filter tabs */
        .perf-filter-row { display: flex; gap: 8px; padding: 0 24px 14px; flex-wrap: wrap; }
        .perf-tab {
            padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700;
            cursor: pointer; border: 1.5px solid #e5e7eb; background: #fff; color: #64748b;
            transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .perf-tab:hover { border-color: #cbd5e1; background: #f8fafc; }
        .perf-tab.active { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
        .perf-tab.active.tab-good { border-color: #22c55e; background: #f0fdf4; color: #166534; }
        .perf-tab.active.tab-bad { border-color: #ef4444; background: #fef2f2; color: #991b1b; }
        .perf-tab.active.tab-zero { border-color: #94a3b8; background: #f1f5f9; color: #334155; }
        .perf-badge {
            font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 10px;
            background: #f1f5f9; color: #64748b; min-width: 18px; text-align: center;
        }
        .perf-tab.active .perf-badge { background: rgba(0,0,0,0.08); }
        .perf-alert {
            display: none; padding: 12px 20px; margin: 0 24px 14px;
            border-radius: 10px; font-size: 12px; font-weight: 600;
            background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;
            align-items: center; gap: 8px;
        }
        .perf-alert.show { display: flex; }
        .tek-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
        .tek-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .tek-table tbody tr { transition: background 0.15s; }
        .tek-table tbody tr:hover { background: #fafbfc; }
        .tek-table tbody td {
            padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .tek-table tfoot td {
            padding: 14px 16px; font-size: 13px; font-weight: 800; color: #1e293b;
            background: #f8fafc; border-top: 2px solid #e5e7eb;
        }
        .tek-name-link { font-size: 14px; font-weight: 700; color: #f59e0b; text-decoration: none; }
        .tek-name-link:hover { color: #ea580c; text-decoration: underline; }
        .tek-nik { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .tek-val { font-size: 13px; font-weight: 600; }
        .tek-val.v-count { color: #1e293b; font-weight: 800; }
        .tek-val.v-fee { color: #6366f1; }
        .tek-val.v-pendapatan { color: #1e293b; }
        .tek-val.v-bonus { color: #16a34a; }
        .tek-val.v-target { color: #94a3b8; }
        .tek-achievement {
            font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px;
            display: inline-block; margin-top: 2px;
        }
        .tek-achievement.above { color: #16a34a; background: #f0fdf4; }
        .tek-achievement.below { color: #ef4444; background: #fef2f2; }
        .tek-act-btn {
            width: 30px; height: 30px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s; font-size: 12px;
        }
        .tek-act-edit { background: #fef3c7; color: #d97706; }
        .tek-act-edit:hover { background: #f59e0b; color: #fff; }
        .tek-act-del { background: #fef2f2; color: #ef4444; }
        .tek-act-del:hover { background: #ef4444; color: #fff; }

        /* Premium modal */
        .prem-modal .modal-content { border-radius: 16px; border: none; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        .prem-modal .modal-header { border-bottom: 1px solid #f1f5f9; padding: 18px 24px; }
        .prem-modal .modal-header h5 { font-size: 15px; font-weight: 800; color: #1e293b; }
        .prem-modal .modal-body { padding: 20px 24px; }
        .prem-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block; }
        .prem-input {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 10px 14px; font-size: 13px; color: #1e293b; background: #f8fafc;
            transition: all 0.2s; font-weight: 500;
        }
        .prem-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.08); outline: none; background: #fff; }
        .prem-footer { display: flex; gap: 8px; }
        .prem-btn-cancel {
            flex: 1; padding: 10px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            background: #fff; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .prem-btn-save {
            flex: 2; padding: 10px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;
            font-size: 13px; font-weight: 700; cursor: pointer;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }
        .spinner-box { padding: 60px; text-align: center; }
        .spinner-ring {
            width: 36px; height: 36px; border: 3px solid #f1f5f9;
            border-top-color: #f59e0b; border-radius: 50%;
            animation: spin 0.7s linear infinite; display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 992px) { .tek-charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .tek-header { flex-direction: column; align-items: flex-start; }
            .tek-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media print {
            body * { visibility: hidden; }
            .printable-area, .printable-area * { visibility: visible; }
            .printable-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative border-radius-lg" style="min-height:100vh;">
        <div class="no-print"><?php include "nav-top.php"; ?></div>
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="tek-header">
                <div class="tek-title-left">
                    <div class="tek-icon"><i class="fa-solid fa-helmet-safety"></i></div>
                    <div>
                        <h4>Data & Kinerja Teknisi</h4>
                        <p>Dashboard analisa performa bulanan</p>
                    </div>
                </div>
                <div class="tek-actions no-print">
                    <button class="tek-btn tek-btn-fee" onclick="openFeeModal()"><i class="fa-solid fa-money-bill-wave"></i> Kelola Fee</button>
                    <button class="tek-btn tek-btn-xls" onclick="exportToXls('laporan-teknisi-<?= date('Y-m'); ?>.xls')"><i class="fa-solid fa-file-excel"></i> Ekspor XLS</button>
                    <button class="tek-btn tek-btn-add" data-bs-toggle="modal" data-bs-target="#tambahTeknisiModal"><i class="fa-solid fa-plus"></i> Tambah Teknisi</button>
                </div>
            </div>

            <!-- Filter -->
            <div class="tek-filter-card no-print">
                <div class="tek-filter-label"><i class="fa-solid fa-calendar-days" style="margin-right:6px;"></i>Periode</div>
                <input type="month" id="filterMonth" class="tek-month-input" value="<?= date('Y-m'); ?>">
                <div class="tek-range-pills">
                    <button type="button" class="tek-range-pill active" data-range="1" onclick="setRange(1, this)">1 Bulan</button>
                    <button type="button" class="tek-range-pill" data-range="3" onclick="setRange(3, this)">3 Bulan</button>
                </div>
                <button id="filterBtn" class="tek-filter-btn"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <span class="tek-range-info" id="rangeInfo"></span>
                <span class="tek-compare-label">Bandingkan bulan lalu</span>
                <button type="button" id="compareToggle" class="tek-compare-toggle" onclick="toggleCompare()"><i class="fa-solid fa-code-compare"></i> Compare</button>
            </div>

            <!-- Stat Cards -->
            <div class="tek-stats no-print" id="statCards">
                <div class="tek-stat-card s1">
                    <div class="tek-stat-icon si1"><i class="fa-solid fa-users"></i></div>
                    <div class="tek-stat-num" id="statTeknisi">-</div>
                    <div class="tek-stat-label">Total Teknisi</div>
                </div>
                <div class="tek-stat-card s2">
                    <div class="tek-stat-icon si2"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                    <div class="tek-stat-num" id="statPendapatan">-</div>
                    <div class="tek-stat-label">Total Pendapatan</div>
                    <div class="tek-stat-sub" id="statPendapatanDiff"></div>
                </div>
                <div class="tek-stat-card s3">
                    <div class="tek-stat-icon si3"><i class="fa-solid fa-gift"></i></div>
                    <div class="tek-stat-num" id="statBonus">-</div>
                    <div class="tek-stat-label">Total Bonus</div>
                    <div class="tek-stat-sub" id="statBonusDiff"></div>
                </div>
                <div class="tek-stat-card s4">
                    <div class="tek-stat-icon si4"><i class="fa-solid fa-bullseye"></i></div>
                    <div class="tek-stat-num" id="statAchieve">-</div>
                    <div class="tek-stat-label">Rata-rata Target Achievement</div>
                </div>
            </div>

            <div class="printable-area">
                <!-- Charts Grid -->
                <div class="tek-charts-grid no-print">
                    <!-- Bar Chart -->
                    <div class="tek-card" style="display:flex; flex-direction:column;">
                        <div class="tek-card-header">
                            <div class="tek-card-hicon h-orange"><i class="fa-solid fa-chart-column"></i></div>
                            <h6>Pendapatan vs Target</h6>
                        </div>
                        <div class="tek-card-body" style="flex:1; min-height:0;"><div style="height:100%; min-height:300px;"><canvas id="technicianChart"></canvas></div></div>
                    </div>

                    <!-- Donut + Top Performer -->
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <!-- Donut -->
                        <div class="tek-card">
                            <div class="tek-card-header">
                                <div class="tek-card-hicon h-green"><i class="fa-solid fa-chart-pie"></i></div>
                                <h6>Target Achievement</h6>
                            </div>
                            <div class="tek-card-body">
                                <div class="donut-section">
                                    <div class="donut-wrap"><div style="height:110px; width:110px;"><canvas id="donutChart"></canvas></div>
                                        <div class="donut-center"><div class="pct" id="donutPct">-</div><div class="lbl">achieved</div></div>
                                    </div>
                                    <div class="donut-legend" id="donutLegend"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Top Performer -->
                        <div class="tek-card" style="flex:1;">
                            <div class="tek-card-header">
                                <div class="tek-card-hicon h-purple"><i class="fa-solid fa-trophy"></i></div>
                                <h6>Top Performer</h6>
                            </div>
                            <div class="tek-card-body" style="padding-top:6px;">
                                <ul class="top-list" id="topList"><li style="text-align:center; padding:20px; color:#94a3b8; font-size:12px;">Loading...</li></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="tek-table-card">
                    <div class="tek-table-header">
                        <div class="tek-table-left">
                            <div class="tek-table-hicon"><i class="fa-solid fa-list-check"></i></div>
                            <h6>Daftar Teknisi</h6>
                        </div>
                        <span class="tek-period-badge" id="report-period"></span>
                    </div>
                    <!-- Performance Filter -->
                    <div class="perf-filter-row" id="perfFilterRow">
                        <button class="perf-tab active" data-filter="all" onclick="filterPerf('all', this)">
                            <i class="fa-solid fa-users"></i> Semua <span class="perf-badge" id="perfAll">0</span>
                        </button>
                        <button class="perf-tab tab-good" data-filter="good" onclick="filterPerf('good', this)">
                            <i class="fa-solid fa-circle-check"></i> Performa Baik <span class="perf-badge" id="perfGood">0</span>
                        </button>
                        <button class="perf-tab tab-bad" data-filter="bad" onclick="filterPerf('bad', this)">
                            <i class="fa-solid fa-circle-exclamation"></i> Perlu Perhatian <span class="perf-badge" id="perfBad">0</span>
                        </button>
                        <button class="perf-tab tab-zero" data-filter="zero" onclick="filterPerf('zero', this)">
                            <i class="fa-solid fa-ban"></i> Tanpa Kegiatan <span class="perf-badge" id="perfZero">0</span>
                        </button>
                    </div>
                    <div class="perf-alert" id="perfAlert">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span id="perfAlertText"></span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="tek-table">
                            <thead>
                                <tr>
                                    <th style="padding-left:24px;">Teknisi</th>
                                    <th style="text-align:center;">Kegiatan</th>
                                    <th style="text-align:center;">Target</th>
                                    <th style="text-align:center;">Fee</th>
                                    <th style="text-align:center;">Pendapatan</th>
                                    <th style="text-align:center;">Bonus</th>
                                    <th style="text-align:center;">Achievement</th>
                                    <th style="text-align:center; width:80px;" class="no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="teknisiTableBody">
                                <tr><td colspan="8"><div class="spinner-box"><div class="spinner-ring"></div></div></td></tr>
                            </tbody>
                            <tfoot id="teknisiTableFooter"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="no-print"><?php include "footer.php"; ?></div>
    </main>

    <!-- Modal: Tambah Teknisi -->
    <div class="modal fade prem-modal" id="tambahTeknisiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Tambah Teknisi Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button></div>
            <div class="modal-body">
                <form method="POST" action="teknisi-db.php">
                    <div style="margin-bottom:14px;"><label class="prem-label">NIK</label><input type="text" class="prem-input" name="nik" required></div>
                    <div style="margin-bottom:14px;"><label class="prem-label">Nama</label><input type="text" class="prem-input" name="nama" required></div>
                    <div style="margin-bottom:14px;"><label class="prem-label">Nomor WhatsApp</label><input type="text" class="prem-input" name="no_wa" placeholder="0812..." required></div>
                    <div style="margin-bottom:14px;"><label class="prem-label">No KTP</label><input type="text" class="prem-input" name="ktp"></div>
                    <div class="prem-footer" style="margin-top:16px;"><button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button><button type="submit" class="prem-btn-save">Simpan</button></div>
                </form>
            </div>
        </div></div>
    </div>

    <!-- Modal: Target -->
    <div class="modal fade prem-modal" id="targetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Input Target</h5><button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button></div>
            <div class="modal-body">
                <form id="targetForm">
                    <label class="prem-label">Nominal Target</label><input type="text" class="prem-input" id="targetInput" required><input type="hidden" id="nikInput">
                    <div class="prem-footer" style="margin-top:16px;"><button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button><button type="submit" class="prem-btn-save">Simpan</button></div>
                </form>
            </div>
        </div></div>
    </div>

    <!-- Modal: Fee -->
    <div class="modal fade prem-modal" id="feeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kelola Fee</h5><button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button></div>
            <div class="modal-body">
                <form id="feeForm">
                    <label class="prem-label">Fee Saat Ini</label>
                    <div id="currentFeeDisplay" style="padding:10px 14px; background:#f1f5f9; border-radius:10px; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:14px;">Memuat...</div>
                    <label class="prem-label">Input Fee Baru</label><input type="text" class="prem-input" id="newFeeInput" name="new_fee" required>
                    <div class="prem-footer" style="margin-top:16px;"><button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button><button type="submit" class="prem-btn-save">Simpan</button></div>
                </form>
            </div>
        </div></div>
    </div>

    <!-- Modal: Reset Password Teknisi -->
    <div class="modal fade prem-modal" id="resetPwModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                <h5 class="modal-title" style="color:#fff;"><i class="fa-solid fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="font-size:10px;"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:12px;color:#64748b;margin-bottom:14px;">Reset password untuk: <strong id="resetPwName" style="color:#1e293b;"></strong></p>
                <form id="resetPwForm">
                    <input type="hidden" id="resetPwTeknisiId">
                    <label class="prem-label">Password Baru</label>
                    <div style="position:relative;">
                        <input type="password" class="prem-input" id="resetPwInput" placeholder="Minimal 6 karakter" required minlength="6" style="padding-right:40px;">
                        <button type="button" id="togglePwBtn" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;" onclick="togglePwVisibility()"><i class="fa-solid fa-eye"></i></button>
                    </div>
                    <div class="prem-footer" style="margin-top:16px;">
                        <button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="prem-btn-save" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">Reset Password</button>
                    </div>
                </form>
            </div>
        </div></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
    <script>
        let technicianChart, donutChart, currentTechnicianData = [], prevMonthData = null, compareMode = false, currentPerfFilter = 'all', grandTotalPendapatan = 0, prevGrandTotalPendapatan = 0;
        let currentDateStart = '', currentDateEnd = '';
        let rangeMonths = 1;
        const bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        function setRange(months, btn) {
            rangeMonths = months;
            document.querySelectorAll('.tek-range-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            updateRangeInfo();
        }

        function updateRangeInfo() {
            const info = document.getElementById('rangeInfo');
            if (rangeMonths <= 1) { info.classList.remove('show'); return; }
            const sel = document.getElementById('filterMonth').value;
            const [y, m] = sel.split('-').map(Number);
            // Calculate start month (go back rangeMonths-1)
            const startDate = new Date(y, m - rangeMonths, 1);
            const startM = startDate.getMonth() + 1;
            const startY = startDate.getFullYear();
            info.textContent = `📅 ${bulanNames[startM]} ${startY} — ${bulanNames[m]} ${y}`;
            info.classList.add('show');
        }
        const formatRupiah = angka => 'Rp ' + (angka ? parseInt(angka).toLocaleString('id-ID') : '0');
        const shortRupiah = angka => {
            if (!angka) return 'Rp 0';
            const n = parseInt(angka);
            if (n >= 1000000) return 'Rp ' + (n/1000000).toFixed(1).replace('.0','') + 'jt';
            if (n >= 1000) return 'Rp ' + (n/1000).toFixed(0) + 'rb';
            return 'Rp ' + n;
        };

        function toggleCompare() {
            compareMode = !compareMode;
            document.getElementById('compareToggle').classList.toggle('active', compareMode);
            fetchDataAndUpdate();
        }

        function getPrevMonth(dateStr) {
            const [y, m] = dateStr.split('-').map(Number);
            const d = new Date(y, m - 2, 1);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Bar chart
            const ctx = document.getElementById('technicianChart').getContext('2d');
            technicianChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: [], datasets: [
                    { label: 'Target', data: [], backgroundColor: 'rgba(148,163,184,0.3)', borderColor: '#94a3b8', borderWidth: 1, borderRadius: 6 },
                    { label: 'Pendapatan', data: [], backgroundColor: 'rgba(245,158,11,0.7)', borderColor: '#f59e0b', borderWidth: 1, borderRadius: 6 },
                    { label: 'Bonus', data: [], backgroundColor: 'rgba(34,197,94,0.7)', borderColor: '#22c55e', borderWidth: 1, borderRadius: 6 }
                ]},
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { font: { size: 10, weight: '600' }, usePointStyle: true, pointStyle: 'rectRounded' } } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: v => shortRupiah(v), font: { size: 10 } } }, x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 45 } } }
                }
            });

            // Donut chart
            const dctx = document.getElementById('donutChart').getContext('2d');
            donutChart = new Chart(dctx, {
                type: 'doughnut',
                data: { labels: ['Achieved', 'Below'], datasets: [{ data: [0, 100], backgroundColor: ['#22c55e', '#f1f5f9'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });

            document.getElementById('filterBtn').addEventListener('click', fetchDataAndUpdate);
            fetchDataAndUpdate();
        });

        async function fetchDataAndUpdate() {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            const selectedDate = document.getElementById('filterMonth').value;
            const [selY, selM] = selectedDate.split('-').map(Number);

            // Calculate date range based on rangeMonths
            let dateStart, dateEnd;
            if (rangeMonths > 1) {
                const startDate = new Date(selY, selM - rangeMonths, 1);
                dateStart = startDate.getFullYear() + '-' + String(startDate.getMonth() + 1).padStart(2, '0');
                dateEnd = selectedDate;
            } else {
                dateStart = selectedDate;
                dateEnd = selectedDate;
            }

            currentDateStart = dateStart;
            currentDateEnd = dateEnd;

            // Update period badge
            if (rangeMonths > 1) {
                const [sY, sM] = dateStart.split('-').map(Number);
                document.getElementById('report-period').textContent = `${bulanNames[sM]} ${sY} — ${bulanNames[selM]} ${selY} (${rangeMonths} Bulan)`;
            } else {
                document.getElementById('report-period').textContent = `${bulanNames[selM]} ${selY}`;
            }
            updateRangeInfo();

            tableBody.innerHTML = '<tr><td colspan="8"><div class="spinner-box"><div class="spinner-ring"></div></div></td></tr>';
            tableFooter.innerHTML = '';

            try {
                const fetchUrl = `get_teknisi_data.php?date=${dateStart}&date_end=${dateEnd}`;
                const prevUrl = compareMode
                    ? (rangeMonths > 1
                        ? (() => { const ps = new Date(selY, selM - rangeMonths * 2, 1); const pe = new Date(selY, selM - rangeMonths, 0); return `get_teknisi_data.php?date=${ps.getFullYear()}-${String(ps.getMonth()+1).padStart(2,'0')}&date_end=${pe.getFullYear()}-${String(pe.getMonth()+1).padStart(2,'0')}`; })()
                        : `get_teknisi_data.php?date=${getPrevMonth(selectedDate)}`)
                    : null;

                const [response, prevResponse] = await Promise.all([
                    fetch(fetchUrl),
                    prevUrl ? fetch(prevUrl) : Promise.resolve(null)
                ]);
                const data = await response.json();
                prevMonthData = prevResponse ? await prevResponse.json() : null;
                currentTechnicianData = data.tableData;
                grandTotalPendapatan = data.grandTotalPendapatan || 0;
                prevGrandTotalPendapatan = prevMonthData?.grandTotalPendapatan || 0;
                updateStats(data.tableData, prevMonthData?.tableData);
                updateTable(data.tableData);
                updateChart(data.chartData);
                updateDonut(data.tableData);
                updateTopPerformers(data.tableData);
                currentPerfFilter = 'all';
                document.querySelectorAll('.perf-tab').forEach(t => t.classList.remove('active'));
                document.querySelector('.perf-tab[data-filter="all"]').classList.add('active');
            } catch (error) { tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:40px; color:#ef4444;">Gagal memuat data.</td></tr>'; }
        }

        function filterPerf(type, btn) {
            currentPerfFilter = type;
            document.querySelectorAll('.perf-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            updateTable(currentTechnicianData);
        }

        function classifyPerf(row) {
            const total = parseFloat(row.total_pendapatan || 0) + parseFloat(row.total_fee || 0);
            const target = parseFloat(row.target || 0);
            const kegiatan = parseInt(row.jumlah_kegiatan || 0);
            if (kegiatan === 0) return 'zero';
            if (target > 0 && total >= target) return 'good';
            return 'bad';
        }

        function updateStats(data, prevData) {
            const totalTeknisi = data.length;
            const totalBonus = data.reduce((s, r) => s + parseFloat(r.bonus), 0);
            let achieveCount = 0;
            data.forEach(r => { if (parseFloat(r.target) > 0 && (parseFloat(r.total_pendapatan) + parseFloat(r.total_fee)) >= parseFloat(r.target)) achieveCount++; });
            const achieveRate = totalTeknisi > 0 ? Math.round((achieveCount / totalTeknisi) * 100) : 0;

            document.getElementById('statTeknisi').textContent = totalTeknisi;
            document.getElementById('statPendapatan').textContent = shortRupiah(grandTotalPendapatan);
            document.getElementById('statBonus').textContent = shortRupiah(totalBonus);
            document.getElementById('statAchieve').textContent = achieveRate + '%';

            // Compare diff
            if (prevData) {
                const prevBonus = prevData.reduce((s, r) => s + parseFloat(r.bonus), 0);
                const diffP = grandTotalPendapatan - prevGrandTotalPendapatan;
                const diffB = totalBonus - prevBonus;
                document.getElementById('statPendapatanDiff').innerHTML = diffP >= 0
                    ? `<i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> +${shortRupiah(diffP)} vs bulan lalu`
                    : `<i class="fa-solid fa-arrow-down" style="font-size:9px;"></i> ${shortRupiah(diffP)} vs bulan lalu`;
                document.getElementById('statPendapatanDiff').className = 'tek-stat-sub ' + (diffP >= 0 ? 'up' : 'down');
                document.getElementById('statBonusDiff').innerHTML = diffB >= 0
                    ? `<i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> +${shortRupiah(diffB)} vs bulan lalu`
                    : `<i class="fa-solid fa-arrow-down" style="font-size:9px;"></i> ${shortRupiah(diffB)} vs bulan lalu`;
                document.getElementById('statBonusDiff').className = 'tek-stat-sub ' + (diffB >= 0 ? 'up' : 'down');
            } else {
                document.getElementById('statPendapatanDiff').innerHTML = '';
                document.getElementById('statBonusDiff').innerHTML = '';
            }
        }

        function updateDonut(data) {
            let above = 0, below = 0;
            data.forEach(r => {
                const total = parseFloat(r.total_pendapatan) + parseFloat(r.total_fee);
                const target = parseFloat(r.target);
                if (target > 0) { total >= target ? above++ : below++; }
            });
            const pct = (above + below) > 0 ? Math.round((above / (above + below)) * 100) : 0;
            const gradient1 = donutChart.ctx.createLinearGradient(0, 0, 100, 100);
            gradient1.addColorStop(0, '#22c55e'); gradient1.addColorStop(1, '#16a34a');
            donutChart.data.datasets[0].data = [above, below];
            donutChart.data.datasets[0].backgroundColor = [gradient1, '#f1f5f9'];
            donutChart.data.datasets[0].borderWidth = 0;
            donutChart.update();
            document.getElementById('donutPct').textContent = pct + '%';
            document.getElementById('donutLegend').innerHTML = `
                <div class="donut-legend-item">
                    <div class="donut-legend-dot" style="background:#22c55e;"></div>
                    <span class="donut-legend-text">Tercapai <span class="donut-legend-num">${above}</span></span>
                </div>
                <div class="donut-legend-item">
                    <div class="donut-legend-dot" style="background:#e2e8f0;"></div>
                    <span class="donut-legend-text">Belum <span class="donut-legend-num">${below}</span></span>
                </div>`;
        }

        function updateTopPerformers(data) {
            const sorted = [...data].sort((a, b) => {
                const tA = parseFloat(a.total_pendapatan) + parseFloat(a.total_fee);
                const tB = parseFloat(b.total_pendapatan) + parseFloat(b.total_fee);
                return tB - tA;
            }).slice(0, 5);
            const list = document.getElementById('topList');
            if (sorted.length === 0) { list.innerHTML = '<li style="text-align:center; padding:20px; color:#94a3b8; font-size:12px;">Tidak ada data</li>'; return; }
            const medals = ['🥇', '🥈', '🥉'];
            const tierClass = ['gold', 'silver', 'bronze'];
            list.innerHTML = sorted.map((r, i) => {
                const total = parseFloat(r.total_pendapatan) + parseFloat(r.total_fee);
                const target = parseFloat(r.target || 0);
                const achPct = target > 0 ? Math.round((total / target) * 100) : 0;
                const achClass = total >= target && target > 0 ? 'above' : 'below';
                const rankClass = i < 3 ? 'r' + (i+1) : '';
                const itemClass = i < 3 ? tierClass[i] : '';
                return `<li class="top-item ${itemClass}">
                    <span class="top-rank ${rankClass}">${i < 3 ? medals[i] : (i+1)}</span>
                    <div class="top-info">
                        <div class="top-name">${r.nama}</div>
                        <div class="top-sub"><i class="fa-solid fa-briefcase" style="font-size:8px;"></i> ${r.jumlah_kegiatan} kegiatan</div>
                    </div>
                    <div class="top-right">
                        <span class="top-val">${shortRupiah(total)}</span>
                        ${target > 0 ? `<span class="top-achieve ${achClass}">${achPct}%</span>` : ''}
                    </div>
                </li>`;
            }).join('');
        }

        function updateTable(data) {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            tableBody.innerHTML = ''; tableFooter.innerHTML = '';

            // Classify all
            const counts = { all: data.length, good: 0, bad: 0, zero: 0 };
            const badNames = [];
            data.forEach(r => {
                const perf = classifyPerf(r);
                counts[perf]++;
                if (perf === 'bad') badNames.push(r.nama);
            });
            document.getElementById('perfAll').textContent = counts.all;
            document.getElementById('perfGood').textContent = counts.good;
            document.getElementById('perfBad').textContent = counts.bad;
            document.getElementById('perfZero').textContent = counts.zero;

            // Alert
            const alert = document.getElementById('perfAlert');
            if (currentPerfFilter === 'bad' && badNames.length > 0) {
                document.getElementById('perfAlertText').textContent = `${badNames.length} teknisi di bawah target: ${badNames.join(', ')}`;
                alert.classList.add('show');
            } else {
                alert.classList.remove('show');
            }

            // Filter
            const filtered = currentPerfFilter === 'all' ? data : data.filter(r => classifyPerf(r) === currentPerfFilter);

            if (filtered.length === 0) { tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:60px; color:#94a3b8;"><i class="fa-solid fa-user-slash" style="font-size:32px; display:block; margin-bottom:8px;"></i>Tidak ada data untuk filter ini.</td></tr>'; return; }
            let totalPendapatan = 0, totalBonus = 0, totalFee = 0;
            const [eY, eM] = currentDateEnd.split('-').map(Number);
            const lastDay = new Date(eY, eM, 0).getDate();
            const start_date = `${currentDateStart}-01`;
            const end_date = `${currentDateEnd}-${String(lastDay).padStart(2,'0')}`;

            filtered.forEach(row => {
                totalPendapatan += parseFloat(row.total_pendapatan);
                totalBonus += parseFloat(row.bonus);
                totalFee += parseFloat(row.total_fee);
                const totalEarning = parseFloat(row.total_pendapatan || 0) + parseFloat(row.total_fee || 0);
                const target = parseFloat(row.target || 0);
                const achievePct = target > 0 ? Math.round((totalEarning / target) * 100) : 0;
                const achieveClass = (totalEarning >= target && target > 0) ? 'above' : 'below';
                const barW = Math.min(100, achievePct);
                tableBody.innerHTML += `<tr>
                    <td style="padding-left:24px;">
                        <a class="tek-name-link" href="list-kegiatan-teknisi.php?start_date=${start_date}&end_date=${end_date}&idTek=${row.id}">${row.nama}</a>
                        <div class="tek-nik">NIK: ${row.nik}</div>
                    </td>
                    <td style="text-align:center;"><span class="tek-val v-count">${row.jumlah_kegiatan}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-target">${formatRupiah(row.target)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-fee">${formatRupiah(row.total_fee)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-pendapatan">${formatRupiah(row.total_pendapatan)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-bonus">${formatRupiah(row.bonus)}</span></td>
                    <td style="text-align:center;">
                        ${target > 0 ? `<div style="display:flex;flex-direction:column;align-items:center;gap:3px;"><span class="tek-achievement ${achieveClass}">${achievePct}%</span><div style="width:50px;height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden;"><div style="height:100%;width:${barW}%;background:${achieveClass==='above'?'#22c55e':'#f59e0b'};border-radius:2px;"></div></div></div>` : '<span style="color:#cbd5e1;">-</span>'}
                    </td>
                    <td style="text-align:center;" class="no-print">
                        <div style="display:flex; gap:4px; justify-content:center;">
                            <button class="tek-act-btn tek-act-edit" onclick='openTargetModal(${JSON.stringify(row.nik)}, ${JSON.stringify(row.target)})' title="Set Target"><i class="fa-solid fa-pen"></i></button>
                            <button class="tek-act-btn" style="background:#eef2ff;color:#6366f1;" onclick='openResetPwModal(${row.id}, ${JSON.stringify(row.nama)})' title="Reset Password"><i class="fa-solid fa-key"></i></button>
                            <button class="tek-act-btn tek-act-del" onclick='softDeleteTeknisi(${row.id})' title="Nonaktifkan"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            });
            // Use grandTotalPendapatan for footer when showing all data, else sum filtered
            const footerPendapatan = (currentPerfFilter === 'all') ? grandTotalPendapatan : totalPendapatan;
            tableFooter.innerHTML = `<tr>
                <td style="text-align:right; padding-left:24px; font-weight:800;">Total</td><td></td><td></td>
                <td style="text-align:center;"><span class="tek-val v-fee">${formatRupiah(totalFee)}</span></td>
                <td style="text-align:center;"><span class="tek-val v-pendapatan">${formatRupiah(footerPendapatan)}</span></td>
                <td style="text-align:center;"><span class="tek-val v-bonus">${formatRupiah(totalBonus)}</span></td>
                <td></td><td class="no-print"></td>
            </tr>`;
        }

        function exportToXls(filename) {
            if (currentTechnicianData.length === 0) { alert("Tidak ada data untuk diekspor."); return; }
            const month = document.getElementById('report-period').textContent;
            let totalPendapatan = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.total_pendapatan), 0);
            let totalBonus = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.bonus), 0);
            let totalFee = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.total_fee), 0);
            let tableHtml = `<html><head><style>table, th, td {border: 1px solid black; border-collapse: collapse; padding: 5px;} .currency { mso-number-format: '"Rp"\\ \\#\\,\\#\\#0'; } .number { mso-number-format: '0'; } .text { mso-number-format: '@'; }</style></head><body><h3>Laporan Kinerja Teknisi</h3><p>Periode: ${month}</p><table><thead><tr><th>NIK</th><th>Nama</th><th>Jml Kegiatan</th><th>Target</th><th>Fee (Paid)</th><th>Pendapatan</th><th>Bonus</th></tr></thead><tbody>
                ${currentTechnicianData.map(row => `<tr><td class="text">'${row.nik}</td><td>${row.nama}</td><td class="number">${row.jumlah_kegiatan}</td><td class="currency">${Math.round(row.target)}</td><td class="currency">${Math.round(row.total_fee)}</td><td class="currency">${Math.round(row.total_pendapatan)}</td><td class="currency">${Math.round(row.bonus)}</td></tr>`).join('')}
                <tr><td colspan="4" style="font-weight:bold;text-align:right;">Total</td><td class="currency" style="font-weight:bold;">${Math.round(totalFee)}</td><td class="currency" style="font-weight:bold;">${Math.round(totalPendapatan)}</td><td class="currency" style="font-weight:bold;">${Math.round(totalBonus)}</td></tr>
            </tbody></table></body></html>`;
            const link = document.createElement("a"); link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(tableHtml); link.download = filename; link.click();
        }

        function updateChart(data) { technicianChart.data.labels = data.labels; technicianChart.data.datasets[0].data = data.targets; technicianChart.data.datasets[1].data = data.pendapatan; technicianChart.data.datasets[2].data = data.bonus; technicianChart.update(); }
        function openTargetModal(nik, currentTarget) { document.getElementById('nikInput').value = nik; document.getElementById('targetInput').value = currentTarget ? parseInt(currentTarget).toLocaleString('id-ID') : ''; $('#targetModal').modal('show'); }
        function softDeleteTeknisi(id) { if (!confirm("Anda yakin ingin menonaktifkan teknisi ini?")) return; $.ajax({ url: 'soft_delete_teknisi.php', type: 'POST', data: { id: id }, success: function(response) { if (response.trim() === "success") { fetchDataAndUpdate(); } else { alert("Gagal menonaktifkan teknisi."); } }, error: function() { alert("Terjadi kesalahan koneksi."); } }); }
        document.getElementById('targetInput').addEventListener('input', e => { let v = e.target.value.replace(/[^0-9]/g, ''); e.target.value = v ? parseInt(v, 10).toLocaleString('id-ID') : ''; });
        $('#targetForm').on('submit', function(e) { e.preventDefault(); let target = $('#targetInput').val().replace(/[^\d]/g, ''); let nik = $('#nikInput').val(); $.ajax({ url: 'update_target.php', type: 'POST', data: { target: target, nik: nik }, success: () => { $('#targetModal').modal('hide'); fetchDataAndUpdate(); }, error: () => alert('Gagal update.') }); });

        let currentFeeValue = 0;
        async function openFeeModal() {
            document.getElementById('currentFeeDisplay').textContent = 'Memuat...';
            try { const response = await fetch('get_current_fee.php'); const data = await response.json(); if(data.success && data.fee !== null) { currentFeeValue = data.fee; document.getElementById('currentFeeDisplay').textContent = formatRupiah(currentFeeValue); } else { document.getElementById('currentFeeDisplay').textContent = 'Belum diatur'; } } catch (error) { document.getElementById('currentFeeDisplay').textContent = 'Gagal memuat'; }
            $('#feeModal').modal('show');
        }
        document.getElementById('newFeeInput').addEventListener('input', e => { let v = e.target.value.replace(/[^0-9]/g, ''); e.target.value = v ? parseInt(v, 10).toLocaleString('id-ID') : ''; });
        $('#feeForm').on('submit', function(e) { e.preventDefault(); let newFee = $('#newFeeInput').val().replace(/[^\d]/g, ''); $.ajax({ url: 'proses_update_fee.php', type: 'POST', data: { new_fee: newFee }, dataType: 'json', success: function(response) { if(response.success) { alert('Fee berhasil diperbarui!'); $('#feeModal').modal('hide'); } else { alert('Gagal memperbarui fee: ' + response.message); } }, error: function() { alert('Terjadi kesalahan koneksi.'); } }); });

        // Reset Password Teknisi
        function openResetPwModal(teknisiId, nama) {
            document.getElementById('resetPwTeknisiId').value = teknisiId;
            document.getElementById('resetPwName').textContent = nama;
            document.getElementById('resetPwInput').value = '';
            $('#resetPwModal').modal('show');
        }
        function togglePwVisibility() {
            const input = document.getElementById('resetPwInput');
            const icon = document.querySelector('#togglePwBtn i');
            if (input.type === 'password') { input.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; }
            else { input.type = 'password'; icon.className = 'fa-solid fa-eye'; }
        }
        $('#resetPwForm').on('submit', function(e) {
            e.preventDefault();
            const teknisiId = $('#resetPwTeknisiId').val();
            const newPw = $('#resetPwInput').val();
            if (newPw.length < 6) { alert('Password minimal 6 karakter.'); return; }
            if (!confirm('Yakin reset password teknisi ini?')) return;
            $.ajax({
                url: 'reset_password_teknisi.php',
                type: 'POST',
                data: { teknisi_id: teknisiId, new_password: newPw },
                dataType: 'json',
                success: function(res) {
                    if (res.success) { alert('Password berhasil direset!'); $('#resetPwModal').modal('hide'); }
                    else { alert('Gagal: ' + res.message); }
                },
                error: function(xhr, status, error) { alert('Error [' + xhr.status + ']: ' + (xhr.responseText || error || 'Tidak ada response')); }
            });
        });
    </script>
</body>
</html>