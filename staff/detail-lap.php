<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Pendapatan";
$currentPage = "Today";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    @media print {
        .no-print { display: none !important; }
        .sidenav, .navbar, .fixed-plugin { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; }
    }

    /* Action bar */
    .action-bar {
        display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
        margin-bottom: 24px;
    }
    .action-btn {
        padding: 10px 20px; border: none; border-radius: 10px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: all 0.2s;
        letter-spacing: 0.02em;
    }
    .action-btn:hover { transform: translateY(-1px); }
    .btn-bulanan { background: #1e293b; color: #fff; box-shadow: 0 4px 12px rgba(30,41,59,0.2); }
    .btn-bulanan:hover { background: #334155; color: #fff; }
    .btn-print-action { background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,0.25); }
    .btn-print-action:hover { color: #fff; box-shadow: 0 6px 16px rgba(6,182,212,0.35); }
    .btn-excel { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 4px 12px rgba(34,197,94,0.25); }
    .btn-excel:hover { color: #fff; box-shadow: 0 6px 16px rgba(34,197,94,0.35); }
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php
    include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>
    <div class="container-fluid py-4">
      <div class="action-bar no-print">
          <a href="laporan.php" class="action-btn btn-bulanan">
              <i class="fa-solid fa-chart-bar"></i> Bulanan
          </a>
          <button class="action-btn btn-print-action btn-print">
              <i class="fa-solid fa-print"></i> Print
          </button>
          <button id="download-btn" class="action-btn btn-excel">
              <i class="fa-solid fa-file-excel"></i> Download Excel
          </button>
      </div>

      <div class="row mb-4 mt-0">
        <?php include "detail-laporan-db.php"; ?>
      </div>
      <?php include "footer.php"; ?>
    </div>
  </main>
  <?php include "js-include.php"; ?>
  <script>
    // Print
    document.querySelector(".btn-print").addEventListener("click", function() {
        var content = document.getElementById("printable-content").innerHTML;
        var originalBody = document.body.innerHTML;
        document.body.innerHTML = content;
        window.print();
        document.body.innerHTML = originalBody;
    });
  </script>
  <script>
    $(document).ready(function(){
        $('#search-input').on('keyup', function(){
            var query = $(this).val();
            $.ajax({
                url: 'search.php',
                method: 'POST',
                data: {query: query, cariBulanTahun: "<?php echo $current_date; ?>"},
                success: function(data){
                    $('#data-tek').html(data);
                }
            });
        });
    });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
  document.getElementById('download-btn').addEventListener('click', function () {
    const table = document.querySelector('#data-tek');
    if (!table) return;
    const dataRows = table.querySelectorAll('tbody tr[data-survey]');
    let data = [["No", "Tanggal Invoice", "No Invoice", "Teknisi", "Customer", "Ket. Survey", "Surveyor", "Nominal Invoice"]];
    let totalNominal = 0, rowIndex = 0;
    dataRows.forEach(function(row) {
        if (row.classList.contains('hidden-row')) return;
        rowIndex++;
        const cells = row.querySelectorAll('td');
        if (cells.length < 8) return;
        const tglInvoice = cells[1].textContent.trim();
        const noInvoice = cells[2].textContent.trim();
        const teknisi = cells[3].textContent.trim().replace(/\n/g, ', ');
        const customer = cells[4].textContent.trim();
        const ketSurvey = cells[5].textContent.trim();
        const surveyor = cells[6].textContent.trim();
        const nominal = parseInt(row.getAttribute('data-nominal')) || 0;
        totalNominal += nominal;
        data.push([rowIndex, tglInvoice, noInvoice, teknisi, customer, ketSurvey === '-' ? '' : ketSurvey, surveyor === '-' ? '' : surveyor, nominal]);
    });
    data.push(["", "", "", "", "", "", "TOTAL", totalNominal]);
    const ws = XLSX.utils.aoa_to_sheet(data);
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = 1; R <= range.e.r; R++) {
        const cell = ws[XLSX.utils.encode_cell({r: R, c: 7})];
        if (cell && typeof cell.v === 'number') { cell.t = 'n'; cell.z = '#,##0'; }
    }
    ws['!cols'] = [{wch:5},{wch:16},{wch:18},{wch:22},{wch:28},{wch:28},{wch:18},{wch:18}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Laporan Pendapatan Teknisi");
    const currentDate = '<?php echo $current_date; ?>';
    const [tahun, bulan] = currentDate.split('-');
    XLSX.writeFile(wb, `Laporan_Pendapatan_Teknisi_${bulan}_${tahun}.xlsx`);
  });
  </script>
</body>
</html>