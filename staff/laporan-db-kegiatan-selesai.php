<?php

if (isset($_GET['cariTgl']) && !empty($_GET['cariTgl'])) {
    $current_date = $_GET['cariTgl'];
} else {
    $current_date = date("Y-m-d"); // Today's date
}
?>
<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-top-left-radius: 0;">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex flex-column flex-md-row align-items-start">
                    <div class="col-12 col-md-6">
                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                    </div>
                    <div class="col-12 col-md-6">
                        <form method="GET" action="">
                            <div class="mb-3 d-flex flex-row justify-content-start align-items-center">
                                <input type="text" name="cari" class="form-control border w-100 w-md-80" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius:0; padding:7.5px;" placeholder="Cari Nama Customer" value="<?php echo isset($_GET['cari']) ? $_GET['cari'] : ''; ?>">
                                <button class="btn bg-gradient-primary w-40 w-md-20" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius:0; margin-top:15.5px;" type="submit">Cari</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-row">
                </div>
            </div>
        </div>

        <?php

        $search = isset($_GET['cari']) ? $_GET['cari'] : '';

        $tomorrow_date = date("Y-m-d", strtotime("+1 day"));
        $current_time = date("H:i:s");

        // Base SQL query
        $sql = "SELECT k.*, k.kode AS kode_transaksi, t.nama_teknisi, c.id AS id_cust, c.nama AS nama_cust, c.telp AS cust_nomor, i.no_invoice AS invoice, i.kode, i.nominal_invoice
            FROM kegiatan k
            LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
            LEFT JOIN customer c ON k.customer_id = c.id
            LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
            WHERE k.status != 'waiting' AND paid = 'yes'
            AND k.deleted_at IS NULL";

        // Add search condition if search term is provided
        if (!empty($search)) {
            $sql .= " AND c.nama LIKE ?";
        }

        // Group by and order by conditions
        $sql .= " GROUP BY k.kode
                  ORDER BY k.jadwal DESC";

        $stmt = $conn->prepare($sql);

        if (!empty($search)) {
            $search_param = "%$search%";
            $stmt->bind_param("s", $search_param);
        }

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();


        // $result = mysqli_query($conn, $sql);

        ?>
        <div class="card-body pb-0 p-0">
            <?php

            $tanggal = date("d", strtotime($current_date));
            $tahun = date("Y", strtotime($current_date));
            // Konversi format tanggal dari Y-m-d menjadi d - M - Y
            $formatted_date = date("d - M - Y", strtotime($current_date));

            // Mendapatkan nama hari dalam bahasa Indonesia
            $day_in_indonesian = strftime("%A", strtotime($current_date));

            // Mendapatkan nama bulan dalam bahasa Indonesia
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
                    // Menginisialisasi variabel tambahan untuk menentukan apakah ada setidaknya satu status 'Clear'
                    $clearFound = false;

                    // Cek status setiap baris
                    if ($status == 'selesai') {
                        // Jika status 'Clear' ditemukan, set $clearFound menjadi true
                        $clearFound = true;
                    }

                    $invFound = true;
                    if ($invoice == NULL) {
                        $invFound = false;
                    }
                ?>
                    <li class="list-group-item ps-0 mb-2 d-md-block d-block pt-4 mt-3" style="background:none;">

                        <div class="row d-flex flex-row justify-content-start align-items-center ms-2" style="border-radius:0;">
                            <div class="col-6">
                                <h5 class="text-info font-weight-bold text-lg p-2 mb-0"><?php echo $namaC; ?></h5>
                            </div>
                            <div class="col-6 text-end ms-md-n6 ms-n3 d-flex justify-content-end align-items-end">
                                <button class="btn bg-gradient-info text-white detailBtn mb-0" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?php echo $kodeTransaksi; ?>">
                                    <i class="material-icons opacity-10 me-2">settings</i> Invoice</button>
                                <a class="btn bg-gradient-danger text-white resetBtn mb-0 ms-2" href="reset_invoice.php?kode=<?php echo $kodeTransaksi; ?>">
                                    ⭮ Reset</a>
                            </div>

                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none" style="background:none;">
                        <div class="row px-4">
                            <div class="col-6 w-md-10 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                                <span class="text-xs">/ Kegiatan</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Request</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"> Mulai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <!--<div class="col-6 w-md-15 mb-2 mb-md-0">-->
                            <!--    <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>-->
                            <!--    <span class="text-xs">Tanggal / Jam</span>-->
                            <!--</div>-->

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Invoice</h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Lunas</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Pendapatan</h6>
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
                    WHERE p.kode = '$kodeTransaksi' 
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

                            // Menggunakan waktu_mulai_pertama dan waktu_selesai_terakhir dari subquery
                            $waktuMulai = $rowLT["waktu_mulai_pertama"];
                            $formattedDateMulai = ($waktuMulai && $waktuMulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($waktuMulai)) : '-';
                            $formattedTimeMulai = ($waktuMulai && $waktuMulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($waktuMulai)) : '-';

                            $waktuSelesai = $rowLT["waktu_selesai_terakhir"];
                            $formattedDateSelesai = ($waktuSelesai && $waktuSelesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($waktuSelesai)) : '-';
                            $formattedTimeSelesai = ($waktuSelesai && $waktuSelesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($waktuSelesai)) : '-';

                            $waktuLunas = $rowLT["lunas"];
                            $formattedDateLunas = ($waktuLunas && $waktuLunas != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($waktuLunas)) : '-';
                            // $formattedTimeLunas = ($waktuLunas && $waktuLunas != '0000-00-00 00:00:00') ? date("H:i", strtotime($waktuLunas)) : '-';
                            
                            $getPendapatan = "
                                SELECT SUM(pendapatan) AS total_pendapatan, no_invoice
                                FROM pendapatan_kegiatan 
                                WHERE kode = '$kodePelaksanaan'
                                AND teknisi_id = $idT
                                AND deleted_at IS NULL
                                ";
                            $resGetPendapatan = mysqli_query($conn, $getPendapatan);
                            $rowPendapatan = mysqli_fetch_assoc($resGetPendapatan);
                            $pendapatan = $rowPendapatan['total_pendapatan'] ?? 0;
                            $invoice = $rowPendapatan['no_invoice'] ?? '';
                            $pendapatanRupiah = "Rp " . number_format($pendapatan, 0, ',', '.');

                    ?>
                            <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block" style="background:none;">
                                <div class="row px-4">

                                    <div class="col-6 w-md-10 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                            <?php
                                            $status = $rowLT['status'];
                                            include 'include/status.php';
                                            $status = $status_terubah;
                                            ?>
                                        </h6>
                                        <span class="text-xs"><a href="view-kegiatan.php?kode_transaksi=<?php echo $kodePelaksanaan; ?>&id_teknisi=<?php echo $idT; ?>"><?php echo $status; ?></a></span>
                                    </div>

                                    <div class="col-6 w-md-15 mb-2 mb-md-0 text-left d-md-block d-none">
                                        <h6 class="text-dark font-weight-bold text-sm"><?php echo $rowLT['nama_teknisi']; ?></h6>
                                    </div>

                                    <div class="col-6 w-md-15 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
                                        <span class="text-xs text-uppercase"><?php echo $formattedTime; ?></span>
                                    </div>
                                    <div class="col-6 w-md-15 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateMulai; ?></h6>
                                        <span class="text-xs text-uppercase"><?php echo $formattedTimeMulai; ?></span>
                                    </div>

                                    <!--<div class="col-6 w-md-15 mb-2 mb-md-0">-->
                                    <!--    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateSelesai; ?></h6>-->
                                    <!--    <span class="text-xs text-uppercase"><?php echo $formattedTimeSelesai; ?></span>-->
                                    <!--</div>-->
                                    
                                    <div class="col-6 w-md-15 mb-2 mb-md-0 d-flex justify-content-start text-start">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm" style="text-transform:uppercase;"><?php echo $invoice; ?></h6>
                                    </div>
                                    

                                    <div class="col-6 w-md-15 mb-2 mb-md-0">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateLunas; ?></h6>
                                    </div>

                                    <div class="col-6 w-md-15 mb-2 mb-md-0 d-flex justify-content-start text-start">
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $pendapatanRupiah; ?></h6>
                                    </div>

                                </div>
                            </li>
                <?php
                        }
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