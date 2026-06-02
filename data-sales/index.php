<?php
// Koneksi ke database (gantilah dengan informasi koneksi sesuai dengan database Anda)
$servername = "localhost";
$username = "u251910282_rootNewTek";
$password = "LoeLoe@220ip";
$database = "u251910282_teknisi_new";

$conn = mysqli_connect($servername, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<meta http-equiv='cache-control' content='no-cache'>-->
    <!--<meta http-equiv='expires' content='0'>-->
    <!--<meta http-equiv='pragma' content='no-cache'>-->
    <title>Grav-Tech Salary</title>
    <meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
    <meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
    <meta name="author" content="Irviani" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <script src="../js/script.js" defer></script>
    <script type="text/javascript" src="../tableExport.js"></script>
    <script type="text/javascript" src="../jquery.base64.js"></script>
    <script type="text/javascript" src="../html2canvas.js"></script>
    <script type="text/javascript" src="../jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="../jspdf/jspdf.js"></script>
    <script type="text/javascript" src="../jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="../js/script-download-all.js?rev=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/style-menu-bar.css"> -->
    <link rel="stylesheet" type="text/css" href="../css/style-data-karyawan.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="../css/foot.css?rev=<?php echo time(); ?>">


    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="../css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="../css/demo.css" />
    <link rel="stylesheet" type="text/css" href="../css/component.css" />
    <script src="../js/modernizr.custom.js"></script>

    <style>
        .highlight td {
            background-color: #ffff66;
        }
    </style>

</head>

<body>
    <div class="container no-print">
    </div><!-- /container -->

    <div class="content">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title" style="margin-left:20px;">Langkah-langkah untuk Mengunggah Dokumen</h5>
                        <ol>
                            <li>File data absen berupa file excel (.xls), buka dokumen tersebut lalu simpan kembali sebagai (.xlsx).</li>
                            <li>Simpan dengan nama "Bulan-Tahun.xlsx" contoh : Juni-2024.xlsx</li>
                            <li>Setelah itu unggah file di sini.</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-12" style="margin-left:20px;margin-top:4vh;">
                <h2 style="margin-bottom:10px;">Upload File Data Customer Sales</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    Pilih file Absensi untuk diunggah :
                    <br>
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <p></p><input type="submit" value="Unggah File" name="submit" class="btn btn-primary"></p>
                </form>
            </div>
        </div>


        <?php

        if (isset($_POST["submit"])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
            $uploadOk = 1;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Cek apakah file adalah file Excel
            if ($fileType != "xls" && $fileType != "xlsx") {
                echo "Maaf, hanya file Excel yang diizinkan.";
                $uploadOk = 0;
            }

            // Cek apakah file sudah ada
            if (file_exists($target_file)) {
                echo "Maaf, file sudah ada.";
                $uploadOk = 0;
            }

            // Jika upload gagal
            if ($uploadOk == 0) {
                echo "Maaf, file tidak terunggah.";
            } else {
                // Jika upload berhasil
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    echo "File " . basename($_FILES["fileToUpload"]["name"]) . " berhasil terunggah.";

                    // Baca file Excel dan impor data ke database
                    require_once 'PHPExcel/Classes/PHPExcel.php';
                    $objPHPExcel = PHPExcel_IOFactory::load($target_file);
                    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

                    // Initialize a row counter
                    $rowIndex = 0;

                    foreach ($sheetData as $row) {
                        $rowIndex++;
                    
                        // Prepare the data
                        $wa = "0" . $row['G'];
                        $nama = $conn->real_escape_string($row['A']);
                        $email = $conn->real_escape_string($row['B']);
                        $nama_toko = $conn->real_escape_string($row['C']);
                        $kategori = $conn->real_escape_string($row['D']);
                        $kota = $conn->real_escape_string($row['E']);
                        $alamat = $conn->real_escape_string($row['F']);
                        $kota_toko = $conn->real_escape_string($row['H']);
                        $alamat_toko = $conn->real_escape_string($row['I']);
                    
                        // Insert data into the database
                        $sql = "INSERT INTO cust (nama, email, nama_toko, kategori, kota, alamat, no_wa, kota_toko, alamat_toko, contact_person) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param('ssssssssss', $nama, $email, $nama_toko, $kategori, $kota, $alamat, $wa, $kota_toko, $alamat_toko, $wa);
                            if ($stmt->execute()) {
                                // Data inserted successfully
                            } else {
                                echo "Error: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            echo "Error: " . $conn->error;
                        }
                    }

                } else {
                    echo "Maaf, terjadi kesalahan saat mengunggah file.";
                }
            }
        }

        $conn->close();
        ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/classie.js"></script>
    <script src="../js/gnmenu.js"></script>
    <script>
        new gnMenu(document.getElementById('gn-menu'));
    </script>
    <script>
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>

</html>