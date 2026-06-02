<?php

// Query untuk mengambil data kegiatan berdasarkan ID kegiatan
$sql = "SELECT k.*, k.id AS id_kegiatan, p.*, t.nama_teknisi, c.nama AS nama_customer, c.telp AS cust_nomor
            FROM kegiatan k
            LEFT JOIN team_kegiatan t ON k.kode = t.kode
            LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
            LEFT JOIN customer c ON k.customer_id = c.id
            WHERE k.kode = '$kode_transaksi'
            ORDER BY k.id DESC 
            LIMIT 1";

$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);
$kodeTransaksi = $data['kode'];
$id_kegiatan = $data['id_kegiatan'];

?>

<div class="list-group-item border-0 d-flex justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
    <div class="row px-4">
        <form method="POST" action="apv_data_kegiatan.php">
            <?php
            $status = $data['status'];
            include "include/status.php";
            $updatedStatus = $status_terubah;
            ?>

            <div class="col-12 col-md-12 mb-2 mb-md-3 d-flex flex-column">
                <h6 class="mb-1 text-dark font-weight-bold text-s" style="text-transform:capitalize;"><?php echo $data['kegiatan']; ?> <span class="font-weight-normal text-sm">Request by <?php echo $data['request']; ?></span></h6>
                <?php echo $kode_transaksi;?>
            </div>

            <?php

            $datetime = $data["jadwal"];

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
                <h6 class="mb-0">Kegiatan</h6>
                <select class="form-select mt-2 p-2" name="kegiatan_pilihan">
                    <option value="survey" <?php echo ($data['kegiatan'] == 'survey') ? 'selected' : ''; ?>>Survey</option>
                    <option value="pasang baru" <?php echo ($data['kegiatan'] == 'pasang baru') ? 'selected' : ''; ?>>Pasang Baru</option>
                    <option value="service" <?php echo ($data['kegiatan'] == 'service') ? 'selected' : ''; ?>>Service</option>
                </select>
            </div>

            <div class="col-12 col-md-12 mb-4 mb-md-0 mt-4 text-start">
                <h6 class="mb-0">Customer</h6>
                <h6 class="ms-3 text-dark mb-n2 font-weight-bold text-sm"><?php echo $data['nama_customer']; ?></h6>
                <span class="ms-3 text-xs"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" class="text-info" target="_blank"><?php echo $data['cust_nomor']; ?></a></span>
            </div>

            <div class="col-12 col-md-12 mb-2 mt-2 mb-md-0 text-start d-flex flex-column">
                <h6 class="mb-0">Teknisi</h6>
                <h6 class="mb-1 text-dark font-weight-bold text-sm">
                    <?php
                    $sqlGetTek = "SELECT k.*, t.nama_teknisi, t.teknisi_id FROM kegiatan k
                                    JOIN team_kegiatan t ON k.kode = t.kode
                                    WHERE k.kode = '$kode_transaksi' AND t.kegiatan_id = '$id_kegiatan'";
                    $queryGetTek = mysqli_query($conn, $sqlGetTek);
                    $namaTekArray = [];
                    $statusArray = [];
                    while ($rowGetTek = mysqli_fetch_assoc($queryGetTek)) {
                        $namaTekArray[] = $rowGetTek['nama_teknisi'];
                        $statusArray[] = $rowGetTek['status'];
                        $idTek = $rowGetTek['teknisi_id'];
                    }

                    $sqlAll = "SELECT * FROM teknisi";
                    $resultAll = mysqli_query($conn, $sqlAll);
                    while ($rowAll = mysqli_fetch_array($resultAll)) {
                        $teknisiName = $rowAll['nama'];
                        $teknisiId = $rowAll['id'];
                        $checked = '';
                        $disabled = '';
                        if (in_array($teknisiName, $namaTekArray)) {
                            $checked = 'checked';
                        }
                        echo "<input type='checkbox' class='ms-3' name='teknisi[]' value='$teknisiName' $checked $disabled> $teknisiName<br>";
                    }

                    ?>
                </h6>
            </div>


            <div class="col-12 col-md-12 mb-2 mb-md-0 mt-4 d-flex text-start pt-1">
                <input type="hidden" name="kode_transaksi" value="<?php echo $kode_transaksi; ?>"></input>
                <button class="btn btn-outline-primary py-2 px-4">Submit</button>
            </div>

        </form>
    </div>
</div>




<?php

include "get_data_teknisi.php";
?>