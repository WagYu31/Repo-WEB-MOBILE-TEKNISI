<?php
/**
 * Read reportPelaksanaan function lines
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found at $file\n";
    exit(1);
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

$start = 304; // Line 305 is 304-indexed
$end = 504;   // Line 505 is 504-indexed

echo "=== Lines 305 to 505 ===\n";
for ($i = $start; $i <= $end && $i < count($lines); $i++) {
    printf("%4d: %s\n", $i + 1, $lines[$i]);
}
echo "========================\n";
?>
