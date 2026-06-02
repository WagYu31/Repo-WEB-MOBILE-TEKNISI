<?php
include "../../conn.php";
$pageNow = "Edit Data Warga";
include "session.php";

// Ambil nilai NIK dari sesi
$nikSesi = $_SESSION["nik"];
$querySesi = "SELECT * FROM data_warga WHERE nik = '$nikSesi'";
$resultSesi = mysqli_query($conn, $querySesi);
$rowSesi = mysqli_fetch_assoc($resultSesi);
$id_warga = $rowSesi['id_warga'];
$nama = $rowSesi['nama'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <?php
    include "head.php";
    ?>
</head>

<body class="g-sidenav-show  bg-gray-200">

    <?php
    include "cek-menu.php";
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <?php
        include "nav-top.php";

        $query = "SELECT data_warga.*, kartu_keluarga.* FROM data_warga JOIN kartu_keluarga ON data_warga.no_kk = kartu_keluarga.no_kk WHERE nik = $nikSesi;";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $id_warga = $row["id_warga"];
            $nama = $row["nama"];
            $no_kk = $row["no_kk"];
            $jenis_kelamin = $row["jenis_kelamin"];
            $tempat_lahir = $row["tempat_lahir"];
            $tanggal_lahir = $row["tanggal_lahir"];
            $kepala_keluarga = $row["kepala_keluarga"];
            $agama = $row["agama"];
            $pendidikan = $row["pendidikan"];
            $pekerjaan = $row["pekerjaan"];
            $nomor_telepon = $row["nomor_telepon"];
            $email = $row["email"];
            $rt = $row["rt"];
            $rw = $row["rw"];
            $alamat = $row["alamat"];
            $kecamatan = $row["kecamatan"];
            $kelurahan = $row["kelurahan"];
            $kota = $row["kota"];
            $kode_pos = $row["kode_pos"];
            $status = $row["status"];
            $hubungan = $row["status_hubungan_dalam_keluarga"];
            $agama = $row["agama"];
            $kewarganegaraan = $row["kewarganegaraan"];
            $domisili = $row["domisili_sekarang"];
        }

        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12 text-end d-block d-md-none">
                    <a class="btn btn-outline-primary mb-4 w-50" href="change_password.php" type="button">Ganti Password</a>
                </div>
                <div class="col-12">
                    <div class="card my-4">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                                <h6 class="text-white text-capitalize ps-3">Edit Data Warga</h6>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form id="editForm" method="post" action="edit_data_warga.php">

                                <input type="hidden" name="id_warga" value="<?php echo $id_warga; ?>">
                                <input type="hidden" name="no_kk" value="<?php echo $no_kk; ?>">

                                <h5 class="mt-4 mb-0">Informasi Personal</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="nama" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control border p-2" id="nama" name="nama" value="<?php echo $nama; ?>" disabled required>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="nik" class="form-label">Nomor Induk Kependudukan</label>
                                        <input type="number" class="form-control border p-2" id="nik" name="nik" value="<?php echo $nikSesi; ?>" disabled required>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="tmpLahir" class="form-label">Tempat Lahir</label>
                                        <input type="text" class="form-control border p-2" id="tmpLahir" name="tmpLahir" value="<?php echo $tempat_lahir; ?>">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="tglLahir" class="form-label">Tanggal Lahir</label>
                                        <input type="date" class="form-control border p-2" id="tglLahir" name="tglLahir" value="<?php echo $tanggal_lahir; ?>">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="jenkel" class="form-label">Jenis Kelamin</label>
                                        <select class="form-select border p-2" id="jenkel" name="jenkel">
                                            <option value="Laki-Laki" <?php echo ($jenis_kelamin == 'Laki-Laki') ? 'selected' : ''; ?>>Laki-Laki</option>
                                            <option value="Perempuan" <?php echo ($jenis_kelamin == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="agama" class="form-label">Agama</label>
                                        <select class="form-select border p-2" id="agama" name="agama">
                                            <option value="Islam" <?php echo ($agama == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                            <option value="Kristen (Protestan)" <?php echo ($agama == 'Kristen (Protestan)') ? 'selected' : ''; ?>>Kristen (Protestan)</option>
                                            <option value="Hindu" <?php echo ($agama == 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                            <option value="Budha" <?php echo ($agama == 'Budha') ? 'selected' : ''; ?>>Budha</option>
                                            <option value="Katolik" <?php echo ($agama == 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                            <option value="Konghucu" <?php echo ($agama == 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                                        </select>
                                    </div>
                                </div>

                                <h5 class="mt-4 mb-0">Informasi Keluarga</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="noKk" class="form-label">Nomor Kartu Keluarga</label>
                                        <input type="number" class="form-control border p-2" id="noKk" name="noKk" disabled value="<?php echo $no_kk; ?>">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="hub" class="form-label">Hubungan Dalam Keluarga</label>
                                        <select class="form-select border p-2" id="hub" name="hub">
                                            <option value="Kepala Keluarga" <?php echo ($hubungan == 'Kepala Keluarga') ? 'selected' : ''; ?>>Kepala Keluarga</option>
                                            <option value="Suami" <?php echo ($hubungan == 'Suami') ? 'selected' : ''; ?>>Suami</option>
                                            <option value="Istri" <?php echo ($hubungan == 'Istri') ? 'selected' : ''; ?>>Istri</option>
                                            <option value="Anak" <?php echo ($hubungan == 'Anak') ? 'selected' : ''; ?>>Anak</option>
                                            <option value="Menantu" <?php echo ($hubungan == 'Menantu') ? 'selected' : ''; ?>>Menantu</option>
                                            <option value="Cucu" <?php echo ($hubungan == 'Cucu') ? 'selected' : ''; ?>>Cucu</option>
                                            <option value="Orang Tua" <?php echo ($hubungan == 'Orang Tua') ? 'selected' : ''; ?>>Orang Tua</option>
                                            <option value="Mertua" <?php echo ($hubungan == 'Mertua') ? 'selected' : ''; ?>>Mertua</option>
                                            <option value="Famili Lainnya" <?php echo ($hubungan == 'Famili Lainnya') ? 'selected' : ''; ?>>Famili Lainnya</option>
                                            <option value="Pembantu" <?php echo ($hubungan == 'Pembantu') ? 'selected' : ''; ?>>Pembantu</option>
                                        </select>
                                    </div>
                                    <?php
                                    $query = "SELECT nik, nama, status_hubungan_dalam_keluarga FROM data_warga WHERE no_kk = '$no_kk'";

                                    $result = mysqli_query($conn, $query);

                                    if ($result) {
                                        $familyMembers = mysqli_fetch_all($result, MYSQLI_ASSOC);
                                    } else {
                                        echo "Error: " . mysqli_error($conn);
                                    }

                                    ?>
                                    <div class="col-md-12 mt-2">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr class="text-center">
                                                        <th>NIK</th>
                                                        <th>Nama</th>
                                                        <th>Status Hubungan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    foreach ($familyMembers as $member) {
                                                        echo '<tr>';
                                                        echo '<td>' . $member['nik'] . '</td>';
                                                        echo '<td>' . $member['nama'] . '</td>';
                                                        echo '<td>' . $member['status_hubungan_dalam_keluarga'] . '</td>';
                                                        echo '</tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                    <h5 class="mt-4 mb-0">Informasi Kontak</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6 mt-2">
                                            <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                                            <input type="tel" class="form-control border p-2" id="nomorTelepon" name="nomorTelepon" value="<?php echo $nomor_telepon; ?>">
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control border p-2" id="email" name="email" value="<?php echo $email; ?>">
                                        </div>
                                    </div>

                                    <h5 class="mt-4 mb-0">Informasi Alamat</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6 mt-2">
                                            <label for="alamat" class="form-label">Alamat KTP</label>
                                            <input type="text" class="form-control border p-2" id="alamat" name="alamat" value="<?php echo $alamat; ?>">
                                        </div>
                                        <div class="col-md-1 mt-2">
                                            <label for="rt" class="form-label">RT</label>
                                            <input type="text" class="form-control border p-2" id="rt" name="rt" value="<?php echo $rt; ?>">
                                        </div>
                                        <div class="col-md-1 mt-2">
                                            <label for="rw" class="form-label">RW</label>
                                            <input type="text" class="form-control border p-2" id="rw" name="rw" value="<?php echo $rw; ?>">
                                        </div>
                                        <div class="col-md-2 mt-2">
                                            <label for="kodePos" class="form-label">Kode POS</label>
                                            <input type="text" class="form-control border p-2" id="kodePos" name="kodePos" value="<?php echo $kode_pos; ?>">
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="kelurahan" class="form-label">Kelurahan</label>
                                            <input type="text" class="form-control border p-2" id="kelurahan" name="kelurahan" value="<?php echo $kelurahan; ?>">
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="kecamatan" class="form-label">Kecamatan</label>
                                            <input type="text" class="form-control border p-2" id="kecamatan" name="kecamatan" value="<?php echo $kecamatan; ?>">
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="domisili" class="form-label">Domisili Sekarang</label>
                                            <input type="text" class="form-control border p-2" id="domisili" name="domisili" value="<?php echo $domisili; ?>">
                                        </div>
                                    </div>

                                    <h5 class="mt-4 mb-0">Informasi Pendidikan dan Pekerjaan</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6 mt-2">
                                            <label for="pendidikan" class="form-label">Pendidikan</label>
                                            <input type="text" class="form-control border p-2" id="pendidikan" name="pendidikan" value="<?php echo $pendidikan; ?>">
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                            <input type="text" class="form-control border p-2" id="pekerjaan" name="pekerjaan" value="<?php echo $pekerjaan; ?>">
                                        </div>
                                    </div>

                                    <h5 class="mt-4 mb-0">Informasi Kewarganegaraan dan Status</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6 mt-2">
                                            <label for="kewarganegaraan" class="form-label">Kewarganegaraan</label>
                                            <select class="form-select border p-2" id="kewarganegaraan" name="kewarganegaraan">
                                                <option value="WNI" <?php echo ($kewarganegaraan == 'WNI') ? 'selected' : ''; ?>>WNI (Warga Negara Indonesia)</option>
                                                <option value="WNA" <?php echo ($kewarganegaraan == 'WNA') ? 'selected' : ''; ?>>WNA (Warga Negara Asing)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mt-2">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select border p-2" id="status" name="status">
                                                <option value="Kawin" <?php echo ($status == 'Kawin') ? 'selected' : ''; ?>>Kawin</option>
                                                <option value="Belum Kawin" <?php echo ($status == 'Belum Kawin') ? 'selected' : ''; ?>>Belum Kawin</option>
                                                <option value="Cerai Hidup" <?php echo ($status == 'Cerai Hidup') ? 'selected' : ''; ?>>Cerai Hidup</option>
                                                <option value="Cerai Mati" <?php echo ($status == 'Cerai Mati') ? 'selected' : ''; ?>>Cerai Mati</option>
                                            </select>
                                        </div>

                                    </div>

                                    <div class="text-start mt-4">
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            <?php
            include "../footer.php";
            ?>
        </div>
    </main>
    <div class="fixed-plugin">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
    </div>


    <?php
    $query = "SELECT * FROM data_warga";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $nik = $row["nik"];
        // ... (kode yang sudah ada)

        // Tambahkan modal untuk setiap data warga
    ?>
        <div class="modal fade" id="editModal<?php echo $nik; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $nik; ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?php echo $nik; ?>">Edit Data Warga</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Form edit data warga -->
                        <form method="post" action="proses_edit_warga.php">
                            <div class="mb-3">
                                <label for="nama">Nama:</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo $row['nama']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="jenis_kelamin">Jenis Kelamin:</label>
                                <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                    <option value="Laki-Laki" <?php echo ($row['jenis_kelamin'] == 'Laki-Laki') ? 'selected' : ''; ?>>Laki-Laki</option>
                                    <option value="Perempuan" <?php echo ($row['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            <!-- Tambahkan input sesuai kebutuhan Anda -->
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>



    <?php
    include "js-include.php";
    ?>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>