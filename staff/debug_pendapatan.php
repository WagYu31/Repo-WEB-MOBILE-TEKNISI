<?php
header('Content-Type: text/plain; charset=utf-8');
include 'conn.php';

$date = $_GET['date'] ?? '2026-01';

echo "=== DEBUG PENDAPATAN - Periode: $date ===\n\n";

// === METHOD 1: Dashboard (laporan-db.php) - Batch JOIN ===
echo "--- METHOD 1: Dashboard (batch JOIN) ---\n";
$sql1 = "SELECT pk.teknisi_id, t.nama,
                COUNT(DISTINCT pk.kode) as cnt, 
                SUM(ROUND(pk.nominal_invoice / counts.tek_count)) as total 
         FROM pendapatan_kegiatan pk
         JOIN (
             SELECT kode, COUNT(*) as tek_count 
             FROM pendapatan_kegiatan 
             WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL
             GROUP BY kode
         ) counts ON pk.kode = counts.kode
         LEFT JOIN teknisi t ON pk.teknisi_id = t.id
         WHERE DATE_FORMAT(pk.tanggal, '%Y-%m') = ? 
         AND pk.deleted_at IS NULL
         GROUP BY pk.teknisi_id, t.nama
         ORDER BY t.nama";
$stmt = $conn->prepare($sql1);
$stmt->bind_param("ss", $date, $date);
$stmt->execute();
$res = $stmt->get_result();
$total1 = 0;
while ($r = $res->fetch_assoc()) {
    echo sprintf("  %-25s => Rp %s\n", $r['nama'], number_format($r['total'], 0, ',', '.'));
    $total1 += $r['total'];
}
echo "  TOTAL METHOD 1: Rp " . number_format($total1, 0, ',', '.') . "\n\n";
$stmt->close();

// === METHOD 2: Data Teknisi (get_teknisi_data.php) - Correlated subquery ===
echo "--- METHOD 2: Data Teknisi (correlated subquery) ---\n";
$sql_tek = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama";
$res_tek = mysqli_query($conn, $sql_tek);
$total2 = 0;
while ($tek = mysqli_fetch_assoc($res_tek)) {
    $sql2 = "SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL))), 0) as total
             FROM pendapatan_kegiatan pk 
             WHERE pk.teknisi_id = ? AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL";
    $stmt = $conn->prepare($sql2);
    $stmt->bind_param("sis", $date, $tek['id'], $date);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $val = $r['total'] ?? 0;
    if ($val > 0) {
        echo sprintf("  %-25s => Rp %s\n", $tek['nama'], number_format($val, 0, ',', '.'));
    }
    $total2 += $val;
    $stmt->close();
}
echo "  TOTAL METHOD 2: Rp " . number_format($total2, 0, ',', '.') . "\n\n";

// === METHOD 3: Old way - SUM(pendapatan) ===
echo "--- METHOD 3: Old way SUM(pendapatan) ---\n";
$sql3 = "SELECT pk.teknisi_id, t.nama, SUM(pk.pendapatan) as total
         FROM pendapatan_kegiatan pk
         LEFT JOIN teknisi t ON pk.teknisi_id = t.id
         WHERE DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL
         GROUP BY pk.teknisi_id, t.nama
         ORDER BY t.nama";
$stmt = $conn->prepare($sql3);
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();
$total3 = 0;
while ($r = $res->fetch_assoc()) {
    echo sprintf("  %-25s => Rp %s\n", $r['nama'], number_format($r['total'], 0, ',', '.'));
    $total3 += $r['total'];
}
echo "  TOTAL METHOD 3: Rp " . number_format($total3, 0, ',', '.') . "\n\n";

// === Raw data sample ===
echo "--- SAMPLE: Raw pendapatan_kegiatan data (first 10 rows) ---\n";
$sql4 = "SELECT pk.kode, pk.teknisi_id, t.nama, pk.pendapatan, pk.nominal_invoice, 
                (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL) as tek_per_kode
         FROM pendapatan_kegiatan pk
         LEFT JOIN teknisi t ON pk.teknisi_id = t.id
         WHERE DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL
         ORDER BY pk.kode
         LIMIT 20";
$stmt = $conn->prepare($sql4);
$stmt->bind_param("ss", $date, $date);
$stmt->execute();
$res = $stmt->get_result();
echo sprintf("  %-12s %-20s %-15s %-18s %-10s\n", "KODE", "NAMA", "pendapatan", "nominal_invoice", "tek/kode");
echo "  " . str_repeat("-", 80) . "\n";
while ($r = $res->fetch_assoc()) {
    echo sprintf("  %-12s %-20s Rp %-12s Rp %-15s %s\n", 
        $r['kode'], substr($r['nama'], 0, 18), 
        number_format($r['pendapatan'], 0, ',', '.'),
        number_format($r['nominal_invoice'], 0, ',', '.'),
        $r['tek_per_kode']);
}
$stmt->close();

echo "\n=== SUMMARY ===\n";
echo "Method 1 (Dashboard batch): Rp " . number_format($total1, 0, ',', '.') . "\n";
echo "Method 2 (Correlated sub):  Rp " . number_format($total2, 0, ',', '.') . "\n";
echo "Method 3 (Old SUM):         Rp " . number_format($total3, 0, ',', '.') . "\n";
echo "Diff 1-2: Rp " . number_format(abs($total1 - $total2), 0, ',', '.') . "\n";
echo "Diff 1-3: Rp " . number_format(abs($total1 - $total3), 0, ',', '.') . "\n";
?>
