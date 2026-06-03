<?php
if (!function_exists('translateActivityStatus_x')) {
  function translateActivityStatus_x($status) {
    $statusMap = ['waiting' => 'Dalam Antrian', 'dijadwalkan' => 'Dijadwalkan', 'berjalan' => 'Dalam Proses', 'selesai' => 'Selesai', 'selesai by admin' => 'Diselesaikan Admin', 'Lanjut Nanti' => 'Berlanjut', 'Lanjutan' => 'Dilanjutkan'];
    return $statusMap[$status] ?? ucfirst($status);
  }
}
if (!function_exists('formatWhatsappNumber_x')) {
  function formatWhatsappNumber_x($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 1) === '0') return '62' . substr($number, 1);
    return $number;
  }
}
if (!function_exists('shortenTechnicianName_x')) {
  function shortenTechnicianName_x($fullName) {
    if (empty($fullName)) return '-';
    $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad'];
    $words = explode(" ", $fullName);
    if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
    $shortenedName = implode(" ", $words);
    if (strlen($shortenedName) > 15 && count($words) > 2) {
      $lastWordIndex = count($words) - 1;
      if (isset($words[$lastWordIndex][0])) $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
      $shortenedName = implode(" ", $words);
    }
    return $shortenedName;
  }
}
if (!function_exists('getStatusBadgeClass_x')) {
  function getStatusBadgeClass_x($status) {
    $map = ['dijadwalkan' => 'badge-dijadwalkan', 'berjalan' => 'badge-dikerjakan', 'Lanjut Nanti' => 'badge-lanjut', 'Lanjutan' => 'badge-dilanjutkan'];
    return $map[$status] ?? 'badge-dijadwalkan';
  }
}
?>

<div class="col-lg-12 mt-4 mb-0">
  <div class="d-flex justify-content-between align-items-center section-header">
    <div class="d-flex align-items-center gap-2">
      <i class="material-icons">warning_amber</i>
      <h6>Tidak Terselesaikan</h6>
    </div>
    <input type="text" id="searchX" placeholder="Cari nama, kode, teknisi..." style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:6px;padding:6px 12px;font-size:12px;color:#fff;outline:none;width:220px;" onfocus="this.style.borderColor='rgba(255,255,255,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.2)'" oninput="filterRows(this.value,'listX')">
  </div>
</div>
<div class="col-lg-12 mt-0 mb-4">
  <div class="card section-card h-100 py-3">
    <?php
    $current_date = date("Y-m-d");
    $sql = "WITH RankedKegiatan AS (
        SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor,
            ROW_NUMBER() OVER(PARTITION BY k.kode ORDER BY k.jadwal DESC, k.id DESC) as rn
        FROM kegiatan k
        LEFT JOIN customer c ON k.customer_id = c.id
        WHERE k.deleted_at IS NULL
    )
    SELECT * FROM RankedKegiatan
    WHERE rn = 1
      AND status IN ('berjalan', 'dijadwalkan', 'Lanjutan', 'Lanjut Nanti')
      AND DATE(jadwal) < '$current_date'
    ORDER BY COALESCE(jadwal, '9999-12-31') DESC";
    $result = mysqli_query($conn, $sql);
    ?>
    <div class="card-body pb-0 p-0">
      <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="listX">
        <li class="list-group-item tbl-header d-md-block d-none">
          <div class="row px-3">
            <div class="col-md-2"><span class="tbl-th">Kegiatan</span></div>
            <div class="col-md-2"><span class="tbl-th">Customer</span></div>
            <div class="col-md-2"><span class="tbl-th">Teknisi & Status</span></div>
            <div class="col-md-2"><span class="tbl-th">Permintaan Dari</span></div>
            <div class="col-md-2"><span class="tbl-th">Jadwal</span></div>
            <div class="col-md-2 text-center"><span class="tbl-th">Aksi</span></div>
          </div>
        </li>
        <?php if (mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)):
            $kodeTransaksi = $row['kode'];
            $kegLower = strtolower($row['kegiatan'] ?? '');
            $badgeClass = 'badge-default';
            if (strpos($kegLower, 'survey') !== false) $badgeClass = 'badge-survey';
            elseif (strpos($kegLower, 'service') !== false) $badgeClass = 'badge-service';
            elseif (strpos($kegLower, 'pasang') !== false) $badgeClass = 'badge-pasang';
            $statusBadge = getStatusBadgeClass_x($row['status']);
            $daysOverdue = (int)((strtotime($current_date) - strtotime(date("Y-m-d", strtotime($row['jadwal'])))) / 86400);
          ?>
            <li class="list-group-item tbl-row">
              <div class="row px-3 w-100 align-items-start">
                <div class="col-md-2">
                  <?php if (!empty($row['kegiatan'])): ?>
                    <span class="badge-type <?= $badgeClass ?>"><?= htmlspecialchars($row['kegiatan']) ?></span>
                  <?php endif; ?>
                  <span class="badge-status <?= $statusBadge ?>" style="margin-top:4px;"><?= translateActivityStatus_x($row['status']) ?></span>
                  <span class="text-code"><?= $kodeTransaksi ?></span>
                </div>
                <div class="col-md-2">
                  <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" class="text-name d-block"><?= htmlspecialchars($row['nama_customer']) ?></a>
                  <a href="https://api.whatsapp.com/send?phone=<?= formatWhatsappNumber_x($row['cust_nomor']) ?>" target="_blank" class="text-phone"><?= htmlspecialchars($row['cust_nomor']) ?></a>
                </div>
                <div class="col-md-2">
                  <?php
                  $selTek = "SELECT tk.teknisi_id, tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE k.kode = ? AND k.id = (SELECT MAX(id) FROM kegiatan WHERE kode = ?) GROUP BY tk.teknisi_id";
                  $stmtTek = $conn->prepare($selTek);
                  $stmtTek->bind_param("ss", $kodeTransaksi, $kodeTransaksi);
                  $stmtTek->execute();
                  $resTek = $stmtTek->get_result();
                  while ($rowTek = $resTek->fetch_assoc()):
                  ?>
                    <div style="margin-bottom:4px;">
                      <a href="list-kegiatan-teknisi.php?idTek=<?= $rowTek['teknisi_id'] ?>" style="font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;"><?= shortenTechnicianName_x($rowTek['nama_teknisi']) ?></a>
                    </div>
                  <?php endwhile; $stmtTek->close(); ?>
                </div>
                <div class="col-md-2">
                  <p style="font-size:12px;font-weight:600;color:#1e293b;margin:0;"><?= htmlspecialchars($row['request'] ?? '-') ?></p>
                </div>
                <div class="col-md-2">
                  <p class="text-time"><?= date("d/m/Y", strtotime($row['jadwal'])) ?></p>
                  <p style="font-size:10px;color:#94a3b8;margin:0;"><?= date("H:i", strtotime($row['jadwal'])) ?> WIB</p>
                  <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;background:#fef2f2;color:#dc2626;display:inline-block;margin-top:4px;"><?= $daysOverdue ?> hari lalu</span>
                </div>
                <div class="col-md-2 text-center">
                  <div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">
                    <a class="btn-act btn-act-view" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Lihat"><i class="material-icons" style="font-size:14px;">visibility</i></a>
                    <button class="btn-act btn-act-edit edit-btn" data-id="<?= htmlspecialchars($kodeTransaksi) ?>" title="Reschedule"><i class="material-icons" style="font-size:14px;">autorenew</i></button>
                    <a class="btn-act btn-act-approve" href="selesaikan-kegiatan.php?kode=<?= urlencode($kodeTransaksi) ?>&id=<?= $row['id'] ?>" title="Selesaikan"><i class="material-icons" style="font-size:14px;">check</i></a>
                  </div>
                </div>
              </div>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="ms-4 text-sm py-4">Tidak ada kegiatan yang digantung</div>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<script>
function filterRows(query, listId) {
  var list = document.getElementById(listId);
  if (!list) return;
  var rows = list.querySelectorAll('.tbl-row');
  var q = query.toLowerCase().trim();
  var count = 0;
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    if (q === '' || text.indexOf(q) > -1) { row.style.display = ''; count++; }
    else { row.style.display = 'none'; }
  });
  var noResult = list.querySelector('.search-no-result');
  if (count === 0 && q !== '') {
    if (!noResult) { noResult = document.createElement('div'); noResult.className = 'search-no-result ms-4 text-sm py-4'; noResult.style.color = '#94a3b8'; list.appendChild(noResult); }
    noResult.textContent = 'Tidak ditemukan "' + query + '"';
    noResult.style.display = '';
  } else if (noResult) { noResult.style.display = 'none'; }
}
</script>