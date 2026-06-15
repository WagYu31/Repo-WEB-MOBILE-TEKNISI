<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR CODES: Orn4Fg (SHIO PAN PIK 2) AND 6cEiKS (Melvin) ===\n\n";

// --- DATABASE PATCHES ---
echo "--- EXECUTING DATABASE PATCHES ---\n";
// 1. Fix Melvin: set paid = NULL where invoice = 'no' and kode = '6cEiKS'
$patch1 = $conn->query("UPDATE kegiatan SET paid = NULL WHERE kode = '6cEiKS' AND invoice = 'no'");
if ($patch1) {
    echo "Patch Melvin: SUCCESS (set paid = NULL for active 'no invoice' rows)\n";
} else {
    echo "Patch Melvin: FAILED (" . $conn->error . ")\n";
}

// 2. Fix SHIO PAN PIK 2: soft-delete Febry Setiawan's abandoned check-in log (id = 4866)
$patch2 = $conn->query("UPDATE pelaksanaan_kegiatan SET deleted_at = NOW() WHERE id = 4866 AND status = 'berjalan' AND waktu_selesai IS NULL");
if ($patch2) {
    echo "Patch SHIO PAN PIK 2: SUCCESS (soft-deleted Febry's check-in log 4866)\n";
} else {
    echo "Patch SHIO PAN PIK 2: FAILED (" . $conn->error . ")\n";
}
echo "\n";

echo "--- EXACT QUERY TEST WITH SEARCH = 'Melvin' ---\n";
$search = 'Melvin';
$sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.catatan_admin, k.kegiatan, k.created_at, k.status AS status_kegiatan, c.id AS id_cust, c.nama AS nama_cust
             FROM kegiatan k
             INNER JOIN (SELECT kode, MAX(id) AS max_id FROM kegiatan WHERE deleted_at IS NULL GROUP BY kode) latest ON k.id = latest.max_id
             LEFT JOIN customer c ON k.customer_id = c.id
             WHERE k.status != 'waiting' AND (k.paid IS NULL OR k.paid = '')
             AND k.deleted_at IS NULL
             AND NOT EXISTS (
                 SELECT 1 FROM pelaksanaan_kegiatan px
                 WHERE px.kegiatan_id = k.id AND px.deleted_at IS NULL
                 AND px.status IN ('Lanjut Nanti', 'Lanjutan', 'berjalan', 'dijadwalkan')
             )";

if (!empty($search)) {
    $sql_main .= " AND (c.nama LIKE ? OR k.kode LIKE ? OR k.keterangan LIKE ?)";
}

$sql_main .= " ORDER BY k.created_at DESC";

$stmtMain = $conn->prepare($sql_main);
if ($stmtMain) {
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmtMain->bind_param("sss", $searchParam, $searchParam, $searchParam);
    }
    $stmtMain->execute();
    $res = $stmtMain->get_result();
    echo "Num rows returned: " . $res->num_rows . "\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
    $stmtMain->close();
} else {
    echo "Prepare failed: " . $conn->error . "\n";
}
echo "\n";
?>
