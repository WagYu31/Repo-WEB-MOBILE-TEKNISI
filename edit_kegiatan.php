<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

include "conn.php";

$id_kegiatan = intval($_GET["id_kegiatan"]);

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

include "get-number-waiting.php";


if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id_kegiatan"])) {
    $id_kegiatan = $_GET["id_kegiatan"];

    include "get_data_kegiatan.php";
    
}

// Menghitung total jumlah data dalam array
$totalDataDB = count($idTeknisiKegiatan);

$idTeknisiString = implode(',', $idTeknisiKegiatan);

include "get_update_kegiatan.php";


// Query untuk mengambil data teknisi dan customer
$sqlTeknisi = "SELECT * FROM teknisi";
$resultTeknisi = mysqli_query($conn, $sqlTeknisi);

$sqlCustomer = "SELECT * FROM customer";
$resultCustomer = mysqli_query($conn, $sqlCustomer);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kegiatan</title>
    <!-- Sisipkan stylesheet Bootstrap dan gaya kustom jika diperlukan -->
    <?php include "dep-css.php"; ?>
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
            width :20px;
            height: 20px;
            justify-content:center;
            text-align:center;
            vertical-align:middle;
            padding-top: 2.5px; /* Padding untuk notifikasi */
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
        
        /* Menghilangkan tampilan bawaan panah */
        .form-control{
            -webkit-appearance: auto;
            -moz-appearance: auto;
            appearance: auto;
        }

        
        
        @media (max-width: 768px) {
            .footer{
                margin-bottom:12vh;
            }
        }
    </style>
</head>
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
                                    <a href="index.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='fas fa-tasks nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>

                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else if($role == "SA"){
                                    ?>
                                    <a href="index-sa.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notification"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
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
        <h2>Edit Kegiatan</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="teknisi_ids">Nama Teknisi:</label><br>
                <?php

                    echo "ID Teknisi: " . $idTeknisiString . "<br>" . $totalDataDB . "<br>";
                    echo count($teknisiIdsString) . "<br>";

                while ($teknisi = mysqli_fetch_assoc($resultTeknisi)) {
                    $teknisiId = $teknisi["id_teknisi"];
                    $checked = in_array($teknisiId, $idTeknisiKegiatan) ? "checked" : ""; // Mengganti $teknisiIds dengan $idTeknisiKegiatan
                    echo "<input type='checkbox' name='teknisi_ids[]' value='$teknisiId' $checked> " . $teknisi["nama"] . "<br>";
                }
                
                ?>
            </div>
            <div class="form-group">
                <label for="jenis_kegiatan">Jenis Kegiatan:</label>
                <select class="form-control" name="jenis_kegiatan" required>
                    <option value="Pasang Baru" <?php if ($jenis_kegiatan === "Pasang Baru") echo 'selected'; ?>>Pasang Baru</option>
                    <option value="Survey" <?php if ($jenis_kegiatan === "Survey") echo 'selected'; ?>>Survey</option>
                    <option value="Service" <?php if ($jenis_kegiatan === "Service") echo 'selected'; ?>>Service</option>
                </select>
            </div>
            <div class="form-group">
                <label for="customer_id">Nama Customer:</label>
                <select class="form-control" name="customer_id" required>
                    <option value="">Pilih Customer</option>
                    <?php

                    while ($customer = mysqli_fetch_assoc($resultCustomer)) {
                        $selected = ($customer["id_cust"] == $customer_id) ? "selected" : "";
                        echo "<option value='" . $customer["id_cust"] . "' " . $selected . ">" . $customer["nama"] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tgl_request_date">Tanggal Request:</label>
                <input type="date" class="form-control" name="tgl_request_date" value="<?php echo date('Y-m-d', strtotime($tgl_request)); ?>" required>
            </div>
            <div class="form-group">
                <label for="tgl_request_time">Waktu Request:</label>
                <input type="time" class="form-control" name="tgl_request_time" value="<?php echo date('H:i', strtotime($tgl_request)); ?>" required>
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan:</label>
                <textarea class="form-control" name="keterangan" rows="4" required><?php echo $keterangan; ?></textarea>
            </div>
            <input type="submit" name="update_kegiatan" class="btn btn-primary" value="Update Kegiatan">
        </form>
    </div>
                </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    <!-- Sisipkan script JavaScript atau file JavaScript eksternal jika diperlukan -->
    <?php include "dep-js.php"; ?>
</body>
</html>
