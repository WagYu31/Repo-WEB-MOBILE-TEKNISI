<?php
include "../../conn.php";
$pageNow = "Tambah Data Warga";
include "session.php";
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
                            <form id="editForm" method="post" action="proses_tambah_data_warga.php">
                                <h5 class="mt-4 mb-0">Informasi Personal</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="nama" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control border p-2" id="nama" name="nama" value="" required>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="nik" class="form-label">Nomor Induk Kependudukan</label>
                                        <input type="number" class="form-control border p-2" id="nik" name="nik" value="" required>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="tmpLahir" class="form-label">Tempat Lahir</label>
                                        <input type="text" class="form-control border p-2" id="tmpLahir" name="tmpLahir" value="">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="tglLahir" class="form-label">Tanggal Lahir</label>
                                        <input type="date" class="form-control border p-2" id="tglLahir" name="tglLahir" value="">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="jenisKelamin" class="form-label">Jenis Kelamin</label>
                                        <select class="form-select border p-2" id="jenisKelamin" name="jenisKelamin">
                                            <option value="Laki-Laki">Laki-Laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="agama" class="form-label">Agama</label>
                                        <select class="form-select border p-2" id="agama" name="agama">
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen (Protestan)">Kristen (Protestan)</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Budha">Budha</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Konghucu">Konghucu</option>
                                        </select>
                                    </div>
                                </div>

                                <h5 class="mb-4">Informasi Keluarga</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="noKk" class="form-label">Nomor Kartu Keluarga</label>
                                        <select class="form-select border p-2" id="noKk" name="noKk">
                                            <?php
                                            $queryNomorKK = "SELECT no_kk, kepala_keluarga FROM kartu_keluarga";
                                            $resultNomorKK = mysqli_query($conn, $queryNomorKK);

                                            while ($row = mysqli_fetch_assoc($resultNomorKK)) {
                                                $no_kk = $row['no_kk'];
                                                $kepala_keluarga = $row['kepala_keluarga'];
                                                echo "<option value='$no_kk'>$no_kk - $kepala_keluarga</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-2">
                                        <label for="hub" class="form-label">Hubungan Dalam Keluarga</label>
                                        <select class="form-select border p-2" id="hub" name="hub">
                                            <option value="Kepala Keluarga">Kepala Keluarga</option>
                                            <option value="Suami">Suami</option>
                                            <option value="Istri">Istri</option>
                                            <option value="Anak">Anak</option>
                                            <option value="Menantu">Menantu</option>
                                            <option value="Cucu">Cucu</option>
                                            <option value="Orang Tua">Orang Tua</option>
                                            <option value="Mertua">Mertua</option>
                                            <option value="Famili Lainnya">Famili Lainnya</option>
                                            <option value="Pembantu">Pembantu</option>
                                        </select>
                                    </div>
                                </div>

                                <h5 class="mb-4">Informasi Kontak</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control border p-2" id="nomorTelepon" name="nomorTelepon" value="">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control border p-2" id="email" name="email" value="">
                                    </div>
                                </div>

                                <h5 class="mb-4">Informasi Pendidikan dan Pekerjaan</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="pendidikan" class="form-label">Pendidikan</label>
                                        <select class="form-select border p-2" id="pendidikan" name="pendidikan">
                                            <option value="Tidak / Belum Sekolah">Tidak / Belum Sekolah</option>
                                            <option value="Belum Tamat SD / Sederajat">Belum Tamat SD / Sederajat</option>
                                            <option value="Tamat SD / Sederajat">Tamat SD / Sederajat</option>
                                            <option value="SLTP / Sederajat">SLTP / Sederajat</option>
                                            <option value="SLTA / Sederajat">SLTA / Sederajat</option>
                                            <option value="Diploma I / II">Diploma I / II</option>
                                            <option value="Akademi / Diploma III / S. Muda">Akademi / Diploma III / S. Muda</option>
                                            <option value="Diploma IV / Strata I">Diploma IV / Strata I</option>
                                            <option value="Strata II">Strata II</option>
                                            <option value="Strata III">Strata III</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-2">
                                        <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                        <input type="text" class="form-control border p-2" id="pekerjaan" name="pekerjaan" value="">
                                    </div>
                                </div>

                                <h5 class="mb-4">Informasi Kewarganegaraan dan Status</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="kewarganegaraan" class="form-label">Kewarganegaraan</label>
                                        <select class="form-select border p-2" id="kewarganegaraan" name="kewarganegaraan">
                                            <option value="WNI">WNI (Warga Negara Indonesia)</option>
                                            <option value="WNA">WNA (Warga Negara Asing)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select border p-2" id="status" name="status">
                                            <option value="Kawin">Kawin</option>
                                            <option value="Belum Kawin">Belum Kawin</option>
                                            <option value="Cerai Hidup">Cerai Hidup</option>
                                            <option value="Cerai Mati">Cerai Mati</option>
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