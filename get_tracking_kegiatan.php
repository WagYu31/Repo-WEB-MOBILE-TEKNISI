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

// Haversine formula for distance between 2 coordinates in km
function calculateDistanceKm($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) return null;
    $lat1 = floatval($lat1);
    $lon1 = floatval($lon1);
    $lat2 = floatval($lat2);
    $lon2 = floatval($lon2);
    if ($lat1 == 0 || $lon1 == 0 || $lat2 == 0 || $lon2 == 0) return null;

    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}

try {
    date_default_timezone_set('Asia/Jakarta');
    $currentDate = isset($_GET['tanggal']) ? $_GET['tanggal'] : date("Y-m-d");

    // 1. Ensure live location table exists
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

    // 2. Query Kegiatan Hari Ini
    $sqlToday = "SELECT 
        k.id, k.kode, k.customer_id, k.kegiatan, k.jadwal, k.status, k.keterangan, 
        k.lat, k.lon, k.rad, k.alamat_lokasi, k.created_at,
        c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat AS cust_alamat
    FROM kegiatan k 
    LEFT JOIN customer c ON k.customer_id = c.id 
    WHERE k.status NOT IN ('waiting', 'selesai by admin') 
      AND DATE(k.jadwal) = ? 
      AND k.deleted_at IS NULL 
    ORDER BY k.jadwal ASC";

    $stmt = $conn->prepare($sqlToday);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $resultToday = $stmt->get_result();

    $groupedKegiatan = [];
    $allKegiatanIds = [];
    $allKodes = [];
    $allTeknisiIds = [];

    while ($r = $resultToday->fetch_assoc()) {
        $kode = $r['kode'];
        $groupedKegiatan[$kode][] = $r;
        $allKegiatanIds[$r['id']] = true;
        $allKodes[$kode] = true;
    }
    $stmt->close();

    // 3. Bulk fetch Team Teknisi
    $teknisiByKegiatan = [];
    if (!empty($allKegiatanIds)) {
        $idList = implode(',', array_map('intval', array_keys($allKegiatanIds)));
        $resTek = $conn->query("
            SELECT 
                tk.kegiatan_id, 
                tk.nama_teknisi, 
                tk.teknisi_id, 
                tk.is_ketua,
                t.telp AS no_hp
            FROM team_kegiatan tk
            LEFT JOIN teknisi t ON t.id = tk.teknisi_id
            WHERE tk.kegiatan_id IN ($idList) AND tk.deleted_at IS NULL
            GROUP BY tk.kegiatan_id, tk.teknisi_id
        ");
        if ($resTek) {
            while ($rt = $resTek->fetch_assoc()) {
                $teknisiByKegiatan[$rt['kegiatan_id']][] = $rt;
                $allTeknisiIds[$rt['teknisi_id']] = true;
            }
            $resTek->free();
        }
    }

    // 4. Bulk fetch Pelaksanaan Kegiatan (Status & Waktu Mulai)
    $pelaksanaanByKodeTek = [];
    if (!empty($allKodes)) {
        $escapedKodes = array_map(function($k) use ($conn) {
            return "'" . $conn->real_escape_string($k) . "'";
        }, array_keys($allKodes));
        $kodeList = implode(',', $escapedKodes);

        $resPel = $conn->query("
            SELECT kode, teknisi_id, status, waktu_mulai, waktu_selesai, latitude, longitude, latitude_s, longitude_s 
            FROM pelaksanaan_kegiatan 
            WHERE kode IN ($kodeList) AND DATE(waktu_mulai) = '$currentDate' 
            ORDER BY id DESC
        ");
        if ($resPel) {
            while ($rp = $resPel->fetch_assoc()) {
                $key = $rp['kode'] . '_' . $rp['teknisi_id'];
                if (!isset($pelaksanaanByKodeTek[$key])) {
                    $pelaksanaanByKodeTek[$key] = $rp;
                }
            }
            $resPel->free();
        }
    }

    // 5. Bulk fetch Live GPS dari teknisi_live_location
    $liveLocations = [];
    $teknisiIdList = array_map('intval', array_keys($allTeknisiIds));
    if (!empty($teknisiIdList)) {
        $tListStr = implode(',', $teknisiIdList);
        $resLoc = $conn->query("
            SELECT 
                teknisi_id, latitude, longitude, accuracy, speed, heading, battery, updated_at,
                TIMESTAMPDIFF(SECOND, updated_at, NOW()) AS last_seen_seconds
            FROM teknisi_live_location
            WHERE teknisi_id IN ($tListStr)
        ");
        if ($resLoc) {
            while ($rl = $resLoc->fetch_assoc()) {
                $rl['is_online'] = ($rl['last_seen_seconds'] !== null && $rl['last_seen_seconds'] <= 120);
                $liveLocations[$rl['teknisi_id']] = $rl;
            }
            $resLoc->free();
        }
    }

    // 6. Assemble response data per Kegiatan
    $kegiatanList = [];
    $totalOnlineTeknisi = 0;
    $countedOnlineTeknisi = [];

    foreach ($groupedKegiatan as $kodeTransaksi => $groupRows) {
        usort($groupRows, fn($a, $b) => $b['id'] - $a['id']);
        $keg = $groupRows[0];

        $custLat = !empty($keg['lat']) && is_numeric($keg['lat']) ? floatval($keg['lat']) : null;
        $custLon = !empty($keg['lon']) && is_numeric($keg['lon']) ? floatval($keg['lon']) : null;
        $alamatDisplay = !empty($keg['alamat_lokasi']) ? $keg['alamat_lokasi'] : ($keg['cust_alamat'] ?? '');

        // List Assigned Technicians
        $assignedTeknisi = [];
        $rawTekList = $teknisiByKegiatan[$keg['id']] ?? [];

        foreach ($rawTekList as $tek) {
            $tekId = intval($tek['teknisi_id']);
            $pelKey = $kodeTransaksi . '_' . $tekId;
            $pel = $pelaksanaanByKodeTek[$pelKey] ?? null;
            $live = $liveLocations[$tekId] ?? null;

            $statusKerja = $pel['status'] ?? 'Belum Mulai';
            $waktuMulai = (!empty($pel['waktu_mulai']) && $pel['waktu_mulai'] !== '0000-00-00 00:00:00') 
                ? date("H:i", strtotime($pel['waktu_mulai'])) 
                : null;
            $waktuSelesai = (!empty($pel['waktu_selesai']) && substr($pel['waktu_selesai'], 0, 10) !== '0000-00-00') 
                ? date("H:i", strtotime($pel['waktu_selesai'])) 
                : null;

            $liveLat = $live['latitude'] ?? null;
            $liveLon = $live['longitude'] ?? null;
            $isOnline = $live['is_online'] ?? false;
            $lastSeenSec = $live['last_seen_seconds'] ?? null;
            $speed = $live['speed'] ?? 0;

            if ($isOnline && !isset($countedOnlineTeknisi[$tekId])) {
                $totalOnlineTeknisi++;
                $countedOnlineTeknisi[$tekId] = true;
            }

            // Distance to customer
            $distToCust = null;
            if ($custLat !== null && $custLon !== null && $liveLat !== null && $liveLon !== null) {
                $distToCust = calculateDistanceKm($liveLat, $liveLon, $custLat, $custLon);
            }

            $assignedTeknisi[] = [
                'teknisi_id'        => $tekId,
                'nama'              => $tek['nama_teknisi'],
                'telp'              => $tek['no_hp'],
                'is_ketua'          => (int)$tek['is_ketua'],
                'status_kerja'      => $statusKerja,
                'waktu_mulai'       => $waktuMulai,
                'waktu_selesai'     => $waktuSelesai,
                'live_latitude'     => $liveLat,
                'live_longitude'    => $liveLon,
                'speed'             => $speed,
                'is_online'         => $isOnline,
                'last_seen_seconds' => $lastSeenSec,
                'jarak_ke_lokasi_km'=> $distToCust,
            ];
        }

        $kegiatanList[] = [
            'id'             => intval($keg['id']),
            'kode'           => $kodeTransaksi,
            'kegiatan'       => $keg['kegiatan'],
            'jadwal'         => date("H:i", strtotime($keg['jadwal'])),
            'customer_id'    => intval($keg['customer_id']),
            'nama_customer'  => $keg['nama_customer'],
            'cust_nomor'     => $keg['cust_nomor'],
            'keterangan'     => $keg['keterangan'] ?? '',
            'alamat'         => $alamatDisplay,
            'latitude'       => $custLat,
            'longitude'      => $custLon,
            'radius'         => intval($keg['rad'] ?? 50),
            'status'         => $keg['status'],
            'teknisi_list'   => $assignedTeknisi,
            'teknisi_count'  => count($assignedTeknisi),
        ];
    }

    echo json_encode([
        'status' => true,
        'tanggal' => $currentDate,
        'summary' => [
            'total_kegiatan'         => count($kegiatanList),
            'total_teknisi_terjadwal'=> count($allTeknisiIds),
            'teknisi_online'         => $totalOnlineTeknisi,
        ],
        'data' => $kegiatanList,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => 'Server Error: ' . $e->getMessage(),
    ]);
}
?>
