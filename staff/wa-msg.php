<?php
session_start();
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $tanggal = $_POST["tanggal"];
    $jam = $_POST["jam"];
    $selectedTechnicians = $_POST["teknisi"];
    $tgl_request = $tanggal . ' ' . $jam;
    $status = "success";
    $token = "VA-ZCZegDvDFHfNq5f4R";

    // Periksa apakah semua data sudah diisi
    if (empty($kegiatanId) || empty($tanggal) || empty($jam) || empty($selectedTechnicians)) {
        echo "Invalid input data.";
        exit;
    }

    // Ambil informasi kegiatan dan customer
    $sql = "SELECT k.*, c.nama AS nama_customer 
            FROM kegiatan k 
            JOIN customer c ON k.customer_id = c.id 
            WHERE k.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo "Failed to prepare statement: " . mysqli_error($conn);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $kegiatanId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) == 0) {
        echo "Kegiatan not found.";
        exit;
    }
    $rowSql = mysqli_fetch_assoc($res);
    $kodeTran = $rowSql['kode'];
    $nama_customer = $rowSql['nama_customer'];
    $jenis = $rowSql['kegiatan'];
    $tglRequest = formatTanggal('dd MMMM yyyy', $tgl_request) . ' Jam ' . date('H:i', strtotime($tgl_request));
    mysqli_stmt_close($stmt);

    foreach ($selectedTechnicians as $teknisiId) {
        // Ambil informasi teknisi
        $query = "SELECT telp, nama FROM teknisi WHERE id = ?";
        $stmtTek = mysqli_prepare($conn, $query);
        if (!$stmtTek) {
            echo "Failed to prepare teknisi statement: " . mysqli_error($conn);
            exit;
        }
        mysqli_stmt_bind_param($stmtTek, "i", $teknisiId);
        mysqli_stmt_execute($stmtTek);
        $result = mysqli_stmt_get_result($stmtTek);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmtTek);

        if ($row) {
            $nama_teknisi = $row['nama'];
            $no_wa = preg_replace('/^0/', '62', $row['telp']);
            $message = "Hai $nama_teknisi! Kamu memiliki jadwal $jenis pada *Tanggal $tglRequest* untuk *$nama_customer*. Buka aplikasi dan mulai kegiatan Anda! Jika ada kendala hubungi : 085855755556 (Support Team)";

            // Kirim pesan via API Fonnte
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => array(
                    'target' => $no_wa,
                    'message' => $message,
                    'countryCode' => '62',
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $token"
                ),
            ));
            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                echo "Curl error: " . curl_error($curl);
                $status = "failed";
            }
            curl_close($curl);

            // Periksa apakah pesan berhasil dikirim
            if (strpos($response, 'success') === false) {
                $status = "failed";
            }
        } else {
            echo "Teknisi not found or not part of the team.";
            exit;
        }
    }

    echo $status;
} else {
    echo "Invalid request method.";
}

mysqli_close($conn);
?>
