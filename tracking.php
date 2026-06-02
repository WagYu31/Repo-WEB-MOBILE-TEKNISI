<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pemesanan</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/invoice-style.css?rev=<?php echo time(); ?>">
    <style>
        #result {
    border: 1px solid #e0e0e0;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    text-align: left;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

#result h4 {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

#result p {
    font-size: 16px;
    margin: 10px 0;
}
        /* CSS untuk menyesuaikan teks di dalam progress bar */
        .progress-bar-text {
            position: absolute;
            text-align: center;
            width: 100%;
            color: #000;
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
                            <a href="guest-mode.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                        </div>
                    </div> <a href="logout.php" class="nav_link"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </a>
                </nav>
            </div>
        <?php
            include "btm-nav.php";
        ?>
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3 text-center">
                <h2>Tracking Pemesanan</h2>
                <form method="POST" id="tracking-form">
                    <div class="form-group">
                        <label for="kode_transaksi">Kode Transaksi</label>
                        <input type="text" class="form-control" id="kode_transaksi" name="kode_transaksi" placeholder="Masukkan Kode Transaksi">
                    </div>
                    <button type="submit" class="btn btn-primary">Cek Pemesanan</button>
                </form>
                <div id="result" class="mt-4">
                    <!-- Hasil tracking pemesanan akan ditampilkan di sini -->
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    
    <?php
        include "foot.php";
        include "dep-js.php";
    ?>

    <!-- Sisipkan script Bootstrap dan JavaScript untuk menangani formulir -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Tangkap formulir dan elemen hasil tracking
        const trackingForm = document.getElementById("tracking-form");
        const kodeTransaksiInput = document.getElementById("kode_transaksi");
        const resultContainer = document.getElementById("result");

        trackingForm.addEventListener("submit", async function (event) {
            event.preventDefault();

            // Ambil kode transaksi dari input
            const kodeTransaksi = kodeTransaksiInput.value;

            // Kirim kode transaksi ke server untuk tracking
            const result = await trackPemesanan(kodeTransaksi);

            if (result) {
    // Tampilkan hasil tracking di dalam kontainer hasil
    resultContainer.innerHTML = `
        <h4>Hasil Tracking Pemesanan</h4>
        <p>Nama Customer: ${result.nama_customer}</p>
        <p>Nomor Telepon: ${result.nomor_customer}</p>
        <p>Status Kegiatan: ${result.status_kegiatan}</p>
        <div class="progress">
            <!-- Progress bar -->
            <div class="progress-bar" role="progressbar" style="width: 0%;">
                <div class="progress-bar-text"></div>
            </div>
        </div>
    `;

    // Tentukan lebar progress bar berdasarkan status kegiatan
switch (result.status_kegiatan) {
    case "Waiting":
        document.querySelector(".progress-bar").style.width = "25%";
        document.querySelector(".progress-bar").style.backgroundColor = "red";
        break;
    case "Pending":
        document.querySelector(".progress-bar").style.width = "50%";
        document.querySelector(".progress-bar").style.backgroundColor = "yellow";
        break;
    case "Reschedule":
        document.querySelector(".progress-bar").style.width = "50%";
        document.querySelector(".progress-bar").style.backgroundColor = "yellow";
        break;
    case "On Process":
        document.querySelector(".progress-bar").style.width = "75%";
        document.querySelector(".progress-bar").style.backgroundColor = "blue";
        break;
    case "Clear":
        document.querySelector(".progress-bar").style.width = "100%";
        document.querySelector(".progress-bar").style.backgroundColor = "green";
        break;
    default:
        // Default handling jika status tidak cocok
        break;
}

} else {
    // Tampilkan pesan jika kode transaksi tidak ditemukan
    resultContainer.innerHTML = "<p>Kode Transaksi tidak ditemukan.</p>";
}

        });

        async function trackPemesanan(kodeTransaksi) {
            try {
                const formData = new FormData();
                formData.append("kode_transaksi", kodeTransaksi);

                const response = await fetch("track_pemesanan.php", {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();

                return data;
            } catch (error) {
                console.error("Error:", error);
                return null;
            }
        }
    </script>
</body>
</html>
