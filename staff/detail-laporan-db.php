<?php
    if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
        $current_date = $_GET['cariBulanTahun'];
    } else {
        $current_date = date("Y-m");
    }
?>
<style>
    .laporan-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .laporan-table thead th {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff; font-size: 12px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em;
        padding: 14px 12px; border: none; white-space: nowrap;
        position: sticky; top: 0; z-index: 2;
    }
    .laporan-table thead th:first-child { border-radius: 10px 0 0 0; }
    .laporan-table thead th:last-child { border-radius: 0 10px 0 0; }
    .laporan-table tbody tr { transition: all 0.2s ease; }
    .laporan-table tbody tr:hover { background: #f0f9ff; }
    .laporan-table tbody tr.hidden-row { display: none; }
    .laporan-table tbody td {
        padding: 12px; font-size: 13px; color: #334155;
        border-bottom: 1px solid #e2e8f0; vertical-align: middle;
    }
    .laporan-table tbody tr:last-child td { border-bottom: none; }
    .laporan-table tfoot td {
        padding: 14px 12px; font-size: 14px; font-weight: 700; color: #fff;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); border: none;
    }
    .laporan-table tfoot td:first-child { border-radius: 0 0 0 10px; }
    .laporan-table tfoot td:last-child { border-radius: 0 0 10px 0; }
    .badge-survey-tag {
        display: inline-block;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #78350f; font-size: 11px; font-weight: 600;
        padding: 4px 10px; border-radius: 20px; white-space: nowrap;
    }
    .surveyor-name { font-weight: 500; color: #6366f1; }
    .invoice-link { color: #2563eb; font-weight: 600; text-decoration: none; }
    .invoice-link:hover { text-decoration: underline; }
    .teknisi-link { color: #334155; font-weight: 600; text-decoration: none; }
    .teknisi-link:hover { color: #2563eb; }
    .nominal-text { font-weight: 600; color: #059669; white-space: nowrap; }
    .no-data-text { color: #94a3b8; font-style: italic; }
    .row-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 50%;
        background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 600;
    }

    /* ===== FILTER BAR ===== */
    .filter-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
        padding: 16px 20px; margin: 0 12px 16px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 14px; border: 1px solid #e2e8f0;
    }
    .filter-group { display: flex; flex-direction: column; gap: 4px; }
    .filter-group label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.08em; color: #64748b; margin: 0; padding-left: 2px;
    }
    .filter-select {
        padding: 8px 32px 8px 12px; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: 13px; color: #334155;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M3 5l3 3 3-3'/%3E%3C/svg%3E") no-repeat right 10px center;
        -webkit-appearance: none; appearance: none; cursor: pointer;
        min-width: 160px; transition: all 0.2s;
    }
    .filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .filter-search {
        padding: 8px 12px 8px 36px; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: 13px; color: #334155;
        background: #fff; min-width: 220px; transition: all 0.2s;
    }
    .filter-search:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .filter-search-wrap {
        position: relative; flex: 1; min-width: 200px;
    }
    .filter-search-wrap::before {
        content: '🔍'; position: absolute; left: 10px; top: 50%;
        transform: translateY(-50%); font-size: 14px; pointer-events: none;
    }

    /* Pill Buttons */
    .pill-group { display: flex; gap: 0; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; }
    .pill-btn {
        padding: 8px 16px; font-size: 12px; font-weight: 600;
        border: none; background: #fff; color: #64748b;
        cursor: pointer; transition: all 0.2s; white-space: nowrap;
        border-right: 1px solid #e2e8f0;
    }
    .pill-btn:last-child { border-right: none; }
    .pill-btn:hover { background: #f1f5f9; }
    .pill-btn.active { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; }

    /* Stats chips */
    .stats-bar {
        display: flex; flex-wrap: wrap; gap: 10px; padding: 0 20px; margin: 0 12px 12px;
    }
    .stat-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
        background: #fff; border: 1px solid #e2e8f0;
    }
    .stat-chip .stat-val { color: #1e293b; }
    .stat-chip .stat-label { color: #64748b; font-weight: 400; }
    .stat-chip.chip-total { border-color: #6366f1; background: #eef2ff; }
    .stat-chip.chip-total .stat-val { color: #4f46e5; }
    .stat-chip.chip-survey { border-color: #f59e0b; background: #fffbeb; }
    .stat-chip.chip-survey .stat-val { color: #d97706; }
    .stat-chip.chip-nominal { border-color: #059669; background: #ecfdf5; }
    .stat-chip.chip-nominal .stat-val { color: #059669; }

    @media (max-width: 768px) {
        .filter-bar { flex-direction: column; align-items: stretch; gap: 10px; margin: 0 4px 12px; padding: 12px; }
        .filter-select, .filter-search { min-width: unset; width: 100%; }
        .filter-search-wrap { min-width: unset; }
        .pill-group { width: 100%; }
        .pill-btn { flex: 1; text-align: center; padding: 8px 8px; }
        .stats-bar { margin: 0 4px 8px; }
        .stat-chip { font-size: 11px; padding: 4px 10px; }
        .laporan-table thead { display: none; }
        .laporan-table tbody tr {
            display: block; margin-bottom: 16px; padding: 16px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;
        }
        .laporan-table tbody td {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 4px; border-bottom: 1px solid #f1f5f9;
        }
        .laporan-table tbody td::before {
            content: attr(data-label);
            font-weight: 600; font-size: 12px; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.03em; flex: 0 0 40%;
        }
        .laporan-table tbody td:last-child { border-bottom: none; }
        .laporan-table tfoot { display: block; }
        .laporan-table tfoot tr {
            display: flex; justify-content: space-between;
            padding: 14px 16px; border-radius: 12px;
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        }
        .laporan-table tfoot td { padding: 0; background: none; }
        .laporan-table tfoot td:first-child { border-radius: 0; }
        .laporan-table tfoot td:last-child { border-radius: 0; }
    }
</style>

<div class="col-lg-12" id="printable-content">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Pendapatan Teknisi</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-row">
                    <form method="GET" action="" class="d-flex align-items-center gap-2">
                        <input type="month" class="form-control border p-2 no-print" style="max-width:200px;" name="cariBulanTahun" value="<?php echo $current_date;?>">
                        <button class="btn bg-gradient-info mt-3 no-print" style="white-space:nowrap;">Cari</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $tomorrow_date = date("Y-m-d", strtotime("+1 day"));
        $current_time = date("H:i:s");
        
        // Calculate month range for index-friendly filtering
        $monthStart = $current_date . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        // Optimized: replaced DATE_FORMAT() with range, correlated subqueries with pre-aggregated JOINs
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
                // Collect unique teknisi names
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
        <div class="card-body pb-0 px-3 pt-2">
            <p class="text-dark ms-1 mb-2" style="font-size:15px;">
                📅 Bulan <strong><?php echo $bulan . ' ' . $tahun_display; ?></strong>
            </p>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar no-print">
            <div class="filter-search-wrap">
                <input type="text" id="search-input" class="filter-search" placeholder="Cari invoice, teknisi, customer...">
            </div>
            <div class="filter-group">
                <label>Teknisi</label>
                <select id="filter-teknisi" class="filter-select">
                    <option value="">Semua Teknisi</option>
                    <?php foreach ($teknisiList as $tek): ?>
                        <option value="<?php echo htmlspecialchars($tek); ?>"><?php echo htmlspecialchars($tek); ?></option>
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

        <!-- STATS CHIPS -->
        <div class="stats-bar no-print">
            <div class="stat-chip chip-total">
                <span class="stat-val" id="stat-count"><?php echo count($allRows); ?></span>
                <span class="stat-label">Invoice</span>
            </div>
            <div class="stat-chip chip-survey">
                <span class="stat-val" id="stat-survey"><?php echo $totalSurvey; ?></span>
                <span class="stat-label">Ada Survey</span>
            </div>
            <div class="stat-chip chip-nominal">
                <span class="stat-val" id="stat-nominal">Rp <?php echo number_format($totalBonusAll, 0, ',', '.'); ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>

        <div class="card-body pb-0 px-3 pt-0">
            <div class="table-responsive" style="border-radius:10px; overflow:hidden;">
                <table class="laporan-table" id="data-tek">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:90px;">Tgl Invoice</th>
                            <th style="width:130px;">No Invoice</th>
                            <th>Teknisi</th>
                            <th>Customer</th>
                            <th style="width:160px;">Ket. Survey</th>
                            <th style="width:120px;">Surveyor</th>
                            <th style="width:120px; text-align:right;">Nominal</th>
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
                        <tr>
                            <td colspan="7"><strong>TOTAL PENDAPATAN</strong></td>
                            <td style="text-align:right;" id="footer-total"><strong><?php echo "Rp " . number_format($totalBonusAll, 0, ',', '.'); ?></strong></td>
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