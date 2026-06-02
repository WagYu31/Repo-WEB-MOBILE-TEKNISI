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
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo ucwords(strtolower($row['kegiatan'])); ?></h6>
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
            $datetime = $row["jadwal"];
            $formattedTime = date("H:i", strtotime($datetime));
            $formattedDate = date("d-m-Y", strtotime($datetime));
            $tanggal_sekarang = date("d-m-Y");

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


            $nomorHandphone = $row['cust_nomor'][0];
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
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $row['nama_customer']; ?></h6>
                </div>
            </div>

            <!-- No Telepon -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">No Telepon</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm">
                        <a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $row['cust_nomor']; ?></a>
                    </h6>
                </div>
            </div>

            <!-- Request By -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Request By</span>
                </div>
                <div class="col-6">
                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $row['request']; ?></h6>
                </div>
            </div>

            <!-- Teknisi -->
            <div class="row mb-1">
                <div class="col-6 mt-n1">
                    <span class="text-xs">Teknisi</span>
                </div>

                <div class="col-6">
                    <?php
                    $sqlGetTek = "SELECT k.*, t.nama_teknisi, t.teknisi_id FROM kegiatan k
                                    JOIN team_kegiatan t ON k.id = t.kegiatan_id
                                WHERE k.kode = '$kodeTransaksi'
                                AND k.id = (
                                    SELECT MAX(id) 
                                    FROM kegiatan 
                                    WHERE kode = '$kodeTransaksi'
                                )";
                    $queryGetTek = mysqli_query($conn, $sqlGetTek);
                    while ($rowGetTek = mysqli_fetch_assoc($queryGetTek)) {
                        $ket_finish = $rowGetTek['status'];
                        if ($ket_finish !== "selesai by admin") {
                    ?>
                            <div class="d-flex align-items-center mb-1 justify-content-start">
                                    <h6 class="mb-0 text-dark font-weight-bold text-sm mr-2"><?php echo $rowGetTek['nama_teknisi']; ?></h6>
                            </div>

                    <?php
                        }
                    }
                    ?>
                </div>


            </div>
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