<?php
include 'conn.php';
include 'session.php';
include 'get-user-data.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['kode_transaksi'])) {
    header("Location: index-sa.php");
    exit;
}

$kodeTransaksi = mysqli_real_escape_string($conn, $_POST['kode_transaksi']);
$tanggalPilihan = mysqli_real_escape_string($conn, $_POST['tanggal_pilihan']);
$waktuPilihan = mysqli_real_escape_string($conn, $_POST['waktu_pilihan']);
$kegiatanDipilih = mysqli_real_escape_string($conn, $_POST['kegiatan_pilihan']);
$keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
$teknisiIds = isset($_POST['teknisi']) ? $_POST['teknisi'] : [];
$ketuaId = isset($_POST['ketua_id']) ? intval($_POST['ketua_id']) : 0;
$now = date("Y-m-d H:i:s");
$jadwal = $tanggalPilihan . ' ' . $waktuPilihan . ':00';

// Auto-set ketua if only 1 technician
if (count($teknisiIds) === 1) {
    $ketuaId = intval($teknisiIds[0]);
}

// Get current kegiatan data (latest by kode)
$sqlCek = "SELECT * FROM kegiatan WHERE kode = '$kodeTransaksi' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
$resultCek = mysqli_query($conn, $sqlCek);
$kegiatan = mysqli_fetch_assoc($resultCek);

if (!$kegiatan) {
    echo "<script>alert('Kegiatan tidak ditemukan.'); window.location.href='index-sa.php';</script>";
    exit;
}

// Mark old kegiatan as 'selesai by admin'
$sqlUpdate = "UPDATE kegiatan SET status = 'selesai by admin', updated_at = '$now' WHERE id = '{$kegiatan['id']}'";
mysqli_query($conn, $sqlUpdate);

// Insert new kegiatan record with updated data
$sqlInsert = "INSERT INTO kegiatan (customer_id, kegiatan, jadwal, keterangan, status, request, lon, lat, rad, lanjutan_id, kode, paid, created_at, updated_at) 
              VALUES (
                  '{$kegiatan['customer_id']}', 
                  '$kegiatanDipilih', 
                  '$jadwal', 
                  '$keterangan', 
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

if (!mysqli_query($conn, $sqlInsert)) {
    echo "<script>alert('Gagal menyimpan perubahan.'); window.history.back();</script>";
    exit;
}

$kegiatanBaruId = mysqli_insert_id($conn);

// Insert team_kegiatan with is_ketua
foreach ($teknisiIds as $tekId) {
    $tekId = intval($tekId);
    
    // Get teknisi name
    $resTek = mysqli_query($conn, "SELECT nama FROM teknisi WHERE id = $tekId LIMIT 1");
    $rowTek = mysqli_fetch_assoc($resTek);
    $namaTek = mysqli_real_escape_string($conn, $rowTek['nama'] ?? '');
    
    // Determine is_ketua
    $isKetua = ($tekId === $ketuaId) ? 1 : 0;
    
    $sqlTeam = "INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, is_ketua, kode, created_at, updated_at) 
                VALUES ('$kegiatanBaruId', '$tekId', '$namaTek', '$isKetua', '$kodeTransaksi', '$now', '$now')";
    mysqli_query($conn, $sqlTeam);
}

// Log activity
$userDisplay = (!empty($nmUser)) ? $nmUser : "System/Admin";
$sqlCustomer = "SELECT nama FROM customer WHERE id = '{$kegiatan['customer_id']}' LIMIT 1";
$resCust = mysqli_query($conn, $sqlCustomer);
$dataCust = mysqli_fetch_assoc($resCust);
$namaCustomer = $dataCust['nama'] ?? 'Unknown';

$isiPesan = mysqli_real_escape_string($conn, "$userDisplay telah melakukan edit pada kegiatan [$kodeTransaksi] dengan nama customer $namaCustomer");
$sqlLog = "INSERT INTO log_aktivitas (waktu, ip_pengunjung, halaman, metode, durasi, pesan_manusia) 
           VALUES ('$now', '-', 'Edit Kegiatan', 'Edit', 0, '$isiPesan')";
mysqli_query($conn, $sqlLog);

// Redirect back to dashboard
header("Location: index-sa.php");
exit;
?>
