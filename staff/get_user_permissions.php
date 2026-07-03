<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

header('Content-Type: application/json');

if ($role !== 'Super Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$targetUserId = intval($_GET['user_id'] ?? 0);
if ($targetUserId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID User tidak valid.']);
    exit;
}

// Fetch user details
$stmtUser = $conn->prepare("SELECT name, email, jabatan FROM users WHERE id = ?");
$stmtUser->bind_param("i", $targetUserId);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
if ($resUser->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
    exit;
}
$targetUser = $resUser->fetch_assoc();
$stmtUser->close();

$targetRole = $targetUser['jabatan'];

// Get default permissions based on role
$defaults = [
    'dashboard' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'tambah_kegiatan' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'waiting_list' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'kegiatan_teknisi' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'laporan_kegiatan' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'target_tercapai' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'progress_kegiatan' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'stok_barang' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'peminjaman' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'tutorial' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'teknisi' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'customer' => ($targetRole == 'Super Admin' || $targetRole == 'Admin') ? 1 : 0,
    'dashboard_sales' => ($targetRole == 'Super Admin' || $targetRole == 'Admin' || $targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
    'data_sales' => ($targetRole == 'Super Admin' || $targetRole == 'Admin' || $targetRole == 'Sales Manager') ? 1 : 0,
    'jadwal_kunjungan' => ($targetRole == 'Super Admin' || $targetRole == 'Admin' || $targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
    'laporan_visit' => ($targetRole == 'Super Admin' || $targetRole == 'Admin' || $targetRole == 'Sales Manager') ? 1 : 0,
    'customer_sales' => ($targetRole == 'Super Admin' || $targetRole == 'Admin' || $targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
    'kegiatan_saya' => ($targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
    'dashboard_teknisi' => ($targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
    'buat_request' => ($targetRole == 'Sales Manager' || $targetRole == 'Sales') ? 1 : 0,
];

// Fetch current set permissions from DB
$currentPermissions = [];
$query = mysqli_query($conn, "SELECT menu_key, is_allowed FROM user_menu_access WHERE user_id = '$targetUserId'");
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $currentPermissions[$row['menu_key']] = intval($row['is_allowed']);
    }
}

// Merge defaults and active overrides
$finalPermissions = [];
foreach ($defaults as $key => $defaultVal) {
    $finalPermissions[$key] = isset($currentPermissions[$key]) ? $currentPermissions[$key] : $defaultVal;
}

echo json_encode([
    'status' => 'success',
    'user' => [
        'name' => $targetUser['name'],
        'email' => $targetUser['email'],
        'jabatan' => $targetRole
    ],
    'permissions' => $finalPermissions
]);
exit;
?>
