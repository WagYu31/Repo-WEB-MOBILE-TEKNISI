<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Teknisi";
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

  // Cek apakah NIK sudah ada (termasuk yang soft-deleted)
  $checkStmt = $conn->prepare("SELECT id, deleted_at FROM teknisi WHERE nik = ?");
  $checkStmt->bind_param("s", $nik);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    if ($existing['deleted_at'] !== null) {
      // Reactivate soft-deleted record
      $reactivate = $conn->prepare("UPDATE teknisi SET nama = ?, telp = ?, ktp = ?, deleted_at = NULL, created_at = ? WHERE id = ?");
      $reactivate->bind_param("ssssi", $nama, $no_tlp, $niKTP, $now, $existing['id']);
      if ($reactivate->execute()) {
        echo '<script>window.location.href = "data-teknisi.php";</script>';
        exit();
      } else {
        echo "Error: Gagal mengaktifkan kembali teknisi. " . $conn->error;
      }
      $reactivate->close();
    } else {
      // NIK masih aktif, tampilkan error
      echo '<script>alert("NIK ' . $nik . ' sudah digunakan oleh teknisi yang masih aktif."); window.location.href = "data-teknisi.php";</script>';
      exit();
    }
  } else {
    // Insert baru
    $sql = "INSERT INTO teknisi (nik, nama, telp, ktp, created_at) VALUES ('$nik', '$nama', '$no_tlp', '$niKTP', '$now')";

    if (mysqli_query($conn, $sql)) {
      echo '<script>window.location.href = "data-teknisi.php";</script>';
      exit();
    } else {
      echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
  }
  $checkStmt->close();
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
        .card-equal-height {
            height: 100%;
        }
        .row-equal-height > .col-12 > .card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .filter-btn {
            margin-top: 0.5rem;
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
    $todayDate = formatTanggal('dd MMMM yyyy');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <div class="row mb-4 mt-4">

        <?php
        include "teknisi-db-all.php";
        ?>

      </div>
                <?php
                    include "floating-menu.php";
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