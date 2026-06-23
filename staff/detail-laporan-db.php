<?php
    if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
        $current_date = $_GET['cariBulanTahun'];
    } else {
        $current_date = date("Y-m");
    }
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    /* ═══ PREMIUM DETAIL CARD ═══ */
    .detail-card {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03), 0 2px 8px -1px rgba(0, 0, 0, 0.02);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 140px);
    }
    .detail-header { padding: 24px 24px 0; }
    .detail-title-row {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
    }
    .detail-title-left { display: flex; align-items: center; gap: 14px; }
    .detail-icon {
        width: 44px; height: 44px;
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 14px rgba(79,70,229,0.25);
    }
    .detail-icon i { color: #fff; font-size: 18px; }
    .detail-title-left h5 { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
    .detail-title-left p { margin: 2px 0 0; font-size: 12.5px; color: #64748b; font-weight: 500; }

    .detail-filter-form {
        display: flex; gap: 8px; align-items: center;
    }
    .detail-month-input {
        border: 1.5px solid #e2e8f0; border-radius: 12px;
        padding: 9px 16px; font-size: 13px; color: #1e293b;
        background: #f8fafc; font-weight: 600; transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }
    .detail-month-input:hover { border-color: #cbd5e1; }
    .detail-month-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); outline: none; background: #fff; }
    .detail-btn-cari {
        padding: 9.5px 22px; border: none; border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5, #4338ca); color: #fff;
        font-size: 13px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(79,70,229,0.2);
    }
    .detail-btn-cari:hover { transform: translateY(-1.5px); box-shadow: 0 6px 18px rgba(79,70,229,0.3); }

    /* Table Scroll Area depth-contrast background */
    .table-scroll-area {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        background: #f8fafc !important;
        padding: 16px 24px !important;
    }

    /* Table separate card list styling */
    .laporan-table { 
        width: 100%; 
        border-collapse: separate !important; 
        border-spacing: 0 10px !important; 
        background: transparent !important;
        table-layout: fixed !important; 
    }
    .laporan-table thead th {
        background: #f8fafc !important;
        border-bottom: none !important;
        padding: 10px 14px; font-size: 11px; font-weight: 800; color: #64748b;
        text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap;
        position: sticky; top: 0; z-index: 2;
    }
    .laporan-table tbody tr { 
        background: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.01) !important;
        border-radius: 12px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .laporan-table tbody tr:hover { 
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.08) !important;
    }
    .laporan-table tbody tr.hidden-row { display: none; }
    .laporan-table tbody td {
        padding: 18px 14px; font-size: 13.5px; color: #334155; vertical-align: middle;
        word-break: break-word; overflow: hidden;
        border: none !important;
        background: #ffffff !important;
    }
    .laporan-table tbody tr td:first-child {
        border-top-left-radius: 12px !important;
        border-bottom-left-radius: 12px !important;
        border-left: 4px solid transparent !important;
        transition: border-color 0.25s ease;
        padding-left: 18px;
    }
    .laporan-table tbody tr td:last-child {
        border-top-right-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
        padding-right: 18px;
    }
    .laporan-table tbody tr:hover td:first-child {
        border-left-color: #4f46e5 !important;
    }
    .cell-truncate {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; text-overflow: ellipsis; max-height: 2.8em; line-height: 1.4em;
    }
    
    /* Footer row styling */
    .laporan-footer-row { 
        background: linear-gradient(135deg, #0f172a, #1e293b) !important; 
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1) !important;
    }
    .laporan-footer-row td { 
        background: transparent !important;
        color: #fff !important; 
        font-size: 13.5px !important;
        font-weight: 800 !important;
        padding: 18px 14.5px !important;
        border: none !important; 
    }
    .laporan-footer-row td:first-child {
        border-top-left-radius: 12px !important;
        border-bottom-left-radius: 12px !important;
        padding-left: 18px;
    }
    .laporan-footer-row td:last-child {
        border-top-right-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
        padding-right: 18px;
    }

    .badge-survey-tag {
        display: inline-block; font-size: 10px; font-weight: 700;
        padding: 4px 8px; border-radius: 6px; white-space: normal;
        background: rgba(245, 158, 11, 0.08);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.15);
    }
    .surveyor-name { font-weight: 700; color: #8b5cf6; font-size: 12.5px; }
    .invoice-link { color: #4f46e5; font-weight: 800; text-decoration: none; font-size: 13.5px; }
    .invoice-link:hover { text-decoration: underline; color: #4338ca; }
    .teknisi-link a { color: #0f172a; font-weight: 700; text-decoration: none; font-size: 13px; display: inline-block; margin-bottom: 2px; }
    .teknisi-link a:hover { color: #4f46e5; text-decoration: underline; }
    .nominal-text { font-weight: 800; color: #10b981; white-space: nowrap; font-size: 13.5px; font-variant-numeric: tabular-nums; }
    .no-data-text { color: #cbd5e1; }
    .row-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 8px;
        background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 800;
        border: 1px solid #e2e8f0;
    }

    /* Filter bar */
    .detail-filter-bar {
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px;
        padding: 16px 24px; background: #ffffff; 
        border-top: 1px solid rgba(226, 232, 240, 0.8);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-group label {
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.08em; color: #64748b; margin: 0;
    }
    .filter-select {
        padding: 9px 36px 9px 14px; border: 1.5px solid #e2e8f0;
        border-radius: 12px; font-size: 13px; color: #1e293b; font-weight: 600;
        background-color: #fff; -webkit-appearance: none; appearance: none;
        cursor: pointer; min-width: 160px; transition: all 0.2s ease;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 12px;
    }
    .filter-select:hover { border-color: #cbd5e1; }
    .filter-select:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
    
    .filter-search {
        padding: 9px 14px 9px 38px; border: 1.5px solid #e2e8f0;
        border-radius: 12px; font-size: 13px; color: #1e293b; font-weight: 600;
        background: #fff; min-width: 240px; transition: all 0.2s ease;
    }
    .filter-search:hover { border-color: #cbd5e1; }
    .filter-search:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
    
    .filter-search-wrap { position: relative; flex: 1; min-width: 220px; }
    .filter-search-wrap i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 13px; color: #94a3b8; pointer-events: none;
    }

    /* Pill Buttons styling */
    .pill-group { 
        display: flex; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 12px; border: none;
    }
    .pill-btn {
        padding: 7px 16px; font-size: 12.5px; font-weight: 700;
        border: none; background: transparent; color: #64748b;
        cursor: pointer; transition: all 0.2s ease; white-space: nowrap;
        border-radius: 8px;
    }
    .pill-btn:hover { color: #0f172a; }
    .pill-btn.active { background: #ffffff; color: #4f46e5; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

    /* Summary cards row */
    .detail-summary-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        padding: 20px 24px;
    }
    .detail-summary-card {
        padding: 20px 24px; 
        border-radius: 16px;
        display: flex; 
        flex-direction: column; 
        gap: 6px;
        position: relative;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .detail-summary-card:hover {
        transform: translateY(-4px);
    }
    .detail-summary-card .ds-label { 
        font-size: 11px; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.08em; 
    }
    .detail-summary-card .ds-value { 
        font-size: 22px; 
        font-weight: 800; 
        letter-spacing: -0.02em; 
    }
    .ds-card-icon {
        position: absolute;
        right: 20px;
        bottom: -8px;
        font-size: 56px;
        transition: all 0.3s ease;
        pointer-events: none;
    }
    .detail-summary-card:hover .ds-card-icon {
        transform: scale(1.1) rotate(-5deg);
    }

    .ds-invoice { 
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        color: #1e3a8a;
    }
    .ds-invoice .ds-label { color: #1d4ed8; }
    .ds-invoice .ds-value { color: #1e40af; }
    .ds-invoice .ds-card-icon { color: rgba(59, 130, 246, 0.15); }
    .ds-invoice:hover {
        box-shadow: 0 12px 24px -10px rgba(59, 130, 246, 0.25);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .ds-survey { 
        background: #fff7ed;
        border-left: 4px solid #f97316;
        color: #7c2d12;
    }
    .ds-survey .ds-label { color: #c2410c; }
    .ds-survey .ds-value { color: #9a3412; }
    .ds-survey .ds-card-icon { color: rgba(249, 115, 22, 0.15); }
    .ds-survey:hover {
        box-shadow: 0 12px 24px -10px rgba(249, 115, 22, 0.25);
        border-color: rgba(249, 115, 22, 0.3);
    }

    .ds-nominal-invoice { 
        background: #f0fdf4;
        border-left: 4px solid #10b981;
        color: #14532d;
    }
    .ds-nominal-invoice .ds-label { color: #15803d; }
    .ds-nominal-invoice .ds-value { color: #166534; }
    .ds-nominal-invoice .ds-card-icon { color: rgba(16, 185, 129, 0.15); }
    .ds-nominal-invoice:hover {
        box-shadow: 0 12px 24px -10px rgba(16, 185, 129, 0.25);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .ds-nominal { 
        background: #faf5ff;
        border-left: 4px solid #a855f7;
        color: #581c87;
    }
    .ds-nominal .ds-label { color: #7e22ce; }
    .ds-nominal .ds-value { color: #6b21a8; }
    .ds-nominal .ds-card-icon { color: rgba(168, 85, 247, 0.15); }
    .ds-nominal:hover {
        box-shadow: 0 12px 24px -10px rgba(168, 85, 247, 0.25);
        border-color: rgba(168, 85, 247, 0.3);
    }

    @media (max-width: 1200px) {
        .detail-summary-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .detail-filter-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 12px 16px; }
        .filter-select, .filter-search { min-width: unset; width: 100%; }
        .filter-search-wrap { min-width: unset; }
        .pill-group { width: 100%; }
        .pill-btn { flex: 1; text-align: center; }
        .detail-summary-row { padding: 12px 16px; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .detail-summary-card { padding: 16px; }
        .table-scroll-area { padding: 12px 16px !important; }
        .laporan-table thead { display: none; }
        .laporan-table tbody tr {
            display: block; margin-bottom: 12px; padding: 14px;
            background: #fff !important; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important; border: 1px solid #e2e8f0 !important;
        }
        .laporan-table tbody td {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px solid #f1f5f9 !important;
            background: transparent !important;
        }
        .laporan-table tbody td::before {
            content: attr(data-label);
            font-weight: 700; font-size: 11px; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.04em; flex: 0 0 40%;
            text-align: left;
        }
        .laporan-table tbody td:last-child { border-bottom: none !important; }
        .laporan-table tbody tr td:first-child { border-left: none !important; padding-left: 0; }
        .laporan-table tbody tr td:last-child { padding-right: 0; }
    }
    @media (max-width: 480px) {
        .detail-summary-row { grid-template-columns: 1fr; }
    }
</style>

<?php
    $tomorrow_date = date("Y-m-d", strtotime("+1 day"));
    $current_time = date("H:i:s");
    
    // Calculate month range for index-friendly filtering
    $monthStart = $current_date . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    $sql = "SELECT pk.*, 
                   GROUP_CONCAT(DISTINCT CONCAT('<a href=\"detail-lap-tek.php?cariBulanTahun=$current_date&idTek=', t.id, '\">', t.nama, '</a>') SEPARATOR '<br>') AS nama_teknisi,
                   GROUP_CONCAT(DISTINCT t.nama SEPARATOR ', ') AS nama_teknisi_plain,
                   k.customer_id, 
                   c.nama AS nama_cust,
                   k.lunas,
                   sv.keterangan_survey,
                   sv.surveyor,
                   counts.tek_count,
                   counts.uniq_tek_count
            FROM pendapatan_kegiatan pk
            JOIN kegiatan k ON k.kode = pk.kode
            JOIN customer c ON c.id = k.customer_id
            JOIN teknisi t ON t.id = pk.teknisi_id
            JOIN (
                SELECT kode, 
                       COUNT(*) as tek_count,
                       COUNT(DISTINCT teknisi_id) as uniq_tek_count
                FROM pendapatan_kegiatan
                WHERE deleted_at IS NULL
                GROUP BY kode
            ) counts ON pk.kode = counts.kode
            LEFT JOIN (
                SELECT k2.kode,
                       GROUP_CONCAT(DISTINCT CONCAT(UPPER(k2.kegiatan), ' - ', DATE_FORMAT(k2.jadwal, '%d/%m/%Y')) SEPARATOR ', ') AS keterangan_survey,
                       GROUP_CONCAT(DISTINCT t2.nama SEPARATOR ', ') AS surveyor
                FROM kegiatan k2
                LEFT JOIN team_kegiatan tk3 ON tk3.kegiatan_id = k2.id AND tk3.deleted_at IS NULL
                LEFT JOIN teknisi t2 ON t2.id = tk3.teknisi_id
                WHERE LOWER(k2.kegiatan) = 'survey'
                GROUP BY k2.kode
            ) sv ON sv.kode = pk.kode
            WHERE pk.deleted_at IS NULL
            AND pk.tanggal >= '$monthStart' AND pk.tanggal <= '$monthEnd 23:59:59'
            GROUP BY pk.kode
            ORDER BY pk.tanggal ASC";
            
    $result = mysqli_query($conn, $sql);
    $totalNominalAll = 0;
    $totalShareAll = 0;
    $rowNum = 0;
    $totalSurvey = 0;
    $allRows = [];
    $teknisiList = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Calculate total share for this invoice to match monthly report
            $tek_count = intval($row['tek_count'] ?? 1);
            if ($tek_count <= 0) $tek_count = 1;
            $share_amount = round($row['nominal_invoice'] / $tek_count);
            $row['total_share'] = $tek_count * $share_amount;

            $allRows[] = $row;
            $totalNominalAll += $row['nominal_invoice'];
            $totalShareAll += $row['total_share'];

            if (!empty($row['keterangan_survey'])) $totalSurvey++;
            $names = explode(', ', $row['nama_teknisi_plain']);
            foreach ($names as $n) {
                $n = trim($n);
                if ($n && !in_array($n, $teknisiList)) $teknisiList[] = $n;
            }
        }
    }
    sort($teknisiList);

    $daftar_bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($current_date);
    $bulan = $daftar_bulan[(int)date('m', $timestamp)];
    $tahun_display = date('Y', $timestamp);
?>

<div class="col-lg-12" id="printable-content">
    <div class="detail-card">
        <!-- Header -->
        <div class="detail-header">
            <div class="detail-title-row">
                <div class="detail-title-left">
                    <div class="detail-icon">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h5>Detail Invoice</h5>
                        <p><?= $bulan . ' ' . $tahun_display ?></p>
                    </div>
                </div>
                <form method="GET" action="" class="detail-filter-form no-print">
                    <input type="month" class="detail-month-input" name="cariBulanTahun" value="<?php echo $current_date;?>">
                    <button type="submit" class="detail-btn-cari">
                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="detail-summary-row">
            <div class="detail-summary-card ds-invoice">
                <span class="ds-label">Invoice</span>
                <span class="ds-value" id="stat-count"><?= count($allRows) ?></span>
                <i class="fa-solid fa-file-invoice ds-card-icon"></i>
            </div>
            <div class="detail-summary-card ds-survey">
                <span class="ds-label">Ada Survey</span>
                <span class="ds-value" id="stat-survey"><?= $totalSurvey ?></span>
                <i class="fa-solid fa-clipboard-question ds-card-icon"></i>
            </div>
            <div class="detail-summary-card ds-nominal-invoice">
                <span class="ds-label">Total Nominal Invoice</span>
                <span class="ds-value" id="stat-nominal-invoice">Rp <?= number_format($totalNominalAll, 0, ',', '.') ?></span>
                <i class="fa-solid fa-receipt ds-card-icon"></i>
            </div>
            <div class="detail-summary-card ds-nominal">
                <span class="ds-label">Total Target Tercapai</span>
                <span class="ds-value" id="stat-nominal">Rp <?= number_format($totalShareAll, 0, ',', '.') ?></span>
                <i class="fa-solid fa-wallet ds-card-icon"></i>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="detail-filter-bar no-print">
            <div class="filter-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-input" class="filter-search" placeholder="Cari invoice, teknisi, customer...">
            </div>
            <div class="filter-group">
                <label>Teknisi</label>
                <select id="filter-teknisi" class="filter-select">
                    <option value="">Semua Teknisi</option>
                    <?php foreach ($teknisiList as $tek): ?>
                        <option value="<?= htmlspecialchars($tek) ?>"><?= htmlspecialchars($tek) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Status Survey</label>
                <div class="pill-group">
                    <button type="button" class="pill-btn active" data-filter="all">Semua</button>
                    <button type="button" class="pill-btn" data-filter="survey">Ada Survey</button>
                    <button type="button" class="pill-btn" data-filter="no-survey">Tanpa Survey</button>
                </div>
            </div>
        </div>

        <!-- Table (scrollable) -->
        <div class="table-scroll-area">
            <table class="laporan-table" id="data-tek" style="min-width:900px;">
                <thead>
                    <tr>
                        <th style="width: 4%; padding-left: 18px;">#</th>
                        <th style="width: 10%;">Tgl Invoice</th>
                        <th style="width: 10%;">Tgl Lunas</th>
                        <th style="width: 13%;">No Invoice</th>
                        <th style="width: 15%;">Teknisi</th>
                        <th style="width: 20%;">Customer</th>
                        <th style="width: 14%;">Ket. Survey</th>
                        <th style="width: 11%;">Surveyor</th>
                        <th style="width: 13%; text-align: right; padding-right: 18px;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($allRows) > 0) {
                        foreach ($allRows as $row) {
                            $rowNum++;
                            $namaT = $row['nama_teknisi'];
                            $namaC = $row['nama_cust'];
                            $invoice = $row['no_invoice'];
                            $nominal = $row['nominal_invoice'];
                            $totalShare = $row['total_share'];
                            $tglInv = date('d M Y', strtotime($row['tanggal']));
                            $is_lunas = (!empty($row['lunas']) && $row['lunas'] != '0000-00-00');
                            $tglLunas = $is_lunas ? date('d M Y', strtotime($row['lunas'])) : '<span style="color:#ef4444; font-weight:600; font-size:11.5px;">Belum Lunas</span>';
                            $ketSurvey = $row['keterangan_survey'] ?? '';
                            $surveyor = $row['surveyor'] ?? '';
                            $nominalFormatted = "Rp " . number_format($nominal, 0, ',', '.');
                            $hasSurvey = !empty($ketSurvey) ? 'yes' : 'no';
                            $teknisiPlain = $row['nama_teknisi_plain'];
                    ?>
                        <tr data-survey="<?php echo $hasSurvey; ?>" data-teknisi="<?php echo htmlspecialchars($teknisiPlain); ?>" data-nominal="<?php echo $nominal; ?>" data-share="<?php echo $totalShare; ?>">
                            <td data-label="#"><span class="row-num"><?php echo $rowNum; ?></span></td>
                            <td data-label="Tgl Invoice"><?php echo $tglInv; ?></td>
                            <td data-label="Tgl Lunas"><?php echo $tglLunas; ?></td>
                            <td data-label="No Invoice"><span class="invoice-link"><?php echo $invoice; ?></span></td>
                            <td data-label="Teknisi"><div class="cell-truncate teknisi-link"><?php echo $namaT; ?></div></td>
                            <td data-label="Customer"><div class="cell-truncate"><?php echo $namaC; ?></div></td>
                            <td data-label="Ket. Survey">
                                <?php if (!empty($ketSurvey)): ?>
                                    <div class="cell-truncate"><span class="badge-survey-tag" style="white-space:normal;"><?php echo $ketSurvey; ?></span></div>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Surveyor">
                                <?php if (!empty($surveyor)): ?>
                                    <div class="cell-truncate surveyor-name"><?php echo $surveyor; ?></div>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Nominal" style="text-align:right;">
                                <span class="nominal-text"><?php echo $nominalFormatted; ?></span>
                                <?php if (abs($totalShare - $nominal) > 10): ?>
                                    <div style="font-size:10px; color:#64748b; font-weight:600; margin-top:2px;">
                                        Porsi: Rp <?php echo number_format($totalShare, 0, ',', '.'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:#94a3b8;">
                                <div style="font-size:48px; margin-bottom:8px;">📭</div>
                                <div style="font-size:14px; font-weight:500;">Tidak ada data target tercapai untuk bulan ini</div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="laporan-footer-row">
                            <td colspan="8" style="padding-left:20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <strong>TOTAL TARGET TERCAPAI TEKNISI</strong>
                                    <span style="font-size: 11px; font-weight: 500; opacity: 0.8; margin-left: 20px;" id="footer-nominal-invoice">Total Nominal Invoice: Rp <?php echo number_format($totalNominalAll, 0, ',', '.'); ?></span>
                                </div>
                            </td>
                            <td style="text-align:right;padding-right:20px;" id="footer-total"><strong><?php echo "Rp " . number_format($totalShareAll, 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const searchInput = document.getElementById('search-input');
    const filterTeknisi = document.getElementById('filter-teknisi');
    const pillBtns = document.querySelectorAll('.pill-btn');
    const tbody = document.querySelector('#data-tek tbody');
    const rows = tbody.querySelectorAll('tr[data-survey]');
    const statCount = document.getElementById('stat-count');
    const statSurvey = document.getElementById('stat-survey');
    const statNominalInvoice = document.getElementById('stat-nominal-invoice');
    const statNominal = document.getElementById('stat-nominal');
    const footerNominalInvoice = document.getElementById('footer-nominal-invoice');
    const footerTotal = document.getElementById('footer-total');

    let surveyFilter = 'all';

    function formatRp(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const teknisi = filterTeknisi.value.toLowerCase();
        let visibleCount = 0, surveyCount = 0, totalNominal = 0, totalShare = 0;

        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            const rowSurvey = row.getAttribute('data-survey');
            const rowTeknisi = (row.getAttribute('data-teknisi') || '').toLowerCase();
            const rowNominal = parseInt(row.getAttribute('data-nominal')) || 0;
            const rowShare = parseInt(row.getAttribute('data-share')) || 0;

            let show = true;

            // Search filter
            if (search && text.indexOf(search) === -1) show = false;

            // Teknisi filter
            if (teknisi && rowTeknisi.indexOf(teknisi) === -1) show = false;

            // Survey filter
            if (surveyFilter === 'survey' && rowSurvey !== 'yes') show = false;
            if (surveyFilter === 'no-survey' && rowSurvey !== 'no') show = false;

            if (show) {
                row.classList.remove('hidden-row');
                visibleCount++;
                if (rowSurvey === 'yes') surveyCount++;
                totalNominal += rowNominal;
                totalShare += rowShare;
            } else {
                row.classList.add('hidden-row');
            }
        });

        // Update stats
        statCount.textContent = visibleCount;
        statSurvey.textContent = surveyCount;
        if (statNominalInvoice) statNominalInvoice.textContent = formatRp(totalNominal);
        if (footerNominalInvoice) footerNominalInvoice.innerHTML = 'Total Nominal Invoice: ' + formatRp(totalNominal);
        
        statNominal.textContent = formatRp(totalShare);
        footerTotal.innerHTML = '<strong>' + formatRp(totalShare) + '</strong>';
    }

    // Search
    searchInput.addEventListener('input', applyFilters);

    // Teknisi dropdown
    filterTeknisi.addEventListener('change', applyFilters);

    // Pill buttons
    pillBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            pillBtns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            surveyFilter = btn.getAttribute('data-filter');
            applyFilters();
        });
    });
})();
</script>