<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Task";
$currentPage = "Task";
$jenis = $_GET['jenis'] ?? null;

// Helper Functions
if (!function_exists('shortenTechnicianName')) {
    function shortenTechnicianName($fullName) {
        $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
        $words = explode(" ", $fullName);
        if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
        $shortenedName = implode(" ", $words);
        if (strlen($shortenedName) > 20) {
            $lastWordIndex = count($words) - 1;
            if ($lastWordIndex > 0) {
                $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
                $shortenedName = implode(" ", $words);
            }
        }
        return $shortenedName;
    }
}

if (!function_exists('getInitials')) {
    function getInitials($fullName) {
        $words = explode(" ", $fullName);
        $initials = "";
        foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
        return $initials;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .table thead th { white-space: nowrap; }
        .table td, .table th { vertical-align: middle; }
        .table-responsive { border-radius: .5rem; }
        .avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e91e63;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .lunas-background {
            position: relative;
            z-index: 1;
        }
        .lunas-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 80%;
            height: 80%;
            background-image: url('assets/img/lunas.png'); /* Pastikan path gambar ini benar */
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: -1;
        }
        <?php include "css/floating-menu2.css";?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        ?>
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-lg-12 d-flex flex-row justify-content-start align-items-start gap-3">
                    <a href="task.php?jenis=survey" class="btn <?= $jenis == 'survey' ? 'btn-secondary' : 'btn-outline-secondary'; ?> px-4 py-2">Survey</a>
                    <a href="task.php?jenis=service" class="btn <?= $jenis == 'service' ? 'btn-secondary' : 'btn-outline-secondary'; ?> px-4 py-2">Service</a>
                    <a href="task.php?jenis=pasang%20baru" class="btn <?= $jenis == 'pasang baru' ? 'btn-secondary' : 'btn-outline-secondary'; ?> px-4 py-2">Pasang Baru</a>
                </div>
            </div>
            <div class="card">
                <div class="card-header p-3">
                    <h5 class="mb-0">Daftar Kegiatan: <?= htmlspecialchars(ucwords($jenis ?? 'Semua')); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-items-center mb-0">
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
                                <?php
                                $sql_kegiatan = "SELECT 
                                                    k.*, 
                                                    c.nama AS nama_customer, 
                                                    c.telp AS cust_nomor, 
                                                    c.alamat,
                                                    inv.no_invoice,
                                                    inv.nominal_invoice
                                                FROM kegiatan k
                                                LEFT JOIN customer c ON k.customer_id = c.id
                                                LEFT JOIN (
                                                    SELECT kode, no_invoice, nominal_invoice 
                                                    FROM pendapatan_kegiatan 
                                                    WHERE deleted_at IS NULL 
                                                    GROUP BY kode
                                                ) inv ON k.kode = inv.kode
                                                WHERE k.status != 'waiting' AND k.deleted_at IS NULL";
                                if ($jenis !== null) {
                                    $sql_kegiatan .= " AND k.kegiatan = '" . mysqli_real_escape_string($conn, $jenis) . "'";
                                }
                                $sql_kegiatan .= " ORDER BY k.jadwal DESC";

                                $result_kegiatan = mysqli_query($conn, $sql_kegiatan);
                                $groupedData = [];

                                if (mysqli_num_rows($result_kegiatan) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_kegiatan)) {
                                        $groupedData[$row['kode']][] = $row;
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-5'>Tidak ada kegiatan yang ditemukan.</td></tr>";
                                }

                                foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
                                    $latest_kegiatan = $kegiatan_group[0];
                                    $lunas_class = (!empty($latest_kegiatan['lunas']) && $latest_kegiatan['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                                ?>
                                <tr>
                                    <td class="ps-4 text-wrap">
                                        <div class="d-flex flex-column">
                                            <!--<span class="badge badge-secondary text-capitalize p-1 px-2"><?= $latest_kegiatan['kegiatan'];?></span>-->
                                            <h6 class="mb-0 text-sm font-weight-bold"><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])); ?></h6>
                                            <p class="text-xs text-secondary mb-0"><?= date("H:i", strtotime($latest_kegiatan['jadwal'])); ?> WIB</p>
                                        </div>
                                    </td>
                                    <td class=" text-wrap w-50">
                                        <div class="d-flex flex-column">
                                            <h6 class="mb-0 text-sm">
                                                <a href="customer-detail.php?id_cust=<?= $latest_kegiatan['customer_id']; ?>"><?= htmlspecialchars($latest_kegiatan['nama_customer']); ?></a>
                                            </h6>
                                            <p class="text-xs text-secondary mb-1">
                                                <?php $nomorHandphone = $latest_kegiatan['cust_nomor']; if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1); ?>
                                                <a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone; ?>" target="_blank"><?= htmlspecialchars($latest_kegiatan['cust_nomor']); ?></a>
                                            </p>
                                            <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($latest_kegiatan['alamat']); ?></p>
                                        </div>
                                    </td>
                                    <td class="text-sm text-wrap <?= $lunas_class ?>">
                                        <?php if (!empty($latest_kegiatan['no_invoice'])) : ?>
                                            <p class="font-weight-bold text-dark mb-0 text-xs"><?= htmlspecialchars($latest_kegiatan['no_invoice']); ?></p>
                                            <p class="text-success font-weight-bold mb-0 text-xs">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.'); ?></p>
                                        <?php else: ?>
                                            <p class="text-xs text-danger mb-0">Belum Ada Invoice</p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // --- Kode untuk menampilkan teknisi (tidak diubah) ---
                                        $sqlTeknisi = "SELECT tk.teknisi_id, tk.nama_teknisi, pk.status as status_pelaksanaan FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id LEFT JOIN pelaksanaan_kegiatan pk ON tk.kegiatan_id = pk.kegiatan_id AND tk.teknisi_id = pk.teknisi_id WHERE k.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id ORDER BY k.id DESC";
                                        $stmt = $conn->prepare($sqlTeknisi);
                                        $stmt->bind_param("s", $kodeTransaksi);
                                        $stmt->execute();
                                        $resultTeknisi = $stmt->get_result();
                                        if($resultTeknisi->num_rows > 0) {
                                            while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
                                                $statusPelaksanaan = $rowTeknisi['status_pelaksanaan'];
                                                $statusClass = 'bg-secondary'; $statusText = 'Dijadwalkan';
                                                if ($statusPelaksanaan == 'selesai') { $statusClass = 'bg-success'; $statusText = 'Selesai'; } 
                                                elseif ($statusPelaksanaan == 'berjalan') { $statusClass = 'bg-info'; $statusText = 'Dikerjakan'; } 
                                                elseif ($statusPelaksanaan == 'Lanjut Nanti') { $statusClass = 'bg-warning'; $statusText = 'Lanjut Nanti'; }
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <p class="text-xs font-weight-bold mb-0"><?= shortenTechnicianName(htmlspecialchars($rowTeknisi['nama_teknisi'])) ?></p>
                                            <!--<span class="badge badge-sm text-xs <?= $statusClass ?>" style="font-size:9px !important;"><?= $statusText ?></span>-->
                                        </div>
                                        <?php }
                                        } else { echo "<p class='text-xs text-secondary mb-0'>Teknisi belum ditugaskan.</p>"; }
                                        $stmt->close(); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column justify-content-center align-items-center">
                                            <div class="avatar-initials mb-1"><?= getInitials($latest_kegiatan['request']) ?></div>
                                            <p class="text-xxs text-secondary mb-0"><?= date("d/m/y, H:i", strtotime($latest_kegiatan['created_at'])); ?></p>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div>
                                            <a class="btn btn-outline-secondary text-dark p-1 px-2 mb-0" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons text-sm">visibility</i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>
    
    <?php include "js-include.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        if (navigator.platform.indexOf('Win') > -1 && document.querySelector('#sidenav-scrollbar')) {
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), { damping: '0.5' });
        }
    </script>
</body>
</html>