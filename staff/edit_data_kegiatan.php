<?php
include 'conn.php'; // Pastikan Anda menghubungkan ke database Anda

// Ambil data dari form
$kodeTransaksi = $_POST['kode_transaksi'];
$tanggalPilihan = $_POST['tanggal_pilihan'];
$waktuPilihan = $_POST['waktu_pilihan'];
$teknisiDipilih = isset($_POST['teknisi']) ? $_POST['teknisi'] : [];
$kegiatanDipilih = $_POST['kegiatan_pilihan'];
$now = date("Y-m-d H:i:s");

// Gabungkan tanggal dan waktu menjadi format datetime
$jadwal = $tanggalPilihan . ' ' . $waktuPilihan . ':00';

// Cek data kegiatan berdasarkan kode (ambil data dengan id terbesar/terakhir)
$sqlCekKegiatan = "
    SELECT * 
    FROM kegiatan 
    WHERE kode = '$kodeTransaksi' 
    ORDER BY id DESC 
    LIMIT 1";
$resultCekKegiatan = mysqli_query($conn, $sqlCekKegiatan);

if (!$resultCekKegiatan) {
    die("Query Error: " . mysqli_error($conn));
}

$kegiatan = mysqli_fetch_assoc($resultCekKegiatan);

if ($kegiatan) {
    $sqlUpdateKegiatan = "
        UPDATE kegiatan 
        SET status = 'selesai by admin', updated_at = '$now' 
        WHERE id = '{$kegiatan['id']}'";
    if (mysqli_query($conn, $sqlUpdateKegiatan)) {
        echo "Status kegiatan sebelumnya berhasil diupdate menjadi 'selesai by admin'.<br>";
    } else {
        echo "Gagal update status kegiatan: " . mysqli_error($conn) . "<br>";
    }

    $sqlInsertKegiatanBaru = "
        INSERT INTO kegiatan (customer_id, kegiatan, jadwal, keterangan, status, request, lon, lat, rad, lanjutan_id, kode, paid, created_at, updated_at) 
        VALUES (
            '{$kegiatan['customer_id']}', 
            '$kegiatanDipilih', 
            '$jadwal', 
            '{$kegiatan['keterangan']}', 
            'dijadwalkan', 
            '{$kegiatan['request']}', 
            '{$kegiatan['lon']}', 
            '{$kegiatan['lat']}', 
            '{$kegiatan['rad']}', 
            '{$kegiatan['id']}', 
            '{$kegiatan['kode']}', 
            '{$kegiatan['paid']}', 
            '$now', 
            '$now'
        )";
    if (mysqli_query($conn, $sqlInsertKegiatanBaru)) {
        echo "Data kegiatan baru berhasil diinsert.<br>";
        $kegiatanBaruId = mysqli_insert_id($conn);

        foreach ($teknisiDipilih as $teknisiName) {
            $sqlGetTeknisiId = "
                SELECT id 
                FROM teknisi 
                WHERE nama = '$teknisiName'";
            $resultTeknisiId = mysqli_query($conn, $sqlGetTeknisiId);
            if ($resultTeknisiId) {
                $rowTeknisi = mysqli_fetch_assoc($resultTeknisiId);
                $teknisiId = $rowTeknisi['id'];

                $sqlInsertTeamKegiatan = "
                    INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode, created_at, updated_at) 
                    VALUES ('$kegiatanBaruId', '$teknisiId', '$teknisiName', '$kodeTransaksi', '$now', '$now')";
                if (mysqli_query($conn, $sqlInsertTeamKegiatan)) {
                    echo "Data team_kegiatan untuk teknisi $teknisiName berhasil diinsert.<br>";
                } else {
                    echo "Gagal insert team_kegiatan untuk teknisi $teknisiName: " . mysqli_error($conn) . "<br>";
                }
            } else {
                echo "Gagal mendapatkan teknisi_id untuk $teknisiName: " . mysqli_error($conn) . "<br>";
            }
        }
    } else {
        echo "Gagal insert data kegiatan: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Data kegiatan tidak ditemukan untuk kode: $kodeTransaksi.<br>";
}

header("Location: index-sa.php");
exit();
?>
