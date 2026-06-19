<?php
/**
 * Read PelaksanaanController lines around the target query
 * Run on server: php info_pelaksanaan_controller.php
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found at $file\n";
    exit(1);
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

$target = "PelaksanaanKegiatan::where";
$found = false;

foreach ($lines as $index => $line) {
    if (strpos($line, $target) !== false) {
        $found = true;
        $start = max(0, $index - 15);
        $end = min(count($lines) - 1, $index + 35);
        
        echo "=== Lines " . ($start + 1) . " to " . ($end + 1) . " ===\n";
        for ($i = $start; $i <= $end; $i++) {
            printf("%4d: %s\n", $i + 1, $lines[$i]);
        }
        echo "=============================\n\n";
    }
}

if (!$found) {
    echo "Could not find '$target' in the file. Here is the file length: " . strlen($content) . " bytes.\n";
}
?>
