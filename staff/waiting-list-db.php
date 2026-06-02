<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12">
            <button id="toggleLoadMore2" type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Waiting List</button>
        </div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4" id="loadMoreX2" style="display: block;">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <?php
        // $sql = "SELECT k.* FROM kegiatan k WHERE k.status = 'waiting' AND k.deleted_at IS NULL ORDER BY CASE WHEN DATE(k.jadwal) = CURDATE() THEN 1 WHEN DATE(k.jadwal) = CURDATE() + INTERVAL 1 DAY THEN 2 WHEN DATE(k.jadwal) > CURDATE() THEN 3 ELSE 4 END ASC, k.created_at ASC";
        $sql = "SELECT k.* FROM kegiatan k WHERE k.status = 'waiting' AND k.deleted_at IS NULL ORDER BY k.created_at ASC";
        $result = mysqli_query($conn, $sql);
        ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Status & Jadwal</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Penangguhan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alamat & Keterangan</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Request</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $customer_id = $row["customer_id"];
                                $customerQuery = "SELECT nama, telp FROM customer WHERE id = '$customer_id'";
                                $customerResult = mysqli_query($conn, $customerQuery);
                                $customerRow = mysqli_fetch_assoc($customerResult);
                        ?>
                        <tr>
                            <td class="ps-4">
                                <?php
                                $status_display = "Dilaporkan";
                                $date_color = "text-dark";
                                $jadwal_display = date('d-m-y', strtotime($row["created_at"]));
                                $jam_display = date('H:i', strtotime($row["created_at"]));

                                if ($row["jadwal"] != '0000-00-00 00:00:00') {
                                    $status_display = "Dijadwalkan";
                                    $tgl_request = strtotime($row["jadwal"]);
                                    $jadwal_display = date('d-m-y', $tgl_request);
                                    $jam_display = date('H:i', $tgl_request);
                                    $cekDate = date('Y-m-d', $tgl_request);
                                    if ($cekDate < date('Y-m-d')) $date_color = "text-danger";
                                    if ($cekDate >= date('Y-m-d') && $cekDate <= date('Y-m-d', strtotime('+2 days'))) $date_color = "text-primary";
                                }
                                ?>
                                <p class="text-xs font-weight-bold mb-0 text-capitalize"><?= htmlspecialchars($status_display); ?></p>
                                <p class="text-xs text-secondary mb-0 text-capitalize"><?= htmlspecialchars($row["kegiatan"]); ?></p>
                                <p class="text-xs <?= $date_color ?> font-weight-bold mb-0"><?= $jadwal_display ?> / <?= $jam_display ?></p>
                            </td>
                            <td>
                                <h6 class="mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $row['customer_id']; ?>"><?= htmlspecialchars($customerRow["nama"]); ?></a></h6>
                                <p class="text-xs text-secondary mb-0"><a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $customerRow['telp']), 1); ?>" target="_blank"><?= htmlspecialchars($customerRow['telp']); ?></a></p>
                            </td>
                            <td class="text-center">
                                <?php 
                                $hasReason = !empty($row["reason"]);
                                $btnReasonClass = $hasReason ? "btn-outline-success" : "btn-outline-danger";
                                $btnReasonIcon = $hasReason ? "edit" : "add";
                                $reasonValue = $hasReason ? htmlspecialchars($row["reason"]) : "";
                                ?>
                                <button class="btn btn-sm <?= $btnReasonClass ?> reason-btn mb-0" data-id="<?= $row['id'] ?>" data-reason="<?= $reasonValue ?>" title="Alasan Penangguhan">
                                    <i class="material-icons" style="font-size:14px;"><?= $btnReasonIcon ?></i>
                                </button>
                            </td>
                            <td class="text-wrap">
                                <p class="text-xs font-weight-bold mb-0 text-capitalize"><?= htmlspecialchars($row["alamat"]); ?></p>
                                <p class="text-xs text-secondary mb-0 fst-italic">"<?= !empty($row["keterangan"]) ? htmlspecialchars($row["keterangan"]) : '-'; ?>"</p>
                            </td>
                            <td class="text-center">
                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($row["request"]); ?></p>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-primary jadwalkan-btn" data-id="<?= $row["id"]; ?>" data-tgl-request="<?= $row["jadwal"]; ?>" title="Jadwalkan">
                                        <i class="material-icons">arrow_upward</i>
                                    </button>
                                    <button class="btn btn-danger hapus-btn" data-id="<?= $row["id"]; ?>" data-kode="<?= $row["kode"]; ?>" data-nama="<?= htmlspecialchars($nmUser); ?>" title="Hapus">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5'>Tidak ada data permintaan baru.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Alasan Penangguhan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reasonForm">
                    <input type="hidden" id="reasonKegiatanId" name="kegiatan_id">
                    <div class="mb-3">
                        <label for="reasonText" class="form-label">Masukkan Alasan:</label>
                        <textarea class="form-control border p-2" id="reasonText" name="reason" rows="4" placeholder="Tulis alasan penangguhan di sini..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveReasonBtn">Simpan</button>
            </div>
        </div>
    </div>
</div>