<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Bulanan"; // Anda bisa sesuaikan ini
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        setlocale(LC_TIME, 'id_ID.utf8');
        ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header p-3 text-center">
                            <h5 class="mb-0 text-uppercase font-weight-bold">Cetak Laporan Bulanan</h5>
                        </div>
                        <div class="card-body">
                            <form action="cetak-laporan-bulanan.php" method="GET" target="_blank">
                                <div class="mb-3">
                                    <label for="bulan" class="form-label">Pilih Bulan</label>
                                    <select class="form-control p-2" style="border:1px solid #cfcfcf;" id="bulan" name="bulan" required>
                                        <option value="1">Januari</option>
                                        <option value="2">Februari</option>
                                        <option value="3">Maret</option>
                                        <option value="4">April</option>
                                        <option value="5">Mei</option>
                                        <option value="6">Juni</option>
                                        <option value="7">Juli</option>
                                        <option value="8">Agustus</option>
                                        <option value="9">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="tahun" class="form-label">Masukkan Tahun</label>
                                    <input type="number" class="form-control p-2" style="border:1px solid #cfcfcf;" id="tahun" name="tahun" min="2020" max="2099" step="1" value="<?= date('Y'); ?>" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="material-icons text-sm me-2">print</i>
                                        Cetak Laporan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <?php include "js-include.php"; ?>
</body>
</html>