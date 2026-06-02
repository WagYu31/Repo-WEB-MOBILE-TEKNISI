<div class="col-lg-12">

    <?php
    $current_date = date("Y-m-d"); // Tanggal hari ini

    $sql = "SELECT v.*, s.nama AS nama_sales, c.nama AS nama_customer
        FROM visits v
        LEFT JOIN sales s ON v.id_sales = s.id_sales
        LEFT JOIN cust c ON v.id_cust = c.id_cust
        WHERE FIND_IN_SET('$idt', v.id_sales) > 0
    ORDER BY v.tgl_visits DESC";


    $result = mysqli_query($conn, $sql);
    ?>

    <div class="card h-100 py-3 pt-0 col-12">
        <div class="card-header pb-0 p-4">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase head">Riwayat Pekerjaan</h6>
                </div>
            </div>
        </div>
        <div class="card-body p-0 pe-3 ps-2">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="list-group m-0 mt-4 col-12 d-flex" id="data-tek">
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            $events = array();
                            $today = date("Y-m-d");
                            $tomorrow = date("Y-m-d", strtotime("+1 day"));
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tglReq = $row["tgl_visits"];
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
                                $id_keg = $row['id_visits'];

                                if ($status == 'dijadwalkan') {
                                    $statusClass = 'pending';
                                    $sts = 'Dijadwalkan';
                                } elseif ($status == 'on process') {
                                    $statusClass = 'on-process';
                                    $sts = 'Di Proses';
                                } elseif ($status == 'clear') {
                                    $statusClass = 'clear';
                                    $sts = 'Selesai';
                                }

                                // Mendapatkan tanggal dan jam dalam format "dd-mm-yyyy H:i" dari "yyyy-mm-dd H:i:s"
                                $datetime = $tglReq;
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

                                $tgl_short = date("Y-m-d", strtotime($tglReq));
                                $saatIni = date("Y-m-d H:i:s");

                                if ($status == 'clear') {
                                    $label = '<b>Selesai</b>';
                                    $kelas = 'bg-gradient-success';
                                } elseif ($status == 'on process') {
                                    $label = '<b>Dikerjakan</b>';
                                    $kelas = 'bg-gradient-info';
                                    if (strtotime($tgl_request) < strtotime($saatIni)) {
                                        $label = '<b>Tidak Diselesaikan</b>';
                                        $kelas = 'bg-gradient-dark';
                                    }
                                } elseif ($status !== 'clear') {
                                    if (strtotime($tgl_request) < strtotime($saatIni)) {
                                        $label = '<b>Terlambat</b>';
                                        $kelas = 'bg-gradient-dark';
                                    } elseif ($tgl_short == $today) {
                                        $label = '<b>Hari ini</b>';
                                        $kelas = 'bg-gradient-info';
                                    } elseif ($tgl_short == $tomorrow) {
                                        $label = '<b>Besok</b>';
                                        $kelas = 'bg-gradient-warning';
                                    } elseif ($tgl_short > $tomorrow) {
                                        $label = '<b>Akan Datang</b>';
                                        $kelas = 'bg-gradient-primary';
                                    } else {
                                        $label = '<b>Hari ini</b>';
                                        $kelas = 'bg-gradient-info';
                                    }
                                } else {
                                    $label = '<b>-</b>';
                                    $kelas = 'bg-gradient-dark';
                                }



                                // Tambahkan data ke dalam array
                                $events[] = array(
                                    'label' => $label,
                                    'kelas' => $kelas,
                                    'id_visits' => $row['id_visits'],
                                    'nama_customer' => $row['nama_customer'],
                                    'namaHariIndonesia' => $namaHariIndonesia,
                                    'formattedDate' => $formattedDate,
                                    'formattedTime' => $formattedTime,
                                    'tgl_request' => $tgl_request,
                                    'today' => $today,
                                    'tgl_short' => $tgl_short
                                );
                            }

                            function compareDates($a, $b)
                            {
                                return strtotime($b['tgl_request']) - strtotime($a['tgl_request']);
                            }

                            // Mengurutkan array $events berdasarkan tgl_short
                            usort($events, 'compareDates');

                            // Menampilkan data yang telah diurutkan
                            foreach ($events as $event) {
                        ?>
                                    <li class="list-group-item border-0 d-flex flex-column <?php echo $event['kelas']; ?> mb-3 mx-4" style="border-radius:10px;">
                                        <div class="text-white"><?php echo $event['label']; ?></div>
                                        <a href="detail-kegiatan.php?id_visits=<?php echo $event['id_visits']; ?>" class="list-group-item custom-list-item border-0 bg-transparent">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1 text-white"><?php echo $event['nama_customer']; ?></h6>
                                            </div>
                                            <p class="mb-1 text-white text-sm"><?php echo $event['namaHariIndonesia']; ?> , <?php echo $event['formattedDate']; ?> <span class="line">|</span> <?php echo $event['formattedTime']; ?></p>
                                            
                                        </a>
                                    </li>
                        <?php
                            }
                        } else {
                            echo '<p class="text-muted ms-4">Tidak ada data kegiatan.</p>';
                        }
                        ?>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>