<?php
function translateActivityStatus($status) { $statusMap = [ 'waiting' => 'Dalam Antrian', 'dijadwalkan' => 'Dijadwalkan', 'berjalan' => 'Dalam Proses', 'selesai' => 'Selesai', 'selesai by admin' => 'Diselesaikan Admin', 'Lanjut Nanti' => 'Berlanjut', 'Lanjutan' => 'Dilanjutkan' ]; return $statusMap[$status] ?? ucfirst($status); }
function formatWhatsappNumber($number) { if (substr($number, 0, 1) === '0') { return '62' . substr($number, 1); } return $number; }
function getInitials($fullName) { if (empty($fullName)) return '-'; $words = explode(" ", $fullName); $initials = ""; foreach ($words as $word) $initials .= strtoupper($word[0] ?? ''); return $initials; }
function shortenTechnicianName($fullName) { if (empty($fullName)) return '-'; $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed']; $words = explode(" ", $fullName); if (in_array($words[0], $muhammadVariants)) $words[0] = "M."; $shortenedName = implode(" ", $words); if (strlen($shortenedName) > 15 && count($words) > 2) { $lastWordIndex = count($words) - 1; if (isset($words[$lastWordIndex][0])) $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.'; $shortenedName = implode(" ", $words); } return $shortenedName; }
function getAddressFromCoordinates($lat, $lon) { if (empty($lat) || empty($lon)) { return null; } $cacheKey = "geo_" . md5($lat . $lon); if (isset($_SESSION[$cacheKey])) { return $_SESSION[$cacheKey]; } $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}"; $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]]; $context = stream_context_create($options); $response = @file_get_contents($url, false, $context); if ($response) { $data = json_decode($response, true); $address = $data['display_name'] ?? null; if ($address) { $_SESSION[$cacheKey] = $address; return $address; } } return null; }
?>
<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12"><button type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Lanjut Nanti</button></div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <?php
        $current_date = date("Y-m-d");
        $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat as cust_alamat FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status IN ('Lanjutan', 'Lanjut Nanti') AND DATE(k.jadwal) >= ? AND k.deleted_at IS NULL GROUP BY k.kode ORDER BY COALESCE(k.jadwal, '9999-12-31') ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_date);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Kegiatan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alamat</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Teknisi</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Info</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <p class="text-sm font-weight-bold mb-0 text-capitalize"><?= htmlspecialchars($row['kegiatan']) ?></p>
                                    <p class="text-xs text-dark mb-1"><?= date("d/m/y, H:i", strtotime($row['jadwal'])) ?></p>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['kode']) ?></p>
                                </td>
                                <td>
                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['nama_customer']) ?></h6>
                                    <p class="text-xs text-secondary mb-1"><?= htmlspecialchars($row['cust_nomor']) ?></p>
                                    <p class="text-xs text-secondary mb-0 fst-italic text-wrap">"<?= !empty($row["keterangan"]) ? htmlspecialchars($row["keterangan"]) : '-'; ?>"</p>
                                </td>
                                <td class="text-wrap">
                                    <p class="text-xs font-weight-bold mb-0 d-flex align-items-center text-wrap">
                                        <span class="me-2"><?= htmlspecialchars(getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['cust_alamat']); ?></span>
                                        <button class="btn btn-secondary text-light p-0 px-1 m-0" onclick='openLocationModal(<?= json_encode($row) ?>)'><i class="material-icons" style="font-size:13px;">edit</i></button>
                                    </p>
                                </td>
                                <td>
                                    <?php
                                    $sqlTeknisi = "SELECT tk.teknisi_id, tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.deleted_at IS NULL AND k.kode = ? AND k.id = (SELECT MAX(sub_k.id) FROM kegiatan sub_k WHERE sub_k.kode = ?) GROUP BY tk.teknisi_id";
                                    $stmtTeknisi = $conn->prepare($sqlTeknisi);
                                    $stmtTeknisi->bind_param("ss", $row['kode'], $row['kode']);
                                    $stmtTeknisi->execute();
                                    $resultTeknisi = $stmtTeknisi->get_result();
                                    while ($rowTeknisi = $resultTeknisi->fetch_assoc()):
                                    ?>
                                    <p class="text-xs font-weight-bold mb-1"><?= htmlspecialchars($rowTeknisi['nama_teknisi']) ?></p>
                                    <?php endwhile; $stmtTeknisi->close(); ?>
                                </td>
                                <td><p class="text-xs text-secondary mb-0"><strong class="text-dark"><?= htmlspecialchars($row['request']) ?></strong></p></td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                    <?php if ($row['approval'] == 'no' && $row['status'] == 'Lanjutan'): ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= urlencode($row['kode']) ?>" title="Lihat Detail"><i class="material-icons" style="font-size:13px;">visibility</i></a>
                                        <button type="button" class="btn btn-success approve-btn" data-id="<?= htmlspecialchars($row['kode']) ?>" title="Setujui"><i class="material-icons" style="font-size:13px;">check</i></button>
                                    <?php else: ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= urlencode($row['kode']) ?>" title="Lihat Detail"><i class="material-icons" style="font-size:13px;">visibility</i></a>
                                        <button class="btn btn-warning edit-btn" data-id="<?= htmlspecialchars($row['kode']) ?>" title="Edit"><i class="material-icons" style="font-size:13px;">autorenew</i></button>
                                        <a class="btn btn-danger" href="delete-kegiatan.php?kode=<?= urlencode($row['kode']) ?>" onclick="return confirm('Yakin ingin menghapus data ini?');" title="Hapus"><i class="material-icons" style="font-size:13px;">delete</i></a>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada kegiatan yang berstatus "Lanjut Nanti".</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
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