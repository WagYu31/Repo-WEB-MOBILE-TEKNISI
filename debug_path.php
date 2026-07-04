<?php
header('Content-Type: text/plain');
$dirs = glob('/www/wwwroot/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    echo "Scanning Dir: $dir\n";
    try {
        $it = new RecursiveDirectoryIterator($dir);
        foreach(new RecursiveIteratorIterator($it) as $file) {
            if (basename($file) == 'api_sales_task.php') {
                echo "  Found: $file (Last modified: " . date("Y-m-d H:i:s", filemtime($file)) . ")\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
