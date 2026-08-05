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

// Auto-fix: Ensure old rescheduled tasks referenced in rescheduled_from are marked status = 'dibatalkan'
mysqli_query($conn, "UPDATE kegiatan_sales SET status = 'dibatalkan' WHERE id IN (SELECT rescheduled_from FROM (SELECT DISTINCT rescheduled_from FROM kegiatan_sales WHERE rescheduled_from IS NOT NULL AND deleted_at IS NULL) AS t) AND status != 'dibatalkan'");


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
 
// Calculate Today's Progress for Greeting Header
$current_date_today = date("Y-m-d");
$today_total = 0;
$today_completed = 0;
 
$qTotal = "SELECT COUNT(DISTINCT ks.id) AS c FROM kegiatan_sales ks 
           LEFT JOIN sales_customer c ON ks.id_customer = c.id ";
if ($selectedSales !== 'all') {
    $qTotal .= "INNER JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales ";
}
$qTotal .= "WHERE ks.deleted_at IS NULL AND DATE(ks.jadwal) = '$current_date_today' AND ks.status NOT IN ('waiting', 'dibatalkan', 'reschedule', 'cancelled') AND (ks.reschedule_reason IS NULL OR ks.reschedule_reason = '') AND ks.id NOT IN (SELECT DISTINCT rescheduled_from FROM kegiatan_sales WHERE rescheduled_from IS NOT NULL AND deleted_at IS NULL) ";
if ($selectedWilayah !== 'all') {
    $qTotal .= "AND c.id_wilayah = '$selectedWilayah' ";
}
if ($selectedSales !== 'all') {
    $qTotal .= "AND tks.id_sales = '$selectedSales' AND tks.deleted_at IS NULL ";
}
if (!empty($searchCustomer)) {
    $safeSearch = mysqli_real_escape_string($conn, $searchCustomer);
    $qTotal .= "AND c.nama LIKE '%$safeSearch%' ";
}
 
$resTotal = mysqli_query($conn, $qTotal);
if ($resTotal && $rowT = mysqli_fetch_assoc($resTotal)) {
    $today_total = (int)$rowT['c'];
}
 
$qComp = "SELECT COUNT(DISTINCT ks.id) AS c FROM kegiatan_sales ks 
          LEFT JOIN sales_customer c ON ks.id_customer = c.id ";
if ($selectedSales !== 'all') {
    $qComp .= "INNER JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales ";
}
$qComp .= "WHERE ks.deleted_at IS NULL AND DATE(ks.jadwal) = '$current_date_today' AND ks.status = 'selesai' ";
if ($selectedWilayah !== 'all') {
    $qComp .= "AND c.id_wilayah = '$selectedWilayah' ";
}
if ($selectedSales !== 'all') {
    $qComp .= "AND tks.id_sales = '$selectedSales' AND tks.deleted_at IS NULL ";
}
if (!empty($searchCustomer)) {
    $safeSearch = mysqli_real_escape_string($conn, $searchCustomer);
    $qComp .= "AND c.nama LIKE '%$safeSearch%' ";
}
$resComp = mysqli_query($conn, $qComp);
if ($resComp && $rowC = mysqli_fetch_assoc($resComp)) {
    $today_completed = (int)$rowC['c'];
}
 
$progress_percent = $today_total > 0 ? round(($today_completed / $today_total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    .dashboard-header {
      position: relative;
      background: linear-gradient(135deg, #ffffff 0%, #fcfdff 100%);
      border: 1px solid #e2e8f0;
      border-left: 6px solid #4f46e5 !important;
      border-radius: 20px;
      padding: 24px 30px;
      color: #0f172a;
      margin-bottom: 24px;
      overflow: hidden;
      box-shadow: 0 10px 25px -5px rgba(148, 163, 184, 0.05), 0 8px 16px -6px rgba(148, 163, 184, 0.02);
    }
    .header-glow-1 {
      position: absolute;
      top: -50px; right: 10%;
      width: 180px; height: 180px;
      background: rgba(59, 130, 246, 0.08);
      border-radius: 50%;
      filter: blur(60px);
      pointer-events: none;
      z-index: 1;
    }
    .header-glow-2 {
      position: absolute;
      bottom: -60px; right: 25%;
      width: 160px; height: 160px;
      background: rgba(99, 102, 241, 0.06);
      border-radius: 50%;
      filter: blur(50px);
      pointer-events: none;
      z-index: 1;
    }
    .dashboard-header .greeting { font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.02em; position: relative; z-index: 2; }
    .dashboard-header .user-name { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -0.02em; position: relative; z-index: 2; }
    .dashboard-header .today-date { font-size: 12.5px; color: #64748b; font-weight: 500; position: relative; z-index: 2; }
    .dashboard-header .header-icon {
      position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
      font-size: 64px; color: #2563eb; opacity: .08;
    }
    .live-indicator-dot {
      width: 6px;
      height: 6px;
      background-color: #10b981;
      border-radius: 50%;
      display: inline-block;
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
      animation: pulse-live 1.6s infinite;
      flex-shrink: 0;
    }
    @keyframes pulse-live {
      0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
      }
      70% {
        transform: scale(1);
        box-shadow: 0 0 0 5px rgba(16, 185, 129, 0);
      }
      100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
      }
    }
    .premium-action-btn {
      position: relative;
      overflow: hidden;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .premium-action-btn::after {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 60%; height: 100%;
      background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.25) 50%,
        rgba(255, 255, 255, 0) 100%
      );
      transform: skewX(-25deg);
      animation: shine-sweep 4s infinite;
    }
    @keyframes shine-sweep {
      0% { left: -100%; }
      20% { left: 100%; }
      100% { left: 100%; }
    }
    .premium-action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(79, 70, 229, 0.28) !important;
      opacity: 0.95;
    }
    .premium-action-btn:hover .material-symbols-outlined {
      transform: rotate(90deg);
    }
    .premium-action-btn .material-symbols-outlined {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Liquid wave progress bar */
    .progress-bar-glow-wave {
      background: linear-gradient(90deg, #10b981, #34d399, #10b981) !important;
      background-size: 200% auto !important;
      animation: progress-wave 2s linear infinite !important;
    }
    @keyframes progress-wave {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    /* Pulsing success badge */
    .pulse-success-badge {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
      animation: pulse-badge 2s infinite;
    }
    @keyframes pulse-badge {
      0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
      }
      70% {
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
      }
    }
    
    /* Premium Styled Progress Capsule */
    .progress-capsule-premium {
      border: 1px solid #ccfbf1 !important;
      min-width: 270px;
      background: linear-gradient(135deg, rgba(240, 253, 250, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
      backdrop-filter: blur(10px);
      height: 52px;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.08) !important;
      padding: 10px 16px 10px 20px !important;
      border-radius: 14px !important;
      display: inline-flex;
      align-items: center;
      gap: 12px;
    }
    .progress-label-text {
      font-size: 10px !important;
      font-weight: 800 !important;
      text-uppercase: uppercase;
      color: #0d9488 !important;
      letter-spacing: 0.06em !important;
      line-height: 1;
    }
    .progress-value-text {
      font-size: 12.5px !important;
      font-weight: 800 !important;
      color: #0f766e !important;
      letter-spacing: -0.01em;
      line-height: 1.2;
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
          <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- Ambient Glow Backgrounds -->
            <div class="header-glow-1"></div>
            <div class="header-glow-2"></div>
            
            <div class="d-flex align-items-center gap-3" style="position: relative; z-index: 2;">
              <div>
                <div class="greeting"><?php echo $greeting; ?>, 👋</div>
                <div class="user-name"><?php echo htmlspecialchars($nmUser ?? 'Admin'); ?></div>
                <div class="today-date d-flex align-items-center mt-1">
                  <span id="live-clock-header">📅 <?php echo $todayFormatted; ?> <span style="font-size: 10px; font-weight: 700; color: #10b981; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 20px; margin-left: 8px; vertical-align: middle; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.04);"><span class="live-indicator-dot" style="margin-right: 0;"></span> LIVE</span></span>
                </div>
              </div>
            </div>
            
            <div class="d-flex align-items-center gap-3 mt-2 mt-md-0 flex-wrap" style="position: relative; z-index: 2;">
                <!-- Progress Bar -->
                <div class="progress-capsule-premium">
                    <div class="d-flex flex-column flex-grow-1" style="padding-left: 4px;">
                        <span class="progress-label-text">
                            Progress Hari Ini
                            <span class="live-indicator-dot" style="width: 5px; height: 5px; background-color: #10b981; margin-bottom: 1px; margin-left: 3px;"></span>
                        </span>
                        <h6 class="mb-0 progress-value-text mt-0.5"><?php echo $today_completed; ?> / <?php echo $today_total; ?> Selesai</h6>
                        <div class="progress mt-1.5" style="height: 5px; background-color: #e6f4f1; border-radius: 10px; overflow: hidden; width: 100%;">
                            <div class="progress-bar progress-bar-glow-wave" role="progressbar" style="width: <?php echo $progress_percent; ?>%; border-radius: 10px;" aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center text-white rounded-3 shadow-sm pulse-success-badge" style="width: 34px; height: 34px; flex-shrink: 0; background: linear-gradient(135deg, #0d9488, #0f766e);">
                        <span class="material-symbols-outlined" style="font-size: 18px;">done_all</span>
                    </div>
                </div>

                <!-- Tambah Kegiatan Button -->
                <a href="kegiatan-baru.php" class="btn btn-sm btn-primary mb-0 d-flex align-items-center justify-content-center gap-2 shadow-sm premium-action-btn" style="border-radius: 12px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); border: none; font-weight: 700; height: 52px; padding: 0 20px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">add_circle</span>
                    Tambah Kegiatan
                </a>
            </div>
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
 
      // 3. Live Ticking Clock (Indonesian Date and Time)
      function updateLiveClock() {
        const clockEl = document.getElementById('live-clock-header');
        if (!clockEl) return;
        
        const now = new Date();
        
        // Days and Months in Indonesian
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const dayName = days[now.getDay()];
        
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const monthName = months[now.getMonth()];
        
        const date = now.getDate().toString().padStart(2, '0');
        const year = now.getFullYear();
        
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        
        clockEl.innerHTML = `${dayName}, ${date} ${monthName} ${year} • ${hours}:${minutes}:${seconds} <span style="font-size: 9px; font-weight: bold; color: #10b981; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 1px 6px; border-radius: 6px; margin-left: 6px; vertical-align: middle; display: inline-block;">LIVE</span>`;
      }
      updateLiveClock();
      setInterval(updateLiveClock, 1000);
    });
  </script>
</body>
</html>