<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

header('Content-Type: application/json');

// Check authorization
if ($role !== 'Super Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetUserId = intval($_POST['user_id'] ?? 0);
    $permissions = $_POST['permissions'] ?? [];

    if ($targetUserId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID User tidak valid.']);
        exit;
    }

    // Verify target user exists and is not Super Admin (Super Admin cannot have their access restricted)
    $stmtUser = $conn->prepare("SELECT jabatan FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $targetUserId);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($resUser->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        exit;
    }
    $targetUser = $resUser->fetch_assoc();
    if ($targetUser['jabatan'] === 'Super Admin') {
        echo json_encode(['status' => 'error', 'message' => 'Akses Super Admin tidak dapat diubah.']);
        exit;
    }
    $stmtUser->close();

    // Define all manageable menu keys to validate input
    $allKeys = [
        'dashboard', 'tambah_kegiatan', 'waiting_list',
        'kegiatan_teknisi', 'laporan_kegiatan', 'target_tercapai', 'progress_kegiatan',
        'stok_barang', 'peminjaman', 'tutorial',
        'teknisi', 'customer',
        'dashboard_sales', 'data_sales', 'jadwal_kunjungan', 'laporan_visit', 'customer_sales',
        'kegiatan_saya', 'dashboard_teknisi', 'buat_request'
    ];

    $conn->begin_transaction();
    try {
        // Upsert permission records
        $stmtUpsert = $conn->prepare("INSERT INTO user_menu_access (user_id, menu_key, is_allowed) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_allowed = ?");
        
        foreach ($allKeys as $key) {
            $isAllowed = isset($permissions[$key]) && intval($permissions[$key]) === 1 ? 1 : 0;
            $stmtUpsert->bind_param("isii", $targetUserId, $key, $isAllowed, $isAllowed);
            $stmtUpsert->execute();
        }
        $stmtUpsert->close();
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Akses menu berhasil diperbarui.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui akses: ' . $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}
?>
