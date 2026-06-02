<?php

// Query untuk mengambil data kegiatan berdasarkan ID kegiatan
$sql = "SELECT v.*, s.nama AS nama_sales, c.nama AS nama_customer, c.no_wa AS cust_nomor
FROM visits v
LEFT JOIN sales s ON v.id_sales = s.id_sales
LEFT JOIN cust c ON v.id_cust = c.id_cust
WHERE v.kode_transaksi = '$kode_transaksi'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
}
$row = mysqli_fetch_assoc($result);
$kodeTransaksi = $row['kode_transaksi'];

?>

<div class="list-group-item border-0 d-flex justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
    <div class="row px-4">
        <?php
        $updatedStatus = $row['status'];
        $warna = '';

        switch ($updatedStatus) {
            case 'dijadwalkan':
                $updatedStatus = 'Dijadwalkan';
                $warna = 'bg-gradient-warning';
                break;
            case 'on process':
                $updatedStatus = 'Diproses';
                $warna = 'bg-gradient-success';
                break;
            case 'clear':
                $updatedStatus = 'Selesai';
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
            $keterangan = $row['keterangan_visits'];
            
            $keterangan_with_links = preg_replace('/<a href="([^"]+)">([^<]+)<\/a>/', '<a href="$1" target="_blank">$2</a>', $keterangan);
            
            // Tampilkan teks dengan tautan yang dapat diklik
            echo '<span class="text-s text-dark">' . $keterangan_with_links . '</span>';
            ?>
        </div>

        <div class="col-12 col-md-12 mb-2 mt-2 mb-md-0 text-start d-flex flex-column">
            <h5 class="mb-0">Teknisi</h5>
            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                <?php
                $selTek = "SELECT visits.kode_transaksi, visits.status, visits.id_sales, sales.nama, sales.id_sales
                            FROM visits
                            JOIN sales ON visits.id_sales = sales.id_sales
                    WHERE visits.kode_transaksi = '$kodeTransaksi'";
                $resTek = mysqli_query($conn, $selTek);
                // Mengecek jika tidak ada teknisi yang ditugaskan
                if(mysqli_num_rows($resTek) == 0) {
                    echo "Belum Dijadwalkan";
                } else {
                    // Jika ada teknisi yang ditugaskan, menampilkan teknisi dan statusnya
                    while ($rowTeknis = mysqli_fetch_assoc($resTek)) {
                        $sts = $rowTeknis['status'];
                        $salesId = $rowTeknis['id_sales'];
                        $teknisiName = $rowTeknis['nama'];
                        $link = "view-kegiatan-sales.php?kode_transaksi=" . $kodeTransaksi . "&id_sales=" . $salesId;
                        if($sts == 'Clear') {
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
