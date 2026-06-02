                        <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-none">
                            <div class="row px-4">

                                <?php
                                $customerId = $row["customer_id"];
                                $customerQuery = "SELECT * FROM customer WHERE id = '$customerId'";
                                $customerResult = mysqli_query($conn, $customerQuery);

                                if ($customerRow = mysqli_fetch_assoc($customerResult)) {

                                    if ($row["jadwal"] == '0000-00-00 00:00:00') {
                                ?>
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold text-xs">Dilaporkan</h6>
                                            <h6 class="mb-1 text-dark font-weight-bold text-xs">
                                            <span class="text-xs"><?php echo ucwords(strtolower($row["kegiatan"])); ?></span>
                                        </div>
                                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold text-xs"><?php echo date('d-m-y', strtotime($row["created_at"])); ?></h6>
                                            <span class="text-xs text-uppercase"><?php echo date('H:i', strtotime($row["jadwal"])); ?></span>
                                        </div>
                                        <?php
                                    } else {
                                        
                                        $tgl_request = strtotime($row["jadwal"]);
                                        $current_date = date('Y-m-d');
                                        $cekDate = date('Y-m-d', $tgl_request);
                                        $today = new DateTime();
                                        $esok = $today->modify('+1 day');
                                        $tomorrow = $esok->format('Y-m-d');
                                        $lusa = $esok->modify('+1 days');
                                        $day_after_tomorrow = $lusa->format('Y-m-d');

                                        if ($cekDate < $current_date) {
                                        ?>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-xs">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo ucwords(strtolower($row["kegiatan"])); ?></span>
                                            </div>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 font-weight-bold text-xs" style="color:red;"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase" style="color:red;"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>
                                        <?php
                                        } elseif ($cekDate >= $current_date && $cekDate <= $day_after_tomorrow) {
                                        ?>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-xs">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo ucwords(strtolower($row["kegiatan"])); ?></span>
                                            </div>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 font-weight-bold text-xs" style="color:blue;"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase" style="color:blue;"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>
                                        <?php
                                        } else {
                                        ?>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="text-dark font-weight-bold text-xs">Dijadwalkan</h6>
                                                <span class="text-xs"><?php echo ucwords(strtolower($row["kegiatan"])); ?></span>
                                            </div>
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-xs"><?php echo date('d-m-y', $tgl_request); ?></h6>
                                                <span class="text-xs text-uppercase"><?php echo date('H:i', $tgl_request); ?></span>
                                            </div>
                                    <?php
                                        }
                                    }
                                    $nomorHandphone = $customerRow['telp'];
                                    if (substr($nomorHandphone, 0, 1) === '0') {
                                        $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                    }

                                    ?>
                                    <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                        <a href="customer-detail.php?id_cust=<?php echo $customerId;?>"><h6 class="mb-1 text-dark font-weight-bold text-xs"><?php echo $customerRow["nama"]; ?></h6></a>
                                        <span class="text-xs text-uppercase"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $customerRow['telp']; ?></a></span>
                                    </div>
                                    <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-left">
                                        <h6 class="mb-1 text-dark font-weight-normal text-xs text-capitalize"><?php echo $customerRow["alamat"]; ?></h6>
                                    </div>
                                    
                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <span class="text-xs"><?php echo ucwords(strtolower($row["keterangan"])); ?></span>
                                            </div>
                                <?php

                                } else {
                                    echo "Data Customer Tidak Ditemukan";
                                }

                                ?>
                                <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                    <h6 class="mb-1 text-dark font-weight-bold text-xs"><?php echo $row["request"]; ?></h6>
                                </div>

                                <div class="col-6 col-md-2 mb-2 mb-md-0 d-flex justify-content-center align-items-center  text-left text-md-center">
                                    <button class="btn btn-primary jadwalkan-btn w-25 p-2 text-center me-1" data-id="<?php echo $row["id"]; ?>" data-tgl-request="<?php echo $row["jadwal"]; ?>">
                                        <i class="material-icons opacity-10">arrow_upward</i></i>
                                    </button>
                                    <?php
                                    echo ' <button class="btn btn-danger hapus-btn w-25 p-2 text-center me-1" data-id="' . $row["id"] . '" data-kode="' . $row["kode"] . '" data-nama="' . $nmUser . '"><i class="material-icons opacity-10">delete</i></button>';
                                    ?>
                                </div>
                        </li>