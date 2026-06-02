<?php
include "conn.php";
include "session.php";

// Ambil tanggal yang dicari atau gunakan tanggal sekarang
$current_date = isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun']) ? $_GET['cariBulanTahun'] : date("Y-m");
$tomorrow_date = date("Y-m-d", strtotime("+1 day")); // Tanggal besok
$current_time = date("H:i:s"); // Waktu sekarang

// Query untuk mendapatkan semua teknisi
$sql = "SELECT * FROM teknisi";
$result = mysqli_query($conn, $sql);

// Format bulan dan tahun
setlocale(LC_TIME, 'id_ID.utf8');
$bt = strftime('%B %Y', strtotime($current_date));

$bonus = 0;
// Ambil bulan dan tahun dari $current_date dan buat tanggalnya jadi 25
$tanggal_pendapatan = date("Y-m-25", strtotime($current_date));

date_default_timezone_set('Asia/Jakarta');
$created_at = date("Y-m-d H:i:s");
$updated_at = $created_at;

while ($row = mysqli_fetch_assoc($result)) {
    $idT = $row['id'];
    $namaT = $row['nama'];
    $target = $row['target'];

    // Hitung total bonus dari pendapatan kegiatan
    $sqlBns = "
        SELECT 
            SUM(pendapatan) AS total_bonus
        FROM 
            pendapatan_kegiatan
        WHERE 
            teknisi_id = $idT
            AND deleted_at IS NULL
            AND DATE_FORMAT(tanggal, '%Y-%m') = '$current_date'
    ";
    $resultBns = mysqli_query($conn, $sqlBns);
    $totalBonus = ($resultBns) ? mysqli_fetch_assoc($resultBns)['total_bonus'] ?? 0 : 0;
    
    $total_fee = 0;
    $sqlFee = "SELECT COALESCE(SUM(k.paid), 0) AS total_fee
                    FROM pelaksanaan_kegiatan pk
                    JOIN (
                        SELECT kode, paid
                        FROM kegiatan
                        WHERE DATE_FORMAT(jadwal, '%Y-%m') = ? AND paid REGEXP '^[0-9]+$'
                        GROUP BY kode
                        ) AS k ON pk.kode = k.kode
                    WHERE pk.teknisi_id = ? 
                    AND pk.deleted_at IS NULL
                    AND pk.status = 'selesai'";
                    
    $stmtFee = $conn->prepare($sqlFee);
    $stmtFee->bind_param("si", $current_date, $idT);
    $stmtFee->execute();
    $resultFee = $stmtFee->get_result();
    if ($rowFee = $resultFee->fetch_assoc()) {
        $total_fee = $rowFee['total_fee'] ?? 0;
    }
    $stmtFee->close();
    $totalFee = $totalFee += $total_fee ?? 0;
    
    $totalBonus = $totalBonus + $total_fee;

    if ($target == 0) {
        $bonus = 0;
    } elseif ($totalBonus > $target) {
        $bonus = ($totalBonus - $target) * (60 / 100);
    } else {
        $bonus = 0;
    }

    // Cek apakah data sudah ada di tabel `pendapatan_fix`
    $sqlCheck = "
        SELECT id FROM pendapatan_fix
        WHERE teknisi_id = '$idT'
        AND DATE_FORMAT(tanggal, '%Y-%m') = '$current_date'
    ";
    $resultCheck = mysqli_query($conn, $sqlCheck);

    if (mysqli_num_rows($resultCheck) > 0) {
        // Jika data ada, lakukan update
        $sqlUpdate = "
            UPDATE pendapatan_fix
            SET target = '$target', pendapatan = '$totalBonus', bonus = '$bonus', updated_at = '$updated_at'
            WHERE teknisi_id = '$idT' 
            AND DATE_FORMAT(tanggal, '%Y-%m') = '$current_date'
        ";

        if (mysqli_query($conn, $sqlUpdate)) {
            echo "Data berhasil di-update untuk teknisi ID: $idT <br>";
        } else {
            echo "Error update: " . mysqli_error($conn) . "<br>";
        }
    } else {
        // Jika data belum ada, lakukan insert
        $sqlInsert = "
            INSERT INTO pendapatan_fix (teknisi_id, target, tanggal, pendapatan, bonus, created_at, updated_at) 
            VALUES ('$idT', '$target', '$tanggal_pendapatan', '$totalBonus', '$bonus', '$created_at', '$updated_at')
        ";

        if (mysqli_query($conn, $sqlInsert)) {
            echo "Data berhasil dimasukkan untuk teknisi ID: $idT <br>";
        } else {
            echo "Error insert: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Setelah proses selesai, redirect ke halaman laporan.php
header("Location: laporan.php?cariBulanTahun=$current_date");
exit;

?>
