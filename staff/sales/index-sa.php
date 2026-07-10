<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

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
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      border-radius: 16px;
      padding: 24px 28px;
      color: #fff;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }
    .dashboard-header::before {
      content: '';
      position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
      pointer-events: none;
    }
    .dashboard-header .greeting { font-size: 13px; opacity: .7; margin-bottom: 4px; }
    .dashboard-header .user-name { font-size: 22px; font-weight: 700; margin-bottom: 2px; }
    .dashboard-header .today-date { font-size: 12px; opacity: .6; }
    .dashboard-header .header-icon {
      position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
      font-size: 72px; opacity: .08;
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
                <button type="submit" class="btn btn-primary w-100 mb-0 py-2 d-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; background: #1e3a8a; border: none; font-weight: 700; height: 38px;">
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

      <!-- ── Kegiatan Tabs + Cards ──────────────────────────────────── -->
      <div class="row g-3">
        <?php include "kegiatan-db.php"; ?>
      </div>

      <?php include "floating-menu.php"; ?>
      <?php include "footer.php"; ?>
    </div>
  </main>

  <?php include "js-include.php"; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), {damping:'0.5'});
    }
  </script>
</body>
</html>