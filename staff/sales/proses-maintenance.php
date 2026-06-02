<?php
include "../conn.php";
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if data is sent from the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $message = $_POST['message'];

    // Prepare SQL statement to insert data into maintenance table
    $sql = "INSERT INTO maintenance (nama, message) VALUES ('$nama', '$message')";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to maintenance page with success alert
        header('Location: maintenance.php?success=1');
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close database connection
$conn->close();
?>
