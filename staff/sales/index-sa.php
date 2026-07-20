<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

// Auto migration: Add Sales App clock-in/out and new sales fields to pelaksanaan_sales
$salesMigrations = [
    "ci_at"        => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `ci_at` DATETIME NULL DEFAULT NULL COMMENT 'Waktu Clock In Sales'",
    "co_at"        => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `co_at` DATETIME NULL DEFAULT NULL COMMENT 'Waktu Clock Out Sales'",
    "lat_ci"       => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `lat_ci` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Latitude saat Clock In'",
    "lon_ci"       => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `lon_ci` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Longitude saat Clock In'",
    "lat_co"       => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `lat_co` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Latitude saat Clock Out'",
    "lon_co"       => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `lon_co` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Longitude saat Clock Out'",
    "catatan_visit"=> "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `catatan_visit` TEXT NULL DEFAULT NULL COMMENT 'Catatan hasil kunjungan'",
    "nama_client"  => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `nama_client` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nama Client / Kontak Kunjungan'",
    "nomer_client" => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `nomer_client` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Nomor Telepon Client / Kontak'",
    "tipe_prospek" => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `tipe_prospek` VARCHAR(30) NULL DEFAULT 'Biasa' COMMENT 'Kategori prospek customer'",
    "no_invoice"   => "ALTER TABLE `pelaksanaan_sales` ADD COLUMN `no_invoice` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nomor invoice opsional jika ada transaksi'",
];
foreach ($salesMigrations as $col => $sql) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM `pelaksanaan_sales` LIKE '$col'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, $sql);
    }
}

// Auto migration: Add rescheduled columns to kegiatan_sales table
$checkReschedFrom = mysqli_query($conn, "SHOW COLUMNS FROM `kegiatan_sales` LIKE 'rescheduled_from'");
if ($checkReschedFrom && mysqli_num_rows($checkReschedFrom) == 0) {
    mysqli_query($conn, "ALTER TABLE `kegiatan_sales` ADD COLUMN `rescheduled_from` INT NULL DEFAULT NULL COMMENT 'Reference ke ID kegiatan lama yang di-reschedule'");
}
$checkReschedReason = mysqli_query($conn, "SHOW COLUMNS FROM `kegiatan_sales` LIKE 'reschedule_reason'");
if ($checkReschedReason && mysqli_num_rows($checkReschedReason) == 0) {
    mysqli_query($conn, "ALTER TABLE `kegiatan_sales` ADD COLUMN `reschedule_reason` TEXT NULL DEFAULT NULL COMMENT 'Alasan reschedule'");
}

include_once "../menu-access-helper.php";
if (!hasMenuAccess($conn, $idSesi, 'dashboard_sales', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager' || $role == 'Sales'))) {
    if (hasMenuAccess($conn, $idSesi, 'dashboard', ($role == 'Super Admin' || $role == 'Admin'))) {
        header("Location: ../index-sa.php");
        exit;
    }
}

$pageNow = "Dashboard Sales";
$currentPage = "Today";

// Greeting berdasarkan jam
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$todayFormatted = date('d F Y');

// Simpan/baca pilihan filter dari session/query
if (isset($_GET['wilayah'])) {
    $_SESSION['selected_wilayah'] = $_GET['wilayah'] === 'all' ? 'all' : intval($_GET['wilayah']);
}
$selectedWilayah = $_SESSION['selected_wilayah'] ?? 'all';

if (isset($_GET['sales'])) {
    $_SESSION['selected_sales'] = $_GET['sales'] === 'all' ? 'all' : intval($_GET['sales']);
}
$selectedSales = $_SESSION['selected_sales'] ?? 'all';

if (isset($_GET['customer'])) {
    $_SESSION['search_customer'] = trim($_GET['customer']);
}
$searchCustomer = $_SESSION['search_customer'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    .dashboard-header {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-left: 5px solid #2563eb !important;
      border-radius: 16px;
      padding: 20px 24px;
      color: #0f172a;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 18px rgba(148, 163, 184, 0.05);
    }
    .dashboard-header .greeting { font-size: 12.5px; color: #64748b; font-weight: 600; margin-bottom: 4px; }
    .dashboard-header .user-name { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
    .dashboard-header .today-date { font-size: 12px; color: #64748b; font-weight: 500; }
    .dashboard-header .header-icon {
      position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
      font-size: 64px; color: #2563eb; opacity: .08;
    }
  </style>
</head>
 
<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
 
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php include "nav-top.php"; ?>
 
    <div class="container-fluid py-4">
 
      <!-- ── Dashboard Header ───────────────────────────────────────── -->
      <div class="row mb-2">
        <div class="col-12">
          <div class="dashboard-header">
            <div class="greeting"><?php echo $greeting; ?>, 👋</div>
            <div class="user-name"><?php echo htmlspecialchars($nmUser ?? 'Admin'); ?></div>
            <div class="today-date">📅 <?php echo $todayFormatted; ?></div>
            <span class="material-symbols-outlined header-icon">bar_chart</span>
          </div>
        </div>
      </div>

      <!-- ── Filter Card ───────────────────────────────────────────── -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
            <form method="GET" action="index-sa.php" class="row g-3 align-items-end">
              <!-- Filter Wilayah -->
              <div class="col-12 col-md-3">
                <label class="form-label font-weight-bold text-dark text-xs text-uppercase mb-2 d-flex align-items-center gap-1" style="letter-spacing: 0.05em; margin-bottom: 0.5rem !important;">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #3b82f6;">map</span>
                  Filter Wilayah
                </label>
                <select name="wilayah" class="form-select border p-2 text-sm" style="border-radius: 8px; font-weight: 600; outline: none; background: #fff;">
                  <option value="all" <?php echo $selectedWilayah === 'all' ? 'selected' : ''; ?>>Semua Wilayah</option>
                  <?php
                  $wQuery = mysqli_query($conn, "SELECT * FROM wilayah WHERE deleted_at IS NULL ORDER BY nama ASC");
                  while ($w = mysqli_fetch_assoc($wQuery)) {
                      $selected = ($selectedWilayah !== 'all' && (int)$selectedWilayah === (int)$w['id']) ? 'selected' : '';
                      echo "<option value='{$w['id']}' $selected>" . htmlspecialchars($w['nama']) . "</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- Filter Sales -->
              <div class="col-12 col-md-3">
                <label class="form-label font-weight-bold text-dark text-xs text-uppercase mb-2 d-flex align-items-center gap-1" style="letter-spacing: 0.05em; margin-bottom: 0.5rem !important;">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #3b82f6;">person</span>
                  Filter Sales
                </label>
                <select name="sales" class="form-select border p-2 text-sm" style="border-radius: 8px; font-weight: 600; outline: none; background: #fff;">
                  <option value="all" <?php echo $selectedSales === 'all' ? 'selected' : ''; ?>>Semua Sales</option>
                  <?php
                  $sQuery = mysqli_query($conn, "SELECT * FROM sales WHERE deleted_at IS NULL ORDER BY nama ASC");
                  while ($s = mysqli_fetch_assoc($sQuery)) {
                      $selected = ($selectedSales !== 'all' && (int)$selectedSales === (int)$s['id']) ? 'selected' : '';
                      echo "<option value='{$s['id']}' $selected>" . htmlspecialchars($s['nama']) . "</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- Cari Customer -->
              <div class="col-12 col-md-3">
                <label class="form-label font-weight-bold text-dark text-xs text-uppercase mb-2 d-flex align-items-center gap-1" style="letter-spacing: 0.05em; margin-bottom: 0.5rem !important;">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #3b82f6;">search</span>
                  Nama Customer
                </label>
                <input type="text" name="customer" class="form-control border p-2 text-sm" style="border-radius: 8px; height: 38px;" placeholder="Ketik nama customer..." value="<?php echo htmlspecialchars($searchCustomer); ?>">
              </div>

              <!-- Tombol Aksi -->
              <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 mb-0 py-2 d-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; background: #3b82f6; border: none; font-weight: 700; height: 38px;">
                  <span class="material-symbols-outlined" style="font-size: 16px;">search</span>
                  Cari
                </button>
                <a href="index-sa.php?wilayah=all&sales=all&customer=" class="btn btn-outline-secondary mb-0 py-2 d-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; font-weight: 700; height: 38px; border: 1.5px solid #cbd5e1; color: #475569;">
                  <span class="material-symbols-outlined" style="font-size: 16px;">restart_alt</span>
                  Reset
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Chart Data Fetching (PHP) ──────────────────────────────── -->
      <?php
      // 1. Data Trend 7 Hari Terakhir
      $trendLabels = [];
      $trendValues = [];
      for ($i = 6; $i >= 0; $i--) {
          $dateStr = date('Y-m-d', strtotime("-$i days"));
          $displayStr = date('d M', strtotime($dateStr));
          
          $queryTrend = "SELECT COUNT(DISTINCT ks.id) AS c FROM kegiatan_sales ks
                         LEFT JOIN sales_customer c ON ks.id_customer = c.id ";
          if ($selectedSales !== 'all') {
              $queryTrend .= "INNER JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales ";
          } else {
              $queryTrend .= "LEFT JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales ";
          }
          
          $queryTrend .= "WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND DATE(ks.jadwal) = '$dateStr' ";
          
          if ($selectedWilayah !== 'all') {
              $queryTrend .= "AND c.id_wilayah = '$selectedWilayah' ";
          }
          if ($selectedSales !== 'all') {
              $queryTrend .= "AND tks.id_sales = '$selectedSales' AND tks.deleted_at IS NULL ";
          }
          if (!empty($searchCustomer)) {
              $safeSearch = mysqli_real_escape_string($conn, $searchCustomer);
              $queryTrend .= "AND c.nama LIKE '%$safeSearch%' ";
          }
          
          $resTrend = mysqli_query($conn, $queryTrend);
          $count = ($resTrend && ($rRow = mysqli_fetch_assoc($resTrend))) ? (int)$rRow['c'] : 0;
          
          $trendLabels[] = $displayStr;
          $trendValues[] = $count;
      }

      // 2. Data Performa Sales
      $salesLabels = [];
      $salesValues = [];

      $querySalesPerformance = "SELECT s.nama AS nama_sales, COUNT(DISTINCT ks.id) AS c
                                FROM kegiatan_sales ks
                                JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales
                                JOIN sales s ON tks.id_sales = s.id
                                LEFT JOIN sales_customer c ON ks.id_customer = c.id
                                WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND tks.deleted_at IS NULL ";

      if ($selectedWilayah !== 'all') {
          $querySalesPerformance .= "AND c.id_wilayah = '$selectedWilayah' ";
      }
      if ($selectedSales !== 'all') {
          $querySalesPerformance .= "AND tks.id_sales = '$selectedSales' ";
      }
      if (!empty($searchCustomer)) {
          $safeSearch = mysqli_real_escape_string($conn, $searchCustomer);
          $querySalesPerformance .= "AND c.nama LIKE '%$safeSearch%' ";
      }

      $querySalesPerformance .= "GROUP BY s.id, s.nama ORDER BY c DESC LIMIT 10";
      $resSalesPerformance = mysqli_query($conn, $querySalesPerformance);

      while ($sp = mysqli_fetch_assoc($resSalesPerformance)) {
          $salesLabels[] = $sp['nama_sales'];
          $salesValues[] = (int)$sp['c'];
      }

      // Fallback jika tidak ada data sales agar grafik tidak kosong melompong
      if (empty($salesLabels)) {
          $salesLabels = ['Belum ada data'];
          $salesValues = [0];
      }
      ?>

      <!-- ── Chart Section ─────────────────────────────────────────── -->
      <div class="row mb-4">
        <!-- Chart 1: Trend Kunjungan -->
        <div class="col-12 col-lg-7 mb-4 mb-lg-0">
          <div class="card p-3 shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background: #fff; height: 350px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="text-xs font-weight-bold text-uppercase text-secondary mb-0" style="letter-spacing: 0.05em; font-family: 'Open Sans', sans-serif;">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #3b82f6; vertical-align: middle; margin-right: 4px;">trending_up</span>
                Trend Kunjungan (7 Hari Terakhir)
              </h6>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="trendChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Chart 2: Performa Sales -->
        <div class="col-12 col-lg-5">
          <div class="card p-3 shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background: #fff; height: 350px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="text-xs font-weight-bold text-uppercase text-secondary mb-0" style="letter-spacing: 0.05em; font-family: 'Open Sans', sans-serif;">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #10b981; vertical-align: middle; margin-right: 4px;">leaderboard</span>
                Performa Kunjungan Sales
              </h6>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Kegiatan Tabs + Cards ──────────────────────────────────── -->
      <div class="row g-3">
        <?php include "kegiatan-db.php"; ?>
      </div>

      <?php include "floating-menu.php"; ?>
      <?php include "footer.php"; ?>
    </div>
  </main>

  <?php include "js-include.php"; ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), {damping:'0.5'});
    }
  </script>

  <!-- ── Chart.js Script CDN & Initialization ────────────────────── -->
  <script src="assets/js/plugins/chartjs.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // 1. Line Glow Shadow Plugin
      const shadowPlugin = {
        id: 'shadowPlugin',
        beforeDatasetDraw: (chart, args) => {
          const { ctx } = chart;
          ctx.save();
          ctx.shadowColor = 'rgba(6, 182, 212, 0.4)';
          ctx.shadowBlur = 12;
          ctx.shadowOffsetX = 0;
          ctx.shadowOffsetY = 6;
        },
        afterDatasetDraw: (chart, args) => {
          const { ctx } = chart;
          ctx.restore();
        }
      };

      // 1. Trend Chart (Line Chart)
      const ctxTrend = document.getElementById('trendChart').getContext('2d');
      
      // Line Stroke Gradient (Indigo to Cyan)
      const gradientStroke = ctxTrend.createLinearGradient(0, 0, ctxTrend.canvas.offsetWidth || 500, 0);
      gradientStroke.addColorStop(0, '#4f46e5');
      gradientStroke.addColorStop(0.5, '#3b82f6');
      gradientStroke.addColorStop(1, '#06b6d4');

      // Line Fill Gradient
      const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 200);
      gradientTrend.addColorStop(0, 'rgba(6, 182, 212, 0.35)');
      gradientTrend.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

      new Chart(ctxTrend, {
        type: 'line',
        plugins: [shadowPlugin],
        data: {
          labels: <?php echo json_encode($trendLabels); ?>,
          datasets: [{
            label: 'Jumlah Kunjungan',
            data: <?php echo json_encode($trendValues); ?>,
            borderColor: gradientStroke,
            borderWidth: 4,
            backgroundColor: gradientTrend,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#06b6d4',
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointHoverBackgroundColor: '#06b6d4',
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e1b4b',
              titleColor: '#ffffff',
              bodyColor: '#38bdf8',
              cornerRadius: 10,
              padding: 12,
              displayColors: false,
              titleFont: { size: 12, weight: 'bold' },
              bodyFont: { size: 12 }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: '#64748b', font: { size: 10, weight: 'bold' } }
            },
            y: {
              grid: { color: '#f1f5f9' },
              ticks: { color: '#64748b', stepSize: 1, precision: 0, font: { size: 10 } }
            }
          }
        }
      });

      // 2. Sales Performance Chart (Bar Chart)
      const ctxSales = document.getElementById('salesChart').getContext('2d');
      
      // Bar Color Gradient (Violet to Pink)
      const gradientSales = ctxSales.createLinearGradient(0, 0, 0, 250);
      gradientSales.addColorStop(0, '#8b5cf6');
      gradientSales.addColorStop(1, '#db2777');

      new Chart(ctxSales, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($salesLabels); ?>,
          datasets: [{
            label: 'Kunjungan',
            data: <?php echo json_encode($salesValues); ?>,
            backgroundColor: gradientSales,
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 20,
            hoverBackgroundColor: '#db2777',
            hoverBorderColor: '#8b5cf6',
            hoverBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e1b4b',
              titleColor: '#ffffff',
              bodyColor: '#f472b6',
              cornerRadius: 10,
              padding: 12,
              displayColors: false,
              titleFont: { size: 12, weight: 'bold' },
              bodyFont: { size: 12 }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: '#64748b', font: { size: 10, weight: 'bold' } }
            },
            y: {
              grid: { color: '#f1f5f9' },
              ticks: { color: '#64748b', stepSize: 1, precision: 0, font: { size: 10 } }
            }
          }
        }
      });
    });
  </script>
</body>
</html>