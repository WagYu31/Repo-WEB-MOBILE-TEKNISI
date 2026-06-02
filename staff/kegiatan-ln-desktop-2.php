<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
    <div class="row px-4">

        <div class="col-6 col-md-2 mb-2 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                <?php
                // echo $no;
                $status = $row['status'];
                switch ($status) {
                    case 'waiting':
                        echo 'Dalam Antrian';
                        break;
                    case 'dijadwalkan':
                        echo 'Dijadwalkan';
                        break;
                    case 'berjalan':
                        echo 'Dalam Proses';
                        break;
                    case 'selesai':
                        echo 'Selesai';
                        break;
                    case 'selesai by admin':
                        echo 'Diselesaikan oleh Admin';
                        break;
                    case 'Lanjut Nanti':
                        echo 'Berlanjut';
                        break;
                    case 'Lanjutan':
                        echo 'Dilanjutkan';
                        break;
                    default:
                        echo $status; // Jika status tidak sesuai dengan kondisi di atas, biarkan nilainya tetap
                }
                ?>
            </h6>
            <!--<span class="text-xs"><?php echo $row['kegiatan']; ?></span><br>-->
                <span class="text-xs font-weight-bold"><?php echo $kodeTransaksi; ?></span>
        </div>

        <?php


        $datetime = $row["jadwal"];
        $idKgt = $row["id"];

        // Format dan tampilkan tanggal dan waktu
        $formattedTime = date("H:i", strtotime($datetime));
        $formattedDate = date("d-m-Y", strtotime($datetime));
        $tanggal_sekarang = date("d-m-Y");
        ?>
        <div class="col-6 col-md-2 mb-2 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
            <span class="text-xs text-uppercase"><?php echo $formattedTime; ?></span>
        </div>

        <?php


        $nomorHandphone = $row['cust_nomor'][0];

        // Cek apakah nomor handphone dimulai dengan angka 0
        if (substr($nomorHandphone, 0, 1) === '0') {
            // Ganti angka 0 dengan 62
            $nomorHandphone = '62' . substr($nomorHandphone, 1);
        }

        ?>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
            <a href="customer-detail.php?id_cust=<?php echo $row['customer_id']; ?>">
                <h6 class="text-dark font-weight-bold text-sm"><?php echo $row['nama_customer']; ?>
                <?php 
                // echo $idKgt; 
                ?>
                </h6>
            </a>
            <span class="text-xs text-uppercase"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $row['cust_nomor']; ?></a></span>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center  justify-content-md-center justify-content-left align-items-center d-flex">
            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                <?php
                $selTek = "
                    SELECT tk.teknisi_id, tk.nama_teknisi
                    FROM team_kegiatan tk
                    JOIN kegiatan k ON tk.kegiatan_id = k.id
                    WHERE k.kode = '$kodeTransaksi'
                    AND k.id = (
                        SELECT MAX(id) 
                        FROM kegiatan 
                        WHERE kode = '$kodeTransaksi'
                    )
                    GROUP BY tk.teknisi_id";

                $resTek = mysqli_query($conn, $selTek);

                while ($rowTeknis = mysqli_fetch_assoc($resTek)) {
                    echo "<a href='list-kegiatan-teknisi.php?idTek=" . $rowTeknis['teknisi_id'] . "'>" . $rowTeknis['nama_teknisi'] . "</a><br>";
                }
                ?>

            </h6>
        </div>


        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center justify-content-md-center justify-content-left align-items-center d-flex">
            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $row['request']; ?></h6>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0 d-flex justify-content-center align-items-center pt-1">
            <!-- <button class="btn btn-info view-btn w-25 p-2 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">visibility</i></button> -->
            <a class="btn btn-info view-btn w-25 p-2 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">visibility</i></a>
            <button class="btn btn-warning edit-btn w-25 p-2 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10">autorenew</i></button>
            <a class="btn btn-success selesaikan-btn w-25 p-2 text-center" href="selesaikan-kegiatan.php?kode=<?php echo $kodeTransaksi; ?>&id=<?php echo $idKgt; ?>"><i class="material-icons opacity-10">check</i></a>
        </div>


    </div>
</li>