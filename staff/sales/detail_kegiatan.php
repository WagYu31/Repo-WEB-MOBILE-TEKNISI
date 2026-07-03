<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Today";

if (!isset($_GET['id'])) {
    echo "ID Kegiatan tidak ditemukan.";
    exit;
}

$idKegiatan = $_GET['id'];

// Ambil data kegiatan
$sqlKegiatan = "SELECT ks.*, c.nama AS nama_customer, c.telp_pribadi, c.alamat 
                FROM kegiatan_sales ks
                LEFT JOIN sales_customer c ON ks.id_customer = c.id
                WHERE ks.id = '$idKegiatan' AND ks.deleted_at IS NULL";

$resultKegiatan = mysqli_query($conn, $sqlKegiatan);
$data = mysqli_fetch_assoc($resultKegiatan);

// Ambil tim sales
$sqlSales = "SELECT s.nama AS nama_sales, ps.status, ps.keterangan, ps.image_1, ps.image_2, ps.image_3, ps.record,
                    ps.ci_at, ps.co_at, ps.lat_ci, ps.lon_ci, ps.lat_co, ps.lon_co, ps.catatan_visit 
             FROM team_kegiatan_sales tks
             LEFT JOIN sales s ON tks.id_sales = s.id
             LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
             WHERE tks.id_kegiatan_sales = '$idKegiatan' AND tks.deleted_at IS NULL";

$resultSales = mysqli_query($conn, $sqlSales);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    /* ── Premium Detail Styling ── */
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
      font-size: 13px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
    }
    
    .card-body-premium {
      padding: 28px 24px;
    }

    /* ── Detail Grid ── */
    .info-label {
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .info-value {
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
    }

    .wa-link {
      color: #10b981;
      text-decoration: none;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .wa-link:hover { text-decoration: underline; }

    /* ── Sales Laporan Cards ── */
    .sales-laporan-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 24px;
      height: 100%;
      box-shadow: 0 4px 15px rgba(0,0,0,0.01);
      border-left: 5px solid #64748b;
      transition: all 0.22s ease;
    }
    .sales-laporan-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.04);
    }
    
    .sales-name-title {
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .status-badge-sales {
      font-size: 10px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .badge-selesai { background: #d1fae5; color: #065f46; }
    .badge-berjalan { background: #dbeafe; color: #1e40af; }
    .badge-dijadwalkan { background: #f1f5f9; color: #475569; }

    .laporan-note {
      background: #f8fafc;
      border-radius: 8px;
      padding: 12px 16px;
      border-left: 3px solid #cbd5e1;
      font-style: italic;
      color: #475569;
      font-size: 13.5px;
    }

    /* ── Documentation Photos ── */
    .doc-img-wrapper {
      position: relative;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      transition: all 0.22s ease;
      cursor: pointer;
    }
    .doc-img-wrapper:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    
    .doc-img {
      width: 100%;
      height: 120px;
      object-fit: cover;
    }

    /* ── Audio Player Style ── */
    .premium-audio {
      width: 100%;
      border-radius: 30px;
      background: #f8fafc;
      outline: none;
    }

    <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php include "nav-top.php"; ?>

    <div class="container-fluid py-4">

      <!-- Detail Kegiatan Card -->
      <div class="card-premium">
        <div class="section-header-premium">
          <h6>
            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">info</span>
            Informasi Rencana Kunjungan
          </h6>
        </div>
        <div class="card-body-premium">
          <div class="row g-4">
            
            <div class="col-md-6 col-lg-3">
              <span class="info-label">
                <span class="material-symbols-outlined" style="font-size: 15px;">storefront</span>
                Nama Customer / Toko
              </span>
              <span class="info-value"><?php echo htmlspecialchars($data['nama_customer'] ?? '-'); ?></span>
            </div>

            <div class="col-md-6 col-lg-3">
              <span class="info-label">
                <span class="material-symbols-outlined" style="font-size: 15px;">call</span>
                No. Telepon WhatsApp
              </span>
              <span class="info-value">
                <?php if (!empty($data['telp_pribadi'])): ?>
                <a href="https://wa.me/<?php echo htmlspecialchars($data['telp_pribadi']); ?>" target="_blank" class="wa-link">
                  <i class="fab fa-whatsapp" style="font-size: 16px;"></i> 
                  <?php echo htmlspecialchars(preg_replace('/^62/', '0', $data['telp_pribadi'])); ?>
                </a>
                <?php else: ?>
                -
                <?php endif; ?>
              </span>
            </div>

            <div class="col-md-6 col-lg-3">
              <span class="info-label">
                <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                Jadwal Kunjungan
              </span>
              <span class="info-value"><?php echo date('d M Y, H:i', strtotime($data['jadwal'])); ?> WIB</span>
            </div>

            <div class="col-md-6 col-lg-3">
              <span class="info-label">
                <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                Alamat Toko
              </span>
              <span class="info-value" style="font-size:13.5px; font-weight: 500; color: #475569;"><?php echo htmlspecialchars($data['alamat'] ?? '-'); ?></span>
            </div>

          </div>
        </div>
      </div>

      <!-- Tim Sales & Laporan Section -->
      <div class="card-premium">
        <div class="section-header-premium">
          <h6>
            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">groups</span>
            Tim Sales &amp; Laporan Lapangan
          </h6>
        </div>
        <div class="card-body-premium">
          <?php if (mysqli_num_rows($resultSales) > 0): ?>
          <div class="row g-4">
            <?php while ($row = mysqli_fetch_assoc($resultSales)): 
              $status = strtolower($row['status'] ?? 'dijadwalkan');
              $badgeClass = match($status) {
                'selesai' => 'badge-selesai',
                'berjalan' => 'badge-berjalan',
                default => 'badge-dijadwalkan'
              };
              $borderLeftColor = match($status) {
                'selesai' => '#10b981',
                'berjalan' => '#3b82f6',
                default => '#cbd5e1'
              };
            ?>
              <div class="col-md-6">
                <div class="sales-laporan-card" style="border-left-color: <?= $borderLeftColor; ?>;">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="sales-name-title">
                      <div class="avatar-initials-table" style="background: <?= $borderLeftColor; ?>; width: 32px; height: 32px; box-shadow: none; font-size:11px;">
                        <?php 
                          $words = explode(' ', $row['nama_sales'] ?? '');
                          echo strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        ?>
                      </div>
                      <?= htmlspecialchars($row['nama_sales'] ?? '-'); ?>
                    </span>
                    <span class="status-badge-sales <?= $badgeClass; ?>">
                      <?= htmlspecialchars($row['status'] ?? 'Dijadwalkan'); ?>
                    </span>
                  </div>

                  <!-- Waktu Absensi (Clock In & Clock Out) -->
                  <div class="row g-2 mb-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; margin-left: 0; margin-right: 0;">
                    <div class="col-6" style="border-right: 1px solid #cbd5e1; padding-right: 10px;">
                      <span class="info-label" style="margin-bottom: 2px; font-size: 9px; color: #10b981; gap: 4px; text-transform: uppercase;">
                        <span class="material-symbols-outlined" style="font-size: 13px;">login</span> Clock In
                      </span>
                      <span style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; font-family: monospace;">
                        <?= !empty($row['ci_at']) ? date('d M, H:i', strtotime($row['ci_at'])) . ' WIB' : '<span class="text-muted font-weight-normal" style="font-family:sans-serif; font-size:11px;">Belum In</span>'; ?>
                      </span>
                      <?php if (!empty($row['lat_ci']) && !empty($row['lon_ci'])): ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['lat_ci']; ?>,<?= $row['lon_ci']; ?>" target="_blank" style="font-size: 10.5px; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 2px; margin-top: 4px; font-weight:700;">
                          <span class="material-symbols-outlined" style="font-size:12px;">location_on</span> Lokasi CI
                        </a>
                      <?php endif; ?>
                    </div>
                    <div class="col-6" style="padding-left: 14px;">
                      <span class="info-label" style="margin-bottom: 2px; font-size: 9px; color: #ef4444; gap: 4px; text-transform: uppercase;">
                        <span class="material-symbols-outlined" style="font-size: 13px;">logout</span> Clock Out
                      </span>
                      <span style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; font-family: monospace;">
                        <?= !empty($row['co_at']) ? date('d M, H:i', strtotime($row['co_at'])) . ' WIB' : '<span class="text-muted font-weight-normal" style="font-family:sans-serif; font-size:11px;">Belum Out</span>'; ?>
                      </span>
                      <?php if (!empty($row['lat_co']) && !empty($row['lon_co'])): ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['lat_co']; ?>,<?= $row['lon_co']; ?>" target="_blank" style="font-size: 10.5px; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 2px; margin-top: 4px; font-weight:700;">
                          <span class="material-symbols-outlined" style="font-size:12px;">location_on</span> Lokasi CO
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Catatan Kunjungan -->
                  <?php 
                  $catatan = !empty($row['catatan_visit']) ? $row['catatan_visit'] : (!empty($row['keterangan']) ? $row['keterangan'] : '');
                  if (!empty($catatan)): 
                  ?>
                    <div class="mb-3">
                      <span class="info-label" style="margin-bottom: 4px;">Catatan Kunjungan</span>
                      <div class="laporan-note">
                        "<?= htmlspecialchars($catatan); ?>"
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- Dokumentasi Foto (Menggunakan path server api-teknisi.id-giti.com yang benar) -->
                  <?php if (!empty($row['image_1']) || !empty($row['image_2']) || !empty($row['image_3'])): ?>
                    <div class="mb-3">
                      <span class="info-label" style="margin-bottom: 6px;">Foto Dokumentasi Lapangan</span>
                      <div class="row g-2">
                        <?php foreach (['image_1', 'image_2', 'image_3'] as $img): ?>
                          <?php if (!empty($row[$img])): ?>
                            <div class="col-4">
                              <a href="https://api-teknisi.id-giti.com/storage/image/<?php echo $row[$img]; ?>" target="_blank" title="Buka Foto Ukuran Penuh">
                                <div class="doc-img-wrapper">
                                  <img src="https://api-teknisi.id-giti.com/storage/image/<?php echo $row[$img]; ?>" class="doc-img" alt="Dokumentasi">
                                </div>
                              </a>
                            </div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- Rekaman Audio (Menggunakan path server api-teknisi.id-giti.com yang benar) -->
                  <?php if (!empty($row['record'])): ?>
                    <div>
                      <span class="info-label" style="margin-bottom: 6px;">Rekaman Laporan Suara</span>
                      <audio controls class="premium-audio">
                        <source src="https://api-teknisi.id-giti.com/storage/record/<?php echo $row['record']; ?>" type="audio/mpeg">
                        <source src="https://api-teknisi.id-giti.com/storage/record/<?php echo $row['record']; ?>" type="audio/aac">
                        <source src="https://api-teknisi.id-giti.com/storage/record/<?php echo $row['record']; ?>" type="audio/x-aac">
                        <source src="https://api-teknisi.id-giti.com/storage/record/<?php echo $row['record']; ?>" type="audio/mp4">
                        Browser Anda tidak mendukung pemutar suara.
                      </audio>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <span class="material-symbols-outlined" style="font-size: 40px; color: #cbd5e1; display: block; margin-bottom: 8px;">groups</span>
              Belum ada sales terdaftar untuk kegiatan kunjungan ini.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php
      include "floating-menu.php";
      include "footer.php";
      ?>
    </div>

  </main>
  
  <?php include "js-include.php"; ?>
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