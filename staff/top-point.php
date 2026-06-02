<div class="row">
  <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
    <div class="card h-100">
      <div class="card-header p-3 pt-2">
        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
          <i class="material-icons opacity-10">event_available</i>
        </div>
        <div class="text-end pt-1">
          <a href="index-sa.php" class="card-title-custom-link text-sm mb-0 text-capitalize font-weight-bold">
            Kegiatan Hari Ini
          </a>
          <?php
          $current_date = date("Y-m-d"); // Today's date
          $sqlToday = "SELECT kode FROM kegiatan
                       WHERE status != 'waiting'
                       AND DATE(jadwal) = '$current_date'
                       AND deleted_at IS NULL
                       GROUP BY kode";
          $resultToday = mysqli_query($conn, $sqlToday);
          $num_rowsToday = mysqli_num_rows($resultToday);
          ?>
          <h4 class="card-count mb-0 mt-2"><?php echo $num_rowsToday; ?></h4>
        </div>
      </div>
      <hr class="dark horizontal my-0">
      <div class="card-footer p-3">
        <p class="mb-0 card-footer-text">
          Diperbarui pada: <span class="text-success"><?php echo $todayDate; ?></span>
        </p>
      </div>
    </div>
  </div>

  <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100">
        <div class="card-header p-3 pt-2">
          <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
            <i class="material-icons opacity-10">arrow_upward</i>
          </div>
          <div class="text-end pt-1">
            <a href="index-ln.php" class="card-title-custom-link text-sm mb-0 text-capitalize font-weight-bold">
              Lanjut Nanti
            </a>
            <?php
            $sqlNotClearFuture = "SELECT kode FROM kegiatan
                                  WHERE status IN ('Lanjutan', 'Lanjut Nanti')
                                  AND DATE(jadwal) >= '$current_date'
                                  AND deleted_at IS NULL
                                  GROUP BY kode";
            $resultNotClearFuture = mysqli_query($conn, $sqlNotClearFuture);
            $num_rowsNotClearFuture = mysqli_num_rows($resultNotClearFuture);
            ?>
            <h4 class="card-count mb-0 mt-2"><?php echo $num_rowsNotClearFuture; ?></h4>
          </div>
        </div>
        <hr class="dark horizontal my-0">
        <div class="card-footer p-3">
          <p class="mb-0 card-footer-text">
            Diperbarui pada: <span class="text-success"><?php echo $todayDate; ?></span>
          </p>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100">
        <div class="card-header p-3 pt-2">
          <div class="icon icon-lg icon-shape bg-gradient-danger shadow-danger text-center border-radius-xl mt-n4 position-absolute">
            <i class="material-icons opacity-10">cancel</i>
          </div>
          <div class="text-end pt-1">
            <a href="index-x.php" class="card-title-custom-link text-sm mb-0 text-capitalize font-weight-bold">
              Digantung
            </a>
            <?php
            // Misalkan $conn adalah variabel koneksi database Anda
            // Misalkan $current_date adalah variabel berisi tanggal hari ini (format YYYY-MM-DD)
            
            $sqlOverdue = "WITH RankedKegiatan AS (
                SELECT
                    k.kode,
                    k.jadwal,
                    k.status,
                    k.id, -- Digunakan untuk tie-breaker jika ada jadwal yang sama persis
                    ROW_NUMBER() OVER(PARTITION BY k.kode ORDER BY k.jadwal DESC, k.id DESC) as rn
                FROM
                    kegiatan k
                WHERE
                    k.deleted_at IS NULL -- 1. Ambil semua data kegiatan yang tidak di-soft delete
            )
            SELECT
                COUNT(*) AS total_overdue
            FROM
                RankedKegiatan rk
            WHERE
                rk.rn = 1 -- 3. Ambil satu kegiatan dengan jadwal paling baru per kode
                AND rk.status IN ('berjalan', 'dijadwalkan', 'Lanjutan', 'Lanjut Nanti') -- 4. Cek status data terpilih
                AND DATE(rk.jadwal) < '$current_date'; -- 5. Cek apakah data terpilih sudah overdue
            
            ";
            
            $resultOverdue = mysqli_query($conn, $sqlOverdue);
            $num_rowsOverdue = 0; // Nilai default
            
            if ($resultOverdue) {
                $row = mysqli_fetch_assoc($resultOverdue);
                if ($row) {
                    $num_rowsOverdue = $row['total_overdue'];
                }
                mysqli_free_result($resultOverdue);
            } else {
                // Opsional: Tambahkan penanganan error jika query gagal
                // error_log("Error pada query sqlOverdue: " . mysqli_error($conn));
            }
            ?>
            <h4 class="card-count mb-0 mt-2"><?php echo $num_rowsOverdue; ?></h4>
          </div>
        </div>
        <hr class="dark horizontal my-0">
        <div class="card-footer p-3">
          <p class="mb-0 card-footer-text">
            Diperbarui pada: <span class="text-success"><?php echo $todayDate; ?></span>
          </p>
        </div>
      </div>
    </div>

    <!--<div class="col-12 col-sm-6 col-md-4 col-lg mb-4">-->
    <!--  <div class="card h-100">-->
    <!--    <div class="card-header p-3 pt-2">-->
    <!--      <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">-->
    <!--        <i class="material-icons opacity-10">playlist_add_check</i>-->
    <!--      </div>-->
    <!--      <div class="text-end pt-1">-->
    <!--        <a href="index-dt.php" class="card-title-custom-link text-sm mb-0 text-capitalize font-weight-bold">-->
    <!--          Diselesaikan-->
    <!--        </a>-->
            <?php
            // $sqlCompletedToday = "SELECT kode FROM kegiatan
            //                       WHERE status = 'paksa_selesai'
            //                       AND DATE(updated_at) = '$current_date' 
            //                       AND deleted_at IS NULL
            //                       GROUP BY kode";
            // $resultCompletedToday = mysqli_query($conn, $sqlCompletedToday);
            // $num_rowsCompletedToday = mysqli_num_rows($resultCompletedToday);
            ?>
    <!--        <h4 class="card-count mb-0 mt-2"><?php echo $num_rowsCompletedToday; ?></h4>-->
    <!--      </div>-->
    <!--    </div>-->
    <!--    <hr class="dark horizontal my-0">-->
    <!--    <div class="card-footer p-3">-->
    <!--      <p class="mb-0 card-footer-text">-->
    <!--        Diselesaikan hari ini: <span class="text-success"><?php echo $todayDate; ?></span>-->
    <!--      </p>-->
    <!--    </div>-->
    <!--  </div>-->
    <!--</div>-->

    <div class="col-12 col-sm-6 col-md-4 col-lg mb-4">
      <div class="card h-100">
        <div class="card-header p-3 pt-2">
          <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
            <i class="material-icons opacity-10">task_alt</i>
          </div>
          <div class="text-end pt-1">
            <a href="index-all.php" class="card-title-custom-link text-sm mb-0 text-capitalize font-weight-bold">
              Selesai
            </a>
        <?php
        
        $sqlClear = "
        SELECT
            COUNT(*) AS total_kegiatan
        FROM (
            SELECT
                k.kode
            FROM
                kegiatan k
            INNER JOIN (
                SELECT
                    sub_k.kode,
                    MAX(sub_k.id) AS max_id
                FROM
                    kegiatan sub_k
                WHERE
                    sub_k.deleted_at IS NULL
                GROUP BY
                    sub_k.kode
            ) AS latest_kegiatan ON k.kode = latest_kegiatan.kode AND k.id = latest_kegiatan.max_id
            WHERE
                k.status IN ('selesai', 'selesai by admin')
                AND k.deleted_at IS NULL
            GROUP BY
                k.kode
        ) AS grouped_kegiatan
        ";
        
        $resultClear = mysqli_query($conn, $sqlClear);
        $countValue = 0; // Default value jika query gagal atau tidak ada hasil
        
        if ($resultClear) {
            $row = mysqli_fetch_assoc($resultClear); // Ambil baris hasil
            if ($row) {
                $countValue = $row['total_kegiatan']; // Akses nilai dari kolom 'total_kegiatan'
            }
            mysqli_free_result($resultClear); // Bebaskan memori hasil query
        } else {
            // Opsional: Tambahkan penanganan error jika query gagal
            // echo "Error: " . mysqli_error($conn);
        }
        ?>
        <h4 class="card-count mb-0 mt-2"><?php echo $countValue; ?></h4>
          </div>
        </div>
        <hr class="dark horizontal my-0">
        <div class="card-footer p-3">
          <p class="mb-0 card-footer-text">
            Diperbarui pada: <span class="text-success"><?php echo $todayDate; ?></span>
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>