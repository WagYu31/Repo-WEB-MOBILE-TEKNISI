<?php
/**
 * Patch PelaksanaanController to handle all 5 base64 image fields
 * Run: php patch_base64_images.php
 */

$file = '/www/wwwroot/teknisi-api-github.id-giti.com/app/Http/Controllers/AppTeknisi/Pelaksanaan/PelaksanaanController.php';

if (!file_exists($file)) {
    echo "ERROR: File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// Check if already patched
if (strpos($content, 'extraImages') !== false) {
    echo "Already patched! No changes needed.\n";
    exit(0);
}

// Find the insertion point: after "$data['image_1'] = $fn . '.jpg';" and its closing "}"
// Insert before "$ck = PelaksanaanKegiatan::where"
$search = "\$ck = PelaksanaanKegiatan::where('kegiatan_id', \$j['kegiatan_id'])->where('teknisi_id', \$j['teknisi_id'])->first();";

$replacement = '// Handle image_dua through image_lima
            $extraImages = [\'image_dua\' => \'image_2\', \'image_tiga\' => \'image_3\', \'image_empat\' => \'image_4\', \'image_lima\' => \'image_5\'];
            foreach ($extraImages as $inputField => $dbField) {
                if (!empty($j[$inputField])) {
                    $decodedExtra = base64_decode($j[$inputField], true);
                    if ($decodedExtra !== false) {
                        $fnExtra = $this->generateRandomString();
                        Storage::disk(\'public\')->put(\'image/\' . $fnExtra . \'.jpg\', $decodedExtra);
                        $data[$dbField] = $fnExtra . \'.jpg\';
                    }
                }
            }

            ' . $search;

$newContent = str_replace($search, $replacement, $content);

if ($newContent === $content) {
    echo "ERROR: Could not find insertion point. File may have different formatting.\n";
    exit(1);
}

// Backup original
file_put_contents($file . '.bak', $content);
echo "Backup saved to: $file.bak\n";

// Write patched file
file_put_contents($file, $newContent);
echo "SUCCESS: Controller patched! All 5 base64 image fields are now supported.\n";
