<?php
header('Content-Type: text/plain');
echo "=== SERVER FILE CONTENT OF customer-detail.php ===\n\n";
echo file_get_contents('customer-detail.php');
?>
