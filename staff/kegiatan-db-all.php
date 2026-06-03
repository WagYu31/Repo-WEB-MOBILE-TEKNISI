<?php
if (!function_exists('formatWhatsappNumber_all')) {
  function formatWhatsappNumber_all($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 1) === '0') return '62' . substr($number, 1);
    return $number;
  }
}
if (!function_exists('shortenTechnicianName_all')) {
  function shortenTechnicianName_all($fullName) {
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
?>

<div class="col-lg-12 mt-4 mb-0">
  <div class="d-flex justify-content-between align-items-center section-header">
    <div class="d-flex align-items-center gap-2">
      <i class="material-icons">check_circle</i>
      <h6>Selesai</h6>
    </div>
    <div class="d-flex align-items-center" style="position:relative;">
      <i class="material-icons" style="position:absolute;left:10px;font-size:16px;color:#94a3b8;pointer-events:none;">search</i>
      <input type="text" id="searchAll" placeholder="Cari nama, kode, teknisi..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:240px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="filterRows(this.value,'listAll')">
    </div>
  </div>
</div>
<div class="col-lg-12 mt-0 mb-4">
  <div class="card section-card h-100 py-3">
    <?php
    $current_date = date("Y-m-d");
    $sql = "
    SELECT k.*, t.nama_teknisi, c.nama AS nama_customer, c.telp AS cust_nomor
    FROM kegiatan k
    INNER JOIN (
        SELECT sub_k.kode, MAX(sub_k.id) AS max_id
        FROM kegiatan sub_k WHERE sub_k.deleted_at IS NULL GROUP BY sub_k.kode
    ) AS latest_kegiatan ON k.kode = latest_kegiatan.kode AND k.id = latest_kegiatan.max_id
    LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
    LEFT JOIN customer c ON k.customer_id = c.id
    WHERE k.status IN ('selesai', 'selesai by admin') AND k.deleted_at IS NULL
    GROUP BY k.kode
    ORDER BY COALESCE(k.jadwal, '9999-12-31') DESC";
    $result = mysqli_query($conn, $sql);
    ?>
    <div class="card-body pb-0 p-0">
      <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="listAll">
        <li class="list-group-item tbl-header d-md-block d-none">
          <div class="row px-3">
            <div class="col-md-2"><span class="tbl-th">Kegiatan</span></div>
            <div class="col-md-2"><span class="tbl-th">Customer</span></div>
            <div class="col-md-2"><span class="tbl-th">Teknisi</span></div>
            <div class="col-md-2"><span class="tbl-th">Permintaan Dari</span></div>
            <div class="col-md-2"><span class="tbl-th">Invoice</span></div>
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
            $displayInvoice = $row['invoice'] ?? 'no';
          ?>
            <li class="list-group-item tbl-row">
              <div class="row px-3 w-100 align-items-start">
                <div class="col-md-2">
                  <?php if (!empty($row['kegiatan'])): ?>
                    <span class="badge-type <?= $badgeClass ?>"><?= htmlspecialchars($row['kegiatan']) ?></span>
                  <?php endif; ?>
                  <span class="badge-status badge-selesai" style="margin-top:4px;">Selesai</span>
                  <span class="text-code"><?= $kodeTransaksi ?></span>
                  <p style="font-size:10px;color:#64748b;margin:2px 0 0;"><?= date("d/m/Y H:i", strtotime($row['jadwal'])) ?></p>
                </div>
                <div class="col-md-2">
                  <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" class="text-name d-block"><?= htmlspecialchars($row['nama_customer']) ?></a>
                  <a href="https://api.whatsapp.com/send?phone=<?= formatWhatsappNumber_all($row['cust_nomor']) ?>" target="_blank" class="text-phone"><?= htmlspecialchars($row['cust_nomor']) ?></a>
                </div>
                <div class="col-md-2">
                  <?php
                  $sqlTeknisi = "SELECT tk.teknisi_id, tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE k.kode = ? AND k.id = (SELECT MAX(sub_k.id) FROM kegiatan sub_k WHERE sub_k.kode = ?) GROUP BY tk.teknisi_id";
                  $stmtTeknisi = mysqli_prepare($conn, $sqlTeknisi);
                  mysqli_stmt_bind_param($stmtTeknisi, "ss", $kodeTransaksi, $kodeTransaksi);
                  mysqli_stmt_execute($stmtTeknisi);
                  $resultTeknisi = mysqli_stmt_get_result($stmtTeknisi);
                  while ($rowTeknisi = mysqli_fetch_assoc($resultTeknisi)):
                  ?>
                    <div style="margin-bottom:4px;">
                      <a href="list-kegiatan-teknisi.php?idTek=<?= urlencode($rowTeknisi['teknisi_id']) ?>" style="font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;"><?= shortenTechnicianName_all($rowTeknisi['nama_teknisi']) ?></a>
                    </div>
                  <?php endwhile; mysqli_stmt_close($stmtTeknisi); ?>
                </div>
                <div class="col-md-2">
                  <p style="font-size:12px;font-weight:600;color:#1e293b;margin:0;"><?= htmlspecialchars($row['request'] ?? '-') ?></p>
                </div>
                <div class="col-md-2">
                  <?php if (strtolower($displayInvoice) == 'no' || empty($displayInvoice)): ?>
                    <span style="font-size:11px;color:#94a3b8;">-</span>
                  <?php else: ?>
                    <button type="button" class="btn-act btn-act-edit edit-invoice-btn" style="width:auto;height:auto;padding:4px 10px;font-size:10px;font-weight:600;"
                      data-bs-toggle="modal" data-bs-target="#editInvoiceModal"
                      data-kode="<?= htmlspecialchars($kodeTransaksi) ?>"
                      data-invoice="<?= htmlspecialchars($row['invoice']) ?>"
                      data-garansi="<?= htmlspecialchars($row['garansi'] ?? '') ?>"
                      data-keterangan-garansi="<?= htmlspecialchars($row['keterangan_garansi'] ?? '') ?>">
                      <?= htmlspecialchars($displayInvoice) ?>
                    </button>
                  <?php endif; ?>
                </div>
                <div class="col-md-2 text-center">
                  <div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">
                    <a class="btn-act btn-act-view" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Lihat"><i class="material-icons" style="font-size:14px;">visibility</i></a>
                    <button class="btn-act btn-act-edit edit-btn" data-id="<?= htmlspecialchars($kodeTransaksi) ?>" title="Edit"><i class="material-icons" style="font-size:14px;">autorenew</i></button>
                    <a class="btn-act btn-act-delete" href="delete-kegiatan.php?kode=<?= urlencode($kodeTransaksi) ?>" onclick="return confirm('Yakin ingin menghapus data ini?');" title="Hapus"><i class="material-icons" style="font-size:14px;">delete</i></a>
                  </div>
                </div>
              </div>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="ms-4 text-sm py-4">Tidak ada kegiatan yang selesai</div>
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