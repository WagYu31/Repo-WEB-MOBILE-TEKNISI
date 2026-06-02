<?php
// ========================================================================
// FUNGSI-FUNGSI PEMBANTU (HELPER FUNCTIONS)
// Logika kompleks dipindahkan ke sini agar blok HTML utama bersih.
// ========================================================================

if (!function_exists('translateActivityStatus')) {
    /**
     * Menerjemahkan status mentah menjadi teks yang ramah pengguna.
     */
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
    /**
     * Memformat nomor telepon ke standar 62.
     */
    function formatWhatsappNumber($number) {
        if (substr($number, 0, 1) === '0') {
            return '62' . substr($number, 1);
        }
        return $number;
    }
}

// ========================================================================
// BLOK TAMPILAN UTAMA (<li>)
// Bagian ini sekarang hanya fokus menampilkan data yang sudah diproses.
// ========================================================================
?>
<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
    <div class="row px-4 align-items-center">
        <?php
        // --- Persiapan Variabel (Data diproses dan diamankan di sini) ---
        $displayStatus = translateActivityStatus($row['status']);
        $stt = $row['status'];
        $displayKegiatan = htmlspecialchars($row['kegiatan']);
        $displayKode = htmlspecialchars($kodeTransaksi);
        $displayInvoice = $row['invoice'] ?? 'no';
        $displayJadwalDate = date("d-m-Y", strtotime($row["jadwal"]));
        $displayJadwalTime = date("H:i", strtotime($row["jadwal"]));
        $displayCustomerId = urlencode($row['customer_id']);
        $displayCustomerName = htmlspecialchars($row['nama_customer']);
        $displayCustomerPhone = htmlspecialchars($row['cust_nomor']);
        $whatsappNumber = formatWhatsappNumber($row['cust_nomor']);
        $displayRequest = htmlspecialchars($row['request']);
        ?>

        <div class="col-6 col-md-2 mb-2 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-xs"><?= $displayStatus ?></h6>
            <span class="text-xs"><?= $displayKegiatan ?></span><br>
            <span class="text-xs font-weight-bold"><?= $displayKode ?></span>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0">
            <h6 class="mb-1 text-dark font-weight-bold text-xs"><?= $displayJadwalDate ?></h6>
            <span class="text-xs text-uppercase"><?= $displayJadwalTime ?></span>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
            <a href="customer-detail.php?id_cust=<?= $displayCustomerId ?>">
                <h6 class="text-dark font-weight-bold text-xs"><?= $displayCustomerName ?></h6>
            </a>
            <span class="text-xs text-uppercase">
                <a href="https://api.whatsapp.com/send?phone=<?= $whatsappNumber ?>" target="_blank"><?= $displayCustomerPhone ?></a>
            </span>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-center text-md-center d-flex justify-content-start align-items-center flex-column">
            <?php
            // [KEAMANAN] Query teknisi diubah menjadi Prepared Statement
            // Catatan: Ini masih merupakan N+1 Query yang kurang efisien. Idealnya data teknisi digabung di query utama.
            $sqlTeknisi = "
                SELECT tk.teknisi_id, tk.nama_teknisi
                FROM team_kegiatan tk
                JOIN kegiatan k ON tk.kegiatan_id = k.id
                WHERE tk.deleted_at IS NULL AND k.kode = ? AND k.id = (SELECT MAX(sub_k.id) FROM kegiatan sub_k WHERE sub_k.kode = ?)
                GROUP BY tk.teknisi_id";
            
            $stmtTeknisi = mysqli_prepare($conn, $sqlTeknisi);
            mysqli_stmt_bind_param($stmtTeknisi, "ss", $kodeTransaksi, $kodeTransaksi);
            mysqli_stmt_execute($stmtTeknisi);
            $resultTeknisi = mysqli_stmt_get_result($stmtTeknisi);
            
            while ($rowTeknisi = mysqli_fetch_assoc($resultTeknisi)) {
                echo "<a href='list-kegiatan-teknisi.php?idTek=" . urlencode($rowTeknisi['teknisi_id']) . "'><h6 class='mb-0 text-dark font-weight-bold text-xs'>" . htmlspecialchars($rowTeknisi['nama_teknisi']) . "</h6></a>";
            }
            mysqli_stmt_close($stmtTeknisi);
            ?>
        </div>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-center text-md-center d-flex justify-content-center align-items-start">
            <h6 class="mb-1 text-dark font-weight-bold text-xs"><?= $displayRequest ?></h6>
        </div>

       <div class="col-6 col-md-2 mb-2 mb-md-0 d-flex justify-content-center align-items-start pt-1">
    
        <?php
        if ($apv == 'no' && $stt == 'Lanjutan') :
        ?>
            <a class="btn btn-outline-info view-btn p-1 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Lihat Detail">
                <i class="material-icons opacity-10">visibility</i>
            </a>
            <button type="button" class="btn btn-success approve-btn p-1 text-center" data-id="<?= htmlspecialchars($displayKode) ?>" title="Setujui">
                <i class="material-icons opacity-10">check</i>
            </button>
    
        <?php
        // Kondisi Lainnya (Default): Jika tidak ada kondisi di atas yang terpenuhi
        else :
        ?>
            <a class="btn btn-outline-info view-btn p-1 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" title="Lihat Detail">
                <i class="material-icons opacity-10">visibility</i>
            </a>
            <button class="btn btn-outline-warning edit-btn p-1 text-center me-1" data-id="<?= htmlspecialchars($displayKode) ?>" title="Edit">
                <i class="material-icons opacity-10">autorenew</i>
            </button>
            <a class="btn btn-outline-danger delete-btn p-1 text-center" href="delete-kegiatan.php?kode=<?= urlencode($kodeTransaksi) ?>" onclick="return confirm('Yakin ingin menghapus data ini?');" title="Hapus">
                <i class="material-icons opacity-10">delete</i>
            </a>
    
        <?php endif; ?>
    
    </div>
    </div>
</li>