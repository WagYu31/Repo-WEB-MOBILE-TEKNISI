<div class="row" style="gap:0;">
  <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
    <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin:0 0 4px 0;">Kegiatan Hari Ini</p>
            <?php
            $current_date = date("Y-m-d");
            $sqlToday = "SELECT kode FROM kegiatan
                         WHERE status != 'waiting'
                         AND DATE(jadwal) = '$current_date'
                         AND deleted_at IS NULL
                         GROUP BY kode";
            $resultToday = mysqli_query($conn, $sqlToday);
            $num_rowsToday = mysqli_num_rows($resultToday);
            ?>
            <a href="index-sa.php" style="text-decoration:none;">
              <h3 style="font-size:28px;font-weight:700;color:#0f172a;margin:0;"><?php echo $num_rowsToday; ?></h3>
            </a>
          </div>
          <div style="width:40px;height:40px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
            <i class="material-icons" style="font-size:20px;color:#475569;">event_available</i>
          </div>
        </div>
      </div>
      <div style="border-top:1px solid #f1f5f9;padding:8px 12px;">
        <p style="font-size:11px;color:#94a3b8;margin:0;">Diperbarui pada: <?php echo $todayDate; ?></p>
      </div>
    </div>
  </div>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin:0 0 4px 0;">Lanjut Nanti</p>
              <?php
              $sqlNotClearFuture = "SELECT kode FROM kegiatan
                                    WHERE status IN ('Lanjutan', 'Lanjut Nanti')
                                    AND DATE(jadwal) >= '$current_date'
                                    AND deleted_at IS NULL
                                    GROUP BY kode";
              $resultNotClearFuture = mysqli_query($conn, $sqlNotClearFuture);
              $num_rowsNotClearFuture = mysqli_num_rows($resultNotClearFuture);
              ?>
              <a href="index-ln.php" style="text-decoration:none;">
                <h3 style="font-size:28px;font-weight:700;color:#0f172a;margin:0;"><?php echo $num_rowsNotClearFuture; ?></h3>
              </a>
            </div>
            <div style="width:40px;height:40px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
              <i class="material-icons" style="font-size:20px;color:#3b82f6;">schedule</i>
            </div>
          </div>
        </div>
        <div style="border-top:1px solid #f1f5f9;padding:8px 12px;">
          <p style="font-size:11px;color:#94a3b8;margin:0;">Diperbarui pada: <?php echo $todayDate; ?></p>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin:0 0 4px 0;">Digantung</p>
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
              <a href="index-x.php" style="text-decoration:none;">
                <h3 style="font-size:28px;font-weight:700;color:<?= $num_rowsOverdue > 0 ? '#dc2626' : '#0f172a'; ?>;margin:0;"><?php echo $num_rowsOverdue; ?></h3>
              </a>
            </div>
            <div style="width:40px;height:40px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center;">
              <i class="material-icons" style="font-size:20px;color:#ef4444;">warning_amber</i>
            </div>
          </div>
        </div>
        <div style="border-top:1px solid #f1f5f9;padding:8px 12px;">
          <p style="font-size:11px;color:#94a3b8;margin:0;">Diperbarui pada: <?php echo $todayDate; ?></p>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin:0 0 4px 0;">Selesai</p>
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
              <a href="index-all.php" style="text-decoration:none;">
                <h3 style="font-size:28px;font-weight:700;color:#0f172a;margin:0;"><?php echo $countValue; ?></h3>
              </a>
            </div>
            <div style="width:40px;height:40px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
              <i class="material-icons" style="font-size:20px;color:#16a34a;">check_circle</i>
            </div>
          </div>
        </div>
        <div style="border-top:1px solid #f1f5f9;padding:8px 12px;">
          <p style="font-size:11px;color:#94a3b8;margin:0;">Diperbarui pada: <?php echo $todayDate; ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>