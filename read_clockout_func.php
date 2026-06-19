<?php
/**
 * Read clockOut function lines in PelaksanaanController
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found at $file\n";
    exit(1);
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

$start = 109; // Line 110 is index 109
$end = 304;   // Line 305 is index 304

echo "=== Lines 110 to 305 ===\n";
for ($i = $start; $i <= $end && $i < count($lines); $i++) {
    printf("%4d: %s\n", $i + 1, $lines[$i]);
}
echo "========================\n";
?>
