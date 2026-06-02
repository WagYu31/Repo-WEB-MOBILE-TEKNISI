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
                <div class="col-12 d-flex align-items-center">
                    <div class="col-6">
                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                    </div>
                    <div class="col-6">
                        <form method="GET" action="">
                            <div class="mb-3 d-flex flex-row justify-content-start align-items-center">
                                <input type="text" name="cari" class="form-control border w-60 w-md-80" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius:0; padding:7.5px;" placeholder="Cari Nama Customer" value="<?php echo isset($_GET['cari']) ? $_GET['cari'] : ''; ?>">
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
        $sql = "SELECT k.*, t.nama_teknisi, c.id AS id_cust, c.nama AS nama_cust, c.telp AS cust_nomor, i.no_invoice AS invoice, i.kode, i.nominal_invoice
            FROM kegiatan k
            LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
            LEFT JOIN customer c ON k.customer_id = c.id
            LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
            WHERE k.status != 'waiting'
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
                    $kodeTransaksi = $row['kode'];
                    $invoice = $row['invoice'];
                    $status = $row['status'];
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
                    <li class="list-group-item ps-0 mb-2 d-md-block d-block pt-4 mt-3">

                        <div class="row d-flex flex-row justify-content-start align-items-center ms-2" style="border-radius:0;">
                            <div class="col-6">
                                <h5 class="text-info font-weight-bold text-lg p-2 mb-0"><?php echo $namaC; ?></h5>
                            </div>
                            <div class="col-6 text-end ms-md-n6 ms-n3 d-flex justify-content-end align-items-end">
                                <button class="btn bg-gradient-info text-white detailBtn mb-0" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?php echo $kodeTransaksi; ?>">
                                    <i class="material-icons opacity-10 me-2">settings</i> Invoice</button>
                            </div>

                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                                <?php
                                $sqlLapTek = "SELECT pelaksanaan_kegiatan.*, kegiatan.customer_id, teknisi.nama, teknisi.id AS id_teknisi
                                FROM pelaksanaan_kegiatan
                                JOIN teknisi ON teknisi.id = pelaksanaan_kegiatan.teknisi_id
                                JOIN kegiatan ON kegiatan.id = pelaksanaan_kegiatan.kegiatan_id
                                WHERE kegiatan.customer_id = $idC AND pelaksanaan_kegiatan.kode = '$kodeTransaksi'";
                                // AND (DATE(tgl_request) = '$current_date' OR DATE(tgl_mulai) = '$current_date' OR DATE(tgl_selesai) = '$current_date')";
                                $resLapTek = mysqli_query($conn, $sqlLapTek);
                                if (mysqli_num_rows($resLapTek) > 0) {

                                ?>
                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
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

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-10 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Pendapatan</h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 text-start text-md-center">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm text-start text-md-center">Denda</h6>
                            </div>

                        </div>
                    </li>
                    <?php

                                    while ($rowLT = mysqli_fetch_assoc($resLapTek)) {

                                        $idT = $rowLT["id_teknisi"];
                                        $ketFinish = $rowLT['ket_finish'];
                                        $denda = $rowLT["denda"];
                                        $sqlMR = "SELECT *
                                                FROM kegiatan 
                                                WHERE id_teknisi = '$idT' AND kode_transaksi = '$kodeTransaksi' ORDER BY id_kegiatan ASC LIMIT 1";
                                        $resMR = mysqli_query($conn, $sqlMR);
                                        $rowMR = mysqli_fetch_assoc($resMR);
                                        $ket_finish = $rowMR["ket_finish"];

                                        $tanggal_sekarang = date("d-m-Y");
                                        $datetime = $rowMR["jadwal"];
                                        $formattedDate = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($datetime)) : '-';
                                        $formattedTime = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("H:i", strtotime($datetime)) : '-';

                                        $tglMulai = $rowMR["waktu_mulai"];
                                        $formattedDateMulai = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglMulai)) : '-';
                                        $formattedTimeMulai = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglMulai)) : '-';

                                        $tglSelesai = $rowLT["waktu_selesai"];

                                        $sqlS = "SELECT tgl_selesai
                                                 FROM kegiatan 
                                                 WHERE id_teknisi = '$idT' AND kode_transaksi = '$kodeTransaksi' 
                                                 ORDER BY id_kegiatan DESC 
                                                 LIMIT 1";
                                        $resS = mysqli_query($conn, $sqlS);

                                        // Cek apakah ada hasil query
                                        if ($resS) {
                                            // Ambil data dari hasil query
                                            $rowS = mysqli_fetch_assoc($resS);
                                            $tglSelesaiTerakhir = $rowS['tgl_selesai'];

                                            // Jika tgl_selesai terakhir NULL atau '0000-00-00 00:00:00', ambil data sebelumnya
                                            if ($tglSelesaiTerakhir == NULL || $tglSelesaiTerakhir == '0000-00-00 00:00:00') {
                                                $sqlSBefore = "SELECT tgl_selesai
                                                               FROM kegiatan 
                                                               WHERE id_teknisi = '$idT' AND kode_transaksi = '$kodeTransaksi' AND status = 'Clear'
                                                               ORDER BY id_kegiatan DESC 
                                                               LIMIT 1, 1";
                                                $resSBefore = mysqli_query($conn, $sqlSBefore);

                                                // Cek apakah ada hasil query
                                                if ($resSBefore) {
                                                    // Ambil data tgl_selesai sebelumnya
                                                    $rowSBefore = mysqli_fetch_assoc($resSBefore);
                                                    $tglSelesaiTerakhir = $rowSBefore['tgl_selesai'];
                                                } else {
                                                    // Handle jika query sebelumnya tidak mengembalikan hasil
                                                    echo "Error: " . mysqli_error($conn);
                                                }
                                            }
                                        } else {
                                            // Handle jika query tidak mengembalikan hasil
                                            echo "Error: " . mysqli_error($conn);
                                        }


                                        $formattedDateSls = ($tglSelesaiTerakhir && $tglSelesaiTerakhir != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglSelesaiTerakhir)) : '-';
                                        $formattedTimeSls = ($tglSelesaiTerakhir && $tglSelesaiTerakhir != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglSelesaiTerakhir)) : '-';

                    ?>
                        <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                            <div class="row px-4">

                                <div class="col-12 w-md-15 mb-2 mb-md-0 text-left d-md-none">
                                    <h6 class="text-dark font-weight-bold text-lg"><?php echo $rowLT['nama_teknisi']; ?></h6>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Status /</span><br>
                                    <span class="text-xs">Kode Transaksi</span>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                        <?php
                                        // Mengubah nilai status menjadi teks yang diinginkan
                                        $status = $rowLT['status'];
                                        switch ($status) {
                                            case 'N':
                                            case 'Pause':
                                                echo 'Lanjut Nanti';
                                                break;
                                            case 'Pending':
                                                echo 'Dijadwalkan';
                                                break;
                                            case 'On Process':
                                                echo 'Diproses';
                                                break;
                                            case 'Clear':
                                                echo 'Selesai';
                                                break;
                                            default:
                                                echo $status; // Jika status tidak sesuai dengan kondisi di atas, biarkan nilainya tetap
                                        }
                                        ?>
                                    </h6>
                                    <span class="text-xs"><a href="view-kegiatan.php?kode_transaksi=<?php echo $rowLT['kode_transaksi']; ?>&id_teknisi=<?php echo $idT; ?>"><?php echo $rowLT['kode_transaksi']; ?></a></span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0 text-left d-md-block d-none">
                                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $rowLT['nama_teknisi']; ?></h6>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Tanggal Request /</span><br>
                                    <span class="text-xs">Jam</span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
                                    <span class="text-xs text-uppercase"><?php echo $formattedTime; ?></span>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Tanggal Mulai /</span><br>
                                    <span class="text-xs">Jam</span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateMulai; ?></h6>
                                    <span class="text-xs text-uppercase"><?php echo $formattedTimeMulai; ?></span>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Tanggal Selesai /</span><br>
                                    <span class="text-xs">Jam</span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <?php
                                        if ($ketFinish == "Diselesaikan oleh Admin") {
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $ketFinish; ?></h6>
                                    <?php
                                        } else {
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateSls; ?></h6>
                                        <span class="text-xs"><?php echo $formattedTimeSls; ?></span>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <?php
                                        // Menghitung selisih waktu antara tgl_request dan tgl_mulai jika tgl_mulai tidak NULL dan bukan '0000-00-00 00:00:00'
                                        if ($rowLT["tgl_mulai"] && $rowLT["tgl_mulai"] != '0000-00-00 00:00:00') {
                                            if ($rowLT["tgl_request"] < $rowLT["tgl_mulai"]) {
                                                $datetime_request = strtotime($rowLT["tgl_request"]);
                                                $datetime_mulai = strtotime($rowLT["tgl_mulai"]);
                                                $telat_in_seconds = $datetime_mulai - $datetime_request;

                                                // Mengonversi selisih waktu menjadi jam, menit, dan detik
                                                $telat_hours = floor($telat_in_seconds / 3600);
                                                $telat_minutes = floor(($telat_in_seconds % 3600) / 60);
                                                $telat_seconds = $telat_in_seconds % 60;

                                                // Format selisih waktu ke dalam string "x jam, y menit, z detik"
                                                $telat_formatted = '';
                                                if ($telat_hours > 0) {
                                                    $telat_formatted .= $telat_hours . ' jam, ';
                                                }
                                                if ($telat_minutes > 0) {
                                                    $telat_formatted .= $telat_minutes . ' menit, ';
                                                }
                                                $telat_formatted .= $telat_seconds . ' detik';
                                            } else {
                                                $telat_formatted = '0';
                                            }
                                        } else {
                                            $telat_formatted = '-';
                                        }
                                ?>


                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Bonus</span>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-flex justify-content-start text-start">
                                    <h6 class="mb-1 text-dark font-weight-bold text-center text-sm">
                                        <?php
                                        $getBonus = "SELECT SUM(bonus) AS total_bonus FROM kegiatan WHERE id_teknisi = '$idT' AND kode_transaksi = '$kodeTransaksi'";
                                        $resultBonus = mysqli_query($conn, $getBonus);
                                        if ($resultBonus) {
                                            $rowBonus = mysqli_fetch_assoc($resultBonus);
                                            $totalBonus = $rowBonus['total_bonus'];

                                            // Format total bonus menjadi mata uang rupiah
                                            $totalBonus_formatted = number_format($totalBonus, 0, ',', '.');

                                            echo "<h6 class='mb-1 text-dark font-weight-bold text-sm text-end'>Rp " . $totalBonus_formatted . "</h6>";

                                            // Sekarang $totalBonus_formatted berisi jumlah bonus dalam format rupiah
                                        } else {
                                            echo "Rp 0";
                                        }

                                        ?></h6>
                                </div>

                                <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                    <span class="text-xs">Denda</span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0 d-flex justify-content-between justify-content-md-start align-items-start">
                                    <?php
                                        // Memeriksa apakah $denda adalah null dan menggantinya dengan 0 jika iya
                                        $denda = $denda ?? 0;

                                        // Ubah nilai menjadi format rupiah
                                        $denda_formatted = number_format($denda, 0, ',', '.');

                                        echo "<span class='text-sm font-weight-bold text-dark d-md-none d-block'>Rp " . $denda_formatted . "</span>";
                                    ?>
                                    <button class="btn bg-gradient-danger me-3 ms-3 p-2 text-xs bonus-btn" data-bs-toggle="modal" data-bs-target="#bonusModal" data-id="<?php echo $idT; ?>" data-kode="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10 text-xs">edit</i></button>

                                    <?php
                                        echo "<span class='text-sm font-weight-bold text-dark d-md-block d-none'>Rp " . $denda_formatted . "</span>";
                                    ?>
                                    <!--<h6 class="mb-1 text-dark font-weight-bold text-sm text-start text-md-center">Denda</h6>-->
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