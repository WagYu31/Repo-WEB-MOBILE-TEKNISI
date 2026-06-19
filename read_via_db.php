<?php
header('Content-Type: text/plain');
include 'conn.php';

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';
$query = "SELECT LOAD_FILE(?) AS content";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $file);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && !empty($row['content'])) {
    echo "Success!\n\n";
    $content = $row['content'];
    $lines = explode("\n", $content);
    
    // We want to see more lines just in case. Let's show lines 1 to 500
    $start = 0; 
    $end = 500;   
    
    for ($i = $start; $i <= $end && $i < count($lines); $i++) {
        printf("%4d: %s\n", $i + 1, $lines[$i]);
    }
} else {
    echo "LOAD_FILE returned empty. MySQL user may not have FILE privilege or secure_file_priv is set.\n\n";
    $res = $conn->query("SHOW VARIABLES LIKE 'secure_file_priv'");
    if ($res) {
        $row_priv = $res->fetch_assoc();
        print_r($row_priv);
    }
}
?>
