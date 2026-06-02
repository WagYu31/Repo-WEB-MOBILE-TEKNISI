<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

// Check if kode parameter exists in GET request
if(isset($_GET['kode'])) {
    $kode = $_GET['kode'];
    
    // Prepare and execute DELETE query
    $deleteQuery = "DELETE FROM pendapatan_kegiatan WHERE kode = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("s", $kode);
    $deleteSuccess = $stmt->execute();
    
    // Prepare and execute the combined UPDATE query
    $updateQuery = "UPDATE kegiatan SET paid = NULL, lunas = '0000-00-00' WHERE kode = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("s", $kode);
    $updateSuccess = $stmt->execute();
    
    // Check if both operations were successful
    if($deleteSuccess && $updateSuccess) {
        // Redirect or show success message
        header("Location: lap-kegiatan-selesai.php?success=1");
        exit();
    } else {
        // Handle error
        header("Location: lap-kegiatan-selesai.php?error=1");
        exit();
    }
} else {
    // kode parameter not provided
    header("Location: lap-kegiatan-selesai.php?error=2");
    exit();
}
?>