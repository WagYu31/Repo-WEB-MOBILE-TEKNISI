<?php
include "conn.php";

if (isset($_POST['kode_transaksi'])) {
    $kode_transaksi = $_POST['kode_transaksi'];

    $query = "SELECT k.*, t.nama, t.id_teknisi, c.nama AS nama_cust, c.id_cust 
            FROM kegiatan k
            JOIN teknisi t ON t.id_teknisi = k.id_teknisi
            JOIN customer c ON c.id_cust = k.id_cust
            WHERE k.kode = ?";

    $stmt = mysqli_prepare($conn, $query);

    mysqli_stmt_bind_param($stmt, "s", $kode_transaksi);

    mysqli_stmt_execute($stmt);

    $resultQuery = mysqli_stmt_get_result($stmt);
    // Menghitung jumlah baris yang dihasilkan
    $num_rows_data = mysqli_num_rows($resultQuery);

    // Menyimpan jumlah baris dalam sebuah variabel
    $jumlah_data = $num_rows_data;
    
    $dataRow = mysqli_fetch_assoc($resultQuery);
    $CustName = $dataRow['nama_cust'];
    $mulai = $dataRow['tgl_mulai'];
    $selesai = $dataRow['tgl_selesai'];
    $request = $dataRow['tgl_request'];
    $stat = $dataRow['status'];
    $inv = $dataRow['invoice'];
    $tglInv = $dataRow['tgl_inv'];
    

    $nama_bulan = array(
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    );

    list($tahun, $bulan_num, $tanggal) = explode('-', date('Y-m-d', strtotime($request)));
    list($jam, $menit) = explode(':', date('H:i', strtotime($request)));

    // Memformat tanggal dalam format 'd M Y' dan jam dalam format 'H:i'
    $tanggal_format = $tanggal . ' ' . $nama_bulan[$bulan_num] . ' ' . $tahun;
    $jam_format = $jam . ':' . $menit;

?>

    <div class="col-12 mb-3">
        <div class="row">
            <div class="col-12">
                <div class="row d-flex flex-row justify-content-between align-items-center">
                    <div class="col-6"><?php echo $kode_transaksi; ?></div>
                    <div class="col-6 text-end"><?php echo $tanggal_format; ?></div>
                </div>
            </div>
            <div class="col-12">
                <div class="row d-flex flex-row justify-content-between align-items-center">
                    <div class="col-6 text-info text-lg font-weight-bold"><?php echo $CustName; ?></div>
                    <div class="col-6 text-end"><?php echo $jam_format; ?></div>
                </div>
            </div>
        </div>
    </div>


    <?php
    
    $query_count = "SELECT COUNT(*) AS count_data 
                FROM kegiatan k
                JOIN teknisi t ON t.id_teknisi = k.id_teknisi
                JOIN customer c ON c.id_cust = k.id_cust
                WHERE k.kode_transaksi = ? AND (k.tgl_selesai IS NOT NULL AND k.tgl_selesai != '0000-00-00 00:00:00' AND k.tgl_mulai IS NOT NULL)";

    $stmt_count = mysqli_prepare($conn, $query_count);
    mysqli_stmt_bind_param($stmt_count, "s", $kode_transaksi);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    
    $jumlah_data_count = $row_count['count_data'];


    $sql = "SELECT k.*, t.nama, t.id_teknisi, c.nama AS nama_cust, c.id_cust 
            FROM kegiatan k
            JOIN teknisi t ON t.id_teknisi = k.id_teknisi
            JOIN customer c ON c.id_cust = k.id_cust
            WHERE k.kode_transaksi = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $kode_transaksi);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    // Menghitung jumlah baris yang dihasilkan
    $num_rows = mysqli_num_rows($result);

    // Menyimpan jumlah baris dalam sebuah variabel
    $jumlah_data_semua = $num_rows;
    
    
    if ($jumlah_data_count > 0) {
    ?>
        <form method="POST" action="tambah-inv.php" class="mt-3">
            <div class="mb-3">
                <label for="invoice" class="">Nomor Invoice</label>
                <input type="text" class="form-control border p-2" name="invoice" value="<?php echo $inv;?>" id="invoice" placeholder="">
            </div>
            <div class="mb-3">
                <label for="dateInv" class="">Tanggal Invoice</label>
                <input type="date" class="form-control border p-2" name="dateInv" value="<?php echo $tglInv;?>" id="dateInv">
            </div>
            <div class="mb-3">
                <label for="nominal" class="">Total Nominal Invoice</label>
                <div class="input-group">
                    <!--<span class="input-group-text">Rp</span>-->
                    <?php
                    $getBonus = "SELECT SUM(bonus) AS total_bonus FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
                        $resultBonus = mysqli_query($conn, $getBonus);
                            if ($resultBonus) {
                                $rowBonus = mysqli_fetch_assoc($resultBonus);
                                $totalBonus = $rowBonus['total_bonus'];
                                $totalBonus_formatted = number_format($totalBonus, 0, ',', '.');
                            } else {
                                $totalBonus = 0;
                            }
                                            ?>
                    <input type="text" class="form-control border p-2" name="nominal" id="nominal" value="Rp <?php echo $totalBonus_formatted;?>" onkeyup="formatRupiah(this)">
                </div>
            </div>
            <?php
                echo "<b>Terhitung " . $jumlah_data_count . " Pekerjaan Terselesaikan dari " . $jumlah_data_semua . " Pekerjaan</b>";
                echo "<div class='text-xs'>* Hanya kegiatan yang diselesaikan yang akan menerima pembagian dana dari invoice yang di input.</div>";
            ?>
            <input type="hidden" name="kodeTran" id="kodeTran" value="<?php echo $kode_transaksi ?>">
            <input type="hidden" name="jumlahData" id="jumlahData" value="<?php echo $jumlah_data_count ?>">
            <input type="hidden" name="jumlahDataAll" id="jumlahDataAll" value="<?php echo $jumlah_data_semua ?>">
            
            <button type="submit" class="btn bg-gradient-info mt-3">Proses</button>
        </form>
    <?php
    }
    else{
        echo "<b>Terhitung " . $jumlah_data_count . " Pekerjaan Terselesaikan dari " . $jumlah_data_semua . " Pekerjaan</b>";
        echo "<div class='text-xs'>* Hanya kegiatan yang diselesaikan yang akan menerima pembagian dana dari invoice yang di input.</div>";
    }
    ?>



    <ul class="list-group m-0 mt-4 col-12" id="data-rincian">
        <li class="list-group-item border-0 d-none d-md-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
            <div class="row px-4">
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                    <!--<span class="text-xs">/ Kegiatan</span>-->
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Request</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm"> Mulai</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Terlambat</h6>
                </div>
            </div>
        </li>

        <?php

        if ($result) {
            while ($data = mysqli_fetch_assoc($result)) {
                $namaTek = $data['nama'];
                $customer = $data['nama_cust'];
                $ketFinish = $data['ket_finish'];

                $tgl_request = $data['tgl_request'];
                $formattedDateReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_request)) : '-';
                $formattedTimeReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_request)) : '-';

                $tgl_mulai = $data['tgl_mulai'];
                $formattedDateMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_mulai)) : '-';
                $formattedTimeMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_mulai)) : '-';

                $tgl_selesai = $data['tgl_selesai'];
                $formattedDateSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_selesai)) : '-';
                $formattedTimeSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_selesai)) : '-';

                $status = $data['status'];



        ?>

                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
                    <div class="row px-4">
                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Teknisi</div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php
                                echo $namaTek;
                                ?>
                            </h6>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Request</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateReq; ?></h6>
                            <span class="text-xs"><?php echo $formattedTimeReq; ?></span>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Mulai</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"> <?php echo $formattedDateMli; ?></h6>
                            <span class="text-xs"><?php echo $formattedTimeMli; ?></span>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Selesai</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
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
                        if ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') {
                            if ($tgl_request < $tgl_mulai) {
                                $datetime_request = strtotime($tgl_request);
                                $datetime_mulai = strtotime($tgl_mulai);
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


                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Terlambat</div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $telat_formatted; ?></h6>
                        </div>
                    </div>
                </li>

        <?php
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
        ?>
        </li>
    </ul>
<?php
    mysqli_stmt_close($stmt);
}
?>

<script>
    function formatRupiah(input) {
        // Menghilangkan semua karakter selain angka
        var nominal = input.value.replace(/\D/g, "");
        // Menambahkan titik sebagai pemisah ribuan
        nominal = nominal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        // Menambahkan format Rupiah
        input.value = "Rp " + nominal;
    }
</script>