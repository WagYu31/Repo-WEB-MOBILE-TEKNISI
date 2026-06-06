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

    // ═══ BATCH QUERY 1: All teknisi ═══
    $teknisiList = [];
    $res_tek = mysqli_query($conn, "SELECT id, nama FROM teknisi ORDER BY nama ASC");
    while ($r = mysqli_fetch_assoc($res_tek)) {
        $teknisiList[$r['id']] = $r['nama'];
    }
    $allTekIds = array_keys($teknisiList);

    if (!empty($allTekIds)) {
        $placeholders = implode(',', array_fill(0, count($allTekIds), '?'));
        $types = str_repeat('i', count($allTekIds));
        $monthStart = "$tahun_filter-$bulan_filter-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));

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
        // Patokan: nominal_invoice / jumlah_teknisi_per_kode (agar Total Pendapatan = Detail Invoice)
        $invCount = [];
        $pendapatanSum = [];
        $sql = "SELECT pk.teknisi_id, 
                       COUNT(DISTINCT pk.kode) as cnt, 
                       SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total 
                FROM pendapatan_kegiatan pk
                JOIN (
                    SELECT kode, COUNT(*) as tek_count 
                    FROM pendapatan_kegiatan 
                    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
                    GROUP BY kode
                ) counts ON pk.kode = counts.kode
                WHERE pk.teknisi_id IN ($placeholders) 
                AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? 
                AND pk.deleted_at IS NULL
                GROUP BY pk.teknisi_id";
        $stmt = $conn->prepare($sql);
        $paramTypes = 's' . $types . 's';
        $paramVals = array_merge([$ym], $allTekIds, [$ym]);
        $stmt->bind_param($paramTypes, ...$paramVals);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $invCount[$r['teknisi_id']] = $r['cnt'];
            $pendapatanSum[$r['teknisi_id']] = $r['total'];
        }
        $stmt->close();

        // ═══ BATCH QUERY 5: Bonus fix per teknisi ═══
        $bonusSum = [];
        $sql = "SELECT teknisi_id, SUM(bonus) as total 
                FROM pendapatan_fix 
                WHERE teknisi_id IN ($placeholders) AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
                GROUP BY teknisi_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($paramTypes, ...$paramVals);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $bonusSum[$r['teknisi_id']] = $r['total'];
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

    // Pre-calculate all rows
    $tableRows = [];
    foreach ($teknisiList as $idT => $namaT) {
        $keg = $kegiatanCount[$idT] ?? 0;
        $sel = $selesaiCount[$idT] ?? 0;
        $inv = $invCount[$idT] ?? 0;
        $fee = $feeMap[$idT] ?? 0;
        $pend = $pendapatanSum[$idT] ?? 0;
        $bon = $bonusSum[$idT] ?? 0;
        $total = $fee + $pend + $bon;

        $grand_total_fee += $fee;
        $grand_total_pendapatan += $pend;
        $grand_total_bonus += $bon;

        $tableRows[] = compact('idT', 'namaT', 'keg', 'sel', 'inv', 'fee', 'pend', 'bon', 'total');
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
                        <p><?= $bulan . ' ' . $tahun ?></p>
                    </div>
                </div>
                <form method="GET" action="" class="rekap-filter no-print">
                    <input type="month" class="rekap-month-input" name="cariBulanTahun" value="<?= $current_date; ?>">
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
                </div>
                <div class="summary-card summary-income">
                    <span class="summary-label">Total Pendapatan</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.') ?></span>
                </div>
                <div class="summary-card summary-bonus">
                    <span class="summary-label">Total Bonus</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_bonus, 0, ',', '.') ?></span>
                </div>
                <div class="summary-card summary-total">
                    <span class="summary-label">Grand Total</span>
                    <span class="summary-value">Rp <?= number_format($grand_total_all, 0, ',', '.') ?></span>
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
                            <span class="money-val <?= $tr['pend'] > 0 ? 'money-positive' : 'money-zero' ?>">
                                Rp <?= number_format($tr['pend'], 0, ',', '.') ?>
                            </span>
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
/* ═══ PREMIUM REKAP STYLES ═══ */
.rekap-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
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
    margin-bottom: 20px;
}

.rekap-title-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.rekap-icon {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(99,102,241,0.25);
}
.rekap-icon i { color: #fff; font-size: 16px; }

.rekap-title-left h5 {
    margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;
    letter-spacing: -0.01em;
}
.rekap-title-left p {
    margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;
}

.rekap-filter {
    display: flex; gap: 8px; align-items: center;
}
.rekap-month-input {
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    color: #1e293b;
    background: #f8fafc;
    font-weight: 500;
    transition: all 0.2s;
}
.rekap-month-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
    outline: none;
    background: #fff;
}
.rekap-btn-cari {
    padding: 8px 20px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(99,102,241,0.25);
}
.rekap-btn-cari:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(99,102,241,0.35);
}

/* Summary Cards */
.summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 768px) {
    .summary-row { grid-template-columns: repeat(2, 1fr); }
}
.summary-card {
    padding: 14px 18px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.summary-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; }
.summary-value { font-size: 16px; font-weight: 800; letter-spacing: -0.01em; }

.summary-fee { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #065f46; }
.summary-income { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1e40af; }
.summary-bonus { background: linear-gradient(135deg, #fefce8, #fef9c3); color: #854d0e; }
.summary-total { background: linear-gradient(135deg, #f0f0ff, #e0e7ff); color: #3730a3; }

/* Table */
.rekap-table { table-layout: fixed; width: 100%; }

.rekap-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    padding: 12px 14px;
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    white-space: nowrap;
}

.rekap-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.15s;
}
.rekap-table tbody tr:hover { background: #f8fafc; }
.rekap-table tbody td { padding: 12px 14px; font-size: 13px; }

/* Teknisi name with avatar */
.tek-name {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #1e293b;
    font-weight: 700;
    font-size: 13px;
    transition: color 0.2s;
}
.tek-name:hover { color: #6366f1; }

.tek-avatar-circle {
    width: 32px; height: 32px;
    border-radius: 10px;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4338ca;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
    flex-shrink: 0;
}

/* Stat pills */
.stat-pill {
    display: inline-block;
    min-width: 28px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    text-align: center;
}
.stat-done { background: #dcfce7; color: #166534; }
.stat-inv { background: #dbeafe; color: #1e40af; }

/* Money values */
.money-val {
    font-size: 12px;
    font-weight: 700;
}
.money-positive { color: #16a34a; }
.money-zero { color: #cbd5e1; }

/* Footer */
.rekap-footer-row {
    background: linear-gradient(135deg, #1e293b, #334155) !important;
}
.rekap-footer-row td {
    color: #fff !important;
    font-size: 13px !important;
    padding: 14px !important;
    border: none !important;
}

@media print {
    .no-print { display: none !important; }
    .rekap-card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>