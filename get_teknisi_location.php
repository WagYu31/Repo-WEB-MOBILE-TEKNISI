<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once __DIR__ . '/conn.php';

try {
    // 1. Auto-create live location table if it does not exist yet
    $conn->query("CREATE TABLE IF NOT EXISTS `teknisi_live_location` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `teknisi_id` INT NOT NULL UNIQUE,
        `latitude` VARCHAR(50) NOT NULL,
        `longitude` VARCHAR(50) NOT NULL,
        `accuracy` VARCHAR(50) DEFAULT NULL,
        `speed` VARCHAR(50) DEFAULT NULL,
        `heading` VARCHAR(50) DEFAULT NULL,
        `is_mock` TINYINT(1) DEFAULT 0,
        `battery` VARCHAR(20) DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`teknisi_id`),
        INDEX (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `teknisi_location_log` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `teknisi_id` INT NOT NULL,
        `latitude` VARCHAR(50) NOT NULL,
        `longitude` VARCHAR(50) NOT NULL,
        `speed` VARCHAR(50) DEFAULT NULL,
        `is_mock` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`teknisi_id`),
        INDEX (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $teknisiId = isset($_GET['teknisi_id']) ? (int)$_GET['teknisi_id'] : null;

    if ($teknisiId) {
        $stmt = $conn->prepare("
            SELECT 
                t.id AS teknisi_id,
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
            FROM teknisi t
            LEFT JOIN teknisi_live_location loc ON loc.teknisi_id = t.id
            WHERE t.id = ? AND t.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->bind_param("i", $teknisiId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $hasCoords = !empty($row['latitude']) && !empty($row['longitude']);
            $row['has_location'] = $hasCoords;
            $row['is_online'] = ($hasCoords && $row['last_seen_seconds'] !== null && $row['last_seen_seconds'] <= 120);
            echo json_encode([
                'status' => true,
                'data' => $row,
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Data teknisi tidak ditemukan',
            ]);
        }
    } else {
        // Query all active technicians, joined with their latest location
        $query = "
            SELECT 
                t.id AS teknisi_id,
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
            FROM teknisi t
            LEFT JOIN teknisi_live_location loc ON loc.teknisi_id = t.id
            WHERE t.deleted_at IS NULL
            ORDER BY 
                (loc.latitude IS NOT NULL AND TIMESTAMPDIFF(SECOND, loc.updated_at, NOW()) <= 120) DESC,
                loc.updated_at DESC,
                t.nama ASC
        ";
        $result = $conn->query($query);
        $list = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $hasCoords = !empty($row['latitude']) && !empty($row['longitude']);
                $row['has_location'] = $hasCoords;
                $row['is_online'] = ($hasCoords && $row['last_seen_seconds'] !== null && $row['last_seen_seconds'] <= 120);
                $list[] = $row;
            }
        }

        echo json_encode([
            'status' => true,
            'count' => count($list),
            'data' => $list,
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
    ]);
}
?>
