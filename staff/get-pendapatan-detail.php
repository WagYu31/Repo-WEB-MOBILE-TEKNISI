<?php
header('Content-Type: application/json');
include "conn.php";
include "session.php";

// Check authentication
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tech_id = isset($_GET['tech_id']) ? intval($_GET['tech_id']) : 0;
$period = isset($_GET['period']) ? trim($_GET['period']) : '';

if ($tech_id <= 0 || empty($period)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Validate period format: single YYYY-MM or comma-separated YYYY-MM list
$periods = array_values(array_filter(array_map('trim', explode(',', $period)), function ($m) {
    return (bool)preg_match('/^\d{4}-\d{2}$/', $m);
}));
if (empty($periods)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid period format. Use YYYY-MM']);
    exit;
}

ensurePorsiKerjaColumn($conn);
$periodWhereSql = targetTercapaiPeriodWhereSql($periods, 'pk.tanggal', 'k_lunas.tgl_lunas');
$hasLunasPeriod = false;
foreach ($periods as $pm) {
    if (targetTercapaiUsesLunas($pm)) {
        $hasLunasPeriod = true;
        break;
    }
}
$orderBySql = $hasLunasPeriod ? 'COALESCE(k_lunas.tgl_lunas, pk.tanggal) ASC, pk.tanggal ASC' : 'pk.tanggal ASC';

// Query to get detail of invoices contributing to technician's revenue
$sql = "SELECT pk.kode, 
               pk.no_invoice, 
               pk.tanggal, 
               k_lunas.tgl_lunas,
               pk.nominal_invoice, 
               counts.tek_count, 
               COUNT(*) as tech_visit_count,
               ROUND((pk.nominal_invoice / counts.tek_count) * MIN(pk.porsi_kerja)) as single_share,
               SUM(ROUND((pk.nominal_invoice / counts.tek_count) * pk.porsi_kerja)) as share_amount,
               c.nama AS nama_cust,
                (
                    SELECT GROUP_CONCAT(CONCAT(t.nama, IF(sub.kegiatan_count > 1, CONCAT(' ', sub.kegiatan_count, 'x'), ''), IF(sub.min_porsi < 1, ' (½ Hari)', '')) ORDER BY sub.kegiatan_count DESC, t.nama ASC SEPARATOR ', ')
                    FROM (
                        SELECT pk2.kode, pk2.teknisi_id, COUNT(*) as kegiatan_count, MIN(pk2.porsi_kerja) as min_porsi
                        FROM pendapatan_kegiatan pk2
                        WHERE pk2.deleted_at IS NULL
                        GROUP BY pk2.kode, pk2.teknisi_id
                    ) sub
                    JOIN teknisi t ON t.id = sub.teknisi_id
                    WHERE sub.kode = pk.kode
                ) as nama_teknisi_group
        FROM pendapatan_kegiatan pk
        JOIN (
            SELECT kode, COUNT(*) as tek_count 
            FROM pendapatan_kegiatan 
            WHERE deleted_at IS NULL
            GROUP BY kode
        ) counts ON pk.kode = counts.kode
        JOIN " . targetTercapaiLunasSubquerySql('k_lunas') . " ON k_lunas.kode = pk.kode
        LEFT JOIN customer c ON c.id = k_lunas.customer_id
        WHERE pk.teknisi_id = ? 
          AND pk.deleted_at IS NULL
          AND $periodWhereSql
        GROUP BY pk.kode
        ORDER BY $orderBySql";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $tech_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
$total_share = 0;

while ($row = $res->fetch_assoc()) {
    $row['nominal_invoice'] = floatval($row['nominal_invoice']);
    $row['share_amount'] = floatval($row['share_amount']);
    $row['single_share'] = floatval($row['single_share']);
    $row['tech_visit_count'] = intval($row['tech_visit_count']);
    $row['tek_count'] = intval($row['tek_count']);
    $total_share += $row['share_amount'];
    
    $tglInvFmt = date('d M Y', strtotime($row['tanggal']));
    $tglLunasFmt = !empty($row['tgl_lunas']) ? date('d M Y', strtotime($row['tgl_lunas'])) : '';
    $lunasYm = !empty($row['tgl_lunas']) ? substr($row['tgl_lunas'], 0, 7) : '';

    if ($tglLunasFmt !== '' && in_array($lunasYm, $periods, true) && targetTercapaiUsesLunas($lunasYm)) {
        $row['formatted_date'] = $tglLunasFmt . ($tglLunasFmt !== $tglInvFmt ? " (Inv: $tglInvFmt)" : '');
    } else {
        $row['formatted_date'] = $tglInvFmt;
    }
    $row['formatted_lunas'] = $tglLunasFmt ?: 'Belum Lunas';
    
    $data[] = $row;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'total_share' => $total_share,
    'formatted_total_share' => 'Rp ' . number_format($total_share, 0, ',', '.'),
    'data' => $data
]);
