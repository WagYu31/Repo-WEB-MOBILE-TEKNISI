<?php
include "conn.php";
include "session.php";
$pageNow = "Ganti Password";
include "get-user-data.php"

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
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Ganti Password</h5>
                        </div>
                        <div class="card-body mt-n4">
                            <form action="proses_ganti_password.php" method="POST">
                                <div class="mb-3">
                                    <label for="oldPassword" class="form-label">Password Lama</label>
                                    <input type="password" class="form-control border p-2" id="oldPassword" name="oldPassword" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control border p-2" id="newPassword" name="newPassword" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmNewPassword" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control border p-2" id="confirmNewPassword" name="confirmNewPassword" required>
                                </div>
                                <input type="hidden" name="nikUsername" value="<?php echo $nik;?>">
                                <div class="text-center">
                                    <?php if (isset($_GET['error'])) : ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo $_GET['error']; ?>
                                        </div>
                                    <?php elseif (isset($_GET['success'])) : ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo $_GET['success']; ?>
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary mt-4 d-flex flex-column justify-content-start">Ganti Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            include "footer.php";
            ?>
        </div>
    </main>


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