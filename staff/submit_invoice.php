<?php
// Koneksi ke database
include 'conn.php';
include_once __DIR__ . '/include/format_tanggal.php';
ensurePorsiKerjaColumn($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_transaksi = $_POST['kode_transaksi'];
    $kode_invoice = trim($_POST['kode_invoice']);
    $nominal_invoice = preg_replace('/[^0-9]/', '', $_POST['nominal_invoice']); 
    $tanggal_invoice = $_POST['tanggal_invoice'];
    $tanggal_lunas = $_POST['tanggal_lunas'];
    $selected_kegiatan = isset($_POST['selected_kegiatan']) ? $_POST['selected_kegiatan'] : [];
    $porsi_map = (isset($_POST['porsi_kegiatan']) && is_array($_POST['porsi_kegiatan'])) ? $_POST['porsi_kegiatan'] : [];
    $getPorsi = function ($id) use ($porsi_map) {
        return (isset($porsi_map[$id]) && abs(floatval($porsi_map[$id]) - 0.5) < 0.01) ? 0.50 : 1.00;
    };

    // === Validasi duplikat kode invoice ===
    $sqlDupCheck = "SELECT id, kode FROM pendapatan_kegiatan WHERE no_invoice = ? AND kode != ? AND deleted_at IS NULL LIMIT 1";
    $stmtDup = $conn->prepare($sqlDupCheck);
    $stmtDup->bind_param('ss', $kode_invoice, $kode_transaksi);
    $stmtDup->execute();
    $resDup = $stmtDup->get_result();
    if ($resDup->num_rows > 0) {
        $rowDup = $resDup->fetch_assoc();
        $stmtDup->close();
        echo "<script>alert('Kode Invoice \\\"" . addslashes($kode_invoice) . "\\\" sudah digunakan pada transaksi " . addslashes($rowDup['kode']) . ". Gunakan kode invoice yang berbeda.'); window.history.back();</script>";
        exit;
    }
    $stmtDup->close();

    // Cek apakah kode transaksi sudah ada pada tabel pendapatan_kegiatan
    $stmtCheck = $conn->prepare("SELECT id FROM pendapatan_kegiatan WHERE kode = ? AND deleted_at IS NULL");
    $stmtCheck->bind_param('s', $kode_transaksi);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck && $resultCheck->num_rows > 0) {
        $stmtDel = $conn->prepare("UPDATE pendapatan_kegiatan SET deleted_at = NOW() WHERE kode = ? AND deleted_at IS NULL");
        $stmtDel->bind_param('s', $kode_transaksi);
        $stmtDel->execute();
        $stmtDel->close();
    }
    $stmtCheck->close();
    
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
        $kegiatan_type_clean = strtolower(trim($kegiatan_type));
        
        if ($kegiatan_type_clean == 'survey') {
            $survey_ids[] = $kegiatan_id;
        } elseif ($kegiatan_type_clean == 'pasang baru') {
            $pasang_baru_ids[] = $kegiatan_id;
        } elseif ($kegiatan_type_clean == 'service') {
            $service_ids[] = $kegiatan_id;
        }
    }
    
    $survey_count = count($survey_ids);
    $pasang_baru_count = count($pasang_baru_ids);
    $service_count = count($service_ids);
    $total_selected_count = count($selected_kegiatan);
    
    // Hitung pembagian nominal (base share dikali porsi_kerja: 1.00 Full / 0.50 Setengah Hari)
    if ($total_selected_count > 0) {
        // Jika hanya ada 1 jenis kegiatan
        if (($survey_count > 0 && $pasang_baru_count == 0 && $service_count == 0) || 
            ($survey_count == 0 && $pasang_baru_count > 0 && $service_count == 0) || 
            ($survey_count == 0 && $pasang_baru_count == 0 && $service_count > 0)) {
            // Pembagian rata untuk semua, lalu kalikan porsi kerja tiap teknisi
            $pendapatan_per_teknisi = $nominal_invoice / ($survey_count + $pasang_baru_count + $service_count);
            
            foreach ($selected_kegiatan as $kegiatan_data) {
                list($kegiatan_id, $kegiatan_type) = explode('|', $kegiatan_data);
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($pendapatan_per_teknisi * $porsi), $porsi, $nominal_invoice);
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
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($survey_per_teknisi * $porsi), $porsi, $nominal_invoice);
            }
            
            // Proses pasang baru
            foreach ($pasang_baru_ids as $kegiatan_id) {
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($pasang_baru_per_teknisi * $porsi), $porsi, $nominal_invoice);
            }
        }
        // Jika ada ketiga jenis kegiatan
        elseif ($survey_count > 0 && $pasang_baru_count > 0 && $service_count > 0) {
            $survey_total = 0.05 * $nominal_invoice; // 5%
            $pasang_baru_total = 0.85 * $nominal_invoice; // 85%
            $service_total = 0.10 * $nominal_invoice; // 10%
            
            $survey_per_teknisi = $survey_total / $survey_count;
            $pasang_baru_per_teknisi = $pasang_baru_total / $pasang_baru_count;
            $service_per_teknisi = $service_total / $service_count;
            
            // Proses survey
            foreach ($survey_ids as $kegiatan_id) {
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($survey_per_teknisi * $porsi), $porsi, $nominal_invoice);
            }
            
            // Proses pasang baru
            foreach ($pasang_baru_ids as $kegiatan_id) {
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($pasang_baru_per_teknisi * $porsi), $porsi, $nominal_invoice);
            }
            
            // Proses service
            foreach ($service_ids as $kegiatan_id) {
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($service_per_teknisi * $porsi), $porsi, $nominal_invoice);
            }
        }
        // Jika ada kombinasi lain (survey + service atau pasang baru + service)
        else {
            $pendapatan_per_teknisi = $nominal_invoice / $total_selected_count;
            
            foreach ($selected_kegiatan as $kegiatan_data) {
                list($kegiatan_id, $kegiatan_type) = explode('|', $kegiatan_data);
                $porsi = $getPorsi($kegiatan_id);
                insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, round($pendapatan_per_teknisi * $porsi), $porsi, $nominal_invoice);
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
function insertPendapatan($conn, $kegiatan_id, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pendapatan, $porsi_kerja, $nominal_invoice) {
    $kegIdInt = intval($kegiatan_id);
    $pendFloat = floatval($pendapatan);
    $porsiFloat = floatval($porsi_kerja);
    $nomFloat = floatval($nominal_invoice);
    $sqlInsert = "INSERT INTO pendapatan_kegiatan (kegiatan_id, teknisi_id, kode, no_invoice, tanggal, pendapatan, porsi_kerja, nominal_invoice, created_at, updated_at) 
                  VALUES (?, (SELECT teknisi_id FROM pelaksanaan_kegiatan WHERE id = ?), ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmtIns = $conn->prepare($sqlInsert);
    if ($stmtIns) {
        $stmtIns->bind_param('iisssddd', $kegIdInt, $kegIdInt, $kode_transaksi, $kode_invoice, $tanggal_invoice, $pendFloat, $porsiFloat, $nomFloat);
        $stmtIns->execute();
        $stmtIns->close();
    }
}
?>