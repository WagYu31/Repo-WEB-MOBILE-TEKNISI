<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Kegiatan Baru";
$currentPage = "Today";

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <style>
    /* ── Premium Form Card Styling ── */
    .card-premium {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.02);
      margin-bottom: 24px;
    }
    
    .section-header-premium {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      background: #1e293b;
      color: #fff;
    }
    
    .section-header-premium h6 {
      margin: 0;
      font-size: 14px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
    }
    
    .card-body-premium {
      padding: 32px;
    }

    /* ── Form Inputs ── */
    .form-group-premium {
      margin-bottom: 24px;
    }
    
    .form-label-premium {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .input-premium {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 12px 16px !important;
      font-size: 14px;
      color: #1e293b;
      background-color: #fff;
      transition: all 0.2s ease;
    }
    
    .input-premium:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
      outline: none;
    }

    /* ── Sales Select Card Grid ── */
    .sales-select-card {
      display: block;
      cursor: pointer;
      margin-bottom: 0;
      user-select: none;
    }
    
    .sales-card-content {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      transition: all 0.22s ease;
      position: relative;
    }
    
    .sales-card-content:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
      transform: translateY(-1px);
    }
    
    .sales-select-input:checked + .sales-card-content {
      background: #f0f7ff;
      border-color: #3b82f6;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.05);
    }
    
    .avatar-initials-small {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #f1f5f9;
      color: #64748b;
      font-size: 12px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }
    
    .sales-select-input:checked + .sales-card-content .avatar-initials-small {
      background: #3b82f6;
      color: #fff;
    }
    
    .sales-card-details {
      display: flex;
      flex-direction: column;
    }
    
    .sales-card-name {
      font-size: 13px; font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
    }
    
    .sales-card-role {
      font-size: 10px; color: #64748b;
      margin-top: 2px;
    }
    
    .sales-card-checkbox {
      margin-left: auto;
      color: #cbd5e1;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }
    
    .sales-select-input:checked + .sales-card-content .sales-card-checkbox {
      color: #3b82f6;
    }

    /* ── Submit Button ── */
    .btn-submit-premium {
      background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
      color: #fff !important;
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-size: 14px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
      transition: all 0.22s ease;
      cursor: pointer;
    }
    
    .btn-submit-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    .btn-submit-premium:active {
      transform: translateY(0);
    }
    
    .btn-submit-premium .material-symbols-outlined {
      font-size: 18px;
    }

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }
    
    <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <?php
    include "nav-top.php";
    $todayDate = formatTanggal('dd MMMM yyyy');
    ?>

    <div class="container-fluid py-4">

      <div class="card-premium">
        
        <div class="section-header-premium">
          <h6>
            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add_task</span>
            Form Tambah Kegiatan Sales Baru
          </h6>
        </div>

        <div class="card-body-premium">
          <?php
          // Ambil customer
          $customerResult = mysqli_query($conn, "SELECT id, nama FROM sales_customer WHERE deleted_at IS NULL");

          // Ambil sales
          $salesResult = mysqli_query($conn, "SELECT id, nama FROM sales WHERE deleted_at IS NULL");

          // Set timezone ke Jakarta
          date_default_timezone_set('Asia/Jakarta');

          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $jadwal = $_POST['jadwal'];
              $visit = $_POST['visit'];
              $id_customer = $_POST['id_customer'];
              $status = 'dijadwalkan';
              $selectedSales = $_POST['sales'] ?? [];

              // Insert ke kegiatan_sales
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

              echo "<div class='alert alert-success text-white font-weight-bold mb-4' style='background: #10b981; border: none; border-radius: 10px;'>
                      <span class='material-symbols-outlined' style='vertical-align: middle; margin-right: 8px;'>check_circle</span>
                      Kegiatan kunjungan sales berhasil ditambahkan!
                    </div>";
          }
          ?>

          <form method="POST">
            <div class="form-group-premium">
              <label for="jadwal" class="form-label-premium">Jadwal Visit</label>
              <input type="datetime-local" class="input-premium" name="jadwal" required>
            </div>

            <div class="form-group-premium">
              <label for="visit" class="form-label-premium">Keterangan Visit / Keperluan Kunjungan</label>
              <textarea class="input-premium" name="visit" rows="4" placeholder="Tuliskan keterangan detail rencana kunjungan sales..." required></textarea>
            </div>

            <div class="form-group-premium">
              <label for="id_customer" class="form-label-premium">Pilih Customer (Toko/Mitra)</label>
              <select class="input-premium" name="id_customer" required>
                <option value="">-- Pilih Customer --</option>
                <?php while ($c = mysqli_fetch_assoc($customerResult)): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nama']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group-premium">
              <label class="form-label-premium">Pilih Sales Agent Terlibat</label>
              <div class="row g-3">
                <?php while ($s = mysqli_fetch_assoc($salesResult)): ?>
                  <div class="col-md-4">
                    <label class="sales-select-card" for="sales<?php echo $s['id']; ?>">
                      <input class="sales-select-input d-none" type="checkbox" name="sales[]" value="<?php echo $s['id']; ?>" id="sales<?php echo $s['id']; ?>">
                      <div class="sales-card-content">
                        <div class="avatar-initials-small">
                          <?php 
                            $words = explode(' ', $s['nama']);
                            echo strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                          ?>
                        </div>
                        <div class="sales-card-details">
                          <span class="sales-card-name"><?php echo htmlspecialchars($s['nama']); ?></span>
                          <span class="sales-card-role">Sales Agent</span>
                        </div>
                        <div class="sales-card-checkbox">
                          <span class="material-symbols-outlined">check_circle</span>
                        </div>
                      </div>
                    </label>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>

            <div class="mt-5">
              <button type="submit" class="btn-submit-premium">
                <span class="material-symbols-outlined">save</span>
                Simpan Rencana Kegiatan
              </button>
            </div>
          </form>
        </div>

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

</body>

</html>