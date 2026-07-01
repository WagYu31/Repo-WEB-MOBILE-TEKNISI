<?php
$current_date = date("Y-m-d");

// ── Hitung summary per tab ─────────────────────────────────────────────────
$tab_meta = [
  'hari-ini'    => ['label'=>'Hari Ini',     'condition'=>"DATE(ks.jadwal) = '$current_date'",                                           'icon'=>'today',        'color'=>'#4A90D9'],
  'akan-datang' => ['label'=>'Akan Datang',  'condition'=>"DATE(ks.jadwal) > '$current_date'",                                          'icon'=>'event',        'color'=>'#7B61FF'],
  'terlewat'    => ['label'=>'Terlewat',     'condition'=>"DATE(ks.jadwal) < '$current_date' AND ks.status != 'selesai'",               'icon'=>'event_busy',   'color'=>'#F5536A'],
  'selesai'     => ['label'=>'Selesai',      'condition'=>"ks.status = 'selesai'",                                                       'icon'=>'task_alt',     'color'=>'#22C55E'],
];

$counts = [];
foreach ($tab_meta as $k => $m) {
  $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM kegiatan_sales ks WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']}");
  $counts[$k] = ($r && ($row = mysqli_fetch_assoc($r))) ? (int)$row['c'] : 0;
}
?>

<!-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────────── -->
<div class="col-12 mb-3">
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
      $sql = "SELECT ks.*, c.nama AS nama_customer, c.telp_pribadi AS cust_nomor, c.alamat
              FROM kegiatan_sales ks
              LEFT JOIN sales_customer c ON ks.id_customer = c.id
              WHERE ks.status != 'waiting' AND ks.deleted_at IS NULL AND {$m['condition']}
              ORDER BY ks.jadwal ASC";
      $result = mysqli_query($conn, $sql);
    ?>
    <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
         id="pane-<?php echo $k; ?>" role="tabpanel">

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
              <span class="material-symbols-outlined" style="font-size:14px;color:<?php echo $borderColor;?>">schedule</span>
              <?php echo $jadwal; ?>
            </div>
          </div>

          <!-- Customer -->
          <div class="keg-cell">
            <span class="cell-label d-md-none">Customer</span>
            <div class="customer-name"><?php echo htmlspecialchars($row['nama_customer'] ?? '-'); ?></div>
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
              <div class="sales-item">
                <span class="sales-name"><?php echo htmlspecialchars($sl['nama']); ?></span>
                <span class="sales-status <?php echo $sl['cls']; ?>"><?php echo $sl['lbl']; ?></span>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="text-muted" style="font-size:12px">Belum ada sales</span>
            <?php endif; ?>
          </div>

          <!-- Alamat -->
          <div class="keg-cell">
            <span class="cell-label d-md-none">Alamat</span>
            <div class="alamat-text"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></div>
          </div>

          <!-- Aksi -->
          <div class="keg-cell keg-actions">
            <a href="detail_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action btn-view">
              <span class="material-symbols-outlined">visibility</span>
            </a>
            <a href="edit_kegiatan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action btn-edit">
              <span class="material-symbols-outlined">edit</span>
            </a>
          </div>
        </div>

        <?php endwhile; ?>

      <?php else: ?>
        <div class="empty-state">
          <span class="material-symbols-outlined empty-icon"><?php echo $m['icon']; ?></span>
          <div class="empty-title">Tidak ada kegiatan</div>
          <div class="empty-sub">Belum ada jadwal untuk kategori "<?php echo $m['label']; ?>"</div>
        </div>
      <?php endif; ?>

    </div>
    <?php $first = false; endforeach; ?>
  </div>
</div>

<!-- ── STYLES ─────────────────────────────────────────────────────────────── -->
<style>
/* ── Stat Cards ── */
.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  border-left: 4px solid var(--accent);
  cursor: pointer;
  transition: transform .18s, box-shadow .18s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
.stat-icon {
  width: 44px; height: 44px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--accent) 15%, transparent);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.stat-icon .material-symbols-outlined { font-size: 22px; color: var(--accent); }
.stat-count { font-size: 26px; font-weight: 700; color: #1a1a2e; line-height: 1; }
.stat-label { font-size: 11px; color: #6b7280; margin-top: 2px; font-weight: 500; }

/* ── Tab Pills ── */
.tab-pills-wrapper {
  background: #f5f6fa;
  border-radius: 14px 14px 0 0;
  padding: 10px 10px 0;
}
.tab-pills { gap: 4px; flex-wrap: wrap; border-bottom: none; }
.tab-pill {
  display: flex; align-items: center; gap: 5px;
  padding: 8px 16px;
  border-radius: 10px 10px 0 0;
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 13px; font-weight: 500;
  cursor: pointer;
  transition: background .15s, color .15s;
  position: relative;
}
.tab-pill:hover { background: rgba(0,0,0,0.05); color: #374151; }
.tab-pill.active {
  background: #fff;
  color: var(--accent);
  box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
}
.tab-icon { font-size: 16px; }
.tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 20px; height: 20px; padding: 0 6px;
  border-radius: 20px;
  color: #fff;
  font-size: 10px; font-weight: 700;
}

/* ── Kegiatan List ── */
.tab-pane { background: #fff; border-radius: 0 14px 14px 14px; padding: 0; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }

.keg-header {
  display: grid;
  grid-template-columns: 130px 1fr 1fr 1.5fr 90px;
  gap: 0;
  padding: 10px 20px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px;
}
.keg-row {
  display: grid;
  grid-template-columns: 130px 1fr 1fr 1.5fr 90px;
  gap: 0;
  padding: 14px 20px;
  border-bottom: 1px solid #f3f4f6;
  border-left: 3px solid var(--row-accent, #4A90D9);
  transition: background .15s;
  align-items: start;
}
.keg-row:hover { background: #fafbff; }
.keg-row:last-child { border-bottom: none; }
.keg-cell { padding: 0 8px; }
.cell-label { display: block; font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; margin-bottom: 3px; }

.jadwal-badge { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #374151; font-weight: 500; }
.customer-name { font-size: 13px; font-weight: 600; color: #111827; }
.wa-link { font-size: 11px; color: #22c55e; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; margin-top: 2px; }
.wa-link:hover { text-decoration: underline; }
.sales-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; gap: 8px; }
.sales-name { font-size: 12px; font-weight: 500; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; }
.sales-status { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.status-scheduled { background: #f3f4f6; color: #6b7280; }
.status-running    { background: #dbeafe; color: #2563eb; }
.status-done       { background: #dcfce7; color: #16a34a; }
.alamat-text { font-size: 12px; color: #6b7280; line-height: 1.4; }
.keg-actions { display: flex; gap: 6px; align-items: flex-start; justify-content: center; }
.btn-action {
  width: 32px; height: 32px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  text-decoration: none; transition: transform .15s, opacity .15s;
}
.btn-action:hover { transform: scale(1.1); }
.btn-action .material-symbols-outlined { font-size: 16px; }
.btn-view { background: #eff6ff; color: #3b82f6; }
.btn-edit { background: #f0fdf4; color: #22c55e; }

/* ── Empty State ── */
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon { font-size: 56px; color: #d1d5db; display: block; margin-bottom: 12px; }
.empty-title { font-size: 15px; font-weight: 600; color: #6b7280; }
.empty-sub   { font-size: 13px; color: #9ca3af; margin-top: 4px; }

/* ── Mobile Responsive ── */
@media (max-width: 767px) {
  .keg-header { display: none; }
  .keg-row {
    display: flex; flex-direction: column; gap: 8px;
    border-left-width: 4px;
    padding: 14px 16px;
  }
  .keg-cell { padding: 0; }
  .keg-actions { justify-content: flex-start; }
  .stat-count { font-size: 22px; }
}
</style>
