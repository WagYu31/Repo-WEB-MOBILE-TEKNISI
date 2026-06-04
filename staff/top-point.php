<!-- ═══ DESKTOP: Original Card Design ═══ -->
<div class="stat-desktop">
<div style="padding:8px 0 0;">
<div class="row" style="gap:0;margin-bottom:0 !important;">
  <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
    <a href="index-sa.php" class="card-stat-link" style="text-decoration:none;">
    <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e293b 0%,#334155 100%);cursor:pointer;">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Kegiatan Hari Ini</p>
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
            <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsToday; ?></h3>
          </div>
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
            <i class="material-icons" style="font-size:22px;color:rgba(255,255,255,0.7);">event_available</i>
          </div>
        </div>
      </div>
      <div style="padding:8px 12px;background:rgba(0,0,0,0.15);">
        <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
      </div>
    </div>
    </a>
  </div>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-ln.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);cursor:pointer;">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Lanjut Nanti</p>
              <?php
              $sqlNotClearFuture = "SELECT kode FROM kegiatan
                                    WHERE status IN ('Lanjutan', 'Lanjut Nanti')
                                    AND DATE(jadwal) >= '$current_date'
                                    AND deleted_at IS NULL
                                    GROUP BY kode";
              $resultNotClearFuture = mysqli_query($conn, $sqlNotClearFuture);
              $num_rowsNotClearFuture = mysqli_num_rows($resultNotClearFuture);
              ?>
              <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsNotClearFuture; ?></h3>
            </div>
            <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
              <i class="material-icons" style="font-size:22px;color:rgba(255,255,255,0.7);">schedule</i>
            </div>
          </div>
        </div>
        <div style="padding:8px 12px;background:rgba(0,0,0,0.15);">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-x.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);cursor:pointer;">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Digantung</p>
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
              <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsOverdue; ?></h3>
            </div>
            <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
              <i class="material-icons" style="font-size:22px;color:rgba(255,255,255,0.7);">warning_amber</i>
            </div>
          </div>
        </div>
        <div style="padding:8px 12px;background:rgba(0,0,0,0.15);">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <a href="index-all.php" class="card-stat-link" style="text-decoration:none;">
      <div class="card h-100 card-stat" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#059669 0%,#10b981 100%);cursor:pointer;">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Selesai</p>
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
              <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $countValue; ?></h3>
            </div>
            <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
              <i class="material-icons" style="font-size:22px;color:rgba(255,255,255,0.7);">check_circle</i>
            </div>
          </div>
        </div>
        <div style="padding:8px 12px;background:rgba(0,0,0,0.15);">
          <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;"><?php echo $todayDate; ?></p>
        </div>
      </div>
      </a>
    </div>
  <?php endif; ?>
</div>
</div>
</div>

<!-- ═══ MOBILE PORTRAIT: Compact Strip ═══ -->
<div class="stat-mobile">
  <div class="stat-strip-m">
    <a href="index-sa.php" class="stat-chip">
      <div class="chip-icon" style="background:rgba(255,255,255,0.15);"><i class="material-icons">event_available</i></div>
      <div class="chip-info">
        <span class="chip-label">Hari Ini</span>
        <span class="chip-val"><?php echo $num_rowsToday; ?></span>
      </div>
    </a>
    <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <a href="index-ln.php" class="stat-chip stat-chip-blue">
      <div class="chip-icon"><i class="material-icons">schedule</i></div>
      <div class="chip-info">
        <span class="chip-label">Lanjut</span>
        <span class="chip-val"><?php echo $num_rowsNotClearFuture; ?></span>
      </div>
    </a>
    <a href="index-x.php" class="stat-chip stat-chip-red">
      <div class="chip-icon"><i class="material-icons">warning_amber</i></div>
      <div class="chip-info">
        <span class="chip-label">Digantung</span>
        <span class="chip-val"><?php echo $num_rowsOverdue; ?></span>
      </div>
    </a>
    <a href="index-all.php" class="stat-chip stat-chip-green">
      <div class="chip-icon"><i class="material-icons">check_circle</i></div>
      <div class="chip-info">
        <span class="chip-label">Selesai</span>
        <span class="chip-val"><?php echo $countValue; ?></span>
      </div>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Loading overlay -->
<div id="page-loader" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:99998;background:rgba(255,255,255,0.85);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="text-align:center;">
    <div class="spinner-border text-primary" style="width:40px;height:40px;" role="status"></div>
    <p style="margin-top:12px;font-size:13px;font-weight:600;color:#475569;">Memuat halaman...</p>
  </div>
</div>

<style>
  /* Desktop cards */
  .card-stat { transition: transform 0.2s, box-shadow 0.2s; }
  .card-stat:hover { transform: translateY(-2px); }
  .card-stat:active { transform: scale(0.97); }

  /* Show/hide logic */
  .stat-mobile { display: none; }
  .stat-desktop { display: block; }

  /* Mobile strip */
  .stat-strip-m {
    display: flex; gap: 6px; padding: 6px 0;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .stat-strip-m::-webkit-scrollbar { display: none; }
  .stat-chip {
    flex: 1; min-width: 0;
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 8px;
    text-decoration: none; color: #fff;
    background: linear-gradient(135deg, #1e293b, #334155);
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    transition: transform 0.15s;
  }
  .stat-chip:active { transform: scale(0.96); }
  .stat-chip:hover { color: #fff; }
  .stat-chip-blue { background: linear-gradient(135deg, #1e40af, #3b82f6); }
  .stat-chip-red { background: linear-gradient(135deg, #dc2626, #ef4444); }
  .stat-chip-green { background: linear-gradient(135deg, #059669, #10b981); }
  .chip-icon {
    width: 24px; height: 24px; border-radius: 6px;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .chip-icon i { font-size: 14px; color: rgba(255,255,255,0.8); }
  .chip-info { min-width: 0; }
  .chip-label {
    font-size: 7px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: rgba(255,255,255,0.5);
    display: block; line-height: 1;
  }
  .chip-val { font-size: 18px; font-weight: 800; line-height: 1.1; display: block; }

  /* Mobile: show strip, hide cards */
  @media (max-width: 767px) {
    .stat-desktop { display: none !important; }
    .stat-mobile { display: block !important; }
  }

  /* Samsung Fold folded (≤400px): smaller chips */
  @media (max-width: 400px) {
    .stat-chip { padding: 6px 8px; gap: 6px; }
    .chip-icon { width: 20px; height: 20px; border-radius: 5px; }
    .chip-icon i { font-size: 12px; }
    .chip-label { font-size: 6px; }
    .chip-val { font-size: 15px; }
  }
</style>

<script>
  document.querySelectorAll('.card-stat-link, .stat-chip').forEach(function(link) {
    link.addEventListener('click', function() {
      var loader = document.getElementById('page-loader');
      if (loader) loader.style.display = 'flex';
    });
  });
</script>