<div class="container">
    <?php
    if (isset($_GET["id_cust"])) {
        $customer = intval($_GET["id_cust"]); // Pastikan nilai adalah integer

        // Query untuk mendapatkan data pelaksanaan_kegiatan
        $sql = "
        SELECT 
            pelaksanaan_kegiatan.*, 
            teknisi.nama AS teknisi_name
        FROM 
            pelaksanaan_kegiatan
        LEFT JOIN 
            kegiatan ON pelaksanaan_kegiatan.kegiatan_id = kegiatan.id
        LEFT JOIN 
            teknisi ON pelaksanaan_kegiatan.teknisi_id = teknisi.id
        WHERE 
            kegiatan.customer_id = $customer
        ORDER BY 
            pelaksanaan_kegiatan.waktu_mulai DESC";

        $result = mysqli_query($conn, $sql);

        if (!$result) {
            echo "Error: " . mysqli_error($conn);
            exit();
        }

        // Query untuk mendapatkan nama customer
        $cekNm = "SELECT nama FROM customer WHERE id = $customer";
        $resNm = mysqli_query($conn, $cekNm);
        if ($resNm) {
            $rowNm = mysqli_fetch_assoc($resNm);
            $namaCustomer = $rowNm['nama'];
        } else {
            $namaCustomer = "Tidak Diketahui";
        }
    } else {
        echo "ID Customer tidak valid.";
        exit();
    }
    ?>

    <div class="card p-4 mt-0">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-0 lead font-weight-bold text-uppercase">Pelaksanaan Kegiatan <?php echo htmlspecialchars($namaCustomer); ?></h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0">
            <!-- Tabel data pelaksanaan kegiatan -->
            <div class="table-responsive mt-3">
    <table class="table table-hover table-sm text-nowrap w-100">
        <thead class="text-dark">
            <tr>
                <th scope="col" class='text-center'>No</th>
                <th scope="col" class='text-center'>Nama Teknisi</th>
                <th scope="col" class='text-center'>Waktu Mulai</th>
                <th scope="col" class='text-center'>Waktu Selesai</th>
                <!--<th scope="col" class='text-center'>Invoice</th>-->
                <th scope="col" class='text-center'>Status</th>
                <th scope="col" class="w-30 text-center">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td class='text-center text-sm'>" . $no . "</td>";
                echo "<td class='text-wrap text-sm'><a href='list-kegiatan-teknisi.php?idTek=" . htmlspecialchars($row['teknisi_id']) . "' target='_blank'> " . htmlspecialchars($row['teknisi_name'] ?: "Tidak Diketahui") . "</a></td>";
                echo "<td class='text-center text-sm'><span class='text-dark'><b>" . ($row['waktu_mulai'] ? date("d/M/Y", strtotime($row['waktu_mulai'])) . "</b></span><br>" . date("H : i : s", strtotime($row['waktu_mulai'])) : "-") . "</td>";
                echo "<td class='text-center text-sm'><span class='text-dark'><b>" . ($row['waktu_selesai'] ? date("d/M/Y", strtotime($row['waktu_selesai'])) . "</b></span><br>" . date("H : i : s", strtotime($row['waktu_selesai'])) : "-") . "</td>";

                // Keterangan
                $keterangan = [];
                if (!empty($row['permasalahan'])) {
                    $keterangan[] = "Permasalahan: " . htmlspecialchars($row['permasalahan']);
                }
                if (!empty($row['solusi'])) {
                    $keterangan[] = "Solusi: " . htmlspecialchars($row['solusi']);
                }
                if (!empty($row['keterangan'])) {
                    $keterangan[] = "Keterangan: " . htmlspecialchars($row['keterangan']);
                }
                // Status
                // echo "<td class='text-wrap text-sm text-center' style='text-transform:capitalize;'>" . htmlspecialchars($row['no_invoice'] ?: "-") . "<br>";
                // if (!empty($row['nominal_invoice'])) {
                //     echo "Rp " . number_format($row['nominal_invoice'], 0, ',', '.');
                // } else {
                //     echo "-";
                // }
                
                // echo "</td>";
                echo "<td class='text-wrap text-sm text-center' style='text-transform:capitalize;'>" . htmlspecialchars($row['status'] ?: "-") . "</td>";
                echo "<td class='text-wrap text-sm w-30'>" . implode("<br>", $keterangan) . "<br>";
                for ($i = 1; $i <= 5; $i++) {
                    $imageField = 'image_' . $i;
                    if (!empty($row[$imageField])) {
                        $filePath = "https://grav-tech.com/jadwal-3/api/storage/app/image/" . htmlspecialchars($row[$imageField]);
                        echo "<a href='$filePath' target='_blank' class='btn btn-outline-primary p-1 px-2 mt-2'>
                                <i class='material-icons opacity-10'>download</i> Download
                              </a>";
                    }
                }
                echo "</td>";

                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>
</div>
        </div>
    </div>
</div>
