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
    // Read from the actual table
    const table = document.querySelector('#data-tek');
    if (!table) return;
    const dataRows = table.querySelectorAll('tbody tr[data-survey]');
    
    // Header row with all columns including survey info
    let data = [
        ["No", "Tanggal Invoice", "No Invoice", "Teknisi", "Customer", "Ket. Survey", "Surveyor", "Nominal Invoice"]
    ];

    let totalNominal = 0;
    let rowIndex = 0;

    dataRows.forEach(function(row) {
        // Skip hidden rows (filtered out)
        if (row.classList.contains('hidden-row')) return;
        
        rowIndex++;
        const cells = row.querySelectorAll('td');
        if (cells.length < 8) return;

        // Extract text content from each cell
        const tglInvoice = cells[1].textContent.trim();
        const noInvoice = cells[2].textContent.trim();
        const teknisi = cells[3].textContent.trim().replace(/\n/g, ', ');
        const customer = cells[4].textContent.trim();
        const ketSurvey = cells[5].textContent.trim();
        const surveyor = cells[6].textContent.trim();
        
        // Get nominal from data attribute for accuracy
        const nominal = parseInt(row.getAttribute('data-nominal')) || 0;
        totalNominal += nominal;

        data.push([
            rowIndex,
            tglInvoice,
            noInvoice,
            teknisi,
            customer,
            ketSurvey === '-' ? '' : ketSurvey,
            surveyor === '-' ? '' : surveyor,
            nominal
        ]);
    });

    // Total row
    data.push(["", "", "", "", "", "", "TOTAL", totalNominal]);

    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(data);
    
    // Format nominal column as number
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = 1; R <= range.e.r; R++) {
        const cell = ws[XLSX.utils.encode_cell({r: R, c: 7})];
        if (cell && typeof cell.v === 'number') {
            cell.t = 'n';
            cell.z = '#,##0';
        }
    }
    
    // Set column widths
    ws['!cols'] = [
        {wch: 5}, {wch: 16}, {wch: 18}, {wch: 22}, {wch: 28}, 
        {wch: 28}, {wch: 18}, {wch: 18}
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Laporan Pendapatan Teknisi");

    const currentDate = '<?php echo $current_date; ?>';
    const [tahun, bulan] = currentDate.split('-');
    XLSX.writeFile(wb, `Laporan_Pendapatan_Teknisi_${bulan}_${tahun}.xlsx`);
});
</script>
  
</body>

</html>