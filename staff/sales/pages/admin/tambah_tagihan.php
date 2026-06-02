<?php
include "../../conn.php";
$pageNow = "Tambah Tagihan";
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
                                <h6 class="text-white text-capitalize ps-3">Tambah Data Warga</h6>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form id="editForm" method="post" action="edit_data_warga.php">
                                <!-- Personal Information -->
                                <h5 class="mb-4">Informasi Personal</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="nama" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control border p-2" id="nama" name="nama" required>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="nik" class="form-label">Nomor Induk Kependudukan</label>
                                        <input type="number" class="form-control border p-2" id="nik" name="nik" required>
                                    </div>
                                </div>

                                <!-- Birth Information -->
                                <h5 class="mb-4">Informasi Kelahiran</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="tmpLahir" class="form-label">Tempat Lahir</label>
                                        <input type="text" class="form-control border p-2" id="tmpLahir" name="tmpLahir">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="tglLahir" class="form-label">Tanggal Lahir</label>
                                        <input type="date" class="form-control border p-2" id="tglLahir" name="tglLahir">
                                    </div>
                                </div>

                                <!-- Family Information -->
                                <h5 class="mb-4">Informasi Keluarga</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="noKk" class="form-label">Nomor Kartu Keluarga</label>
                                        <input type="number" class="form-control border p-2" id="noKk" name="noKk">
                                    </div>
                                    <!-- <div class="col-md-3">
                                        <label for="noKk" class="form-label">Kepala Keluarga</label>
                                        <input type="text" class="form-control border p-2" id="kepalaKeluarga" name="kepalaKeluarga" value="">
                                    </div> -->
                                    <div class="col-md-6 mt-2">
                                        <label for="hub" class="form-label">Hubungan Dalam Keluarga</label>
                                        <select class="form-select border p-2" id="hub" name="hub">
                                            <option value="" disabled>-- Pilih Hubungan --</option>
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

                                <!-- Contact Information -->
                                <h5 class="mb-4">Informasi Kontak</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control border p-2" id="nomorTelepon" name="nomorTelepon">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control border p-2" id="email" name="email">
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <h5 class="mb-4">Informasi Alamat</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="alamat" class="form-label">Alamat</label>
                                        <input type="text" class="form-control border p-2" id="alamat" name="alamat">
                                    </div>
                                    <div class="col-md-1 mt-2">
                                        <label for="rt" class="form-label">RT</label>
                                        <input type="text" class="form-control border p-2" id="rt" name="rt">
                                    </div>
                                    <div class="col-md-1 mt-2">
                                        <label for="rw" class="form-label">RW</label>
                                        <input type="text" class="form-control border p-2" id="rw" name="rw">
                                    </div>
                                    <div class="col-md-2 mt-2">
                                        <label for="kodePos" class="form-label">Kode POS</label>
                                        <input type="text" class="form-control border p-2" id="kodePos" name="kodePos">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="kelurahan" class="form-label">Kelurahan</label>
                                        <input type="text" class="form-control border p-2" id="kelurahan" name="kelurahan">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="kecamatan" class="form-label">Kecamatan</label>
                                        <input type="text" class="form-control border p-2" id="kecamatan" name="kecamatan">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="domisili" class="form-label">Domisili Sekarang</label>
                                        <input type="text" class="form-control border p-2" id="domisili" name="domisili">
                                    </div>
                                </div>

                                <!-- Education and Employment Information -->
                                <h5 class="mb-4">Informasi Pendidikan dan Pekerjaan</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="pendidikan" class="form-label">Pendidikan</label>
                                        <input type="text" class="form-control border p-2" id="pendidikan" name="pendidikan">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                        <input type="text" class="form-control border p-2" id="pekerjaan" name="pekerjaan">
                                    </div>
                                </div>

                                <!-- Citizenship Information -->
                                <h5 class="mb-4">Informasi Kewarganegaraan dan Status</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mt-2">
                                        <label for="kewarganegaraan" class="form-label">Kewarganegaraan</label>
                                        <select class="form-select border p-2" id="kewarganegaraan" name="kewarganegaraan">
                                            <option value="" disabled>-- Pilih Kewarganegaraan --</option>
                                            <option value="WNI">WNI (Warga Negara Indonesia)</option>
                                            <option value="WNA">WNA (Warga Negara Asing)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select border p-2" id="status" name="status">
                                            <option value="" disabled>-- Pilih Status --</option>
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