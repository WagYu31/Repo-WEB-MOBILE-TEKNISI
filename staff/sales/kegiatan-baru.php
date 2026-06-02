<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Kegiatan Baru";
$currentPage = "Today";

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }

    #toggleLoadMore,
    #toggleLoadMore1,
    #toggleLoadMore2 {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }
        <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $todayDate = strftime('%d %B %Y');
    ?>

<div class="container-fluid py-4">

  <div class="card p-4 mb-4 shadow-sm">
<?php

// Ambil customer
$customerResult = mysqli_query($conn, "SELECT id, nama FROM sales_customer WHERE deleted_at IS NULL");

// Ambil sales
$salesResult = mysqli_query($conn, "SELECT id, nama FROM sales WHERE deleted_at IS NULL");

// Jika form disubmit
// Set timezone ke Jakarta
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jadwal = $_POST['jadwal'];
    $visit = $_POST['visit'];
    $id_customer = $_POST['id_customer'];
    $status = 'dijadwalkan';
    $selectedSales = $_POST['sales'] ?? [];

    // Insert ke kegiatan_sales dengan created_at dan updated_at
    $stmt = $conn->prepare("
        INSERT INTO kegiatan_sales (jadwal, keterangan, id_customer, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("ssis", $jadwal, $visit, $id_customer, $status);
    $stmt->execute();
    $kegiatanId = $stmt->insert_id;
    $stmt->close();

    // Insert ke team_kegiatan_sales
    foreach ($selectedSales as $id_sales) {
        // Ambil nama sales
        $getNama = mysqli_query($conn, "SELECT nama FROM sales WHERE id = '$id_sales' LIMIT 1");
        $namaSales = mysqli_fetch_assoc($getNama)['nama'] ?? '';

        $stmtTeam = $conn->prepare("
            INSERT INTO team_kegiatan_sales (id_kegiatan_sales, id_sales, nama_sales, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmtTeam->bind_param("iis", $kegiatanId, $id_sales, $namaSales);
        $stmtTeam->execute();
        $stmtTeam->close();
    }

    echo "<div class='alert alert-success'>Kegiatan berhasil ditambahkan!</div>";
}

?>

<form method="POST" class="p-4">
  <div class="mb-3">
    <label for="jadwal" class="form-label">Jadwal Visit</label>
    <input type="datetime-local" class="form-control border p-2" name="jadwal" required>
  </div>

  <div class="mb-3">
    <label for="visit" class="form-label">Keterangan Visit</label>
    <textarea class="form-control border p-2" name="visit" rows="3" required></textarea>
  </div>

  <div class="mb-3">
    <label for="id_customer" class="form-label">Customer</label>
    <select class="form-select border p-2" name="id_customer" required>
      <option value="">-- Pilih Customer --</option>
      <?php while ($c = mysqli_fetch_assoc($customerResult)): ?>
        <option value="<?php echo $c['id']; ?>"><?php echo $c['nama']; ?></option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Pilih Sales</label>
    <div class="row">
      <?php while ($s = mysqli_fetch_assoc($salesResult)): ?>
        <div class="col-md-4 mb-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sales[]" value="<?php echo $s['id']; ?>" id="sales<?php echo $s['id']; ?>">
            <label class="form-check-label" for="sales<?php echo $s['id']; ?>">
              <?php echo $s['nama']; ?>
            </label>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Simpan Kegiatan</button>
</form>


  </div>



  <?php
  include "floating-menu.php";
  include "footer.php";
  ?>
</div>

    <?php
    // }
    ?>


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




</body>

</html>