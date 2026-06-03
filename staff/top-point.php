<div style="padding:8px 0 0;">
<div class="row" style="gap:0;margin-bottom:0 !important;">
  <div class="col-6 col-sm-6 col-md-4 col-lg mb-2">
    <a href="index-sa.php" class="card-stat-link" style="text-decoration:none;">
    <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e293b 0%,#334155 100%);cursor:pointer;">
      <div class="card-body stat-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="stat-label">Kegiatan Hari Ini</p>
            <?php
            $current_date = date("Y-m-d");
            $sqlToday = "SELECT kode FROM kegiatan
                         WHERE status NOT IN ('waiting', 'selesai by admin')
                         AND DATE(jadwal) = '$current_date'
                         AND deleted_at IS NULL
                         GROUP BY kode";
            $resultToday = mysqli_query($conn, $sqlToday);
            $num_rowsToday = mysqli_num_rows($resultToday);
            ?>
            <h3 class="stat-number"><?php echo $num_rowsToday; ?></h3>
          </div>
          <div class="stat-icon">
            <i class="material-icons" style="font-size:20px;color:rgba(255,255,255,0.7);">event_available</i>
          </div>
        </div>
      </div>
      <div class="stat-footer">
        <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
      </div>
    </div>
    </a>
  </div>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <div class="col-6 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-ln.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);cursor:pointer;">
        <div class="card-body stat-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="stat-label">Lanjut Nanti</p>
              <?php
              $sqlNotClearFuture = "SELECT kode FROM kegiatan
                                    WHERE status IN ('Lanjutan', 'Lanjut Nanti')
                                    AND DATE(jadwal) >= '$current_date'
                                    AND deleted_at IS NULL
                                    GROUP BY kode";
              $resultNotClearFuture = mysqli_query($conn, $sqlNotClearFuture);
              $num_rowsNotClearFuture = mysqli_num_rows($resultNotClearFuture);
              ?>
              <h3 class="stat-number"><?php echo $num_rowsNotClearFuture; ?></h3>
            </div>
            <div class="stat-icon">
              <i class="material-icons" style="font-size:20px;color:rgba(255,255,255,0.7);">schedule</i>
            </div>
          </div>
        </div>
        <div class="stat-footer">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-x.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);cursor:pointer;">
        <div class="card-body stat-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="stat-label">Digantung</p>
              <?php
              $sqlOverdue = "WITH RankedKegiatan AS (
                  SELECT
                      k.kode, k.jadwal, k.status, k.id,
                      ROW_NUMBER() OVER(PARTITION BY k.kode ORDER BY k.jadwal DESC, k.id DESC) as rn
                  FROM kegiatan k WHERE k.deleted_at IS NULL
              )
              SELECT COUNT(*) AS total_overdue
              FROM RankedKegiatan rk
              WHERE rk.rn = 1
                AND rk.status IN ('berjalan', 'dijadwalkan', 'Lanjutan', 'Lanjut Nanti')
                AND DATE(rk.jadwal) < '$current_date'";
              
              $resultOverdue = mysqli_query($conn, $sqlOverdue);
              $num_rowsOverdue = 0;
              if ($resultOverdue) {
                  $row = mysqli_fetch_assoc($resultOverdue);
                  if ($row) $num_rowsOverdue = $row['total_overdue'];
                  mysqli_free_result($resultOverdue);
              }
              ?>
              <h3 class="stat-number"><?php echo $num_rowsOverdue; ?></h3>
            </div>
            <div class="stat-icon">
              <i class="material-icons" style="font-size:20px;color:rgba(255,255,255,0.7);">warning_amber</i>
            </div>
          </div>
        </div>
        <div class="stat-footer">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-all.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#059669 0%,#10b981 100%);cursor:pointer;">
        <div class="card-body stat-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="stat-label">Selesai</p>
              <?php
              $sqlClear = "SELECT COUNT(*) AS total_kegiatan FROM (
                  SELECT k.kode FROM kegiatan k
                  INNER JOIN (SELECT sub_k.kode, MAX(sub_k.id) AS max_id FROM kegiatan sub_k WHERE sub_k.deleted_at IS NULL GROUP BY sub_k.kode) AS latest_kegiatan 
                  ON k.kode = latest_kegiatan.kode AND k.id = latest_kegiatan.max_id
                  WHERE k.status IN ('selesai', 'selesai by admin') AND k.deleted_at IS NULL GROUP BY k.kode
              ) AS grouped_kegiatan";
              $resultClear = mysqli_query($conn, $sqlClear);
              $countValue = 0;
              if ($resultClear) { $row = mysqli_fetch_assoc($resultClear); if ($row) $countValue = $row['total_kegiatan']; mysqli_free_result($resultClear); }
              ?>
              <h3 class="stat-number"><?php echo $countValue; ?></h3>
            </div>
            <div class="stat-icon">
              <i class="material-icons" style="font-size:20px;color:rgba(255,255,255,0.7);">check_circle</i>
            </div>
          </div>
        </div>
        <div class="stat-footer">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>
  <?php endif; ?>
</div>
</div>

<!-- Loading overlay saat klik card -->
<div id="page-loader" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:99998;background:rgba(255,255,255,0.85);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="text-align:center;">
    <div class="spinner-border text-primary" style="width:40px;height:40px;" role="status"></div>
    <p style="margin-top:12px;font-size:13px;font-weight:600;color:#475569;">Memuat halaman...</p>
  </div>
</div>

<style>
  .card-stat { transition: transform 0.2s, box-shadow 0.2s; }
  .card-stat:hover { transform: translateY(-2px); }
  .card-stat:active { transform: scale(0.97); }

  .stat-body { padding: 16px !important; }
  .stat-label {
    font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.5);
    text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 6px 0;
  }
  .stat-number {
    font-size: 28px; font-weight: 800; color: #fff; margin: 0; line-height: 1;
  }
  .stat-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: rgba(255,255,255,0.12); display: flex;
    align-items: center; justify-content: center;
  }
  .stat-footer { padding: 6px 12px; background: rgba(0,0,0,0.15); }

  /* MOBILE COMPACT */
  @media (max-width: 768px) {
    .stat-body { padding: 10px 12px !important; }
    .stat-label { font-size: 8px; margin: 0 0 3px 0; letter-spacing: 0.05em; }
    .stat-number { font-size: 20px; }
    .stat-icon { width: 28px; height: 28px; border-radius: 6px; }
    .stat-icon .material-icons { font-size: 16px !important; }
    .stat-footer { padding: 4px 10px; }
    .stat-footer p { font-size: 8px !important; }
  }
  @media (max-width: 480px) {
    .stat-body { padding: 8px 10px !important; }
    .stat-label { font-size: 7px; margin: 0 0 2px 0; }
    .stat-number { font-size: 18px; }
    .stat-icon { width: 24px; height: 24px; }
    .stat-icon .material-icons { font-size: 14px !important; }
    .stat-footer { padding: 3px 8px; }
  }
</style>
<script>
  document.querySelectorAll('.card-stat-link').forEach(function(link) {
    link.addEventListener('click', function() {
      var loader = document.getElementById('page-loader');
      if (loader) loader.style.display = 'flex';
    });
  });
</script>