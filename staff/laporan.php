<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Target Tercapai";
$currentPage = "Today";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    
    @media print {
        .no-print, .sidenav, .navbar, .fixed-plugin { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; }
    }
    <?php include "css/floating-menu2.css";?>
    /* Action bar */
    .action-bar {
        display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
        margin-bottom: 24px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .action-btn {
        padding: 12px 24px; border: none; border-radius: 12px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.01em;
    }
    .action-btn:hover { 
        transform: translateY(-2px); 
    }
    .btn-detail { 
        background: linear-gradient(135deg, #0f172a, #1e293b); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(15,23,42,0.15); 
    }
    .btn-detail:hover { 
        box-shadow: 0 6px 20px rgba(15,23,42,0.25); 
        color: #fff;
    }
    .btn-print { 
        background: linear-gradient(135deg, #0284c7, #0369a1); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(2,132,199,0.15); 
    }
    .btn-print:hover { 
        box-shadow: 0 6px 20px rgba(2,132,199,0.25); 
        color: #fff;
    }
    .btn-lengkap { 
        background: linear-gradient(135deg, #059669, #047857); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(5,150,105,0.15); 
    }
    .btn-lengkap:hover { 
        box-shadow: 0 6px 20px rgba(5,150,105,0.25); 
        color: #fff;
    }
    .btn-validasi { 
        background: linear-gradient(135deg, #4f46e5, #4338ca); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(79,70,229,0.15); 
        margin-left: auto; 
    }
    .btn-validasi:hover { 
        box-shadow: 0 6px 20px rgba(79,70,229,0.25); 
        color: #fff;
    }
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php
    include "cek-menu.php";
    $filterBulan = $_GET['bulan'] ?? ''; // format: "2026-06", "2026-04_3", or "2026-02_to_2026-04"
    $filterTeknisiId = intval($_GET['ftek'] ?? 0);

    // Parse filter: determine current_date and period
    if (!empty($filterBulan)) {
        if (str_contains($filterBulan, '_to_')) {
            $filterPeriode = 'custom';
            $parts = explode('_to_', $filterBulan);
            $current_date = $parts[1] ?? date("Y-m"); // end month for general fallback
        } elseif (str_contains($filterBulan, '_3')) {
            $filterPeriode = '3';
            $current_date = str_replace('_3', '', $filterBulan); // keep as start month for date calc
            $endDt = new DateTime($current_date . '-01');
            $endDt->modify('+2 months');
            $current_date = $endDt->format('Y-m'); // set to end month
        } else {
            $filterPeriode = '1';
            $current_date = $filterBulan;
        }
    } else {
        $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
        $filterPeriode = '1';
    }

    // Build month options for last 18 months
    $namaBulanList = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthOptions = [];
    for ($i = 0; $i < 18; $i++) {
        $dt = new DateTime();
        $dt->modify("-$i months");
        $val = $dt->format('Y-m');
        $bln = intval($dt->format('m'));
        $thn = $dt->format('Y');
        $monthOptions[] = ['value' => $val, 'label' => $namaBulanList[$bln] . ' ' . $thn];
    }

    // Build teknisi options
    $tekOptions = [];
    $resTekOpt = mysqli_query($conn, "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
    while ($rTekOpt = mysqli_fetch_assoc($resTekOpt)) $tekOptions[] = $rTekOpt;
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="action-bar no-print">
                <a href="detail-lap.php?cariBulanTahun=<?= $current_date; ?>" class="action-btn btn-detail">
                    <i class="fa-solid fa-file-invoice"></i> Detail Invoice
                </a>
                <a href="print-laporan.php?cariBulanTahun=<?= $current_date;?>&bulan=<?= urlencode($filterBulan); ?>&ftek=<?= $filterTeknisiId; ?>" target="_blank" class="action-btn btn-print">
                    <i class="fa-solid fa-print"></i> Print Laporan
                </a>
                <button onclick="openLaporanLengkap(<?= date('m', strtotime($current_date)); ?>, <?= date('Y', strtotime($current_date)); ?>)" class="action-btn btn-lengkap">
                    <i class="fa-solid fa-file-lines"></i> Laporan Lengkap
                </button>
                <a href="generate-bonus.php?cariBulanTahun=<?= $current_date;?>" class="action-btn btn-validasi">
                    <i class="fa-solid fa-circle-check"></i> Validasi Data
                </a>
            </div>
            <div class="row">
                <?php include "laporan-db.php"; ?>
            </div>
            <?php 
            // include "floating-menu.php"; 
            include "footer.php"; ?>
        </div>
    </main>
    <!-- ═══ FULL-SCREEN MODAL: LAPORAN LENGKAP ═══ -->
    <div id="ll-overlay" class="ll-overlay" style="display:none;">
        <div class="ll-modal">
            <div class="ll-modal-header">
                <div class="ll-modal-title-left">
                    <div class="ll-modal-icon"><i class="fa-solid fa-chart-bar"></i></div>
                    <div>
                        <h2 id="ll-modal-title">Laporan Kegiatan Lengkap</h2>
                        <span class="ll-modal-period" id="ll-modal-period"></span>
                    </div>
                </div>
                <div class="ll-modal-actions">
                    <button onclick="printLaporanLengkap()" class="ll-btn ll-btn-print"><i class="fa-solid fa-print"></i> Cetak</button>
                    <a id="ll-export-link" href="#" target="_blank" class="ll-btn ll-btn-excel"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
                    <button onclick="closeLaporanLengkap()" class="ll-btn ll-btn-close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="ll-modal-body" id="ll-modal-body">
                <div class="ll-loading">
                    <div class="ll-spinner"></div>
                    <span>Memuat laporan...</span>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* ═══ FULL-SCREEN MODAL ═══ */
    .ll-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15,23,42,0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .ll-overlay.ll-show { opacity: 1; }
    .ll-modal {
        position: absolute; top: 0; right: 0;
        width: 85vw; max-width: 1200px;
        height: 100vh;
        background: #fff;
        box-shadow: -8px 0 32px rgba(0,0,0,0.15);
        display: flex; flex-direction: column;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .ll-overlay.ll-show .ll-modal { transform: translateX(0); }
    .ll-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
        flex-wrap: wrap;
        gap: 12px;
    }
    .ll-modal-title-left { display: flex; align-items: center; gap: 12px; }
    .ll-modal-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg,#22c55e,#16a34a);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 16px;
        box-shadow: 0 4px 12px rgba(34,197,94,0.25);
    }
    .ll-modal-header h2 {
        margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;
        letter-spacing: -0.02em;
    }
    .ll-modal-period {
        font-size: 12px; font-weight: 600; color: #16a34a;
        background: rgba(34,197,94,0.08); padding: 3px 10px;
        border-radius: 16px; border: 1px solid rgba(34,197,94,0.15);
    }
    .ll-modal-actions { display: flex; gap: 8px; align-items: center; }
    .ll-btn {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700;
        padding: 8px 14px; border-radius: 8px;
        border: none; cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
    }
    .ll-btn-print { background: #2563eb; color: #fff; box-shadow: 0 2px 6px rgba(37,99,235,0.2); }
    .ll-btn-print:hover { background: #1d4ed8; color: #fff; }
    .ll-btn-excel { background: #059669; color: #fff; box-shadow: 0 2px 6px rgba(5,150,105,0.2); }
    .ll-btn-excel:hover { background: #047857; color: #fff; }
    .ll-btn-close {
        background: #f1f5f9; color: #64748b;
        width: 36px; height: 36px;
        padding: 0; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
    }
    .ll-btn-close:hover { background: #e2e8f0; color: #1e293b; }

    .ll-modal-body {
        flex: 1; overflow-y: auto;
        padding: 20px 24px 40px;
    }

    /* Loading */
    .ll-loading {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        padding: 80px 0; color: #64748b;
        font-size: 13px; font-weight: 500;
    }
    .ll-spinner {
        width: 36px; height: 36px;
        border: 3px solid #e2e8f0;
        border-top: 3px solid #22c55e;
        border-radius: 50%;
        animation: llspin 0.8s linear infinite;
        margin-bottom: 14px;
    }
    @keyframes llspin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    /* ═══ REPORT CARD STYLES (inside modal) ═══ */
    .ll-stats {
        display: grid; grid-template-columns: repeat(4,1fr);
        gap: 12px; margin-bottom: 20px;
    }
    .ll-stat-box {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 16px 18px;
        display: flex; align-items: center; gap: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .ll-stat-dot {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; flex-shrink: 0;
    }
    .ll-dot-blue { background: #eff6ff; color: #2563eb; }
    .ll-dot-emerald { background: #ecfdf5; color: #059669; }
    .ll-dot-rose { background: #fff1f2; color: #e11d48; }
    .ll-dot-amber { background: #fffbeb; color: #d97706; }
    .ll-stat-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1px; }
    .ll-stat-num { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em; line-height: 1.1; }

    .ll-col-labels {
        display: grid; grid-template-columns: 42px 1.2fr 1fr 1.3fr;
        gap: 12px; padding: 0 18px 8px;
    }
    .ll-col-labels span {
        font-size: 10px; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.06em;
    }

    .ll-card {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 12px; margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        transition: box-shadow 0.2s, border-color 0.2s;
        overflow: hidden;
    }
    .ll-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-color: #cbd5e1; }
    .ll-card-grid {
        display: grid; grid-template-columns: 42px 1.2fr 1fr 1.3fr;
        gap: 12px; padding: 18px; align-items: start;
    }
    .ll-card.ll-lunas { border-left: 3px solid #10b981; }
    .ll-card.ll-unpaid { border-left: 3px solid #f43f5e; }
    .ll-card.ll-manual { border-left: 3px solid #f59e0b; }

    .ll-rnum {
        width: 28px; height: 28px; border-radius: 6px;
        background: #f1f5f9; color: #64748b;
        font-size: 11px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }
    .ll-cname { font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .ll-cket {
        font-size: 11.5px; color: #64748b; background: #f8fafc;
        border-radius: 6px; padding: 5px 10px; margin-bottom: 6px;
        font-style: italic; line-height: 1.5; border-left: 3px solid #e2e8f0;
    }
    .ll-ctags { display: flex; flex-wrap: wrap; gap: 8px; }
    .ll-ctag {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10.5px; color: #94a3b8; font-weight: 500;
    }
    .ll-ctag i { font-size: 10px; }
    .ll-ctag strong { color: #64748b; font-weight: 600; }

    .ll-inv {
        background: #f8fafc; border: 1px solid #f1f5f9;
        border-radius: 8px; padding: 12px 14px;
    }
    .ll-inv-lbl { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
    .ll-inv-no { font-size: 12.5px; font-weight: 700; color: #2563eb; margin-bottom: 8px; }
    .ll-inv-amount { font-size: 17px; font-weight: 800; color: #059669; letter-spacing: -0.01em; }
    .ll-inv-amtsm { font-size: 14px; font-weight: 700; color: #059669; }
    .ll-inv-notxt { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
    .ll-inv-sep { border: 0; height: 1px; background: #e2e8f0; margin: 10px 0; }

    .ll-pay-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; font-weight: 700;
        padding: 4px 10px; border-radius: 16px;
    }
    .ll-pay-lunas { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .ll-pay-belum { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
    .ll-pay-none { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 16px; }
    .ll-pay-none-gray { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 16px; }

    .ll-tek {
        background: #f8fafc; border: 1px solid #f1f5f9;
        border-radius: 8px; padding: 10px 12px; margin-bottom: 6px;
    }
    .ll-tek:last-child { margin-bottom: 0; }
    .ll-tek-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .ll-tek-nm { font-size: 12.5px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 5px; }
    .ll-tek-nm i { font-size: 12px; color: #7c3aed; }
    .ll-tek-pay {
        font-size: 11px; font-weight: 700; color: #059669;
        background: #ecfdf5; padding: 3px 9px; border-radius: 14px;
        border: 1px solid #a7f3d0;
    }
    .ll-tek-rows { padding-left: 12px; border-left: 2px solid #e2e8f0; }
    .ll-tek-row {
        display: flex; align-items: center; gap: 8px;
        padding: 3px 0; border-bottom: 1px dashed #f1f5f9;
    }
    .ll-tek-row:last-child { border-bottom: none; }
    .ll-tek-date { font-size: 10.5px; font-weight: 600; color: #64748b; min-width: 36px; }
    .ll-tek-t {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 4px;
    }
    .ll-tek-t i { font-size: 9px; }
    .ll-tek-plan { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 600; }
    .ll-tek-in { background: #ecfdf5; color: #059669; }
    .ll-tek-out { background: #fff1f2; color: #e11d48; }
    .ll-tek-none { font-size: 11px; color: #94a3b8; font-style: italic; padding: 4px 0; margin: 0; }

    .ll-lunas-overlay { position: relative; z-index: 1; }
    .ll-lunas-overlay::after {
        content: ''; position: absolute; top: 0; left: 0;
        width: 100%; height: 100%;
        background-image: url('assets/img/lunas.png');
        background-size: 40%; background-position: center;
        background-repeat: no-repeat;
        opacity: 0.08; z-index: -1; pointer-events: none;
    }

    .ll-empty {
        text-align: center; padding: 60px 20px; color: #94a3b8;
    }
    .ll-empty p { font-size: 13px; font-weight: 500; margin: 0; }

    @media (max-width: 992px) {
        .ll-modal { width: 100vw; max-width: 100vw; }
        .ll-card-grid { grid-template-columns: 1fr; gap: 12px; }
        .ll-col-labels { display: none; }
        .ll-stats { grid-template-columns: repeat(2,1fr); }
    }

    /* Print from modal */
    @media print {
        .ll-overlay { position: static !important; background: none !important; backdrop-filter: none !important; }
        .ll-modal { position: static !important; width: 100% !important; max-width: 100% !important; height: auto !important; box-shadow: none !important; transform: none !important; }
        .ll-modal-actions { display: none !important; }
        .ll-modal-header { background: #fff !important; }
        .ll-card, .ll-stat-box { box-shadow: none !important; }
        .ll-lunas-overlay::after { opacity: 0.15 !important; }
        body > *:not(#ll-overlay) { display: none !important; }
        #ll-overlay { display: block !important; }
        .sidenav, .navbar, .fixed-plugin, .main-content { display: none !important; }
    }
    </style>

    <?php include "js-include.php"; ?>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
          Scrollbar.init(document.querySelector('#sidenav-scrollbar'), { damping: '0.5' });
        }

        // ═══ LAPORAN LENGKAP MODAL ═══
        const llOverlay = document.getElementById('ll-overlay');
        const llBody = document.getElementById('ll-modal-body');
        const llPeriod = document.getElementById('ll-modal-period');
        const llExportLink = document.getElementById('ll-export-link');
        const bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        function openLaporanLengkap(bulan, tahun) {
            currentStatusFilter = 'all';
            llPeriod.textContent = bulanNames[bulan] + ' ' + tahun;
            llExportLink.href = 'export-laporan-excel.php?bulan=' + bulan + '&tahun=' + tahun;
            llBody.innerHTML = '<div class="ll-loading"><div class="ll-spinner"></div><span>Memuat laporan...</span></div>';

            llOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                requestAnimationFrame(() => { llOverlay.classList.add('ll-show'); });
            });

            fetch('ajax-laporan-lengkap.php?bulan=' + bulan + '&tahun=' + tahun)
                .then(r => r.text())
                .then(html => { llBody.innerHTML = html; })
                .catch(err => {
                    llBody.innerHTML = '<div class="ll-empty"><i class="fa-solid fa-triangle-exclamation" style="font-size:36px;opacity:0.3;margin-bottom:12px;"></i><p>Gagal memuat data: ' + err.message + '</p></div>';
                });
        }

        function closeLaporanLengkap() {
            llOverlay.classList.remove('ll-show');
            setTimeout(() => {
                llOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }

        function printLaporanLengkap() {
            window.print();
        }

        // Close on overlay click (outside modal)
        llOverlay.addEventListener('click', function(e) {
            if (e.target === llOverlay) closeLaporanLengkap();
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && llOverlay.style.display !== 'none') closeLaporanLengkap();
        });

        // ═══ FILTER LAPORAN LENGKAP ═══
        let currentStatusFilter = 'all';

        function llFilter(type, btn) {
            // Update active button
            document.querySelectorAll('.ll-filter-btn').forEach(b => b.classList.remove('ll-fbtn-active'));
            btn.classList.add('ll-fbtn-active');
            currentStatusFilter = type;
            runCombinedFilter();
        }

        function llSearchFilter() {
            runCombinedFilter();
        }

        function runCombinedFilter() {
            const searchInput = document.getElementById('ll-search-input');
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const cards = document.querySelectorAll('.ll-card');
            let visible = 0;
            let totalIncome = 0;

            cards.forEach(card => {
                // 1. Status Filter Check
                let statusMatch = false;
                if (currentStatusFilter === 'all') {
                    statusMatch = true;
                } else if (currentStatusFilter === 'lunas') {
                    statusMatch = card.classList.contains('ll-lunas');
                } else if (currentStatusFilter === 'belum') {
                    statusMatch = !card.classList.contains('ll-lunas');
                }

                // 2. Search Query Check (Customer Name, Transaction Code, Keterangan)
                let queryMatch = false;
                if (!query) {
                    queryMatch = true;
                } else {
                    const customerName = card.querySelector('.ll-cname')?.textContent.toLowerCase() || '';
                    const keterangan = card.querySelector('.ll-cket')?.textContent.toLowerCase() || '';
                    const txCode = card.querySelector('.ll-ctag strong')?.textContent.toLowerCase() || '';
                    const invoiceNo = card.querySelector('.ll-inv-no')?.textContent.toLowerCase() || '';

                    if (customerName.includes(query) || 
                        txCode.includes(query) || 
                        keterangan.includes(query) || 
                        invoiceNo.includes(query)) {
                        queryMatch = true;
                    }
                }

                // Combine conditions
                if (statusMatch && queryMatch) {
                    card.classList.remove('ll-hidden');
                    visible++;
                    const income = parseFloat(card.getAttribute('data-income') || 0);
                    totalIncome += income;
                } else {
                    card.classList.add('ll-hidden');
                }
            });

            // Update Total Income Value Card
            const totalIncomeVal = document.getElementById('ll-total-income-val');
            if (totalIncomeVal) {
                totalIncomeVal.textContent = 'Rp ' + totalIncome.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Show/hide empty state
            const noResult = document.getElementById('ll-no-result');
            if (noResult) {
                if (visible === 0) noResult.classList.add('ll-show');
                else noResult.classList.remove('ll-show');
            }
        }
    </script>
</body>
</html>