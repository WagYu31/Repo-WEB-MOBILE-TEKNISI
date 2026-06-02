<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block">
                        <div class="row px-4">
                            <?php
                            $updatedStatus = '';

                            foreach ($data['status'] as $status) {
                                if ($status != 'Clear') {
                                    $updatedStatus = $status;

                                    switch ($updatedStatus) {
                                        case 'Waiting':
                                            $updatedStatus = 'Belum Dijadwalkan';
                                            break;
                                        case 'Pending':
                                            $updatedStatus = 'Dijadwalkan';
                                            break;
                                        case 'On Process':
                                            $updatedStatus = 'Diproses';
                                            break;
                                        case 'Pause':
                                            $updatedStatus = 'Lanjut Nanti';
                                            break;
                                        case 'Reschedule':
                                            $updatedStatus = 'Dijadwalkan Ulang';
                                            break;
                                        case 'Reschedule2':
                                            $updatedStatus = 'Dijadwalkan Ulang';
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

                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $data['jenis'][0]; ?></h6>
                                <span class="text-xs"><?php echo $updatedStatus; ?></span>
                            </div>

                            <?php


                            $smallestIndex = 0; // Initialize with the first index
                            $smallestDateTime = strtotime($data["tgl_mulai"][0]);

                            foreach ($data["tgl_mulai"] as $index => $datetime) {
                                $currentDateTime = strtotime($datetime);

                                if ($currentDateTime < $smallestDateTime) {
                                    $smallestDateTime = $currentDateTime;
                                    $smallestIndex = $index;
                                }
                            }

                            $selectedDatetime = $data["tgl_mulai"][$smallestIndex];
                            $rescheduleDates = $groupedData[$kodeTransaksi]['tgl_reschedule'];

                            $rescheduleNotNull = null;
                            foreach ($rescheduleDates as $date) {
                                if ($date !== null) {
                                    $rescheduleNotNull = $date;
                                    break;
                                }
                            }

                            if ($rescheduleNotNull !== null) {
                                $datetime = $rescheduleNotNull;
                            } else {
                                $datetime = $data["tgl_request"][0];
                            }

                            // Format dan tampilkan tanggal dan waktu
                            $formattedTime = date("H:i", strtotime($datetime));
                            $formattedDate = date("d-m-Y", strtotime($datetime));
                            $tanggal_sekarang = date("d-m-Y");
                            $timestamp_now = strtotime($tanggal_sekarang);
                            $timestamp_tomorrow = strtotime("+1 day", $timestamp_now);
                            $timestamp_after_tomorrow = strtotime("+2 day", $timestamp_now);

                            if ($formattedDate > $tanggal_sekarang && $formattedDate <= date("d-m-Y", $timestamp_after_tomorrow)) {
                                $text_color = "blue";
                            } elseif ($formattedDate < $tanggal_sekarang) {
                                $text_color = "red";
                            } else {
                                $text_color = "dark";
                            }
                            ?>

                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                <h6 class="mb-1 font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
                                <span class="text-xs text-uppercase"><?php echo $formattedTime; ?></span>
                            </div>
                            
                            <?php
                            $nomorHandphone = $data['cust_nomor'][0];

                            if (substr($nomorHandphone, 0, 1) === '0') {
                            // Ganti angka 0 dengan 62
                            $nomorHandphone = '62' . substr($nomorHandphone, 1);
                            }

                            ?>

                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                <h6 class="text-dark font-weight-bold text-sm"><?php echo $data['customer'][0]; ?></h6>
                                <span class="text-xs text-uppercase"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $data['cust_nomor'][0]; ?></a></span>
                            </div>

                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center justify-content-md-center justify-content-start align-items-center d-flex">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                    <?php
                                    foreach ($data['teknisi'] as $teknisiName) {
                                        $teknisiQuery = "SELECT t.id_teknisi, t.log_link, t.no_wa, k.status
                                        FROM teknisi t 
                                        INNER JOIN kegiatan k ON t.id_teknisi = k.id_teknisi
                                        WHERE t.nama = '" . mysqli_real_escape_string($conn, $teknisiName) . "'";
                                        $teknisiResult = mysqli_query($conn, $teknisiQuery);

                                        $status = '';

                                        if ($teknisiResult && mysqli_num_rows($teknisiResult) > 0) {
                                            $teknisiRow = mysqli_fetch_assoc($teknisiResult);
                                            $teknisiId = $teknisiRow['id_teknisi'];
                                            $logLink = $teknisiRow['log_link'];
                                            $noWa = $teknisiRow['no_wa'];
                                            $noWa = '62' . substr($noWa, 1);
                                            while ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                                if ($teknisiRow['status'] != 'Clear') {
                                                    $status = $teknisiRow['status'];
                                                    break; // Keluar dari loop saat status ditemukan
                                                }
                                            }
                                            $pesan = urlencode("Jangan lupa selesaikan kegiatanmu $teknisiName. Cek disini $logLink");
                                            $pesan = str_replace('%2F', '/', $pesan); // Replace encoded slashes with normal slashes
                                            $textColor = $status != 'Clear' ? 'black' : 'red';
                                            $link = "teknisi_detail.php?id_teknisi=" . $teknisiId;

                                            echo "<span style='color: $textColor;'><a href='$link'>$teknisiName</a></span><br>";
                                        }
                                    }
                                    ?>
                                </h6>
                            </div>


                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center justify-content-center align-items-center d-flex">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $data['req_by'][0]; ?></h6>
                            </div>

                            <div class="col-6 col-md-2 mb-2 mb-md-0 d-flex justify-content-md-center justify-content-start align-items-center pt-1">
                                <!-- <button class="btn btn-info view-btn w-25 p-2 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">visibility</i></button> -->
                                <a class="btn btn-info view-btn w-25 p-2 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">visibility</i></a>
                                <button class="btn btn-warning edit-btn w-25 p-2 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">autorenew</i></button>
                                <button class="btn btn-danger delete-btn w-25 p-2 text-center" data-kode="<?php echo $kodeTransaksi; ?>" data-nama="<?php echo $nmUser; ?>"><i class="material-icons opacity-10">delete</i></button>
                            </div>


                        </div>
                    </li>