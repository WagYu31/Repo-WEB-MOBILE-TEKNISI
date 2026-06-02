<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kode_transaksi = $_POST["kode_transaksi"];

    $query = "SELECT k.*, c.id_cust AS id_customer, c.nama AS nama_cust, c.nomor_tlp AS no_cust FROM kegiatan k
              LEFT JOIN customer c
              ON k.id_cust = c.id_cust
              WHERE kode_transaksi = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $kode_transaksi);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                "nama_customer" => $row["nama_cust"],
                "nomor_customer" => $row["no_cust"],
                "status_kegiatan" => $row["status"]
            ];
            echo json_encode($data);
        } else {
            echo json_encode(["error" => "Tidak ada hasil yang cocok untuk kode transaksi: $kode_transaksi"]);
        }
    } else {
        echo json_encode(["error" => "Gagal menjalankan kueri: " . $stmt->error]);
    }
} else {
    echo json_encode(["error" => "Permintaan tidak valid."]);
}

$stmt->close();
$conn->close();

?>
