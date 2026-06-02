<?php

include "../../conn.php";
// Assuming $conn is your database connection

// Assuming the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve values from the form
    $id_warga = $_POST["id_warga"];
    $no_kk = $_POST["no_kk"];
    $tmpLahir = $_POST["tmpLahir"];
    $tglLahir = $_POST["tglLahir"];
    $jenkel = $_POST["jenkel"];
    $agama = $_POST["agama"];
    $nomorTelepon = $_POST["nomorTelepon"];
    $email = $_POST["email"];
    $alamat = $_POST["alamat"];
    $rt = $_POST["rt"];
    $rw = $_POST["rw"];
    $kelurahan = $_POST["kelurahan"];
    $kecamatan = $_POST["kecamatan"];
    $kodePos = $_POST["kodePos"];
    $domisili = $_POST["domisili"];
    $pendidikan = $_POST["pendidikan"];
    $pekerjaan = $_POST["pekerjaan"];
    $kewarganegaraan = $_POST["kewarganegaraan"];
    $status = $_POST["status"];
    $hubungan = $_POST["hub"];

    // Update data_warga table
    $updateWargaQuery = "UPDATE data_warga 
                         SET tempat_lahir='$tmpLahir', tanggal_lahir='$tglLahir', jenis_kelamin='$jenkel', agama='$agama', 
                             nomor_telepon='$nomorTelepon', email='$email', pekerjaan='$pekerjaan', status='$status', 
                             status_hubungan_dalam_keluarga='$hubungan', pendidikan='$pendidikan', kewarganegaraan='$kewarganegaraan'
                         WHERE id_warga='$id_warga'";

    if (mysqli_query($conn, $updateWargaQuery)) {
        // Update kartu_keluarga table
        $updateKartuKeluargaQuery = "UPDATE kartu_keluarga 
                                        SET alamat='$alamat', rt='$rt', rw='$rw', kelurahan='$kelurahan', 
                                            kecamatan='$kecamatan', kode_pos='$kodePos', domisili_sekarang='$domisili'
                                        WHERE no_kk='$no_kk'";

        if (mysqli_query($conn, $updateKartuKeluargaQuery)) {
            header("Location: profile.php");
        } else {
            echo "Error updating record in kartu_keluarga table: " . mysqli_error($conn);
        }
    } else {
        echo "Error updating record in data_warga table: " . mysqli_error($conn);
    }
}
