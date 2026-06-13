<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Teknisi";
$currentPage = "Today";
$role = $_SESSION['jabatan'];

$idTeknis = isset($_GET['idTek']) ? $_GET['idTek'] : null; // Ensure $idTeknis is set

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
        #toggleLoadMore {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
    @media print {
    .no-print {
        display: none;
    }
}

    /* ═══ PREMIUM INDIVIDUAL KPI DASHBOARD ═══ */
    .kpi-row {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 24px;
      margin-top: 10px;
    }
    .kpi-col {
      flex: 1;
      min-width: 220px;
    }
    .kpi-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
      transition: all 0.2s ease-in-out;
      height: 100%;
    }
    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
    }
    .kpi-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .kpi-content {
      display: flex;
      flex-direction: column;
      min-width: 0;
      flex-grow: 1;
    }
    .kpi-label {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin: 0;
    }
    .kpi-value {
      font-size: 18px;
      font-weight: 800;
      color: #1e293b;
      line-height: 1.2;
      margin-top: 2px;
    }
    .kpi-sub-label {
      font-size: 10px;
      font-weight: 600;
      color: #64748b;
      margin-top: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .kpi-blue .kpi-icon { background: #eef2ff; color: #6366f1; }
    .kpi-orange .kpi-icon { background: #fffbeb; color: #d97706; }
    .kpi-green .kpi-icon { background: #f0fdf4; color: #16a34a; }
    .kpi-purple .kpi-icon { background: #faf5ff; color: #a855f7; }
    
    /* Profile Summary Header */
    .tek-profile-header {
      background: linear-gradient(135deg, #1e293b, #334155);
      border-radius: 16px;
      padding: 20px 24px;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 20px;
    }
    .tek-profile-left {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .tek-avatar {
      width: 48px;
      height: 48px;
      background: rgba(255,255,255,0.1);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      border: 1.5px solid rgba(255,255,255,0.2);
    }
    .tek-profile-info h4 {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      color: #fff;
    }
    .tek-profile-info p {
      margin: 2px 0 0;
      font-size: 12px;
      color: #cbd5e1;
      font-weight: 500;
    }
    .tek-profile-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .tek-badge-status {
      font-size: 11px;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
    }
    .tek-badge-status.above { background: #dcfce7; color: #16a34a; }
    .tek-badge-status.below { background: #fef3c7; color: #b45309; }


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

      <div class="row mb-4 mt-0">
        <div class="col-md-4 col-12 d-flex justify-content-start align-items-center">
            <!--<button class="btn bg-gradient-info w-30 btn-print">Print</button>-->
        </div>

          <div class="col-8">
              
          </div>
        
        <?php
        include "list-kegiatan-teknisi-db.php";
        ?>

      </div>
      <?php
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
  
  <script>
    // Fungsi untuk mencetak konten
    function printContent() {
        var content = document.getElementById("printable-content").innerHTML;
        var originalBody = document.body.innerHTML;
        document.body.innerHTML = content;
        window.print();
        document.body.innerHTML = originalBody;
    }

    // Menambahkan event listener untuk tombol "Print"
    document.querySelector(".btn-print").addEventListener("click", printContent);
</script>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
</body>

</html>