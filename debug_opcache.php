<?php
header('Content-Type: text/plain');
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OPcache reset successfully!\n";
    } else {
        echo "OPcache reset failed.\n";
    }
} else {
    echo "opcache_reset function does not exist.\n";
}
