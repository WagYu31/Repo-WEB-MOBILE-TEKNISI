<?php

// Default: 30 hari terakhir
$default_start_date = date("Y-m-d", strtotime("-29 days")); // -29 karena inklusif dengan hari ini jadi 30 hari
$default_end_date = date("Y-m-d"); // Hari ini

$start_date = $default_start_date;
$end_date = $default_end_date;

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

// Logika untuk periode cepat (jika ada tombolnya nanti)
if (isset($_GET['periode'])) {
    if ($_GET['periode'] == 'last_7_days') {
        $start_date = date("Y-m-d", strtotime("-6 days"));
        $end_date = date("Y-m-d");
    } elseif ($_GET['periode'] == 'last_30_days') {
        $start_date = date("Y-m-d", strtotime("-29 days"));
        $end_date = date("Y-m-d");
    }
    // Anda bisa tambahkan periode lain seperti 'this_month', 'last_month'
}

// Ambil nama teknisi untuk judul laporan
$namaTeknisiUntukJudul = "";
$teknisiInfoFound = false;
if (isset($idTeknis) && $idTeknis !== null && $conn) {
    $sqlNamaTek = "SELECT nama FROM teknisi WHERE id = '" . mysqli_real_escape_string($conn, $idTeknis) . "' LIMIT 1";
    $resultNamaTek = mysqli_query($conn, $sqlNamaTek);
    if ($resultNamaTek && mysqli_num_rows($resultNamaTek) > 0) {
        $rowNamaTek = mysqli_fetch_assoc($resultNamaTek);
        $namaTeknisiUntukJudul = htmlspecialchars($rowNamaTek['nama']);
        $teknisiInfoFound = true;
    } else {
        $namaTeknisiUntukJudul = "Teknisi Tidak Ditemukan";
    }
} else if (!isset($idTeknis) || $idTeknis === null) {
    $namaTeknisiUntukJudul = "Belum Dipilih";
}

?>
<!--<div class="col-12">-->
<!--    <div class="card mb-3 p-2 ps-4">-->
<!--        <input type="text" id="search-input" class="form-control" placeholder="Cari dalam detail kegiatan...">-->
<!--    </div>-->
<!--</div>-->
<div class="col-lg-12" id="printable-content">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 col-md-7 d-flex align-items-center mb-3 mb-md-0">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bolder text-uppercase">Laporan Detail Kegiatan <?php echo $namaTeknisiUntukJudul; ?></h6>
                </div>
                <div class="col-12 col-md-5">
                    <form method="GET" action="" class="w-100">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label form-label-sm text-xs">Dari Tanggal:</label>
                                <input type="date" class="form-control form-control-sm border p-2 no-print" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" aria-label="Tanggal Mulai Periode">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label form-label-sm text-xs">Sampai Tanggal:</label>
                                <input type="date" class="form-control form-control-sm border p-2 no-print" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" aria-label="Tanggal Akhir Periode">
                            </div>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <button type="submit" name="periode" value="last_7_days" class="btn btn-outline-secondary btn-sm m-0 no-print">7 Hari Terakhir</button>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="periode" value="last_30_days" class="btn btn-outline-secondary btn-sm m-0 no-print">30 Hari Terakhir</button>
                            </div>
                            <div class="col">
                                 <input type="hidden" name="idTek" value="<?php echo htmlspecialchars($idTeknis); ?>">
                                 <button type="submit" class="btn bg-gradient-info btn-sm m-0 no-print w-100">Tampilkan Periode</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $result = null; // Initialize result
        if (isset($idTeknis) && $idTeknis !== null && $teknisiInfoFound) {
            $sql = "SELECT
                pk.id AS pelaksanaan_id,
                pk.waktu_mulai,
                pk.waktu_selesai,
                pk.latitude,
                pk.longitude,
                pk.latitude_s,
                pk.longitude_s,
                pk.permasalahan,
                pk.solusi,
                pk.kode,
                pk.keterangan AS keterangan_pelaksanaan,
                pk.image_1,
                pk.image_2,
                pk.image_3,
                pk.image_4,
                pk.image_5,
                k.kegiatan AS nama_kegiatan,
                k.customer_id,
                cust.nama AS nama_customer
            FROM
                pelaksanaan_kegiatan pk
            JOIN
                kegiatan k ON k.id = pk.kegiatan_id
            JOIN
                teknisi t ON t.id = pk.teknisi_id
            JOIN
                customer cust ON cust.id = k.customer_id
            WHERE
                pk.deleted_at IS NULL
                AND t.id = '" . mysqli_real_escape_string($conn, $idTeknis) . "'
                AND DATE(pk.waktu_mulai) >= '" . mysqli_real_escape_string($conn, $start_date) . "'  -- MODIFIKASI DI SINI
                AND DATE(pk.waktu_mulai) <= '" . mysqli_real_escape_string($conn, $end_date) . "'    -- MODIFIKASI DI SINI
                AND k.deleted_at IS NULL
            ORDER BY
                pk.waktu_mulai DESC";

            $result = mysqli_query($conn, $sql);

            if (!$result) {
                echo "<div class='p-3 text-danger'>Error executing query: " . mysqli_error($conn) . "</div>";
            }
        }
        ?>
        <div class="card-body pt-2 pb-0 p-0">
            <div class="col-12 px-3 py-2">
                <p class="text-dark text-sm">
                    Detail Kegiatan Periode:
                    <strong class="ms-1">
                    <?php
                    $periode_mulai_formatted = formatTanggal('dd MMMM yyyy', $start_date);
                    $periode_selesai_formatted = formatTanggal('dd MMMM yyyy', $end_date);
                    echo htmlspecialchars($periode_mulai_formatted) . " - " . htmlspecialchars($periode_selesai_formatted);

                    ?>
                    </strong>
                </p>
            </div>
            <ul class="list-group list-group-flush m-0 col-12" id="data-tek">

                <li class="list-group-item d-none d-md-flex py-2 px-3 bg-light border-bottom">
                    <div class="row w-100">
                        <div class="col-md-1 text-center"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Tanggal</small></div>
                        <div class="col-md-1"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Kegiatan</small></div>
                        <div class="col-md-2"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Customer</small></div>
                        <div class="col-md-1"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Mulai</small></div>
                        <div class="col-md-1"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Selesai</small></div>
                        <div class="col-md-3"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Permasalahan</small></div>
                        <div class="col-md-2"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Solusi</small></div>
                        <div class="col-md-1"><small class="text-uppercase text-dark font-weight-bolder opacity-7">Dokumentasi</small></div>
                    </div>
                </li>

                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $tglKegiatan = date('d/m/Y', strtotime($row['waktu_mulai']));
                        $waktuMulai = date('H:i', strtotime($row['waktu_mulai']));
                        $waktuSelesai = $row['waktu_selesai'] ? date('H:i', strtotime($row['waktu_selesai'])) : '-';

                        $lokasiMulaiLink = "-";
                        if (!empty($row['latitude']) && !empty($row['longitude'])) {
                            $lokasiMulaiLink = "<a href='https://www.google.com/maps?q=" . htmlspecialchars(urlencode($row['latitude'])) . "," . htmlspecialchars(urlencode($row['longitude'])) . "' target='_blank' class='text-info fw-bold'>Peta</a>";
                        }

                        $lokasiSelesaiLink = "-";
                        if (!empty($row['latitude_s']) && !empty($row['longitude_s'])) {
                            $lokasiSelesaiLink = "<a href='https://www.google.com/maps?q=" . htmlspecialchars(urlencode($row['latitude_s'])) . "," . htmlspecialchars(urlencode($row['longitude_s'])) . "' target='_blank' class='text-info fw-bold'>Peta</a>";
                        }
                        
                        $imageButtons = "";
                        for ($i = 1; $i <= 5; $i++) {
                            if (!empty($row["image_" . $i])) {
                                $imageUrl = "https://grav-tech.com/jadwal-3/api/storage/app/image/" . rawurlencode($row["image_" . $i]);
                                // Gunakan class btn-sm untuk tombol lebih kecil, dan margin
                                $imageButtons .= "<a href='" . htmlspecialchars($imageUrl) . "' target='_blank' class='btn btn-outline-secondary p-1 py-0 btn-sm me-1 mb-1' style='background-color:white;'>📷</a>";
                            }
                        }
                        if (empty($imageButtons)) {
                            $imageButtons = "-";
                        }
                        ?>
                        <li class="list-group-item py-3 px-3 border-bottom">
                            <div class="row align-items-start">
                                <div class="col-12 col-md-1 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Tanggal: </strong>
                                    <span class="text-sm d-block d-md-inline text-md-center"><?php echo $tglKegiatan; ?></span><br>
                                    <span class="text-sm d-block d-md-inline text-md-center"><a href="view-kegiatan.php?kode_transaksi=<?php echo htmlspecialchars($row['kode']); ?>" target="_blank" class="text-info"><?php echo htmlspecialchars($row['kode']); ?></a></span>
                                </div>
                                <div class="col-12 col-md-1 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Kegiatan: </strong>
                                    <span class="text-sm text-capitalize"><?php echo htmlspecialchars($row['nama_kegiatan']); ?></span>
                                </div>
                                <div class="col-12 col-md-2 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Customer: </strong>
                                    <span class="text-sm"><a href="customer-detail.php?id_cust=<?php echo htmlspecialchars($row['customer_id']);?>" target="_blank"><?php echo htmlspecialchars($row['nama_customer']); ?></a></span>
                                </div>
                                <div class="col-12 col-md-1 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Mulai Pukul: </strong>
                                    <span class="text-sm d-block d-md-inline text-md-center"><?php echo $waktuMulai; ?></span><br>
                                    <span class="text-sm d-block d-md-inline text-md-center"><?php echo $lokasiMulaiLink; ?></span>
                                </div>
                                <div class="col-12 col-md-1 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Selesai Pukul: </strong>
                                    <span class="text-sm d-block d-md-inline text-md-center"><?php echo $waktuSelesai; ?></span><br>
                                    <span class="text-sm d-block d-md-inline text-md-center"><?php echo $lokasiSelesaiLink; ?></span>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Permasalahan: </strong>
                                    <span class="text-sm fst-italic">Permasalahan : <?php echo nl2br(htmlspecialchars($row['permasalahan'] ?: '-')); ?></span><br>
                                    <span class="text-sm fst-italic">Keterangan : <?php echo nl2br(htmlspecialchars($row['keterangan_pelaksanaan'] ?: '-')); ?></span>
                                </div>
                                <div class="col-12 col-md-2 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Solusi: </strong>
                                    <span class="text-sm fst-italic"><?php echo nl2br(htmlspecialchars($row['solusi'] ?: '-')); ?></span>
                                </div>
                                <div class="col-12 col-md-1 mb-md-0">
                                    <strong class="d-md-none text-xs text-muted">Dokumentasi: </strong>
                                    <div class="text-sm">
                                         <?php echo $imageButtons; // Variabel yang berisi tombol-tombol gambar ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php
                    }
                } else {
                     if (isset($idTeknis) && $idTeknis !== null && $teknisiInfoFound) {
                        echo "<li class='list-group-item border-0'><p class='text-center text-secondary p-3'>Tidak ada data kegiatan untuk teknisi '" . $namaTeknisiUntukJudul . "' di bulan " . htmlspecialchars($bt) . ".</p></li>";
                    } else if (!$teknisiInfoFound && isset($idTeknis) && $idTeknis !== null) {
                         echo "<li class='list-group-item border-0'><p class='text-center text-danger p-3'>Data teknisi dengan ID yang dipilih tidak ditemukan.</p></li>";
                    } else {
                         echo "<li class='list-group-item border-0'><p class='text-center text-secondary p-3'>Silakan pilih teknisi dan periode untuk menampilkan laporan kegiatan.</p></li>";
                    }
                }
                ?>
            </ul>
        </div>
    </div>
</div>