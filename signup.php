<?php
include "conn.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SignUp</title>
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
            <div class="col-md-6 mx-auto mt-5 signup-form">
                <div class="form-header">
                    <h2>Sign Up</h2>
                </div>
                <form action="proses_signup.php" method="post">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <select class="form-control" id="nik" name="nik">
                            <?php
                            
                            $kueri = "SELECT nik FROM user";
                            $reskueri = mysqli_query($conn, $kueri);
                            
                            // Query to retrieve data from the 'loewix' table
                            $sql = "SELECT nik, nama FROM loewix WHERE nik != '000'";
                    
                            // Execute the query
                            $result = mysqli_query($conn, $sql);
                    
                            // Check if there is data available
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $nik = $row['nik'];
                                    $nama = $row['nama'];
                    
                                    // Create an option for each technician
                                    echo "<option value='$nik'>$nama</option>";
                                }
                            } else {
                                echo "<option value=''>Tidak ada teknisi tersedia</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" class="form-control">
                        <?php if (isset($username_err)) echo "<p class='text-danger'>$username_err</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" class="form-control">
                        <?php if (isset($password_err)) echo "<p class='text-danger'>$password_err</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                        <?php if (isset($confirm_password_err)) echo "<p class='text-danger'>$confirm_password_err</p>"; ?>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Sign Up</button>
                    </div>
                    
                </form>
                <p class="signup-link">Sudah punya akun? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>

    <!-- Sisipkan script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
