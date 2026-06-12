<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12">
            <button id="toggleLoadMore1" type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Kegiatan Yang Akan Datang</button>
        </div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4" id="loadMoreX" style="display: block;">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <?php
        $current_date = date("Y-m-d"); // Today's date
        $tomorrow_date = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date
        $current_time = date("H:i:s"); // Current time

        $sql = "SELECT k.*, t.nama_teknisi, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat
        FROM kegiatan k
        LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
        LEFT JOIN customer c ON k.customer_id = c.id
        WHERE k.status NOT IN ('waiting', 'selesai', 'selesai by admin')
        AND (
            DATE(k.jadwal) > '$current_date'
            OR k.jadwal = '0000-00-00 00:00:00'
        )
        AND k.deleted_at IS NULL
        ORDER BY COALESCE(k.jadwal, '9999-12-31') ASC";


        $result = mysqli_query($conn, $sql);


        ?>
        <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-2 col-12" id="data-tek">

            <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                    <div class="row px-4">
                        <div class="col-6 col-md-1 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Kegiatan</h6>
                            <!-- <span class="text-xs">/ Status</span> -->
                        </div>

                        <div class="col-6 col-md-1 mb-2 mb-md-0 text-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Request</h6>
                            <!-- <span class="text-xs">/ Jam</span> -->
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                        </div>

                        <div class="col-6 col-md-3 mb-2 mb-md-0 text-left text-md-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6>
                        </div>

                        <div class="col-6 col-md-1 mb-2 mb-md-0 text-center ms-4">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Dibuat</h6>
                        </div>

                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-start text-md-end ms-n4">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"></h6>
                        </div>
                    </div>

                    </li>
                <?php
                $groupedData = [];

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $kodeTransaksi = $row['kode'];

                        if (!isset($groupedData[$kodeTransaksi])) {
                            $groupedData[$kodeTransaksi] = [
                                'jadwal' => [],
                                'customer_id' => [],
                                'customer' => [],
                                'cust_nomor' => [],
                                'nama_teknisi' => [],
                                'teknisi' => [],
                                'kegiatan' => [],
                                'keterangan' => [],
                                'request' => [],
                                'status' => [],
                                'created_at' => [],
                                'alamat' => [],
                                'id' => []
                            ];
                        }

                        $groupedData[$kodeTransaksi]['jadwal'][] = $row['jadwal'];
                        $groupedData[$kodeTransaksi]['customer_id'][] = $row['customer_id'];
                        $groupedData[$kodeTransaksi]['customer'][] = $row['nama_customer'];
                        $groupedData[$kodeTransaksi]['cust_nomor'][] = $row['cust_nomor'];
                        $teknisiIds = explode(',', $row['id']);
                        $teknisiNames = [];
                        foreach ($teknisiIds as $teknisiId) {
                            $teknisiQuery = "SELECT nama FROM teknisi WHERE id = " . intval($teknisiId);
                            $teknisiResult = mysqli_query($conn, $teknisiQuery);
                            if ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                $teknisiNames[] = $teknisiRow['nama'];
                            }
                        }
                        $groupedData[$kodeTransaksi]['teknisi'][] = implode("<br>", $teknisiNames);
                        $groupedData[$kodeTransaksi]['kegiatan'][] = $row['kegiatan'];
                        $groupedData[$kodeTransaksi]['keterangan'][] = $row['keterangan'];
                        $groupedData[$kodeTransaksi]['request'][] = $row['request'];
                        $groupedData[$kodeTransaksi]['status'][] = $row['status'];
                        $groupedData[$kodeTransaksi]['id'][] = $row['id'];
                        $groupedData[$kodeTransaksi]['created_at'][] = $row['created_at'];
                        $groupedData[$kodeTransaksi]['alamat'][] = $row['alamat'];
                    }
                } else {
                    echo "<div class='ms-4 text-sm'>Tidak ada kegiatan untuk Hari Ini</div>";
                }

                $no = 1;

                foreach ($groupedData as $kodeTransaksi => $data) {

                    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']);

                    // Include the appropriate menu file based on device size
                    if ($isMobile) {
                        include "kegiatan-db-load-more-mobile.php";
                    } else {
                        include "kegiatan-db-load-more-dekstop.php";
                    }
                    $no++;
                }
                ?>

            </ul>
        </div>
    </div>
</div>