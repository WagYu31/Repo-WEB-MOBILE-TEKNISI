<?php
/**
 * Patch PelaksanaanController to prevent report upload from overwriting waktu_selesai
 * Run on server: sudo php patch_api_v2.php
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// Check if already patched in the JSON/Base64 section
if (strpos($content, 'empty($ck->waktu_selesai)') !== false) {
    echo "Already patched or partly patched! Checking standard section too...\n";
}

// 1. Target code block for JSON/Base64 reportPelaksanaan
$targetJson = '            $ck = PelaksanaanKegiatan::where(\'kegiatan_id\', $j[\'kegiatan_id\'])->where(\'teknisi_id\', $j[\'teknisi_id\'])->first();
                if ($ck) {
                    $data[\'status\'] = \'selesai\';
                $data["waktu_selesai"] = now("Asia/Jakarta");
                    $ck->update($data);';

$replacementJson = '            $ck = PelaksanaanKegiatan::where(\'kegiatan_id\', $j[\'kegiatan_id\'])->where(\'teknisi_id\', $j[\'teknisi_id\'])->first();
                if ($ck) {
                    $data[\'status\'] = \'selesai\';
                    if (empty($ck->waktu_selesai) || $ck->waktu_selesai == \'0000-00-00 00:00:00\') {
                        $data[\'waktu_selesai\'] = now("Asia/Jakarta");
                    }
                    $ck->update($data);';

$countJson = 0;
$newContent = str_replace($targetJson, $replacementJson, $content, $countJson);

if ($countJson === 0) {
    echo "WARNING: Could not patch JSON/Base64 section using exact string match. Trying regex...\n";
    
    // Fallback to regex for JSON/Base64 section
    $patternJson = '/\$ck\s*=\s*PelaksanaanKegiatan::where\(\s*[\'"]kegiatan_id[\'"]\s*,\s*\$j\[[\'"]kegiatan_id[\'"]\]\s*\)->where\(\s*[\'"]teknisi_id[\'"]\s*,\s*\$j\[[\'"]teknisi_id[\'"]\]\s*\)->first\(\)\s*;\s*if\s*\(\s*\$ck\s*\)\s*\{\s*\$data\[[\'"]status[\'"]\]\s*=\s*[\'"]selesai[\'"]\s*;\s*\$data\[["\']waktu_selesai["\']\]\s*=\s*now\("Asia\/Jakarta"\)\s*;\s*\$ck->update\(\$data\)\s*;/';
    
    $regexReplacementJson = '$ck = PelaksanaanKegiatan::where(\'kegiatan_id\', $j[\'kegiatan_id\'])->where(\'teknisi_id\', $j[\'teknisi_id\'])->first();
                if ($ck) {
                    $data[\'status\'] = \'selesai\';
                    if (empty($ck->waktu_selesai) || $ck->waktu_selesai == \'0000-00-00 00:00:00\') {
                        $data[\'waktu_selesai\'] = now("Asia/Jakarta");
                    }
                    $ck->update($data);';
                    
    $newContent = preg_replace($patternJson, $regexReplacementJson, $content, 1, $countJson);
}

if ($countJson > 0) {
    echo "SUCCESS: JSON/Base64 section patched successfully!\n";
} else {
    echo "ERROR: Could not patch JSON/Base64 section. Code might already be patched or has different formatting.\n";
}

// Backup original
copy($file, $file . '.bak_clockout_v2');
echo "Backup saved to: $file.bak_clockout_v2\n";

// Write patched file
file_put_contents($file, $newContent);
echo "SUCCESS: Controller patched! reportPelaksanaan will no longer overwrite existing waktu_selesai in JSON/Base64 uploads.\n";
?>
