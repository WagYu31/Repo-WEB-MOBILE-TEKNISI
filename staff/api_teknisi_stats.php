<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'conn.php';

$teknisiId = intval($_GET['teknisi_id'] ?? 0);
$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));

if ($teknisiId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'teknisi_id is required']);
    exit;
}

$date = sprintf('%04d-%02d', $tahun, $bulan);
$monthStart = $date . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

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

// Jumlah kegiatan
$stmtKegiatan = $conn->prepare("SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL");
$stmtKegiatan->bind_param("is", $teknisiId, $date);
$stmtKegiatan->execute();
$jumlahKegiatan = $stmtKegiatan->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtKegiatan->close();

// Selesai count
$stmtSelesai = $conn->prepare("SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL AND k.status = 'selesai'");
$stmtSelesai->bind_param("is", $teknisiId, $date);
$stmtSelesai->execute();
$selesai = $stmtSelesai->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtSelesai->close();

// === Pendapatan (SAME as dashboard: nominal_invoice / tek_count) ===
$stmtPendapatan = $conn->prepare("SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL))), 0) AS total FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = ? AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL");
$stmtPendapatan->bind_param("sis", $date, $teknisiId, $date);
$stmtPendapatan->execute();
$totalPendapatan = floatval($stmtPendapatan->get_result()->fetch_assoc()['total'] ?? 0);
$stmtPendapatan->close();

// === Fee 30k (SAME as dashboard: 30k split per teknisi for non-invoiced kegiatan) ===
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

$conn->close();

echo json_encode([
    'teknisi_id' => intval($teknisiInfo['id']),
    'nama_teknisi' => $teknisiInfo['nama'],
    'bulan' => $bulan,
    'tahun' => $tahun,
    'target' => intval($target),
    'jumlah_kegiatan' => intval($jumlahKegiatan),
    'selesai' => intval($selesai),
    'invoice' => 0,
    'fee' => intval($totalFee),
    'total_pendapatan' => intval($totalPendapatan),
    'total_keseluruhan' => intval($totalKeseluruhan),
    'bonus' => intval($bonus),
]);
