<?php
/**
 * List functions in PelaksanaanController
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found at $file\n";
    exit(1);
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

foreach ($lines as $index => $line) {
    if (preg_match('/public\s+function\s+(\w+)/', $line, $matches)) {
        echo "Line " . ($index + 1) . ": public function " . $matches[1] . "\n";
    }
}
?>
