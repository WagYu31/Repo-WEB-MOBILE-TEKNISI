<?php
$current_date = date("Y-m-d");
$selectedWilayah = $_SESSION['selected_wilayah'] ?? 'all';

// ── Hitung summary per tab ─────────────────────────────────────────────────
$tab_meta = [
  'hari-ini'    => ['label'=>'Hari Ini',     'condition'=>"DATE(ks.jadwal) = '$current_date'",                                           'icon'=>'today',        'color'=>'#1e293b'],
  'akan-datang' => ['label'=>'Akan Datang',  'condition'=>"DATE(ks.jadwal) > '$current_date'",                                          'icon'=>'event',        'color'=>'#3b82f6'],
  'terlewat'    => ['label'=>'Terlewat',     'condition'=>"DATE(ks.jadwal) < '$current_date' AND ks.status != 'selesai'",               'icon'=>'event_busy',   'color'=>'#ef4444'],
  'selesai'     => ['label'=>'Selesai',      'condition'=>"ks.status = 'selesai'",                                                       'icon'=>'task_alt',     'color'=>'#10b981'],
];

$counts = [];
foreach ($tab_meta as $k => $m) {
  $queryStr = "SELECT COUNT(ks.id) AS c FROM kegiatan_sales ks ";
  if ($selectedWilayah !== 'all') {
      $queryStr .= "LEFT JOIN sales_customer c ON ks.id_customer = c.id ";
      $queryStr .= "WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND c.id_wilayah = '$selectedWilayah' AND {$m['condition']}";
  } else {
      $queryStr .= "WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']}";
  }
  
  $r = mysqli_query($conn, $queryStr);
  $counts[$k] = ($r && ($row = mysqli_fetch_assoc($r))) ? (int)$row['c'] : 0;
}
?>

<!-- ── SUMMARY STAT CARDS (Sama dengan Web Teknisi) ─────────────────────────── -->
<div class="col-12 mb-4">
  <div class="row g-3">
    <!-- Card 1: Hari Ini -->
    <div class="col-6 col-md-3">
      <div class="stat-card-premium" style="background: linear-gradient(135deg,#1e293b 0%,#334155 100%);" onclick="document.getElementById('tab-hari-ini').click()">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="stat-label-premium">Hari Ini</p>
              <h3 class="stat-count-premium"><?php echo $counts['hari-ini']; ?></h3>
            </div>
            <div class="stat-icon-premium">
              <span class="material-symbols-outlined">event_available</span>
            </div>
          </div>
        </div>
        <div class="stat-footer-premium">
          <p><?php echo date('d F Y'); ?></p>
        </div>
      </div>
    </div>

    <!-- Card 2: Akan Datang -->
    <div class="col-6 col-md-3">
      <div class="stat-card-premium" style="background: linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);" onclick="document.getElementById('tab-akan-datang').click()">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="stat-label-premium">Akan Datang</p>
              <h3 class="stat-count-premium"><?php echo $counts['akan-datang']; ?></h3>
            </div>
            <div class="stat-icon-premium">
              <span class="material-symbols-outlined">event</span>
            </div>
          </div>
        </div>
        <div class="stat-footer-premium">
          <p>Jadwal Mendatang</p>
        </div>
      </div>
    </div>

    <!-- Card 3: Terlewat -->
    <div class="col-6 col-md-3">
      <div class="stat-card-premium" style="background: linear-gradient(135deg,#dc2626 0%,#ef4444 100%);" onclick="document.getElementById('tab-terlewat').click()">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="stat-label-premium">Terlewat</p>
              <h3 class="stat-count-premium"><?php echo $counts['terlewat']; ?></h3>
            </div>
            <div class="stat-icon-premium">
              <span class="material-symbols-outlined">warning_amber</span>
            </div>
          </div>
        </div>
        <div class="stat-footer-premium">
          <p>Perlu Tindak Lanjut</p>
        </div>
      </div>
    </div>

    <!-- Card 4: Selesai -->
    <div class="col-6 col-md-3">
      <div class="stat-card-premium" style="background: linear-gradient(135deg,#15803d 0%,#22c55e 100%);" onclick="document.getElementById('tab-selesai').click()">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="stat-label-premium">Selesai</p>
              <h3 class="stat-count-premium"><?php echo $counts['selesai']; ?></h3>
            </div>
            <div class="stat-icon-premium">
              <span class="material-symbols-outlined">check_circle</span>
            </div>
          </div>
        </div>
        <div class="stat-footer-premium">
          <p>Kunjungan Selesai</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── TAB NAVIGATION ─────────────────────────────────────────────────────── -->
<div class="col-12 mb-0">
  <div class="tab-pills-wrapper">
    <ul class="nav tab-pills" id="kegiatanTab" role="tablist">
      <?php $first = true; foreach ($tab_meta as $k => $m): ?>
      <li class="nav-item" role="presentation">
        <button class="tab-pill <?php echo $first ? 'active' : ''; ?>"
                id="tab-<?php echo $k; ?>"
                data-bs-toggle="tab"
                data-bs-target="#pane-<?php echo $k; ?>"
                type="button" role="tab"
                style="--accent:<?php echo $m['color']; ?>">
          <span class="material-symbols-outlined tab-icon"><?php echo $m['icon']; ?></span>
          <?php echo $m['label']; ?>
          <span class="tab-badge" style="background:<?php echo $m['color']; ?>"><?php echo $counts[$k]; ?></span>
        </button>
      </li>
      <?php $first = false; endforeach; ?>
    </ul>
  </div>
</div>

<!-- ── TAB CONTENT ────────────────────────────────────────────────────────── -->
<div class="col-12">
  <div class="tab-content" id="kegiatanTabContent">
    <?php $first = true; foreach ($tab_meta as $k => $m):
      $sql = "SELECT ks.*, c.nama AS nama_customer, c.telp_pribadi AS cust_nomor, c.alamat, c.id AS customer_id
              FROM kegiatan_sales ks
              LEFT JOIN sales_customer c ON ks.id_customer = c.id
              WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']} ";
      
      if ($selectedWilayah !== 'all') {
          $sql .= "AND c.id_wilayah = '$selectedWilayah' ";
      }
      
      $sql .= "ORDER BY ks.jadwal ASC";
      $result = mysqli_query($conn, $sql);
      $borderColor = $m['color'];
    ?>
    <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
         id="pane-<?php echo $k; ?>" role="tabpanel">

      <!-- Section Header (Sama dengan Web Teknisi) -->
      <div class="section-header-premium">
        <h6>
          <span class="material-symbols-outlined" style="font-size: 18px; color: #fff; vertical-align: middle; margin-right: 6px;"><?php echo $m['icon']; ?></span>
          Kegiatan <?php echo $m['label']; ?>
        </h6>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-light text-dark font-weight-bold" style="font-size: 11px;"><?php echo $counts[$k]; ?> Kunjungan</span>
        </div>
      </div>

      <div class="keg-list-container">
        <?php if (mysqli_num_rows($result) > 0): ?>

          <!-- Desktop header -->
          <div class="keg-header d-none d-md-grid">
            <div>Jadwal</div>
            <div>Customer</div>
            <div>Sales &amp; Status</div>
            <div>Alamat</div>
            <div class="text-center">Aksi</div>
          </div>

          <?php while ($row = mysqli_fetch_assoc($result)):
            $kegiatanId = $row['id'];
            $jadwal     = date("d M Y H:i", strtotime($row['jadwal']));
            $telp       = $row['cust_nomor'] ?? '';
            if ($telp && substr($telp, 0, 1) === '0') $telp = '62' . substr($telp, 1);

            // Ambil sales
            $salesList = [];
            $sqlSales  = "SELECT s.nama AS nama_sales, ps.status AS status_pelaksanaan, ps.ci_at, ps.co_at
                          FROM team_kegiatan_sales tks
                          LEFT JOIN sales s ON tks.id_sales = s.id
                          LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
                          WHERE tks.id_kegiatan_sales = '$kegiatanId' AND tks.deleted_at IS NULL";
            $resSales  = mysqli_query($conn, $sqlSales);
            while ($s = mysqli_fetch_assoc($resSales)) {
              $st   = strtolower($s['status_pelaksanaan'] ?? 'dijadwalkan');
              $cls  = match($st) { 'berjalan'=>'status-running','selesai'=>'status-done', default=>'status-scheduled' };
              $lbl  = match($st) { 'berjalan'=>'Berjalan','selesai'=>'Selesai', default=>'Dijadwalkan' };
              $ci_time = !empty($s['ci_at']) ? date('H:i', strtotime($s['ci_at'])) : '';
              $co_time = !empty($s['co_at']) ? date('H:i', strtotime($s['co_at'])) : '';
              
              $salesList[] = [
                'nama' => $s['nama_sales'],
                'cls' => $cls,
                'lbl' => $lbl,
                'ci_time' => $ci_time,
                'co_time' => $co_time
              ];
            }
          ?>

          <div class="keg-row" style="--row-accent:<?php echo $borderColor; ?>">
            <!-- Jadwal -->
            <div class="keg-cell">
              <span class="cell-label d-md-none">Jadwal</span>
              <div class="jadwal-badge">
                <span class="material-symbols-outlined" style="font-size:16px;color:<?php echo $borderColor;?>">schedule</span>
                <?php echo $jadwal; ?> WIB
              </div>
            </div>

            <!-- Customer -->
            <div class="keg-cell">
              <span class="cell-label d-md-none">Customer</span>
              <div class="customer-name">
                <a href="customer-detail.php?id_cust=<?php echo $row['customer_id']; ?>" class="cust-link">
                  <?php echo htmlspecialchars($row['nama_customer'] ?? '-'); ?>
                </a>
              </div>
              <?php if ($telp): ?>
              <a href="https://api.whatsapp.com/send?phone=<?php echo $telp;?>" target="_blank" class="wa-link">
                <i class="fab fa-whatsapp"></i> <?php echo $row['cust_nomor']; ?>
              </a>
              <?php endif; ?>
            </div>

            <!-- Sales -->
            <div class="keg-cell">
              <span class="cell-label d-md-none">Sales</span>
              <?php if (count($salesList) > 0): ?>
                <?php foreach ($salesList as $sl): ?>
                <div class="sales-item d-flex align-items-center gap-2 mb-2">
                  <div class="avatar-initials" style="background-color: <?php echo $borderColor; ?>;">
                    <?php 
                      $words = explode(' ', $sl['nama']);
                      echo strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    ?>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="sales-name"><?php echo htmlspecialchars($sl['nama']); ?></span>
                    <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                      <span class="sales-status-badge <?php echo $sl['cls']; ?>"><?php echo $sl['lbl']; ?></span>
                      <?php if (!empty($sl['ci_time'])): ?>
                        <span class="badge bg-light text-success font-weight-bold" style="font-size: 9px; padding: 2px 6px; border: 1px solid #d1fae5; border-radius: 4px; font-family: monospace; text-transform: uppercase;" title="Jam Clock In">
                          📥 IN: <?php echo $sl['ci_time']; ?>
                        </span>
                      <?php endif; ?>
                      <?php if (!empty($sl['co_time'])): ?>
                        <span class="badge bg-light text-danger font-weight-bold" style="font-size: 9px; padding: 2px 6px; border: 1px solid #fee2e2; border-radius: 4px; font-family: monospace; text-transform: uppercase;" title="Jam Clock Out">
                          📤 OUT: <?php echo $sl['co_time']; ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-muted" style="font-size:12px">Belum ada sales</span>
              <?php endif; ?>
            </div>

            <!-- Alamat -->
            <div class="keg-cell">
              <span class="cell-label d-md-none">Alamat</span>
              <div class="alamat-text">
                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; margin-right: 2px;">location_on</span>
                <?php echo htmlspecialchars($row['alamat'] ?? '-'); ?>
              </div>
            </div>

            <!-- Aksi -->
            <div class="keg-cell keg-actions">
              <a href="detail_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action btn-view" title="Lihat Detail">
                <span class="material-symbols-outlined">visibility</span>
              </a>
              <a href="edit_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action btn-edit" title="Edit Jadwal">
                <span class="material-symbols-outlined">edit</span>
              </a>
            </div>
          </div>

          <?php endwhile; ?>

        <?php else: ?>
          <div class="empty-state-premium" style="--accent: <?php echo $borderColor; ?>;">
            <div class="empty-icon-wrapper">
              <span class="material-symbols-outlined empty-icon-pulsing"><?php echo $m['icon']; ?></span>
            </div>
            <h5 class="empty-title mt-3">Tidak Ada Kegiatan</h5>
            <p class="empty-sub text-muted">Belum ada jadwal kunjungan untuk kategori <strong>"<?php echo $m['label']; ?>"</strong></p>
          </div>
        <?php endif; ?>
      </div>

    </div>
    <?php $first = false; endforeach; ?>
  </div>
</div>

<!-- ── STYLES ─────────────────────────────────────────────────────────────── -->
<style>
/* ── Premium Stat Cards (Sama dengan Web Teknisi) ── */
.stat-card-premium {
  border: none;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}
.stat-card-premium:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}
.stat-label-premium {
  font-size: 10px;
  font-weight: 700;
  color: rgba(255,255,255,0.6);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 0 0 6px 0;
}
.stat-count-premium {
  font-size: 32px;
  font-weight: 800;
  color: #fff;
  margin: 0;
  line-height: 1;
}
.stat-icon-premium {
  width: 42px; height: 42px;
  border-radius: 10px;
  background: rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
  color: rgba(255,255,255,0.8);
  transition: transform 0.25s;
}
.stat-card-premium:hover .stat-icon-premium {
  transform: scale(1.1) rotate(5deg);
}
.stat-icon-premium .material-symbols-outlined {
  font-size: 22px;
  color: #fff;
}
.stat-footer-premium {
  padding: 8px 16px;
  background: rgba(0,0,0,0.15);
}
.stat-footer-premium p {
  font-size: 10px;
  color: rgba(255,255,255,0.5);
  margin: 0;
}

/* ── Section Header Premium (Sama dengan Web Teknisi) ── */
.section-header-premium {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px; background: #1e293b; border-radius: 10px 10px 0 0;
  transition: background 0.2s;
  margin-top: 15px;
}
.section-header-premium h6 { 
  margin: 0; font-size: 13px; font-weight: 700; color: #fff; 
  letter-spacing: 0.04em; text-transform: uppercase;
  display: flex; align-items: center;
}

/* ── Tab Pills ── */
.tab-pills-wrapper {
  background: #f8fafc;
  border-radius: 16px 16px 0 0;
  padding: 12px 14px 0;
  border: 1px solid #e2e8f0;
  border-bottom: none;
}
.tab-pills { gap: 6px; flex-wrap: wrap; border-bottom: none; }
.tab-pill {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 20px;
  border-radius: 12px 12px 0 0;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 14px; font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.tab-pill:hover { background: rgba(0,0,0,0.03); color: #1e293b; }
.tab-pill.active {
  background: #fff;
  color: var(--accent);
  box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
}
.tab-icon { font-size: 18px; }
.tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 22px; height: 22px; padding: 0 8px;
  border-radius: 20px;
  color: #fff;
  font-size: 11px; font-weight: 700;
  margin-left: 4px;
}

/* ── Kegiatan List Pane ── */
.tab-pane { 
  background: transparent !important; 
  box-shadow: none !important; 
  border: none !important;
}
.keg-list-container {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 12px 12px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.02);
}

.keg-header {
  display: grid;
  grid-template-columns: 140px 1.2fr 1.2fr 1.5fr 110px;
  gap: 16px;
  padding: 12px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px;
  margin-bottom: 10px;
}
.keg-row {
  display: grid;
  grid-template-columns: 140px 1.2fr 1.2fr 1.5fr 110px;
  gap: 16px;
  padding: 18px 20px;
  background: #fff;
  border-radius: 12px;
  margin-bottom: 12px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid var(--row-accent, #3b82f6);
  transition: all 0.22s ease;
  align-items: center;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
}
.keg-row:hover { 
  background: #fff; 
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
  border-color: var(--row-accent);
}
.keg-cell { padding: 0; }
.cell-label { display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }

.jadwal-badge { 
  display: inline-flex; 
  align-items: center; 
  gap: 6px; 
  font-size: 13px; 
  color: #334155; 
  font-weight: 600; 
  background: #f8fafc;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.customer-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.cust-link { color: #1e293b; text-decoration: none; transition: color 0.15s; }
.cust-link:hover { color: #3b82f6; }
.wa-link { font-size: 12px; color: #10b981; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; font-weight: 500; }
.wa-link:hover { text-decoration: underline; }

/* Sales item style */
.avatar-initials {
  width: 32px; height: 32px;
  border-radius: 50%;
  color: #fff;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.sales-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.sales-status-badge { 
  font-size: 10px; 
  font-weight: 700; 
  padding: 2px 8px; 
  border-radius: 12px; 
  white-space: nowrap; 
  width: fit-content;
}
.sales-status-badge.status-scheduled { background: #f1f5f9; color: #64748b; }
.sales-status-badge.status-running { background: #dbeafe; color: #2563eb; }
.sales-status-badge.status-done { background: #d1fae5; color: #059669; }

.alamat-text { font-size: 13px; color: #475569; line-height: 1.5; font-weight: 400; }

.keg-actions { display: flex; gap: 8px; align-items: center; justify-content: center; }
.btn-action {
  width: 36px; height: 36px; border-radius: 10px;
  display: inline-flex; align-items: center; justify-content: center;
  text-decoration: none; transition: all 0.2s;
  border: 1px solid transparent;
}
.btn-action:hover { transform: scale(1.08); }
.btn-action .material-symbols-outlined { font-size: 18px; }
.btn-view { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.btn-view:hover { background: #2563eb; color: #fff; }
.btn-edit { background: #ecfdf5; color: #059669; border-color: #d1fae5; }
.btn-edit:hover { background: #059669; color: #fff; }

/* ── Premium Empty State ── */
.empty-state-premium { 
  text-align: center; 
  padding: 60px 40px; 
  background: #fff; 
  border-radius: 16px; 
  border: 2px dashed #e2e8f0;
  max-width: 480px;
  margin: 30px auto;
}
.empty-icon-wrapper {
  width: 80px; height: 80px;
  margin: 0 auto;
  background: color-mix(in srgb, var(--accent) 10%, transparent);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.empty-icon-pulsing {
  font-size: 38px;
  color: var(--accent);
  animation: pulse 2s infinite ease-in-out;
}
.empty-title { font-size: 16px; font-weight: 700; color: #334155; }
.empty-sub { font-size: 13px; color: #64748b; margin-top: 6px; }

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.8; }
  50% { transform: scale(1.08); opacity: 1; }
  100% { transform: scale(1); opacity: 0.8; }
}

/* ── Mobile Responsive ── */
@media (max-width: 767px) {
  .keg-header { display: none; }
  .keg-row {
    display: flex; flex-direction: column; gap: 12px;
    border-left-width: 5px;
    padding: 16px 20px;
    border-radius: 14px;
    align-items: stretch;
  }
  .keg-cell { padding: 0; }
  .keg-actions { justify-content: flex-start; margin-top: 4px; }
  .stat-count { font-size: 24px; }
  .empty-state-premium { padding: 40px 20px; margin: 15px auto; }
}
</style>
