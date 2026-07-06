<?php
include "conn.php";

if (isset($_POST['id_sales']) && isset($_POST['kode_transaksi'])) {
    $id_teknisi = $_POST['id_sales'];
    $kode_transaksi = $_POST['kode_transaksi'];

    // Query untuk mengambil data dari database
    $sql = "SELECT tks.*, s.nama, tks.id_sales, sc.nama AS nama_cust, sc.id AS id_cust,
                   ks.id AS kode_transaksi, ks.jadwal AS tgl_visits,
                   IFNULL(ps.status, 'dijadwalkan') AS status,
                   ps.ci_at AS tgl_mulai, ps.co_at AS tgl_selesai,
                   CONCAT(IFNULL(ps.lat_ci, ''), ',', IFNULL(ps.lon_ci, '')) AS lokasi_mulai,
                   CONCAT(IFNULL(ps.lat_co, ''), ',', IFNULL(ps.lon_co, '')) AS lokasi_selesai,
                   ps.keterangan AS hasil_visits, ps.catatan_visit AS keterangan_tambahan,
                   ps.image_1 AS gambar_1, ps.image_2 AS gambar_2, ps.image_3 AS gambar_3
             FROM team_kegiatan_sales tks
             JOIN sales s ON tks.id_sales = s.id
             JOIN kegiatan_sales ks ON tks.id_kegiatan_sales = ks.id
             JOIN sales_customer sc ON ks.id_customer = sc.id
             LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
             WHERE tks.id_sales = ? AND ks.id = ? AND tks.deleted_at IS NULL";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $id_teknisi, $kode_transaksi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

<style>
  /* ── CSS Style Premium untuk Detail Pengerjaan ── */
  .details-container {
    font-family: 'Outfit', 'Inter', 'Segoe UI', sans-serif;
    color: #334155;
    padding: 0px;
  }

  /* Override Parent Modal Styles */
  #detailModal .modal-content {
    border-radius: 20px !important;
    border: none !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    overflow: hidden !important;
    background: #fff !important;
  }
  #detailModal .modal-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 20px 24px !important;
  }
  #detailModal .modal-title {
    font-weight: 700 !important;
    color: #0f172a !important;
    font-size: 16px !important;
  }
  #detailModal .modal-body {
    padding: 24px !important;
  }
  #detailModal .modal-footer {
    border-top: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    padding: 16px 24px !important;
  }
  #detailModal .modal-footer .btn {
    border-radius: 12px !important;
    padding: 10px 24px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: 0.03em !important;
    margin: 0 !important;
  }

  /* Badge status */
  .badge-status-pills {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .badge-status-pills.status-selesai {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
  }
  .badge-status-pills.status-berjalan {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
  }
  .badge-status-pills.status-dijadwalkan {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
  }

  /* Header card */
  .header-card-detail {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #2563eb 100%);
    color: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
    position: relative;
    overflow: hidden;
  }
  .header-card-detail::after {
    content: '';
    position: absolute;
    right: -20px; top: -20px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.03);
  }

  /* Timeline */
  .detail-timeline {
    position: relative;
    padding-left: 28px;
    margin-left: 14px;
    border-left: 2px dashed #cbd5e1;
  }
  .timeline-item-detail {
    position: relative;
    margin-bottom: 28px;
  }
  .timeline-item-detail:last-child {
    margin-bottom: 0;
  }
  .timeline-dot-detail {
    position: absolute;
    left: -40px;
    top: 2px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #fff;
    border: 2.5px solid #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
  }
  .timeline-item-detail.active .timeline-dot-detail {
    border-color: #3b82f6;
    background: #3b82f6;
    color: #fff;
  }
  .timeline-item-detail.success .timeline-dot-detail {
    border-color: #10b981;
    background: #10b981;
    color: #fff;
  }
  .timeline-item-detail.info .timeline-dot-detail {
    border-color: #6366f1;
    background: #6366f1;
    color: #fff;
  }
  .timeline-dot-detail span {
    font-size: 12px;
    font-weight: bold;
  }
  
  .timeline-box-detail {
    background: #f8fafc;
    border-radius: 14px;
    padding: 16px;
    border: 1px solid #edf2f7;
    transition: all 0.2s;
  }
  .timeline-box-detail:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
  }
  .timeline-lbl-detail {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 4px;
  }
  .timeline-val-detail {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
  }
  .timeline-loc-detail {
    font-size: 13px;
    color: #475569;
    background: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
  }
  .timeline-loc-detail span.icon-map {
    color: #94a3b8;
    font-size: 16px;
    margin-top: 2px;
  }
  .gmaps-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    color: #3b82f6;
    text-decoration: none;
    margin-top: 8px;
    transition: all 0.2s;
  }
  .gmaps-btn:hover {
    color: #1d4ed8;
    text-decoration: underline;
  }

  /* Grid rincian hasil */
  .grid-rincian-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 24px;
  }
  @media (max-width: 768px) {
    .grid-rincian-detail {
      grid-template-columns: 1fr;
    }
  }
  .card-rincian-detail {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 18px;
    border-left: 4px solid #3b82f6;
  }
  .card-rincian-detail.purple {
    border-left-color: #8b5cf6;
  }
  .lbl-rincian-detail {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .val-rincian-detail {
    font-size: 13.5px;
    color: #1e293b;
    line-height: 1.6;
    font-weight: 500;
  }

  /* Gallery */
  .gallery-wrapper-detail {
    margin-top: 28px;
  }
  .gallery-header-detail {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .gallery-grid-detail {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
  }
  .gallery-card-detail {
    border-radius: 12px;
    overflow: hidden;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    transition: all 0.25s ease;
  }
  .gallery-card-detail:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
  }
  .gallery-img-wrapper-detail {
    width: 100%;
    height: 110px;
    overflow: hidden;
    background: #f1f5f9;
  }
  .gallery-img-detail {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    transition: all 0.3s ease;
  }
  .gallery-card-detail:hover .gallery-img-detail {
    transform: scale(1.05);
  }
  .gallery-btn-detail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 8px;
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    border-top: 1px solid #e2e8f0;
    transition: all 0.2s;
  }
  .gallery-card-detail:hover .gallery-btn-detail {
    background: #2563eb;
    color: #fff;
    border-top-color: #2563eb;
  }
</style>

<div class="details-container">
    <?php
    if ($result) {
        $rowNumber = 1;
        while ($data = mysqli_fetch_assoc($result)) {
            $namaTek = $data['nama'];
            $customer = $data['nama_cust'];
            $ketFinish = $data['hasil_visits'];
            $ketTam = $data['keterangan_tambahan'];
            $gambar1 = $data['gambar_1'];
            $gambar2 = $data['gambar_2'];
            $gambar3 = $data['gambar_3'];

            $tgl_request = $data['tgl_visits'];
            $formattedDateReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_request)) : '-';
            $formattedTimeReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_request)) : '-';

            $tgl_mulai = $data['tgl_mulai'];
            $formattedDateMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_mulai)) : '-';
            $formattedTimeMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_mulai)) : '-';

            $tgl_selesai = $data['tgl_selesai'];
            $formattedDateSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_selesai)) : '-';
            $formattedTimeSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_selesai)) : '-';

            $status = strtolower($data['status']);
            $statusClass = 'status-dijadwalkan';
            if ($status == 'selesai') $statusClass = 'status-selesai';
            if ($status == 'berjalan' || $status == 'proses') $statusClass = 'status-berjalan';
    ?>
            <!-- ═══ Header Card ═══ -->
            <div class="header-card-detail">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                    <div>
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgba(255,255,255,0.7); margin-bottom:4px;">Sales Agent</div>
                        <h4 style="color:#fff; margin:0; font-size:18px; font-weight:700;"><?php echo htmlspecialchars($namaTek); ?></h4>
                        <div style="font-size:13px; color:rgba(255,255,255,0.8); margin-top:6px; display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-outlined" style="font-size:16px;">storefront</span>
                            <?php echo htmlspecialchars($customer); ?>
                        </div>
                    </div>
                    <div class="badge-status-pills <?php echo $statusClass; ?>">
                        <span style="width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; margin-right:4px;"></span>
                        <?php
                        if ($status == 'berjalan') {
                            echo 'Diproses';
                        } else {
                            echo ucfirst($status);
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- ═══ Timeline Detail ═══ -->
            <div class="detail-timeline">
                
                <!-- Step 1: Dijadwalkan -->
                <div class="timeline-item-detail info">
                    <div class="timeline-dot-detail">
                        <span class="material-symbols-outlined" style="font-size:12px;">calendar_month</span>
                    </div>
                    <div class="timeline-box-detail">
                        <div class="timeline-lbl-detail">Tanggal / Jam Kunjungan</div>
                        <div class="timeline-val-detail"><?php echo $formattedDateReq . " pukul " . $formattedTimeReq; ?></div>
                    </div>
                </div>

                <!-- Step 2: Mulai (Check-In) -->
                <div class="timeline-item-detail active">
                    <div class="timeline-dot-detail">
                        <span class="material-symbols-outlined" style="font-size:12px;">login</span>
                    </div>
                    <div class="timeline-box-detail">
                        <div class="timeline-lbl-detail">Mulai (Check-In)</div>
                        <div class="timeline-val-detail">
                            <?php echo ($formattedDateMli != '-') ? $formattedDateMli . " pukul " . $formattedTimeMli : '-'; ?>
                        </div>
                        <div class="timeline-loc-detail">
                            <span class="material-symbols-outlined icon-map">location_on</span>
                            <div>
                                <?php
                                include "get-lok.php";
                                $lokasi_parts_ci = explode(',', $data['lokasi_mulai']);
                                if (count($lokasi_parts_ci) == 2 && !empty($lokasi_parts_ci[0]) && !empty($lokasi_parts_ci[1])) {
                                    $latitude = $lokasi_parts_ci[0];
                                    $longitude = $lokasi_parts_ci[1];
                                    $addressFunction = ${"getAddressFromCoordinates$rowNumber"};
                                    echo htmlspecialchars($addressFunction($latitude, $longitude));
                                    echo "<br><a href='https://www.google.com/maps/search/?api=1&query=$latitude,$longitude' target='_blank' class='gmaps-btn'><span class='material-symbols-outlined' style='font-size:12px;'>map</span> Buka di Google Maps</a>";
                                } else {
                                    echo "Lokasi mulai tidak tersedia.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Selesai (Check-Out) -->
                <div class="timeline-item-detail success">
                    <div class="timeline-dot-detail">
                        <span class="material-symbols-outlined" style="font-size:12px;">logout</span>
                    </div>
                    <div class="timeline-box-detail">
                        <div class="timeline-lbl-detail">Selesai (Check-Out)</div>
                        <div class="timeline-val-detail">
                            <?php echo ($formattedDateSls != '-') ? $formattedDateSls . " pukul " . $formattedTimeSls : '-'; ?>
                        </div>
                        <div class="timeline-loc-detail">
                            <span class="material-symbols-outlined icon-map">location_on</span>
                            <div>
                                <?php
                                include "get-lok-end.php";
                                $lokasi_parts_co = explode(',', $data['lokasi_selesai']);
                                if (count($lokasi_parts_co) == 2 && !empty($lokasi_parts_co[0]) && !empty($lokasi_parts_co[1])) {
                                    $latitude = $lokasi_parts_co[0];
                                    $longitude = $lokasi_parts_co[1];
                                    $addressFunctionEnd = ${"getAddressFromCoordinatesEnd$rowNumber"};
                                    echo htmlspecialchars($addressFunctionEnd($latitude, $longitude));
                                    echo "<br><a href='https://www.google.com/maps/search/?api=1&query=$latitude,$longitude' target='_blank' class='gmaps-btn'><span class='material-symbols-outlined' style='font-size:12px;'>map</span> Buka di Google Maps</a>";
                                } else {
                                    echo "Lokasi selesai tidak tersedia.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ═══ Rincian Hasil ═══ -->
            <div class="grid-rincian-detail">
                <div class="card-rincian-detail">
                    <div class="lbl-rincian-detail">
                        <span class="material-symbols-outlined" style="font-size:15px; color:#2563eb;">description</span>
                        Hasil Visit
                    </div>
                    <div class="val-rincian-detail">
                        <?php echo !empty($ketFinish) ? nl2br(htmlspecialchars($ketFinish)) : '-'; ?>
                    </div>
                </div>
                <div class="card-rincian-detail purple">
                    <div class="lbl-rincian-detail">
                        <span class="material-symbols-outlined" style="font-size:15px; color:#8b5cf6;">note_alt</span>
                        Keterangan Tambahan
                    </div>
                    <div class="val-rincian-detail">
                        <?php echo !empty($ketTam) ? nl2br(htmlspecialchars($ketTam)) : '-'; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ Gallery Foto ═══ -->
            <?php
            $gambar_finish_columns = array('gambar_1', 'gambar_2', 'gambar_3');
            $hasImages = false;
            foreach ($gambar_finish_columns as $col) {
                if (!empty($data[$col]) && $data[$col] !== "NO" && $data[$col] !== "-") {
                    $hasImages = true;
                }
            }

            if ($hasImages) :
            ?>
                <div class="gallery-wrapper-detail">
                    <div class="gallery-header-detail">
                        <span class="material-symbols-outlined" style="font-size:18px; color:#64748b;">photo_library</span>
                        Dokumentasi Foto
                    </div>
                    <div class="gallery-grid-detail">
                        <?php
                        foreach ($gambar_finish_columns as $column) :
                            if (!empty($data[$column]) && $data[$column] !== "NO" && $data[$column] !== "-") :
                                $imageUrl = "https://api-teknisi.id-giti.com/storage/image/" . htmlspecialchars($data[$column]);
                        ?>
                                <div class="gallery-card-detail">
                                    <div class="gallery-img-wrapper-detail">
                                        <img src="<?php echo $imageUrl; ?>" class="gallery-img-detail" alt="Dokumentasi">
                                    </div>
                                    <a href="<?php echo $imageUrl; ?>" target="_blank" class="gallery-btn-detail">
                                        <span class="material-symbols-outlined" style="font-size:14px;">visibility</span>
                                        Lihat Foto
                                    </a>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>

    <?php
            $rowNumber++;
        }
    } else {
        echo "<div class='text-center py-4 text-muted'>Error: " . mysqli_error($conn) . "</div>";
    }
    ?>
</div>
<?php
    mysqli_stmt_close($stmt);
}
?>