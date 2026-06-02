<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';
setlocale(LC_TIME, 'id_ID.utf8');

function translateActivityStatus(string $status): string {
    $statusMap = [ 'waiting' => 'Dalam Antrian', 'dijadwalkan' => 'Dijadwalkan', 'berjalan' => 'Dalam Proses', 'selesai' => 'Selesai', 'selesai by admin' => 'Diselesaikan Admin', 'Lanjut Nanti' => 'Berlanjut', 'Lanjutan' => 'Dilanjutkan' ];
    return $statusMap[$status] ?? ucfirst($status);
}

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

function clusterLocations($locations, $radiusMeters = 100) {
    $clustered = [];
    foreach ($locations as $location) {
        $isClustered = false;
        foreach ($clustered as $cluster) {
            if (haversineDistance($location['lat'], $location['lon'], $cluster['lat'], $cluster['lon']) <= $radiusMeters) {
                $isClustered = true;
                break;
            }
        }
        if (!$isClustered) {
            $clustered[] = $location;
        }
    }
    return $clustered;
}

function getAddressFromCoordinates($lat, $lon) {
    if (empty($lat) || empty($lon)) return null;
    $cacheKey = "geo_" . md5($lat . $lon);
    if (isset($_SESSION[$cacheKey])) return $_SESSION[$cacheKey];
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
    $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['address']['road'])) {
            $address = $data['address']['road'];
            if (isset($data['address']['house_number'])) {
                $address .= ' ' . $data['address']['house_number'];
            }
            $_SESSION[$cacheKey] = $address;
            return $address;
        }
    }
    return null;
}

$response_data = ['displayHtml' => '<div class="alert alert-danger mx-4">Parameter customer tidak valid.</div>', 'relasiOptions' => [], 'locationRecommendations' => []];

if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode($response_data);
    exit;
}

$id_cust = (int)$_GET['customer_id'];

$sqlKC = "SELECT 
            k.kode, 
            k.kegiatan, 
            MIN(k.jadwal) AS tanggal_pertama, 
            MAX(k.jadwal) AS tanggal_terakhir,
            (SELECT status FROM kegiatan WHERE kode = k.kode AND kegiatan = k.kegiatan AND customer_id = ? ORDER BY id DESC LIMIT 1) AS status_terakhir,
            GROUP_CONCAT(DISTINCT tk.nama_teknisi SEPARATOR ', ') AS nama_semua_teknisi
          FROM kegiatan k 
          LEFT JOIN team_kegiatan tk ON k.kode = tk.kode AND tk.deleted_at IS NULL
          WHERE k.customer_id = ? AND k.deleted_at IS NULL 
          GROUP BY k.kode, k.kegiatan 
          ORDER BY tanggal_terakhir DESC 
          LIMIT 15";

$stmt = $conn->prepare($sqlKC);
$stmt->bind_param("ii", $id_cust, $id_cust);
$stmt->execute();
$resultKC = $stmt->get_result();

$list_items_html = '';
$relasi_options_data = [];

if ($resultKC && $resultKC->num_rows > 0) {
    while ($rowkc = $resultKC->fetch_assoc()) {
        $status_terubah = translateActivityStatus($rowkc['status_terakhir']);
        $namaTeknisi = !empty($rowkc['nama_semua_teknisi']) ? htmlspecialchars($rowkc['nama_semua_teknisi']) : "Belum ada teknisi";
        
        $tanggal_pertama_formatted = strftime('%d %b %Y', strtotime($rowkc['tanggal_pertama']));
        $tanggal_terakhir_formatted = strftime('%d %b %Y', strtotime($rowkc['tanggal_terakhir']));
        
        if ($tanggal_pertama_formatted == $tanggal_terakhir_formatted) {
            $tanggal_display = $tanggal_terakhir_formatted;
        } else {
            $tanggal_display = $tanggal_pertama_formatted . " - " . $tanggal_terakhir_formatted;
        }

        $relasi_options_data[] = ['kode' => $rowkc['kode'], 'teks' => $tanggal_terakhir_formatted . " - " . htmlspecialchars(ucfirst($rowkc['kegiatan']))];
        $jenis_kegiatan = htmlspecialchars(ucfirst($rowkc['kegiatan']));
        $status_display = htmlspecialchars($status_terubah);
        $theCode = htmlspecialchars(ucfirst($rowkc['kode']));

        $list_items_html .= <<<HTML
        <a href="view-kegiatan.php?kode_transaksi={$theCode}" target="_blank" class="list-group-item list-group-item-action flex-column align-items-start">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1 text-primary text-capitalize">{$jenis_kegiatan}</h6>
                <small class="text-muted">{$theCode}</small>
            </div>
            <p class="mb-1 text-sm">Teknisi: {$namaTeknisi}</p>
            <div class="d-flex w-100 justify-content-between">
                <small class="text-dark fw-bold">Status: {$status_display}</small>
                <small class="text-dark">{$tanggal_display}</small>
            </div>
        </a>
HTML;
    }
} else {
    $list_items_html = '<div class="list-group-item">Tidak ada riwayat kegiatan untuk customer ini.</div>';
}

$stmt->close();

$stmt_loc = $conn->prepare("SELECT DISTINCT lat, lon, rad FROM kegiatan WHERE customer_id = ? AND lat IS NOT NULL AND lon IS NOT NULL ORDER BY id DESC");
$stmt_loc->bind_param("i", $id_cust);
$stmt_loc->execute();
$result_loc = $stmt_loc->get_result();
$locations = $result_loc->fetch_all(MYSQLI_ASSOC);
$stmt_loc->close();
if (!empty($locations)) {
    $clustered_locations = clusterLocations($locations);
    foreach($clustered_locations as $loc) {
        $response_data['locationRecommendations'][] = [
            'lat' => $loc['lat'],
            'lon' => $loc['lon'],
            'rad' => $loc['rad'],
            'address' => getAddressFromCoordinates($loc['lat'], $loc['lon']) ?: "Lokasi ({$loc['lat']}, {$loc['lon']})"
        ];
    }
}

$final_html_output = <<<HTML
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Riwayat Kunjungan Terakhir</h5>
    </div>
    <div class="card-body p-2">
        <div class="list-group">
            {$list_items_html}
        </div>
    </div>
</div>
HTML;

$response_data['displayHtml'] = $final_html_output;
$response_data['relasiOptions'] = $relasi_options_data;

echo json_encode($response_data);
exit;
?>