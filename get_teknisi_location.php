<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

include_once __DIR__ . '/conn.php';

$teknisiId = isset($_GET['teknisi_id']) ? (int)$_GET['teknisi_id'] : null;

if ($teknisiId) {
    $stmt = $conn->prepare("
        SELECT 
            loc.teknisi_id,
            t.nama,
            t.telp,
            loc.latitude,
            loc.longitude,
            loc.accuracy,
            loc.speed,
            loc.heading,
            loc.is_mock,
            loc.battery,
            loc.updated_at,
            TIMESTAMPDIFF(SECOND, loc.updated_at, NOW()) AS last_seen_seconds
        FROM teknisi_live_location loc
        LEFT JOIN teknisi t ON t.id = loc.teknisi_id
        WHERE loc.teknisi_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $teknisiId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $row['is_online'] = ($row['last_seen_seconds'] !== null && $row['last_seen_seconds'] <= 120);
        echo json_encode([
            'status' => true,
            'data' => $row,
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Lokasi teknisi belum tersedia',
        ]);
    }
} else {
    // Get all technicians live locations
    $query = "
        SELECT 
            loc.teknisi_id,
            t.nama,
            t.telp,
            loc.latitude,
            loc.longitude,
            loc.accuracy,
            loc.speed,
            loc.heading,
            loc.is_mock,
            loc.battery,
            loc.updated_at,
            TIMESTAMPDIFF(SECOND, loc.updated_at, NOW()) AS last_seen_seconds
        FROM teknisi_live_location loc
        LEFT JOIN teknisi t ON t.id = loc.teknisi_id
        ORDER BY loc.updated_at DESC
    ";
    $result = $conn->query($query);
    $list = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['is_online'] = ($row['last_seen_seconds'] !== null && $row['last_seen_seconds'] <= 120);
            $list[] = $row;
        }
    }

    echo json_encode([
        'status' => true,
        'count' => count($list),
        'data' => $list,
    ]);
}
?>

