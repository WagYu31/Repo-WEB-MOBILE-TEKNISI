<?php
/**
 * Patch PelaksanaanController to use orderBy('id', 'desc')->first() instead of first()
 * to support tasks that have multiple records (e.g. historical / rescheduled tasks)
 * Run on server: sudo php patch_first_to_latest.php
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// Backup original
copy($file, $file . '.bak_first_to_latest');
echo "Backup saved to: $file.bak_first_to_latest\n";

// Replace 1: clockOut function
$target1 = '    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
        ->where(\'teknisi_id\', $request->teknisi_id)
        ->first();';
$replacement1 = '    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
        ->where(\'teknisi_id\', $request->teknisi_id)
        ->orderBy(\'id\', \'desc\')
        ->first();';

// Replace 2: reportPelaksanaan JSON/Base64 function
$target2 = '            $ck = PelaksanaanKegiatan::where(\'kegiatan_id\', $j[\'kegiatan_id\'])->where(\'teknisi_id\', $j[\'teknisi_id\'])->first();';
$replacement2 = '            $ck = PelaksanaanKegiatan::where(\'kegiatan_id\', $j[\'kegiatan_id\'])->where(\'teknisi_id\', $j[\'teknisi_id\'])->orderBy(\'id\', \'desc\')->first();';

// Replace 3: reportPelaksanaan standard function
$target3 = '                    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
                        ->where(\'teknisi_id\', $request->teknisi_id)->first();';
$replacement3 = '                    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
                        ->where(\'teknisi_id\', $request->teknisi_id)->orderBy(\'id\', \'desc\')->first();';

// Replace 4: lanjutNanti function
$target4 = '                    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
                        ->where(\'teknisi_id\', $request->teknisi_id)->first();';
$replacement4 = '                    $checkKegiatan = PelaksanaanKegiatan::where(\'kegiatan_id\', $request->kegiatan_id)
                        ->where(\'teknisi_id\', $request->teknisi_id)->orderBy(\'id\', \'desc\')->first();';

$newContent = str_replace($target1, $replacement1, $content, $count1);
$newContent = str_replace($target2, $replacement2, $newContent, $count2);
$newContent = str_replace($target3, $replacement3, $newContent, $count3);
$newContent = str_replace($target4, $replacement4, $newContent, $count4);

echo "Replacement 1 count: $count1\n";
echo "Replacement 2 count: $count2\n";
echo "Replacement 3 count: $count3\n";
echo "Replacement 4 count: $count4\n";

if ($count1 > 0 || $count2 > 0 || $count3 > 0 || $count4 > 0) {
    file_put_contents($file, $newContent);
    echo "SUCCESS: PelaksanaanController.php updated to orderBy('id', 'desc')->first()\n";
} else {
    echo "ERROR: No replacements made.\n";
}
?>
