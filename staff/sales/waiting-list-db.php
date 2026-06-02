<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12">
            <button id="toggleLoadMore2" type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Waiting List</button>
        </div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4" id="loadMoreX2" style="display: block;">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <!--<div class="card-header pb-0 p-3">-->
        <!--    <div class="row">-->
        <!--        <div class="col-12 d-flex align-items-center">-->
        <!--            <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Waiting List</h6>-->
        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->
        <?php
$sql = "SELECT k.*, t.nama AS nama_teknisi 
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        WHERE k.status = 'Waiting'
        ORDER BY 
            CASE 
                WHEN DATE(k.tgl_request) = CURDATE() THEN 1   -- Hari ini
                WHEN DATE(k.tgl_request) = CURDATE() + INTERVAL 1 DAY THEN 2   -- Besok
                WHEN DATE(k.tgl_request) > CURDATE() THEN 3   -- Setelah hari ini
                ELSE 4   -- Sebelum hari ini
            END ASC,
            CASE 
                WHEN DATE(k.tgl_request) = CURDATE() THEN k.tgl_request
                ELSE k.tgl_update 
            END ASC";


        $result = mysqli_query($conn, $sql);

        ?>
        <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-2" id="data-tek">

                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
                    <div class="row px-4">
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                            <span class="text-xs">/ Kegiatan</span>
                        </div>

                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Tanggal</h6>
                            <span class="text-xs">/ Jam</span>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6>
                            <span class="text-xs">/ No Telepon</span>
                        </div>

                        <div class="col-6 col-md-3 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Permintaan Dari</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0  text-start text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Aksi</h6>
                        </div>
                    </div>
                </li>
                <?php
                setlocale(LC_TIME, 'id_ID.utf8');

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>

                        <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                            <div class="row px-4">

                                <?php
                                $customerId = $row["id_cust"];
                                $customerQuery = "SELECT * FROM customer WHERE id_cust = '$customerId'";
                                $customerResult = mysqli_query($conn, $customerQuery);

                                if ($customerRow = mysqli_fetch_assoc($customerResult)) {

                                    if ($row["tgl_request"] == '0000-00-00 00:00:00') {
                                ?>

                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Dilaporkan</h6>
                                            <span class="text-xs"><?php echo $row["jenis"]; ?></span>
                                        </div>

                                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo date('d-m-y', strtotime($row["tgl_update"])); ?></h6>
                                            <span class="text-xs text-uppercase"><?php echo date('H:i', strtotime($row["tgl_update"])); ?></span>
                                        </div>

                                        <?php
                                    } else {
                                        // Mengambil tanggal request dari database
                                        $tgl_request = strtotime($row["tgl_request"]);

                                        $current_date = date('Y-m-d');

                                        $cekDate = date('Y-m-d', $tgl_request);
                                        $today = new DateTime();
                                        $esok = $today->modify('+1 day');
                                        $tomorrow = $esok->format('Y-m-d');
                                        $lusa = $esok->modify('+1 days');
                                        $day_after_tomorrow = $lusa->format('Y-m-d');

                                        // Mengecek apakah tanggal request adalah besok atau lusa
                                        if ($cekDate < $current_date) {
                                        ?>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo $row["jenis"]; ?></span>
                                            </div>

                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 font-weight-bold text-sm" style="color:red;"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase" style="color:red;"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>

                                        <?php
                                        } elseif ($cekDate >= $current_date && $cekDate <= $day_after_tomorrow) {

                                        ?>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo $row["jenis"]; ?></span>
                                            </div>

                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 font-weight-bold text-sm" style="color:blue;"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase" style="color:blue;"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>

                                        <?php
                                        } else {
                                        ?>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <h6 class="text-dark font-weight-bold text-sm">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo $row["jenis"]; ?></span>
                                            </div>

                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>

                                    <?php
                                        }
                                    }


                                    $nomorHandphone = $customerRow['nomor_tlp'];

                                    // Cek apakah nomor handphone dimulai dengan angka 0
                                    if (substr($nomorHandphone, 0, 1) === '0') {
                                        // Ganti angka 0 dengan 62
                                        $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                    }


                                    ?>
                                    <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $customerRow["nama"]; ?></h6>
                                        <span class="text-xs text-uppercase"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $customerRow['nomor_tlp']; ?></a></span>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2 mb-md-0 text-left text-md-left">
                                        <h6 class="mb-1 text-dark font-weight-normal text-sm text-capitalize"><?php echo $customerRow["alamat"]; ?></h6>
                                    </div>
                                <?php


                                } else {
                                    echo "Data Customer Tidak Ditemukan"; // Tampilkan tanggal request
                                }

                                // Query untuk mengambil nama customer berdasarkan id_cust

                                ?>
                                <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $row["req_by"]; ?></h6>
                                </div>

                                <div class="col-6 col-md-2 mb-2 mb-md-0 d-flex justify-content-center align-items-center  text-left text-md-center">
                                    <button class="btn btn-primary jadwalkan-btn w-25 p-2 text-center me-1" data-id="<?php echo $row["id_kegiatan"]; ?>" data-tgl-request="<?php echo $row["tgl_request"]; ?>">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <?php
                                    echo ' <button class="btn btn-danger hapus-btn w-25 p-2 text-center me-1" data-id="' . $row["id_kegiatan"] . '" data-kode="' . $row["kode_transaksi"] . '" data-nama="' . $nmUser . '"><i class="far fa-trash-alt"></i></button>';
                                    ?>
                                </div>
                        <?php

                    }
                } else {
                    echo "<div class='p-4'>Tidak ada data permintaan baru.</div>";
                }

                        ?>

            </ul>
        </div>
    </div>
</div>