<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Target Tercapai";
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
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    
    @media print {
        .no-print { display: none !important; }
        .sidenav, .navbar, .fixed-plugin { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; }
    }

    /* Action bar */
    .action-bar {
        display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
        margin-bottom: 24px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .action-btn {
        padding: 12px 24px; border: none; border-radius: 12px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.01em;
    }
    .action-btn:hover { 
        transform: translateY(-2px); 
    }
    .btn-bulanan { 
        background: linear-gradient(135deg, #0f172a, #1e293b); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(15,23,42,0.15); 
    }
    .btn-bulanan:hover { 
        box-shadow: 0 6px 20px rgba(15,23,42,0.25); 
        color: #fff;
    }
    .btn-print-action { 
        background: linear-gradient(135deg, #0284c7, #0369a1); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(2,132,199,0.15); 
    }
    .btn-print-action:hover { 
        box-shadow: 0 6px 20px rgba(2,132,199,0.25); 
        color: #fff;
    }
    .btn-excel { 
        background: linear-gradient(135deg, #059669, #047857); 
        color: #fff; 
        box-shadow: 0 4px 14px rgba(5,150,105,0.15); 
    }
    .btn-excel:hover { 
        box-shadow: 0 6px 20px rgba(5,150,105,0.25); 
        color: #fff;
    }
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php
    include "nav-top.php";
    $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>
    <div class="container-fluid py-4">
      <div class="action-bar no-print">
          <a href="laporan.php?cariBulanTahun=<?= $current_date; ?>" class="action-btn btn-bulanan">
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
        window.print();
    });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
  document.getElementById('download-btn').addEventListener('click', function () {
    const table = document.querySelector('#data-tek');
    if (!table) return;
    const dataRows = table.querySelectorAll('tbody tr[data-survey]');
    let data = [["No", "Tanggal Invoice", "Tanggal Lunas", "No Invoice", "Teknisi", "Customer", "Ket. Survey", "Surveyor", "Nominal Invoice", "Porsi Pendapatan"]];
    let totalNominal = 0, totalShare = 0, rowIndex = 0;
    dataRows.forEach(function(row) {
        if (row.classList.contains('hidden-row')) return;
        rowIndex++;
        const cells = row.querySelectorAll('td');
        if (cells.length < 9) return;
        const tglInvoice = cells[1].textContent.trim();
        const tglLunas = cells[2].textContent.trim();
        const noInvoice = cells[3].textContent.trim();
        const teknisi = cells[4].textContent.trim().replace(/\n/g, ', ');
        const customer = cells[5].textContent.trim();
        const ketSurvey = cells[6].textContent.trim();
        const surveyor = cells[7].textContent.trim();
        const nominal = parseInt(row.getAttribute('data-nominal')) || 0;
        const share = parseInt(row.getAttribute('data-share')) || 0;
        totalNominal += nominal;
        totalShare += share;
        data.push([rowIndex, tglInvoice, tglLunas, noInvoice, teknisi, customer, ketSurvey === '-' ? '' : ketSurvey, surveyor === '-' ? '' : surveyor, nominal, share]);
    });
    data.push(["", "", "", "", "", "", "", "TOTAL", totalNominal, totalShare]);
    const ws = XLSX.utils.aoa_to_sheet(data);
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = 1; R <= range.e.r; R++) {
        const cellNominal = ws[XLSX.utils.encode_cell({r: R, c: 8})];
        if (cellNominal && typeof cellNominal.v === 'number') { cellNominal.t = 'n'; cellNominal.z = '#,##0'; }
        const cellShare = ws[XLSX.utils.encode_cell({r: R, c: 9})];
        if (cellShare && typeof cellShare.v === 'number') { cellShare.t = 'n'; cellShare.z = '#,##0'; }
    }
    ws['!cols'] = [{wch:5},{wch:16},{wch:16},{wch:18},{wch:22},{wch:28},{wch:28},{wch:18},{wch:18},{wch:18}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Laporan Pendapatan Teknisi");
    const currentDate = '<?php echo $current_date; ?>';
    const [tahun, bulan] = currentDate.split('-');
    XLSX.writeFile(wb, `Laporan_Pendapatan_Teknisi_${bulan}_${tahun}.xlsx`);
  });
  </script>
</body>
</html>