<?php
header('Content-Type: text/plain');
echo "List of directories in /www/wwwroot:\n";
$items = scandir('/www/wwwroot');
foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    $fullPath = '/www/wwwroot/' . $item;
    echo " - $item (" . (is_dir($fullPath) ? "DIR" : "FILE") . ")\n";
    if (is_dir($fullPath)) {
        // Look inside up to 2 levels
        $sub = @scandir($fullPath);
        if ($sub) {
            foreach ($sub as $s) {
                if ($s == '.' || $s == '..') continue;
                echo "    + $s\n";
            }
        }
    }
}
