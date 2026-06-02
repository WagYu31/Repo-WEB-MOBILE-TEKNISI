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

    // Tampilkan data dari database// Query SQL untuk mengambil data pelanggan dan total kegiatan, diurutkan berdasarkan total kegiatan secara menurun
    $sql = "SELECT c.id_cust, c.nama, c.nomor_tlp, c.alamat, COUNT(k.id_kegiatan) AS total_kegiatan
            FROM customer c
            LEFT JOIN kegiatan k ON c.id_cust = k.id_cust
            GROUP BY c.id_cust, c.nama, c.nomor_tlp, c.alamat
            ORDER BY total_kegiatan DESC";
    
    $result = mysqli_query($conn, $sql);


include "get-next-page.php";

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Customer</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
        include "dep-css.php";
    ?>
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
        .btn-np{
            text-align:center;
        }/* Mengatur gaya untuk tombol "Previous" dan "Next" */
        .btn-np a{
            margin-right: 20px; /* Menambahkan jarak kanan 20px antara tombol */
            padding: 4px 10px; /* Menyesuaikan ukuran tombol */
            background-color: #007bff; /* Memberikan warna latar biru yang bagus */
            color: #fff; /* Memberikan warna teks putih */
            border: none; /* Menghilangkan border */
            border-radius: 5px; /* Memberikan sudut bulat pada tombol */
            text-decoration: none; /* Menghilangkan garis bawah pada tautan */
        }
        
        .p{
            margin-right:20px;
            font-size:13px;
        }
        
        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover {
            background-color: #0056b3; /* Mengubah warna latar saat dihover */
        }
        .btn-right {
            float: right;
            margin-top: -50px; /* Atur sesuai dengan posisi vertikal yang diinginkan */
            margin-right: 10px; /* Atur sesuai dengan jarak dari tepi kanan */
        }
        
        table{
            margin-top:3vh;
        }
        th{
            text-align:center;
            font-size:16px;
        }
        td{
            font-size:14px;
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
            .table-responsive {
                overflow-x: auto;
            }
            h2{
                font-size:25px;
                font-weight:bold;
            }
            table{
                font-size:14px;
            }
            .btn-right{
                float: left; /* Hapus floating pada mode mobile */
                display: block; /* Membuat tombol menampilkan sebagai block element */
                text-align: center; /* Pusatkan tombol pada mode mobile */
                margin: 10px auto;
                margin-top:0;
                width:60%;
                font-size:16px;
            }
            .btn-warning{
                margin-left:10px;
                margin-bottom:5px;
            }
            
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
                <!--<div class="container">-->
                    <h2>Data Customer</h2>
                    <a href="input-customer.php" class="btn btn-primary btn-right">Tambah Customer</a>
                    <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Nama</th>
                                <?php
                                
                                if ($role == "Admin" || $role == "SA") {
                                    ?>
                                    <th scope="col">Jumlah Permintaan</th>
                                    <?php
                                }
                                
                                ?>

                                
                                <th scope="col">No Telepon</th>
                                <th scope="col">Alamat</th>
                                <?php
                                
                                if ($role == "Admin" || $role == "SA") {
                                    ?>
                                    <th scope="col" width="100px">Aksi</th>
                                    <?php
                                }
                                
                                ?>
                                    <!--<th scope="col">Aksi</th>-->
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td style='text-align:center;'>" . $no . "</td>";
                            
                                    if ($role !== "Sales") {
                                        echo "<td><a href='customer_detail.php?id_cust=" . $row["id_cust"] . "'>" . $row['nama'] . "</a></td>";
                                    } else {
                                        echo "<td>" . $row["nama"] . "</td>";
                                    }
                            
                                    $id_cust = $row["id_cust"];
                                    $queryTotalKegiatan = "SELECT COUNT(*) AS total_kegiatan FROM kegiatan WHERE id_cust = $id_cust";
                                    $resultTotalKegiatan = mysqli_query($conn, $queryTotalKegiatan);
                                    $totalKegiatan = mysqli_fetch_assoc($resultTotalKegiatan)["total_kegiatan"];
                            
                                    if ($role == "Admin" || $role == "SA") {
                                        echo "<td style='text-align:center;'>" . $totalKegiatan . "</td>";
                                    }
                            
                                    $nomorHandphone = $row['nomor_tlp'];
                            
                                    // Cek apakah nomor handphone dimulai dengan angka 0
                                    if (substr($nomorHandphone, 0, 1) === '0') {
                                        // Ganti angka 0 dengan 62
                                        $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                    }
                            
                                    echo "<td style='text-align:center;'><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                    echo $row['nomor_tlp'];
                                    echo "</a></td>";
                                    echo "<td>" . $row["alamat"] . "</td>";
                            
                                    if ($role == "Admin" || $role == "SA") {
                                        // Tambahkan tombol "Edit" dengan link ke halaman edit (contoh: edit.php?id=ID_CUSTOMER)
                                        echo "<td style='text-align:center;'>";
                                        echo "<a href='edit_customer.php?id=" . $row["id_cust"] . "' class='btn btn-warning btn-sm mr-2' title='Edit'><i class='far fa-edit'></i></a>";
                                        
                                        // Tambahkan tombol "Delete" dengan link ke skrip penghapusan (contoh: delete.php?id=ID_CUSTOMER)
                                        echo "<a href='delete.php?id=" . $row["id_cust"] . "' class='btn btn-danger btn-sm' title='Delete'><i class='far fa-trash-alt'></i></a>";
                                        
                                        echo "</td>";
                                    }
                                    
                            
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='4'>Tidak ada data customer.</td></tr>";
                            }
                            ?>

                            <!-- Tambahkan data customer lainnya di sini -->
                        </tbody>
                    </table>
                    </div>
                    <div class="btn-np">
                        <!-- Tampilkan tombol "Previous" jika tidak ada di halaman pertama -->
                        <?php

                        if ($currentPage > 1) {
                            echo '<a href="?page=' . ($currentPage - 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-left"></i></a>';
                        }
                        
                        echo '<span class="p"> Halaman ' . $currentPage . ' dari ' . $totalPages . ' </span>';
            
                        // Tampilkan tombol "Next" jika tidak ada di halaman terakhir
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-right"></i></a>';
                        }
                        ?>
                    </div>
                <!--</div>-->
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>

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