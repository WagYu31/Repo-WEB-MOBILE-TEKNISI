<?php
// Pastikan koneksi database aktif
$current_date = date("Y-m-d");

$kegiatan_tabs = [
  'hari-ini' => [
    'label' => 'Hari Ini',
    'condition' => "DATE(ks.jadwal) = '$current_date'"
  ],
  'akan-datang' => [
    'label' => 'Akan Datang',
    'condition' => "DATE(ks.jadwal) > '$current_date'"
  ],
  'terlewat' => [
    'label' => 'Terlewat',
    'condition' => "DATE(ks.jadwal) < '$current_date' AND ks.status != 'selesai'"
  ],
  'selesai' => [
    'label' => 'Selesai',
    'condition' => "ks.status = 'selesai'"
  ]
];
?>


<!-- Nav Tabs -->
<div class="col-lg-12 mt-4 mb-0">
  <ul class="nav nav-tabs" id="kegiatanTab" role="tablist">
    <?php $first = true; foreach ($kegiatan_tabs as $tab_id => $tab): ?>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                id="<?php echo $tab_id; ?>-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#<?php echo $tab_id; ?>" 
                type="button" 
                role="tab">
          <?php echo $tab['label']; ?>
        </button>
      </li>
    <?php $first = false; endforeach; ?>
  </ul>
</div>

<!-- Tab Content -->
<div class="tab-content p-0" id="kegiatanTabsContent">
  <?php $first = true; foreach ($kegiatan_tabs as $tab_id => $tab): ?>
    <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
         id="<?php echo $tab_id; ?>" 
         role="tabpanel" 
         aria-labelledby="<?php echo $tab_id; ?>-tab">
         
      <div class="col-lg-12 m-0">
        <div class="card h-100" style="border-top-left-radius:0;">
          <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-2 col-12">

              <!-- Header -->
              <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                <div class="row px-4">
                  <div class="col-2 mb-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Jadwal</h6></div>
                  <div class="col-2 mb-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6></div>
                  <div class="col-3 mb-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Sales</h6></div>
                  <div class="col-4 mb-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6></div>
                  <div class="col-1 mb-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Aksi</h6></div>
                </div>
              </li>

              <?php
              $condition = $tab['condition'];
              $sql = "SELECT ks.*, c.nama AS nama_customer, c.telp_pribadi AS cust_nomor, c.alamat
                      FROM kegiatan_sales ks
                      LEFT JOIN sales_customer c ON ks.id_customer = c.id
                      WHERE ks.status != 'waiting' AND $condition AND ks.deleted_at IS NULL
                      ORDER BY ks.id DESC";
              $result = mysqli_query($conn, $sql);

              if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                  $kegiatanId = $row['id'];
                  $jadwal = date("d/m/Y H:i", strtotime($row['jadwal']));
                  $telp = $row['cust_nomor'];
                  if (substr($telp, 0, 1) === '0') $telp = '62' . substr($telp, 1);

                  // Ambil sales
                  $salesList = [];
                  $sqlSales = "SELECT s.nama AS nama_sales, ps.status AS status_pelaksanaan
                               FROM team_kegiatan_sales tks
                               LEFT JOIN sales s ON tks.id_sales = s.id
                               LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
                               WHERE tks.id_kegiatan_sales = '$kegiatanId' AND tks.deleted_at IS NULL";
                  $resultSales = mysqli_query($conn, $sqlSales);

                  while ($s = mysqli_fetch_assoc($resultSales)) {
                    $status = strtolower($s['status_pelaksanaan'] ?? 'dijadwalkan');
                    $badgeColor = match ($status) {
                      'berjalan' => 'info',
                      'selesai' => 'success',
                      default => 'secondary'
                    };
                    $salesList[] = '
                      <div class="mb-1 d-flex justify-content-between align-items-center">
                        <span class="text-sm font-weight-bold text-dark">' . $s['nama_sales'] . '</span>
                        <span class="badge bg-gradient-' . $badgeColor . ' text-white px-2 py-1" style="font-size: 10px;">' . ucfirst($status) . '</span>
                      </div>';

                  }
              ?>

              <!-- Isi Kegiatan -->
              <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
                <div class="row px-4">
                  <div class="col-2 mb-2"><span class="text-sm text-dark"><?php echo $jadwal; ?></span></div>
                  <div class="col-2 mb-2">
                    <h6 class="mb-0 text-sm text-dark font-weight-bold"><?php echo $row['nama_customer']; ?></h6>
                    <a href="https://api.whatsapp.com/send?phone=<?php echo $telp; ?>" target="_blank" class="text-xs"><?php echo $row['cust_nomor']; ?></a>
                  </div>
                  <div class="col-3 mb-2"><?php echo implode('', $salesList); ?></div>
                  <div class="col-4 mb-2"><span class="text-sm text-dark"><?php echo $row['alamat']; ?></span></div>
                  <div class="col-1 mb-2">
                    <a href="edit_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">Edit</a>
                    <a href="detail_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info">View</a>
                  </div>
                </div>
              </li>

              <?php endwhile; else: ?>
                <li class="list-group-item border-0 ps-4 text-sm text-muted">Tidak ada kegiatan untuk tab ini.</li>
              <?php endif; ?>

            </ul>
          </div>
        </div>
      </div>
    </div>
  <?php $first = false; endforeach; ?>
</div>
