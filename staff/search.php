<?php
include 'conn.php'; // Include koneksi ke database

if (isset($_POST['query'])) {
    $search_query = mysqli_real_escape_string($conn, $_POST['query']);
    $current_date = $_POST['cariBulanTahun'];

    // Query utama dengan GROUP_CONCAT untuk menampilkan beberapa teknisi dalam satu baris
            $sql = "SELECT pk.*, 
                       GROUP_CONCAT(DISTINCT CONCAT('<a href=\"detail-lap-tek.php?cariBulanTahun=$current_date&idTek=', t.id, '\">', t.nama, '</a>') SEPARATOR '<br>') AS nama_teknisi, 
                       k.customer_id, 
                       c.nama AS nama_cust
                FROM pendapatan_kegiatan pk
                JOIN kegiatan k ON k.kode = pk.kode
                JOIN customer c ON c.id = k.customer_id
                JOIN teknisi t ON t.id = pk.teknisi_id
                WHERE pk.deleted_at IS NULL
                AND DATE_FORMAT(pk.tanggal, '%Y-%m') = '$current_date'
                AND pk.pendapatan != 0
                AND (t.nama LIKE '%$search_query%' OR c.nama LIKE '%$search_query%' OR pk.no_invoice LIKE '%$search_query%')
                GROUP BY pk.kode
                ORDER BY pk.tanggal ASC";

    $result = mysqli_query($conn, $sql);

    // Awal dari layout list yang tetap ditampilkan
?>
<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-none">
    <div class="row px-4 w-md-100">
        <!-- Header tabel -->
        <div class="col-6 w-md-10 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-center">Tgl Invoice</h6>
        </div>
        <div class="col-6 w-md-20 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-1 text-start">No Invoice</h6>
        </div>
        <div class="col-6 w-md-30 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-2 text-start">Teknisi</h6>
        </div>
        <div class="col-6 w-md-25 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-2 text-start">Customer</h6>
        </div>
        <div class="col-6 w-md-15 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-end">Nominal Invoice</h6>
        </div>
    </div>
</li>
<?php

    // Cek apakah ada hasil dari query pencarian
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Mengambil informasi yang dibutuhkan dari hasil query
            $namaT = $row['nama_teknisi']; // Nama teknisi, bisa lebih dari satu
            $namaC = $row['nama_cust']; // Nama customer
            $invoice = $row['no_invoice'];
            $tglInv = date('d M Y', strtotime($row['tanggal']));
            $nominal = $row['nominal_invoice'];
            $totalBonusFormatted = "Rp " . number_format($nominal, 0, ',', '.');
?>
<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
    <div class="row px-4">
        <!-- Menampilkan data pencarian -->
        <div class="col-6 w-md-10 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-normal text-sm p-2 text-start text-md-center">
                <?php echo $tglInv; ?>
            </h6>
        </div>

        <div class="col-6 w-md-20 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-md-normal font-weight-bold text-sm p-2 text-start">
                <?php echo $invoice; ?>
            </h6>
        </div>

        <div class="col-6 w-md-30 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-md-normal font-weight-bold text-sm p-2">
                <!-- Menampilkan semua nama teknisi -->
                <?php echo $namaT; ?>
            </h6>
        </div>

        <div class="col-6 w-md-25 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-normal text-sm p-2">
                <?php echo $namaC; ?>
            </h6>
        </div>

        <div class="col-6 w-md-15 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-normal text-sm p-2 text-start text-md-center">
                <?php echo $totalBonusFormatted; ?>
            </h6>
        </div>
    </div>
</li>
<?php
        }
        // Menampilkan total pendapatan
?>
<li class="list-group-item border-0 d-flex flex-column justify-content-center align-items-center bg-info ps-0 mb-2 d-md-block d-block">
    <div class="row px-4">
        <div class="col-6 w-md-15 mb-md-0 d-md-block d-none">
            <h6 class="mb-1 text-white font-weight-bold text-sm p-2 text-center">TOTAL</h6>
        </div>
        <div class="col-6 w-md-70 mb-md-0 d-none d-md-flex"></div>
        <div class="col-6 w-md-15 mb-md-0">
            <h6 class="mb-1 text-white font-weight-bold text-sm p-2 text-start text-md-center">
                <?php
                    echo "Rp " . number_format($totalBonusAll, 0, ',', '.');
                ?>
            </h6>
        </div>
    </div>
</li>
<?php
    } else {
        // Jika tidak ada hasil
        echo "<li class='list-group-item'>Tidak ada hasil ditemukan</li>";
    }
}
?>
