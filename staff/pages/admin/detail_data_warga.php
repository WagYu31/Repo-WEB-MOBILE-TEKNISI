<?php
include "../../conn.php";
$pageNow = "Edit Data Warga";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <?php
    include "head.php";
    $nik = $_GET['nik'];
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

        $query = "SELECT data_warga.*, kartu_keluarga.* FROM data_warga JOIN kartu_keluarga ON data_warga.no_kk = kartu_keluarga.no_kk WHERE nik = $nik;";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $id_warga = $row["id_warga"];
            $nama = $row["nama"];
            $no_kk = $row["no_kk"];
            $jenis_kelamin = $row["jenis_kelamin"];
            $tempat_lahir = $row["tempat_lahir"];
            $tanggal_lahir = $row["tanggal_lahir"];
            $kepala_keluarga = $row["kepala_keluarga"];
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
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Data Warga</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <!-- Personal Information -->
                    <h5 class="mb-4">Informasi Personal</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <span class="form-control "><?php echo $nama; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="nik" class="form-label">Nomor Induk Kependudukan</label>
                            <span class="form-control "><?php echo $nik; ?></span>
                        </div>
                    </div>

                    <!-- Birth Information -->
                    <h5 class="mb-4">Informasi Kelahiran</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="tmpLahir" class="form-label">Tempat Lahir</label>
                            <span class="form-control "><?php echo $tempat_lahir; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="tglLahir" class="form-label">Tanggal Lahir</label>
                            <span class="form-control "><?php echo $tanggal_lahir; ?></span>
                        </div>
                    </div>

                    <!-- Family Information -->
                    <h5 class="mb-4">Informasi Keluarga</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="noKk" class="form-label">Nomor Kartu Keluarga</label>
                            <span class="form-control "><?php echo $no_kk; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="hub" class="form-label">Hubungan Dalam Keluarga</label>
                            <span class="form-control "><?php echo $hubungan; ?></span>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <h5 class="mb-4">Informasi Kontak</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                            <span class="form-control "><?php echo $nomor_telepon; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="email" class="form-label">Email</label>
                            <span class="form-control "><?php echo $email; ?></span>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <h5 class="mb-4">Informasi Alamat</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="alamat" class="form-label">Alamat</label>
                            <span class="form-control "><?php echo $alamat; ?></span>
                        </div>
                        <div class="col-md-1 mt-2">
                            <label for="rt" class="form-label">RT</label>
                            <span class="form-control "><?php echo $rt; ?></span>
                        </div>
                        <div class="col-md-1 mt-2">
                            <label for="rw" class="form-label">RW</label>
                            <span class="form-control "><?php echo $rw; ?></span>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label for="kodePos" class="form-label">Kode POS</label>
                            <span class="form-control "><?php echo $kode_pos; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="kelurahan" class="form-label">Kelurahan</label>
                            <span class="form-control "><?php echo $kelurahan; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="kecamatan" class="form-label">Kecamatan</label>
                            <span class="form-control "><?php echo $kecamatan; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="domisili" class="form-label">Domisili Sekarang</label>
                            <span class="form-control "><?php echo $domisili; ?></span>
                        </div>
                    </div>

                    <!-- Education and Employment Information -->
                    <h5 class="mb-4">Informasi Pendidikan dan Pekerjaan</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="pendidikan" class="form-label">Pendidikan</label>
                            <span class="form-control "><?php echo $pendidikan; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="pekerjaan" class="form-label">Pekerjaan</label>
                            <span class="form-control "><?php echo $pekerjaan; ?></span>
                        </div>
                    </div>

                    <!-- Citizenship Information -->
                    <h5 class="mb-4">Informasi Kewarganegaraan dan Status</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mt-2">
                            <label for="kewarganegaraan" class="form-label">Kewarganegaraan</label>
                            <span class="form-control "><?php echo $kewarganegaraan; ?></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="status" class="form-label">Status</label>
                            <span class="form-control "><?php echo $status; ?></span>
                        </div>
                    </div>

                    <div class="text-start mt-4">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include "../footer.php"; ?>
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