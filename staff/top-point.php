<style>
  .stat-strip {
    display: flex; gap: 8px; padding: 8px;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .stat-strip::-webkit-scrollbar { display: none; }
  .stat-item {
    flex: 1; min-width: 0;
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    text-decoration: none; color: #fff;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  }
  .stat-item:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); color: #fff; }
  .stat-item:active { transform: scale(0.97); }
  .stat-item-dark { background: linear-gradient(135deg, #1e293b, #334155); }
  .stat-item-blue { background: linear-gradient(135deg, #1e40af, #3b82f6); }
  .stat-item-red { background: linear-gradient(135deg, #dc2626, #ef4444); }
  .stat-item-green { background: linear-gradient(135deg, #059669, #10b981); }
  .stat-item .stat-icon-sm {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,0.15); display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
  }
  .stat-item .stat-icon-sm i { font-size: 18px; color: rgba(255,255,255,0.8); }
  .stat-info { min-width: 0; }
  .stat-info .stat-title {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: rgba(255,255,255,0.5);
    margin: 0; line-height: 1.2; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
  }
  .stat-info .stat-val {
    font-size: 22px; font-weight: 800; color: #fff;
    margin: 0; line-height: 1.1;
  }

  @media (max-width: 768px) {
    .stat-strip { gap: 6px; padding: 6px 8px; }
    .stat-item { padding: 8px 10px; gap: 8px; border-radius: 8px; }
    .stat-item .stat-icon-sm { width: 26px; height: 26px; border-radius: 6px; }
    .stat-item .stat-icon-sm i { font-size: 14px !important; }
    .stat-info .stat-title { font-size: 7px; }
    .stat-info .stat-val { font-size: 16px; }
  }
  @media (max-width: 400px) {
    .stat-item { padding: 6px 8px; gap: 6px; }
    .stat-item .stat-icon-sm { width: 22px; height: 22px; }
    .stat-item .stat-icon-sm i { font-size: 12px !important; }
    .stat-info .stat-val { font-size: 14px; }
  }
</style>

<div class="stat-strip">
  <a href="index-sa.php" class="stat-item stat-item-dark">
    <div class="stat-icon-sm"><i class="material-icons">event_available</i></div>
    <div class="stat-info">
      <p class="stat-title">Hari Ini</p>
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
      <p class="stat-val"><?php echo $num_rowsToday; ?></p>
    </div>
  </a>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
  <a href="index-ln.php" class="stat-item stat-item-blue">
    <div class="stat-icon-sm"><i class="material-icons">schedule</i></div>
    <div class="stat-info">
      <p class="stat-title">Lanjut</p>
      <?php
      $sqlNotClearFuture = "SELECT kode FROM kegiatan
                            WHERE status IN ('Lanjutan', 'Lanjut Nanti')
                            AND DATE(jadwal) >= '$current_date'
                            AND deleted_at IS NULL
                            GROUP BY kode";
      $resultNotClearFuture = mysqli_query($conn, $sqlNotClearFuture);
      $num_rowsNotClearFuture = mysqli_num_rows($resultNotClearFuture);
      ?>
      <p class="stat-val"><?php echo $num_rowsNotClearFuture; ?></p>
    </div>
  </a>

  <a href="index-x.php" class="stat-item stat-item-red">
    <div class="stat-icon-sm"><i class="material-icons">warning_amber</i></div>
    <div class="stat-info">
      <p class="stat-title">Digantung</p>
      <?php
      $sqlOverdue = "WITH RankedKegiatan AS (
          SELECT k.kode, k.jadwal, k.status, k.id,
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
      <p class="stat-val"><?php echo $num_rowsOverdue; ?></p>
    </div>
  </a>

  <a href="index-all.php" class="stat-item stat-item-green">
    <div class="stat-icon-sm"><i class="material-icons">check_circle</i></div>
    <div class="stat-info">
      <p class="stat-title">Selesai</p>
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
      <p class="stat-val"><?php echo $countValue; ?></p>
    </div>
  </a>
  <?php endif; ?>
</div>

<!-- Loading overlay -->
<div id="page-loader" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:99998;background:rgba(255,255,255,0.85);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="text-align:center;">
    <div class="spinner-border text-primary" style="width:40px;height:40px;" role="status"></div>
    <p style="margin-top:12px;font-size:13px;font-weight:600;color:#475569;">Memuat halaman...</p>
  </div>
</div>
<script>
  document.querySelectorAll('.stat-item').forEach(function(link) {
    link.addEventListener('click', function() {
      var loader = document.getElementById('page-loader');
      if (loader) loader.style.display = 'flex';
    });
  });
</script>