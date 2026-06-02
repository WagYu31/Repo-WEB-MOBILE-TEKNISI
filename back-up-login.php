<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loewix | LOG-IN</title>
        <!-- Tambahkan favicon (logo) -->
        <link rel="icon" href="img/logo3.png" type="image/png">
    <!-- Sisipkan stylesheet Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <!-- Tambahkan gaya kustom -->
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-form, .signup-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-header h2 {
            margin: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-control {
            width: 100%;
        }

        .btn-primary {
            width: 100%;
        }

        .signup-link {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 mx-auto mt-5 login-form">
                <h2>Login</h2>
                <form action="proses_login.php" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                        <?php if (isset($username_err)) echo "<p class='text-danger'>$username_err</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
                        <?php if (isset($password_err)) echo "<p class='text-danger'>$password_err</p>"; ?>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <p class="signup-link">Belum punya akun? <a href="signup.php">Sign Up</a></p>
                <p class="signup-link">Masuk Sebagai Tamu <a href="guest-mode.php">Guest Mode</a></p>
                <p class="signup-link">Cek Status Permohonan Anda <a href="cek-resi.php">Cek Status</a></p>
            </div>
        </div>
    </div>

    <!-- Sisipkan script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    
        <script>
        // Periksa apakah ada parameter "login_err" dalam URL
        const urlParams = new URLSearchParams(window.location.search);
        const loginError = urlParams.get('login_err');

        // Jika ada parameter "login_err", tampilkan pesan kesalahan
        if (loginError === '1') {
            alert("Username atau password salah. Silakan coba lagi.");
        }
    </script>
</body>
</html>
