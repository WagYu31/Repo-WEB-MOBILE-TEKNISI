<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

if (!function_exists('getAddressFromCoordinates')) {
    function getAddressFromCoordinates($lat, $lon) {
        if (empty($lat) || empty($lon)) return null;
        $cacheKey = "geo_" . md5($lat . $lon);
        if (isset($_SESSION[$cacheKey])) return $_SESSION[$cacheKey];
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
        $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            $address = $data['display_name'] ?? null;
            if ($address) { $_SESSION[$cacheKey] = $address; return $address; }
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

        /* ═══════ WAITING LIST V4 — CARD LAYOUT ═══════ */
        body { font-family: 'Roboto', 'Inter', -apple-system, sans-serif !important; }

        .wl-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; background: #1e293b; border-radius: 10px 10px 0 0;
        }
        .wl-header h6 { margin: 0; font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.04em; text-transform: uppercase; }
        .wl-header .material-icons { font-size: 18px; color: #94a3b8; margin-right: 8px; }
        .wl-counter { min-width: 24px; height: 24px; border-radius: 12px; background: rgba(255,255,255,0.15); color: #fff; font-size: 11px; font-weight: 700; margin-left: 10px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; }
        .wl-export { font-size: 11px; padding: 6px 14px; background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; }
        .wl-export:hover { background: rgba(255,255,255,0.2); color: #fff; }

        /* Outer card */
        .wl-wrap {
            border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; border-top: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05); background: #fff; padding: 12px;
        }

        /* ── INDIVIDUAL ITEM CARD ── */
        .wl-card {
            background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
            margin-bottom: 10px; overflow: hidden; transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .wl-card:last-child { margin-bottom: 0; }
        .wl-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-color: #d1d5db; transform: translateY(-1px); }

        /* Left accent border */
        .wl-card-scheduled { border-left: 4px solid #3b82f6; }
        .wl-card-reported { border-left: 4px solid #f59e0b; }
        .wl-card-overdue { border-left: 4px solid #ef4444; }

        .wl-card-body { padding: 16px 18px; }

        /* ── Top row: badge + customer + actions ── */
        .wl-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px; }
        .wl-top-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
        .wl-top-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: 12px; }

        /* Status badge */
        .wl-status {
            font-size: 10px; font-weight: 700; padding: 5px 12px;
            border-radius: 20px; letter-spacing: 0.03em; white-space: nowrap;
        }
        .wl-s-reported { background: #fef3c7; color: #92400e; }
        .wl-s-scheduled { background: #dbeafe; color: #1e40af; }
        .wl-s-overdue { background: #fee2e2; color: #991b1b; }

        /* Type tag */
        .wl-type {
            font-size: 9px; font-weight: 700; padding: 3px 8px;
            border-radius: 20px; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap;
        }
        .wl-t-survey { background: #fef3c7; color: #92400e; }
        .wl-t-service { background: #e0e7ff; color: #3730a3; }
        .wl-t-pasang { background: #dcfce7; color: #166534; }
        .wl-t-default { background: #f1f5f9; color: #475569; }

        /* Customer name inline */
        .wl-cust { font-size: 14px; font-weight: 700; color: #1e293b; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .wl-cust:hover { color: #3b82f6; }

        /* ── Bottom row: details grid ── */
        .wl-details { display: grid; grid-template-columns: 180px 1fr auto; gap: 16px; align-items: start; }

        .wl-detail-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
        .wl-detail-value { font-size: 12px; color: #475569; line-height: 1.4; }
        .wl-detail-value strong { color: #1e293b; font-weight: 600; }

        /* Phone link */
        .wl-phone { font-size: 11px; color: #3b82f6; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
        .wl-phone:hover { text-decoration: underline; }

        /* Date display */
        .wl-date { font-size: 12px; font-weight: 600; color: #1e293b; }
        .wl-date-red { color: #dc2626 !important; }
        .wl-code { font-size: 10px; color: #b0b8c4; }

        /* Address */
        .wl-addr { font-size: 11.5px; color: #475569; line-height: 1.5; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .wl-ket { font-size: 10.5px; color: #94a3b8; font-style: italic; margin: 4px 0 0; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }

        /* ── Right side meta ── */
        .wl-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; min-width: 140px; }
        .wl-meta-row { display: flex; align-items: center; gap: 6px; }
        .wl-request-badge { font-size: 11px; font-weight: 600; color: #1e293b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; }

        /* ── Action Buttons ── */
        .wl-btn {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s;
        }
        .wl-btn i { font-size: 16px; }
        .wl-btn-cal { background: #eff6ff; color: #3b82f6; }
        .wl-btn-cal:hover { background: #3b82f6; color: #fff; transform: scale(1.1); }
        .wl-btn-note { background: #f0fdf4; color: #16a34a; }
        .wl-btn-note:hover { background: #16a34a; color: #fff; transform: scale(1.1); }
        .wl-btn-note-empty { background: #fff7ed; color: #ea580c; }
        .wl-btn-note-empty:hover { background: #ea580c; color: #fff; transform: scale(1.1); }
        .wl-btn-del { background: #f8fafc; color: #94a3b8; }
        .wl-btn-del:hover { background: #ef4444; color: #fff; transform: scale(1.1); }
        .wl-btn-loc { background: none; border: none; padding: 0; cursor: pointer; }

        /* ── Empty State ── */
        .wl-empty { padding: 60px 20px; text-align: center; }
        .wl-empty i { font-size: 64px; color: #e2e8f0; display: block; margin-bottom: 16px; }
        .wl-empty p { font-size: 15px; color: #94a3b8; margin: 0; }

        /* Modal */
        .modal-content { border-radius: 12px !important; border: none !important; box-shadow: 0 20px 60px rgba(0,0,0,0.15) !important; }
        .modal-header { border-bottom: 1px solid #f1f5f9 !important; padding: 16px 20px !important; }
        .modal-header .modal-title { font-size: 15px !important; font-weight: 700 !important; color: #1e293b !important; }
        .modal-body { padding: 20px !important; }
        .modal-footer { border-top: 1px solid #f1f5f9 !important; padding: 12px 20px !important; }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php 
        include "nav-top.php"; 
        $todayDate = date('d F Y');

        $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id as customer_id,
                (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count
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
                    <div class="wl-header" id="toggleLoadMore2">
                        <div class="d-flex align-items-center">
                            <i class="material-icons">pending_actions</i>
                            <h6>Waiting List</h6>
                            <span class="wl-counter"><?= $totalWaiting ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:11px;color:#94a3b8;"><?= $todayDate ?></span>
                            <a href="?export=waiting" class="wl-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-0 mb-4">
                    <div class="wl-wrap">
                        <?php
                        if ($totalWaiting > 0) {
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $status_display = "Dilaporkan";
                                $status_class = "wl-s-reported";
                                $card_class = "wl-card-reported";
                                $jadwal_raw = $row["jadwal"];
                                $jadwal_display = date('d/m/y', strtotime($row["created_at"]));
                                $is_overdue = false;

                                if ($jadwal_raw != '0000-00-00 00:00:00' && !empty($jadwal_raw)) {
                                    $status_display = "Dijadwalkan";
                                    $status_class = "wl-s-scheduled";
                                    $card_class = "wl-card-scheduled";
                                    $tgl_request = strtotime($jadwal_raw);
                                    $jadwal_display = date('d/m/y H:i', $tgl_request);
                                    if (date('Y-m-d', $tgl_request) < date('Y-m-d')) {
                                        $is_overdue = true;
                                        $status_class = "wl-s-overdue";
                                        $card_class = "wl-card-overdue";
                                        $status_display = "Terlambat";
                                    }
                                }

                                $hasReason = $row['reason_count'] > 0;

                                $kegLower = strtolower($row['kegiatan']);
                                $typeClass = 'wl-type wl-t-default';
                                if (strpos($kegLower, 'survey') !== false) $typeClass = 'wl-type wl-t-survey';
                                elseif (strpos($kegLower, 'service') !== false) $typeClass = 'wl-type wl-t-service';
                                elseif (strpos($kegLower, 'pasang') !== false) $typeClass = 'wl-type wl-t-pasang';

                                $fullAddr = getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['alamat'];
                        ?>
                        <!-- CARD ITEM -->
                        <div class="wl-card <?= $card_class ?>">
                            <div class="wl-card-body">
                                <!-- Top: Status + Customer + Actions -->
                                <div class="wl-top">
                                    <div class="wl-top-left">
                                        <span class="wl-status <?= $status_class ?>"><?= $status_display ?></span>
                                        <span class="<?= $typeClass ?>"><?= htmlspecialchars($row['kegiatan']) ?></span>
                                        <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" class="wl-cust"><?= htmlspecialchars($row['nama_customer']) ?></a>
                                    </div>
                                    <div class="wl-top-right">
                                        <button type="button" class="wl-btn <?= $hasReason ? 'wl-btn-note' : 'wl-btn-note-empty' ?> reason-btn" data-id="<?= $row['id'] ?>" title="<?= $hasReason ? $row['reason_count'].' catatan' : 'Tambah catatan' ?>">
                                            <i class="material-icons"><?= $hasReason ? 'history' : 'add_comment' ?></i>
                                        </button>
                                        <button type="button" class="wl-btn wl-btn-cal jadwalkan-btn" data-id="<?= $row['id'] ?>" data-tgl="<?= $row['jadwal'] ?>" title="Jadwalkan">
                                            <i class="material-icons">calendar_today</i>
                                        </button>
                                        <button type="button" class="wl-btn wl-btn-del hapus-btn" data-id="<?= $row['id'] ?>" data-kode="<?= $row['kode'] ?>" title="Hapus">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Bottom: Detail Grid -->
                                <div class="wl-details">
                                    <!-- Left: Phone + Date -->
                                    <div>
                                        <a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $row['cust_nomor']), 1) ?>" target="_blank" class="wl-phone">
                                            <i class="material-icons" style="font-size:13px;">phone</i> <?= htmlspecialchars($row['cust_nomor']) ?>
                                        </a>
                                        <div style="margin-top:6px;">
                                            <span class="wl-date <?= $is_overdue ? 'wl-date-red' : '' ?>"><?= $jadwal_display ?></span>
                                            <span class="wl-code" style="margin-left:6px;"><?= $row['kode'] ?></span>
                                        </div>
                                    </div>

                                    <!-- Center: Address -->
                                    <div>
                                        <p class="wl-addr" title="<?= htmlspecialchars($fullAddr) ?>">
                                            <i class="material-icons" style="font-size:12px;vertical-align:middle;color:#94a3b8;margin-right:2px;">location_on</i>
                                            <?= htmlspecialchars($fullAddr) ?>
                                            <button type="button" class="wl-btn-loc edit-loc-btn" 
                                                    data-id="<?= $row['id'] ?>" data-cust="<?= $row['customer_id'] ?>" 
                                                    data-lat="<?= $row['lat'] ?>" data-lon="<?= $row['lon'] ?>" data-rad="<?= $row['rad'] ?>">
                                                <i class="material-icons" style="font-size:12px;color:#3b82f6;">edit</i>
                                            </button>
                                        </p>
                                        <?php if (!empty($row['keterangan'])): ?>
                                        <p class="wl-ket">"<?= htmlspecialchars($row['keterangan']) ?>"</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Right: Request + Reason count -->
                                    <div class="wl-meta">
                                        <span class="wl-request-badge"><?= htmlspecialchars($row['request']) ?></span>
                                        <?php if ($hasReason): ?>
                                        <span style="font-size:10px;color:#16a34a;font-weight:600;"><?= $row['reason_count'] ?> catatan</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } } else { ?>
                        <div class="wl-empty">
                            <i class="material-icons">check_circle_outline</i>
                            <p>Semua kegiatan sudah terjadwalkan 🎉</p>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </main>

    <!-- Modal Jadwalkan -->
    <div class="modal fade" id="jadwalkanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Jadwalkan Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="jadwalkanForm">
                        <input type="hidden" id="modalKegiatanId">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:600;">Tanggal</label>
                            <input type="date" class="form-control border p-2" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:600;">Jam</label>
                            <input type="time" class="form-control border p-2" id="jam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:600;">Pilih Teknisi</label>
                            <div id="technician-list" class="border p-2 rounded" style="max-height:200px;overflow-y:auto;">
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <form id="reasonForm">
                                <input type="hidden" name="kegiatan_id" id="reKegId">
                                <textarea name="reason" class="form-control border p-2 mb-2" rows="4" placeholder="Tulis alasan penangguhan..." required></textarea>
                                <input type="file" name="media" class="form-control border mb-2">
                                <button type="submit" class="btn btn-primary w-100">Simpan Catatan</button>
                            </form>
                        </div>
                        <div class="col-md-7 border-start">
                            <div id="reasonList" style="max-height:300px;overflow-y:auto;"></div>
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
        const modalJadwal = new bootstrap.Modal(document.getElementById('jadwalkanModal'));
        const modalLokasi = new bootstrap.Modal(document.getElementById('locationModal'));
        const modalReason = new bootstrap.Modal(document.getElementById('reasonModal'));
        let map, marker, circle;

        $(document).ready(function() {
            $(document).on('click', '.jadwalkan-btn', function(e) {
                e.preventDefault();
                $('#modalKegiatanId').val($(this).data('id'));
                const tglFull = $(this).data('tgl');
                if(tglFull && tglFull !== '0000-00-00 00:00:00') {
                    const parts = tglFull.split(' ');
                    $('#tanggal').val(parts[0]); $('#jam').val(parts[1].substring(0,5));
                }
                fetchSchedules(); modalJadwal.show();
            });
            $(document).on('click', '.edit-loc-btn', function(e) {
                e.preventDefault();
                $('#locKegId').val($(this).data('id')); $('#locCustId').val($(this).data('cust'));
                $('#lat').val($(this).data('lat')); $('#lon').val($(this).data('lon'));
                $('#rad').val($(this).data('rad') || 50);
                modalLokasi.show(); setTimeout(initMap, 400);
            });
            $(document).on('click', '.reason-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id'); $('#reKegId').val(id);
                loadReasons(id); modalReason.show();
            });
            $(document).on('click', '.hapus-btn', function(e) {
                e.preventDefault();
                if(confirm('Hapus kegiatan ini?')) {
                    $.post('proses_hapus_kegiatan.php', { kegiatanId: $(this).data('id'), kode: $(this).data('kode') }, function() { location.reload(); });
                }
            });
            $('#btnSubmitJadwal').click(function() {
                const id = $('#modalKegiatanId').val(), tgl = $('#tanggal').val(), jam = $('#jam').val();
                const teks = $('.tek-check:checked').map(function() { return this.value; }).get();
                if(!tgl || !jam || teks.length === 0) return alert('Data tidak lengkap!');
                $.post('proses_jadwalkan.php', { kegiatanId: id, teknisi: teks, tanggal: tgl, jam: jam }, function(res) {
                    if(res.trim() === 'success') { $.post('wa-msg.php', { teknisi: teks, kegiatanId: id, tanggal: tgl, jam: jam }); location.reload(); }
                    else alert('Gagal menjadwalkan.');
                });
            });
        });

        function fetchSchedules() {
            const tgl = $('#tanggal').val(); if(!tgl) return;
            $.getJSON('cek_jadwal_teknisi.php?tanggal=' + tgl, function(data) {
                $('[id^="info-tk-"]').html('');
                for(let id in data) { $(`#info-tk-${id}`).html(data[id].map(i => `${i.customer} (${i.waktu})`).join(', ')); }
            });
        }

        function initMap() {
            const lt = parseFloat($('#lat').val()) || -6.175, ln = parseFloat($('#lon').val()) || 106.865;
            if(!map) {
                map = L.map('map').setView([lt, ln], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                marker = L.marker([lt, ln], {draggable:true}).addTo(map);
                circle = L.circle([lt, ln], {radius:$('#rad').val()}).addTo(map);
                marker.on('dragend', function(e) { const p = e.target.getLatLng(); $('#lat').val(p.lat.toFixed(6)); $('#lon').val(p.lng.toFixed(6)); circle.setLatLng(p); });
            } else { map.setView([lt, ln], 15); marker.setLatLng([lt, ln]); circle.setLatLng([lt, ln]).setRadius($('#rad').val()); }
            map.invalidateSize();
        }

        $('#btnSearchLoc').click(function() {
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${$('#addressSearch').val()}&limit=1`, function(data) {
                if(data.length > 0) { $('#lat').val(data[0].lat); $('#lon').val(data[0].lon); map.setView([data[0].lat, data[0].lon], 15); marker.setLatLng([data[0].lat, data[0].lon]); circle.setLatLng([data[0].lat, data[0].lon]); }
            });
        });

        $('#btnSaveLoc').click(function() { $.post('update_lokasi.php', $('#locationForm').serialize(), function() { location.reload(); }); });

        function loadReasons(id) {
            $('#reasonList').html('<div style="text-align:center;padding:20px;color:#94a3b8;">Memuat...</div>');
            $.getJSON('get_reasons.php?id=' + id, function(data) {
                let h = '';
                data.forEach(i => {
                    h += `<div style="padding:12px;border-bottom:1px solid #f1f5f9;margin-bottom:8px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:4px;">${i.formatted_date}</div>
                            <div style="font-size:12px;color:#475569;line-height:1.5;">${i.reason}</div>
                            ${i.media ? `<a href="uploads/reasons/${i.media}" target="_blank" style="font-size:11px;color:#3b82f6;text-decoration:none;margin-top:6px;display:inline-flex;align-items:center;gap:4px;"><i class="material-icons" style="font-size:14px;">attach_file</i> Lampiran</a>` : ''}
                          </div>`;
                });
                $('#reasonList').html(h || '<div style="text-align:center;padding:24px;color:#94a3b8;">Belum ada catatan.</div>');
            });
        }

        $('#reasonForm').submit(function(e) {
            e.preventDefault();
            $.ajax({ url: 'save_reason.php', type: 'POST', data: new FormData(this), processData: false, contentType: false,
                success: function() { loadReasons($('#reKegId').val()); $('#reasonForm')[0].reset(); }
            });
        });
    </script>
</body>
</html>