<?php

// Query untuk mengambil data kegiatan berdasarkan ID kegiatan
$sql = "SELECT v.*, s.nama AS nama_sales, c.nama AS nama_customer, c.no_wa AS cust_nomor
            FROM visits v
            LEFT JOIN sales s ON v.id_sales = s.id_sales
            LEFT JOIN cust c ON v.id_cust = c.id_cust
            WHERE v.kode_transaksi = '$kode_transaksi'";

$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);
$kodeTransaksi = $data['kode_transaksi'];

?>

<div class="list-group-item border-0 d-flex justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
    <div class="row px-4">
        <form method="POST" action="edit_data_kegiatan.php">
            <?php
            $updatedStatus = '';
            switch ($data['status']) {
                case 'dijadwalkan':
                    $updatedStatus = 'Dijadwalkan';
                    break;
                case 'on process':
                    $updatedStatus = 'Diproses';
                    break;
                case 'clear':
                    $updatedStatus = 'Selesai';
                    break;
                default:
                    $updatedStatus = $data['status'];
            }



            ?>

            <div class="col-12 col-md-12 mb-2 mb-md-3 d-flex flex-column">
                <h6 class="mb-1 text-dark font-weight-bold text-s"><?php echo $updatedStatus; ?> <span class="font-weight-normal text-sm">Request by </span></h6>
                <!-- <span class="text-s mt-3"><?php echo $updatedStatus; ?></span> -->
            </div>

            <?php

            $datetime = $data["tgl_visits"];

            // Format dan tampilkan tanggal dan waktu
            $formattedTime = date("H:i", strtotime($datetime));
            $formattedDate = date("d-m-Y", strtotime($datetime));
            $formattedDate2 = date("Y-m-d", strtotime($datetime));
            $tanggal_sekarang = date("d-m-Y");
            if ($formattedDate > $tanggal_sekarang) {
            ?>
                <div class="col-12 col-md-12 mb-2 mb-md-0 justify-content-start d-flex flex-row">
                    <input type="date" class="form-control border p-2 w-50 w-md-50" name="tanggal_pilihan" value="<?php echo $formattedDate2; ?>">
                    <input type="time" class="form-control border p-2 w-40 w-md-35 ms-4" name="waktu_pilihan" value="<?php echo $formattedTime; ?>">
                </div>
            <?php
            } else {
            ?>
                <div class="col-12 col-md-12 mb-2 mb-md-0 justify-content-start d-flex flex-row">
                    <input type="date" class="form-control border p-2 w-50 w-md-50" name="tanggal_pilihan" value="<?php echo $formattedDate2; ?>">
                    <input type="time" class="form-control border p-2 w-40 w-md-35 ms-4" name="waktu_pilihan" value="<?php echo $formattedTime; ?>">
                </div>
            <?php
            }

            $nomorHandphone = $data['cust_nomor'];

            if (substr($nomorHandphone, 0, 1) === '0') {
                $nomorHandphone = '62' . substr($nomorHandphone, 1);
            }

            ?>

            <div class="col-12 col-md-12 mb-4 mb-md-0 mt-4 text-start">
                <h6 class="mb-0">Customer</h6>
                <h6 class="ms-3 text-dark mb-n2 font-weight-bold text-sm"><?php echo $data['nama_customer']; ?></h6>
                <span class="ms-3 text-xs"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" class="text-info" target="_blank"><?php echo $data['cust_nomor']; ?></a></span>
            </div>

            <div class="col-12 col-md-12 mb-2 mt-2 mb-md-0 text-start d-flex flex-column">
                <h6 class="mb-0">Sales</h6>
                <h6 class="mb-1 text-dark font-weight-bold text-sm">
                    <?php
                    $sqlGetTek = "SELECT v.*, s.nama, s.id_sales FROM visits v
                        JOIN sales s ON v.id_sales = s.id_sales
                    WHERE v.kode_transaksi = '$kode_transaksi'";
                    $queryGetTek = mysqli_query($conn, $sqlGetTek);
                    $namaTekArray = [];
                    while ($rowGetTek = mysqli_fetch_assoc($queryGetTek)) {
                        $namaTekArray[] = $rowGetTek['nama'];
                        $idTek = $rowGetTek['id_sales'];
                    }

                    $sqlAll = "SELECT * FROM sales";
                    $resultAll = mysqli_query($conn, $sqlAll);
                    while ($rowAll = mysqli_fetch_array($resultAll)) {
                        $teknisiName = $rowAll['nama'];
                        $teknisiId = $rowAll['id_sales'];
                        $checked = '';
                        if (in_array($teknisiName, $namaTekArray)) {
                            $checked = 'checked';
                        }
                        echo "<input type='checkbox' class='ms-3' name='teknisi[]' value='$teknisiName' $checked> $teknisiName<br>";
                    }




                    ?>
                </h6>
            </div>


            <div class="col-12 col-md-12 mb-2 mb-md-0 mt-4 d-flex text-start pt-1">
                <input type="hidden" name="kode_transaksi" value="<?php echo $kodeTransaksi; ?>"></input>
                <button class="btn btn-outline-primary py-2 px-4">Submit</button>
            </div>

        </form>
    </div>
</div>




<?php

include "get_data_teknisi.php";
?>