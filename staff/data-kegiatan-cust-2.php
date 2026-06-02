<?php
// Set header ke JSON karena output file ini adalah data JSON
header('Content-Type: application/json');
include "conn.php";
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID.UTF-8', 'id_ID', 'IND.UTF8', 'IND.UTF-8', 'IND', 'Indonesian.UTF8', 'Indonesian.UTF-8', 'Indonesian');

// Fungsi pembantu untuk menerjemahkan status
if (!function_exists('translateActivityStatus')) {
    function translateActivityStatus(string $status): string
    {
        $statusMap = [
            'waiting'          => 'Dalam Antrian',
            'dijadwalkan'      => 'Dijadwalkan',
            'berjalan'         => 'Dalam Proses',
            'selesai'          => 'Selesai',
            'selesai by admin' => 'Diselesaikan Admin',
            'Lanjut Nanti'     => 'Berlanjut',
            'Lanjutan'         => 'Dilanjutkan',
        ];
        return $statusMap[$status] ?? ucfirst($status);
    }
}

// Inisialisasi struktur data default untuk response
$response_data = [
    'kegiatanHistory' => [],
    'relasiOptions' => []
];

// Validasi input GET
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode($response_data);
    exit;
}

$id_cust = (int)$_GET['customer_id'];

// Query yang efisien untuk mengambil semua data yang dibutuhkan
$sqlKC = "SELECT 
            k.jadwal, k.status, k.kegiatan, k.kode,
            GROUP_CONCAT(DISTINCT tk.nama_teknisi SEPARATOR ', ') AS nama_semua_teknisi
          FROM kegiatan k
          LEFT JOIN team_kegiatan tk ON k.kode = tk.kode
          WHERE k.customer_id = ? AND k.deleted_at IS NULL
          GROUP BY k.id
          ORDER BY k.jadwal DESC
          LIMIT 15";

$stmt = mysqli_prepare($conn, $sqlKC);
mysqli_stmt_bind_param($stmt, "i", $id_cust);
mysqli_stmt_execute($stmt);
$resultKC = mysqli_stmt_get_result($stmt);

if ($resultKC && mysqli_num_rows($resultKC) > 0) {
    while ($rowkc = mysqli_fetch_assoc($resultKC)) {
        $jadwal_timestamp = strtotime($rowkc['jadwal']);
        $isJadwalValid = (!empty($rowkc['jadwal']) && $rowkc['jadwal'] !== '0000-00-00 00:00:00');

        // Kumpulkan data mentah untuk riwayat yang akan ditampilkan
        $response_data['kegiatanHistory'][] = [
            'status' => translateActivityStatus($rowkc['status']),
            'teknisi' => !empty($rowkc['nama_semua_teknisi']) ? $rowkc['nama_semua_teknisi'] : "Belum ada teknisi",
            'jadwal_display' => $isJadwalValid ? strftime('%d %b %Y', $jadwal_timestamp) : 'Belum Dijadwalkan',
            'waktu_display' => $isJadwalValid ? date('H:i', $jadwal_timestamp) : '',
            'jenis' => ucfirst($rowkc['kegiatan'])
        ];

        // Kumpulkan data untuk dropdown relasi
        $option_text = 'Tidak Diketahui';
        if ($isJadwalValid) {
             $option_text = strftime('%d %b %Y', $jadwal_timestamp) . " - " . ucfirst($rowkc['kegiatan']);
        }
        $response_data['relasiOptions'][] = ['kode' => $rowkc['kode'], 'teks' => $option_text];
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Kembalikan response sebagai JSON
echo json_encode($response_data);
exit;
?>