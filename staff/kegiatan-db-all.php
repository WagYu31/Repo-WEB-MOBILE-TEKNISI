<div class="col-lg-12">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Selesai</h6>
                </div>
            </div>
        </div>
        <?php
        $current_date = date("Y-m-d"); // Today's date
        $tomorrow_date = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date
        $current_time = date("H:i:s"); // Current time

        // $sql = "SELECT k.*, t.nama_teknisi, c.nama AS nama_customer, c.telp AS cust_nomor
        //     FROM kegiatan k
        //     LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
        //     LEFT JOIN customer c ON k.customer_id = c.id
        //     WHERE k.status = 'selesai' OR k.status = 'N'
        //     AND DATE(k.jadwal) < '$current_date%' AND k.deleted_at IS NULL
        //     GROUP BY kode
        //     ORDER BY COALESCE(k.jadwal, '9999-12-31') DESC";
        
        $sql = "
        SELECT
            k.*,
            t.nama_teknisi,
            c.nama AS nama_customer,
            c.telp AS cust_nomor
        FROM
            kegiatan k
        INNER JOIN (
            SELECT
                sub_k.kode,
                MAX(sub_k.id) AS max_id
            FROM
                kegiatan sub_k
            WHERE
                sub_k.deleted_at IS NULL
            GROUP BY
                sub_k.kode
        ) AS latest_kegiatan ON k.kode = latest_kegiatan.kode AND k.id = latest_kegiatan.max_id
        LEFT JOIN
            team_kegiatan t ON k.id = t.kegiatan_id
        LEFT JOIN
            customer c ON k.customer_id = c.id
        WHERE
            k.status IN ('selesai', 'selesai by admin')
            AND k.deleted_at IS NULL
        GROUP BY
            k.kode
        ORDER BY
            COALESCE(k.jadwal, '9999-12-31') DESC
        ";


        $result = mysqli_query($conn, $sql);
        ?>
        <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-4 col-12" id="data-tek">

                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                    <div class="row px-4">
                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                            <span class="text-xs">/ Kegiatan</span>
                        </div>
                        
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm text-center">Invoice</h6>
                        </div>

                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Tanggal</h6>
                            <span class="text-xs">/ Jam</span>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Permintaan Dari</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0  text-start text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Aksi</h6>
                        </div>
                    </div>
                </li>
                <?php
                setlocale(LC_TIME, 'id_ID.utf8');
                $groupedData = [];

                if (mysqli_num_rows($result) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $kodeTransaksi = $row['kode'];


                        $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']);

                        // Include the appropriate menu file based on device size
                        if ($isMobile) {
                            include "kegiatan-ln-mobile.php";
                        } else {
                            include "kegiatan-ln-dekstop.php";
                        }
                        $no++;
                    }
                } else {
                    echo "<div class='ms-4 text-sm'>Tidak ada kegiatan untuk Hari Ini</div>";
                }




                ?>

            </ul>
        </div>
    </div>
</div>