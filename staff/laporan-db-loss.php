<?php
// Set default date
$current_date = isset($_GET['cariTgl']) && !empty($_GET['cariTgl']) ? $_GET['cariTgl'] : date("Y-m-d");
$search = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// Set locale for Indonesian date format
setlocale(LC_TIME, 'id_ID.utf8');
?>

<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-top-left-radius: 0;">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-md-flex d-block align-items-center">
                    <div class="col-md-6 col-12">
                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                    </div>
                    <div class="col-md-6 col-12">
                        <form method="GET" action="">
                            <div class="mb-3 d-flex flex-row justify-content-start align-items-center">
                                <input type="text" name="cari" class="form-control border w-60 w-md-80" 
                                       style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius:0; padding:7.5px;" 
                                       placeholder="Cari Nama Customer" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button class="btn bg-gradient-primary w-40 w-md-20" 
                                        style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius:0; margin-top:15.5px;" 
                                        type="submit">Cari</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Build and execute query
        $sql = "SELECT k.*, k.kode AS kode_transaksi, GROUP_CONCAT(DISTINCT t.nama_teknisi SEPARATOR ', ') AS teknisi_list, 
                       c.id AS id_cust, c.nama AS nama_cust, c.telp AS cust_nomor, 
                       i.no_invoice AS invoice, i.kode, i.nominal_invoice
                FROM kegiatan k
                LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
                LEFT JOIN customer c ON k.customer_id = c.id
                LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
                LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
                WHERE k.status != 'waiting' 
                AND (k.paid IS NULL OR k.paid = '')
                AND k.deleted_at IS NULL
                AND p.kode IS NULL";

        if (!empty($search)) {
            $sql .= " AND c.nama LIKE ?";
        }

        $sql .= " GROUP BY k.kode
                ORDER BY k.jadwal DESC";

        $stmt = $conn->prepare($sql);

        if (!empty($search)) {
            $search_param = "%$search%";
            $stmt->bind_param("s", $search_param);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $jumlah_data = $result->num_rows;
        
        // Display count
        echo "<div class='text-s mb-3 ms-4'>Jumlah data ditemukan: $jumlah_data</div>";
        ?>

        <div class="card-body pb-0 p-0">
            <ul class="list-group">
                <?php if ($jumlah_data > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $jadwal = $row['jadwal'];
                        $formattedDate = ($jadwal && $jadwal != '0000-00-00 00:00:00') 
                            ? strftime("%A, %d %B %Y", strtotime($jadwal)) 
                            : '-';
                        $formattedTime = ($jadwal && $jadwal != '0000-00-00 00:00:00') 
                            ? date("H:i", strtotime($jadwal)) 
                            : '-';
                    ?>
                        <li class="list-group-item mb-3 border-radius-lg">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="font-weight-bold mb-1">
                                        <a href="view-kegiatan.php?kode_transaksi=<?= htmlspecialchars($row['kode_transaksi']) ?>" 
                                           class="text-info" target="_blank">
                                            <?= htmlspecialchars($row['nama_cust']) ?>
                                        </a>
                                    </h5>
                                    <span style="text-transform: capitalize;">
                                        <?= htmlspecialchars($row['kegiatan']) ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Tanggal Request</small>
                                    <div class="font-weight-bold"><?= $formattedDate ?></div>
                                    <div class="text-sm"><?= $formattedTime ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($row['keterangan'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Keterangan:</small>
                                    <p class="mb-0"><?= htmlspecialchars($row['keterangan']) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <small class="text-muted">Teknisi:</small>
                                <p class="mb-0"><?= !empty($row['teknisi_list']) ? htmlspecialchars($row['teknisi_list']) : 'Belum ada teknisi' ?></p>
                            </div>
                            
                            <div class="mt-2 text-end">
                                <small class="text-muted">Kode: <?= htmlspecialchars($row['kode_transaksi']) ?></small>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item text-center py-4">
                        Tidak ada data yang ditemukan
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>