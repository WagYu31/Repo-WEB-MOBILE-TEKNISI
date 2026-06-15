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

// Validate period format YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid period format. Use YYYY-MM']);
    exit;
}

// Query to get detail of invoices contributing to technician's revenue
$sql = "SELECT pk.kode, 
               pk.no_invoice, 
               pk.tanggal, 
               pk.nominal_invoice, 
               counts.tek_count, 
               ROUND(pk.nominal_invoice / counts.tek_count) as share_amount,
               c.nama AS nama_cust,
               (
                   SELECT GROUP_CONCAT(t.nama SEPARATOR ', ')
                   FROM pendapatan_kegiatan pk2
                   JOIN teknisi t ON t.id = pk2.teknisi_id
                   WHERE pk2.kode = pk.kode AND pk2.deleted_at IS NULL
               ) as nama_teknisi_group
        FROM pendapatan_kegiatan pk
        JOIN (
            SELECT kode, COUNT(*) as tek_count 
            FROM pendapatan_kegiatan 
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
            GROUP BY kode
        ) counts ON pk.kode = counts.kode
        JOIN kegiatan k ON k.kode = pk.kode
        JOIN customer c ON c.id = k.customer_id
        WHERE pk.teknisi_id = ? 
          AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? 
          AND pk.deleted_at IS NULL
        GROUP BY pk.kode
        ORDER BY pk.tanggal ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('sis', $period, $tech_id, $period);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
$total_share = 0;

while ($row = $res->fetch_assoc()) {
    $row['nominal_invoice'] = floatval($row['nominal_invoice']);
    $row['share_amount'] = floatval($row['share_amount']);
    $row['tek_count'] = intval($row['tek_count']);
    $total_share += $row['share_amount'];
    
    // Format date nicely
    $row['formatted_date'] = date('d M Y', strtotime($row['tanggal']));
    
    $data[] = $row;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'total_share' => $total_share,
    'formatted_total_share' => 'Rp ' . number_format($total_share, 0, ',', '.'),
    'data' => $data
]);
