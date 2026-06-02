<div class="col-lg-12">

    <?php
    $current_date = date("Y-m-d"); // Tanggal hari ini

    $sql = "SELECT k.id_teknisi, k.id_kegiatan, k.*, t.nama AS nama_teknisi, c.nama AS nama_customer
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        LEFT JOIN customer c ON k.id_cust = c.id_cust
        WHERE FIND_IN_SET('$idt', k.id_teknisi) > 0
        AND k.id_kegiatan IN (
            SELECT MAX(id_kegiatan)
            FROM kegiatan
            WHERE k.status != 'N'
            GROUP BY kode_transaksi, id_teknisi
        )
        ORDER BY k.kode_transaksi, k.id_teknisi DESC";

        $result = mysqli_query($conn, $sql);


    ?>

    <div class="card h-100 py-3 pt-0 col-12">
        <div class="card-header pb-0 p-4">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase head">Seluruh Kegiatan Anda</h6>
                </div>
            </div>
        </div>
        <div class="card-body p-0 pe-3 ps-2">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="list-group m-0 mt-4 col-12 d-flex" id="data-tek">
                        <?php
                        $today = date("Y-m-d");
                        $tomorrow = date("Y-m-d", strtotime("+1 day"));
                        if (mysqli_num_rows($result) > 0) {
                            $events = array();
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tglReq = $row["tgl_request"];
                                $tglMul = $row["tgl_mulai"];
                                $tglSel = $row["tgl_selesai"];

                                if ($tglSel !== NULL && $tglSel !== '0000-00-00 00:00:00') {
                                    $tgl_request = $tglSel;
                                } else {
                                    if ($tglMul !== NULL && $tglMul !== '0000-00-00 00:00:00') {
                                        $tgl_request = $tglMul;
                                    } else {
                                        $tgl_request = $tglReq;
                                    }
                                }

                                $status = $row['status'];
                                $statusClass = '';
                                $id_keg = $row['id_kegiatan'];

                                if ($status == 'Pending') {
                                    $statusClass = 'pending';
                                    $sts = 'Dijadwalkan';
                                } elseif ($status == 'Reschedule' || $status == 'Reschedule2') {
                                    $statusClass = 'reschedule';
                                    $sts = 'Reschedule';
                                } elseif ($status == 'Pause') {
                                    $statusClass = 'pause';
                                    $sts = 'Lanjut Nanti';
                                } elseif ($status == 'On Process') {
                                    $statusClass = 'on-process';
                                    $sts = 'Di Proses';
                                } elseif ($status == 'Clear') {
                                    $statusClass = 'clear';
                                    $sts = 'Selesai';
                                }


                                // Mendapatkan tanggal dan jam dalam format "dd-mm-yyyy H:i" dari "yyyy-mm-dd H:i:s"
                                $datetime = $tgl_request;
                                $formattedTime = date("H:i", strtotime($datetime));
                                $formattedDate = date("d F Y", strtotime($datetime));

                                $namaHari = date("l", strtotime($datetime));
                                $namaHariIndonesia = "";
                                switch ($namaHari) {
                                    case "Monday":
                                        $namaHariIndonesia = "Senin";
                                        break;
                                    case "Tuesday":
                                        $namaHariIndonesia = "Selasa";
                                        break;
                                    case "Wednesday":
                                        $namaHariIndonesia = "Rabu";
                                        break;
                                    case "Thursday":
                                        $namaHariIndonesia = "Kamis";
                                        break;
                                    case "Friday":
                                        $namaHariIndonesia = "Jumat";
                                        break;
                                    case "Saturday":
                                        $namaHariIndonesia = "Sabtu";
                                        break;
                                    case "Sunday":
                                        $namaHariIndonesia = "Minggu";
                                        break;
                                }

                                if ($tgl_request == $today && $status !== 'Clear') {
                                    $label = '<b>Hari ini</b>';
                                    $kelas = 'bg-gradient-info';
                                } elseif ($tgl_request == $tomorrow && $status !== 'Clear') {
                                    $label = '<b>Besok</b>';
                                    $kelas = 'bg-gradient-primary';
                                } elseif ($tgl_request < $today && $status !== 'Clear') {
                                    $label = '<b>Terlewatkan</b>';
                                    $kelas = 'bg-gradient-dark';
                                } elseif ($tgl_request > $today && $status !== 'Clear') {
                                    $label = '<b>Akan Datang</b>';
                                    $kelas = 'bg-gradient-primary';
                                } elseif ($status == 'Clear') {
                                    $label = '<b>Selesai</b>';
                                    $kelas = 'bg-gradient-success';
                                } else {
                                    $label = '<b>-</b>';
                                    $kelas = 'bg-gradient-dark';
                                }


                                // Tambahkan data ke dalam array
                                $events[] = array(
                                    'label' => $label,
                                    'kelas' => $kelas,
                                    'id_kegiatan' => $row['id_kegiatan'],
                                    'nama_customer' => $row['nama_customer'],
                                    'namaHariIndonesia' => $namaHariIndonesia,
                                    'formattedDate' => $formattedDate,
                                    'formattedTime' => $formattedTime,
                                    'jenis' => $row['jenis']
                                );
                            }

                            // Fungsi untuk dibandingkan dan diurutkan berdasarkan label dan tanggal
                            function compareEvents($a, $b)
                            {
                                if ($a['label'] != $b['label']) {
                                    return ($a['label'] == 'Hari ini') ? -1 : 1;
                                } else {
                                    return strcmp($a['formattedDate'], $b['formattedDate']);
                                }
                            }

                            // Urutkan array menggunakan fungsi compareEvents
                            usort($events, 'compareEvents');

                            // Tampilkan hasil
                            foreach ($events as $event) {
                        ?>

                                <li class="list-group-item border-0 d-flex flex-column <?php echo $event['kelas']; ?> mb-3 mx-4" style="border-radius:10px;">
                                    <div class="text-white"><?php echo $event['label']; ?></div>
                                    <a href="detail-kegiatan.php?id_kegiatan=<?php echo $event['id_kegiatan']; ?>" class="list-group-item custom-list-item border-0 bg-transparent">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 text-white"><?php echo $event['nama_customer']; ?></h6>
                                        </div>
                                        <p class="mb-1 text-white text-sm"><?php echo $event['namaHariIndonesia']; ?> , <?php echo $event['formattedDate']; ?> <span class="line">|</span> <?php echo $event['formattedTime']; ?></p>
                                        <p class="day text-white text-sm">Kegiatan : <?php echo $event['jenis']; ?></p>
                                    </a>
                                </li>
                        <?php
                            }
                        } else {
                            echo '<p class="text-muted">Tidak ada data kegiatan.</p>';
                        }
                        ?>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>