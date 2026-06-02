<div style="padding:8px 0 0;">
<div class="row" style="gap:0;margin-bottom:0 !important;">
  <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
    <div class="card h-100" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e293b 0%,#334155 100%);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(30,41,59,0.25)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Kegiatan Hari Ini</p>
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
              <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsToday; ?></h3>
            </a>
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
  </div>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <div class="card h-100" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(59,130,246,0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
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
              <a href="index-ln.php" style="text-decoration:none;">
                <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsNotClearFuture; ?></h3>
              </a>
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
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <div class="card h-100" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(239,68,68,0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px 0;">Digantung</p>
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
                <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $num_rowsOverdue; ?></h3>
              </a>
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
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-2">
      <div class="card h-100" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);background:linear-gradient(135deg,#059669 0%,#10b981 100%);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(16,185,129,0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
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
              <a href="index-all.php" style="text-decoration:none;">
                <h3 style="font-size:32px;font-weight:800;color:#fff;margin:0;line-height:1;"><?php echo $countValue; ?></h3>
              </a>
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
    </div>
  <?php endif; ?>
</div>
</div>