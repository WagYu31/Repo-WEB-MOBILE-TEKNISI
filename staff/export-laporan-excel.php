<?php
include "conn.php";
include "session.php";

if (!isset($_GET['bulan']) || !isset($_GET['tahun']) || !is_numeric($_GET['bulan']) || !is_numeric($_GET['tahun'])) {
    die("Error: Bulan dan Tahun tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];

setlocale(LC_TIME, 'id_ID');
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
$nama_bulan = strftime('%B', mktime(0, 0, 0, $bulan, 1));
$filename = "Laporan Kegiatan - " . $nama_bulan . " " . $tahun . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';
echo '<h3>Laporan Kegiatan Lengkap - Periode: ' . $nama_bulan . ' ' . $tahun . '</h3>';
echo '<table border="1">';
echo '<thead>
        <tr>
            <th rowspan="2">Customer</th>
            <th rowspan="2">Request</th>
            <th rowspan="2">No. Invoice</th>
            <th rowspan="2">Nominal Invoice</th>
            <th rowspan="2">Status Bayar</th>
            <th colspan="5">Rincian Teknisi</th>
        </tr>
        <tr>
            <th>Teknisi</th>
            <th>Pendapatan Teknisi</th>
            <th>Tanggal Kerja</th>
            <th>Absen Mulai</th>
            <th>Absen Selesai</th>
        </tr>
      </thead>';
echo '<tbody>';

$sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.created_at, k.lunas, k.paid, c.nama AS nama_cust
            FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
            WHERE MONTH(k.created_at) = ? AND YEAR(k.created_at) = ? AND k.deleted_at IS NULL
            GROUP BY k.kode ORDER BY k.created_at ASC";

$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("ii", $bulan, $tahun);
$stmt_main->execute();
$result_main = $stmt_main->get_result();

if ($result_main->num_rows > 0) {
    while ($row_main = $result_main->fetch_assoc()) {
        $kodeTransaksi = $row_main['kode_transaksi'];
        $is_manual_fee = is_numeric($row_main['paid']);
        
        $sql_invoice = "SELECT no_invoice, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
        $stmt_invoice = $conn->prepare($sql_invoice);
        $stmt_invoice->bind_param("s", $kodeTransaksi);
        $stmt_invoice->execute();
        $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
        $stmt_invoice->close();

        $sql_count_active = "SELECT COUNT(DISTINCT teknisi_id) as total_aktif 
                            FROM pelaksanaan_kegiatan 
                            WHERE kode = ? AND waktu_mulai IS NOT NULL";
        $stmt_count = $conn->prepare($sql_count_active);
        $stmt_count->bind_param("s", $kodeTransaksi);
        $stmt_count->execute();
        $res_count = $stmt_count->get_result()->fetch_assoc();
        $jumlah_teknisi_aktif = $res_count['total_aktif'] ?? 0;
        $stmt_count->close();

        $sql_team = "SELECT t.id, t.nama AS nama_teknisi 
                     FROM team_kegiatan tk 
                     JOIN teknisi t ON tk.teknisi_id = t.id 
                     JOIN kegiatan k ON tk.kegiatan_id = k.id 
                     WHERE k.kode = ? 
                     GROUP BY t.id";
        $stmt_team = $conn->prepare($sql_team);
        $stmt_team->bind_param("s", $kodeTransaksi);
        $stmt_team->execute();
        $res_team = $stmt_team->get_result();

        $grouped_data = [];
        $total_rows_for_job = 0;

        while ($t = $res_team->fetch_assoc()) {
            $tid = $t['id'];
            
            $sql_pendapatan = "SELECT SUM(pendapatan) as total FROM pendapatan_kegiatan WHERE kode = ? AND teknisi_id = ?";
            $stmt_p = $conn->prepare($sql_pendapatan);
            $stmt_p->bind_param("si", $kodeTransaksi, $tid);
            $stmt_p->execute();
            $pendapatan_db = $stmt_p->get_result()->fetch_assoc()['total'] ?? 0;
            $stmt_p->close();

            $sql_absensi = "SELECT DATE(waktu_mulai) as tgl, MIN(waktu_mulai) as mulai, MAX(waktu_selesai) as selesai 
                            FROM pelaksanaan_kegiatan 
                            WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL 
                            GROUP BY tgl ORDER BY tgl ASC";
            $stmt_a = $conn->prepare($sql_absensi);
            $stmt_a->bind_param("si", $kodeTransaksi, $tid);
            $stmt_a->execute();
            $res_a = $stmt_a->get_result();
            
            $absensi_list = [];
            while($a = $res_a->fetch_assoc()) {
                $absensi_list[] = $a;
            }
            $stmt_a->close();

            $final_pendapatan = $pendapatan_db;
            if ($pendapatan_db == 0 && $is_manual_fee) {
                if (count($absensi_list) > 0 && $jumlah_teknisi_aktif > 0) {
                    $final_pendapatan = 30000 / $jumlah_teknisi_aktif;
                } else {
                    $final_pendapatan = 0;
                }
            }

            $rowspan_tech = max(1, count($absensi_list));
            $grouped_data[] = [
                'nama' => $t['nama_teknisi'],
                'pendapatan' => $final_pendapatan,
                'absensi' => $absensi_list,
                'rowspan_tech' => $rowspan_tech
            ];
            $total_rows_for_job += $rowspan_tech;
        }
        $stmt_team->close();

        if (empty($grouped_data)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row_main['nama_cust']) . '</td>';
            echo '<td>' . date("d/m/Y", strtotime($row_main['created_at'])) . '</td>';
            echo '<td>' . ($invoice_data['no_invoice'] ?? ($is_manual_fee ? 'Tidak ada Invoice' : '-')) . '</td>';
            echo '<td>' . ($invoice_data ? number_format($invoice_data['nominal_invoice'], 0, ',', '.') : ($is_manual_fee ? '30.000' : '-')) . '</td>';
            echo '<td>' . ((!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'Lunas ' . date("d/m/Y", strtotime($row_main['lunas'])) : 'Belum Lunas') . '</td>';
            echo '<td colspan="5">Tidak ada data teknisi</td>';
            echo '</tr>';
        } else {
            $first_row_job = true;
            foreach ($grouped_data as $g) {
                $first_row_tech = true;
                $loop_count = count($g['absensi']) > 0 ? count($g['absensi']) : 1;

                for ($i = 0; $i < $loop_count; $i++) {
                    echo '<tr>';
                    if ($first_row_job) {
                        echo '<td rowspan="' . $total_rows_for_job . '">' . htmlspecialchars($row_main['nama_cust']) . '</td>';
                        echo '<td rowspan="' . $total_rows_for_job . '">' . date("d/m/Y", strtotime($row_main['created_at'])) . '</td>';
                        echo '<td rowspan="' . $total_rows_for_job . '">' . ($invoice_data['no_invoice'] ?? ($is_manual_fee ? 'Tidak ada Invoice' : '-')) . '</td>';
                        echo '<td rowspan="' . $total_rows_for_job . '">' . ($invoice_data ? number_format($invoice_data['nominal_invoice'], 0, ',', '.') : ($is_manual_fee ? '30.000' : '-')) . '</td>';
                        echo '<td rowspan="' . $total_rows_for_job . '">' . ((!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'Lunas ' . date("d/m/Y", strtotime($row_main['lunas'])) : 'Belum Lunas') . '</td>';
                        $first_row_job = false;
                    }

                    if ($first_row_tech) {
                        echo '<td rowspan="' . $g['rowspan_tech'] . '">' . htmlspecialchars($g['nama']) . '</td>';
                        echo '<td rowspan="' . $g['rowspan_tech'] . '">' . number_format($g['pendapatan'], 0, ',', '.') . '</td>';
                        $first_row_tech = false;
                    }

                    if (!empty($g['absensi'])) {
                        $abs = $g['absensi'][$i];
                        echo '<td>' . date("d/m/Y", strtotime($abs['tgl'] ?? '')) . '</td>';
                        echo '<td>' . ($abs['mulai'] ? date("H:i", strtotime($abs['mulai'])) : '-') . '</td>';
                        echo '<td>' . ($abs['selesai'] ? date("H:i", strtotime($abs['selesai'])) : '-') . '</td>';
                    } else {
                        echo '<td colspan="3" style="color:red;">Tidak ada data pelaksanaan</td>';
                    }
                    echo '</tr>';
                }
            }
        }
    }
}

echo '</tbody></table></body></html>';
$stmt_main->close();
$conn->close();
exit;
?>