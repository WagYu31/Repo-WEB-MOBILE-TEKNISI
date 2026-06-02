<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Inventory";
// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nik = $_POST["nik"];
  $nama = $_POST["nama"];
  $no_wa = $_POST["no_wa"];
  $niKTP = $_POST["ktp"];
  $jbtn = "Teknisi";

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


  date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
  $now = date('Y-m-d H:i:s'); // Menyimpan date time saat ini ke variabel $now

  // Masukkan data ke dalam database
  $sql = "INSERT INTO teknisi (nik, nama, telp, ktp, created_at) VALUES ('$nik', '$nama', '$no_tlp', '$niKTP', '$now')";

  if (mysqli_query($conn, $sql)) {
    // $id_teknisi = mysqli_insert_id($conn);
    echo '<script>window.location.href = "teknisi-db.php";</script>';
    exit();
  } else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <?php
  include "head.php";
  ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }

    #toggleLoadMore {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }
    
    .inventory-card {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .inventory-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .image-container {
        position: relative;
        height: 180px;
        background-color: #f8f9fa;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        overflow: hidden;
    }

    .inventory-card .card-img-top {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .stock-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        text-align: center;
        padding: 1.25rem 0;
        margin-top: 1rem;
        border-top: 1px solid #f0f0f0;
    }

    .stock-info .stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1; }
    .stock-info .stat-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }

    .borrower-list-toggler {
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 500;
        color: #1A73E8;
    }
    
    .borrower-list-toggler .fa-chevron-down {
        transition: transform 0.3s ease;
    }

    .borrower-list-toggler[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
    }

    .image-popup-button {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        border: none;
        border-radius: 50%;
        width: 34px;
        height: 34px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        opacity: 0;
        transform: scale(0.8);
        transition: opacity 0.2s, transform 0.2s;
        z-index: 10;
    }

    .image-container:hover .image-popup-button {
        opacity: 1;
        transform: scale(1);
    }
    .image-popup-button i { font-size: 1rem; }

    #imagePreviewModal {
        background-color: rgba(0, 0, 0, 0.85);
    }
    #imagePreviewModal .modal-dialog {
        /* Memastikan dialog itu sendiri berada di tengah layar */
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0 auto;
    }
    #imagePreviewModal .modal-content {
        background: transparent;
        border: none;
        box-shadow: none;
        /* Ukuran konten disesuaikan dengan gambar */
        width: auto;
    }
    #imagePreviewModal .modal-body {
        padding: 0;
    }
    #imagePreviewModal img {
        /* Batasi ukuran gambar agar tidak melebihi layar */
        max-height: 90vh;
        max-width: 90vw;
        border-radius: 0.5rem;
    }
    #imagePreviewModal .btn-close-lightbox {
        position: fixed;
        top: 20px;
        right: 20px;
        font-size: 1.5rem;
        color: white;
        opacity: 0.8;
        z-index: 1060;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    #imagePreviewModal .btn-close-lightbox:hover {
        opacity: 1;
    }
        <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4 pt-0">

      <div class="row mb-4 mt-0">

        <?php
        include "inventory-db.php";
        ?>

      </div>
                <?php
                    // include "floating-menu.php";
      include "footer.php";
      ?>
    </div>


  </main>
  <?php
  include "js-include.php";
  ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    function deleteTek(nik) {
      if (confirm("Apakah Anda yakin ingin menghapus data teknisi ini?")) {
        // Konfirmasi penghapusan

        // Buat objek XMLHttpRequest
        var xhr = new XMLHttpRequest();

        var url = "hapus_teknisi.php";

        // Buat data yang akan dikirimkan dalam permintaan POST
        var data = new FormData();
        data.append("nik", nik); // Mengirim ID sales yang akan dihapus

        // Atur jenis permintaan dan URL
        xhr.open("POST", url, true);

        // Tangani perubahan status permintaan
        xhr.onreadystatechange = function() {
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
  <script>
document.getElementById('targetInput').addEventListener('input', function (e) {
    let value = e.target.value;

    // Hapus semua karakter selain angka
    value = value.replace(/[^0-9]/g, '');

    // Pisahkan setiap 3 digit dengan koma
    if (value) {
        value = parseInt(value, 10).toLocaleString('id-ID');
    }

    // Tampilkan hasil yang sudah diformat di input
    e.target.value = value;
});

      // Fungsi untuk membuka modal dan mengisi NIK ke input hidden
function openTargetModal(nik) {
    // Set value NIK di input hidden
    document.getElementById('nikInput').value = nik;
    
    // Tampilkan modal
    $('#targetModal').modal('show');
}

document.getElementById('targetForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Mencegah pengiriman form secara default
    const targetInput = document.getElementById('targetInput');

    // Hapus semua tanda baca selain angka
    targetInput.value = targetInput.value.replace(/[^\d]/g, ''); 

    // Ambil nilai target dan NIK dari form
    const targetNominal = parseFloat(targetInput.value); // Pastikan nilai decimal
    const nik = document.getElementById('nikInput').value;

    // Buat request AJAX untuk mengirim data ke server
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_target.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    // Data yang dikirim
    const params = `target=${targetNominal}&nik=${nik}`;

    xhr.onload = function () {
        if (this.status === 200) {
            // Proses berhasil
            alert('Target berhasil diupdate!');

            // Tutup modal
            $('#targetModal').modal('hide');

            // Refresh halaman atau update tampilan jika perlu
            location.reload(); // Bisa diganti dengan metode lain untuk update tanpa reload
        } else {
            // Handle error
            alert('Terjadi kesalahan saat mengupdate target.');
        }
    };

    // Kirim data
    xhr.send(params);
});


  </script>

</body>

</html>