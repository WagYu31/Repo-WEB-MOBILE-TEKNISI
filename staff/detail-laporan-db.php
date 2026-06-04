<?php
    if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
        $current_date = $_GET['cariBulanTahun'];
    } else {
        $current_date = date("Y-m");
    }
?>
<style>
    /* ═══ PREMIUM DETAIL CARD ═══ */
    .detail-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .detail-header { padding: 24px 24px 0; }
    .detail-title-row {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 16px; margin-bottom: 20px;
    }
    .detail-title-left { display: flex; align-items: center; gap: 14px; }
    .detail-icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(99,102,241,0.25);
    }
    .detail-icon i { color: #fff; font-size: 16px; }
    .detail-title-left h5 { margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; }
    .detail-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }

    .detail-filter-form {
        display: flex; gap: 8px; align-items: center;
    }
    .detail-month-input {
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: 8px 14px; font-size: 13px; color: #1e293b;
        background: #f8fafc; font-weight: 500; transition: all 0.2s;
    }
    .detail-month-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); outline: none; background: #fff; }
    .detail-btn-cari {
        padding: 8px 20px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;
        font-size: 13px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(99,102,241,0.25);
    }
    .detail-btn-cari:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.35); }

    /* Table */
    .laporan-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .laporan-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e5e7eb;
        padding: 12px 14px; font-size: 10px; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap;
    }
    .laporan-table tbody tr { transition: all 0.15s; border-bottom: 1px solid #f1f5f9; }
    .laporan-table tbody tr:hover { background: #f8fafc; }
    .laporan-table tbody tr.hidden-row { display: none; }
    .laporan-table tbody td { padding: 12px 14px; font-size: 13px; color: #334155; vertical-align: middle; }
    .laporan-table tfoot td {
        padding: 14px; font-size: 13px; font-weight: 700; color: #fff; border: none;
    }

    /* Footer row */
    .laporan-footer-row { background: linear-gradient(135deg, #1e293b, #334155) !important; }
    .laporan-footer-row td { color: #fff !important; border: none !important; }

    .badge-survey-tag {
        display: inline-block; font-size: 10px; font-weight: 700;
        padding: 3px 8px; border-radius: 6px; white-space: nowrap;
        background: #fef3c7; color: #92400e;
    }
    .surveyor-name { font-weight: 600; color: #6366f1; font-size: 12px; }
    .invoice-link { color: #2563eb; font-weight: 600; text-decoration: none; font-size: 12px; }
    .invoice-link:hover { text-decoration: underline; }
    .teknisi-link { color: #1e293b; font-weight: 600; text-decoration: none; font-size: 12px; }
    .teknisi-link:hover { color: #6366f1; }
    .nominal-text { font-weight: 700; color: #16a34a; white-space: nowrap; font-size: 12px; }
    .no-data-text { color: #cbd5e1; }
    .row-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 8px;
        background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 700;
    }

    /* Filter bar */
    .detail-filter-bar {
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px;
        padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
    .filter-group { display: flex; flex-direction: column; gap: 4px; }
    .filter-group label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.06em; color: #64748b; margin: 0;
    }
    .filter-select {
        padding: 8px 32px 8px 12px; border: 1.5px solid #e5e7eb;
        border-radius: 10px; font-size: 13px; color: #1e293b; font-weight: 500;
        background: #fff; -webkit-appearance: none; appearance: none;
        cursor: pointer; min-width: 160px; transition: all 0.2s;
    }
    .filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
    .filter-search {
        padding: 8px 12px 8px 36px; border: 1.5px solid #e5e7eb;
        border-radius: 10px; font-size: 13px; color: #1e293b; font-weight: 500;
        background: #fff; min-width: 240px; transition: all 0.2s;
    }
    .filter-search:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
    .filter-search-wrap { position: relative; flex: 1; min-width: 220px; }
    .filter-search-wrap i {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        font-size: 13px; color: #94a3b8; pointer-events: none;
    }

    /* Pill Buttons */
    .pill-group { display: flex; gap: 0; border-radius: 10px; overflow: hidden; border: 1.5px solid #e5e7eb; }
    .pill-btn {
        padding: 8px 16px; font-size: 12px; font-weight: 600;
        border: none; background: #fff; color: #64748b;
        cursor: pointer; transition: all 0.2s; white-space: nowrap;
        border-right: 1px solid #e5e7eb;
    }
    .pill-btn:last-child { border-right: none; }
    .pill-btn:hover { background: #f1f5f9; }
    .pill-btn.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }

    /* Summary cards row */
    .detail-summary-row {
        display: flex; flex-wrap: wrap; gap: 12px; padding: 16px 24px;
    }
    .detail-summary-card {
        padding: 12px 18px; border-radius: 12px;
        display: flex; flex-direction: column; gap: 2px;
        min-width: 140px;
    }
    .detail-summary-card .ds-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; }
    .detail-summary-card .ds-value { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; }
    .ds-invoice { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1e40af; }
    .ds-survey { background: linear-gradient(135deg, #fefce8, #fef9c3); color: #854d0e; }
    .ds-nominal { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #065f46; }

    @media (max-width: 768px) {
        .detail-filter-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 12px 16px; }
        .filter-select, .filter-search { min-width: unset; width: 100%; }
        .filter-search-wrap { min-width: unset; }
        .pill-group { width: 100%; }
        .pill-btn { flex: 1; text-align: center; }
        .detail-summary-row { padding: 12px 16px; }
        .detail-summary-card { min-width: unset; flex: 1; }
        .laporan-table thead { display: none; }
        .laporan-table tbody tr {
            display: block; margin-bottom: 12px; padding: 14px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb;
        }
        .laporan-table tbody td {
            display: flex; justify-content: space-between; align-items: center;
            padding: 6px 0; border-bottom: 1px solid #f1f5f9;
        }
        .laporan-table tbody td::before {
            content: attr(data-label);
            font-weight: 600; font-size: 11px; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.04em; flex: 0 0 40%;
        }
        .laporan-table tbody td:last-child { border-bottom: none; }
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
                   sv.keterangan_survey,
                   sv.surveyor
            FROM pendapatan_kegiatan pk
            JOIN kegiatan k ON k.kode = pk.kode
            JOIN customer c ON c.id = k.customer_id
            JOIN teknisi t ON t.id = pk.teknisi_id
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
    $totalBonusAll = 0;
    $rowNum = 0;
    $totalSurvey = 0;
    $allRows = [];
    $teknisiList = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $allRows[] = $row;
            $totalBonusAll += $row['nominal_invoice'];
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
            </div>
            <div class="detail-summary-card ds-survey">
                <span class="ds-label">Ada Survey</span>
                <span class="ds-value" id="stat-survey"><?= $totalSurvey ?></span>
            </div>
            <div class="detail-summary-card ds-nominal">
                <span class="ds-label">Total Pendapatan</span>
                <span class="ds-value" id="stat-nominal">Rp <?= number_format($totalBonusAll, 0, ',', '.') ?></span>
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

        <!-- Table -->
        <div class="table-responsive">
            <table class="laporan-table" id="data-tek">
                <thead>
                    <tr>
                        <th style="width:45px;padding-left:20px;">#</th>
                        <th style="width:100px;">Tgl Invoice</th>
                        <th style="width:130px;">No Invoice</th>
                        <th>Teknisi</th>
                        <th>Customer</th>
                        <th style="width:140px;">Ket. Survey</th>
                        <th style="width:110px;">Surveyor</th>
                        <th style="width:120px;text-align:right;padding-right:20px;">Nominal</th>
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
                            $tglInv = date('d M Y', strtotime($row['tanggal']));
                            $ketSurvey = $row['keterangan_survey'] ?? '';
                            $surveyor = $row['surveyor'] ?? '';
                            $nominalFormatted = "Rp " . number_format($nominal, 0, ',', '.');
                            $hasSurvey = !empty($ketSurvey) ? 'yes' : 'no';
                            $teknisiPlain = $row['nama_teknisi_plain'];
                    ?>
                        <tr data-survey="<?php echo $hasSurvey; ?>" data-teknisi="<?php echo htmlspecialchars($teknisiPlain); ?>" data-nominal="<?php echo $nominal; ?>">
                            <td data-label="#"><span class="row-num"><?php echo $rowNum; ?></span></td>
                            <td data-label="Tgl Invoice"><?php echo $tglInv; ?></td>
                            <td data-label="No Invoice"><span class="invoice-link"><?php echo $invoice; ?></span></td>
                            <td data-label="Teknisi"><span class="teknisi-link"><?php echo $namaT; ?></span></td>
                            <td data-label="Customer"><?php echo $namaC; ?></td>
                            <td data-label="Ket. Survey">
                                <?php if (!empty($ketSurvey)): ?>
                                    <span class="badge-survey-tag"><?php echo $ketSurvey; ?></span>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Surveyor">
                                <?php if (!empty($surveyor)): ?>
                                    <span class="surveyor-name"><?php echo $surveyor; ?></span>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Nominal" style="text-align:right;">
                                <span class="nominal-text"><?php echo $nominalFormatted; ?></span>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">
                                <div style="font-size:48px; margin-bottom:8px;">📭</div>
                                <div style="font-size:14px; font-weight:500;">Tidak ada data pendapatan untuk bulan ini</div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="laporan-footer-row">
                            <td colspan="7" style="padding-left:20px;"><strong>TOTAL PENDAPATAN</strong></td>
                            <td style="text-align:right;padding-right:20px;" id="footer-total"><strong><?php echo "Rp " . number_format($totalBonusAll, 0, ',', '.'); ?></strong></td>
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
    const statNominal = document.getElementById('stat-nominal');
    const footerTotal = document.getElementById('footer-total');

    let surveyFilter = 'all';

    function formatRp(num) {
        return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const teknisi = filterTeknisi.value.toLowerCase();
        let visibleCount = 0, surveyCount = 0, totalNominal = 0;

        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            const rowSurvey = row.getAttribute('data-survey');
            const rowTeknisi = (row.getAttribute('data-teknisi') || '').toLowerCase();
            const rowNominal = parseInt(row.getAttribute('data-nominal')) || 0;

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
            } else {
                row.classList.add('hidden-row');
            }
        });

        // Update stats
        statCount.textContent = visibleCount;
        statSurvey.textContent = surveyCount;
        statNominal.textContent = formatRp(totalNominal);
        footerTotal.innerHTML = '<strong>' + formatRp(totalNominal) + '</strong>';
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