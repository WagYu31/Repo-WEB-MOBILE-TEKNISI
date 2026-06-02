<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Pendapatan";
$currentPage = "Today";
$role = $jabatan;
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
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }
        #toggleLoadMore {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
    @media print {
    .no-print {
        display: none;
    }
}

  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <div class="row mb-4 mt-0">
        <div class="col-md-4 col-12 d-flex justify-content-start align-items-center">
            <a href="laporan.php" class="btn bg-gradient-dark w-30 me-2">Bulanan</a>
            <button class="btn bg-gradient-info w-30 btn-print me-2">Print</button>
            <button id="download-btn" class="btn btn-success no-print w-40 mb-3">Download Excel</button>
        </div>

          <div class="col-8">
              
          </div>
        
        <?php
        include "detail-laporan-db.php";
        ?>

      </div>
      <?php
      include "footer.php";
      ?>
    </div>


  </main>
  <?php
  include "js-include.php";
  ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  
  <script>
    // Fungsi untuk mencetak konten
    function printContent() {
        var content = document.getElementById("printable-content").innerHTML;
        var originalBody = document.body.innerHTML;
        document.body.innerHTML = content;
        window.print();
        document.body.innerHTML = originalBody;
    }

    // Menambahkan event listener untuk tombol "Print"
    document.querySelector(".btn-print").addEventListener("click", printContent);
</script>
<script>
    $(document).ready(function(){
        // Saat pengguna mengetik di input search
        $('#search-input').on('keyup', function(){
            var query = $(this).val(); // Ambil nilai input

            // Kirim permintaan AJAX
            $.ajax({
                url: 'search.php', // Ganti dengan file PHP yang menangani pencarian
                method: 'POST',
                data: {query: query, cariBulanTahun: "<?php echo $current_date; ?>"}, // Kirim query dan current_date
                success: function(data){
                    $('#data-tek').html(data); // Tampilkan hasil pencarian di list
                }
            });
        });
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <script>
document.getElementById('download-btn').addEventListener('click', function () {
    // Ambil elemen list dari HTML
    const listGroupItems = document.querySelectorAll('#data-tek li.list-group-item');

    // Inisialisasi array untuk menyimpan data
    let data = [
        ["Tanggal Invoice", "No Invoice", "Teknisi", "Customer", "Nominal Invoice"] // Header Kolom
    ];

    let totalNominalInvoice = 0; // Inisialisasi total nominal

    // Iterasi melalui setiap list item untuk mengambil data
    listGroupItems.forEach((item, index) => {
        if (index === 0 || index === listGroupItems.length - 1) return; // Skip header dan total
        
        const row = [];

        // Ambil elemen yang sesuai dari setiap baris
        const tanggalInvoice = item.querySelector('.col-6:nth-child(2)').innerText.trim();
        const noInvoice = item.querySelector('.col-6:nth-child(4)').innerText.trim();
        
        // Mengambil nama teknisi sebagai teks dan menggabungkannya dengan koma
        let teknisi = item.querySelector('.col-6:nth-child(6)').innerText.trim();
        teknisi = teknisi.split('\n').join(', '); // Pisahkan dengan koma jika ada baris baru

        const customer = item.querySelector('.col-6:nth-child(8)').innerText.trim();
        let nominalInvoice = item.querySelector('.col-6:nth-child(10)').innerText.trim();

        // Ubah nominal menjadi hanya angka saja
        nominalInvoice = nominalInvoice.replace(/[^\d]/g, ''); // Hanya ambil angka
        
        // Ubah nominalInvoice menjadi number
        const nominalNumber = parseInt(nominalInvoice, 10);

        // Tambahkan nominal ke total
        if (!isNaN(nominalNumber)) {
            totalNominalInvoice += nominalNumber;
        }

        // Tambahkan data ke array row
        row.push(tanggalInvoice, noInvoice, teknisi, customer, nominalNumber.toLocaleString('id-ID'));

        // Tambahkan row ke data
        data.push(row);
    });

    // Tambahkan baris untuk total nominal invoice di bagian bawah
    data.push(["", "", "", "TOTAL", totalNominalInvoice.toLocaleString('id-ID')]);

    // Konversi data menjadi worksheet menggunakan SheetJS
    const ws = XLSX.utils.aoa_to_sheet(data);

    // Buat workbook baru
    const wb = XLSX.utils.book_new();

    // Tambahkan worksheet ke workbook
    XLSX.utils.book_append_sheet(wb, ws, "Laporan Pendapatan Teknisi");

    // Ambil nilai current_date dari PHP
    const currentDate = '<?php echo $current_date; ?>'; // Menggunakan PHP untuk menyuntikkan nilai ke JavaScript

    // Pisahkan bulan dan tahun dari currentDate
    const [tahun, bulan] = currentDate.split('-');

    // Simpan workbook ke file Excel dengan format nama sesuai bulan dan tahun dari current_date
    XLSX.writeFile(wb, `Laporan_Pendapatan_Teknisi_${bulan}_${tahun}.xlsx`);
});

</script>
  
</body>

</html>