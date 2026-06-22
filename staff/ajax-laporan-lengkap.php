<?php
include "conn.php";
include "session.php";

if (!isset($_GET['bulan']) || !isset($_GET['tahun']) || !is_numeric($_GET['bulan']) || !is_numeric($_GET['tahun'])) {
    echo '<div class="ll-empty"><i class="fa-solid fa-triangle-exclamation" style="font-size:36px;opacity:0.3;margin-bottom:12px;"></i><p>Parameter bulan/tahun tidak valid.</p></div>';
    exit;
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$daftar_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$nama_bulan = $daftar_bulan[$bulan] ?? '';

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
                   WHERE pk.kode IN (
                       SELECT DISTINCT k2.kode FROM kegiatan k2
                       WHERE MONTH(k2.created_at) = ? AND YEAR(k2.created_at) = ? AND k2.deleted_at IS NULL
                   )
                   AND pk.deleted_at IS NULL
                   GROUP BY pk.kode
               ) sub";
$stmt_income = $conn->prepare($sql_income);
$stmt_income->bind_param("ii", $bulan, $tahun);
$stmt_income->execute();
$total_income = $stmt_income->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_income->close();
?>

<!-- Stats -->
<div class="ll-stats">
    <div class="ll-stat-box">
        <div class="ll-stat-dot ll-dot-blue"><i class="fa-solid fa-clipboard-list"></i></div>
        <div><div class="ll-stat-label">Total Kegiatan</div><div class="ll-stat-num"><?= $total_kegiatan ?></div></div>
    </div>
    <div class="ll-stat-box">
        <div class="ll-stat-dot ll-dot-emerald"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="ll-stat-label">Lunas</div><div class="ll-stat-num" style="color:#059669"><?= $total_lunas ?></div></div>
    </div>
    <div class="ll-stat-box">
        <div class="ll-stat-dot ll-dot-rose"><i class="fa-solid fa-clock"></i></div>
        <div><div class="ll-stat-label">Belum Lunas</div><div class="ll-stat-num" style="color:#e11d48"><?= $total_belum_lunas ?></div></div>
    </div>
    <div class="ll-stat-box">
        <div class="ll-stat-dot ll-dot-amber"><i class="fa-solid fa-wallet"></i></div>
        <div><div class="ll-stat-label">Total Pendapatan</div><div class="ll-stat-num" style="font-size:17px">Rp <?= number_format($total_income, 0, ',', '.') ?></div></div>
    </div>
</div>

<!-- Column Labels -->
<div class="ll-col-labels">
    <span>No</span>
    <span>Customer & Request</span>
    <span>Invoice & Pembayaran</span>
    <span>Teknisi & Pelaksanaan</span>
</div>

<!-- Data Cards -->
<?php
if (!empty($all_rows)) {
    $no = 0;
    foreach ($all_rows as $row_main) {
        $no++;
        $kode = $row_main['kode_transaksi'];
        $is_manual = is_numeric($row_main['paid']);
        $is_lunas = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00');
        $card_type = $is_lunas ? 'll-lunas' : ($is_manual ? 'll-manual' : 'll-unpaid');
        $overlay = $is_lunas ? 'll-lunas-overlay' : '';
?>
<div class="ll-card <?= $card_type ?>">
    <div class="ll-card-grid">
        <div><span class="ll-rnum"><?= $no ?></span></div>
        <div>
            <div class="ll-cname"><?= htmlspecialchars($row_main['nama_cust']); ?></div>
            <?php if (!empty($row_main['keterangan'])) : ?>
                <div class="ll-cket"><?= htmlspecialchars($row_main['keterangan']); ?></div>
            <?php endif; ?>
            <div class="ll-ctags">
                <span class="ll-ctag"><i class="fa-solid fa-hashtag"></i> <strong><?= $kode; ?></strong></span>
                <span class="ll-ctag"><i class="fa-regular fa-calendar"></i> <?= date("d M Y", strtotime($row_main['created_at'])); ?></span>
            </div>
        </div>
        <div>
            <?php
            $sql_inv = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
            $stmt_inv = $conn->prepare($sql_inv);
            $stmt_inv->bind_param("s", $kode);
            $stmt_inv->execute();
            $inv = $stmt_inv->get_result()->fetch_assoc();
            $stmt_inv->close();
            ?>
            <div class="ll-inv <?= $overlay ?>">
                <?php if ($inv) : ?>
                    <div class="ll-inv-lbl">No. Invoice</div>
                    <div class="ll-inv-no"><?= htmlspecialchars($inv['no_invoice']); ?></div>
                    <div class="ll-inv-lbl">Nominal</div>
                    <div class="ll-inv-amount">Rp <?= number_format($inv['nominal_invoice'], 0, ',', '.'); ?></div>
                    <hr class="ll-inv-sep">
                    <?php if ($is_lunas) : ?>
                        <span class="ll-pay-badge ll-pay-lunas"><i class="fa-solid fa-circle-check"></i> Lunas <?= date("d M Y", strtotime($row_main['lunas'])) ?></span>
                    <?php else : ?>
                        <span class="ll-pay-badge ll-pay-belum"><i class="fa-regular fa-clock"></i> Belum Lunas</span>
                    <?php endif; ?>
                <?php elseif ($is_manual) : ?>
                    <div class="ll-inv-lbl">Status</div>
                    <div class="ll-inv-notxt">Tidak Ada Invoice</div>
                    <div class="ll-inv-lbl">Nominal Manual</div>
                    <div class="ll-inv-amtsm">Rp 30.000</div>
                <?php else : ?>
                    <div style="text-align:center;padding:10px 0;">
                        <span class="ll-pay-none"><i class="fa-solid fa-ban"></i> NO PAYMENT</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                        WHERE k.kode = ? AND t.deleted_at IS NULL GROUP BY t.id";
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
            <div class="ll-tek">
                <div class="ll-tek-top">
                    <span class="ll-tek-nm"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($rt['nama_teknisi']); ?></span>
                    <span class="ll-tek-pay">Rp <?= number_format($ptampil, 0, ',', '.'); ?></span>
                </div>
                <?php if ($ada_abs) : ?>
                <div class="ll-tek-rows">
                    <?php while ($ra = $res_abs->fetch_assoc()) : ?>
                    <div class="ll-tek-row">
                        <span class="ll-tek-date"><?= date("d/m", strtotime($ra['tgl'])); ?></span>
                        <span class="ll-tek-t ll-tek-in"><i class="fa-solid fa-right-to-bracket"></i> <?= $ra['masuk'] ? date("H:i", strtotime($ra['masuk'])) : '-'; ?></span>
                        <span class="ll-tek-t ll-tek-out"><i class="fa-solid fa-right-from-bracket"></i> <?= $ra['pulang'] ? date("H:i", strtotime($ra['pulang'])) : '-'; ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else : ?>
                    <p class="ll-tek-none">Tidak ada data pelaksanaan.</p>
                <?php endif; ?>
            </div>
            <?php
                $stmt_abs->close();
            }
            $stmt_tek->close();
            if (!$has_tek) echo '<p class="ll-tek-none">Belum ada teknisi ditugaskan.</p>';
            ?>
        </div>
    </div>
</div>
<?php
    }
} else {
?>
<div class="ll-empty">
    <i class="fa-solid fa-inbox" style="font-size:36px;opacity:0.3;margin-bottom:12px;"></i>
    <p>Tidak ada data kegiatan untuk periode <strong><?= $nama_bulan . ' ' . $tahun ?></strong>.</p>
</div>
<?php } $conn->close(); ?>
