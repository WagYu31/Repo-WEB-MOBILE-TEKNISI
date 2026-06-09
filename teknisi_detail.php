<?php
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

// Periksa apakah parameter id_teknisi ada di URL
if (isset($_GET["id_teknisi"]) && is_numeric($_GET["id_teknisi"])) {
    $teknisi = intval($_GET["id_teknisi"]);

    // Query aman dengan prepared statement
    $sql = "SELECT k.id_teknisi, k.*, t.nama AS nama_teknisi, c.nama AS nama_customer
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        LEFT JOIN customer c ON k.id_cust = c.id_cust
        WHERE FIND_IN_SET(?, k.id_teknisi) > 0
        ORDER BY 
            CASE 
                WHEN k.status = 'On Process' THEN 1
                WHEN k.status = 'Pending' THEN 2
                WHEN k.status = 'Clear' THEN 3
            END,
            CASE 
                WHEN k.status = 'On Process' THEN k.tgl_request
                WHEN k.status = 'Pending' THEN k.tgl_request
                WHEN k.status = 'Clear' THEN k.tgl_selesai
            END DESC";
    $stmtKegiatan = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmtKegiatan, "i", $teknisi);
    mysqli_stmt_execute($stmtKegiatan);
    $result = mysqli_stmt_get_result($stmtKegiatan);

    // ...

    // Anda dapat menampilkan daftar kegiatan teknisi dalam bentuk tabel HTML
} else {
    // Tampilkan pesan kesalahan jika id_teknisi tidak valid
    echo "ID Teknisi tidak valid.";
}
include "get-next-page.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Teknisi</title>
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
        
        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover, .finish-btn:hover {
            background-color: #0056b3; /* Mengubah warna latar saat dihover */
        }
        table{
            font-size:14px;
        }
        th{
            text-align:center;
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
            th{
                font-size:14px;
            }
            td, i{
                font-size:12px;
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

                                    <a href="teknisi.php" class="nav_link active"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
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
                                    <a href="teknisi.php" class="nav_link active"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="history.php" class="nav_link"> <i class='bx bxs-time nav_icon'></i> <span class="nav_name">Riwayat</span> </a>
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
                    <?php
                        $stmtTek = mysqli_prepare($conn, "SELECT * FROM teknisi WHERE id_teknisi = ?");
                    mysqli_stmt_bind_param($stmtTek, "i", $teknisi);
                    mysqli_stmt_execute($stmtTek);
                    $resTekResult = mysqli_stmt_get_result($stmtTek);
                        $dataTek = mysqli_fetch_assoc($resTekResult);
                        $namaTek = $dataTek["nama"];
                    ?>
                    <h2>Data Kegiatan Teknisi : <?php echo $namaTek; ?></h2>
                    <a href="teknisi.php" class="btn btn-secondary mb-3">
                        <i class='bx bx-arrow-back'></i> Kembali ke Daftar Teknisi
                    </a>
                    <!-- Tampilkan daftar kegiatan teknisi dalam bentuk tabel -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kegiatan</th>
                                    <th>Customer</th>
                                    <th colspan="2">Tanggal Request</th>
                                    <!--<th colspan="2">Tanggal Mulai</th>-->
                                    <!--<th colspan="2">Tanggal Selesai</th>-->
                                    <th>Status</th>
                                    <th>Aksi</th>
                                    <!-- Tambahkan kolom-kolom lain sesuai kebutuhan -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Tampilkan daftar kegiatan teknisi di sini -->
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $cust = $row["id_cust"];
                                    $req = $row["tgl_request"];
                                    $formattedReqD = !empty($req) ? date("d-m-Y", strtotime($req)) : '-'; // Periksa apakah $req tidak kosong
                                    $formattedReqT = !empty($req) ? date("H:i", strtotime($req)) : '-'; // Periksa apakah $req tidak kosong
                                
                                    $start = $row["tgl_mulai"];
                                    $formattedStartD = !empty($start) ? date("d-m-Y", strtotime($start)) : '-'; // Periksa apakah $start tidak kosong
                                    $formattedStartT = !empty($start) ? date("H:i", strtotime($start)) : '-'; // Periksa apakah $start tidak kosong
                                
                                    $fin = $row["tgl_selesai"];
                                    $formattedFinD = !empty($fin) ? date("d-m-Y", strtotime($fin)) : '-'; // Periksa apakah $fin tidak kosong
                                    $formattedFinT = !empty($fin) ? date("H:i", strtotime($fin)) : '-'; // Periksa apakah $fin tidak kosong
                                    
                                    $status = $row['status'];
                                    $statusClass = '';
            
                                    if ($status == 'Pending') {
                                        $statusClass = 'pending';
                                        $sts = 'Dijadwalkan';
                                    } elseif ($status == 'Reschedule' || $status == 'Reschedule2') {
                                        $statusClass = 'reschedule';
                                        $sts = 'Reschedule';
                                    } elseif ($status == 'Pause') {
                                        $statusClass = 'pause';
                                        $sts = 'Lanjut Nanti';
                                    } elseif ($status == 'On Process') {
                                        $statusClass = 'on-process';
                                        $sts = 'Di Proses';
                                    } elseif ($status == 'Clear') {
                                        $statusClass = 'clear';
                                        $sts = 'Selesai';
                                    }

                                    echo "<tr>";
                                    echo "<td>" . $no . "</td>";
                                    echo "<td>" . $row['jenis'] . "</td>";
                                    
                                    echo "<td>" . $row['nama_customer'] . "</td>";
                                    echo "<td style='text-align:center;'>" . $formattedReqT . "</td>";
                                    echo "<td style='text-align:center;'>" . $formattedReqD . "</td>";
                                    // echo "<td style='text-align:center;'>" . $formattedStartT . "</td>";
                                    // echo "<td style='text-align:center;'>" . $formattedStartD . "</td>";
                                    // echo "<td style='text-align:center;'>" . $formattedFinT . "</td>";
                                    // echo "<td style='text-align:center;'>" . $formattedFinD . "</td>";
                                    echo "<td style='text-align:center;'>" . $sts . "</td>";
                                    // Tambahkan kolom-kolom lain di sini
                                    ?>
                                        <td style="text-align:center;"><button class="btn btn-info view-btn" data-id="<?php echo $row['id_kegiatan']; ?>"><i class="far fa-eye"></i></button></td>
                                    <?php
                                    echo "</tr>";
                                    $no++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    
                    <div class="btn-np">
                        <!-- Tampilkan tombol "Previous" jika tidak ada di halaman pertama -->
                        <?php

                        if ($currentPage > 1) {
                            echo '<a href="?id_teknisi=' . $teknisi . '&page=' . ($currentPage - 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-left"></i></a>';
                        }
                        
                        echo '<span class="p"> Halaman ' . $currentPage . ' dari ' . $totalPages . ' </span>';
            
                        // Tampilkan tombol "Next" jika tidak ada di halaman terakhir
                        if ($currentPage < $totalPages) {
                            echo '<a href="?id_teknisi=' . $teknisi . '&page=' . ($currentPage + 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-right"></i></a>';
                        }
                        ?>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    <script>
        $(".view-btn").click(function() {
            var kegiatanId = $(this).data("id");
            window.location.href = "detail_kegiatan_2.php?id_kegiatan=" + kegiatanId;
        });
    </script>
</body>
</html>
