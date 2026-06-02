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

    // Tangkap data dari form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nik = $_POST["nik"];
        $nama = $_POST["nama"];
        $no_wa = $_POST["no_wa"];
        $sales = "Sales";
        
        // Hilangkan karakter selain angka dari nomor telepon
        $no_tlp = preg_replace("/[^0-9]/", "", $no_wa);
    
        // Lakukan validasi data (jika diperlukan)
    
        // Ubah format nomor telepon
        if (substr($no_tlp, 0, 1) == "0") {
            // Jika angka pertama adalah 0, biarkan seperti itu
        } elseif (substr($no_tlp, 0, 2) == "62") {
            // Jika angka pertama adalah 62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 2);
        } elseif (substr($no_tlp, 0, 3) == "+62") {
            // Jika angka pertama adalah +62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 3);
        } elseif (substr($no_tlp, 0, 5) == "+6262") {
            // Jika angka pertama adalah +6262, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 5);
        } elseif (substr($no_tlp, 0, 4) == "6262") {
            // Jika angka pertama adalah 6162, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 4);
        } elseif (substr($no_tlp, 0, 6) == "+62+62") {
            // Jika angka pertama adalah +62+62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 6);
        } else {
            // Jika angka pertama bukan 0, 62, atau +62, tambahkan 0 di depannya
            $no_tlp = "0" . $no_tlp;
        }

        // Lakukan validasi data (jika diperlukan)

        // Masukkan data ke dalam database
        $sql = "INSERT INTO loewix (nik, nama, no_tlp, jabatan) VALUES ('$nik', '$nama', '$no_tlp', '$sales')";

        if (mysqli_query($conn, $sql)) {
            // Redirect atau refresh halaman
            echo '<script>window.location.href = "sales.php";</script>';
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }

    // Tampilkan data dari database
    $sql = "SELECT * FROM loewix";
    $result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sales</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">

    <!-- Tambahkan gaya kustom untuk sidebar -->
    <style>

        /* Stil untuk tabel */
        table {
            width: 100%;
        }

        table, th, td {
            border: 1px solid #ccc;
            border-collapse: collapse;
            text-align: center;
        }

        th, td {
            padding: 10px;
        }
        th{
            text-align:center;
            font-size:14px;
        }
        td{
            font-size:14px;
        }
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
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link active"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="history.php" class="nav_link"> <i class='bx bxs-time nav_icon'></i> <span class="nav_name">Riwayat</span> </a>
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
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
                    <h2>Tambah Data Sales</h2>
                    <!-- Form input data teknisi -->
                    <form class="form-inline" method="POST">
                        <div class="form-group mx-sm-3 mb-3">
                            <label for="nik" class="sr-only">NIK</label>
                            <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK">
                        </div>
                        <div class="form-group mx-sm-3 mb-3">
                            <label for="nama" class="sr-only">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama">
                        </div>
                        <div class="form-group mx-sm-3 mb-3">
                            <label for="no_telepon" class="sr-only">Nomor WhatsApp</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" class="form-control" id="no_wa" name="no_wa" placeholder="Masukkan No WhatsApp">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary mb-2 d-flex align-items-center">
                                <i class='bx bx-plus nav_icon'></i>
                                <span>Tambah</span>
                            </button>
                        </div>
                    </form>

                    <h2>Data Sales</h2>
                    <!-- Tabel data teknisi -->
                    <div class="table-responsive">
                     <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Nomor Telepon</th>
                                <th>Aksi</th> <!-- Kolom baru untuk tombol delete -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $nik = $row["nik"];
                                    // Hanya tampilkan data jika jabatannya adalah "Sales"
                                    if ($row["jabatan"] === "Sales") {
                                        echo "<tr>";
                                        echo "<td>" . $no . "</td>";
                                        echo "<td>" . $nik . "</td>";
                                        echo "<td>" . $row["nama"] . "</td>";
                                        $nomorHandphone = $row["no_tlp"];
                    
                                        // Cek apakah nomor handphone dimulai dengan angka 0
                                        if (substr($nomorHandphone, 0, 1) === '0') {
                                            // Ganti angka 0 dengan 62
                                            $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                        }
                    
                                        echo "<td><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                        echo $row['no_tlp'];
                                        echo "</a></td>";
                                        ?>
                                        <td><button class='btn btn-danger' onclick='deleteSales("<?php echo $nik; ?>")'><i class='far fa-trash-alt'></i></button></td>
                                        <?php
                    
                                        echo "</tr>";
                                        $no++;
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='5'>Tidak ada data Sales.</td></tr>";
                            }
                            ?>
                    
                        </tbody>
                    </table>

                    </div>
                </div>
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    
    <!-- Di dalam tag <script> Anda bisa menambahkan fungsi berikut: -->
<script>
    function deleteSales(nik) {
        if (confirm("Apakah Anda yakin ingin menghapus data sales ini?")) {
            // Konfirmasi penghapusan

            // Buat objek XMLHttpRequest
            var xhr = new XMLHttpRequest();

            var url = "hapus_sales.php";

            // Buat data yang akan dikirimkan dalam permintaan POST
            var data = new FormData();
            data.append("nik", nik); // Mengirim ID sales yang akan dihapus

            // Atur jenis permintaan dan URL
            xhr.open("POST", url, true);

            // Tangani perubahan status permintaan
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Tangani respons dari server
                    var response = xhr.responseText;
                    if (response === "sukses") {
                        // Data sales berhasil dihapus
                        location.reload(); // Muat ulang halaman untuk memperbarui tampilan
                    } else {
                        // Terjadi kesalahan
                        alert("Gagal menghapus data sales.");
                    }
                }
            };

            // Kirim permintaan dengan data yang sudah disiapkan
            xhr.send(data);
        }
    }
</script>


</body>
</html>
