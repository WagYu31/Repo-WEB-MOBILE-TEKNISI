                
                <h2>Data Kegiatan Hari Ini</h2>
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <!--<th>Kode Transaksi</th>-->
                                <th width="8%">Status</th>
                                <th width="10%">Tanggal</th>
                                <th width="5%">Jam</th>
                                <th width="10%">Customer</th>
                                <th width="27%">Teknisi</th>
                                <th width="10%">Kegiatan</th>
                                <!--<th>Keterangan</th>-->
                                <th width="15%">Request By</th>
                                <th class="action-column" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $groupedData = []; // Membuat array untuk mengelompokkan data berdasarkan kode_transaksi
                                
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $kodeTransaksi = $row['kode_transaksi'];
                                
                                        // Jika kode_transaksi belum ada dalam groupedData, buat array baru untuknya
                                        if (!isset($groupedData[$kodeTransaksi])) {
                                            $groupedData[$kodeTransaksi] = [
                                                'tgl_request' => [],
                                                'tgl_reschedule' => [],
                                                'tgl_mulai' => [],
                                                // 'jam' => [],
                                                'customer' => [],
                                                'teknisi' => [],
                                                'jenis' => [],
                                                'keterangan' => [],
                                                'req_by' => [],
                                                'status' => [],
                                                'id_kegiatan' => []
                                            ];
                                        }
                                
                                        // Masukkan data ke dalam grup sesuai kode_transaksi
                                        $groupedData[$kodeTransaksi]['tgl_request'][] = $row['tgl_request'];
                                        $groupedData[$kodeTransaksi]['tgl_reschedule'][] = $row['tgl_reschedule'];
                                        $groupedData[$kodeTransaksi]['tgl_mulai'][] = $row['tgl_mulai'];
                                        // $groupedData[$kodeTransaksi]['jam'][] = $row['jam'];
                                        $groupedData[$kodeTransaksi]['customer'][] = $row['nama_customer'];
                                        $teknisiIds = explode(',', $row['id_teknisi']);
                                        $teknisiNames = [];
                                        foreach ($teknisiIds as $teknisiId) {
                                            // Lakukan query untuk mengambil nama teknisi berdasarkan ID
                                            $teknisiQuery = "SELECT nama FROM teknisi WHERE id = " . intval($teknisiId);
                                            $teknisiResult = mysqli_query($conn, $teknisiQuery);
                                            if ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                                $teknisiNames[] = $teknisiRow['nama'];
                                            }
                                        }
                                        $groupedData[$kodeTransaksi]['teknisi'][] = implode("<br>", $teknisiNames);
                                        $groupedData[$kodeTransaksi]['jenis'][] = $row['jenis'];
                                        $groupedData[$kodeTransaksi]['keterangan'][] = $row['keterangan'];
                                        $groupedData[$kodeTransaksi]['req_by'][] = $row['req_by'];
                                        $groupedData[$kodeTransaksi]['status'][] = $row['status'];
                                        $groupedData[$kodeTransaksi]['id_kegiatan'][] = $row['id_kegiatan'];
                                    }
                                }
                                
                                $no = 1; // Nomor urutan baris
                                
                                foreach ($groupedData as $kodeTransaksi => $data) {
                                    echo "<tr>";
                                    echo "<td style='text-align:center;'>" . $no . "</td>";
                                    // echo "<td>" . $kodeTransaksi . "</td>";
                                    
                                    // Update status based on your specified conditions
                                    $updatedStatus = '';
                                    switch ($data['status'][0]) {
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
                                        case 'Clear':
                                            $updatedStatus = 'Selesai';
                                            break;
                                        case 'Reschedule':
                                            $updatedStatus = 'Dijadwalkan Ulang';
                                            break;
                                        case 'Reschedule2':
                                            $updatedStatus = 'Dijadwalkan Ulang';
                                            break;
                                        default:
                                            $updatedStatus = $data['status'][0]; // Keep the current status if it doesn't match any of the specified conditions
                                    }
                                
                                    echo "<td>" . $updatedStatus . "</td>";
                                    
                                        // Assuming $data["tgl_mulai"] is an array of datetime values

                                    $smallestIndex = 0; // Initialize with the first index
                                    $smallestDateTime = strtotime($data["tgl_mulai"][0]);
                                    
                                    foreach ($data["tgl_mulai"] as $index => $datetime) {
                                        $currentDateTime = strtotime($datetime);
                                    
                                        // Compare the current datetime with the smallest datetime
                                        if ($currentDateTime < $smallestDateTime) {
                                            $smallestDateTime = $currentDateTime;
                                            $smallestIndex = $index;
                                        }
                                    }
                                    
                                    // Now, $smallestIndex contains the index with the smallest combined date and time
                                    $selectedDatetime = $data["tgl_mulai"][$smallestIndex];
                                    
                                    // Ambil nilai tgl_reschedule dari array $groupedData untuk kode transaksi tertentu
                                        $rescheduleDates = $groupedData[$kodeTransaksi]['tgl_reschedule'];
                                        
                                        // Cari nilai tgl_reschedule yang bukan null
                                        $rescheduleNotNull = null;
                                        foreach ($rescheduleDates as $date) {
                                            if ($date !== null) {
                                                $rescheduleNotNull = $date;
                                                break;
                                            }
                                        }
                                        
                                        // Jika ada nilai tgl_reschedule yang bukan null, gunakan nilainya, jika tidak, gunakan tgl_request
                                        if ($rescheduleNotNull !== null) {
                                            $datetime = $rescheduleNotNull;
                                        } else {
                                            $datetime = $data["tgl_request"][0];
                                        }
                                        
                                        // Format dan tampilkan tanggal dan waktu
                                        $formattedTime = date("H:i", strtotime($datetime));
                                        $formattedDate = date("d-m-Y", strtotime($datetime));
                                        $tanggal_sekarang = date("d-m-Y");
                                        if($formattedDate > $tanggal_sekarang){
                                            echo "<td style='text-align:center;color:blue; font-weight:bold;'>" . $formattedDate . "</td>";
                                            echo "<td style='text-align:center;color:blue; font-weight:bold;'>" . $formattedTime . "</td>";
                                        }
                                        else{
                                        echo "<td style='text-align:center;'>" . $formattedDate . "</td>";
                                        echo "<td style='text-align:center;'>" . $formattedTime . "</td>";
                                        }

                                    echo "<td>" . $data['customer'][0] . "</td>";
                                                                        echo "<td>";
                                    foreach ($data['teknisi'] as $teknisiName) {
                                        // Query to select id_teknisi based on nama
                                        $teknisiQuery = "SELECT id_teknisi, log_link, no_wa FROM teknisi WHERE nama = '" . mysqli_real_escape_string($conn, $teknisiName) . "'";
                                        $teknisiResult = mysqli_query($conn, $teknisiQuery);
                                    
                                        if ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                            $teknisiId = $teknisiRow['id_teknisi'];
                                            $logLink = $teknisiRow['log_link'];
                                            $noWa = $teknisiRow['no_wa'];
                                            $noWa = '62' . substr($noWa, 1);
                                            $pesan = urlencode("Jangan lupa selesaikan kegiatanmu $teknisiName. Cek disini $logLink");
                                            $pesan = str_replace('%2F', '/', $pesan); // Replace encoded slashes with normal slashes

                                            $link = "teknisi_detail.php?id_teknisi=" . $teknisiId;
                                    
                                            // Output link with teknisiName
                                            echo "<a class='btn btn-warn view-btn' href='https://wa.me/$noWa?text=$pesan'><img src='img/notify.png'></a>";
                                            echo "<a href='$link'>$teknisiName</a><br>";
                                    
                                        } else {
                                            // Handle the case where id_teknisi is not found for a given nama
                                            echo $teknisiName . "<br>";
                                        }
                                    }
                                    echo "</td>";
                                    echo "<td>" . $data['jenis'][0] . "</td>";
                                    // echo "<td>" . $data['keterangan'][0] . "</td>";
                                    echo "<td>" . $data['req_by'][0] . "</td>";
                                        echo "<td style='text-align:center;' class='duaratus'>";
                                        ?>
                                        <button class="btn btn-info view-btn" data-id="<?php echo $data['id_kegiatan'][0]; ?>"><i class="far fa-eye"></i></button>
                                        <!--<button class="btn btn-warning edit-btn" data-id="<?php echo $data['id_kegiatan'][0]; ?>"><i class="far fa-edit"></i></button>-->
                                        <button class="btn btn-danger delete-btn" data-id="<?php echo $kodeTransaksi; ?>"><i class="far fa-trash-alt"></i></button>
                                        <?php
                                        echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                                ?>

                        </tbody>
                    </table>