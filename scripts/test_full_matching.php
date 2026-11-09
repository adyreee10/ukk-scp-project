<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SpreadsheetImportService;
use App\Services\SchoolDocumentParserService;
use Illuminate\Http\UploadedFile;

$service = app(SpreadsheetImportService::class);
$docService = app(SchoolDocumentParserService::class);

$file1Path = 'C:/Users/HypeAMD/.gemini/antigravity/brain/f504e779-ec71-4e98-97ca-5f0fa45243b1/.user_uploaded/media_1794220102652_3f2362f6.xlsx';
$rows1 = $service->parse(new UploadedFile($file1Path, 'Data_Pegawai.xlsx', null, null, true), 'teachers');

$file2Path = 'C:/Users/HypeAMD/.gemini/antigravity/brain/f504e779-ec71-4e98-97ca-5f0fa45243b1/.user_uploaded/media_1794220111435_feb1fc50.xlsx';
$rows2 = $service->parse(new UploadedFile($file2Path, 'Wali_Kelas.xlsx', null, null, true), 'teachers');

// Extract teachers from File 1 (91 teachers)
$teachersFromFile1 = collect();
foreach ($rows1 as $r) {
    $no = trim($r['no'] ?? '');
    $name = trim($r['nama'] ?? '');
    if (is_numeric($no) && !empty($name)) {
        $u = new \App\Models\User();
        $u->name = $name;
        $u->nip = $r['nip'] ?? '';
        $teachersFromFile1->push($u);
    }
}

echo "Extracted teachers from File 1: " . $teachersFromFile1->count() . "\n";

// Map aliases if needed
$knownAliases = [
    'tatik herawati' => 'tatik wardayati',
];

$matchedCount = 0;
$unmatched = [];

for ($i = 0; $i < 48; $i++) {
    $r = $rows2[$i];
    $waliRaw = trim($r['nama_wali_kelas'] ?? '');
    $waliClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $waliRaw), " \t\n\r\0\x0B\"'");

    // Check alias
    $norm = $docService->stripAcademicTitles($waliClean);
    if (isset($knownAliases[$norm])) {
        $searchName = $knownAliases[$norm];
    } else {
        $searchName = $waliClean;
    }

    $matched = $docService->findTeacherByNameFlexible($searchName, $teachersFromFile1);
    if ($matched) {
        $matchedCount++;
    } else {
        $unmatched[] = "Row $i ({$r['kelas']}): '$waliClean' (norm: '$norm')";
    }
}

echo "Wali Kelas Matched: $matchedCount / 48\n";
if (!empty($unmatched)) {
    echo "Unmatched:\n";
    foreach ($unmatched as $u) echo "  $u\n";
}

// BK matching
$bkMatched = 0;
$bkUnmatched = [];
for ($i = 0; $i < 48; $i++) {
    $r = $rows2[$i];
    $bkRaw = trim($r['nama_guru_bk'] ?? '');
    $bkClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $bkRaw), " \t\n\r\0\x0B\"'");

    $matched = $docService->findTeacherByNameFlexible($bkClean, $teachersFromFile1);
    if ($matched) {
        $bkMatched++;
    } else {
        $bkUnmatched[] = "Row $i ({$r['kelas']}): '$bkClean'";
    }
}

echo "Guru BK Matched: $bkMatched / 48\n";
if (!empty($bkUnmatched)) {
    echo "Unmatched BK:\n";
    foreach ($bkUnmatched as $u) echo "  $u\n";
}

