<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Task";
$currentPage = "Task";

function shortenTechnicianName($fullName) {
    if (empty($fullName)) return '-';
    $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
    $words = explode(" ", $fullName);
    if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
    $shortenedName = implode(" ", $words);
    if (strlen($shortenedName) > 20) {
        $lastWordIndex = count($words) - 1;
        if ($lastWordIndex > 0) {
            $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
        }
    }
    return $shortenedName;
}

function getInitials($fullName) {
    if (empty($fullName)) return '-';
    $words = explode(" ", $fullName);
    $initials = "";
    foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
    return $initials;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$teknisi_id = $_GET['teknisi_id'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$jenis_kegiatan = $_GET['jenis_kegiatan'] ?? '';

$is_search_triggered = !empty($start_date) || !empty($end_date) || !empty($teknisi_id) || !empty($customer_name) || !empty($jenis_kegiatan);
$groupedData = [];

$sql_all_teknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
$result_all_teknisi = mysqli_query($conn, $sql_all_teknisi);

if ($is_search_triggered) {
    $params = [];
    $types = '';

    $sql_kegiatan = "SELECT DISTINCT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, inv.no_invoice, inv.nominal_invoice
                     FROM kegiatan k
                     LEFT JOIN customer c ON k.customer_id = c.id
                     LEFT JOIN team_kegiatan tk ON k.id = tk.kegiatan_id
                     LEFT JOIN pelaksanaan_kegiatan pk ON k.id = pk.kegiatan_id
                     LEFT JOIN (
                         SELECT kode, no_invoice, nominal_invoice 
                         FROM pendapatan_kegiatan 
                         WHERE deleted_at IS NULL 
                         GROUP BY kode
                     ) inv ON k.kode = inv.kode
                     WHERE k.status != 'waiting' AND k.deleted_at IS NULL";

    if (!empty($start_date) && !empty($end_date)) {
        $sql_kegiatan .= " AND DATE(k.jadwal) BETWEEN ? AND ?";
        $types .= 'ss';
        array_push($params, $start_date, $end_date);
    }
    if (!empty($teknisi_id)) {
        $sql_kegiatan .= " AND pk.teknisi_id = ?";
        $types .= 'i';
        $params[] = $teknisi_id;
    }
    if (!empty($customer_id)) {
        $sql_kegiatan .= " AND k.customer_id = ?";
        $types .= 'i';
        $params[] = $customer_id;
    }
    if (!empty($jenis_kegiatan)) {
        $sql_kegiatan .= " AND k.kegiatan = ?";
        $types .= 's';
        $params[] = $jenis_kegiatan;
    }
    $sql_kegiatan .= " ORDER BY k.jadwal DESC";
    
    $stmt = $conn->prepare($sql_kegiatan);
    if ($stmt && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt) {
        $stmt->execute();
        $result_kegiatan = $stmt->get_result();
        if ($result_kegiatan->num_rows > 0) {
            while ($row = $result_kegiatan->fetch_assoc()) {
                $groupedData[$row['kode']][] = $row;
            }
        }
        $stmt->close();
    }
}

if (isset($_GET['export_txt']) && $_GET['export_txt'] == '1' && !empty($groupedData)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Export_Kegiatan_' . date('Y-m-d_H-i-s') . '.txt"');
    
    $output = "DATA LAPORAN KEGIATAN\r\n";
    $output .= "=====================================================\r\n\r\n";
    
    foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
        $latest_kegiatan = $kegiatan_group[0];
        
        $teknisi_list = [];
        $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk WHERE tk.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
        $stmt_tek = $conn->prepare($sqlTeknisi);
        $stmt_tek->bind_param("s", $kodeTransaksi);
        $stmt_tek->execute();
        $resultTeknisi = $stmt_tek->get_result();
        while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
            $teknisi_list[] = shortenTechnicianName($rowTeknisi['nama_teknisi']);
        }
        $stmt_tek->close();
        
        $teknisi_str = !empty($teknisi_list) ? implode(", ", $teknisi_list) : "N/A";
        
        $output .= "Nama Customer    : " . $latest_kegiatan['nama_customer'] . "\r\n";
        $output .= "Nomor Telepon    : " . $latest_kegiatan['cust_nomor'] . "\r\n";
        $output .= "Tanggal Request  : " . date("d/m/Y, H:i", strtotime($latest_kegiatan['created_at'])) . "\r\n";
        $output .= "Teknisi Terlibat : " . $teknisi_str . "\r\n";
        $output .= "Jenis Kegiatan   : " . ucfirst($latest_kegiatan['kegiatan']) . "\r\n";
        $output .= "Status           : " . ucfirst($latest_kegiatan['status']) . "\r\n";
        $output .= "-----------------------------------------------------\r\n";
    }
    echo $output;
    exit;
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Kegiatan</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .lunas-background { position: relative; z-index: 1; }
        .lunas-background::after { content: ''; position: absolute; top: 0; left: 0; width: 80%; height: 80%; background-image: url('assets/img/lunas.png'); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.1; z-index: -1; }
        <?php include "css/floating-menu2.css";?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="card mb-4">
                <div class="card-header pb-0"><h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filter Laporan Kegiatan</h5></div>
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label>Jenis Kegiatan</label><select class="form-control border p-2" name="jenis_kegiatan"><option value="">Semua Jenis</option><option value="survey" <?= ($jenis_kegiatan == 'survey' ? ' selected' : '') ?>>Survey</option><option value="service" <?= ($jenis_kegiatan == 'service' ? ' selected' : '') ?>>Service</option><option value="pasang baru" <?= ($jenis_kegiatan == 'pasang baru' ? ' selected' : '') ?>>Pasang Baru</option></select></div>
                            <div class="col-md-4 mb-3"><label>Teknisi</label><select class="form-control border p-2" name="teknisi_id"><option value="">Semua Teknisi</option><?php mysqli_data_seek($result_all_teknisi, 0); while ($teknisi = mysqli_fetch_assoc($result_all_teknisi)) { echo "<option value='" . $teknisi['id'] . "'" . ($teknisi_id == $teknisi['id'] ? ' selected' : '') . ">" . htmlspecialchars($teknisi['nama']) . "</option>"; } ?></select></div>
                            <div class="col-md-4 mb-3 mb-3 position-relative">
                                <label for="customerSearchInput" class="form-label">Nama Customer</label>
                                
                                <input type="text" id="customerSearchInput" name="nama_customer_display" class="form-control border p-2" placeholder="Ketik untuk mencari nama customer..." autocomplete="off">
                                
                                <input type="hidden" id="customerIdInput" name="customer_id">
                                
                                <div id="searchResults" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <div class="col-md-4 mb-3"><label>Dari Tanggal</label><input type="date" class="form-control border p-2" name="start_date" id="startDate" value="<?= htmlspecialchars($start_date) ?>"></div>
                            <div class="col-md-4 mb-3"><label>Sampai Tanggal</label><input type="date" class="form-control border p-2" name="end_date" id="endDate" value="<?= htmlspecialchars($end_date) ?>"></div>
                            <!--<div class="col-md-4 mb-3 align-self-end"><div class="d-flex"><button type="button" class="btn btn-outline-secondary w-50 me-2" id="btn7Days">7 Hari Terakhir</button><button type="button" class="btn btn-outline-secondary w-50" id="btn30Days">30 Hari Terakhir</button></div></div>-->
                            <div class="col-12 d-flex gap-2">
    <button type="submit" class="btn btn-primary w-100">Tampilkan Data</button>
    <button type="submit" name="export_txt" value="1" class="btn btn-success w-100"><i class="fa-solid fa-download me-2"></i>Export to TXT</button>
</div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Jadwal & Jenis</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer & Alamat</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Invoice & Status Bayar</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Teknisi Terlibat</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Info Request</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$is_search_triggered): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Silakan gunakan filter di atas untuk menampilkan data.</td></tr>
                            <?php elseif (empty($groupedData)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada kegiatan yang cocok dengan kriteria filter Anda.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedData as $kodeTransaksi => $kegiatan_group):
                                    $latest_kegiatan = $kegiatan_group[0];
                                    $lunas_class = (!empty($latest_kegiatan['lunas']) && $latest_kegiatan['lunas'] != '0000-00-00') ? 'lunas-background' : ''; ?>
                                    <tr>
                                        <td class="ps-4 text-wrap"><div class="d-flex flex-column"><span class="badge badge-secondary text-capitalize p-1 px-2"><?= htmlspecialchars($latest_kegiatan['kegiatan']) ?></span><h6 class="mb-0 text-sm font-weight-bold"><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])) ?></h6><p class="text-xs text-secondary mb-0"><?= date("H:i", strtotime($latest_kegiatan['jadwal'])) ?> WIB</p></div></td>
                                        <td class="text-wrap w-50"><div class="d-flex flex-column"><h6 class="mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $latest_kegiatan['customer_id'] ?>" target="_blank"><?= htmlspecialchars($latest_kegiatan['nama_customer']) ?></a></h6><p class="text-xs text-secondary mb-1"><?php $nomorHandphone = $latest_kegiatan['cust_nomor']; if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1); ?><a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone ?>" target="_blank"><?= htmlspecialchars($latest_kegiatan['cust_nomor']) ?></a></p><p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($latest_kegiatan['alamat']) ?></p></div></td>
                                        <td class="text-sm text-wrap <?= $lunas_class ?>"><?php if (!empty($latest_kegiatan['no_invoice'])) : ?><p class="font-weight-bold text-dark mb-0 text-xs"><?= htmlspecialchars($latest_kegiatan['no_invoice']) ?></p><p class="text-success font-weight-bold mb-0 text-xs">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.') ?></p><?php else: ?><p class="text-xs text-danger mb-0">Belum Ada Invoice</p><?php endif; ?></td>
                                        <td><?php $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk WHERE tk.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id"; $stmt_tek = $conn->prepare($sqlTeknisi); $stmt_tek->bind_param("s", $kodeTransaksi); $stmt_tek->execute(); $resultTeknisi = $stmt_tek->get_result(); if($resultTeknisi->num_rows > 0) { while ($rowTeknisi = $resultTeknisi->fetch_assoc()) { echo "<p class='text-xs font-weight-bold mb-1'>" . shortenTechnicianName(htmlspecialchars($rowTeknisi['nama_teknisi'])) . "</p>"; } } else { echo "<p class='text-xs text-secondary mb-0'>N/A</p>"; } $stmt_tek->close(); ?></td>
                                        <td class="text-center"><div class="d-flex flex-column justify-content-center align-items-center"><div class="avatar-initials mb-1 bg-dark px-2 text-white d-flex align-items-center justify-content-center fw-bold"><?= getInitials($latest_kegiatan['request']) ?></div><p class="text-xxs text-secondary mb-0"><?= date("d/m/y, H:i", strtotime($latest_kegiatan['created_at'])) ?></p></div></td>
                                        <td class="text-center pe-4"><div><a class="btn btn-outline-secondary text-dark p-1 px-2 mb-0" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi ?>"><i class="fa-solid fa-eye text-sm"></i></a></div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>
    <?php include "js-include.php"; ?>
    <script>
        function setDateRange(days) {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - (days - 1));
            const formatDate = (date) => date.toISOString().slice(0, 10);
            document.getElementById('endDate').value = formatDate(endDate);
            document.getElementById('startDate').value = formatDate(startDate);
        }
        document.getElementById('btn7Days').addEventListener('click', () => setDateRange(7));
        document.getElementById('btn30Days').addEventListener('click', () => setDateRange(30));
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customerSearchInput');
    const customerIdInput = document.getElementById('customerIdInput');
    const resultsContainer = document.getElementById('searchResults');
    let debounceTimer;

    searchInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        const searchTerm = searchInput.value;

        if (searchTerm.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`search_customer.php?term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(customer => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.classList.add('list-group-item', 'list-group-item-action');
                            item.textContent = customer.nama;
                            item.setAttribute('data-id', customer.id);
                            
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                searchInput.value = this.textContent;
                                customerIdInput.value = this.getAttribute('data-id');
                                resultsContainer.innerHTML = '';
                            });
                            
                            resultsContainer.appendChild(item);
                        });
                    } else {
                        resultsContainer.innerHTML = '<span class="list-group-item">Customer tidak ditemukan.</span>';
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput) {
            resultsContainer.innerHTML = '';
        }
    });
});
</script>
</body>
</html>