<?php
// Include koneksi ke database
include 'conn.php';

if (isset($_POST['nik']) && isset($_POST['target'])) {
    $nik = $_POST['nik'];
    
    // Pastikan nilai target adalah decimal
    $target = (float) $_POST['target'];  // Casting to float

    // Update data target di tabel teknisi
    $sql = "UPDATE teknisi SET target = ?, updated_at = NOW() WHERE nik = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ds", $target, $nik); // 'd' for decimal/float, 's' for string (nik)

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
