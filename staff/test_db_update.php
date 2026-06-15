<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "--- ALL COLUMNS OF kegiatan TABLE ---\n";
$res = $conn->query("SHOW COLUMNS FROM kegiatan");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Default: {$row['Default']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
