<?php
include "conn.php";

$idTrack = 41; // ID Arif
$bulan = 03; 
$tahun = 2025;

$query = "
    SELECT * FROM pendapatan_kegiatan
    WHERE teknisi_id = $idTrack
    AND MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun

    ORDER BY tanggal ASC
";
// yang di hapus dihitung
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pendapatan Kegiatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Pendapatan Kegiatan - Arif (Maret 2025)</h2>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Kegiatan ID</th>
                    <th>Teknisi ID</th>
                    <th>Kode</th>
                    <th>No Invoice</th>
                    <th>Tanggal</th>
                    <th>Pendapatan</th>
                    <th>Nominal Invoice</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Deleted At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalPendapatan = 0;

                if ($result->num_rows > 0) {
                    $no = 1;
                    while ($row = $result->fetch_assoc()) {
                        $totalPendapatan += $row['pendapatan']; // Akumulasi total pendapatan

                        echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['kegiatan_id']}</td>
                            <td>{$row['teknisi_id']}</td>
                            <td>{$row['kode']}</td>
                            <td>{$row['no_invoice']}</td>
                            <td>" . date("d-m-Y H:i", strtotime($row['tanggal'])) . "</td>
                            <td>Rp " . number_format($row['pendapatan'], 0, ',', '.') . "</td>
                            <td>Rp " . number_format($row['nominal_invoice'], 0, ',', '.') . "</td>
                            <td>" . date("d-m-Y H:i", strtotime($row['created_at'])) . "</td>
                            <td>" . ($row['updated_at'] ? date("d-m-Y H:i", strtotime($row['updated_at'])) : '-') . "</td>
                            <td>" . ($row['deleted_at'] ? date("d-m-Y H:i", strtotime($row['deleted_at'])) : '-') . "</td>
                        </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center'>Data tidak ditemukan.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <th colspan="6" class="text-end">Total Pendapatan:</th>
                    <th colspan="4">Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>