<?php
include "../../conn.php";
include "session.php";
// Ambil nilai NIK dari sesi
$nikSesi = $_SESSION["nik"];
$querySesi = "SELECT * FROM data_warga WHERE nik = '$nikSesi'";
$resultSesi = mysqli_query($conn, $querySesi);
$rowSesi = mysqli_fetch_assoc($resultSesi);
$id_warga = $rowSesi['id_warga'];
$nama = $rowSesi['nama'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<?php
include "head.php";
?>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .slc-rek{
            border:1px solid #ddd;
            padding: 5px 10px 5px 10px;

        }
    </style>
</head>

<body>
    <?php
    $idTagihanArr = explode(',', $_GET['id_tagihan']);
    $queryTagihan = "SELECT * FROM tagihan WHERE id_tagihan IN (" . implode(',', array_fill(0, count($idTagihanArr), '?')) . ")";
    $stmtTagihan = mysqli_prepare($conn, $queryTagihan);
    $typesTagihan = str_repeat('i', count($idTagihanArr));
    mysqli_stmt_bind_param($stmtTagihan, $typesTagihan, ...$idTagihanArr);
    mysqli_stmt_execute($stmtTagihan);

    $resultTagihan = mysqli_stmt_get_result($stmtTagihan);

    $totalJumlahTagihan = 0;

    if ($resultTagihan) {
        echo '<div class="container mt-5">';
        echo '<div class="row">';
        echo '<div class="col-md-6 offset-md-3">';
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h4 class="mb-0">Proses Pembayaran</h4>';
        echo '</div>';
        echo '<div class="card-body">';

        echo '<form action="bayar.php" method="post" enctype="multipart/form-data" style="margin-top:-20px;">'; // Tambahkan form element
        echo '<input type="hidden" name="id_warga" value="' . $id_warga . '">';
        echo '<input type="hidden" name="id_tagihan" value="' . implode(',', $idTagihanArr) . '">'; // Mengubah array menjadi string

        while ($rowTagihan = mysqli_fetch_assoc($resultTagihan)) {
            $tglTagihanFormatted = date("d-m-Y", strtotime($rowTagihan['tgl_tagihan']));
            $jumlahTagihanFormatted = "Rp " . number_format($rowTagihan['jumlah'], 2, ',', '.');
            $namaTagihan = $rowTagihan['nama_tagihan'];

            echo "<span><h6>$namaTagihan</h6></span>";
            echo "<span style='float:right; margin-top:-35px; font-size:12px;'><strong> $jumlahTagihanFormatted</strong></span>";

            // Tambahkan jumlah tagihan ke total
            $totalJumlahTagihan += $rowTagihan['jumlah'];
        }

        // Format total jumlah tagihan
        $totalJumlahTagihanFormatted = "Rp " . number_format($totalJumlahTagihan, 2, ',', '.');
        echo "<div class='col-12 col-md-12 p-2 mx-1' style='border-top:1px solid #ccc;'></div>";
        echo '<h6>Total</h6>';
        echo "<p style='float:right; margin-top:-35px; font-size:14px;'><strong>{$totalJumlahTagihanFormatted}</strong></p>";

        // Tampilkan opsi metode pembayaran dari tabel bank_account
        $queryBankAccount = "SELECT * FROM bank_account WHERE nomor_rekening != 'NULL'";
        $resultBankAccount = mysqli_query($conn, $queryBankAccount);

        if ($resultBankAccount) {
            echo "<h6 class='mt-4'>Pilih Metode Pembayaran :</h6>";
            echo '<div class="d-flex align-items-center">';
            echo '<select class="form-select border p-2" id="selected_bank" name="selected_bank" onchange="displaySelectedRekening()">';
            while ($rowBankAccount = mysqli_fetch_assoc($resultBankAccount)) {
                echo "<option value='{$rowBankAccount['id_bank']}' data-rekening='{$rowBankAccount['nomor_rekening']}'>{$rowBankAccount['nama_bank']} - {$rowBankAccount['nomor_rekening']}</option>";
            }
            echo '</select>';
            echo "</div>";
            echo "<p class='text-xs mt-2' id='bankRekeningInfo'><i>***Silahkan pilih metode pembayaran dan transfer ke nomor rekening tujuan yang tertera, lampirkan bukti transfer berupa photo atau screenshoot.</i></p>";
            echo "<div id='selectedRekening' class='mt-3 me-3'></div>";  // Div untuk menampilkan nomor rekening yang dipilih
            echo "<button class='btn btn-outline-primary btn-sm mt-0 mb-0' data-bs-toggle='tooltip' data-bs-placement='top' title='Copy Nomor Rekening' onclick='copyRekening()'>Copy Nomor Rekening</button>";
        } else {
            echo "Error: " . mysqli_error($conn);
            exit();
        }

        echo "<h6 class='mt-4'>Upload Bukti Pembayaran:</h6>";
        echo '<input type="file" class="form-control border p-2" name="bukti_pembayaran" required accept="image/*"><br>';

        echo '<button type="submit" class="btn btn-primary mt-5 col-12 col-md-12">Bayar</button>';
        echo '</form>'; // Menutup form element

        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo "Error: " . mysqli_error($conn);
        exit();
    }
    ?>

    <!-- Include Bootstrap JS (jQuery and Popper.js required) -->
    <script src="path/to/jquery.min.js"></script>
    <script src="path/to/popper.min.js"></script>
    <script src="path/to/bootstrap/js/bootstrap.min.js"></script>
    <script>
        function displaySelectedRekening() {
            var selectedBank = document.getElementById('selected_bank');
            var selectedOption = selectedBank.options[selectedBank.selectedIndex];
            var nomorRekening = selectedOption.getAttribute('data-rekening');
            var selectedRekening = document.getElementById('selectedRekening');

            // Tampilkan nomor rekening yang dipilih di div selectedRekening
            selectedRekening.innerHTML = `<span class='slc-rek'><strong>${nomorRekening}</strong></span>`;
            selectedRekening.style.display = 'inline';
        }

        function copyRekening() {
            var selectedBank = document.getElementById('selected_bank');
            var selectedOption = selectedBank.options[selectedBank.selectedIndex];
            var nomorRekening = selectedOption.getAttribute('data-rekening');

            // Salin nomor rekening ke clipboard
            navigator.clipboard.writeText(nomorRekening)
                .then(function() {
                    // Tampilkan tooltip atau pesan sukses (opsional)
                    var tooltipContainer = document.getElementById('bankRekeningInfo');
                    tooltipContainer.innerHTML = '<span class="text-success">Nomor rekening berhasil disalin!</span>';
                    setTimeout(function() {
                        tooltipContainer.innerHTML = '<i>***Silahkan pilih metode pembayaran dan transfer ke nomor rekening tujuan yang tertera, lampirkan bukti transfer berupa photo atau screenshoot.</i>';
                    }, 3000); // Setelah 3 detik, kembalikan pesan aslinya

                    // Fokuskan kembali ke elemen select setelah menyalin
                    selectedBank.focus();
                })

                .catch(function(err) {
                    console.error('Gagal menyalin ke clipboard:', err);
                });
        }
    </script>
</body>

</html>