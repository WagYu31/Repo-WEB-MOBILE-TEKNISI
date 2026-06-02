<?php
header('Content-Type: application/json');
include 'conn.php'; // Hubungkan ke database

// Ambil tanggal dari parameter GET
$selectedDate = $_GET['tanggal'] ?? null;

if (!$selectedDate) {
    echo json_encode([]); // Kembalikan array kosong jika tidak ada tanggal
    exit;
}

// Query untuk mengambil semua kegiatan teknisi pada tanggal yang dipilih
// Query ini menggabungkan beberapa tabel untuk mendapatkan data yang relevan
$sql = "SELECT
            t.teknisi_id,
            c.nama AS nama_customer,
            k.jadwal
        FROM team_kegiatan t
        JOIN kegiatan k ON t.kegiatan_id = k.id
        JOIN customer c ON k.customer_id = c.id
        WHERE 
            DATE(k.jadwal) = ? 
            AND t.deleted_at IS NULL
            AND k.deleted_at IS NULL
        ORDER BY t.teknisi_id, k.jadwal ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Kelompokkan jadwal berdasarkan teknisi_id
        $schedules[$row['teknisi_id']][] = [
            'customer' => $row['nama_customer'],
            'waktu' => date('H:i', strtotime($row['jadwal']))
        ];
    }
}

$stmt->close();
$conn->close();

// Kembalikan data dalam format JSON
echo json_encode($schedules);
?>