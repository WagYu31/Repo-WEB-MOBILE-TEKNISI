<?php
header('Content-Type: application/json; charset=utf-8');
include 'conn.php';
include_once __DIR__ . '/include/geo_dispatch_helper.php';

date_default_timezone_set('Asia/Jakarta');

$selectedDate = $_GET['tanggal'] ?? null;
$withProximity = !empty($_GET['with_proximity']);
$targetKegiatanId = isset($_GET['kegiatan_id']) ? intval($_GET['kegiatan_id']) : 0;

if (!$selectedDate) {
    echo json_encode($withProximity ? ['schedules' => [], 'proximity' => [], 'target' => null] : []);
    exit;
}

// Fetch scheduled activities on selected date
$sql = "SELECT
            t.teknisi_id,
            t.nama_teknisi,
            t.is_ketua,
            k.id AS kegiatan_id,
            k.kegiatan AS jenis_kegiatan,
            k.jadwal,
            k.lat,
            k.lon,
            k.alamat_lokasi,
            c.id AS customer_id,
            c.nama AS nama_customer,
            c.alamat AS cust_alamat
        FROM team_kegiatan t
        JOIN kegiatan k ON t.kegiatan_id = k.id
        JOIN customer c ON k.customer_id = c.id
        WHERE
            DATE(k.jadwal) = ?
            AND t.deleted_at IS NULL
            AND k.deleted_at IS NULL
        ORDER BY k.jadwal ASC, t.teknisi_id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();

$rawRows = [];
$custIds = [];
$kegiatanTeams = []; // kegiatan_id => [ ['id'=>..., 'nama'=>..., 'is_ketua'=>...] ]

while ($row = $result->fetch_assoc()) {
    $rawRows[] = $row;
    if (!empty($row['customer_id'])) {
        $custIds[intval($row['customer_id'])] = true;
    }
    $kid = intval($row['kegiatan_id']);
    $tid = intval($row['teknisi_id']);
    $kegiatanTeams[$kid][$tid] = [
        'id' => $tid,
        'nama' => $row['nama_teknisi'],
        'is_ketua' => intval($row['is_ketua']),
    ];
}
$stmt->close();

// Optional target kegiatan lookup when with_proximity=1
$targetRow = null;
if ($withProximity && $targetKegiatanId > 0) {
    $stmtTarget = $conn->prepare("SELECT k.id, k.customer_id, k.kegiatan, k.jadwal, k.lat, k.lon, k.alamat_lokasi, c.nama AS nama_customer, c.alamat AS cust_alamat FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.id = ? LIMIT 1");
    if ($stmtTarget) {
        $stmtTarget->bind_param("i", $targetKegiatanId);
        $stmtTarget->execute();
        $resT = $stmtTarget->get_result();
        $targetRow = $resT ? $resT->fetch_assoc() : null;
        $stmtTarget->close();
        if ($targetRow && !empty($targetRow['customer_id'])) {
            $custIds[intval($targetRow['customer_id'])] = true;
        }
    }
}

// Bulk fetch fallback saved customer coordinates
$custCoordsMap = [];
if (!empty($custIds)) {
    $cIdList = implode(',', array_keys($custIds));
    $resCC = @$conn->query("SELECT cust_id, lat, lon, address FROM cust_coordinate WHERE cust_id IN ($cIdList) AND deleted_at IS NULL ORDER BY id DESC");
    if ($resCC) {
        while ($cc = $resCC->fetch_assoc()) {
            $cid = intval($cc['cust_id']);
            if (!isset($custCoordsMap[$cid]) && dispatchIsValidIndoCoord($cc['lat'], $cc['lon'])) {
                $custCoordsMap[$cid] = $cc;
            }
        }
        $resCC->free();
    }
}

$schedules = [];
$lastAnchorByTek = [];
$partnersByTek = [];

foreach ($rawRows as $row) {
    $tid = intval($row['teknisi_id']);
    $cid = intval($row['customer_id'] ?? 0);
    $kid = intval($row['kegiatan_id']);
    $zoneInfo = dispatchResolveZoneAndCoords(
        $row['lat'],
        $row['lon'],
        $row['alamat_lokasi'] ?? '',
        $row['cust_alamat'] ?? '',
        '',
        $custCoordsMap[$cid] ?? null
    );
    $waktu = date('H:i', strtotime($row['jadwal']));

    $schedules[$tid][] = [
        'kegiatan_id' => $kid,
        'customer' => $row['nama_customer'],
        'waktu' => $waktu,
        'jenis' => $row['jenis_kegiatan'],
        'zone' => $zoneInfo['zone'],
        'zone_label' => $zoneInfo['label'],
        'zone_bg' => $zoneInfo['bg'],
        'zone_color' => $zoneInfo['color'],
        'lat' => $zoneInfo['lat'],
        'lon' => $zoneInfo['lon'],
        'is_ketua' => intval($row['is_ketua']),
    ];

    if ($zoneInfo['lat'] !== null && $zoneInfo['lon'] !== null) {
        $lastAnchorByTek[$tid] = [
            'lat' => $zoneInfo['lat'],
            'lon' => $zoneInfo['lon'],
            'from_label' => $row['nama_customer'] . ' (' . $zoneInfo['label'] . ')',
            'waktu' => $waktu,
            'duration_min' => dispatchJobDurationMin($row['jenis_kegiatan']),
            'was_ketua' => intval($row['is_ketua']) === 1,
        ];
    }

    if (!empty($kegiatanTeams[$kid])) {
        foreach ($kegiatanTeams[$kid] as $partnerTid => $pInfo) {
            if ($partnerTid !== $tid) {
                $partnersByTek[$tid][$partnerTid] = $pInfo['nama'];
            }
        }
    }
}

// Legacy mode: return plain schedules map so all existing callers stay 100% intact
if (!$withProximity) {
    $conn->close();
    echo json_encode($schedules);
    exit;
}

$targetInfo = null;
if ($targetRow) {
    $tCid = intval($targetRow['customer_id'] ?? 0);
    $tZone = dispatchResolveZoneAndCoords(
        $targetRow['lat'],
        $targetRow['lon'],
        $targetRow['alamat_lokasi'] ?? '',
        $targetRow['cust_alamat'] ?? '',
        '',
        $custCoordsMap[$tCid] ?? null
    );
    $targetInfo = [
        'id' => intval($targetRow['id']),
        'customer' => $targetRow['nama_customer'] ?? '',
        'kegiatan' => $targetRow['kegiatan'] ?? '',
        'zone' => $tZone['zone'],
        'zone_label' => $tZone['label'],
        'zone_bg' => $tZone['bg'],
        'zone_color' => $tZone['color'],
        'zone_border' => $tZone['border'],
        'lat' => $tZone['lat'],
        'lon' => $tZone['lon'],
        'is_exact' => $tZone['is_exact'],
    ];
}

// Check live GPS when scheduling for today for technicians without scheduled anchor
if ($selectedDate === date('Y-m-d')) {
    $resLive = @$conn->query("SELECT teknisi_id, latitude, longitude, TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS mins_ago FROM teknisi_live_location WHERE COALESCE(gps_disabled, 0) = 0 AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) <= 180");
    if ($resLive) {
        while ($lv = $resLive->fetch_assoc()) {
            $ltid = intval($lv['teknisi_id']);
            if (!isset($lastAnchorByTek[$ltid]) && dispatchIsValidIndoCoord($lv['latitude'], $lv['longitude'])) {
                $lvZone = dispatchResolveZoneAndCoords($lv['latitude'], $lv['longitude']);
                $lastAnchorByTek[$ltid] = [
                    'lat' => floatval($lv['latitude']),
                    'lon' => floatval($lv['longitude']),
                    'from_label' => 'GPS Live (' . $lvZone['label'] . ')',
                    'waktu' => null,
                    'duration_min' => 0,
                    'was_ketua' => false,
                ];
            }
        }
        $resLive->free();
    }
}

// Compute proximity per technician
$proximity = [];
$resAllTek = $conn->query("SELECT id FROM teknisi WHERE deleted_at IS NULL");
if ($resAllTek) {
    while ($tk = $resAllTek->fetch_assoc()) {
        $tid = intval($tk['id']);
        $hasJobs = !empty($schedules[$tid]);
        $anchor = $lastAnchorByTek[$tid] ?? null;
        $partnerIds = isset($partnersByTek[$tid]) ? array_map('intval', array_keys($partnersByTek[$tid])) : [];
        $partnerNames = isset($partnersByTek[$tid]) ? array_values($partnersByTek[$tid]) : [];

        $entry = [
            'has_jobs' => $hasJobs,
            'job_count' => $hasJobs ? count($schedules[$tid]) : 0,
            'status' => $hasJobs ? 'unknown' : 'kosong',
            'km' => null,
            'minutes' => null,
            'from_label' => $anchor['from_label'] ?? null,
            'suggested_jam' => null,
            'was_ketua' => $anchor['was_ketua'] ?? false,
            'partner_ids' => $partnerIds,
            'partner_names' => $partnerNames,
        ];

        if ($anchor && $targetInfo && $targetInfo['lat'] !== null && $targetInfo['lon'] !== null) {
            $est = dispatchRoadEstimate($anchor['lat'], $anchor['lon'], $targetInfo['lat'], $targetInfo['lon']);
            if ($est) {
                $entry['status'] = $est['status']; // searah | sedang | lawan_arah
                $entry['km'] = $est['km'];
                $entry['minutes'] = $est['minutes'];
                if (!empty($anchor['waktu'])) {
                    $parts = explode(':', $anchor['waktu']);
                    $startMin = intval($parts[0]) * 60 + intval($parts[1]);
                    $nextMin = $startMin + intval($anchor['duration_min']) + intval($est['minutes']);
                    $nextMin = (int)(ceil($nextMin / 15) * 15);
                    if ($nextMin < 21 * 60) {
                        $entry['suggested_jam'] = sprintf('%02d:%02d', intdiv($nextMin, 60), $nextMin % 60);
                    }
                }
            }
        }

        $proximity[$tid] = $entry;
    }
    $resAllTek->free();
}

$conn->close();
echo json_encode([
    'schedules' => $schedules,
    'proximity' => $proximity,
    'target' => $targetInfo,
]);
