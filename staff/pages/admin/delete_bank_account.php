<?php
session_start();
include "../../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idBank = $_POST["idBank"];

    // Update database to set nomor_rekening to NULL
    $updateQuery = "UPDATE bank_account SET nomor_rekening = NULL WHERE id_bank = $idBank";

    if (mysqli_query($conn, $updateQuery)) {
        // Success
        $_SESSION["success_message"] = "Nomor rekening berhasil dihapus.";
    } else {
        // Error
        $_SESSION["error_message"] = "Error updating record: " . mysqli_error($conn);
    }
} else {
    // Invalid request method
    $_SESSION["error_message"] = "Invalid request method";
}

// Redirect back to the original page
header("Location: tagihan.php");
exit();
?>
