<div class="row">
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-header p-3 pt-2">
        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
          <i class="material-icons opacity-10">event_available</i>
        </div>
        <div class="text-end pt-1">
          <a href="index-sa.php" class="text-sm mb-0 text-capitalize font-weight-bold" style="border-bottom:2px solid #000435; padding:3px; padding-top: 0;">Kegiatan Hari Ini</a>
          <?php
          $current_date = date("Y-m-d"); // Today's date
          $sqlToday = "SELECT k.kode_transaksi, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS cust_nomor
             FROM kegiatan k
             LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
             LEFT JOIN customer c ON k.id_cust = c.id_cust
             WHERE k.status != 'Waiting' AND k.status != 'N'
             AND (
                 DATE(k.tgl_request) LIKE '$current_date%'
                 OR DATE(k.tgl_mulai) LIKE '$current_date%'
                 OR DATE(k.tgl_reschedule) LIKE '$current_date%'
                 OR DATE(k.tgl_selesai) LIKE '$current_date%'
             )
             GROUP BY k.kode_transaksi";


          $resultToday = mysqli_query($conn, $sqlToday);
          $num_rowsToday = mysqli_num_rows($resultToday);

          ?>
          <h4 class="mb-0 mt-2"><?php echo $num_rowsToday; ?></h4>
        </div>

      </div>
      <hr class="dark horizontal my-0">
      <div class="card-footer p-3">
        <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card">
      <div class="card-header p-3 pt-2">
        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
          <i class="material-icons opacity-10">task_alt</i>
        </div>
        <div class="text-end pt-1">
          <a href="index-all.php" class="text-sm mb-0 text-capitalize font-weight-bold" style="border-bottom:2px solid #000435; padding:3px; padding-top: 0;">Kegiatan Selesai</a>
          <?php
          $sqlClear = "SELECT k.*, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS cust_nomor
          FROM kegiatan k
          LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
          LEFT JOIN customer c ON k.id_cust = c.id_cust
          WHERE k.status = 'Clear'
          GROUP BY k.kode_transaksi";

          $resultClear = mysqli_query($conn, $sqlClear);
          $num_rowsClear = mysqli_num_rows($resultClear);

          ?>
          <h4 class="mb-0 mt-2"><?php echo $num_rowsClear; ?></h4>
        </div>
      </div>
      <hr class="dark horizontal my-0">
      <div class="card-footer p-3">
        <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4 mt-4 mt-md-0">
    <div class="card">
      <div class="card-header p-3 pt-2">
        <div class="icon icon-lg icon-shape bg-gradient-danger shadow-primary text-center border-radius-xl mt-n4 position-absolute">
          <i class="material-icons opacity-10">cancel</i>
        </div>
        <div class="text-end pt-1">
          <a href="index-x.php" class="text-sm mb-0 text-capitalize font-weight-bold" style="border-bottom:2px solid #000435; padding:3px; padding-top: 0;">Tidak Terselesaikan</a>
          <?php
          $sqlNotClear = "SELECT k.*, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS cust_nomor
          FROM kegiatan k
          LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
          LEFT JOIN customer c ON k.id_cust = c.id_cust
          WHERE k.status != 'Clear' AND DATE(k.tgl_request) < CURDATE()  AND k.status != 'N' AND k.status != 'Waiting'
        GROUP BY k.kode_transaksi";

          $resultNotClear = mysqli_query($conn, $sqlNotClear);
          $num_rowsNotClear = mysqli_num_rows($resultNotClear);

          ?>
          <h4 class="mb-0 mt-2"><?php echo $num_rowsNotClear; ?></h4>
        </div>

      </div>
      <hr class="dark horizontal my-0">
      <div class="card-footer p-3">
        <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
      </div>
    </div>
  </div>
</div>