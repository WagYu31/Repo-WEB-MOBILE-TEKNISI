<?php
header('Content-Type: application/json');
include "conn.php";
include "session.php";

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Data utama untuk update kegiatan
    $kegiatan_id = $_POST['kegiatan_id'] ?? null;
    $lat = $_POST['lat'] ?? null;
    $lon = $_POST['lon'] ?? null;
    $rad = $_POST['rad'] ?? null;

    // Data tambahan untuk menyimpan lokasi baru
    $save_location = $_POST['save_location'] ?? null;
    $location_alias = $_POST['location_alias'] ?? null;
    $customer_id = $_POST['customer_id'] ?? null;
    $address = !empty($_POST['address']) ? $_POST['address'] : null;

    if (empty($kegiatan_id)) {
        $response['message'] = 'ID Kegiatan tidak boleh kosong.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $conn->begin_transaction();

    try {
        // 1. Perbarui lokasi pada tabel kegiatan
        $stmt_update = $conn->prepare("UPDATE kegiatan SET lat = ?, lon = ?, rad = ?, alamat_lokasi = ? WHERE id = ?");
        $stmt_update->bind_param("ssisi", $lat, $lon, $rad, $address, $kegiatan_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 2. Jika ada permintaan, simpan lokasi baru ke cust_coordinate
        if ($save_location === 'on' && !empty($location_alias) && !empty($customer_id)) {
            $stmt_insert = $conn->prepare("INSERT INTO cust_coordinate (cust_id, alias, lat, lon, rad, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param("isssis", $customer_id, $location_alias, $lat, $lon, $rad, $address);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        $conn->commit();
        $response = ['success' => true];

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Terjadi kesalahan pada database: ' . $exception->getMessage();
    }
}

$conn->close();
echo json_encode($response);
?>