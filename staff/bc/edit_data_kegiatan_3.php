<?php
include 'conn.php'; // Pastikan Anda menghubungkan ke database Anda

// Ambil data dari form
$kodeTransaksi = $_POST['kode_transaksi'];
$tanggalPilihan = $_POST['tanggal_pilihan'];
$waktuPilihan = $_POST['waktu_pilihan'];
$teknisiDipilih = isset($_POST['teknisi']) ? $_POST['teknisi'] : [];
$now = date("Y-m-d H:i:s");

// Gabungkan tanggal dan waktu menjadi format datetime
$jadwal = $tanggalPilihan . ' ' . $waktuPilihan . ':00';

// Update tabel kegiatan: set status menjadi 'dijadwalkan' dan update jadwal
$sqlUpdateKegiatan = "UPDATE kegiatan SET status = 'dijadwalkan', jadwal = '$jadwal', updated_at = '$now' WHERE kode = '$kodeTransaksi'";
mysqli_query($conn, $sqlUpdateKegiatan);

// Dapatkan data teknisi yang ada di team_kegiatan berdasarkan kode
$sqlGetExistingTeknisi = "SELECT * FROM team_kegiatan WHERE kode = '$kodeTransaksi'";
$resultExistingTeknisi = mysqli_query($conn, $sqlGetExistingTeknisi);

$existingTeknisi = [];
while ($row = mysqli_fetch_assoc($resultExistingTeknisi)) {
    $existingTeknisi[$row['teknisi_id']] = $row['nama_teknisi'];
}

// Loop untuk memproses setiap teknisi yang dipilih di checkbox
foreach ($teknisiDipilih as $teknisiName) {
    // Dapatkan teknisi_id berdasarkan nama teknisi
    $sqlGetTeknisiId = "SELECT id FROM teknisi WHERE nama = '$teknisiName'";
    $resultTeknisiId = mysqli_query($conn, $sqlGetTeknisiId);
    $rowTeknisi = mysqli_fetch_assoc($resultTeknisiId);
    $teknisiId = $rowTeknisi['id'];

    if (isset($existingTeknisi[$teknisiId])) {
        // Jika teknisi sudah ada di team_kegiatan, update updated_at saja
        $sqlUpdateTeamKegiatan = "UPDATE team_kegiatan SET updated_at = '$now' WHERE teknisi_id = '$teknisiId' AND kode = '$kodeTransaksi'";
        mysqli_query($conn, $sqlUpdateTeamKegiatan);
    } else {
        // Jika teknisi belum ada, insert ke team_kegiatan
        $sqlInsertTeamKegiatan = "INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode, created_at, updated_at) 
                                  VALUES ((SELECT id FROM kegiatan WHERE kode = '$kodeTransaksi'), '$teknisiId', '$teknisiName', '$kodeTransaksi', '$now', '$now')";
        mysqli_query($conn, $sqlInsertTeamKegiatan);
    }
}

// Loop untuk memeriksa teknisi yang tidak dipilih di checkbox
foreach ($existingTeknisi as $teknisiId => $teknisiName) {
    if (!in_array($teknisiName, $teknisiDipilih)) {
        // Jika teknisi tidak dipilih, set deleted_at
        $sqlDeleteTeamKegiatan = "UPDATE team_kegiatan SET deleted_at = '$now' WHERE teknisi_id = '$teknisiId' AND kode = '$kodeTransaksi'";
        mysqli_query($conn, $sqlDeleteTeamKegiatan);
    }
}

header("Location: success_page.php"); // Redirect ke halaman sukses setelah selesai
exit();

?>
