<?php
if (!function_exists('translateActivityStatus')) {
  function translateActivityStatus($status) {
    $statusMap = ['waiting' => 'Dalam Antrian', 'dijadwalkan' => 'Dijadwalkan', 'berjalan' => 'Dalam Proses', 'menunggu laporan' => 'Menunggu Laporan', 'selesai' => 'Selesai', 'selesai by admin' => 'Diselesaikan Admin', 'Lanjut Nanti' => 'Berlanjut', 'Lanjutan' => 'Dilanjutkan'];
    return $statusMap[$status] ?? ucfirst($status);
  }
}
if (!function_exists('formatWhatsappNumber_ln')) {
  function formatWhatsappNumber_ln($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 1) === '0') return '62' . substr($number, 1);
    return $number;
  }
}
if (!function_exists('shortenTechnicianName_ln')) {
  function shortenTechnicianName_ln($fullName) {
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
if (!function_exists('getInitials_ln')) {
  function getInitials_ln($fullName) {
    if (empty($fullName)) return '-';
    $words = explode(" ", $fullName);
    $initials = "";
    foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
    return $initials;
  }
}
if (!function_exists('getAddressFromCoordinates_ln')) {
  function getAddressFromCoordinates_ln($lat, $lon) {
    if (empty($lat) || empty($lon)) return null;
    $cacheKey = "geo_" . md5($lat . $lon);
    if (isset($_SESSION[$cacheKey])) return $_SESSION[$cacheKey];
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
    $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response) { $data = json_decode($response, true); $address = $data['display_name'] ?? null; if ($address) { $_SESSION[$cacheKey] = $address; return $address; } }
    return null;
  }
}
if (!function_exists('getStatusBadgeClass_ln')) {
  function getStatusBadgeClass_ln($status) {
    $map = ['Lanjut Nanti' => 'badge-lanjut', 'Lanjutan' => 'badge-dilanjutkan'];
    return $map[$status] ?? 'badge-lanjut';
  }
}
?>

<div class="col-lg-12 mt-4 mb-0">
  <div class="d-flex justify-content-between align-items-center section-header">
    <div class="d-flex align-items-center gap-2">
      <i class="material-icons">schedule</i>
      <h6>Lanjut Nanti</h6>
    </div>
  </div>
</div>
<div class="col-lg-12 mt-0 mb-4">
  <div class="card section-card h-100 py-3">
    <?php
    $current_date = date("Y-m-d");
    $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat as cust_alamat FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status IN ('Lanjutan', 'Lanjut Nanti') AND DATE(k.jadwal) >= ? AND k.deleted_at IS NULL GROUP BY k.kode ORDER BY COALESCE(k.jadwal, '9999-12-31') ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    <div class="card-body pb-0 p-0">
      <ul class="list-group m-0 mt-0 col-12 p-0 py-0">
        <li class="list-group-item tbl-header d-md-block d-none">
          <div class="row px-3">
            <div class="col-md-2"><span class="tbl-th">Kegiatan</span></div>
            <div class="col-md-2"><span class="tbl-th">Customer</span></div>
            <div class="col-md-2"><span class="tbl-th">Teknisi & Status</span></div>
            <div class="col-md-3"><span class="tbl-th">Alamat</span></div>
            <div class="col-md-3 text-center"><span class="tbl-th">Aksi</span></div>
          </div>
        </li>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()):
            $kodeTransaksi = $row['kode'];
            $kegLower = strtolower($row['kegiatan']);
            $badgeClass = 'badge-default';
            if (strpos($kegLower, 'survey') !== false) $badgeClass = 'badge-survey';
            elseif (strpos($kegLower, 'service') !== false) $badgeClass = 'badge-service';
            elseif (strpos($kegLower, 'pasang') !== false) $badgeClass = 'badge-pasang';
            $statusBadge = getStatusBadgeClass_ln($row['status']);
            $address = getAddressFromCoordinates_ln($row['lat'], $row['lon']) ?: $row['cust_alamat'];
          ?>
            <li class="list-group-item tbl-row">
              <div class="row px-3 w-100 align-items-start">
                <div class="col-md-2">
                  <span class="badge-type <?= $badgeClass ?>"><?= htmlspecialchars($row['kegiatan']) ?></span>
                  <p class="text-time"><?= date("H:i", strtotime($row['jadwal'])) ?> WIB</p>
                  <span class="text-code"><?= $kodeTransaksi ?></span>
                  <p style="font-size:10px;color:#64748b;margin:2px 0 0;"><?= date("d/m/Y", strtotime($row['jadwal'])) ?></p>
                </div>
                <div class="col-md-2">
                  <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" class="text-name d-block"><?= htmlspecialchars($row['nama_customer']) ?></a>
                  <a href="https://api.whatsapp.com/send?phone=<?= formatWhatsappNumber_ln($row['cust_nomor']) ?>" target="_blank" class="text-phone"><?= htmlspecialchars($row['cust_nomor']) ?></a>
                  <p class="text-note">"<?= !empty($row["keterangan"]) ? htmlspecialchars($row["keterangan"]) : '-' ?>"</p>
                </div>
                <div class="col-md-2">
                  <?php
                  $sqlTeknisi = "SELECT tk.teknisi_id, tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.deleted_at IS NULL AND k.kode = ? AND k.id = (SELECT MAX(sub_k.id) FROM kegiatan sub_k WHERE sub_k.kode = ?) GROUP BY tk.teknisi_id";
                  $stmtTeknisi = $conn->prepare($sqlTeknisi);
                  $stmtTeknisi->bind_param("ss", $kodeTransaksi, $kodeTransaksi);
                  $stmtTeknisi->execute();
                  $resultTeknisi = $stmtTeknisi->get_result();
                  while ($rowTeknisi = $resultTeknisi->fetch_assoc()):
                  ?>
                    <div style="margin-bottom:6px;">
                      <a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id'] ?>" style="font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;"><?= shortenTechnicianName_ln($rowTeknisi['nama_teknisi']) ?></a>
                      <span class="badge-status <?= $statusBadge ?>"><?= translateActivityStatus($row['status']) ?></span>
                    </div>
                  <?php endwhile; $stmtTeknisi->close(); ?>
                </div>
                <div class="col-md-3">
                  <p class="text-addr"><?= htmlspecialchars($address) ?></p>
                </div>
                <div class="col-md-3 text-center">
                  <div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">
                    <?php if ($row['request']): ?>
                      <span style="font-size:10px;color:#475569;font-weight:600;margin-right:4px;"><?= htmlspecialchars($row['request']) ?></span>
                    <?php endif; ?>
                    <a class="btn-act btn-act-view" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Lihat"><i class="material-icons" style="font-size:14px;">visibility</i></a>
                    <?php if ($row['approval'] == 'no' && $row['status'] == 'Lanjutan'): ?>
                      <a class="btn-act btn-act-approve approve-btn" href="apv_kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Setujui"><i class="material-icons" style="font-size:14px;">check</i></a>
                    <?php else: ?>
                      <button class="btn-act btn-act-edit edit-btn" data-id="<?= htmlspecialchars($kodeTransaksi) ?>" title="Edit"><i class="material-icons" style="font-size:14px;">autorenew</i></button>
                      <a class="btn-act btn-act-delete" href="delete-kegiatan.php?kode=<?= urlencode($kodeTransaksi) ?>" onclick="return confirm('Yakin ingin menghapus data ini?');" title="Hapus"><i class="material-icons" style="font-size:14px;">delete</i></a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="ms-4 text-sm py-4">Tidak ada kegiatan berstatus "Lanjut Nanti"</div>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Data Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="map" style="height: 300px; border-radius: 0.375rem; margin-bottom: 1rem;"></div>
                <form id="locationForm">
                    <input type="hidden" id="kegiatanId" name="kegiatan_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text" class="form-control border p-2" id="latitude" name="lat"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text" class="form-control border p-2" id="longitude" name="lon"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Radius (meter)</label><input type="number" class="form-control border p-2" id="radius" name="rad"></div>
                    <div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    let map, marker, radiusCircle;
    const latInput = document.getElementById('latitude'), lonInput = document.getElementById('longitude'), radInput = document.getElementById('radius'), locationModal = document.getElementById('locationModal');
    function initMap() { if (!map) { map = L.map('map').setView([-6.9925, 110.4208], 13); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map); map.on('click', function(e) { const { lat, lng } = e.latlng; latInput.value = lat.toFixed(6); lonInput.value = lng.toFixed(6); updateMarkerAndCircle(); }); } }
    function updateMarkerAndCircle() { const lat = parseFloat(latInput.value), lon = parseFloat(lonInput.value), rad = parseInt(radInput.value) || 50; if (!isNaN(lat) && !isNaN(lon)) { const latLng = [lat, lon]; if (marker) { marker.setLatLng(latLng); } else { marker = L.marker(latLng).addTo(map); } if (radiusCircle) { radiusCircle.setLatLng(latLng).setRadius(rad); } else { radiusCircle = L.circle(latLng, { radius: rad }).addTo(map); } map.fitBounds(radiusCircle.getBounds(), { padding: [50, 50] }); } }
    locationModal.addEventListener('shown.bs.modal', function() { initMap(); setTimeout(() => { map.invalidateSize(); updateMarkerAndCircle(); }, 10); });
    [latInput, lonInput, radInput].forEach(input => { input.addEventListener('input', updateMarkerAndCircle); });
    function openLocationModal(data) { document.getElementById('kegiatanId').value = data.id; document.getElementById('latitude').value = data.lat; document.getElementById('longitude').value = data.lon; document.getElementById('radius').value = data.rad; new bootstrap.Modal(locationModal).show(); }
    document.getElementById('locationForm').addEventListener('submit', function(e) { e.preventDefault(); const formData = new FormData(e.target); fetch('update_lokasi.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { alert('Lokasi berhasil diperbarui.'); location.reload(); } else { alert('Gagal: ' + (data.message || 'Error')); } }).catch(() => alert('Terjadi kesalahan koneksi.')); });
</script>