<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

    include "conn.php";

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

include "get-number-waiting.php";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $id_customer = $_GET["id"];
    
}

    // Tangkap data dari form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_customer_get = $_GET['id'];
        $nama = $_POST["nama"];
        $no_tlp = $_POST["no_tlp"];
        $alamat = $_POST["alamat"];
        
        include "nomor_telepon.php";
    
        
            // Insert data into the database
            $sql = "UPDATE customer SET nama='$nama', nomor_tlp='$no_tlp', alamat='$alamat' WHERE id_cust=$id_customer_get";

            if (mysqli_query($conn, $sql)) {
                // Redirect atau refresh halaman
                echo '<script>window.location.href = "data-customer.php";</script>';
                exit();
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
        // Check if a customer with the same phone number exists
        // $checkCustomerQuery = "SELECT nama FROM customer WHERE nomor_tlp = '$no_tlp'";
        // $checkCustomerResult = mysqli_query($conn, $checkCustomerQuery);
    
        // $duplikat = isset($_POST["duplikat"]) ? true : false;
    
        // if (mysqli_num_rows($checkCustomerResult) > 0 && !$duplikat) {
        //     $existingCustomer = mysqli_fetch_assoc($checkCustomerResult);
        //     $existingCustomerName = $existingCustomer['nama'];
        //         // If a customer with the same phone number exists and "Duplikat" is not checked, show an alert
        //     echo '<script>';
        //     echo 'if (confirm("PERINGATAN! Data pelanggan dengan nomor telepon yang sama sudah ada dengan nama: ' . $existingCustomerName . '\nJika ingin memasukkan data dengan nama atau alamat yang berbeda, silakan centang Duplikat Data.")) {';
        //     echo '    window.location.href = "data-customer.php";'; // Redirect ke halaman data-customer.php
        //     echo '}';
        //     echo '</script>';

        // } else {
        // }
    }
    

    // Tampilkan data dari database
    $sql = "SELECT * FROM teknisi";
    $result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Customer</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
        include "dep-css.php";
    ?>
</head>
<style>
    
        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px; /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 8px; /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red; /* Warna latar belakang notifikasi */
            color: white; /* Warna teks notifikasi */
            font-size: 10px; /* Ukuran teks notifikasi */
            border-radius: 50%; /* Membuat notifikasi menjadi lingkaran */
            padding: 2px 6px; /* Padding untuk notifikasi */
            vertical-align:middle;
            justify-content:center;
        }
        p.small-text{
            font-size:11px;
            color:blue;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 5px 3px 15px rgba(0, 0, 0, 0.5);
        }
        
        li.nav-item a i, li.nav-item span{
            color:#4723D9;
        }
        
        ul.menuv li i{
            color:white;
        }
        .menuv li a span {
            width:65px;
            left:-17%;
        }
        .menuv li a span.tg{
            left:-45%;
        }
        
        @media (max-width: 768px) {
            .footer{
                margin-bottom:12vh;
            }
        }

</style>
<body id="body-pd">
    <div class="container-fluid">
        <div class="row">
            <?php
                include "header.php";
            ?>
            <div class="l-navbar" id="nav-bar">
                <nav class="nav">
                    <div> <a href="#" class="nav_logo"> <img src="img/logo2.png" width="50px"></img> <span class="nav_logo-name">Loewix</span> </a>
                        <div class="nav_list">
                            <?php
                                if($role == "Admin"){
                                    ?>
                                    <a href="index.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>

                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link active"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else if($role == "SA"){
                                    ?>
                                    <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link active"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="history.php" class="nav_link"> <i class='bx bx-time nav_icon'></i> <span class="nav_name">Riwayat</span> </a>
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="data-customer.php" class="nav_link active"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else{
                                    ?>
                                    <a href="index-teknisi.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="profile-tek.php" class="nav_link"> <i class='bx bx-throphy nav_icon'></i> <span class="nav_name">Profile</span> </a>
                                    <!--<a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>-->
                                <?php
                                }
                            ?>
                            <!-- <a href="#" class="nav_link"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Stats</span> </a> -->
                        </div>
                    </div> <a href="logout.php" class="nav_link"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </a>
                </nav>
            </div>
            
            
            <?php
                include "btm-nav.php";
            ?>
            
            <!-- Konten Utama -->
            <main id="content" class="mx-auto">
                <div class="container">
                    <h2>Edit Data Customer</h2>
                    <!-- Form input data customer baru -->
                    <?php
                    $sqlCustomer = "SELECT * FROM customer WHERE id_cust = $id_customer";
                    $resultCustomer = mysqli_query($conn, $sqlCustomer);
                    while($rowCust = mysqli_fetch_assoc($resultCustomer)){
                        $namaCust = $rowCust['nama'];
                        $nomorTlp = $rowCust['nomor_tlp'];
                        $alamatCust = $rowCust['alamat'];
                    ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="nama">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama" value="<?php echo $namaCust;?>" required>
                        </div>
                        <div class="form-group">
                            <label for="no_whatsapp">No WhatsApp</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" class="form-control" id="no_whatsapp" name="no_tlp" placeholder="Masukkan No WhatsApp" value="<?php echo $nomorTlp;?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea class="form-control" id="alamat" rows="3" name="alamat" placeholder="Masukkan alamat" required><?php echo $alamatCust;?></textarea>
                        </div>
                            
                            <div class="form-group">
                                <input type="checkbox" id="duplikat" name="duplikat">
                                <label for="duplikat">Duplikat Data</label>
                                <p class="small-text">* Centang jika ingin input data customer dengan nama atau alamat berbeda tapi memiliki nomor telepon yang sama.</p>
                            </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                    <?php
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    
    <!-- Sisipkan script Bootstrap -->

    <!-- Tambahkan script untuk mengatur sidebar responsif -->
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });
        });
    </script>
</body>
</html>