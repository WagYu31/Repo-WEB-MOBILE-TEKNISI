<?php
// Koneksi ke database
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_transaksi = $_POST['kode_transaksi'];
    $kode_invoice = $_POST['kode_invoice'];
    $nominal_invoice = preg_replace('/[^0-9]/', '', $_POST['nominal_invoice']); 
    $tanggal_invoice = $_POST['tanggal_invoice'];
    $tanggal_lunas = $_POST['tanggal_lunas'];
    $selected_kegiatan = isset($_POST['selected_kegiatan']) ? $_POST['selected_kegiatan'] : [];

    // Cek apakah kode transaksi sudah ada pada tabel pendapatan_kegiatan
    $sqlCheck = "SELECT id FROM pendapatan_kegiatan WHERE kode = '$kode_transaksi' AND deleted_at IS NULL";
    $resultCheck = mysqli_query($conn, $sqlCheck);

    if (mysqli_num_rows($resultCheck) > 0) {
        $sqlDelete = "UPDATE pendapatan_kegiatan SET deleted_at = NOW() WHERE kode = '$kode_transaksi'";
        mysqli_query($conn, $sqlDelete);
    }
    
    if (!empty($tanggal_lunas)) {
        $sqlUpdateKegiatan = "UPDATE kegiatan SET paid = 'yes', lunas = ? WHERE kode = ?";
        $stmtUpdate = mysqli_prepare($conn, $sqlUpdateKegiatan);
        mysqli_stmt_bind_param($stmtUpdate, 'ss', $tanggal_lunas, $kode_transaksi);
    } else {
        $sqlUpdateKegiatan = "UPDATE kegiatan SET paid = 'yes' WHERE kode = ?";
        $stmtUpdate = mysqli_prepare($conn, $sqlUpdateKegiatan);
        mysqli_stmt_bind_param($stmtUpdate, 's', $kode_transaksi);
    }
    
    
    mysqli_stmt_execute($stmtUpdate);

    // Hitung jumlah kegiatan per jenis
    $survey_ids = [];
    $pasang_baru_ids = [];
    $service_ids = [];
    
    foreach ($selected_kegiatan as $kegiatan_data) {
        list($kegiatan_id, $kegiatan_type) = explode('|', $kegiatan_data);
        
        if ($kegiatan_type == 'survey') {
            $survey_ids[] = $kegiatan_id;
        } elseif ($kegiatan_type == 'pasang baru') {
            $pasang_baru_ids[] = $kegiatan_id;
        } elseif ($kegiatan_type == 'service') {
            $service_ids[] = $kegiatan_id;
        }
    }
    
    $survey_count = count($survey_ids);
    $pasang_baru_count = count($pasang_baru_ids);
    $service_count = count($service_ids);
    
    // Hitung pembagian nominal
    if ($survey_count + $pasang_baru_count + $service_count > 0) {
        // Jika hanya ada 1 jenis kegiatan
        if (($survey_count > 0 && $pasang_baru_count == 0 && $service_count == 0) || 
            ($survey_count == 0 && $pasang_baru_count > 0 && $service_count == 0) || 
            ($survey_count == 0 && $pasang_baru_count == 0 && $service_count > 0)) {
            // Pembagian rata untuk semua
            $pendapatan_per_teknisi = $nominal_invoice / ($survey_count + $pasang_baru_count + $service_count);
            
            foreach ($selected_kegiatan as $kegiatan_data) {
                list($kegiatan_id, $kegiatan_type) = explode('|', $kegiatan_data);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pendapatan_per_teknisi, $nominal_invoice);
            }
        } 
        // Jika ada survey dan pasang baru
        elseif ($survey_count > 0 && $pasang_baru_count > 0 && $service_count == 0) {
            $survey_total = 0.1 * $nominal_invoice; // 10%
            $pasang_baru_total = 0.9 * $nominal_invoice; // 90%
            
            $survey_per_teknisi = $survey_total / $survey_count;
            $pasang_baru_per_teknisi = $pasang_baru_total / $pasang_baru_count;
            
            // Proses survey
            foreach ($survey_ids as $kegiatan_id) {
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $survey_per_teknisi, $nominal_invoice);
            }
            
            // Proses pasang baru
            foreach ($pasang_baru_ids as $kegiatan_id) {
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pasang_baru_per_teknisi, $nominal_invoice);
            }
        }
        // Jika ada ketiga jenis kegiatan
        elseif ($survey_count > 0 && $pasang_baru_count > 0 && $service_count > 0) {
            $survey_total = 0.05 * $nominal_invoice; // 5%
            $pasang_baru_total = 0.85 * $nominal_invoice; // 80%
            $service_total = 0.10 * $nominal_invoice; // 15%
            
            $survey_per_teknisi = $survey_total / $survey_count;
            $pasang_baru_per_teknisi = $pasang_baru_total / $pasang_baru_count;
            $service_per_teknisi = $service_total / $service_count;
            
            // Proses survey
            foreach ($survey_ids as $kegiatan_id) {
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $survey_per_teknisi, $nominal_invoice);
            }
            
            // Proses pasang baru
            foreach ($pasang_baru_ids as $kegiatan_id) {
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pasang_baru_per_teknisi, $nominal_invoice);
            }
            
            // Proses service
            foreach ($service_ids as $kegiatan_id) {
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $service_per_teknisi, $nominal_invoice);
            }
        }
        // Jika ada kombinasi lain (survey + service atau pasang baru + service)
        else {
            $pendapatan_per_teknisi = $nominal_invoice / ($survey_count + $pasang_baru_count + $service_count);
            
            foreach ($selected_kegiatan as $kegiatan_data) {
                list($kegiatan_id, $kegiatan_type) = explode('|', $kegiatan_data);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pendapatan_per_teknisi, $nominal_invoice);
            }
        }
    }
    
    if (isset($_POST['tambah_fee'])) {
        // Redirect atau tampilkan pesan sukses
        header("Location: proses_set_no_invoice.php?kode=$kode_transaksi");
        exit;
    }
    else {
        // Redirect atau tampilkan pesan sukses
        header("Location: lap-kegiatan.php");
        exit;
    }

}

// Fungsi untuk insert data pendapatan
function insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pendapatan, $nominal_invoice) {
    $sqlInsert = "INSERT INTO pendapatan_kegiatan (kegiatan_id, teknisi_id, kode, no_invoice, tanggal, pendapatan, nominal_invoice, created_at, updated_at) 
                  VALUES ('$kegiatan_id', 
                          (SELECT teknisi_id FROM pelaksanaan_kegiatan WHERE id = '$kegiatan_id'),
                          '$kode_transaksi', 
                          '$kode_invoice', 
                          '$tanggal_invoice', 
                          '$pendapatan', 
                          '$nominal_invoice', 
                          NOW(),
                          NOW())";
    mysqli_query($conn, $sqlInsert);
}
?>