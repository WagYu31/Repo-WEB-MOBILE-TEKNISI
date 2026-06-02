<?php

// Query untuk mengambil data kegiatan berdasarkan ID kegiatan
$sql = "SELECT k.*, t.nama_teknisi, c.nama AS nama_customer, c.telp AS cust_nomor
FROM kegiatan k
LEFT JOIN team_kegiatan t ON k.kode = t.kode
LEFT JOIN customer c ON k.customer_id = c.id
WHERE k.kode = '$kode_transaksi'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
}
$row = mysqli_fetch_assoc($result);
$kodeTransaksi = $row['kode'];

?>

<div class="list-group-item border-0 d-flex justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
    <div class="row px-4">
        <?php
        $updatedStatus = $row['status'];
        $warna = '';

        switch ($updatedStatus) {
            case 'waiting':
                $updatedStatus = 'Dalam Antrian';
                $warna = 'bg-gradient-warning';
                break;
            case 'dijadwalkan':
                $updatedStatus = 'Dijadwalkan';
                $warna = 'bg-gradient-warning';
                break;
            case 'berjalan':
                $updatedStatus = 'Dalam Proses';
                $warna = 'bg-gradient-success';
                break;
            case 'selesai':
                $updatedStatus = 'Selesai';
                $warna = 'yellow';
                break;
            case 'selesai by admin':
                $updatedStatus = 'Diselesaikan oleh Admin';
                $warna = 'yellow';
                break;
            case 'Lanjut Nanti':
                $updatedStatus = 'Berlanjut';
                $warna = 'bg-gradient-info';
                break;
            case 'Lanjutan':
                $updatedStatus = 'Dilanjutkan';
                $warna = 'bg-gradient-info';
                break;
            default:
                $updatedStatus = $row['status'];
        }


        if (empty($updatedStatus)) {
            $updatedStatus = 'Selesai';
            $warna = 'green';
        }

        ?>

        <div class="col-12 col-md-12 mb-2 mb-md-0 d-flex flex-column">
            <h6 class="mb-1 text-dark font-weight-bold text-s"><?php echo $row['kegiatan']; ?> <br><span class="font-weight-normal text-sm">Request by <?php echo $row['request']; ?></span></h6>
        </div>

        <?php

        $nomorHandphone = $row['cust_nomor'];

        if (substr($nomorHandphone, 0, 1) === '0') {
            $nomorHandphone = '62' . substr($nomorHandphone, 1);
        }

        ?>

        <div class="col-12 col-md-12 mb-4 mb-md-2 mt-2 text-start">
            <h5 class="text-dark mb-n1 font-weight-bold"><?php echo $row['nama_customer']; ?></h5>
            <span class="text-s"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" class="text-info" target="_blank"><?php echo $row['cust_nomor']; ?></a></span><br>
            <h6 class="text-s text-dark text-uppercase mt-1 mb-n1">Keterangan :</h6>
            <?php
            // Teks dan link dalam keterangan
            $keterangan = $row['keterangan'];

            $keterangan_with_links = preg_replace('/<a href="([^"]+)">([^<]+)<\/a>/', '<a href="$1" target="_blank">$2</a>', $keterangan);

            // Tampilkan teks dengan tautan yang dapat diklik
            echo '<span class="text-s text-dark">' . $keterangan_with_links . '</span>';
            ?>
        </div>

        <div class="col-12 col-md-12 mb-2 mt-2 mb-md-0 text-start d-flex flex-column">
            <h5 class="mb-0">Teknisi</h5>
            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                <?php
                $selTek = "SELECT kegiatan.kode, kegiatan.status, team_kegiatan.nama_teknisi, team_kegiatan.teknisi_id
                            FROM kegiatan
                            JOIN team_kegiatan ON kegiatan.kode = team_kegiatan.kode
                    WHERE kegiatan.kode = '$kodeTransaksi'
                    GROUP BY team_kegiatan.nama_teknisi";
                $resTek = mysqli_query($conn, $selTek);
                // Mengecek jika tidak ada teknisi yang ditugaskan
                if (mysqli_num_rows($resTek) == 0) {
                    echo "Dalam Antrian";
                } else {
                    // Jika ada teknisi yang ditugaskan, menampilkan teknisi dan statusnya
                    while ($rowTeknis = mysqli_fetch_assoc($resTek)) {
                        $sts = $rowTeknis['status'];
                        $teknisiId = $rowTeknis['teknisi_id'];
                        $teknisiName = $rowTeknis['nama_teknisi'];
                        $link = "view-kegiatan.php?kode_transaksi=" . $kodeTransaksi . "&teknisi_id=" . $teknisiId;
                        if ($sts == 'selesai') {
                            echo "<a href='$link' class='btn bg-gradient-info text-white px-3 py-2 mt-1 mb-0'>$teknisiName</a><br>";
                        } else {
                            echo "<a href='$link' class='btn bg-gradient-dark text-white px-3 py-2 mt-1 mb-0'>$teknisiName</a><br>";
                        }
                    }
                }
                ?>
            </h6>
        </div>

    </div>
</div>

<?php
include "get_data_teknisi.php";
