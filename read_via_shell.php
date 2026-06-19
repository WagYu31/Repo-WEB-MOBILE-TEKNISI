<?php
header('Content-Type: text/plain');
$src = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';
$dest = '/tmp/PelaksanaanController.php';

echo "Copying $src to $dest...\n";
$out = shell_exec("cp " . escapeshellarg($src) . " " . escapeshellarg($dest) . " 2>&1");
echo "Copy output: " . var_export($out, true) . "\n";

if (file_exists($dest)) {
    echo "Success! File size: " . filesize($dest) . " bytes.\n\n";
    
    $content = file_get_contents($dest);
    $lines = explode("\n", $content);
    
    $start = 109; // index for line 110
    $end = 304;   // index for line 305
    
    for ($i = $start; $i <= $end && $i < count($lines); $i++) {
        printf("%4d: %s\n", $i + 1, $lines[$i]);
    }
    
    unlink($dest);
} else {
    echo "Failed to copy file.\n";
}
?>
