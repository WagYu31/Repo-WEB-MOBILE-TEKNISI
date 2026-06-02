<?php
// Set header ke JSON karena output file ini adalah data JSON
header('Content-Type: application/json');
include "conn.php";

// Inisialisasi struktur data default untuk response
$response_data = [
    'displayHtml'   => '<div class="alert alert-danger mx-4">Parameter customer tidak valid.</div>',
    'relasiOptions' => []
];

if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode($response_data);
    exit; // Hentikan eksekusi jika tidak ada ID
}

$id_cust = $_GET['customer_id'];

// Query tunggal yang efisien untuk mengambil semua data yang dibutuhkan
$sqlKC = "SELECT 
            k.jadwal, 
            k.status, 
            k.kegiatan,
            k.kode, -- Ambil kode untuk relasi
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

// Inisialisasi variabel untuk menampung hasil
$list_items_html = '';
$relasi_options_data = [];

if (mysqli_num_rows($resultKC) > 0) {
    while ($rowkc = mysqli_fetch_assoc($resultKC)) {
        $tglRec = $rowkc['jadwal'];
        $status = $rowkc['status'];

        include "include/status.php"; // File ini mengubah $status menjadi $status_terubah

        $namaTeknisi = !empty($rowkc['nama_semua_teknisi']) ? htmlspecialchars($rowkc['nama_semua_teknisi']) : "Belum ada teknisi";
        
        // 1. Kumpulkan data untuk array relasiOptions
        $option_text = 'Tidak Diketahui';
        if (!empty($tglRec) && $tglRec !== '0000-00-00 00:00:00') {
             $option_text = strftime('%d %b %Y', strtotime($tglRec)) . " - " . htmlspecialchars(ucfirst($rowkc['kegiatan']));
        }
       
        $relasi_options_data[] = [
            'kode' => $rowkc['kode'],
            'teks' => $option_text
        ];

        // 2. Susun string HTML untuk setiap item list
        $tanggal_html = "<h6 class='mb-1 text-dark font-weight-bold text-sm'>Belum Dijadwalkan</h6>";
        if (!empty($tglRec) && $tglRec !== '0000-00-00 00:00:00') {
            $tanggal_html = "<h6 class='mb-1 text-dark font-weight-bold text-sm'>" . strftime('%d %b %Y', strtotime($tglRec)) . "</h6>" .
                            "<span class='text-xs'>" . date('H:i', strtotime($tglRec)) . "</span>";
        }

        $list_items_html .= "
        <li class='list-group-item border-0 d-flex flex-row justify-content-between align-items-center ps-0 mb-2 border-radius-lg'>
            <div class='row px-4' style='width: 100%;'>
                <div class='col-3'>
                    <h6 class='mb-1 text-dark font-weight-bold text-sm'>" . htmlspecialchars($status_terubah) . "</h6>
                </div>
                <div class='col-4'>
                    <h6 class='mb-1 text-dark font-weight-bold text-sm'>{$namaTeknisi}</h6>
                </div>
                <div class='col-3'>
                    {$tanggal_html}
                </div>
                <div class='col-2'>
                    <h6 class='mb-1 text-dark font-weight-bold text-sm'>" . htmlspecialchars(ucfirst($rowkc['kegiatan'])) . "</h6>
                </div>
            </div>
        </li>";
    }
} else {
    $list_items_html = '<li class="list-group-item border-0">Tidak ada riwayat kegiatan untuk customer ini.</li>';
}

// Bungkus semua item list HTML yang sudah terkumpul dengan card-nya
$final_html_output = '
<div class="card z-index-2">
    <h4 class="pt-3 ps-4 pb-0">History Kunjungan</h4>
    <div class="card-header col-12 p-0 position-relative mt-3 mx-3 z-index-2 bg-transparent">
        <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
            <div class="row px-4 d-flex flex-row justify-content-between align-items-center">
                <div class="col-3"><h6 class="mb-1 text-white font-weight-bold text-sm">Status</h6></div>
                <div class="col-4"><h6 class="mb-1 text-white font-weight-bold text-sm">Teknisi</h6></div>
                <div class="col-3"><h6 class="mb-1 text-white font-weight-bold text-sm">Jadwal</h6></div>
                <div class="col-2"><h6 class="mb-1 text-white font-weight-bold text-sm">Jenis</h6></div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <ul class="list-group m-0 mt-0 col-12" id="data-kegiatan-cust">' . $list_items_html . '</ul>
    </div>
</div>';

// Susun data final untuk di-encode ke JSON
$response_data['displayHtml'] = $final_html_output;
$response_data['relasiOptions'] = $relasi_options_data;

mysqli_stmt_close($stmt);

// Kembalikan response sebagai JSON
echo json_encode($response_data);
exit;
?>