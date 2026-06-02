<?php
// Tentukan nilai default $keyword untuk menghindari error "undefined variable"
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if (!empty($keyword)) {
    $sql = "SELECT c.*, COUNT(p.id) AS total_kegiatan
    FROM customer c
    LEFT JOIN kegiatan k ON c.id = k.customer_id
    LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
    WHERE (c.nama LIKE '%$keyword%' 
        OR c.telp LIKE '%$keyword%' 
        OR c.alamat LIKE '%$keyword%' 
        OR c.kota LIKE '%$keyword%' 
        OR c.kodepos LIKE '%$keyword%' 
        OR c.provinsi LIKE '%$keyword%')
        AND c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY total_kegiatan DESC";
} else {
    // Jika tidak ada kata kunci pencarian, tampilkan semua data
    $sql = "SELECT c.*, COUNT(p.id) AS total_kegiatan
    FROM customer c
    LEFT JOIN kegiatan k ON c.id = k.customer_id
    LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
    WHERE c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY total_kegiatan DESC";
}
$result = mysqli_query($conn, $sql);
?>
<div class="container">
    <div class="card p-4">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Data Customer</h6>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 col-md-6 d-flex flex-row justify-content-start align-items-center">
                    <a href="tambah-customer.php" class="btn bg-gradient-info me-2"><i class='fas fa-plus me-2'></i>Tambah</a>
                    <a href="customer.php" class="btn bg-gradient-info">Refresh</a>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="">
                        <div class="mb-3 d-flex flex-row justify-content-start align-items-center">
                            <input type="text" class="form-control border w-60 w-md-80" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius:0; padding:7.5px;" placeholder="Cari berdasarkan nama, nomor telepon, atau alamat" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                            <button class="btn bg-gradient-primary w-40 w-md-20" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius:0; margin-top:15.5px;" type="submit">Cari</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
                <div class="card-body pb-0 p-0 text-center mt-3">
                    <!-- Tampilan data teknisi -->
                    <?php
                    $no = 1;
                    if (mysqli_num_rows($result) > 0) {
                    ?>
                        <div class="row border-bottom font-weight-bold d-md-flex d-none flex-row justify-content-start justify-content-md-center align-items-center">
                            <div class="w-5 d-none d-md-flex">No</div>

                            <div class="w-20 d-none d-md-flex">Nama</div>
                            <div class="col-6 d-flex d-md-none">Nama</div>

                            <!--<div class="w-15 d-none d-md-flex text-start">Jumlah Permintaan</div>-->
                            <div class="col-6 d-flex d-md-none">No Handphone</div>

                            <div class="w-15 d-none d-md-flex">No Handphone</div>
                            <div class="col-6 d-flex d-md-none text-start">Jumlah Permintaan</div>

                            <div class="w-50 d-none d-md-flex">Alamat</div>
                            <div class="col-6 d-flex d-md-none">Alamat</div>

                            <div class="w-10 d-none d-md-flex">Aksi</div>
                            <div class="col-6 d-flex d-md-none"></div>
                            <div class="col-6 d-flex d-md-none">Aksi</div>
                        </div>
                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <div class="row border-bottom py-3 text-sm text-dark">
                                <div class="w-5 d-none d-md-flex"><?= $no ?></div>

                                <div class="w-20 text-start d-none d-md-flex">
                                    <?php $id_cust = $row['id'];
                                    echo "<a href='customer-detail.php?id_cust=" . $id_cust . "'> " . $row['nama'] . "</a>";
                                    ?>
                                </div>
                                <div class="col-12 d-flex d-md-none text-start" style="font-weight:bold;">
                                    <?php $id_cust = $row['id'];
                                    echo "<a href='customer-detail.php?id_cust=" . $id_cust . "'> " . $row['nama'] . "</a>";
                                    ?>
                                </div>

                                <!--<div class="w-15 d-none d-md-flex justify-content-center">-->
                                    <?php
                                    // $id_cust = $row["id"];
                                    // $queryTotalKegiatan = "SELECT COUNT(p.id) AS total_kegiatan
                                    //                         FROM customer c
                                    //                         LEFT JOIN kegiatan k ON c.id = k.customer_id
                                    //                         LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
                                    //                         WHERE c.id = $id_cust";
                                    // $resultTotalKegiatan = mysqli_query($conn, $queryTotalKegiatan);
                                    // $totalKegiatan = mysqli_fetch_assoc($resultTotalKegiatan)["total_kegiatan"];
                                    // echo $totalKegiatan;
                                    ?>
                                <!--</div>-->
                                <?php
                                // Nomor telepon dari database
                                $nomor_tlp = $row['telp'];

                                // Menghilangkan seluruh tanda baca dan huruf, hanya menyisakan angka
                                $nomor_tlp_clean = preg_replace('/[^0-9]/', '', $nomor_tlp);

                                // Mengganti angka pertama dengan 62
                                $nomor_tlp_final = '62' . substr($nomor_tlp_clean, 1);

                                // URL untuk digunakan dalam href
                                $url_whatsapp = 'https://api.whatsapp.com/send?phone=' . $nomor_tlp_final;
                                ?>

                                <div class="col-12 d-flex d-md-none">
                                    <a href="<?= $url_whatsapp ?>" target="_blank"><?= $nomor_tlp ?></a>
                                </div>

                                <div class="w-15 d-none d-md-flex">
                                    <a href="<?= $url_whatsapp ?>" target="_blank"><?= $nomor_tlp ?></a>
                                </div>

                                <div class="col-12 d-flex d-md-none">
                                    <?php
                                    $id_cust = $row["id"];
                                    $queryTotalKegiatan = "SELECT COUNT(p.id) AS total_kegiatan
                                                            FROM customer c
                                                            LEFT JOIN kegiatan k ON c.id = k.customer_id
                                                            LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
                                                            WHERE c.id = $id_cust";
                                    $resultTotalKegiatan = mysqli_query($conn, $queryTotalKegiatan);
                                    $totalKegiatan = mysqli_fetch_assoc($resultTotalKegiatan)["total_kegiatan"];
                                    echo $totalKegiatan . " Permintaan";
                                    ?></div>

                                <?php
                                $alamatCS = $row['alamat'];
                                ${"makeLinksClickable$no"} = function ($text) {
                                    $pattern = '/(http|https|ftp):\/\/[^\s]+/i';
                                    $replacement = '<a href="$0" target="_blank">$0</a>';
                                    $text = preg_replace($pattern, $replacement, $text);

                                    return $text;
                                };
                                $linkClick = ${"makeLinksClickable$no"};
                                $CL = $linkClick($alamatCS);
                                ?>
                                <div class="w-50 text-start d-none d-md-flex flex-column wrap-text"><?= $CL; ?></div>
                                <div class="col-12 d-flex d-md-none flex-column text-start wrap-text"><?= $CL; ?></div>

                                <div class="w-10 d-none d-md-flex">
                                    <a href='edit-customer.php?id=<?= $row["id"] ?>' class='btn btn-warning btn-sm me-2 ' title='Edit' style='height: 33px;'><i class="material-icons opacity-10" style='font-size: 15px;'>edit</i></a>
                                    <a href='delete.php?id=<?= $row["id"] ?>' class='btn btn-danger btn-sm' title='Delete' style='height: 33px;'><i class="material-icons opacity-10" style='font-size: 15px;'>delete</i></a>
                                </div>
                                <div class="col-12 d-flex d-md-none"></div>
                                <div class="col-12 d-flex d-md-none mt-1">
                                    <a href='edit-customer.php?id=<?= $row["id"] ?>' class='btn btn-warning btn-sm mr-2' title='Edit'><i class="material-icons opacity-10" style='font-size: 15px;'>edit</i></a>
                                    <a href='delete.php?id=<?= $row["id"] ?>' class='btn btn-danger btn-sm ms-2' title='Delete'><i class="material-icons opacity-10" style='font-size: 15px;'>delete</i></a>
                                </div>
                            </div>
                    <?php
                            $no++;
                        }
                    } else {
                        echo "<div class='row'><div class='col'><p>Tidak ada data customer.</p></div></div>";
                    }
                    ?>
                </div>
            </div>
        </div>