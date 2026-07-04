<?php
header('Content-Type: text/plain');
echo "Content of /www/wwwroot/api-teknisi.com/public/api_sales_task.php:\n";
$lines = file('/www/wwwroot/api-teknisi.com/public/api_sales_task.php');
for ($i = 50; $i < 90 && $i < count($lines); $i++) {
    echo ($i + 1) . ": " . $lines[$i];
}
