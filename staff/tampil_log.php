<?php
require_once 'conn.php';

$semua_log = [];
$query = "SELECT * FROM log_aktivitas ORDER BY waktu DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $semua_log[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Aktivitas Web</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007BFF; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .durasi { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Rekaman Aktivitas Website</h2>
    
    <table>
        <thead>
            <tr>
                <th>Waktu Kejadian</th>
                <th>IP Pengunjung</th>
                <th>Halaman (Metode)</th>
                <th>Durasi Proses</th>
                <th>Detail Aktivitas (Bahasa Manusia)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($semua_log) > 0): ?>
                <?php foreach ($semua_log as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['waktu']) ?></td>
                        <td><?= htmlspecialchars($log['ip_pengunjung']) ?></td>
                        <td><strong><?= htmlspecialchars($log['halaman']) ?></strong> <br><small>(<?= htmlspecialchars($log['metode']) ?>)</small></td>
                        <td class="durasi"><?= htmlspecialchars($log['durasi']) ?> detik</td>
                        <td><?= htmlspecialchars($log['pesan_manusia']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada data log aktivitas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>