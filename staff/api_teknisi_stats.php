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

// Jumlah kegiatan - fetch from Laravel API (same source as Statistik page)
$laravelUrl = "https://api-teknisi.id-giti.com/api/v4/teknisi/pencapaian/{$teknisiId}/{$bulan}/{$tahun}";
$ch = curl_init($laravelUrl);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
$laravelResp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
    // Mengambil jumlah kegiatan yang SUDAH LUNAS di bulan ini (berdasarkan pendapatan_kegiatan)
    $currentYm = sprintf('%04d-%02d', $tahun, $bulan);
    $sql_lunas = "SELECT COUNT(DISTINCT pk.kode) AS jumlah_lunas FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = ? AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL";
    $stmt_lunas = $conn->prepare($sql_lunas);
    $stmt_lunas->bind_param("is", $teknisiId, $currentYm);
    $stmt_lunas->execute();
    $jumlahKegiatan = $stmt_lunas->get_result()->fetch_assoc()['jumlah_lunas'] ?? 0;
    $stmt_lunas->close();

// Selesai count (consistent with Laporan Detail: pelaksanaan with status selesai)
$sql = "SELECT COUNT(DISTINCT pk.kode) AS jumlah FROM pelaksanaan_kegiatan pk JOIN kegiatan k ON k.id = pk.kegiatan_id WHERE pk.teknisi_id = ? AND pk.deleted_at IS NULL AND k.deleted_at IS NULL AND DATE(pk.waktu_mulai) >= ? AND DATE(pk.waktu_mulai) <= ? AND k.status IN ('selesai', 'selesai by admin')";
$stmtSelesai = $conn->prepare($sql);
$stmtSelesai->bind_param("iss", $teknisiId, $quarterStart, $quarterEnd);
$stmtSelesai->execute();
$selesai = $stmtSelesai->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtSelesai->close();

// === Pendapatan (nominal_invoice / tek_count) — single month, dedup by kode ===
// Must match web laporan-db.php BATCH QUERY 4 exactly
$currentYm = sprintf('%04d-%02d', $tahun, $bulan);
$sql = "SELECT COALESCE(SUM(share_amount), 0) AS total FROM (
    SELECT pk.kode,
           ROUND(pk.nominal_invoice / counts.tek_count) AS share_amount
    FROM pendapatan_kegiatan pk
    JOIN (
        SELECT kode, COUNT(*) AS tek_count
        FROM pendapatan_kegiatan
        WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
        GROUP BY kode
    ) counts ON pk.kode = counts.kode
    WHERE pk.teknisi_id = ?
    AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ?
    AND pk.deleted_at IS NULL
    GROUP BY pk.kode
) deduped";
$stmtPendapatan = $conn->prepare($sql);
$stmtPendapatan->bind_param("sis", $currentYm, $teknisiId, $currentYm);
$stmtPendapatan->execute();
$totalPendapatan = floatval($stmtPendapatan->get_result()->fetch_assoc()['total'] ?? 0);
$stmtPendapatan->close();

// === Fee 30k (single month, matching web) ===
$monthStart = sprintf('%04d-%02d-01', $tahun, $bulan);
$monthEnd = date('Y-m-t', strtotime($monthStart));
$feeKodes = [];
$sql = "SELECT k.kode FROM kegiatan k 
        WHERE k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
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
