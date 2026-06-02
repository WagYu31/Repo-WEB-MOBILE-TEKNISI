<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_fee'])) {
    $new_fee = preg_replace('/[^0-9]/', '', $_POST['new_fee']);

    if (!is_numeric($new_fee)) {
        $response['message'] = 'Nilai fee harus berupa angka.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt_soft_delete = $conn->prepare("UPDATE noinv SET deleted_at = NOW() WHERE deleted_at IS NULL");
        $stmt_soft_delete->execute();
        $stmt_soft_delete->close();
        
        $stmt_insert = $conn->prepare("INSERT INTO noinv (nilai) VALUES (?)");
        $stmt_insert->bind_param("s", $new_fee);
        $stmt_insert->execute();
        $stmt_insert->close();

        $conn->commit();
        $response = ['success' => true, 'message' => 'Nilai fee berhasil diperbarui.'];

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
}

$conn->close();
echo json_encode($response);
?>