<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

if (!function_exists('getAddressFromCoordinates')) {
    function getAddressFromCoordinates($lat, $lon) {
        if (empty($lat) || empty($lon)) {
            return null;
        }
        $cacheKey = "geo_" . md5($lat . $lon);
        if (isset($_SESSION[$cacheKey])) {
            return $_SESSION[$cacheKey];
        }
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
        $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            $address = $data['display_name'] ?? null;
            if ($address) {
                $_SESSION[$cacheKey] = $address;
                return $address;
            }
        }
        return null;
    }
}

$pageNow = "Waiting List";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .nav-link i.material-icons { font-size: 2em; }
        .btm-nav { position: fixed; bottom: 15px; left: 0; right: 0; margin: 0 auto; border-radius: 15px; background-color: rgba(0, 0, 0, 0.7); width: 94%; margin-left: 3%; z-index: 1050; }
        #map { height: 300px; width: 100%; border-radius: 8px; z-index: 1; }
        .modal { z-index: 1060 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        <?php include "css/floating-menu2.css"; ?>

        /* Premium Waiting List Styles */
        body { font-family: 'Roboto', 'Inter', -apple-system, sans-serif; }
        .wl-section-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 20px; background: #1e293b; border-radius: 10px 10px 0 0;
        }
        .wl-section-header h6 { margin: 0; font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.02em; text-transform: uppercase; }
        .wl-section-header .material-icons { font-size: 18px; color: #94a3b8; margin-right: 8px; }
        .wl-card { border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; border-top: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .wl-th { font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
        .wl-row { border: none !important; border-bottom: 1px solid #f1f5f9 !important; border-radius: 0 !important; padding: 14px 16px !important; transition: background 0.15s; }
        .wl-row:hover { background: #f8fafc; }
        .wl-badge-waiting { font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 4px; background: #fef3c7; color: #92400e; display: inline-block; }
        .wl-badge-scheduled { font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 4px; background: #dbeafe; color: #1e40af; display: inline-block; }
        .wl-badge-overdue { font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 4px; background: #fee2e2; color: #991b1b; display: inline-block; }
        .wl-badge-type { font-size: 9px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.04em; text-transform: uppercase; display: inline-block; }
        .wl-btn { width: 30px; height: 30px; border-radius: 6px; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
        .wl-btn-schedule { background: #dbeafe; color: #1e40af; }
        .wl-btn-schedule:hover { background: #1e40af; color: #fff; }
        .wl-btn-reason { background: #f0fdf4; color: #166534; }
        .wl-btn-reason:hover { background: #166534; color: #fff; }
        .wl-btn-reason-empty { background: #fef2f2; color: #991b1b; }
        .wl-btn-reason-empty:hover { background: #991b1b; color: #fff; }
        .wl-btn-delete { background: #fef2f2; color: #991b1b; }
        .wl-btn-delete:hover { background: #991b1b; color: #fff; }
        .wl-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; border-radius: 12px; background: rgba(255,255,255,0.15); color: #fff; font-size: 11px; font-weight: 700; margin-left: 10px; padding: 0 8px; }

        /* Modal Premium */
        .modal-content { border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 16px 20px; }
        .modal-header .modal-title { font-size: 15px; font-weight: 700; color: #1e293b; }
        .modal-body { padding: 20px; }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 12px 20px; }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php 
        include "nav-top.php"; 
        $todayDate = date('d F Y');

        // Query waiting list
        $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id as customer_id,
                (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count,
                (SELECT MAX(created_at) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as latest_reason_date
                FROM kegiatan k 
                LEFT JOIN customer c ON k.customer_id = c.id 
                WHERE k.status = 'waiting' AND k.deleted_at IS NULL 
                ORDER BY k.created_at ASC";
        $result = mysqli_query($conn, $sql);
        $totalWaiting = mysqli_num_rows($result);
        ?>
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-lg-12 mt-4 mb-0">
                    <div class="wl-section-header" id="toggleLoadMore2">
                        <div class="d-flex align-items-center">
                            <i class="material-icons">pending_actions</i>
                            <h6>Waiting List</h6>
                            <span class="wl-count"><?= $totalWaiting ?></span>
                        </div>
                        <span style="font-size:11px;color:#94a3b8;"><?= $todayDate ?></span>
                    </div>
                </div>

                <div class="col-lg-12 mt-0 mb-4">
                    <div class="card wl-card h-100 py-3">
                        <div class="card-body pb-0 p-0">
                            <ul class="list-group m-0 col-12 p-0">
                                <!-- Table Header -->
                                <li class="list-group-item d-md-block d-none" style="background:#f8fafc;border:none;border-bottom:2px solid #e2e8f0;border-radius:0;padding:10px 16px !important;">
                                    <div class="row px-3">
                                        <div class="col-md-2"><span class="wl-th">Status & Jadwal</span></div>
                                        <div class="col-md-2"><span class="wl-th">Customer</span></div>
                                        <div class="col-md-3"><span class="wl-th">Alamat & Keterangan</span></div>
                                        <div class="col-md-1"><span class="wl-th">Request</span></div>
                                        <div class="col-md-2 text-center"><span class="wl-th">History Alasan</span></div>
                                        <div class="col-md-2 text-center"><span class="wl-th">Aksi</span></div>
                                    </div>
                                </li>
                                <?php
                                if ($totalWaiting > 0) {
                                    mysqli_data_seek($result, 0);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $status_display = "Dilaporkan";
                                        $badge_class = "wl-badge-waiting";
                                        $jadwal_raw = $row["jadwal"];
                                        $jadwal_display = date('d/m/y', strtotime($row["created_at"]));
                                        $is_overdue = false;

                                        if ($jadwal_raw != '0000-00-00 00:00:00' && !empty($jadwal_raw)) {
                                            $status_display = "Dijadwalkan";
                                            $badge_class = "wl-badge-scheduled";
                                            $tgl_request = strtotime($jadwal_raw);
                                            $jadwal_display = date('d/m/y H:i', $tgl_request);
                                            if (date('Y-m-d', $tgl_request) < date('Y-m-d')) {
                                                $is_overdue = true;
                                                $badge_class = "wl-badge-overdue";
                                                $status_display = "Terlambat";
                                            }
                                        }

                                        $hasReason = $row['reason_count'] > 0;

                                        $kegLower = strtolower($row['kegiatan']);
                                        $typeBadge = 'background:#f1f5f9;color:#475569;';
                                        if (strpos($kegLower, 'survey') !== false) $typeBadge = 'background:#fef3c7;color:#92400e;';
                                        elseif (strpos($kegLower, 'service') !== false) $typeBadge = 'background:#e0e7ff;color:#3730a3;';
                                        elseif (strpos($kegLower, 'pasang') !== false) $typeBadge = 'background:#dcfce7;color:#166534;';
                                ?>
                                <li class="list-group-item wl-row">
                                    <div class="row px-3 w-100 align-items-start">
                                        <!-- Status & Jadwal -->
                                        <div class="col-md-2">
                                            <div style="margin-bottom:4px;">
                                                <span class="<?= $badge_class ?>"><?= $status_display ?></span>
                                                <span class="wl-badge-type" style="<?= $typeBadge ?>;margin-left:4px;"><?= htmlspecialchars($row['kegiatan']) ?></span>
                                            </div>
                                            <p style="font-size:13px;font-weight:600;color:<?= $is_overdue ? '#dc2626' : '#1e293b' ?>;margin:4px 0 0;"><?= $jadwal_display ?></p>
                                            <span style="font-size:10px;color:#94a3b8;display:block;"><?= $row['kode'] ?></span>
                                        </div>
                                        <!-- Customer -->
                                        <div class="col-md-2">
                                            <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" style="text-decoration:none;color:#1e293b;">
                                                <h6 style="font-size:13px;font-weight:700;margin:0 0 2px;"><?= htmlspecialchars($row['nama_customer']) ?></h6>
                                            </a>
                                            <a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $row['cust_nomor']), 1) ?>" target="_blank" style="font-size:11px;color:#3b82f6;text-decoration:none;"><?= htmlspecialchars($row['cust_nomor']) ?></a>
                                        </div>
                                        <!-- Alamat & Keterangan -->
                                        <div class="col-md-3">
                                            <p style="font-size:11px;color:#475569;margin:0;line-height:1.5;">
                                                <?= htmlspecialchars(getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['alamat']) ?>
                                                <button type="button" class="edit-loc-btn" style="background:none;border:none;padding:0;margin-left:4px;cursor:pointer;vertical-align:middle;" 
                                                        data-id="<?= $row['id'] ?>" 
                                                        data-cust="<?= $row['customer_id'] ?>" 
                                                        data-lat="<?= $row['lat'] ?>" 
                                                        data-lon="<?= $row['lon'] ?>" 
                                                        data-rad="<?= $row['rad'] ?>">
                                                    <i class="material-icons" style="font-size:13px;color:#3b82f6;">edit_location</i>
                                                </button>
                                            </p>
                                            <?php if (!empty($row['keterangan'])): ?>
                                            <p style="font-size:10px;color:#94a3b8;margin:4px 0 0;font-style:italic;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">"<?= htmlspecialchars($row['keterangan']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Request -->
                                        <div class="col-md-1">
                                            <span style="font-size:11px;font-weight:600;color:#1e293b;"><?= htmlspecialchars($row['request']) ?></span>
                                        </div>
                                        <!-- History Alasan -->
                                        <div class="col-md-2 text-center">
                                            <button type="button" class="wl-btn <?= $hasReason ? 'wl-btn-reason' : 'wl-btn-reason-empty' ?> reason-btn" data-id="<?= $row['id'] ?>" title="<?= $hasReason ? 'Lihat Riwayat' : 'Tambah Catatan' ?>">
                                                <i class="material-icons" style="font-size:16px;"><?= $hasReason ? 'history' : 'add_comment' ?></i>
                                            </button>
                                            <?php if ($hasReason): ?>
                                            <span style="font-size:10px;font-weight:600;color:#475569;margin-left:4px;"><?= $row['reason_count'] ?> catatan</span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Aksi -->
                                        <div class="col-md-2 text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <button type="button" class="wl-btn wl-btn-schedule jadwalkan-btn" data-id="<?= $row['id'] ?>" data-tgl="<?= $row['jadwal'] ?>" title="Jadwalkan">
                                                    <i class="material-icons" style="font-size:16px;">calendar_today</i>
                                                </button>
                                                <button type="button" class="wl-btn wl-btn-delete hapus-btn" data-id="<?= $row['id'] ?>" data-kode="<?= $row['kode'] ?>" title="Hapus">
                                                    <i class="material-icons" style="font-size:16px;">delete</i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php } } else { ?>
                                <li class="list-group-item" style="border:none;padding:40px 16px;text-align:center;">
                                    <i class="material-icons" style="font-size:48px;color:#cbd5e1;">check_circle</i>
                                    <p style="font-size:14px;color:#94a3b8;margin:12px 0 0;">Tidak ada kegiatan dalam waiting list.</p>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            // include "floating-menu.php"; 
            include "footer.php"; ?>
        </div>
    </main>

    <!-- Modal Jadwalkan -->
    <div class="modal fade" id="jadwalkanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Jadwalkan Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="jadwalkanForm">
                        <input type="hidden" id="modalKegiatanId">
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control border p-2" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam</label>
                            <input type="time" class="form-control border p-2" id="jam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Teknisi</label>
                            <div id="technician-list" class="border p-2 rounded" style="max-height:200px; overflow-y:auto;">
                                <?php
                                $st = mysqli_query($conn, "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
                                while ($rt = mysqli_fetch_assoc($st)) {
                                    echo "<div class='form-check'>
                                            <input class='form-check-input tek-check' type='checkbox' value='{$rt['id']}' id='tk{$rt['id']}'>
                                            <label class='form-check-label' for='tk{$rt['id']}'>{$rt['nama']}</label>
                                            <div class='text-xs text-danger' id='info-tk-{$rt['id']}'></div>
                                          </div>";
                                }
                                ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitJadwal">Simpan Jadwal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lokasi -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="locationForm">
                        <input type="hidden" name="kegiatan_id" id="locKegId">
                        <input type="hidden" name="customer_id" id="locCustId">
                        <div class="input-group mb-2">
                            <input type="text" id="addressSearch" class="form-control border p-2" placeholder="Cari alamat...">
                            <button class="btn btn-primary mb-0" type="button" id="btnSearchLoc">Cari</button>
                        </div>
                        <div id="map"></div>
                        <div class="row mt-2">
                            <div class="col-6"><label class="text-xs">Latitude</label><input type="text" name="lat" id="lat" class="form-control border p-2" readonly></div>
                            <div class="col-6"><label class="text-xs">Longitude</label><input type="text" name="lon" id="lon" class="form-control border p-2" readonly></div>
                        </div>
                        <div class="mt-2">
                            <label class="text-xs">Radius (meter)</label>
                            <input type="number" name="rad" id="rad" class="form-control border p-2">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="save_location" id="saveLocCheck" checked>
                            <label class="form-check-label" for="saveLocCheck">Update Master Customer</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="btnSaveLoc">Update Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reason -->
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Riwayat Penangguhan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <form id="reasonForm">
                                <input type="hidden" name="kegiatan_id" id="reKegId">
                                <textarea name="reason" class="form-control border p-2 mb-2" rows="4" placeholder="Tulis alasan..." required></textarea>
                                <input type="file" name="media" class="form-control border mb-2">
                                <button type="submit" class="btn btn-info w-100">Simpan Catatan</button>
                            </form>
                        </div>
                        <div class="col-md-7 border-start">
                            <div id="reasonList" style="max-height:300px; overflow-y:auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Inisialisasi Modal BS5 secara manual
        const modalJadwal = new bootstrap.Modal(document.getElementById('jadwalkanModal'));
        const modalLokasi = new bootstrap.Modal(document.getElementById('locationModal'));
        const modalReason = new bootstrap.Modal(document.getElementById('reasonModal'));

        let map, marker, circle;

        $(document).ready(function() {
            // Trigger Jadwal
            $(document).on('click', '.jadwalkan-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const tglFull = $(this).data('tgl');
                $('#modalKegiatanId').val(id);
                if(tglFull && tglFull !== '0000-00-00 00:00:00') {
                    const parts = tglFull.split(' ');
                    $('#tanggal').val(parts[0]);
                    $('#jam').val(parts[1].substring(0,5));
                }
                fetchSchedules();
                modalJadwal.show();
            });

            // Trigger Lokasi
            $(document).on('click', '.edit-loc-btn', function(e) {
                e.preventDefault();
                $('#locKegId').val($(this).data('id'));
                $('#locCustId').val($(this).data('cust'));
                $('#lat').val($(this).data('lat'));
                $('#lon').val($(this).data('lon'));
                $('#rad').val($(this).data('rad') || 50);
                modalLokasi.show();
                setTimeout(initMap, 400);
            });

            // Trigger Reason
            $(document).on('click', '.reason-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                $('#reKegId').val(id);
                loadReasons(id);
                modalReason.show();
            });

            // Hapus
            $(document).on('click', '.hapus-btn', function(e) {
                e.preventDefault();
                if(confirm('Apakah Anda yakin ingin menghapus kegiatan ini?')) {
                    $.post('proses_hapus_kegiatan.php', { 
                        kegiatanId: $(this).data('id'), 
                        kode: $(this).data('kode') 
                    }, function() {
                        location.reload();
                    });
                }
            });

            // Submit Jadwal
            $('#btnSubmitJadwal').click(function() {
                const id = $('#modalKegiatanId').val();
                const tgl = $('#tanggal').val();
                const jam = $('#jam').val();
                const teks = $('.tek-check:checked').map(function() { return this.value; }).get();
                if(!tgl || !jam || teks.length === 0) return alert('Data tidak lengkap!');

                $.post('proses_jadwalkan.php', { 
                    kegiatanId: id, 
                    teknisi: teks, 
                    tanggal: tgl, 
                    jam: jam 
                }, function(res) {
                    if(res.trim() === 'success') {
                        $.post('wa-msg.php', { teknisi: teks, kegiatanId: id, tanggal: tgl, jam: jam });
                        location.reload();
                    } else {
                        alert('Gagal menjadwalkan.');
                    }
                });
            });
        });

        function fetchSchedules() {
            const tgl = $('#tanggal').val();
            if(!tgl) return;
            $.getJSON('cek_jadwal_teknisi.php?tanggal=' + tgl, function(data) {
                $('[id^="info-tk-"]').html('');
                for(let id in data) {
                    let info = data[id].map(i => `${i.customer} (${i.waktu})`).join(', ');
                    $(`#info-tk-${id}`).html(info);
                }
            });
        }

        function initMap() {
            const lt = parseFloat($('#lat').val()) || -6.175;
            const ln = parseFloat($('#lon').val()) || 106.865;
            if(!map) {
                map = L.map('map').setView([lt, ln], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                marker = L.marker([lt, ln], {draggable:true}).addTo(map);
                circle = L.circle([lt, ln], {radius:$('#rad').val()}).addTo(map);
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    $('#lat').val(pos.lat.toFixed(6));
                    $('#lon').val(pos.lng.toFixed(6));
                    circle.setLatLng(pos);
                });
            } else {
                const pos = [lt, ln];
                map.setView(pos, 15);
                marker.setLatLng(pos);
                circle.setLatLng(pos).setRadius($('#rad').val());
            }
            map.invalidateSize();
        }

        $('#btnSearchLoc').click(function() {
            const q = $('#addressSearch').val();
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${q}&limit=1`, function(data) {
                if(data.length > 0) {
                    const lt = data[0].lat, ln = data[0].lon;
                    $('#lat').val(lt); $('#lon').val(ln);
                    const pos = [lt, ln];
                    map.setView(pos, 15);
                    marker.setLatLng(pos);
                    circle.setLatLng(pos);
                }
            });
        });

        $('#btnSaveLoc').click(function() {
            $.post('update_lokasi.php', $('#locationForm').serialize(), function() {
                location.reload();
            });
        });

        function loadReasons(id) {
            $('#reasonList').html('Memuat...');
            $.getJSON('get_reasons.php?id=' + id, function(data) {
                let h = '';
                data.forEach(i => {
                    h += `<div style="padding:10px;border-bottom:1px solid #f1f5f9;margin-bottom:8px;background:#f8fafc;border-radius:6px;">
                            <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:4px;">${i.formatted_date}</div>
                            <div style="font-size:12px;color:#475569;line-height:1.5;">${i.reason}</div>
                            ${i.media ? `<a href="uploads/reasons/${i.media}" target="_blank" style="font-size:11px;color:#3b82f6;text-decoration:none;margin-top:4px;display:inline-block;"><i class="material-icons" style="font-size:12px;vertical-align:middle;">attach_file</i> Lihat Lampiran</a>` : ''}
                          </div>`;
                });
                $('#reasonList').html(h || '<div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">Tidak ada catatan.</div>');
            });
        }

        $('#reasonForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'save_reason.php', 
                type: 'POST', 
                data: new FormData(this), 
                processData: false, 
                contentType: false,
                success: function() { 
                    loadReasons($('#reKegId').val()); 
                    $('#reasonForm')[0].reset(); 
                }
            });
        });
    </script>
</body>
</html>