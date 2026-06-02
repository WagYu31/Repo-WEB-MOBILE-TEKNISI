<?php
include "conn.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE kegiatan SET reason = ? WHERE id = ?");
        $stmt->bind_param("si", $reason, $id);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "invalid id";
    }
} else {
    echo "invalid request";
}
?>