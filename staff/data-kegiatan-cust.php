<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

function translateActivityStatus($status) {
    $statusMap = [
        'waiting' => 'Dalam Antrian',
        'dijadwalkan' => 'Dijadwalkan',
        'berjalan' => 'Dalam Proses',
        'selesai' => 'Selesai',
        'selesai by admin' => 'Diselesaikan Admin',
        'Lanjut Nanti' => 'Berlanjut',
        'Lanjutan' => 'Dilanjutkan'
    ];
    return $statusMap[$status] ?? ucfirst($status);
}

$response_data = [
    'displayHtml' => '',
    'relasiOptions' => [],
    'locationRecommendations' => [],
    'lastKnownLocation' => null
];

if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode($response_data);
    exit;
}

$id_cust = (int)$_GET['customer_id'];

$sql = "SELECT 
            k.kode, 
            k.kegiatan, 
            MAX(k.jadwal) as jadwal_terakhir, 
            k.status,
            (SELECT GROUP_CONCAT(DISTINCT nama_teknisi SEPARATOR ', ') 
             FROM team_kegiatan 
             WHERE kode = k.kode AND deleted_at IS NULL) as teknisi_unik
        FROM kegiatan k 
        WHERE k.customer_id = ? AND k.deleted_at IS NULL 
        GROUP BY k.kode 
        ORDER BY jadwal_terakhir DESC 
        LIMIT 15";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cust);
$stmt->execute();
$result = $stmt->get_result();

$html = '<div class="card"><div class="card-header"><h5 class="mb-0">Riwayat Terakhir</h5></div><div class="list-group p-2">';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status_label = translateActivityStatus($row['status']);
        $tgl = date('d M Y', strtotime($row['jadwal_terakhir']));
        $nama_teknisi = !empty($row['teknisi_unik']) ? htmlspecialchars($row['teknisi_unik']) : "-";
        $kode_trans = htmlspecialchars($row['kode']);
        $jenis_kegiatan = htmlspecialchars(ucfirst($row['kegiatan']));

        $response_data['relasiOptions'][] = [
            'kode' => $kode_trans,
            'teks' => $tgl . " - " . $jenis_kegiatan
        ];

        $html .= "
        <a href='view-kegiatan.php?kode_transaksi={$kode_trans}' target='_blank' class='list-group-item list-group-item-action flex-column align-items-start'>
            <div class='d-flex w-100 justify-content-between'>
                <h6 class='mb-1 text-primary text-capitalize'>{$jenis_kegiatan}</h6>
                <small class='text-muted'>{$kode_trans}</small>
            </div>
            <p class='mb-1 text-sm' style='white-space: normal;'>Teknisi: {$nama_teknisi}</p>
            <div class='d-flex w-100 justify-content-between mt-1'>
                <small class='text-dark fw-bold'>Status: {$status_label}</small>
                <small class='text-dark'>{$tgl}</small>
            </div>
        </a>";
    }
} else {
    $html .= "<div class='p-3 text-center'>Tidak ada riwayat kegiatan untuk customer ini.</div>";
}
$html .= '</div></div>';

$response_data['displayHtml'] = $html;

$stmt_loc = $conn->prepare("SELECT alias, lat, lon, rad, address FROM cust_coordinate WHERE cust_id = ? AND deleted_at IS NULL ORDER BY id DESC");
$stmt_loc->bind_param("i", $id_cust);
$stmt_loc->execute();
$res_loc = $stmt_loc->get_result();
while ($loc = $res_loc->fetch_assoc()) {
    $response_data['locationRecommendations'][] = $loc;
    if (!$response_data['lastKnownLocation']) {
        $response_data['lastKnownLocation'] = $loc;
    }
}

echo json_encode($response_data);