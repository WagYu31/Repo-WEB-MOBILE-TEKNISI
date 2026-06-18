<div class="col-lg-12" id="printable-content">
<?php
    $daftar_bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($current_date);
    $bulan = $daftar_bulan[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    $bulan_filter = date('m', $timestamp);
    $tahun_filter = date('Y', $timestamp);
    $ym = $current_date; // e.g. "2026-06"

    // Period calculation (1 or 3 months)
    $filterPeriode = $filterPeriode ?? '1';
    $filterTeknisiId = $filterTeknisiId ?? 0;
    if ($filterPeriode == '3') {
        $dtStart = new DateTime($current_date . '-01');
        $dtStart->modify('-2 months');
        $monthStart = $dtStart->format('Y-m-d');
        $monthEnd = date('Y-m-t', $timestamp);
        // Build list of Y-m for SQL IN
        $ymList = [];
        $dtTmp = clone $dtStart;
        for ($mi = 0; $mi < 3; $mi++) {
            $ymList[] = $dtTmp->format('Y-m');
            $dtTmp->modify('+1 month');
        }
        $ymCondition = implode(',', array_map(function($v) { return "'$v'"; }, $ymList));
        $periodeLabel = $daftar_bulan[intval($dtStart->format('m'))] . ' - ' . $bulan . ' ' . $tahun;
    } else {
        $monthStart = "$tahun_filter-$bulan_filter-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $ymList = [$ym];
        $ymCondition = "'$ym'";
        $periodeLabel = $bulan . ' ' . $tahun;
    }

    // ═══ BATCH QUERY 1: All teknisi ═══
    $teknisiList = [];
    $teknisiTargets = [];
    $res_tek = mysqli_query($conn, "SELECT id, nama, target FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
    while ($r = mysqli_fetch_assoc($res_tek)) {
        $teknisiList[$r['id']] = $r['nama'];
        $teknisiTargets[$r['id']] = floatval($r['target'] ?? 0);
    }
    $allTekIds = array_keys($teknisiList);

    if (!empty($allTekIds)) {
        $placeholders = implode(',', array_fill(0, count($allTekIds), '?'));
        $types = str_repeat('i', count($allTekIds));

        // ═══ BATCH QUERY 2: Kegiatan count per teknisi ═══
        $kegiatanCount = [];
        $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total 
                FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id 
                WHERE tk.teknisi_id IN ($placeholders) 
                AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
                AND k.deleted_at IS NULL AND tk.deleted_at IS NULL
                GROUP BY tk.teknisi_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$allTekIds);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $kegiatanCount[$r['teknisi_id']] = $r['total'];
        $stmt->close();

        // ═══ BATCH QUERY 3: Selesai count per teknisi ═══
        $selesaiCount = [];
        $sql = "SELECT tk.teknisi_id, COUNT(DISTINCT k.kode) as total 
                FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id 
                WHERE tk.teknisi_id IN ($placeholders)
                AND k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
                AND k.status = 'selesai' AND k.deleted_at IS NULL AND tk.deleted_at IS NULL
                GROUP BY tk.teknisi_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$allTekIds);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $selesaiCount[$r['teknisi_id']] = $r['total'];
        $stmt->close();

        // ═══ BATCH QUERY 4: Invoice count + pendapatan per teknisi ═══
        $invCount = [];
        $pendapatanSum = [];
        $sql = "SELECT pk.teknisi_id, 
                       COUNT(DISTINCT pk.kode) as cnt, 
                       SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total
                FROM pendapatan_kegiatan pk
                JOIN (
                    SELECT kode, COUNT(*) as tek_count 
                    FROM pendapatan_kegiatan 
                    WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($ymCondition) AND deleted_at IS NULL
                    GROUP BY kode
                ) counts ON pk.kode = counts.kode
                WHERE pk.teknisi_id IN ($placeholders) 
                AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($ymCondition) 
                AND pk.deleted_at IS NULL
                GROUP BY pk.teknisi_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$allTekIds);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $invCount[$r['teknisi_id']] = $r['cnt'];
            $pendapatanSum[$r['teknisi_id']] = $r['total'];
        }
        $stmt->close();

        // ═══ BATCH QUERY 6: Fee 30k calculation (2 queries instead of N*M) ═══
        // Step 1: Get all eligible kode for this month
        $feeKodes = [];
        $sql = "SELECT k.kode FROM kegiatan k 
                WHERE k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
                AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
                GROUP BY k.kode";
        $res = mysqli_query($conn, $sql);
        while ($r = mysqli_fetch_assoc($res)) $feeKodes[] = $r['kode'];

        // Step 2: Get active teknisi per kode in 1 query
        $feeMap = []; // teknisi_id => total_fee
        if (!empty($feeKodes)) {
            $kodePlaceholders = implode(',', array_fill(0, count($feeKodes), '?'));
            $kodeTypes = str_repeat('s', count($feeKodes));
            
            $sql = "SELECT DISTINCT kode, teknisi_id
                    FROM pelaksanaan_kegiatan 
                    WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($kodeTypes, ...$feeKodes);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $kodeTeknisi = []; // kode => [teknisi_ids]
            while ($r = $res->fetch_assoc()) {
                $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
            }
            $stmt->close();
            
            // Calculate fee per teknisi
            foreach ($kodeTeknisi as $kd => $tekIds) {
                $jml = count($tekIds);
                if ($jml > 0) {
                    $share = 30000 / $jml;
                    foreach ($tekIds as $tid => $_) {
                        if (!isset($feeMap[$tid])) $feeMap[$tid] = 0;
                        $feeMap[$tid] += $share;
                    }
                }
            }
        }
    }

    $grand_total_fee = 0;
    $grand_total_pendapatan = 0;
    $grand_total_bonus = 0;

    // ═══ GRAND TOTAL PENDAPATAN ═══
    $sqlGrandPend = "SELECT SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total
        FROM pendapatan_kegiatan pk
        JOIN (
            SELECT kode, COUNT(*) as tek_count
            FROM pendapatan_kegiatan
            WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($ymCondition) AND deleted_at IS NULL
            GROUP BY kode
        ) counts ON pk.kode = counts.kode
        WHERE DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($ymCondition) AND pk.deleted_at IS NULL";
    $resGP = mysqli_query($conn, $sqlGrandPend);
    $rowGP = mysqli_fetch_assoc($resGP);
    $grand_total_pendapatan = $rowGP['total'] ?? 0;

    // Pre-calculate all rows
    $tableRows = [];
    foreach ($teknisiList as $idT => $namaT) {
        // Filter by teknisi if set
        if ($filterTeknisiId > 0 && $idT != $filterTeknisiId) continue;

        $keg = $kegiatanCount[$idT] ?? 0;
        $sel = $selesaiCount[$idT] ?? 0;
        $inv = $invCount[$idT] ?? 0;
        $fee = $feeMap[$idT] ?? 0;
        $pend = $pendapatanSum[$idT] ?? 0;
        $target = $teknisiTargets[$idT] ?? 0;
        $totalEarning = $fee + $pend;
        $bon = ($target > 0 && $totalEarning > $target) ? ($totalEarning - $target) * 0.60 : 0;
        $total = $fee + $pend + $bon;

        $grand_total_fee += $fee;
        $grand_total_bonus += $bon;

        $tableRows[] = compact('idT', 'namaT', 'keg', 'sel', 'inv', 'fee', 'pend', 'bon', 'total');
    }
    // Recalculate grand pendapatan if filtering by teknisi
    if ($filterTeknisiId > 0) {
        $grand_total_pendapatan = $pendapatanSum[$filterTeknisiId] ?? 0;
    }
    $grand_total_all = $grand_total_fee + $grand_total_pendapatan + $grand_total_bonus;
?>

    <!-- ═══ PREMIUM REKAP CARD ═══ -->
    <div class="rekap-card">
        <div class="rekap-header">
            <div class="rekap-title-row">
                <div class="rekap-title-left">
                    <div class="rekap-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h5>Rekapitulasi Bulanan</h5>
                        <p><?= $periodeLabel ?></p>
                    </div>
                </div>
                <form method="GET" action="" class="rekap-filter no-print">
                    <select name="bulan" class="rekap-month-input" style="min-width:200px;">
                        <option value="">Semua Bulan</option>
                        <optgroup label="Per Bulan">
                            <?php foreach ($monthOptions as $mo): ?>
                                <option value="<?= $mo['value'] ?>" <?= $filterBulan == $mo['value'] ? 'selected' : '' ?>><?= $mo['label'] ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Per 3 Bulan">
                            <?php 
                            for ($qi = 0; $qi < 12; $qi += 3) {
                                $dt3 = new DateTime();
                                $dt3->modify("-$qi months");
                                $bln3s = intval($dt3->format('m'));
                                $dt3e = clone $dt3;
                                $dt3e->modify('-2 months');
                                $bln3e = intval($dt3e->format('m'));
                                $val3 = $dt3e->format('Y-m') . '_3';
                                $label3 = $namaBulanList[$bln3e] . ' - ' . $namaBulanList[$bln3s] . ' ' . $dt3->format('Y');
                                echo '<option value="' . $val3 . '"' . ($filterBulan == $val3 ? ' selected' : '') . '>' . $label3 . '</option>';
                            }
                            ?>
                        </optgroup>
                    </select>
                    <select name="ftek" class="rekap-month-input" style="min-width:140px;">
                        <option value="0">Semua Teknisi</option>
                        <?php foreach ($tekOptions as $to): ?>
                            <option value="<?= $to['id'] ?>" <?= $filterTeknisiId == $to['id'] ? 'selected' : '' ?>><?= htmlspecialchars($to['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="rekap-btn-cari">
                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                    </button>
                </form>
            </div>
            <!-- Summary Cards -->
            <div class="summary-row">
                <div class="summary-card summary-fee">
                    <span class="summary-label">Total Fee</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_fee, 0, ',', '.') ?></span>
                    <i class="fa-solid fa-hand-holding-dollar summary-card-icon"></i>
                </div>
                <div class="summary-card summary-income">
                    <span class="summary-label">Total Pendapatan</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.') ?></span>
                    <i class="fa-solid fa-wallet summary-card-icon"></i>
                </div>
                <div class="summary-card summary-bonus">
                    <span class="summary-label">Total Bonus</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_bonus, 0, ',', '.') ?></span>
                    <i class="fa-solid fa-gift summary-card-icon"></i>
                </div>
                <div class="summary-card summary-total">
                    <span class="summary-label">Grand Total</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_all, 0, ',', '.') ?></span>
                    <i class="fa-solid fa-sack-dollar summary-card-icon"></i>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0 rekap-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:25%;">Teknisi</th>
                        <th class="text-center" style="width:8%;">Kegiatan</th>
                        <th class="text-center" style="width:8%;">Selesai</th>
                        <th class="text-center" style="width:8%;">Invoice</th>
                        <th class="text-center" style="width:17%;">Fee (30k)</th>
                        <th class="text-center" style="width:17%;">Pendapatan</th>
                        <th class="text-center pe-4" style="width:17%;">Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableRows as $i => $tr): ?>
                    <tr>
                        <td class="ps-4">
                            <a href="list-kegiatan-teknisi.php?cariBulanTahun=<?= $current_date ?>&idTek=<?= $tr['idT'] ?>" class="tek-name">
                                <span class="tek-avatar-circle"><?= strtoupper(substr($tr['namaT'], 0, 1)) ?></span>
                                <?= htmlspecialchars($tr['namaT']) ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="stat-pill"><?= $tr['keg'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="stat-pill stat-done"><?= $tr['sel'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="stat-pill stat-inv"><?= $tr['inv'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="money-val <?= $tr['fee'] > 0 ? 'money-positive' : 'money-zero' ?>">
                                Rp <?= number_format($tr['fee'], 0, ',', '.') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($tr['pend'] > 0): ?>
                                <span class="money-val money-positive money-clickable" 
                                      onclick="showRevenueDetail(<?= $tr['idT'] ?>, '<?= htmlspecialchars($tr['namaT'], ENT_QUOTES) ?>', '<?= $ym ?>')">
                                    Rp <?= number_format($tr['pend'], 0, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                <span class="money-val money-zero">
                                    Rp <?= number_format($tr['pend'], 0, ',', '.') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-4">
                            <span class="money-val <?= $tr['bon'] > 0 ? 'money-positive' : 'money-zero' ?>">
                                Rp <?= number_format($tr['bon'], 0, ',', '.') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="rekap-footer-row">
                        <td class="ps-4 font-weight-bold">TOTAL KESELURUHAN</td>
                        <td colspan="3"></td>
                        <td class="text-center font-weight-bold">Rp <?= number_format($grand_total_fee, 0, ',', '.') ?></td>
                        <td class="text-center font-weight-bold">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.') ?></td>
                        <td class="text-center font-weight-bold pe-4">Rp <?= number_format($grand_total_bonus, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

/* ═══ PREMIUM REKAP STYLES ═══ */
.rekap-card, #revenueDetailModal {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
}

.rekap-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025), 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.rekap-header {
    padding: 24px 24px 0;
}

.rekap-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.rekap-title-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.rekap-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.rekap-icon i { color: #fff; font-size: 18px; }

.rekap-title-left h5 {
    margin: 0; font-size: 17px; font-weight: 800; color: #1e293b;
    letter-spacing: -0.02em;
}
.rekap-title-left p {
    margin: 2px 0 0; font-size: 12.5px; color: #64748b; font-weight: 500;
}

.rekap-filter {
    display: flex; gap: 8px; align-items: center;
}
.rekap-month-input {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 8.5px 14px;
    font-size: 13px;
    color: #1e293b;
    background: #f8fafc;
    font-weight: 600;
    transition: all 0.2s;
}
.rekap-month-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    outline: none;
    background: #fff;
}
.rekap-btn-cari {
    padding: 9px 20px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 13px;
    font-weight: 750;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(99,102,241,0.25);
}
.rekap-btn-cari:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 6px 20px rgba(99,102,241,0.35);
}

/* Summary Cards */
.summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 768px) {
    .summary-row { grid-template-columns: repeat(2, 1fr); }
}
.summary-card {
    padding: 18px 22px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    position: relative;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.summary-card:hover {
    transform: translateY(-4px);
}
.summary-label { 
    font-size: 11px; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 0.09em; 
    opacity: 0.95; 
}
.summary-value { 
    font-size: 21px; 
    font-weight: 850; 
    letter-spacing: -0.03em; 
}
.summary-card-icon {
    position: absolute;
    right: 14px;
    bottom: -12px;
    font-size: 64px;
    opacity: 0.16;
    color: #fff;
    pointer-events: none;
    transition: all 0.25s ease;
}
.summary-card:hover .summary-card-icon {
    transform: scale(1.12) rotate(-8deg);
    opacity: 0.24;
}

.summary-fee { 
    background: linear-gradient(135deg, #0d9488, #0f766e); 
    color: #ffffff; 
    box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.2); 
}
.summary-income { 
    background: linear-gradient(135deg, #2563eb, #1d4ed8); 
    color: #ffffff; 
    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); 
}
.summary-bonus { 
    background: linear-gradient(135deg, #ea580c, #c2410c); 
    color: #ffffff; 
    box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.2); 
}
.summary-total { 
    background: linear-gradient(135deg, #7c3aed, #6d28d9); 
    color: #ffffff; 
    box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.2); 
}

/* Table */
.rekap-table { 
    table-layout: fixed; 
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0; 
}

.rekap-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 16px 14px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}

.rekap-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}
.rekap-table tbody tr:hover { 
    background-color: #f8fafc; 
    box-shadow: inset 4px 0 0 0 #6366f1;
}
.rekap-table tbody td { 
    padding: 15px 14px; 
    font-size: 13.5px; 
    vertical-align: middle;
}

/* Teknisi name with avatar */
.tek-name {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #1e293b;
    font-weight: 700;
    font-size: 13.5px;
    transition: all 0.2s ease;
}
.tek-name:hover { 
    color: #6366f1; 
    transform: translateX(3px);
}

.tek-avatar-circle {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4338ca;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(99,102,241,0.1);
}

/* Stat pills */
.stat-pill {
    display: inline-block;
    min-width: 30px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    text-align: center;
    border: 1px solid #e2e8f0;
}
.stat-done { 
    background: rgba(16, 185, 129, 0.1); 
    color: #059669; 
    border: 1px solid rgba(16, 185, 129, 0.2); 
}
.stat-inv { 
    background: rgba(59, 130, 246, 0.1); 
    color: #2563eb; 
    border: 1px solid rgba(59, 130, 246, 0.2); 
}

/* Money values */
.money-val {
    font-size: 13px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.money-positive { 
    color: #059669; 
}
.money-zero { 
    color: #94a3b8; 
    opacity: 0.6;
    font-weight: 500;
}

/* Footer */
.rekap-footer-row {
    background: linear-gradient(135deg, #1e293b, #334155) !important;
}
.rekap-footer-row td {
    color: #fff !important;
    font-size: 13.5px !important;
    font-weight: 800 !important;
    padding: 16px 14.5px !important;
    border: none !important;
}

@media print {
    .no-print { display: none !important; }
    .rekap-card { box-shadow: none !important; border: 1px solid #ddd !important; }
}

/* Clickable revenue styles */
.money-clickable {
    cursor: pointer;
    color: #6366f1 !important;
    border-bottom: 1.5px dashed rgba(99, 102, 241, 0.4);
    transition: all 0.2s ease;
    display: inline-block;
    padding-bottom: 1px;
}
.money-clickable:hover {
    color: #4f46e5 !important;
    border-bottom: 1.5px solid #4f46e5;
    transform: scale(1.03);
}

/* Light styling for modal success badges */
.bg-success-light {
    background-color: rgba(34, 197, 94, 0.1) !important;
    color: #166534 !important;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

/* Modal Table Styling */
#modal-detail-table {
    table-layout: auto;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
#modal-detail-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 16px 12px;
    font-size: 11px;
    font-weight: 750;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
#modal-detail-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
}
#modal-detail-table tbody tr:hover {
    background-color: #f8fafc;
    box-shadow: inset 4px 0 0 0 #6366f1;
}
#modal-detail-table tbody td {
    padding: 14px 12px;
    font-size: 13.5px;
    color: #334155;
    vertical-align: middle;
}

/* Spinner helper */
.modal-spinner-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
}
.modal-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #6366f1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 12px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- Modal Detail Rincian Pendapatan -->
<div class="modal fade" id="revenueDetailModal" tabindex="-1" aria-labelledby="revenueDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
      <div class="modal-header bg-light" style="border-bottom: 1px solid #f1f5f9; padding: 20px 24px;">
        <h5 class="modal-title" id="revenueDetailModalLabel" style="font-weight: 800; color: #1e293b; display: flex; align-items: center;">
          <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Rincian Pendapatan: <span id="modal-tech-name" class="ms-1" style="color: #6366f1;"></span>
        </h5>
        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close" style="background-color: transparent; border: none; font-size: 24px; font-weight: bold; line-height: 1; padding: 0; margin: 0; opacity: 0.5; cursor: pointer;">&times;</button>
      </div>
      <div class="modal-body" style="padding: 24px;">
        <!-- Period & Total Info -->
        <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <span class="badge bg-light text-dark p-2" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
            <i class="fa-regular fa-calendar-days me-1 text-primary"></i> Periode: <span id="modal-period"></span>
          </span>
          <div class="d-flex align-items-center gap-2">
            <button id="modal-export-btn" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" style="font-size: 12px; font-weight: 700; border-radius: 8px; padding: 6px 12px; margin: 0; box-shadow: 0 4px 12px rgba(34,197,94,0.15); border: none; color: #fff; cursor: pointer;">
              <i class="fa-solid fa-file-excel"></i> Ekspor Excel
            </button>
            <span class="badge bg-success-light text-success p-2" style="font-size: 13px; font-weight: 700; border-radius: 8px; margin: 0; display: inline-flex; align-items: center; height: 32px;">
              Total Terhitung: <span id="modal-total-amount" class="ms-1">Rp 0</span>
            </span>
          </div>
        </div>
        
        <!-- Table Detail -->
        <div class="table-responsive" style="border-radius: 12px; border: 1px solid #e5e7eb; max-height: 400px; overflow-y: auto;">
          <table class="table align-middle mb-0" id="modal-detail-table">
            <thead style="position: sticky; top: 0; z-index: 10; background-color: #f8fafc;">
              <tr style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; background-color: #f8fafc;">
                <th class="ps-3" style="width: 50px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">#</th>
                <th style="width: 100px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">Tanggal</th>
                <th style="background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">No Invoice</th>
                <th style="background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">Customer</th>
                <th class="text-end" style="width: 120px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">Nominal Invoice</th>
                <th class="text-center" style="width: 240px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">Bagi</th>
                <th class="text-end pe-3" style="width: 120px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">Diterima</th>
              </tr>
            </thead>
            <tbody id="modal-detail-tbody" style="font-size: 13px; color: #334155;">
              <!-- Content loaded dynamically -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showRevenueDetail(techId, techName, period) {
    // Populate simple headers
    document.getElementById('modal-tech-name').textContent = techName;
    
    // Format period for display, e.g. "2026-06" to "Juni 2026"
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const parts = period.split('-');
    let periodFormatted = period;
    if (parts.length === 2) {
        const mIdx = parseInt(parts[1], 10) - 1;
        if (mIdx >= 0 && mIdx < 12) {
            periodFormatted = months[mIdx] + ' ' + parts[0];
        }
    }
    document.getElementById('modal-period').textContent = periodFormatted;
    
    const tbody = document.getElementById('modal-detail-tbody');
    const totalEl = document.getElementById('modal-total-amount');
    
    // Set loading state
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center" style="padding: 40px 0;">
                <div class="modal-spinner-wrap">
                    <div class="modal-spinner"></div>
                    <span style="font-size: 13px; font-weight: 500; color: #64748b;">Memuat rincian invoice...</span>
                </div>
            </td>
        </tr>
    `;
    totalEl.textContent = 'Rp 0';
    
    // Show Modal
    const modalEl = document.getElementById('revenueDetailModal');
    const myModal = new bootstrap.Modal(modalEl);
    myModal.show();
    
    // Fetch data
    fetch('get-pendapatan-detail.php?tech_id=' + techId + '&period=' + period)
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data detail');
            }
            return response.json();
        })
        .then(res => {
            if (res.success && res.data.length > 0) {
                totalEl.textContent = res.formatted_total_share;
                
                let html = '';
                res.data.forEach((item, index) => {
                    const nomFormatted = 'Rp ' + numberFormat(item.nominal_invoice);
                    const shareFormatted = 'Rp ' + numberFormat(item.share_amount);
                    
                    // Detail of shared technicians
                    let infoBagi = `<span class="badge" style="font-size: 10px; font-weight: 700; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em;">${item.tek_count} Kunjungan</span>`;
                    if (item.nama_teknisi_group) {
                        const cleanTechGroup = item.nama_teknisi_group.replace(/, /g, '\n').replace(/'/g, "\\'");
                        infoBagi = `<span class="badge" style="cursor: pointer; max-width: 150px; font-size: 10px; font-weight: 700; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em;" title="Klik untuk rincian kegiatan" onclick="alert('Rincian Kegiatan Teknisi untuk Invoice ${item.no_invoice}:\\n\\n${cleanTechGroup}')">${item.tek_count} Kunjungan</span>`;
                    }

                    if (item.nama_teknisi_group) {
                        const formattedTechs = item.nama_teknisi_group.split(', ').map(name => {
                            const isActive = name.startsWith(techName) || name.includes(techName);
                            const style = isActive
                                ? `display: inline-block; white-space: nowrap; background: rgba(99, 102, 241, 0.1); padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.2); font-size: 10.5px; margin: 2px; color: #4f46e5; font-weight: 700;`
                                : `display: inline-block; white-space: nowrap; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 10.5px; margin: 2px; color: #475569; font-weight: 550;`;
                            return `<span style="${style}">${name}</span>`;
                        }).join('');
                        infoBagi += `<div class="d-flex flex-wrap justify-content-center mt-2" style="max-width: 230px; margin-left: auto; margin-right: auto; line-height: 1.2;">${formattedTechs}</div>`;
                    }
                    
                    let shareHtml = `<span style="font-weight: 750; color: #059669; font-variant-numeric: tabular-nums;">${shareFormatted}</span>`;
                    
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td class="ps-3"><span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 700; border: 1px solid #e2e8f0;">${index + 1}</span></td>
                            <td style="white-space: nowrap; font-weight: 500; color: #475569;">${item.formatted_date}</td>
                            <td style="font-weight: 700; color: #2563eb;">${item.no_invoice}</td>
                            <td style="word-break: break-word; font-weight: 600; color: #1e293b;">${item.nama_cust}</td>
                            <td class="text-end" style="color: #475569; font-weight: 600; font-variant-numeric: tabular-nums;">${nomFormatted}</td>
                            <td class="text-center">${infoBagi}</td>
                            <td class="text-end pe-3">${shareHtml}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 40px; color: #94a3b8;">
                            <div style="font-size: 48px; margin-bottom: 8px;">📭</div>
                            <div style="font-size: 14px; font-weight: 500;">Tidak ditemukan invoice untuk bulan ini</div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger" style="padding: 40px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; margin-bottom: 8px;"></i>
                        <div style="font-size: 14px; font-weight: 600;">Terjadi kesalahan: ${err.message}</div>
                    </td>
                </tr>
            `;
        });
}

function numberFormat(val) {
    return Math.round(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
</script>

<!-- SheetJS library for client-side Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.getElementById('modal-export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const techName = document.getElementById('modal-tech-name').textContent.trim();
            const periodText = document.getElementById('modal-period').textContent.trim();
            const tbody = document.getElementById('modal-detail-tbody');
            const rows = tbody.querySelectorAll('tr');
            
            if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
                alert('Tidak ada data untuk diekspor');
                return;
            }
            
            let data = [
                ["Rincian Pendapatan Teknisi"],
                ["Nama Teknisi:", techName],
                ["Periode:", periodText],
                [], // Empty row
                ["No", "Tanggal", "No Invoice", "Customer", "Nominal Invoice", "Bagi", "Porsi Diterima"]
            ];
            
            let totalNominal = 0;
            let totalDiterima = 0;
            
            rows.forEach((row, index) => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 7) return;
                
                const no = index + 1;
                const tanggal = cells[1].textContent.trim();
                const noInvoice = cells[2].textContent.trim();
                const customer = cells[3].textContent.trim();
                
                // Extract original nominal invoice (removing Rp, dots, etc)
                const nominalStr = cells[4].textContent.trim().replace(/[^\d]/g, '');
                const nominal = parseInt(nominalStr, 10) || 0;
                totalNominal += nominal;
                
                // Extract bagi text (only the main badge text, not the list of other technicians in the div)
                const badges = cells[5].querySelectorAll('.badge');
                let bagi = '';
                if (badges.length > 0) {
                    bagi = Array.from(badges).map(b => b.textContent.trim()).join(' / ');
                } else {
                    bagi = cells[5].textContent.trim();
                }
                
                // Extract received portion (only from the main span to avoid including breakdown numbers)
                const diterimaSpan = cells[6].querySelector('span');
                const diterimaStr = (diterimaSpan ? diterimaSpan.textContent : cells[6].textContent).trim().replace(/[^\d]/g, '');
                const diterima = parseInt(diterimaStr, 10) || 0;
                totalDiterima += diterima;
                
                data.push([no, tanggal, noInvoice, customer, nominal, bagi, diterima]);
            });
            
            // Total row
            data.push([]);
            data.push(["", "", "", "TOTAL", totalNominal, "", totalDiterima]);
            
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            // Auto-format column widths
            ws['!cols'] = [
                { wch: 6 },  // No
                { wch: 15 }, // Tanggal
                { wch: 25 }, // No Invoice
                { wch: 30 }, // Customer
                { wch: 18 }, // Nominal Invoice
                { wch: 15 }, // Bagi
                { wch: 18 }  // Porsi Diterima
            ];
            
            // Format numeric columns for currency
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let R = 5; R <= range.e.r; R++) {
                // Column E: Nominal Invoice (index 4)
                const cellE = ws[XLSX.utils.encode_cell({r: R, c: 4})];
                if (cellE && typeof cellE.v === 'number') {
                    cellE.t = 'n';
                    cellE.z = '"Rp" #,##0';
                }
                
                // Column G: Porsi Diterima (index 6)
                const cellG = ws[XLSX.utils.encode_cell({r: R, c: 6})];
                if (cellG && typeof cellG.v === 'number') {
                    cellG.t = 'n';
                    cellG.z = '"Rp" #,##0';
                }
            }
            
            const wb = XLSX.utils.book_new();
            const sheetName = ("Rincian_" + techName).substring(0, 31).replace(/[\\\?\*\/\[\]]/g, ""); // SheetJS limit is 31 chars
            XLSX.utils.book_append_sheet(wb, ws, sheetName);
            
            const safeTechName = techName.toLowerCase().replace(/[^a-z0-9]/g, "_");
            const safePeriod = periodText.toLowerCase().replace(/[^a-z0-9]/g, "_");
            XLSX.writeFile(wb, `Rincian_Pendapatan_${safeTechName}_${safePeriod}.xlsx`);
        });
    }

    // Make revenueDetailModal draggable
    const draggableModal = document.getElementById('revenueDetailModal');
    if (draggableModal) {
        const dialog = draggableModal.querySelector('.modal-dialog');
        const header = draggableModal.querySelector('.modal-header');
        
        let activeDrag = false;
        let startX, startY;
        let initialLeft = 0;
        let initialTop = 0;
        
        header.style.cursor = 'move';
        
        // Reset positioning when modal opens
        draggableModal.addEventListener('show.bs.modal', function() {
            dialog.style.position = '';
            dialog.style.margin = '';
            dialog.style.left = '';
            dialog.style.top = '';
            dialog.style.transform = '';
        });
        
        // Mouse events
        header.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return; // Only left click
            if (e.target.closest('.btn-close') || e.target.closest('button')) return;
            
            activeDrag = true;
            startX = e.clientX;
            startY = e.clientY;
            
            const rect = dialog.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;
            
            dialog.style.position = 'absolute';
            dialog.style.margin = '0';
            dialog.style.left = initialLeft + 'px';
            dialog.style.top = initialTop + 'px';
            dialog.style.transform = 'none';
            
            document.addEventListener('mousemove', mouseDrag);
            document.addEventListener('mouseup', mouseDragEnd);
        });
        
        function mouseDrag(e) {
            if (!activeDrag) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            dialog.style.left = (initialLeft + dx) + 'px';
            dialog.style.top = (initialTop + dy) + 'px';
        }
        
        function mouseDragEnd() {
            activeDrag = false;
            document.removeEventListener('mousemove', mouseDrag);
            document.removeEventListener('mouseup', mouseDragEnd);
        }
        
        // Touch events
        header.addEventListener('touchstart', function(e) {
            if (e.target.closest('.btn-close') || e.target.closest('button')) return;
            
            activeDrag = true;
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            
            const rect = dialog.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;
            
            dialog.style.position = 'absolute';
            dialog.style.margin = '0';
            dialog.style.left = initialLeft + 'px';
            dialog.style.top = initialTop + 'px';
            dialog.style.transform = 'none';
            
            document.addEventListener('touchmove', touchDrag, { passive: false });
            document.addEventListener('touchend', touchDragEnd);
        });
        
        function touchDrag(e) {
            if (!activeDrag) return;
            const touch = e.touches[0];
            const dx = touch.clientX - startX;
            const dy = touch.clientY - startY;
            dialog.style.left = (initialLeft + dx) + 'px';
            dialog.style.top = (initialTop + dy) + 'px';
            e.preventDefault();
        }
        
        function touchDragEnd() {
            activeDrag = false;
            document.removeEventListener('touchmove', touchDrag);
            document.removeEventListener('touchend', touchDragEnd);
        }
    }
});
</script>