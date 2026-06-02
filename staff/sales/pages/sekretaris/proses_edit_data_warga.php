<?php
// Memasukkan file koneksi ke database
include "../../conn.php";

// Menangkap data yang dikirim dari formulir
$no_kk = $_POST['noKk'];
$nama_kepala_keluarga = $_POST['namaKepalaKeluarga'];
$alamat = $_POST['alamat'];
$rt = $_POST['rt'];
$rw = $_POST['rw'];
$kecamatan = $_POST['kecamatan'];
$kelurahan = $_POST['kelurahan'];
$kota = $_POST['kota'];
$kode_pos = $_POST['kodePos'];
$domisili_sekarang = $_POST['domisili'];

// Menyimpan perubahan pada tabel kartu_keluarga
$queryKartuKeluarga = "UPDATE kartu_keluarga SET 
                        alamat = ?, 
                        rt = ?, 
                        rw = ?, 
                        kecamatan = ?, 
                        kelurahan = ?, 
                        kota = ?, 
                        kode_pos = ?, 
                        domisili_sekarang = ? 
                      WHERE no_kk = ?";

$stmtKartuKeluarga = mysqli_prepare($conn, $queryKartuKeluarga);
mysqli_stmt_bind_param($stmtKartuKeluarga, "sssssssss", 
                        $alamat, 
                        $rt, 
                        $rw, 
                        $kecamatan, 
                        $kelurahan, 
                        $kota, 
                        $kode_pos, 
                        $domisili_sekarang, 
                        $no_kk);

// Eksekusi statement
$resultKartuKeluarga = mysqli_stmt_execute($stmtKartuKeluarga);

// Menangkap data yang dikirim dari formulir untuk tabel data_warga
$id_warga = $_POST['idWarga'];
$nik = $_POST['nik'];
$nama = $_POST['nama'];
$jenis_kelamin = $_POST['jenisKelamin'];
$tempat_lahir = $_POST['tmpLahir'];
$tanggal_lahir = $_POST['tglLahir'];
$pendidikan = $_POST['pendidikan'];
$nomor_telepon = $_POST['nomorTelepon'];
$email = $_POST['email'];
$pekerjaan = $_POST['pekerjaan'];
$status = $_POST['status'];
$status_hubungan_dalam_keluarga = $_POST['statusHubunganDalamKeluarga'];
$agama = $_POST['agama'];
$kewarganegaraan = $_POST['kewarganegaraan'];

// Menyimpan perubahan pada tabel data_warga
$queryDataWarga = "UPDATE data_warga SET 
                    no_kk = ?, 
                    nik = ?, 
                    nama = ?, 
                    jenis_kelamin = ?, 
                    tempat_lahir = ?, 
                    tanggal_lahir = ?, 
                    pendidikan = ?, 
                    nomor_telepon = ?, 
                    email = ?, 
                    pekerjaan = ?, 
                    status = ?, 
                    status_hubungan_dalam_keluarga = ?, 
                    agama = ?, 
                    kewarganegaraan = ? 
                  WHERE id_warga = ?";

$stmtDataWarga = mysqli_prepare($conn, $queryDataWarga);
mysqli_stmt_bind_param($stmtDataWarga, "sssssssssssssss", 
                        $no_kk, 
                        $nik, 
                        $nama, 
                        $jenis_kelamin, 
                        $tempat_lahir, 
                        $tanggal_lahir, 
                        $pendidikan, 
                        $nomor_telepon, 
                        $email, 
                        $pekerjaan, 
                        $status, 
                        $status_hubungan_dalam_keluarga, 
                        $agama, 
                        $kewarganegaraan, 
                        $id_warga);

// Eksekusi statement
$resultDataWarga = mysqli_stmt_execute($stmtDataWarga);

// Tutup statement dan koneksi
mysqli_stmt_close($stmtKartuKeluarga);
mysqli_stmt_close($stmtDataWarga);
mysqli_close($conn);

if ($resultKartuKeluarga && $resultDataWarga) {
    header("Location: edit_data_warga.php?nik=$nik");
    exit();
} else {
    echo "Update gagal. Silakan coba lagi.";
}
?>
