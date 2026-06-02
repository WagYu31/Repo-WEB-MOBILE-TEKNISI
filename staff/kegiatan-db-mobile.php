<li class="list-group-item border-0 d-flex flex-column mb-2 border-radius-lg d-md-none d-block p-3" style="width: 100%;">
    <div class="row px-3">
        <?php
        $updatedStatus = '';

        foreach ($data['status'] as $status) {
            if ($status != 'selesai') {
                $updatedStatus = $status;

                switch ($updatedStatus) {
                    case 'waiting':
                        $updatedStatus = 'Dalam Antrian';
                        break;
                    case 'dijadwalkan':
                        $updatedStatus = 'Dijadwalkan';
                        break;
                    case 'berjalan':
                        $updatedStatus = 'Dalam Proses';
                        break;
                    case 'selesai':
                        $updatedStatus = 'Selesai';
                        break;
                    case 'selesai by admin':
                        $updatedStatus = 'Diselesaikan oleh Admin';
                        break;
                    case 'Lanjut Nanti':
                        $updatedStatus = 'Berlanjut';
                        break;
                    case 'Lanjutan':
                        $updatedStatus = 'Dilanjutkan';
                        break;
                    default:
                        $updatedStatus = $data['status'][0];
                }
                break;
            }
        }

        if (empty($updatedStatus)) {
            $updatedStatus = 'Selesai';
        }
        ?>

        <div class="col-12">
            <!-- Kegiatan -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Kegiatan</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo ucwords(strtolower($data['kegiatan'][0])); ?></h6>
                </div>
            </div>

            <!-- Status -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Status</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $updatedStatus; ?></h6>
                </div>
            </div>

            <!-- Tanggal dan Jam -->
            <?php
            $datetime = $data['jadwal'][0];
            $formattedTime = date("H:i", strtotime($datetime));
            $formattedDate = date("d-m-Y", strtotime($datetime));
            $tanggal_sekarang = date("d-m-Y");

            if ($formattedDate > $tanggal_sekarang) {
                // Tanggal di masa depan
                echo "
                    <div class='row mb-1'>
                        <div class='col-6 mt-n1'>
                            <span class='text-xs text-primary'>Tanggal</span>
                        </div>
                        <div class='col-6'>
                            <h6 class='text-dark font-weight-bold text-sm'>$formattedDate</h6>
                        </div>
                    </div>
                    <div class='row mb-1'>
                        <div class='col-6 mt-n1'>
                            <span class='text-xs text-primary'>Jam</span>
                        </div>
                        <div class='col-6'>
                            <h6 class='text-dark font-weight-bold text-sm'>$formattedTime</h6>
                        </div>
                    </div>
                ";
            } else {
                // Tanggal saat ini atau masa lalu
                echo "
                    <div class='row mb-1'>
                        <div class='col-6 mt-n1'>
                            <span class='text-xs'>Tanggal</span>
                        </div>
                        <div class='col-6'>
                            <h6 class='text-dark font-weight-bold text-sm'>$formattedDate</h6>
                        </div>
                    </div>
                    <div class='row mb-1'>
                        <div class='col-6 mt-n1'>
                            <span class='text-xs'>Jam</span>
                        </div>
                        <div class='col-6'>
                            <h6 class='text-dark font-weight-bold text-sm'>$formattedTime</h6>
                        </div>
                    </div>
                ";
            }

            $nomorHandphone = $data['cust_nomor'][0];
            if (substr($nomorHandphone, 0, 1) === '0') {
                $nomorHandphone = '62' . substr($nomorHandphone, 1);
            }
            ?>

            <!-- Customer -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Customer</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $data['customer'][0]; ?></h6>
                </div>
            </div>

            <!-- No Telepon -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">No Telepon
                    </span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm">
                        <a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $data['cust_nomor'][0]; ?></a></h6>
                </div>
            </div>

            <!-- Request By -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Request By</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $data['request'][0]; ?></h6>
                </div>
            </div>

            <!-- Teknisi -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">TEKNISI</span>
                </div>
            </div>

            <?php
            // Ambil kegiatan dan teknisi yang terkait
            $sqlGetLatestKegiatan = "SELECT id FROM kegiatan WHERE kode = '$kodeTransaksi' ORDER BY id DESC LIMIT 1";
            $queryGetLatestKegiatan = mysqli_query($conn, $sqlGetLatestKegiatan);
            $rowLatestKegiatan = mysqli_fetch_assoc($queryGetLatestKegiatan);
            $latestKegiatanId = $rowLatestKegiatan['id'];

            $sqlGetTeknisi = "SELECT nama_teknisi, teknisi_id FROM team_kegiatan WHERE kegiatan_id = '$latestKegiatanId' AND deleted_at IS NULL";
            $queryGetTeknisi = mysqli_query($conn, $sqlGetTeknisi);

            while ($rowTeknisi = mysqli_fetch_assoc($queryGetTeknisi)) {
                $teknisi_id = $rowTeknisi['teknisi_id'];
                $getStatusToday = "SELECT waktu_mulai, waktu_selesai FROM pelaksanaan_kegiatan WHERE kegiatan_id = '$latestKegiatanId' AND teknisi_id = '$teknisi_id'";
                $resultGetStatusToday = mysqli_query($conn, $getStatusToday);
                $rowGetStatusToday = mysqli_fetch_assoc($resultGetStatusToday);

                $waktuMulai = $rowGetStatusToday['waktu_mulai'];
                $waktuSelesai = $rowGetStatusToday['waktu_selesai'];

                $statusTeknisi = !is_null($waktuSelesai) ? 'Selesai' : (!is_null($waktuMulai) ? 'Dikerjakan' : 'Dijadwalkan');
                $statusClass = $statusTeknisi == 'Selesai' ? 'bg-success' : ($statusTeknisi == 'Dikerjakan' ? 'bg-info' : 'bg-secondary');
                ?>

                <div class="row mb-1">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-dark font-weight-bold text-sm"><?php echo $rowTeknisi['nama_teknisi']; ?></h6>
                            <div class="<?php echo $statusClass; ?> text-white rounded-pill p-1 px-2 text-center" style="font-size:11px;">
                                <?php echo $statusTeknisi; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            }
            ?>

            <!-- Action Buttons -->
            <div class="row mb-1 mt-3">
                <div class="col-12 mt-2">
                    <a class="btn btn-info view-btn w-20 p-2 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">visibility</i></a>
                    <button class="btn btn-warning edit-btn w-20 p-2 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">autorenew</i></button>
                    <a class="btn btn-danger delete-btn w-20 p-2 text-center" href="delete-kegiatan.php?kode=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">delete</i></a>
                </div>
            </div>
        </div>
    </div>
</li>
