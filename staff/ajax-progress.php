<?php
include "conn.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $kode = $_POST['kode'] ?? '';

    if (empty($kode)) {
        echo json_encode(['status' => 'error', 'msg' => 'Kode tidak valid']);
        exit;
    }

    // Pastikan row progress_kegiatan untuk kode ini sudah ada, jika belum buatkan
    $cek_query = "SELECT id FROM progress_kegiatan WHERE kode = ?";
    $stmt_cek = $conn->prepare($cek_query);
    $stmt_cek->bind_param("s", $kode);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows == 0) {
        $insert = "INSERT INTO progress_kegiatan (kode) VALUES (?)";
        $stmt_ins = $conn->prepare($insert);
        $stmt_ins->bind_param("s", $kode);
        $stmt_ins->execute();
    }

    if ($action == 'update_keterangan') {
        $keterangan = $_POST['keterangan'];
        $sql = "UPDATE progress_kegiatan SET keterangan_penangguhan = ?, tgl_update_keterangan = NOW() WHERE kode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $keterangan, $kode);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal menyimpan']);
        }
    } 
    elseif ($action == 'update_so') {
        $no_so = $_POST['no_so'];
        $tgl_keluar = $_POST['tgl_keluar_so'];
        $is_so = 1;
        
        $sql = "UPDATE progress_kegiatan SET is_so = ?, no_so = ?, tgl_keluar_so = ?, tgl_cek_so = NOW() WHERE kode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $is_so, $no_so, $tgl_keluar, $kode);
        echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    }
    elseif ($action == 'update_sj') {
        $no_sj = $_POST['no_sj'];
        $tgl_keluar = $_POST['tgl_keluar_sj'];
        $is_sj = 1;
        
        $sql = "UPDATE progress_kegiatan SET is_sj = ?, no_sj = ?, tgl_keluar_sj = ?, tgl_cek_sj = NOW() WHERE kode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $is_sj, $no_sj, $tgl_keluar, $kode);
        echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    }
    elseif ($action == 'uncheck_doc') {
        $type = $_POST['type']; // 'so' atau 'sj'
        if($type == 'so') {
            $sql = "UPDATE progress_kegiatan SET is_so = 0, no_so = NULL, tgl_keluar_so = NULL, tgl_cek_so = NULL WHERE kode = ?";
        } else {
            $sql = "UPDATE progress_kegiatan SET is_sj = 0, no_sj = NULL, tgl_keluar_sj = NULL, tgl_cek_sj = NULL WHERE kode = ?";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $kode);
        echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    }
    elseif ($action == 'update_finish') {
        $is_finish = $_POST['is_finish'];
        $sql = "UPDATE progress_kegiatan SET is_finish = ?, tgl_cek_finish = NOW() WHERE kode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $is_finish, $kode);
        echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    }
}
?>