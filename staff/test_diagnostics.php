<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR USERS & TECHNICIAN: Agung Putra ===\n\n";

$search_name = "Agung";

// 1. Check users table
echo "--- USERS TABLE ---\n";
$sql_user = "SELECT id, email, name, jabatan FROM users WHERE name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
$q_user = $conn->query($sql_user);
if ($q_user) {
    echo "Jumlah user cocok: " . $q_user->num_rows . "\n";
    while ($row = $q_user->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error users table: " . $conn->error . "\n";
}

// 2. Check teknisi table
echo "\n--- TEKNISI TABLE ---\n";
$sql_tek = "SELECT id, nik, nama, deleted_at FROM teknisi WHERE nama LIKE '%" . $conn->real_escape_string($search_name) . "%'";
$q_tek = $conn->query($sql_tek);
if ($q_tek) {
    echo "Jumlah teknisi cocok: " . $q_tek->num_rows . "\n";
    while ($row = $q_tek->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error teknisi table: " . $conn->error . "\n";
}
?>
