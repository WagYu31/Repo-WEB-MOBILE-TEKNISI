<?php

if (isset($_GET['cariTgl']) && !empty($_GET['cariTgl'])) {
    $current_date = $_GET['cariTgl'];
} else {
    $current_date = date("Y-m-d");
}
?>
<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-top-left-radius: 0;">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-md-flex d-block align-items-center">
                    <div class="col-md-6 col-12">
                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                    </div>
                    <div class="col-md-6 col-12">
                        <form method="GET" action="">
                            <div class="mb-3 d-flex flex-row justify-content-start align-items-center">
                                <input type="text" name="cari" class="form-control border w-60 w-md-80" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius:0; padding:7.5px;" placeholder="Cari Nama Customer" value="<?php echo isset($_GET['cari']) ? $_GET['cari'] : ''; ?>">
                                <button class="btn bg-gradient-primary w-40 w-md-20" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius:0; margin-top:15.5px;" type="submit">Cari</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php

        $search = isset($_GET['cari']) ? $_GET['cari'] : '';

        $tomorrow_date = date("Y-m-d", strtotime("+1 day"));
        $current_time = date("H:i:s");
        
        $jumlah_data = 0;

        $sql = "SELECT k.*, k.kode AS kode_transaksi, t.nama_teknisi, c.id AS id_cust, c.nama AS nama_cust, c.telp AS cust_nomor, i.no_invoice AS invoice, i.kode, i.nominal_invoice
        FROM kegiatan k
        LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
        LEFT JOIN customer c ON k.customer_id = c.id
        LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
        LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
        WHERE k.status != 'waiting' 
        AND (k.paid IS NULL OR k.paid = '')
        AND k.deleted_at IS NULL
        AND p.kode IS NOT NULL";

        if (!empty($search)) {
            $sql .= " AND c.nama LIKE ?";
        }

        $sql .= " GROUP BY k.kode
                  ORDER BY k.jadwal DESC";

        $stmt = $conn->prepare($sql);

        if (!empty($search)) {
            $search_param = "%$search%";
            $stmt->bind_param("s", $search_param);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $jumlah_data = $result->num_rows;
        
        // Display the count
        echo "<div class='ms-4 text-s'>Jumlah data ditemukan: $jumlah_data</div>";

        ?>
        <div class="card-body pb-0 p-0">
            <?php

            $tanggal = date("d", strtotime($current_date));
            $tahun = date("Y", strtotime($current_date));
            $formatted_date = date("d - M - Y", strtotime($current_date));

            $day_in_indonesian = strftime("%A", strtotime($current_date));

            $month_in_indonesian = strftime("%B", strtotime($current_date));

            ?>
            <!--<p class="ms-4 mt-2 text-dark font-weight-bold"><?php echo $day_in_indonesian . ", " . $tanggal . " " . $month_in_indonesian . " " . $tahun; ?></p>-->

            <ul class="list-group m-0 mt-2 col-12" id="data-tek">


                <?php
                setlocale(LC_TIME, 'id_ID.utf8');
                $groupedData = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $idC = $row['id_cust'];
                    $namaC = $row['nama_cust'];
                    $kodeTransaksi = $row['kode_transaksi'];
                    $invoice = $row['invoice'];
                    $status = $row['status'];
                    $idKegiatan = $row['id'];
                    $clearFound = false;

                    // Cek status setiap baris
                    if ($status == 'selesai') {
                        $clearFound = true;
                    }

                    $invFound = true;
                    if ($invoice == NULL) {
                        $invFound = false;
                    }
                ?>
                    <li class="list-group-item ps-0 mb-2 d-md-block d-block pt-4 mt-3">

                        <div class="row d-flex flex-row justify-content-start align-items-center ms-2" style="border-radius:0;">
                            <div class="col-6">
                                <h5 class="font-weight-bold text-lg p-2 mb-0"><a href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>" class="text-info" target="_blank"><?php echo $namaC; ?></a></h5>
                            </div>
                            <div class="col-6 text-end ms-md-n6 ms-n3 d-flex justify-content-end align-items-end">
                                <button class="btn bg-gradient-info text-white detailBtn mb-0" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?php echo $kodeTransaksi; ?>">
                                    <i class="material-icons opacity-10 me-2">settings</i> Invoice</button>
                            </div>

                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                        <div class="row px-4">
                            <div class="col-6 w-md-20 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                                <span class="text-xs">/ Kegiatan</span>
                            </div>

                            <div class="col-6 w-md-20 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                            </div>

                            <div class="col-6 w-md-20 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Request</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-20 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"> Mulai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-20 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                        </div>
                    </li>
                    <?php
                    $sqlLapTek = "
                        SELECT 
                            p.*, 
                            t.*, 
                            p.kode AS kode_pelaksanaan, 
                            k.customer_id,
                            (SELECT MIN(waktu_mulai) 
                             FROM pelaksanaan_kegiatan 
                             WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_mulai_pertama,
                            (SELECT MAX(waktu_selesai) 
                             FROM pelaksanaan_kegiatan 
                             WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_selesai_terakhir
                        FROM pelaksanaan_kegiatan p
                        JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                        JOIN kegiatan k ON t.kegiatan_id = k.id
                        WHERE p.kode = '$kodeTransaksi' AND p.deleted_at IS NULL
                        AND k.customer_id = $idC
                        GROUP BY p.teknisi_id";

                    $resLapTek = mysqli_query($conn, $sqlLapTek);

                    if (mysqli_num_rows($resLapTek) > 0) {
                        while ($rowLT = mysqli_fetch_assoc($resLapTek)) {
                            $idT = $rowLT["teknisi_id"];
                            $kodePelaksanaan = $rowLT["kode_pelaksanaan"];

                            $jadwal = $row["jadwal"];
                            $formattedDate = ($jadwal && $jadwal != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($jadwal)) : '-';
                            $formattedTime = ($jadwal && $jadwal != '0000-00-00 00:00:00') ? date("H:i", strtotime($jadwal)) : '-';

                            $waktuMulai = $rowLT["waktu_mulai_pertama"];
                            $formattedDateMulai = ($waktuMulai && $waktuMulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($waktuMulai)) : '-';
                            $formattedTimeMulai = ($waktuMulai && $waktuMulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($waktuMulai)) : '-';

                            $waktuSelesai = $rowLT["waktu_selesai_terakhir"];
                            $formattedDateSelesai = ($waktuSelesai && $waktuSelesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($waktuSelesai)) : '-';
                            $formattedTimeSelesai = ($waktuSelesai && $waktuSelesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($waktuSelesai)) : '-';

                            $pendapatan = 0;

                    ?>
                            <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                                <div class="row px-4">

                                    <div class="col-12 w-md-20 mb-0 mb-md-0 text-left d-md-none">
                                        <h6 class="text-dark font-weight-bold text-lg"><?php echo $rowLT['nama_teknisi']; ?></h6>
                                    </div>

                                    <div class="col-md-6 col-12 w-md-20 mb-0 mb-md-0 mt-n3 mt-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                            <?php
                                            $status = $rowLT['status'];
                                            include 'include/status.php';
                                            $status = $status_terubah;
                                            ?>
                                        </h6>
                                        <span class="text-xs"><a href="view-kegiatan.php?kode_transaksi=<?php echo $kodePelaksanaan; ?>&id_teknisi=<?php echo $idT; ?>"><?php echo $status; ?></a></span>
                                    </div>

                                    <div class="col-6 w-md-20 mb-2 mb-md-0 text-left d-md-block d-none">
                                        <h6 class="text-dark font-weight-bold text-sm"><?php echo $rowLT['nama_teknisi']; ?></h6>
                                    </div>

                                    <div class="col-6 w-md-20 mb-2 mb-md-0 d-block d-md-none">
                                        <span class="text-xs text-uppercase">Request</span>
                                    </div>

                                    <div class="col-6 w-md-20 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-block d-md-none"><?php echo $formattedDate . " / " . $formattedTime; ?></h6>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-md-block d-none"><?php echo $formattedDate; ?></h6>
                                        <span class="text-xs text-uppercase d-md-block d-none"><?php echo $formattedTime; ?></span>
                                    </div>
                                    
                                    <div class="col-6 w-md-20 mb-2 mb-md-0 d-block d-md-none">
                                        <span class="text-xs text-uppercase">Mulai</span>
                                    </div>
                                    
                                    <div class="col-6 w-md-20 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-block d-md-none"><?php echo $formattedDateMulai . " / " . $formattedTimeMulai; ?></h6>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-md-block d-none"><?php echo $formattedDateMulai; ?></h6>
                                        <span class="text-xs text-uppercase d-md-block d-none"><?php echo $formattedTimeMulai; ?></span>
                                    </div>

                                    <div class="col-6 w-md-20 mb-2 mb-md-0 d-block d-md-none">
                                        <span class="text-xs text-uppercase">Selesai</span>
                                    </div>
                                    
                                    <div class="col-6 w-md-20 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-block d-md-none"><?php echo $formattedDateSelesai . " / " . $formattedTimeSelesai; ?></h6>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm d-md-block d-none"><?php echo $formattedDateSelesai; ?></h6>
                                        <span class="text-xs text-uppercas d-md-block d-nonee"><?php echo $formattedTimeSelesai; ?></span>
                                    </div>

                                </div>
                            </li>
                <?php
                        }
                        echo "<span class='text-xs text-end'>KODE : $kodeTransaksi</span>";
                    } else {
                        echo "<li class='list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-4 mb-2 border-radius-lg d-md-block d-block'>Tidak ada kegiatan.</li>";
                    }
                }

                ?>
        </div>
        
    </div>
    </li>

    </ul>
</div>
</div>
</div>