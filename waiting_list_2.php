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

// Query untuk mengambil data kegiatan dengan status "Waiting"
$sql = "SELECT k.*, t.nama AS nama_teknisi 
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        WHERE k.status = 'Waiting'
        ORDER BY k.tgl_request DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting List</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
        include "dep-css.php";
    ?>
    
        <style>

        .modal, .fade{
            width:100vw;
            height:100vh;
        }
        .modal-content {
            border-radius: 10px; /* Menambahkan radius sudut pada modal */
        }
    
        .modal-header {
            background-color: #007bff; /* Warna latar belakang header modal */
            color: #fff; /* Warna teks header modal */
            border-bottom: none; /* Menghilangkan garis bawah header modal */
        }
    
        .modal-title {
            font-weight: bold;
        }
    
        .modal-footer {
            border-top: none; /* Menghilangkan garis atas footer modal */
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
            td, i{
                font-size:13px;
            }
            
            .footer{
                margin-bottom:12vh;
            }
            /* Sembunyikan kolom kecuali Customer, Status, dan Aksi */
            /*th:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(9)),*/
            /*td:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(9)) {*/
            /*    display: none;*/
            /*}*/
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
                                    <a href="waiting_list.php" class="nav_link active">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>

                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else if($role == "SA"){
                                    ?>
                                    <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link active">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
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
                                    <a href="index-teknisi.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
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
                    <h2>Waiting List Customer</h2>
                    <!-- Tabel data kegiatan Waiting -->
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kegiatan</th>
                                <th>Nama Customer</th>
                                <th>Nomor WhatsApp</th>
                                <th>Alamat</th>
                                <th>Request By</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td style='text-align:center;'>" . $no . "</td>";
                                    echo "<td>" . $row["jenis"] . "</td>";
                                
                                    // Query untuk mengambil nama customer berdasarkan id_cust
                                    $customerId = $row["id_cust"];
                                    $customerQuery = "SELECT * FROM customer WHERE id_cust = '$customerId'";
                                    $customerResult = mysqli_query($conn, $customerQuery);
                                
                                    if ($customerRow = mysqli_fetch_assoc($customerResult)) {
                                        echo "<td>" . $customerRow["nama"] . "</td>"; // Tampilkan tanggal request
                                        
                                        $nomorHandphone = $customerRow['nomor_tlp'];
                                    
                                        // Cek apakah nomor handphone dimulai dengan angka 0
                                        if (substr($nomorHandphone, 0, 1) === '0') {
                                            // Ganti angka 0 dengan 62
                                            $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                        }
                                        
                                        echo "<td><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                        echo $customerRow['nomor_tlp'];
                                        echo "</a></td>";
                                    
                                        echo "<td>" . $customerRow["alamat"] . "</td>";
                                    } else {
                                        echo "<td>Data Customer Tidak Ditemukan</td>"; // Tampilkan tanggal request
                                    }
                                
                                    echo "<td>" . $row["req_by"] . "</td>";
                                    echo "<td style='text-align:center;'>";
                                    ?>
                                    <button class="btn btn-primary jadwalkan-btn" data-id="<?php echo $row["id_kegiatan"]; ?>" data-tgl-request="<?php echo $row["tgl_request"]; ?>">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <?php
                                    echo ' <button class="btn btn-danger hapus-btn" data-id="' . $row["id_kegiatan"] . '"><i class="far fa-trash-alt"></i></button>';
                                    echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='6'>Tidak ada data kegiatan Waiting.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                
                
            
                <!-- Modal Jadwalkan -->
                <div class="modal fade" id="jadwalkanModal" tabindex="-1" role="dialog" aria-labelledby="jadwalkanModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="jadwalkanModalLabel">Jadwalkan Kegiatan</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="location.href='waiting_list.php';">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                                <div class="modal-body">
                                    <!-- Form to schedule an activity -->
                                    <form id="jadwalkanForm">
                                        <div class="form-group">
                                            <label for="tanggal">Tanggal:</label>
                                            <input type="date" class="form-control" id="tanggal" name="tanggal">
                                        </div>
                                        <div class="form-group">
                                            <label for="jam">Jam:</label>
                                            <input type="time" class="form-control" id="jam" name="jam">
                                        </div>
                                        <div class="form-group">
                                            <label for="nama_teknisi">Nama Teknisi</label>
                                            <?php
                                            // Query to fetch data from the 'teknisi' table
                                            $sql = "SELECT id_teknisi, nama FROM teknisi";
                                            $result = mysqli_query($conn, $sql);
                                
                                            // Check if there are any technicians available
                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $id_teknisi = $row['id_teknisi'];
                                                    $nama_teknisi = $row['nama'];
                                
                                                    // Display checkboxes for each technician
                                                    echo "<div class='form-check'>";
                                                    echo "<input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='$id_teknisi' id='teknisi$id_teknisi' disabled>";
                                                    echo "<label class='form-check-label' for='teknisi$id_teknisi'>$nama_teknisi</label>";
                                                    echo "</div>";
                                                }
                                            } else {
                                                echo "Tidak ada teknisi tersedia.";
                                            }
                                            ?>
                                        </div>
                                        <!--<span class="theTasks">-->
                                        <!--    <table class="table table-striped">-->
                                        <!--        <thead>-->
                                        <!--            <tr>-->
                                        <!--                <th>ID Teknisi</th>-->
                                        <!--                <th>Nama Teknisi</th>-->
                                        <!--                <th>Jenis</th>-->
                                        <!--                <th>Tanggal dan Waktu</th>-->
                                        <!--                <th>Status</th>-->
                                        <!--            </tr>-->
                                        <!--        </thead>-->
                                        <!--        <tbody id="kegiatanTableBody">-->
                                                    <!-- Data kegiatan akan ditampilkan di sini -->
                                        <!--        </tbody>-->
                                        <!--    </table>-->
                                        <!--</span>-->
                                    </form>
                                </div>


                            <div class="modal-footer">
                                <!--<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>-->
                                <button type="button" class="btn btn-primary" id="submitJadwalkan">Jadwalkan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Fungsi untuk menampilkan modal jadwalkan
        // $(".jadwalkan-btn").click(function () {
        //     var kegiatanId = $(this).data("id");
        //     // Reset form modal
        //     $("#jadwalkanForm")[0].reset();
        //     // Menambahkan data-id ke form untuk mengidentifikasi kegiatan yang akan dijadwalkan
        //     $("#jadwalkanForm").attr("data-id", kegiatanId);
        //     // Menampilkan modal
        //     $("#jadwalkanModal").modal("show");
        // });
        
        // Fungsi untuk menampilkan modal jadwalkan
        $(".jadwalkan-btn").click(function () {
            var kegiatanId = $(this).data("id");
            // Reset form modal
            $("#jadwalkanForm")[0].reset();
            // Menambahkan data-id ke form untuk mengidentifikasi kegiatan yang akan dijadwalkan
            $("#jadwalkanForm").attr("data-id", kegiatanId);
            // Menampilkan modal
            $("#jadwalkanModal").modal("show");
        
            // Ambil nilai tgl_request dari elemen data-tgl-request
            var tglRequest = $(this).data("tgl-request");
            var tanggalInput = document.getElementById("tanggal");
            var jamInput = document.getElementById("jam");
        
            // Periksa apakah tgl_request tidak kosong (tidak NULL)
            if (tglRequest) {
                // Pisahkan tanggal dan waktu dari tgl_request
                var tglWaktu = tglRequest.split(" ");
                if (tglWaktu.length === 2) {
                    var tanggal = tglWaktu[0];
                    var waktu = tglWaktu[1];
                    // Isi nilai pada input tanggal dan jam
                    tanggalInput.value = tanggal;
                    jamInput.value = waktu;
                    
                    handleDateChange();
                }
            }
        });

    
        // Fungsi untuk mengirim jadwal ke server
        $("#submitJadwalkan").click(function () {
            var kegiatanId = $("#jadwalkanForm").data("id");
            var tanggal = $("#tanggal").val();
            var jam = $("#jam").val();
            // Mengumpulkan teknisi yang terpilih
            // var selectedTechnicians = $(".teknisi-checkbox:checked").map(function () {
            //     return this.value;
            // }).get().join(",");
            // Mengumpulkan teknisi yang terpilih dalam bentuk array
            var selectedTechnicians = $(".teknisi-checkbox:checked").map(function () {
                return this.value;
            }).get();

            // Kirim data ke server menggunakan AJAX (sesuaikan dengan URL dan data yang dibutuhkan)
            $.ajax({
                url: "proses_jadwalkan_2.php", // Ganti dengan URL yang sesuai
                type: "POST",
                data: {
                    kegiatanId: kegiatanId,
                    teknisi: selectedTechnicians,
                    tanggal: tanggal,
                    jam: jam
                },
                success: function (response) {
                    if (response === "success") {
                        // Tutup modal setelah berhasil
                        $("#jadwalkanModal").modal("hide");
                        alert("Berhasil");
                        // Refresh halaman
                        window.location.reload();
                    } else {
                        alert("Gagal menjadwalkan kegiatan.");
                    }
                },
                error: function () {
                    alert("Terjadi kesalahan saat menghubungi server.");
                }
            });
        });
        
        
        // Fungsi untuk menampilkan modal konfirmasi penghapusan
        $(".hapus-btn").click(function () {
            var kegiatanId = $(this).data("id");
            if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
                // Kirim permintaan penghapusan ke server menggunakan AJAX (sesuaikan dengan URL yang sesuai)
                $.ajax({
                    url: "proses_hapus_kegiatan.php", // Ganti dengan URL yang sesuai
                    type: "POST",
                    data: {
                        kegiatanId: kegiatanId
                    },
                    success: function (response) {
                        if (response === "success") {
                            alert("Kegiatan berhasil dihapus.");
                            // Refresh halaman
                            window.location.reload();
                        } else {
                            alert("Gagal menghapus kegiatan.");
                        }
                    },
                    error: function () {
                        alert("Terjadi kesalahan saat menghubungi server.");
                    }
                });
            }
        });

    </script>
<script>
// Fungsi untuk menampilkan modal jadwalkan dan mengisi tanggal dan jam saat change dan load
function handleDateChange() {
    // Mendapatkan nilai tanggal yang dipilih
    var selectedDate = document.getElementById("tanggal").value;
    var selectedTime = document.getElementById("jam").value;

    // Mendapatkan semua kotak centang teknisi
    var checkboxes = document.querySelectorAll(".teknisi-checkbox");

    // Disable semua kotak centang
    checkboxes.forEach(function (checkbox) {
        checkbox.disabled = false;
    });

    // Lakukan permintaan AJAX saat tanggal berubah atau halaman dimuat
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "get-kegiatan-teknisi.php?tanggal=" + selectedDate, true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var kegiatanData = JSON.parse(xhr.responseText);

            checkboxes.forEach(function (checkbox) {
                var id_teknisi = checkbox.value;
                var teknisiData = kegiatanData.find(function (data) {
                    return data.id_teknisi == id_teknisi;
                });

                if (teknisiData) {
                    checkbox.disabled = false;
                    // Format ulang teks label
                    var formattedText = " ( ";
                    if (teknisiData.jenis) {
                        formattedText += teknisiData.jenis + " ";
                    }
                    formattedText += ` jam ${teknisiData.tgl_request.substring(11, 16)}`;
                    if (teknisiData.status == "Pending") {
                        formattedText += " - Dijadwalkan";
                    } else if (teknisiData.status == "On Process") {
                        formattedText += " - Dalam proses";
                    }
                    formattedText += ")";

                    checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "") + formattedText;
                } else {
                    // Jika tidak ada data teknisi, hapus teks yang ada di dalam tanda kurung
                    checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "");
                }
            });
        }
    };

    xhr.send();
}

// Tambahkan event listener ke elemen tanggal
document.getElementById("tanggal").addEventListener("change", handleDateChange);

// Jalankan kode saat halaman dimuat
window.addEventListener("load", handleDateChange);



</script>


<script>
    const jamInput = document.getElementById("jam");

    jamInput.addEventListener("input", function() {
        const selectedTime = new Date(`2000-01-01T${jamInput.value}`);
        const minTime = new Date(`2000-01-01T07:00`);
        const maxTime = new Date(`2000-01-01T20:00`);

        if (selectedTime < minTime || selectedTime > maxTime) {
            alert("Jam harus berada dalam rentang antara jam 07:00 pagi sampai jam 20:00 malam.");
            jamInput.value = ""; // Menghapus input jika waktu di luar rentang
        }
    });
</script>



</body>
</html>