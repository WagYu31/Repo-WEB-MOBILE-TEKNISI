<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Soft delete kegiatan_sales
    $stmt = $conn->prepare("UPDATE kegiatan_sales SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Soft delete juga tim sales terkait
        $conn->query("UPDATE team_kegiatan_sales SET deleted_at = NOW(), updated_at = NOW() WHERE id_kegiatan_sales = '$id' AND deleted_at IS NULL");
        
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "invalid";
}
?>
