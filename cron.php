<?php
$folderPath = './uploads/';

if (is_dir($folderPath)) {
    $files = scandir($folderPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $folderPath . $file;
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
?>
