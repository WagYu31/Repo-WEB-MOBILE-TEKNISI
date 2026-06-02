<?php
include "../conn.php";
include "../session.php";
include "../get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "NotClear";

// ========================================================================
// FUNGSI-FUNGSI PEMBANTU (HELPER FUNCTIONS)
// Didefinisikan di luar loop agar lebih efisien.
// ========================================================================
if (!function_exists('translateActivityStatus')) {
    function translateActivityStatus($status) {
        $statusMap = [
            'waiting'          => 'Dalam Antrian',
            'dijadwalkan'      => 'Dijadwalkan',
            'berjalan'         => 'Dalam Proses',
            'selesai'          => 'Selesai',
            'selesai by admin' => 'Diselesaikan Admin',
            'Lanjut Nanti'     => 'Berlanjut',
            'Lanjutan'         => 'Dilanjutkan',
        ];
        return $statusMap[$status] ?? ucfirst($status);
    }
}
if (!function_exists('formatWhatsappNumber')) {
    function formatWhatsappNumber($number) {
        if (substr($number, 0, 1) === '0') {
            return '62' . substr($number, 1);
        }
        return $number;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lanjut Nanti - Mobile View</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header, .card-footer { background-color: #ffffff; }
        .customer-name { font-weight: 600; color: #0d6efd; }
    </style>
</head>

<body class="bg-light">
    <?php include "bottom-navbar.php"; // Menggunakan bottom navbar untuk mobile ?>

    <main class="main-content">
        <div class="container-fluid py-3">

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lanjut Nanti</h4>
                    <p class="text-muted mb-0">Tugas yang dijadwalkan ulang untuk dilanjutkan.</p>
                </div>
            </div>

            <?php
            // --- Kueri PHP untuk mengambil data (Logika sama, dengan perbaikan kecil) ---
            $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor
                    FROM kegiatan k
                    LEFT JOIN customer c ON k.customer_id = c.id
                    WHERE k.status IN ('Lanjutan', 'Lanjut Nanti')
                    AND DATE(k.jadwal) >= CURDATE()
                    AND k.deleted_at IS NULL
                    GROUP BY k.kode
                    ORDER BY k.jadwal ASC"; // Mengurutkan dari yang terdekat

            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // --- Persiapan Variabel (Data diproses dan diamankan di sini) ---
                    $kodeTransaksi = $row['kode'];
                    $apv = $row['approval'];
                    $stt = $row['status'];
                    
                    $displayStatus = translateActivityStatus($stt);
                    $displayKegiatan = htmlspecialchars($row['kegiatan']);
                    $displayCustomerName = htmlspecialchars($row['nama_customer']);
                    $whatsappNumber = formatWhatsappNumber($row['cust_nomor']);
            ?>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted"><?= $displayKegiatan ?></small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark"><?= $displayStatus ?></span>
                    </div>
                </div>

                <div class="card-body py-2">
                    <p class="mb-1">
                        <i class="fa-regular fa-calendar-days fa-fw text-secondary me-2"></i><strong>Jadwal:</strong> <?= date("d M Y, H:i", strtotime($row["jadwal"])) ?>
                    </p>
                     <p class="mb-1">
                        <i class="fa-solid fa-hashtag fa-fw text-secondary me-2"></i><strong>Kode:</strong> <?= htmlspecialchars($kodeTransaksi) ?>
                    </p>
                    <div class="mt-2">
                        <strong class="small d-block mb-1"><i class="fa-solid fa-users-gear fa-fw text-secondary me-2"></i>Teknisi Bertugas:</strong>
                        <?php
                        // Query untuk mendapatkan teknisi (logika sama, dibuat lebih aman)
                        $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.deleted_at IS NULL AND k.kode = ? AND k.id = (SELECT MAX(sub_k.id) FROM kegiatan sub_k WHERE sub_k.kode = ?) GROUP BY tk.teknisi_id";
                        $stmtTeknisi = mysqli_prepare($conn, $sqlTeknisi);
                        mysqli_stmt_bind_param($stmtTeknisi, "ss", $kodeTransaksi, $kodeTransaksi);
                        mysqli_stmt_execute($stmtTeknisi);
                        $resultTeknisi = mysqli_stmt_get_result($stmtTeknisi);
                        
                        if (mysqli_num_rows($resultTeknisi) > 0) {
                            while ($rowTeknisi = mysqli_fetch_assoc($resultTeknisi)) {
                                echo "<span class='badge bg-info-subtle text-info-emphasis me-1'>" . htmlspecialchars($rowTeknisi['nama_teknisi']) . "</span>";
                            }
                        } else {
                            echo "<span class='text-muted small'>N/A</span>";
                        }
                        mysqli_stmt_close($stmtTeknisi);
                        ?>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="btn-group w-100">
                    <?php if ($apv == 'no' && $stt == 'Lanjutan') : ?>
                        <a class="btn btn-outline-info view-btn" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>">
                            <i class="fa-solid fa-eye"></i> Detail
                        </a>
                        <button type="button" class="btn btn-success approve-btn" data-id="<?= htmlspecialchars($kodeTransaksi) ?>">
                            <i class="fa-solid fa-check-circle"></i> Setujui
                        </button>
                    <?php else : ?>
                        <a class="btn btn-outline-info view-btn" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>">
                            <i class="fa-solid fa-eye"></i> Detail
                        </a>
                        <button class="btn btn-outline-warning edit-btn" data-id="<?= htmlspecialchars($kodeTransaksi) ?>">
                            <i class="fa-solid fa-edit"></i> Edit
                        </button>
                        <a class="btn btn-outline-danger delete-btn" href="delete-kegiatan.php?kode=<?= urlencode($kodeTransaksi) ?>" onclick="return confirm('Yakin ingin menghapus data ini?');">
                           <i class="fa-solid fa-trash-alt"></i> Hapus
                        </a>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
                } // End while loop
            } else {
                echo "<div class='alert alert-info text-center'><i class='fa-solid fa-circle-info me-2'></i>Tidak ada pekerjaan yang dijadwalkan ulang.</div>";
            }
            ?>
        </div>
    </main>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $(".view-btn").click(function(e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        });

        $(".edit-btn").click(function() {
            var kodeTransaksi = $(this).data("id");
            window.location.href = "edit_kegiatan.php?kode_transaksi=" + kodeTransaksi;
        });

        $(".approve-btn").click(function() {
            var kodeTransaksi = $(this).data("id");
            if (confirm('Anda yakin ingin menyetujui jadwal lanjutan ini?')) {
                window.location.href = "apv_kegiatan.php?kode_transaksi=" + kodeTransaksi;
            }
        });

        // Event handler untuk .delete-btn tidak perlu script khusus karena sudah ada href dan onclick confirm.
        // Jika delete menggunakan AJAX, scriptnya akan ada di sini.
    });
    </script>
</body>
</html>