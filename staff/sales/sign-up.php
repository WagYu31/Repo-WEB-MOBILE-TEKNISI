<?php
session_start();
include "../conn.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
  <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
  <title>
    SIGN UP
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
  <?php
  if (isset($_SESSION['sign_up_error'])) {
    echo '<script>';
    echo 'var signUpError = "' . $_SESSION['sign_up_error'] . '";';
    echo '</script>';
    unset($_SESSION['sign_up_error']); // Clear the error message to avoid displaying it again
  }
  ?>
</head>
<?php
function isMobileDevice()
{
  return preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|Windows Phone/', $_SERVER['HTTP_USER_AGENT']);
}

$bodyClass = isMobileDevice() ? 'bg-white' : 'bg-gradient-dark';
?>

<body class="<?php echo $bodyClass; ?>">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
      </div>
    </div>
  </div>
  <main class="main-content  mt-0">
    <section>
      <div class="page-header min-vh-100">
        <div class="container">
          <div class="row">
            <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto ms-lg-auto p-3 bg-white" style="border-radius: 20px;">
              <div class="card card-plain">
                <div class="card-header" style="border-bottom:1px solid #ccc;">
                  <h4 class="font-weight-bolder">DAFTAR AKUN</h4>
                  <p class="mb-0">Masukan Nama, Username dan Kata Sandi Anda</p>
                </div>
                <div class="card-body">
                  <form role="form" action="proses_signup.php" method="POST" class="text-start">
                    <div class="input-group input-group-outline mb-3">
                      <label>Nama Lengkap</label><br>
                      <select class="form-control mt-4" style="margin-left:-100px;" id="nip" name="nip" required>
                        <option value=""></option>
                        <?php

                        $kueri = "SELECT id_account FROM account";
                        $reskueri = mysqli_query($conn, $kueri);

                        $sql = "SELECT nip, nama FROM sales WHERE nip != '000'";

                        $result = mysqli_query($conn, $sql);

                        if (mysqli_num_rows($result) > 0) {
                          while ($row = mysqli_fetch_assoc($result)) {
                            $nip = $row['nip'];
                            $nama = $row['nama'];

                            echo "<option value='$nip'>$nama</option>";
                          }
                        } else {
                          echo "<option value=''>Tidak ada teknisi tersedia</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="input-group input-group-outline mb-3">
                      <label class="form-label">Username</label>
                      <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="input-group input-group-outline mb-3">
                      <label class="form-label">Kata Sandi</label>
                      <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="input-group input-group-outline mb-3">
                      <label class="form-label">Konfirmasi Kata Sandi</label>
                      <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn btn-lg bg-gradient-primary btn-lg w-100 mt-4 mb-0">Sign Up</button>
                    </div>
                  </form>
                </div>
                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                  <p class="mb-2 text-sm mx-auto">
                    Sudah punya akun?
                    <a href="index.php" class="text-primary text-gradient font-weight-bold">LOG IN</a>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <footer class="footer position-absolute bottom-2 py-2 w-100">
      <div class="container">
        <div class="row align-items-center justify-content-lg-between">
          <div class="col-12 col-md-12 my-auto">
            <div class="copyright text-center text-sm text-muted">
              © 2024 Loewix
            </div>
          </div>
        </div>
      </div>
    </footer>
  </main>
  <!--   Core JS Files   -->
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    // Display the alert if signUpError is set and not an empty string
    if (typeof signUpError !== 'undefined' && signUpError !== '') {
      alert(signUpError);
    }
  </script>
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
  <script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>