<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loewix | Guest Form</title>
        <!-- Tambahkan favicon (logo) -->
        <link rel="icon" href="img/logo3.png" type="image/png">
    <!-- Sisipkan stylesheet Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
    <style>
        
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
        .navv input:checked ~ .menuv li:nth-child(1) {
          top: -80px;
          transition-delay: 0.1s;
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
            <header class="header" id="header">
                <div class="header_toggle d-none d-md-block"> <i class='bx bx-menu' id="header-toggle"></i> </div>
                <div class="header_img"> <img src="img/loewix.png" alt=""></div>
                 Hai, Sobat LOEWIX
            </header>
            <div class="l-navbar" id="nav-bar">
                <nav class="nav">
                    <div> <a href="#" class="nav_logo"> <img src="img/logo2.png" width="50px"></img> <span class="nav_logo-name">Loewix</span> </a>
                        <div class="nav_list">
                            <a href="guest-mode.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                            <a href="cek-resi.php" class="nav_link"> <i class='bx bx-task nav_icon'></i> <span class="nav_name">Cek Status</span> </a>
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
                    <h2>Form Permohonan</h2>
                    <!-- Form input data survey -->
                    <form method="POST" id="first">

                    <!--Sudah Terdaftar-->
                        
                        <div class="form-group">
                            <label for="no_whatsapp">No WhatsApp</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" class="form-control" id="no_whatsapp" name="nomorTelepon" placeholder="Masukkan No WhatsApp">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                    
                    
                    
                    <!--Next Step-->
                    <form method="POST" id="next-step" style="display: none;">
                        
                        <input type="hidden" name="id_customer" id="id_customer">
                        
                        <div class="form-group">
                            <label for="nama_customer">Nama Customer</label>
                            <input type="text" class="form-control" id="nama_customer" name="nama_customer" placeholder="Nama Lengkap">
                        </div>
                        
                        <div class="form-group">
                            <label for="no_whatsapp">No WhatsApp</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" class="form-control" id="nomor_customer" name="nomor_customer" placeholder="Masukkan No WhatsApp">
                            </div>
                        </div>
                                                
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea class="form-control" id="alamat_customer" rows="3" name="alamat_customer" placeholder="Alamat"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="Kegiatan">Kegiatan</label>
                            <select class="form-control" id="kegiatan" name="kegiatan">
                                <option value="Survey">Survey</option>
                                <option value="Pasang Baru">Pasang Baru</option>
                                <option value="Service">Service</option>
                                <!-- Tambahkan daftar nama teknisi lainnya di sini -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea>
                        </div>
                        
                        
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                    
                </div>
            </main>
        </div>
    </div>
    
<?php
    include "foot.php";
?>
    
    <!-- Sisipkan script Bootstrap -->
    <script src="js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    
<script>
// Tangkap formulir dan elemen-elemen yang diperlukan
const firstForm = document.getElementById("first");
const nextStepForm = document.getElementById("next-step");
const namaCustomerInput = document.getElementById("nama_customer"); 
const nomorCustomerInput = document.getElementById("nomor_customer");
const alamatCustomerInput = document.getElementById("alamat_customer");
const idCustomerInput = document.getElementById("id_customer");

firstForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    // Cek nomor telepon di sini (Anda perlu menambahkan kode AJAX untuk memeriksa di database)
    const nomorTelepon = document.getElementById("no_whatsapp").value;

    // Contoh: Simpan hasil cek di variabel isNomorTeleponTerdaftar (ganti ini dengan kode AJAX Anda)
    const isNomorTeleponTerdaftar = await cekNomorTelepon(nomorTelepon);

    if (isNomorTeleponTerdaftar) {
        // Nomor telepon sudah terdaftar, tampilkan formulir berikutnya
        nextStepForm.style.display = "block";
        firstForm.style.display = "none"; // Ganti urutan ini agar form pertama disembunyikan
    } else {
        // Nomor telepon belum terdaftar, tampilkan pesan kesalahan
        nextStepForm.style.display = "block";
        firstForm.style.display = "none";
    }
});

// Fungsi untuk memeriksa nomor telepon di server (AJAX)
async function cekNomorTelepon(nomorTelepon) {
    try {
        const formData = new FormData();
        formData.append("nomorTelepon", nomorTelepon);

        const response = await fetch("cek_nomor_telepon.php", {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.status === "terdaftar") {
            namaCustomerInput.value = data.namaCustomer;
            nomorCustomerInput.value = data.nomorCustomer;
            alamatCustomerInput.value = data.alamatCustomer;
            idCustomerInput.value = data.idCustomer;

            // Menjadikan input sebagai readonly
            namaCustomerInput.setAttribute("readonly", "true");
            nomorCustomerInput.setAttribute("readonly", "true");
            alamatCustomerInput.setAttribute("readonly", "true");
            
            return true;
        } else if (data.status === "tidak terdaftar") {
            nomorCustomerInput.value = data.nmr;
            
            nomorCustomerInput.setAttribute("readonly", "true");
            return true;
        }
    } catch (error) {
        console.error("Error:", error);
        return false;
    }
}


nextStepForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    
    const nmCustomer = document.getElementById("nama_customer").value; 
    const nmrCustomer = document.getElementById("nomor_customer").value;
    const almtCustomer = document.getElementById("alamat_customer").value;
    const idCust = document.getElementById("id_customer").value;
    const kegiatan = document.getElementById("kegiatan").value;
    const keterangan = document.getElementById("keterangan").value;

    // Kirim data ke server
    const isSuccess = await tambahDataKegiatan(nmCustomer, nmrCustomer, almtCustomer, idCust, kegiatan, keterangan);
    
    if (isSuccess) {
    console.log("Redirecting to berhasil.php with id_kegiatan: " + isSuccess.id_kegiatan);
    window.location.href = `berhasil.php?id_kegiatan=${isSuccess.id_kegiatan}`;
    } else {
        alert("Gagal menyimpan data");
        window.location.reload();
    }
});

async function tambahDataKegiatan(nmCustomer, nmrCustomer, almtCustomer, idCust, kegiatan, keterangan) {
    try {
        const formData = new FormData();
        formData.append("nama_customer", nmCustomer);
        formData.append("nomor_customer", nmrCustomer);
        formData.append("alamat_customer", almtCustomer);
        formData.append("id_customer", idCust);
        formData.append("kegiatan", kegiatan);
        formData.append("keterangan", keterangan);

        const response = await fetch("tambah_data_kegiatan.php", {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.status === "sukses") {
            return data;
        } else {
            return false;
        }
    } catch (error) {
        console.error("Error:", error);
        return false;
    }
}


</script>



</body>
</html>
