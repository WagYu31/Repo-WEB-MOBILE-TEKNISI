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

// Auto-create live location table if it does not exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS `teknisi_live_location` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createTableQuery);

// Auto-create history log table for trip trail
$createLogQuery = "CREATE TABLE IF NOT EXISTS `teknisi_location_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `teknisi_id` INT NOT NULL,
    `latitude` VARCHAR(50) NOT NULL,
    `longitude` VARCHAR(50) NOT NULL,
    `speed` VARCHAR(50) DEFAULT NULL,
    `is_mock` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`teknisi_id`),
    INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createLogQuery);

// Read incoming JSON or POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$teknisiId = isset($data['teknisi_id']) ? $data['teknisi_id'] : ($_POST['teknisi_id'] ?? null);
$latitude = isset($data['latitude']) ? $data['latitude'] : ($_POST['latitude'] ?? null);
$longitude = isset($data['longitude']) ? $data['longitude'] : ($_POST['longitude'] ?? null);
$accuracy = isset($data['accuracy']) ? $data['accuracy'] : ($_POST['accuracy'] ?? null);
$speed = isset($data['speed']) ? $data['speed'] : ($_POST['speed'] ?? null);
$heading = isset($data['heading']) ? $data['heading'] : ($_POST['heading'] ?? null);
$isMock = isset($data['is_mock']) ? ($data['is_mock'] ? 1 : 0) : ($_POST['is_mock'] ?? 0);
$battery = isset($data['battery']) ? $data['battery'] : ($_POST['battery'] ?? null);

if (!$teknisiId || !$latitude || !$longitude) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Parameter teknisi_id, latitude, dan longitude wajib disertakan',
    ]);
    exit();
}

$teknisiId = (int)$teknisiId;
$latitude = (string)$latitude;
$longitude = (string)$longitude;
$accuracy = $accuracy !== null ? (string)$accuracy : null;
$speed = $speed !== null ? (string)$speed : null;
$heading = $heading !== null ? (string)$heading : null;
$isMock = (int)$isMock;
$battery = $battery !== null ? (string)$battery : null;

// Upsert live location
$stmt = $conn->prepare("INSERT INTO `teknisi_live_location` 
    (`teknisi_id`, `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `is_mock`, `battery`, `updated_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
    `latitude` = VALUES(`latitude`),
    `longitude` = VALUES(`longitude`),
    `accuracy` = VALUES(`accuracy`),
    `speed` = VALUES(`speed`),
    `heading` = VALUES(`heading`),
    `is_mock` = VALUES(`is_mock`),
    `battery` = VALUES(`battery`),
    `updated_at` = NOW()");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'DB Prepare Error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("isssssis", $teknisiId, $latitude, $longitude, $accuracy, $speed, $heading, $isMock, $battery);
$success = $stmt->execute();

if ($success) {
    // Record trail log
    $logStmt = $conn->prepare("INSERT INTO `teknisi_location_log` (`teknisi_id`, `latitude`, `longitude`, `speed`, `is_mock`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($logStmt) {
        $logStmt->bind_param("isssi", $teknisiId, $latitude, $longitude, $speed, $isMock);
        $logStmt->execute();
    }

    echo json_encode([
        'status' => true,
        'message' => 'Lokasi teknisi berhasil diperbarui',
        'data' => [
            'teknisi_id' => $teknisiId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'updated_at' => date('Y-m-d H:i:s'),
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal memperbarui lokasi: ' . $stmt->error,
    ]);
}
?>

