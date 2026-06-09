<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include 'conn.php';

$teknisiId = intval($_GET['teknisi_id'] ?? 0);
$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));

if ($teknisiId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'teknisi_id is required']);
    exit;
}

// === Quarter calculation (3-month cycle starting from June) ===
// Jun-Jul-Aug, Sep-Oct-Nov, Dec-Jan-Feb, Mar-Apr-May
$offset = (($bulan - 6) % 3 + 3) % 3;
$startBulan = $bulan - $offset;
$startTahun = $tahun;
if ($startBulan <= 0) {
    $startBulan += 12;
    $startTahun--;
}

// Build list of months in this quarter (from startBulan to current bulan)
$months = [];
$tmpBulan = $startBulan;
$tmpTahun = $startTahun;
for ($i = 0; $i <= $offset; $i++) {
    $months[] = sprintf('%04d-%02d', $tmpTahun, $tmpBulan);
    $tmpBulan++;
    if ($tmpBulan > 12) {
        $tmpBulan = 1;
        $tmpTahun++;
    }
}

// Quarter start/end dates for fee calculation
$quarterStart = sprintf('%04d-%02d-01', $startTahun, $startBulan);
$lastMonth = end($months);
$quarterEnd = date('Y-m-t', strtotime($lastMonth . '-01'));
reset($months);

// Month conditions for SQL
$monthConditions = implode(',', array_map(function($m) { return "'$m'"; }, $months));

$stmtInfo = $conn->prepare("SELECT id, nik, nama, target FROM teknisi WHERE id = ? AND deleted_at IS NULL");
$stmtInfo->bind_param("i", $teknisiId);
$stmtInfo->execute();
$teknisiInfo = $stmtInfo->get_result()->fetch_assoc();
$stmtInfo->close();

if (!$teknisiInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'Teknisi tidak ditemukan']);
    exit;
}

// Jumlah kegiatan (across quarter months)
$sql = "SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') IN ($monthConditions) AND tk.deleted_at IS NULL";
$stmtKegiatan = $conn->prepare($sql);
$stmtKegiatan->bind_param("i", $teknisiId);
$stmtKegiatan->execute();
$jumlahKegiatan = $stmtKegiatan->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtKegiatan->close();

// Selesai count
$sql = "SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') IN ($monthConditions) AND tk.deleted_at IS NULL AND k.status = 'selesai'";
$stmtSelesai = $conn->prepare($sql);
$stmtSelesai->bind_param("i", $teknisiId);
$stmtSelesai->execute();
$selesai = $stmtSelesai->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtSelesai->close();

// === Pendapatan (nominal_invoice / tek_count) across quarter ===
$sql = "SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') IN ($monthConditions) AND pk2.deleted_at IS NULL))), 0) AS total FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = ? AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($monthConditions) AND pk.deleted_at IS NULL";
$stmtPendapatan = $conn->prepare($sql);
$stmtPendapatan->bind_param("i", $teknisiId);
$stmtPendapatan->execute();
$totalPendapatan = floatval($stmtPendapatan->get_result()->fetch_assoc()['total'] ?? 0);
$stmtPendapatan->close();

// === Fee 30k (same as dashboard, across quarter) ===
$feeKodes = [];
$sql = "SELECT k.kode FROM kegiatan k 
        WHERE k.created_at >= '$quarterStart' AND k.created_at < DATE_ADD('$quarterEnd', INTERVAL 1 DAY)
        AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
        GROUP BY k.kode";
$res = mysqli_query($conn, $sql);
while ($r = mysqli_fetch_assoc($res)) $feeKodes[] = $r['kode'];

$totalFee = 0;
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
    
    $kodeTeknisi = [];
    while ($r = $res->fetch_assoc()) {
        $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
    }
    $stmt->close();
    
    foreach ($kodeTeknisi as $kd => $tekIds) {
        $jml = count($tekIds);
        if ($jml > 0 && isset($tekIds[$teknisiId])) {
            $totalFee += 30000 / $jml;
        }
    }
}

$target = floatval($teknisiInfo['target'] ?? 0);
$totalKeseluruhan = $totalPendapatan + $totalFee;
$bonus = ($totalKeseluruhan > $target && $target > 0) ? ($totalKeseluruhan - $target) * 0.60 : 0;

// Quarter info
$quarterLabel = '';
foreach ($months as $i => $m) {
    $dt = DateTime::createFromFormat('Y-m', $m);
    $quarterLabel .= ($i > 0 ? ', ' : '') . $dt->format('M Y');
}

$conn->close();

echo json_encode([
    'teknisi_id' => intval($teknisiInfo['id']),
    'nama_teknisi' => $teknisiInfo['nama'],
    'bulan' => $bulan,
    'tahun' => $tahun,
    'quarter_months' => $months,
    'quarter_label' => $quarterLabel,
    'quarter_month_number' => $offset + 1,
    'target' => intval($target),
    'jumlah_kegiatan' => intval($jumlahKegiatan),
    'selesai' => intval($selesai),
    'invoice' => 0,
    'fee' => intval($totalFee),
    'total_pendapatan' => intval($totalPendapatan),
    'total_keseluruhan' => intval($totalKeseluruhan),
    'bonus' => intval($bonus),
]);
