<?php
include "../../conn.php";

if (isset($_POST['bulanTahun'])) {

    // Dapatkan nilai bulan dan tahun yang dipilih dari opsi bulan dan tahun
    $bulanTahun = $_POST['bulanTahun'];
    $bulanTahunExploded = explode("-", $bulanTahun);
    $bulan = $bulanTahunExploded[0];
    $tahun = $bulanTahunExploded[1];

    $query = "SELECT 
        sedekah.id_warga, 
        NULL AS id_tagihan, 
        sedekah.tgl_sedekah AS tgl_bayar, 
        sedekah.kode_pembayaran, 
        sedekah.jumlah, 
        sedekah.status,
        'Sedekah' AS nama_tagihan,
        data_warga.nik,
        data_warga.nama,
        data_warga.no_kk
    FROM 
        sedekah
    JOIN 
        data_warga ON sedekah.id_warga = data_warga.id_warga
    WHERE 
        sedekah.status = 'Verified' AND
        MONTH(sedekah.tgl_sedekah) = $bulan AND
        YEAR(sedekah.tgl_sedekah) = $tahun
    ORDER BY 
        tgl_bayar DESC";

    // Eksekusi query
    $result = mysqli_query($conn, $query);
    $no = 1;
    // Buat tampilan data
    if (mysqli_num_rows($result) == 0) {
        echo "<span class='mt-n2 text-sm'>Tidak ada riwayat pembayaran baru</span>";
    } else {
?>
        <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
            <div class="row">
                <div class="col-6 col-md-1 mb-1 mb-md-0">
                    <span class="text-sm text-uppercase font-weight-bold">No</span>
                </div>
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <span class="text-sm text-uppercase font-weight-bold">No KK</span>
                </div>

                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <span class="text-sm text-uppercase font-weight-bold">Nama</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <span class="text-sm text-uppercase font-weight-bold">Tagihan</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                    <span class="text-sm text-uppercase font-weight-bold">Jumlah Bayar</span>
                </div>

                <div class="col-6 col-md-2 mb-1 mb-md-0  text-start text-md-center">
                    <span class="text-sm text-uppercase font-weight-bold">Tanggal Bayar</span>
                </div>
            </div>
        </li>
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            $idTagihan = $row['id_tagihan'];
            $kodePembayaran = $row['kode_pembayaran'];
            $nik = $row['nik'];
            $namaWarga = $row['nama'];
            $noKK = $row['no_kk'];
            $tglBayar = formatTanggal('dd MMMM yyyy', $row['tgl_bayar']);

            $namaTagihan = $row['nama_tagihan'];
            $totalJumlah = $row['jumlah'];
            $statusPembayaran = $row['status'];
            if ($statusPembayaran == "Pending") {
                $statusPembayaran = "Menunggu Verifikasi";
            } elseif ($statusPembayaran == "Verified") {
                $statusPembayaran = "Berhasil";
            } elseif ($statusPembayaran == "Tolak") {
                $statusPembayaran = "Ditolak";
            } else {
                $statusPembayaran = "?";
            }
            // Buat tampilan data sesuai kebutuhan Anda
        ?>
            <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
                <div class="row">
                    <div class="col-6 col-md-1 mb-1 mb-md-0">
                        <span class="text-sm text-uppercase"><?php echo $no; ?></span>
                    </div>

                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <span class="text-sm text-uppercase"><?php echo $noKK; ?></span>
                    </div>

                    <div class="col-6 col-md-3 mb-2 mb-md-0">
                        <span class="text-sm text-capitalize"><?php echo $namaWarga; ?></span>
                    </div>

                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <span class="text-sm text-capitalize"><?php echo $namaTagihan; ?></span>
                    </div>

                    <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                        <span class="text-sm font-weight-bold">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></span>
                    </div>

                    <div class="col-6 col-md-2 mb-1 mb-md-0  text-start text-md-center">
                        <span class="text-sm"><?php echo $tglBayar; ?></span>
                    </div>
                </div>
            </li>
<?php
            $no++;
        }
    }
}
?>
