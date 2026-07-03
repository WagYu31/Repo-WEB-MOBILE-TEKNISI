<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard Sales";
$currentPage = "Today";

// Greeting berdasarkan jam
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$todayFormatted = date('d F Y');

// Simpan/baca pilihan wilayah dari session/query
if (isset($_GET['wilayah'])) {
    $_SESSION['selected_wilayah'] = $_GET['wilayah'] === 'all' ? 'all' : intval($_GET['wilayah']);
}
$selectedWilayah = $_SESSION['selected_wilayah'] ?? 'all';
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

      <!-- ── Region Selector Dropdown ───────────────────────────────── -->
      <div class="row mb-4">
        <div class="col-12 col-md-4">
          <div class="card p-3 shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
            <label class="form-label font-weight-bold text-dark text-xs text-uppercase mb-2 d-flex align-items-center gap-1" style="letter-spacing: 0.05em;">
              <span class="material-symbols-outlined" style="font-size: 16px; color: #3b82f6;">map</span>
              Filter Wilayah Operasional
            </label>
            <select class="form-select border p-2 text-sm" style="border-radius: 8px; font-weight: 600; outline: none; background: #fff;" onchange="window.location.href='index-sa.php?wilayah=' + this.value">
              <option value="all" <?php echo $selectedWilayah === 'all' ? 'selected' : ''; ?>>Tampilkan Semua Wilayah</option>
              <?php
              $wQuery = mysqli_query($conn, "SELECT * FROM wilayah WHERE deleted_at IS NULL ORDER BY nama ASC");
              while ($w = mysqli_fetch_assoc($wQuery)) {
                  $selected = ($selectedWilayah !== 'all' && (int)$selectedWilayah === (int)$w['id']) ? 'selected' : '';
                  echo "<option value='{$w['id']}' $selected>" . htmlspecialchars($w['nama']) . "</option>";
              }
              ?>
            </select>
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