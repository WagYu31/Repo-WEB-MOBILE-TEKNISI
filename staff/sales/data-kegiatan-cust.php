<?php
include "../conn.php";
if (isset($_GET['customer_id'])) {
    $id_cust = $_GET['customer_id'];
} else {
    // Parameter customer_id tidak diberikan
    echo "Tidak Ada Kegiatan";
}
?>
<div class="card z-index-2">
    <div class="card-header col-9 col-md-auto p-0 position-relative mt-3 mx-3 z-index-2 bg-transparent">
        <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
            <div class="row px-4 d-flex flex-row justify-content-between align-items-center">
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <h6 class="mb-1 text-white font-weight-bold text-sm">Status</h6>
                </div>
                <div class="col-12 col-md-5 mb-2 mb-md-0">
                    <h6 class="mb-1 text-white font-weight-bold text-sm">Sales</h6>
                </div>
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <h6 class="mb-1 text-white font-weight-bold text-sm">Tanggal Visit</h6>
                    <span class="text-xs text-white">/ jam</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <ul class="list-group m-0 mt-0 col-12" id="data-kegiatan-cust">
            <?php
            $sqlKC = "SELECT c.*, v.id_cust, v.tgl_visits, v.status, v.id_sales, v.kode_transaksi
            FROM cust c
            JOIN visits v ON c.id_cust = v.id_cust
            WHERE c.id_cust = '$id_cust' AND v.status != 'N' 
            GROUP BY v.kode_transaksi 
            ORDER BY v.tgl_visits DESC";
            // LIMIT 15";
            $resultKC = mysqli_query($conn, $sqlKC);
            while ($rowkc = mysqli_fetch_assoc($resultKC)) {
                $tglRec = $rowkc['tgl_visits'];
                $tanggal_bulan_indonesia = formatTanggal('dd MMMM yyyy', $tglRec);
                $waktu = date('H:i', strtotime($tglRec));
                $status = $rowkc['status'];
                $status_terubah = '';
                switch ($status) {
                    case 'Pending':
                        $status_terubah = 'Dijadwalkan';
                        break;
                    case 'Pause':
                        $status_terubah = 'Lanjut Nanti';
                        break;
                    case 'Clear':
                        $status_terubah = 'Selesai';
                        break;
                    case 'Waiting':
                        $status_terubah = 'Menunggu Dijadwalkan';
                        break;
                    default:
                        // Jika status tidak dikenali, biarkan nilai tetap sama
                        $status_terubah = $status;
                        break;
                }

                $idSales = $rowkc['id_sales'];
                $kodeTran = $rowkc['kode_transaksi'];
            ?>
                <li class="list-group-item border-0  d-flex flex-row justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block">
                    <div class="row px-4">
                        <div class="col-12 col-md-3 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $status_terubah; ?></h6>
                        </div>
                        <div class="col-12 col-md-5 mb-2 mb-md-0">
                            <?php
                            $sqlGetTek = "SELECT v.*, s.nama, s.id_sales FROM visits v
                                        JOIN sales s ON v.id_sales = s.id_sales
                                        WHERE v.kode_transaksi = '$kodeTran' AND v.status != 'N'";
                            $queryGetTek = mysqli_query($conn, $sqlGetTek);
                            while ($rowGetTek = mysqli_fetch_assoc($queryGetTek)) {
                                $namaTek = ($rowGetTek['id_sales'] !== NULL) ? $rowGetTek['nama'] : "-";

                            ?>
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaTek; ?></h6>
                            <?php } ?>
                        </div>

                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $tanggal_bulan_indonesia; ?></h6>
                            <span class="text-xs"><?php echo $waktu; ?></span>
                        </div>
                    </div>
                </li>
            <?php
            }
            ?>
    </div>

</div>