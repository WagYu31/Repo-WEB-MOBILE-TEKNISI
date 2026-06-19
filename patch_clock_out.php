<?php
/**
 * Patch PelaksanaanController to prevent report upload from overwriting waktu_selesai
 * Run on server: php patch_clock_out.php
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// Check if already patched
if (strpos($content, 'unset($data[\'waktu_selesai\'])') !== false) {
    echo "Already patched! No changes needed.\n";
    exit(0);
}

// Find the target code block in reportPelaksanaan using a flexible regex
$pattern = '/\$data\[[\'"]status[\'"]\]\s*=\s*[\'"]selesai[\'"]\s*;\s*\$data\[[\'"]waktu_selesai[\'"]\]\s*=\s*\$dateTime\s*;/';

$replacement = "\$data['status'] = 'selesai';
                if (empty(\$checkKegiatan->waktu_selesai) || \$checkKegiatan->waktu_selesai == '0000-00-00 00:00:00') {
                    \$data['waktu_selesai'] = \$dateTime;
                } else {
                    unset(\$data['waktu_selesai']);
                }";

$newContent = preg_replace($pattern, $replacement, $content, 1, $count);

if ($count === 0) {
    echo "ERROR: Could not find insertion point. File may have different formatting.\n";
    exit(1);
}

// Backup original
file_put_contents($file . '.bak_clockout', $content);
echo "Backup saved to: $file.bak_clockout\n";

// Write patched file
file_put_contents($file, $newContent);
echo "SUCCESS: PelaksanaanController patched! reportPelaksanaan will no longer overwrite existing waktu_selesai.\n";
?>
