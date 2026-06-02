<?php
include "conn.php";
// Ambil ID kegiatan dari parameter URL
$id_kegiatan = $_GET['id_kegiatan'];

require 'vendor/autoload.php';

// Query untuk mengambil data kegiatan berdasarkan ID
$query = "SELECT * FROM kegiatan WHERE id_kegiatan = $id_kegiatan";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $data_kegiatan = mysqli_fetch_assoc($result);
    // Tampilkan data kegiatan sesuai kebutuhan
    $kode = $data_kegiatan["kode_transaksi"];
    $cust = $data_kegiatan["id_cust"];
    $jenis = $data_kegiatan["jenis"];
    $ket = $data_kegiatan["keterangan"];
    // ...
} else {
    echo "Data kegiatan tidak ditemukan.";
}

$customer = "SELECT * FROM customer WHERE id_cust = $cust";
$custResult = mysqli_query($conn, $customer);

if ($custResult && mysqli_num_rows($custResult) > 0) {
    $data_cust = mysqli_fetch_assoc($custResult);

    $nama = $data_cust["nama"];
    $tlp = $data_cust["nomor_tlp"];
    $alamat = $data_cust["alamat"];
} else {
    echo "Data tidak ditemukan.";
}
                    $link = "https://teknisi.loewix.com/tracking.php?kode_transaksi=$kode";
                    $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
                    $barcode = $generator->getBarcode($kode, $generator::TYPE_CODE_128, 2, 50);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <?php
        include "dep-css.php";
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Tautan stylesheet Bootstrap terbaru (versi 5) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/invoice-style.css?rev=<?php echo time(); ?>">

</head>
<body>
    
    <div class="container-fluid">
    <?php
        include "btm-nav.php";
    ?>
    <div class="actions no-print">
        <a href="guest-mode.php" class="btn btn-secondary">Kembali</a>
        <a href="javascript:void(0);" onclick="printInvoice()" class="btn btn-primary">Cetak Invoice</a>
    </div>
    <p class="warn no-print">*Simpan bukti permohonan ini atau catat kode unik Anda : <b><?php echo $kode;?></b></p>
    <div class="container invoice-container">
        <div class="row">
            <div class="col-md-6">
                <img src="img/loewix.png" style="width:200px;" alt="Loewix Logo">
                <p class="br"><?php echo $barcode; ?></p>
                <h4>Kode Unik : <?php echo $kode; ?></h4>
            </div>
            <div class="col-md-6 text-right kepada">
                <h4>Kepada :</h4>
                <p><?php echo $nama; ?></p>
                <p>Nomor Telepon : <?php echo $tlp; ?></p>
                <p>Alamat : <?php echo $alamat; ?></p>
                <p>Tanggal : <?php echo date('d-M-Y'); ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Keterangan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $jenis; ?></td>
                            <td><?php echo $ket; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <p>Terima kasih telah memilih layanan kami.</p>
                <p class="english-text">Thank you for choosing our services.</p>
            </div>
            <div class="col-md-6 text-right">
                <p>Simpan invoice ini untuk memeriksa status permintaan Anda.</p>
                <p class="english-text">Keep this invoice for checking the status of your request.</p>
            </div>
        </div>
    </div>
    </div>
    
    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    
    <!-- Tambahkan ini ke dalam tag <head> pada halaman HTML Anda -->
    <script>
        function printInvoice() {
            // Sembunyikan elemen-elemen yang tidak ingin dicetak
            document.querySelector('.no-print').style.display = 'none';
            // Cetak halaman
            window.print();
            // Kembalikan tampilan elemen-elemen yang disembunyikan
            document.querySelector('.no-print').style.display = 'block';
        }
    </script>

</body>
</html>