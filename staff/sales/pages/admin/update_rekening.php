<?php
include "../../conn.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $bankId = $_POST["bankSelect"];
    $nomorRekening = $_POST["nomorRekening"];
    $atasNama = $_POST["atasNama"];
    
    $updateQuery = "UPDATE bank_account SET atas_nama = '$atasNama', nomor_rekening = '$nomorRekening' WHERE id_bank = $bankId";


    if (mysqli_query($conn, $updateQuery)) {
        // Success: Redirect to tagihan.php
        header("Location: tagihan.php");
        exit();
    } else {
        // Error: Store error message in session and redirect to tagihan.php
        $_SESSION["error_message"] = "Error updating record: " . mysqli_error($conn);
        header("Location: tagihan.php");
        exit();
    }

    // Close database connection
    mysqli_close($conn);
} else {
    // Invalid request method: Redirect to tagihan.php with an error message
    $_SESSION["error_message"] = "Invalid request method";
    header("Location: tagihan.php");
    exit();
}
?>
