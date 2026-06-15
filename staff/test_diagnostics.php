<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== EXTENDED CUSTOMER SEARCH ===\n\n";

$keywords = ['Alfian', 'Alvian', 'Akasia', 'Akuarium', 'PIK'];
foreach ($keywords as $kw) {
    echo "--- Search for '$kw' ---\n";
    $q = $conn->query("SELECT id, nama, telp FROM customer WHERE nama LIKE '%$kw%'");
    if ($q && $q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "No match\n";
    }
}
?>
