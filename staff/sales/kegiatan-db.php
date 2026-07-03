<?php
$current_date = date("Y-m-d");

// ── Hitung summary per tab ─────────────────────────────────────────────────
$tab_meta = [
  'hari-ini'    => ['label'=>'Hari Ini',     'condition'=>"DATE(ks.jadwal) = '$current_date'",                                           'icon'=>'today',        'color'=>'#3b82f6'],
  'akan-datang' => ['label'=>'Akan Datang',  'condition'=>"DATE(ks.jadwal) > '$current_date'",                                          'icon'=>'event',        'color'=>'#8b5cf6'],
  'terlewat'    => ['label'=>'Terlewat',     'condition'=>"DATE(ks.jadwal) < '$current_date' AND ks.status != 'selesai'",               'icon'=>'event_busy',   'color'=>'#ef4444'],
  'selesai'     => ['label'=>'Selesai',      'condition'=>"ks.status = 'selesai'",                                                       'icon'=>'task_alt',     'color'=>'#10b981'],
];

$counts = [];
foreach ($tab_meta as $k => $m) {
  $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM kegiatan_sales ks WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']}");
  $counts[$k] = ($r && ($row = mysqli_fetch_assoc($r))) ? (int)$row['c'] : 0;
}
?>

<!-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────────── -->
<div class="col-12 mb-4">
  <div class="row g-3">
    <?php foreach ($tab_meta as $k => $m): ?>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--accent:<?php echo $m['color']; ?>" onclick="document.getElementById('tab-<?php echo $k;?>').click()">
        <div class="stat-icon">
          <span class="material-symbols-outlined"><?php echo $m['icon']; ?></span>
        </div>
        <div class="stat-body">
          <div class="stat-count"><?php echo $counts[$k]; ?></div>
          <div class="stat-label"><?php echo $m['label']; ?></div>
        </div>
        <div class="stat-progress-bar"></div>
      </div>
    </div>
    <?php endforeach; ?>
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
              WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']}
              ORDER BY ks.jadwal ASC";
      $result = mysqli_query($conn, $sql);
    ?>
    <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
         id="pane-<?php echo $k; ?>" role="tabpanel">

      <div class="p-3 bg-white">
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
            $sqlSales  = "SELECT s.nama AS nama_sales, ps.status AS status_pelaksanaan
                          FROM team_kegiatan_sales tks
                          LEFT JOIN sales s ON tks.id_sales = s.id
                          LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
                          WHERE tks.id_kegiatan_sales = '$kegiatanId' AND tks.deleted_at IS NULL";
            $resSales  = mysqli_query($conn, $sqlSales);
            while ($s = mysqli_fetch_assoc($resSales)) {
              $st   = strtolower($s['status_pelaksanaan'] ?? 'dijadwalkan');
              $cls  = match($st) { 'berjalan'=>'status-running','selesai'=>'status-done', default=>'status-scheduled' };
              $lbl  = match($st) { 'berjalan'=>'Berjalan','selesai'=>'Selesai', default=>'Dijadwalkan' };
              $salesList[] = ['nama'=>$s['nama_sales'],'cls'=>$cls,'lbl'=>$lbl];
            }

            // warna left-border berdasarkan tab
            $borderColor = $m['color'];
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
                    <span class="sales-status-badge <?php echo $sl['cls']; ?>"><?php echo $sl['lbl']; ?></span>
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
          <div class="empty-state-premium" style="--accent: <?php echo $m['color']; ?>;">
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
/* ── Stat Cards ── */
.stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 20px 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
  border: 1px solid #f1f5f9;
  border-left: 5px solid var(--accent);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}
.stat-card:hover { 
  transform: translateY(-4px); 
  box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
  border-color: color-mix(in srgb, var(--accent) 30%, #fff);
}
.stat-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--accent) 10%, transparent);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: transform 0.25s;
}
.stat-card:hover .stat-icon {
  transform: scale(1.1) rotate(5deg);
}
.stat-icon .material-symbols-outlined { font-size: 24px; color: var(--accent); }
.stat-count { font-size: 28px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-label { font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-progress-bar {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent);
  opacity: 0.15;
  transition: opacity 0.25s;
}
.stat-card:hover .stat-progress-bar {
  opacity: 0.4;
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
  background: #fff; 
  border-radius: 0 0 16px 16px; 
  padding: 0; 
  overflow: hidden; 
  box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
  border: 1px solid #e2e8f0;
}

.keg-header {
  display: grid;
  grid-template-columns: 140px 1.2fr 1.2fr 1.5fr 110px;
  gap: 16px;
  padding: 12px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px;
}
.keg-row {
  display: grid;
  grid-template-columns: 140px 1.2fr 1.2fr 1.5fr 110px;
  gap: 16px;
  padding: 20px 24px;
  background: #fff;
  border-radius: 12px;
  margin-bottom: 12px;
  border: 1px solid #f1f5f9;
  border-left: 5px solid var(--row-accent, #3b82f6);
  transition: all 0.22s ease;
  align-items: center;
}
.keg-row:hover { 
  background: #fff; 
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
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
