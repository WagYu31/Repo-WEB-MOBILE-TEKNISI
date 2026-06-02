<div class="col-lg-12" id="printable-content">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="text-uppercase mb-0 font-weight-bold text-primary">
                        <i class="material-icons opacity-10 align-middle mb-1">summarize</i> Rekapitulasi Bulanan Teknisi
                    </h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" action="" class="row g-2 justify-content-md-end no-print">
                        <div class="col-auto">
                            <input type="month" class="form-control" name="cariBulanTahun" value="<?= $current_date; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-info px-4 mb-0">Cari</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-4 pt-3">
    <?php
        $daftar_bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $timestamp = strtotime($current_date);
        $bulan = $daftar_bulan[(int)date('m', $timestamp)];
        $tahun = date('Y', $timestamp);
    ?>
    <p class="text-muted mb-0">Periode Laporan: <strong><?= $bulan . ' ' . $tahun; ?></strong></p>
</div>
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0" id="data-tek">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-uppercase text-dark text-xs font-weight-bolder opacity-7 ps-4">Teknisi</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7">Kegiatan</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7">Selesai</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7">Inv.</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7">Total Fee (30k)</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7">Total Pendapatan</th>
                            <th class="text-center text-uppercase text-dark text-xs font-weight-bolder opacity-7 pe-4">Total Bonus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $grand_total_fee = 0;
                        $grand_total_pendapatan = 0;
                        $grand_total_bonus = 0;

                        $sql_tek = "SELECT id, nama FROM teknisi ORDER BY nama ASC";
                        $res_tek = mysqli_query($conn, $sql_tek);

                        $bulan_filter = date('m', strtotime($current_date));
                        $tahun_filter = date('Y', strtotime($current_date));

                        while ($row = mysqli_fetch_assoc($res_tek)) {
                            $idT = $row['id'];
                            $namaT = $row['nama'];

                            $sql_keg = "SELECT COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id = ? AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL AND tk.deleted_at IS NULL";
                            $stmt = $conn->prepare($sql_keg); $stmt->bind_param("isi", $idT, $bulan_filter, $tahun_filter); $stmt->execute();
                            $total_kegiatan = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                            $sql_sel = "SELECT COUNT(DISTINCT k.kode) as total FROM kegiatan k JOIN team_kegiatan tk ON k.id = tk.kegiatan_id WHERE tk.teknisi_id = ? AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.status = 'selesai' AND k.deleted_at IS NULL AND tk.deleted_at IS NULL";
                            $stmt = $conn->prepare($sql_sel); $stmt->bind_param("isi", $idT, $bulan_filter, $tahun_filter); $stmt->execute();
                            $total_selesai = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                            $sql_inv_cnt = "SELECT COUNT(DISTINCT kode) as total FROM pendapatan_kegiatan WHERE teknisi_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL";
                            $stmt = $conn->prepare($sql_inv_cnt); $stmt->bind_param("is", $idT, $current_date); $stmt->execute();
                            $total_inv_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                            $sql_inc = "SELECT SUM(pendapatan) as total FROM pendapatan_kegiatan WHERE teknisi_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL";
                            $stmt = $conn->prepare($sql_inc); $stmt->bind_param("is", $idT, $current_date); $stmt->execute();
                            $pendapatan_invoice = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                            $total_fee_30k = 0;
                            $sql_fee_logic = "SELECT k.kode FROM kegiatan k 
                                              WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ?
                                              AND k.paid REGEXP '^[0-9]+$' 
                                              AND k.deleted_at IS NULL
                                              AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
                                              GROUP BY k.kode";
                            $stmt_f = $conn->prepare($sql_fee_logic); $stmt_f->bind_param("si", $bulan_filter, $tahun_filter); $stmt_f->execute();
                            $res_f = $stmt_f->get_result();
                            
                            while ($f = $res_f->fetch_assoc()) {
                                $kd = $f['kode'];
                                $sql_active = "SELECT COUNT(DISTINCT teknisi_id) as jml FROM pelaksanaan_kegiatan WHERE kode = ? AND waktu_mulai IS NOT NULL";
                                $st_act = $conn->prepare($sql_active); $st_act->bind_param("s", $kd); $st_act->execute();
                                $jml_aktif = $st_act->get_result()->fetch_assoc()['jml'] ?? 0;
                                
                                if ($jml_aktif > 0) {
                                    $sql_is_me = "SELECT 1 FROM pelaksanaan_kegiatan WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL LIMIT 1";
                                    $st_me = $conn->prepare($sql_is_me); $st_me->bind_param("si", $kd, $idT); $st_me->execute();
                                    if ($st_me->get_result()->num_rows > 0) {
                                        $total_fee_30k += (30000 / $jml_aktif);
                                    }
                                }
                            }

                            $sql_bonus_fix = "SELECT SUM(bonus) as total FROM pendapatan_fix WHERE teknisi_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? AND deleted_at IS NULL";
                            $stmt = $conn->prepare($sql_bonus_fix); $stmt->bind_param("is", $idT, $current_date); $stmt->execute();
                            $bonus_fix = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                            $grand_total_fee += $total_fee_30k;
                            $grand_total_pendapatan += $pendapatan_invoice;
                            $grand_total_bonus += $bonus_fix;
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="list-kegiatan-teknisi.php?cariBulanTahun=<?= $current_date; ?>&idTek=<?= $idT; ?>" class="text-sm font-weight-bold text-dark mb-0"><?= $namaT; ?></a>
                                </td>
                                <td class="text-center text-sm"><?= $total_kegiatan; ?></td>
                                <td class="text-center text-sm"><?= $total_selesai; ?></td>
                                <td class="text-center text-sm"><?= $total_inv_count; ?></td>
                                <td class="text-center text-sm font-weight-bold text-success">Rp <?= number_format($total_fee_30k, 0, ',', '.'); ?></td>
                                <td class="text-center text-sm font-weight-bold">Rp <?= number_format($pendapatan_invoice, 0, ',', '.'); ?></td>
                                <td class="text-center text-sm font-weight-bold pe-4">Rp <?= number_format($bonus_fix, 0, ',', '.'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot class="bg-info text-white">
                        <tr>
                            <td class="ps-4 font-weight-bold text-sm">TOTAL KESELURUHAN</td>
                            <td colspan="3"></td>
                            <td class="text-center font-weight-bold text-sm text-light">Rp <?= number_format($grand_total_fee, 0, ',', '.'); ?></td>
                            <td class="text-center font-weight-bold text-sm text-light">Rp <?= number_format($grand_total_pendapatan, 0, ',', '.'); ?></td>
                            <td class="text-center font-weight-bold text-sm pe-4 text-light">Rp <?= number_format($grand_total_bonus, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>