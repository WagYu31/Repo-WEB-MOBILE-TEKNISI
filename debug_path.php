<?php
header('Content-Type: text/plain');
echo "LS /www/wwwroot:\n";
echo shell_exec('ls -la /www/wwwroot');
echo "\nFind api_sales_task.php:\n";
echo shell_exec('find /www/wwwroot -name "api_sales_task.php"');
