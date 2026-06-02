<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Teknisi";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Data & Kinerja Teknisi</title>
    <?php include "head.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        @media print {
            body * {
                visibility: hidden;
            }

            .printable-area,
            .printable-area * {
                visibility: visible;
            }

            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }

            .no-print {
                display: none !important;
            }

            .table {
                font-size: 10pt;
            }

            h4,
            h5 {
                margin-top: 20px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <!--<div class="no-print">-->
        <?php include "cek-menu.php"; ?>
    <!--</div>-->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <div class="no-print"><?php include "nav-top.php"; ?></div>
        <div class="container-fluid py-4">
            <div class="d-sm-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-2 mb-sm-0 text-uppercase">Data & Kinerja Teknisi</h4>
                <div class="d-flex no-print">
                    <button class="btn btn-outline-info me-2" onclick="openFeeModal()"><i class="fa-solid fa-money-bill-wave me-2"></i>Kelola Fee</button>
                    <button class="btn btn-outline-success me-2" onclick="exportToXls('laporan-teknisi-<?= date('Y-m'); ?>.xls')"><i class="fa-solid fa-file-excel me-2"></i>Ekspor XLS</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahTeknisiModal"><i class="fa-solid fa-plus me-2"></i>Tambah Teknisi</button>
                </div>
            </div>
            <div class="card mb-4 no-print">
                <div class="card-body row align-items-end">
                    <div class="col-md-12"><label for="filterMonth" class="form-label">Tampilkan Data untuk Bulan</label></div>
                    <div class="col-md-6 d-flex">
                        <input type="month" id="filterMonth" class="form-control p-2 mt-0 border w-50" value="<?= date('Y-m'); ?>">
                        <button id="filterBtn" class="btn btn-dark py-2 w-50 ms-2"><i class="fa-solid fa-filter me-2"></i>Terapkan Filter</button>
                    </div>
                </div>
            </div>
            <div class="printable-area">
                <div class="card no-print">
                    <div class="card-header mb-0 pb-0"><h5 class="mb-0">Grafik Kinerja</h5></div>
                    <div class="card-body mt-0 pt-0"><div style="height: 300px;"><canvas id="technicianChart"></canvas></div></div>
                </div>
                <div class="card mt-4">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0">Daftar Teknisi</h5>
                        <p class="text-sm mb-0">Laporan Kinerja per: <span id="report-period"></span></p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Teknisi</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kegiatan</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Target</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fee (Paid)</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pendapatan</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Bonus</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="teknisiTableBody">
                                <tr><td colspan="7" class="py-5 text-center"><div class="spinner-border text-primary"></div></td></tr>
                            </tbody>
                            <tfoot id="teknisiTableFooter"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="no-print"><?php include "footer.php"; ?></div>
    </main>
    <div class="modal fade" id="tambahTeknisiModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Teknisi Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="teknisi-db.php"><div class="mb-3"><label class="form-label">NIK</label><input type="text" class="form-control p-2 border" name="nik" required></div><div class="mb-3"><label class="form-label">Nama</label><input type="text" class="form-control p-2 border" name="nama" required></div><div class="mb-3"><label class="form-label">Nomor WhatsApp</label><div class="input-group"><input type="text" class="form-control p-2 border" name="no_wa" placeholder="0812..." required></div></div><div class="mb-3"><label class="form-label">No KTP</label><input type="text" class="form-control p-2 border" name="ktp"></div><div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div></div>
    <div class="modal fade" id="targetModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Input Nominal Target</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="targetForm"><div class="form-group"><label>Nominal Target</label><input type="text" class="form-control p-2 border" id="targetInput" required></div><input type="hidden" id="nikInput"><div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div></div>
    
    <div class="modal fade" id="feeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Kelola Fee Default</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="feeForm">
                        <div class="mb-3">
                            <label>Fee Saat Ini</label>
                            <span class="form-control-plaintext border p-2 disabled bg-gray-200" id="currentFeeDisplay">Memuat...</span>
                        </div>
                        <div class="form-group">
                            <label for="newFeeInput">Input Fee Baru</label>
                            <input type="text" class="form-control border p-2" id="newFeeInput" name="new_fee" required>
                        </div>
                        <div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
    
    <?php 
    // include "js-include.php";
    ?>
    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>-->
    <script>
        let technicianChart, currentTechnicianData = [];
        const formatRupiah = angka => 'Rp ' + (angka ? parseInt(angka).toLocaleString('id-ID') : '0');
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('technicianChart').getContext('2d');
            technicianChart = new Chart(ctx, { type: 'bar', data: { labels: [], datasets: [ { label: 'Target', data: [], backgroundColor: '#adb5bd' }, { label: 'Pendapatan', data: [], backgroundColor: '#495057' }, { label: 'Bonus', data: [], backgroundColor: '#28a745' } ] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: value => formatRupiah(value) } } } } });
            document.getElementById('filterBtn').addEventListener('click', fetchDataAndUpdate);
            fetchDataAndUpdate();
        });

        async function fetchDataAndUpdate() {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            const selectedDate = document.getElementById('filterMonth').value;
            const date = new Date(selectedDate + '-02');
            document.getElementById('report-period').textContent = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            tableBody.innerHTML = '<tr><td colspan="7" class="py-5 text-center"><div class="spinner-border text-primary"></div></td></tr>';
            tableFooter.innerHTML = '';
            try {
                const response = await fetch(`get_teknisi_data.php?date=${selectedDate}`);
                const data = await response.json();
                currentTechnicianData = data.tableData;
                updateTable(data.tableData);
                updateChart(data.chartData);
            } catch (error) { tableBody.innerHTML = '<tr><td colspan="7" class="py-5 text-center text-danger">Gagal memuat data.</td></tr>'; }
        }

        function updateTable(data) {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            tableBody.innerHTML = '';
            tableFooter.innerHTML = '';
            if (data.length === 0) { tableBody.innerHTML = '<tr><td colspan="7" class="py-5 text-center">Tidak ada data untuk periode ini.</td></tr>'; return; }
            let totalPendapatan = 0; let totalBonus = 0; let totalFee = 0;
            data.forEach(row => {
                totalPendapatan += parseFloat(row.total_pendapatan);
                totalBonus += parseFloat(row.bonus);
                totalFee += parseFloat(row.total_fee);
                const tr = `<tr><td><div class="d-flex px-2 py-1"><div class="d-flex flex-column justify-content-center"><h6 class="mb-0 text-sm"><a href="list-kegiatan-teknisi.php?cariBulanTahun=${document.getElementById('filterMonth').value}&idTek=${row.id}">${row.nama}</a></h6><p class="text-xs text-secondary mb-0">NIK: ${row.nik}</p></div></div></td><td class="align-middle text-center"><span class="text-secondary text-sm font-weight-bold">${row.jumlah_kegiatan}</span></td><td class="align-middle text-center"><span class="text-secondary text-sm font-weight-bold">${formatRupiah(row.target)}</span></td><td class="align-middle text-center"><span class="text-info text-sm font-weight-bold">${formatRupiah(row.total_fee)}</span></td><td class="align-middle text-center"><span class="text-secondary text-sm font-weight-bold">${formatRupiah(row.total_pendapatan)}</span></td><td class="align-middle text-center"><span class="text-success text-sm font-weight-bold">${formatRupiah(row.bonus)}</span></td><td class="align-middle text-center no-print"><button class="btn btn-link text-secondary mb-0 p-2" onclick='openTargetModal(${JSON.stringify(row.nik)}, ${JSON.stringify(row.target)})'><i class="fa fa-pencil-alt text-dark"></i></button><button class="btn btn-link text-danger mb-0 p-2" onclick='softDeleteTeknisi(${row.id})'><i class="fa fa-trash"></i></button></td></tr>`;
                tableBody.innerHTML += tr;
            });
            const footerRow = `<tr class="bg-light"><td class="text-end pe-3 font-weight-bold">Total</td><td></td><td></td><td class="text-center font-weight-bold text-info">${formatRupiah(totalFee)}</td><td class="text-center font-weight-bold">${formatRupiah(totalPendapatan)}</td><td class="text-center font-weight-bold text-success">${formatRupiah(totalBonus)}</td><td class="no-print"></td></tr>`;
            tableFooter.innerHTML = footerRow;
        }
        
        function exportToXls(filename) {
            if (currentTechnicianData.length === 0) { alert("Tidak ada data untuk diekspor."); return; }
            const month = document.getElementById('report-period').textContent;
            let totalPendapatan = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.total_pendapatan), 0);
            let totalBonus = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.bonus), 0);
            let totalFee = currentTechnicianData.reduce((sum, row) => sum + parseFloat(row.total_fee), 0);
            let tableHtml = `<html><head><style>table, th, td {border: 1px solid black; border-collapse: collapse; padding: 5px;} .currency { mso-number-format: '"Rp"\\ \\#\\,\\#\\#0'; } .number { mso-number-format: '0'; } .text { mso-number-format: '@'; } </style></head><body><h3>Laporan Kinerja Teknisi</h3><p>Periode: ${month}</p><table><thead><tr><th>NIK</th><th>Nama</th><th>Jml Kegiatan</th><th>Target</th><th>Fee (Paid)</th><th>Pendapatan</th><th>Bonus</th></tr></thead><tbody>
                ${currentTechnicianData.map(row => `<tr><td class="text">'${row.nik}</td><td>${row.nama}</td><td class="number">${row.jumlah_kegiatan}</td><td class="currency">${Math.round(row.target)}</td><td class="currency">${Math.round(row.total_fee)}</td><td class="currency">${Math.round(row.total_pendapatan)}</td><td class="currency">${Math.round(row.bonus)}</td></tr>`).join('')}
                <tr><td colspan="4" style="font-weight:bold;text-align:right;">Total</td><td class="currency" style="font-weight:bold;">${Math.round(totalFee)}</td><td class="currency" style="font-weight:bold;">${Math.round(totalPendapatan)}</td><td class="currency" style="font-weight:bold;">${Math.round(totalBonus)}</td></tr>
            </tbody></table></body></html>`;
            const link = document.createElement("a");
            link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(tableHtml);
            link.download = filename;
            link.click();
        }
        function updateChart(data) { technicianChart.data.labels = data.labels; technicianChart.data.datasets[0].data = data.targets; technicianChart.data.datasets[1].data = data.pendapatan; technicianChart.data.datasets[2].data = data.bonus; technicianChart.update(); }
        function openTargetModal(nik, currentTarget) { document.getElementById('nikInput').value = nik; document.getElementById('targetInput').value = currentTarget ? parseInt(currentTarget).toLocaleString('id-ID') : ''; $('#targetModal').modal('show'); }
        function softDeleteTeknisi(id) { if (!confirm("Anda yakin ingin menonaktifkan teknisi ini?")) return; $.ajax({ url: 'soft_delete_teknisi.php', type: 'POST', data: { id: id }, success: function(response) { if (response.trim() === "success") { fetchDataAndUpdate(); } else { alert("Gagal menonaktifkan teknisi."); } }, error: function() { alert("Terjadi kesalahan koneksi."); } }); }
        document.getElementById('targetInput').addEventListener('input', e => { let v = e.target.value.replace(/[^0-9]/g, ''); e.target.value = v ? parseInt(v, 10).toLocaleString('id-ID') : ''; });
        $('#targetForm').on('submit', function(e) { e.preventDefault(); let target = $('#targetInput').val().replace(/[^\d]/g, ''); let nik = $('#nikInput').val(); $.ajax({ url: 'update_target.php', type: 'POST', data: { target: target, nik: nik }, success: () => { $('#targetModal').modal('hide'); fetchDataAndUpdate(); }, error: () => alert('Gagal update.') }); });
    </script>
    <script>
        let currentFeeValue = 0;

async function openFeeModal() {
    document.getElementById('currentFeeDisplay').textContent = 'Memuat...';
    
    try {
        const response = await fetch('get_current_fee.php');
        const data = await response.json();
        
        if(data.success && data.fee !== null) {
            currentFeeValue = data.fee;
            document.getElementById('currentFeeDisplay').textContent = formatRupiah(currentFeeValue);
        } else {
            document.getElementById('currentFeeDisplay').textContent = 'Belum diatur';
        }
    } catch (error) {
        document.getElementById('currentFeeDisplay').textContent = 'Gagal memuat';
    }

    $('#feeModal').modal('show');
}

document.getElementById('newFeeInput').addEventListener('input', e => {
    let v = e.target.value.replace(/[^0-9]/g, '');
    e.target.value = v ? parseInt(v, 10).toLocaleString('id-ID') : '';
});

$('#feeForm').on('submit', function(e) {
    e.preventDefault();
    let newFee = $('#newFeeInput').val().replace(/[^\d]/g, '');

    $.ajax({
        url: 'proses_update_fee.php',
        type: 'POST',
        data: { new_fee: newFee },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('Fee berhasil diperbarui!');
                $('#feeModal').modal('hide');
            } else {
                alert('Gagal memperbarui fee: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan koneksi.');
        }
    });
});
    </script>
</body>
</html>