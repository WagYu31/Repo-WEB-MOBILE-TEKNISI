<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Today";

if (!isset($_GET['id'])) {
    echo "ID Kegiatan tidak ditemukan.";
    exit;
}

$idKegiatan = $_GET['id'];

// Ambil data kegiatan
$sqlKegiatan = "SELECT ks.*, c.nama AS nama_customer, c.telp_pribadi, c.alamat 
                FROM kegiatan_sales ks
                LEFT JOIN sales_customer c ON ks.id_customer = c.id
                WHERE ks.id = '$idKegiatan' AND ks.deleted_at IS NULL";

$resultKegiatan = mysqli_query($conn, $sqlKegiatan);
$data = mysqli_fetch_assoc($resultKegiatan);

// Ambil tim sales
$sqlSales = "SELECT s.nama AS nama_sales, ps.status, ps.keterangan, ps.image_1, ps.image_2, ps.image_3, ps.record 
             FROM team_kegiatan_sales tks
             LEFT JOIN sales s ON tks.id_sales = s.id
             LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
             WHERE tks.id_kegiatan_sales = '$idKegiatan' AND tks.deleted_at IS NULL";

$resultSales = mysqli_query($conn, $sqlSales);
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
  <h3 class="mb-4">Detail Kegiatan</h3>

  <div class="card p-4 mb-4 shadow-sm">
    <div class="row">
      <!--<div class="col-md-6 mb-3">-->
      <!--  <strong>Kode:</strong> <br><?php echo $data['kode']; ?>-->
      <!--</div>-->
      <!--<div class="col-md-6 mb-3">-->
      <!--  <strong>Status:</strong> <br><span class="badge bg-primary"><?php echo ucfirst($data['status']); ?></span>-->
      <!--</div>-->
      <div class="col-md-6 mb-3">
        <strong>Customer:</strong> <br><?php echo $data['nama_customer']; ?>
      </div>
      <div class="col-md-6 mb-3">
        <strong>Telepon:</strong> <br><?php echo $data['telp_pribadi']; ?>
      </div>
      <div class="col-md-6 mb-3">
        <strong>Alamat:</strong> <br><?php echo $data['alamat']; ?>
      </div>
      <div class="col-md-6 mb-3">
        <strong>Jadwal:</strong> <br><?php echo date('d/m/Y H:i', strtotime($data['jadwal'])); ?>
      </div>
    </div>
  </div>

  <h5 class="mb-3">Tim Sales & Laporan</h5>

  <?php if (mysqli_num_rows($resultSales) > 0): ?>
  <div class="row">
    <?php while ($row = mysqli_fetch_assoc($resultSales)): ?>
      <div class="col-md-6 mb-4">
        <div class="card p-4 h-100 shadow-sm border-start">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><?php echo $row['nama_sales']; ?></h6>
            <span class="badge bg-<?php echo $row['status'] == 'Selesai' ? 'success' : ($row['status'] == 'Berjalan' ? 'info' : 'secondary'); ?>">
              <?php echo $row['status'] ?? 'Dijadwalkan'; ?>
            </span>
          </div>

          <?php if (!empty($row['keterangan'])): ?>
            <p class="mb-2">
              <strong>Keterangan:</strong> <?php echo $row['keterangan']; ?>
            </p>
          <?php endif; ?>

          <?php if ($row['image_1'] || $row['image_2'] || $row['image_3']): ?>
            <div class="row mb-3">
              <?php foreach (['image_1', 'image_2', 'image_3'] as $img): ?>
                <?php if (!empty($row[$img])): ?>
                  <div class="col-4 mb-2">
                    <img src="https://grav-tech.com/jadwal-3/api/storage/app/image/<?php echo $row[$img]; ?>" class="img-fluid rounded border" alt="Dokumentasi">
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($row['record'])): ?>
            <p class="mb-1"><strong>Rekaman:</strong></p>
            <audio controls class="w-100">
              <source src="https://grav-tech.com/jadwal-3/api/storage/app/record/<?php echo $row['record']; ?>" type="audio/mpeg">
              <source src="https://grav-tech.com/jadwal-3/api/storage/app/record/<?php echo $row['record']; ?>" type="audio/aac">
              <source src="https://grav-tech.com/jadwal-3/api/storage/app/record/<?php echo $row['record']; ?>" type="audio/x-aac">
              <source src="https://grav-tech.com/jadwal-3/api/storage/app/record/<?php echo $row['record']; ?>" type="audio/mp4">
              Browser Anda tidak mendukung elemen audio.
            </audio>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <p class="text-muted">Belum ada sales terdaftar untuk kegiatan ini.</p>
<?php endif; ?>


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