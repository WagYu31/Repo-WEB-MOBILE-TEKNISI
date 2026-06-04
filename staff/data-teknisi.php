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
        /* ═══ PREMIUM DATA TEKNISI ═══ */
        .tek-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px; margin-bottom: 20px;
        }
        .tek-title-left { display: flex; align-items: center; gap: 14px; }
        .tek-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(245,158,11,0.3);
        }
        .tek-icon i { color: #fff; font-size: 18px; }
        .tek-title-left h4 { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; }
        .tek-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }
        .tek-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .tek-btn {
            padding: 9px 18px; border: none; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .tek-btn:hover { transform: translateY(-1px); }
        .tek-btn-fee { background: #eef2ff; color: #6366f1; border: 1.5px solid #c7d2fe; }
        .tek-btn-fee:hover { background: #6366f1; color: #fff; }
        .tek-btn-xls { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
        .tek-btn-xls:hover { background: #22c55e; color: #fff; }
        .tek-btn-add {
            background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }
        .tek-btn-add:hover { color: #fff; }

        /* Filter card */
        .tek-filter-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 16px 20px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .tek-filter-label { font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; }
        .tek-month-input {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px;
            font-size: 13px; font-weight: 600; color: #1e293b; background: #f8fafc;
            transition: all 0.2s;
        }
        .tek-month-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.08); outline: none; }
        .tek-filter-btn {
            padding: 9px 24px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #1e293b, #334155); color: #fff;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .tek-filter-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30,41,59,0.2); }

        /* Chart card */
        .tek-chart-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden; margin-bottom: 20px;
        }
        .tek-chart-header {
            display: flex; align-items: center; gap: 12px; padding: 20px 24px 0;
        }
        .tek-chart-icon {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(139,92,246,0.2);
        }
        .tek-chart-icon i { color: #fff; font-size: 13px; }
        .tek-chart-header h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .tek-chart-body { padding: 16px 20px 20px; }

        /* Table card */
        .tek-table-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .tek-table-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
        }
        .tek-table-left { display: flex; align-items: center; gap: 12px; }
        .tek-table-icon {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(245,158,11,0.2);
        }
        .tek-table-icon i { color: #fff; font-size: 13px; }
        .tek-table-left h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .tek-period-badge {
            font-size: 11px; font-weight: 700; color: #f59e0b; background: #fffbeb;
            padding: 5px 14px; border-radius: 20px; border: 1px solid #fde68a;
        }

        .tek-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
        .tek-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .tek-table tbody tr { transition: background 0.15s; }
        .tek-table tbody tr:hover { background: #fafbfc; }
        .tek-table tbody td {
            padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .tek-table tfoot td {
            padding: 14px 16px; font-size: 13px; font-weight: 800; color: #1e293b;
            background: #f8fafc; border-top: 2px solid #e5e7eb;
        }

        .tek-name-link { font-size: 14px; font-weight: 700; color: #f59e0b; text-decoration: none; }
        .tek-name-link:hover { color: #ea580c; text-decoration: underline; }
        .tek-nik { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .tek-val { font-size: 13px; font-weight: 600; }
        .tek-val.v-count { color: #1e293b; font-weight: 800; }
        .tek-val.v-fee { color: #6366f1; }
        .tek-val.v-pendapatan { color: #1e293b; }
        .tek-val.v-bonus { color: #16a34a; }
        .tek-val.v-target { color: #94a3b8; }

        .tek-act-btn {
            width: 30px; height: 30px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s; font-size: 12px;
        }
        .tek-act-edit { background: #fef3c7; color: #d97706; }
        .tek-act-edit:hover { background: #f59e0b; color: #fff; }
        .tek-act-del { background: #fef2f2; color: #ef4444; }
        .tek-act-del:hover { background: #ef4444; color: #fff; }

        /* Premium modal */
        .prem-modal .modal-content {
            border-radius: 16px; border: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        .prem-modal .modal-header {
            border-bottom: 1px solid #f1f5f9; padding: 18px 24px;
        }
        .prem-modal .modal-header h5 { font-size: 15px; font-weight: 800; color: #1e293b; }
        .prem-modal .modal-body { padding: 20px 24px; }
        .prem-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block; }
        .prem-input {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 10px 14px; font-size: 13px; color: #1e293b; background: #f8fafc;
            transition: all 0.2s; font-weight: 500;
        }
        .prem-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.08); outline: none; background: #fff; }
        .prem-footer { padding: 0 24px 20px; display: flex; gap: 8px; }
        .prem-btn-cancel {
            flex: 1; padding: 10px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            background: #fff; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .prem-btn-save {
            flex: 2; padding: 10px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;
            font-size: 13px; font-weight: 700; cursor: pointer;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }

        .spinner-box { padding: 60px; text-align: center; }
        .spinner-ring {
            width: 36px; height: 36px; border: 3px solid #f1f5f9;
            border-top-color: #f59e0b; border-radius: 50%;
            animation: spin 0.7s linear infinite; display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .tek-header { flex-direction: column; align-items: flex-start; }
        }
        @media print {
            body * { visibility: hidden; }
            .printable-area, .printable-area * { visibility: visible; }
            .printable-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
            .tek-chart-card, .tek-table-card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
            .no-print { display: none !important; }
            .tek-table { font-size: 10pt; }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <div class="no-print"><?php include "nav-top.php"; ?></div>
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="tek-header">
                <div class="tek-title-left">
                    <div class="tek-icon"><i class="fa-solid fa-helmet-safety"></i></div>
                    <div>
                        <h4>Data & Kinerja Teknisi</h4>
                        <p>Dashboard performa dan laporan bulanan</p>
                    </div>
                </div>
                <div class="tek-actions no-print">
                    <button class="tek-btn tek-btn-fee" onclick="openFeeModal()">
                        <i class="fa-solid fa-money-bill-wave"></i> Kelola Fee
                    </button>
                    <button class="tek-btn tek-btn-xls" onclick="exportToXls('laporan-teknisi-<?= date('Y-m'); ?>.xls')">
                        <i class="fa-solid fa-file-excel"></i> Ekspor XLS
                    </button>
                    <button class="tek-btn tek-btn-add" data-bs-toggle="modal" data-bs-target="#tambahTeknisiModal">
                        <i class="fa-solid fa-plus"></i> Tambah Teknisi
                    </button>
                </div>
            </div>

            <!-- Filter -->
            <div class="tek-filter-card no-print">
                <div class="tek-filter-label"><i class="fa-solid fa-calendar-days" style="margin-right:6px;"></i>Periode</div>
                <input type="month" id="filterMonth" class="tek-month-input" value="<?= date('Y-m'); ?>">
                <button id="filterBtn" class="tek-filter-btn">
                    <i class="fa-solid fa-filter"></i> Terapkan
                </button>
            </div>

            <div class="printable-area">
                <!-- Chart -->
                <div class="tek-chart-card no-print">
                    <div class="tek-chart-header">
                        <div class="tek-chart-icon"><i class="fa-solid fa-chart-column"></i></div>
                        <h6>Grafik Kinerja</h6>
                    </div>
                    <div class="tek-chart-body"><div style="height:280px;"><canvas id="technicianChart"></canvas></div></div>
                </div>

                <!-- Table -->
                <div class="tek-table-card">
                    <div class="tek-table-header">
                        <div class="tek-table-left">
                            <div class="tek-table-icon"><i class="fa-solid fa-list-check"></i></div>
                            <h6>Daftar Teknisi</h6>
                        </div>
                        <span class="tek-period-badge" id="report-period"></span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="tek-table">
                            <thead>
                                <tr>
                                    <th style="padding-left:24px;">Teknisi</th>
                                    <th style="text-align:center;">Kegiatan</th>
                                    <th style="text-align:center;">Target</th>
                                    <th style="text-align:center;">Fee (Paid)</th>
                                    <th style="text-align:center;">Pendapatan</th>
                                    <th style="text-align:center;">Bonus</th>
                                    <th style="text-align:center; width:90px;" class="no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="teknisiTableBody">
                                <tr><td colspan="7"><div class="spinner-box"><div class="spinner-ring"></div></div></td></tr>
                            </tbody>
                            <tfoot id="teknisiTableFooter"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="no-print"><?php include "footer.php"; ?></div>
    </main>

    <!-- Modal: Tambah Teknisi -->
    <div class="modal fade prem-modal" id="tambahTeknisiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Teknisi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="teknisi-db.php">
                        <div style="margin-bottom:14px;"><label class="prem-label">NIK</label><input type="text" class="prem-input" name="nik" required></div>
                        <div style="margin-bottom:14px;"><label class="prem-label">Nama</label><input type="text" class="prem-input" name="nama" required></div>
                        <div style="margin-bottom:14px;"><label class="prem-label">Nomor WhatsApp</label><input type="text" class="prem-input" name="no_wa" placeholder="0812..." required></div>
                        <div style="margin-bottom:14px;"><label class="prem-label">No KTP</label><input type="text" class="prem-input" name="ktp"></div>
                        <div class="prem-footer" style="padding:0; margin-top:16px;">
                            <button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="prem-btn-save">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Target -->
    <div class="modal fade prem-modal" id="targetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <div class="modal-body">
                    <form id="targetForm">
                        <label class="prem-label">Nominal Target</label>
                        <input type="text" class="prem-input" id="targetInput" required>
                        <input type="hidden" id="nikInput">
                        <div class="prem-footer" style="padding:0; margin-top:16px;">
                            <button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="prem-btn-save">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Kelola Fee -->
    <div class="modal fade prem-modal" id="feeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kelola Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <div class="modal-body">
                    <form id="feeForm">
                        <label class="prem-label">Fee Saat Ini</label>
                        <div id="currentFeeDisplay" style="padding:10px 14px; background:#f1f5f9; border-radius:10px; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:14px;">Memuat...</div>
                        <label class="prem-label">Input Fee Baru</label>
                        <input type="text" class="prem-input" id="newFeeInput" name="new_fee" required>
                        <div class="prem-footer" style="padding:0; margin-top:16px;">
                            <button type="button" class="prem-btn-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="prem-btn-save">Simpan</button>
                        </div>
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
    <script>
        let technicianChart, currentTechnicianData = [];
        const formatRupiah = angka => 'Rp ' + (angka ? parseInt(angka).toLocaleString('id-ID') : '0');

        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('technicianChart').getContext('2d');
            technicianChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Target', data: [], backgroundColor: 'rgba(148,163,184,0.4)', borderColor: '#94a3b8', borderWidth: 1, borderRadius: 6 },
                        { label: 'Pendapatan', data: [], backgroundColor: 'rgba(245,158,11,0.7)', borderColor: '#f59e0b', borderWidth: 1, borderRadius: 6 },
                        { label: 'Bonus', data: [], backgroundColor: 'rgba(34,197,94,0.7)', borderColor: '#22c55e', borderWidth: 1, borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { font: { size: 11, weight: '600' }, usePointStyle: true, pointStyle: 'rectRounded' } } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: value => formatRupiah(value), font: { size: 10 } } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } }
                }
            });
            document.getElementById('filterBtn').addEventListener('click', fetchDataAndUpdate);
            fetchDataAndUpdate();
        });

        async function fetchDataAndUpdate() {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            const selectedDate = document.getElementById('filterMonth').value;
            const date = new Date(selectedDate + '-02');
            document.getElementById('report-period').textContent = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            tableBody.innerHTML = '<tr><td colspan="7"><div class="spinner-box"><div class="spinner-ring"></div></div></td></tr>';
            tableFooter.innerHTML = '';
            try {
                const response = await fetch(`get_teknisi_data.php?date=${selectedDate}`);
                const data = await response.json();
                currentTechnicianData = data.tableData;
                updateTable(data.tableData);
                updateChart(data.chartData);
            } catch (error) { tableBody.innerHTML = '<tr><td colspan="7" class="py-5 text-center" style="color:#ef4444;">Gagal memuat data.</td></tr>'; }
        }

        function updateTable(data) {
            const tableBody = document.getElementById('teknisiTableBody');
            const tableFooter = document.getElementById('teknisiTableFooter');
            tableBody.innerHTML = '';
            tableFooter.innerHTML = '';
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:60px; color:#94a3b8;"><i class="fa-solid fa-user-slash" style="font-size:32px; display:block; margin-bottom:8px;"></i>Tidak ada data untuk periode ini.</td></tr>';
                return;
            }
            let totalPendapatan = 0, totalBonus = 0, totalFee = 0;
            data.forEach(row => {
                totalPendapatan += parseFloat(row.total_pendapatan);
                totalBonus += parseFloat(row.bonus);
                totalFee += parseFloat(row.total_fee);
                const tr = `<tr>
                    <td style="padding-left:24px;">
                        <a class="tek-name-link" href="list-kegiatan-teknisi.php?cariBulanTahun=${document.getElementById('filterMonth').value}&idTek=${row.id}">${row.nama}</a>
                        <div class="tek-nik">NIK: ${row.nik}</div>
                    </td>
                    <td style="text-align:center;"><span class="tek-val v-count">${row.jumlah_kegiatan}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-target">${formatRupiah(row.target)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-fee">${formatRupiah(row.total_fee)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-pendapatan">${formatRupiah(row.total_pendapatan)}</span></td>
                    <td style="text-align:center;"><span class="tek-val v-bonus">${formatRupiah(row.bonus)}</span></td>
                    <td style="text-align:center;" class="no-print">
                        <div style="display:flex; gap:4px; justify-content:center;">
                            <button class="tek-act-btn tek-act-edit" onclick='openTargetModal(${JSON.stringify(row.nik)}, ${JSON.stringify(row.target)})' title="Set Target"><i class="fa-solid fa-pen"></i></button>
                            <button class="tek-act-btn tek-act-del" onclick='softDeleteTeknisi(${row.id})' title="Nonaktifkan"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
                tableBody.innerHTML += tr;
            });
            tableFooter.innerHTML = `<tr>
                <td style="text-align:right; padding-left:24px;">Total</td>
                <td></td><td></td>
                <td style="text-align:center;"><span class="tek-val v-fee">${formatRupiah(totalFee)}</span></td>
                <td style="text-align:center;"><span class="tek-val v-pendapatan">${formatRupiah(totalPendapatan)}</span></td>
                <td style="text-align:center;"><span class="tek-val v-bonus">${formatRupiah(totalBonus)}</span></td>
                <td class="no-print"></td>
            </tr>`;
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

        let currentFeeValue = 0;
        async function openFeeModal() {
            document.getElementById('currentFeeDisplay').textContent = 'Memuat...';
            try {
                const response = await fetch('get_current_fee.php');
                const data = await response.json();
                if(data.success && data.fee !== null) { currentFeeValue = data.fee; document.getElementById('currentFeeDisplay').textContent = formatRupiah(currentFeeValue); }
                else { document.getElementById('currentFeeDisplay').textContent = 'Belum diatur'; }
            } catch (error) { document.getElementById('currentFeeDisplay').textContent = 'Gagal memuat'; }
            $('#feeModal').modal('show');
        }
        document.getElementById('newFeeInput').addEventListener('input', e => { let v = e.target.value.replace(/[^0-9]/g, ''); e.target.value = v ? parseInt(v, 10).toLocaleString('id-ID') : ''; });
        $('#feeForm').on('submit', function(e) { e.preventDefault(); let newFee = $('#newFeeInput').val().replace(/[^\d]/g, ''); $.ajax({ url: 'proses_update_fee.php', type: 'POST', data: { new_fee: newFee }, dataType: 'json', success: function(response) { if(response.success) { alert('Fee berhasil diperbarui!'); $('#feeModal').modal('hide'); } else { alert('Gagal memperbarui fee: ' + response.message); } }, error: function() { alert('Terjadi kesalahan koneksi.'); } }); });
    </script>
</body>
</html>