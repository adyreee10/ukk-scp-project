<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SpreadsheetImportService
{
    /**
     * Membaca file yang diunggah (.xlsx atau .csv) dan mengembalikannya dalam bentuk array asosiatif
     *
     * @param UploadedFile $file
     * @return array
     */
    public function parse(UploadedFile $file, ?string $sheetType = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xlsm', 'xls', 'xltx'])) {
            $rows = $this->parseXlsx($file, $extension, $sheetType);
        } else {
            // Default: Perlakukan sebagai CSV
            $rows = $this->parseCsv($file->getRealPath());
        }

        return $this->formatToAssoc($rows);
    }

    /**
     * Parsing file CSV dengan auto-detect pemisah (koma atau titik koma) dan BOM UTF-8
     */
    protected function parseCsv(string $path): array
    {
        $rows = [];
        $content = file_get_contents($path);

        // Hapus UTF-8 BOM jika ada
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Normalisasi newline
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        // Deteksi pemisah pada baris pertama (biasanya ';' di Excel versi Indonesia, ',' di versi global)
        $firstLine = $lines[0] ?? '';
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = str_getcsv($line, $delimiter);
            $rows[] = array_map('trim', $row);
        }

        return $rows;
    }

    /**
     * Menjalankan parser python (scripts/parse_school_doc.py) untuk membaca berkas Excel/PDF secara presisi
     */
    public function runPythonParser(string $path, ?string $extension = null): ?array
    {
        $scriptPath = base_path('scripts/parse_school_doc.py');
        if (!file_exists($scriptPath)) {
            return null;
        }

        try {
            $cmd = ['python', $scriptPath, $path];
            if ($extension) {
                $cmd[] = $extension;
            }
            $process = new \Symfony\Component\Process\Process($cmd);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful()) {
                return null;
            }

            $output = trim($process->getOutput());
            $json = json_decode($output, true);
            if (is_array($json) && ($json['status'] ?? '') === 'success') {
                return $json;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Python parser warning: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Membaca seluruh sheet dari file spreadsheet (.xlsx atau .csv)
     * Mengembalikan associative array: ['SheetName' => [ [row], [row]... ]]
     *
     * @param UploadedFile|string $file
     * @param string|null $forcedExtension
     * @return array<string, array<int, array<int, string>>>
     */
    public function parseAllSheets(UploadedFile|string $file, ?string $forcedExtension = null): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = $forcedExtension;

        if (!$extension) {
            if ($file instanceof UploadedFile) {
                $extension = strtolower($file->getClientOriginalExtension());
            } else {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            }
        }

        // Jika ekstensi tmp atau tidak dikenal, periksa magic bytes file
        if (in_array($extension, ['tmp', '']) || !in_array($extension, ['xlsx', 'xlsm', 'xltx', 'xls', 'csv', 'txt'])) {
            $handle = @fopen($path, 'rb');
            if ($handle) {
                $magic = fread($handle, 8);
                fclose($handle);
                if (str_starts_with($magic, "PK\x03\x04")) {
                    $extension = 'xlsx';
                } elseif (str_starts_with($magic, "\xd0\xcf\x11\xe0")) {
                    $extension = 'xls';
                }
            }
        }

        if (in_array($extension, ['xlsx', 'xlsm', 'xltx', 'xls'])) {
            // 1. Coba gunakan parser python (openpyxl / xlrd) untuk membaca seluruh sheet dengan sempurna
            $pyResult = $this->runPythonParser($path, $extension);
            if ($pyResult && isset($pyResult['sheets']) && is_array($pyResult['sheets']) && !empty($pyResult['sheets'])) {
                return $pyResult['sheets'];
            }

            // 2. Fallback ke ZipArchive murni PHP yang membaca seluruh worksheet jika XLSX
            if (in_array($extension, ['xlsx', 'xlsm', 'xltx'])) {
                $zipSheets = $this->parseAllSheetsXlsxZip($path);
                if (!empty($zipSheets)) {
                    return $zipSheets;
                }
            }
        }

        // CSV: default satu sheet
        return ['Sheet1' => $this->parseCsv($path)];
    }

    /**
     * Parsing file XLSX murni menggunakan ZipArchive bawaan PHP untuk SEMUA sheet di dalam berkas
     */
    protected function parseAllSheetsXlsxZip(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // 1. Baca Shared Strings
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent) {
            $xml = simplexml_load_string($sharedXmlContent);
            if ($xml) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string) $val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca daftar nama sheet dari workbook.xml dan relasinya di workbook.xml.rels
        $sheetFiles = []; // [ 'SheetName' => 'xl/worksheets/sheet1.xml' ]
        $wbXmlContent = $zip->getFromName('xl/workbook.xml');
        $wbRelsXmlContent = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($wbXmlContent && $wbRelsXmlContent) {
            $wbXml = simplexml_load_string($wbXmlContent);
            $relsXml = simplexml_load_string($wbRelsXmlContent);

            $relMap = [];
            if ($relsXml && isset($relsXml->Relationship)) {
                foreach ($relsXml->Relationship as $rel) {
                    $rId = (string) $rel['Id'];
                    $target = (string) $rel['Target'];
                    $cleanTarget = ltrim($target, '/');
                    $relMap[$rId] = str_starts_with($cleanTarget, 'xl/') ? $cleanTarget : 'xl/' . $cleanTarget;
                }
            }

            if ($wbXml && isset($wbXml->sheets->sheet)) {
                foreach ($wbXml->sheets->sheet as $s) {
                    $sName = (string) $s['name'];
                    $rId = (string) $s->attributes('r', true)['id'];
                    if (!$rId) {
                        $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    }
                    if (isset($relMap[$rId])) {
                        $sheetFiles[$sName] = $relMap[$rId];
                    }
                }
            }
        }

        // Jika metadata relasi kosong, scan langsung seluruh worksheet di dalam zip
        if (empty($sheetFiles)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'] ?? '';
                if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#i', $name, $sm)) {
                    $sheetFiles['Sheet' . $sm[1]] = $name;
                }
            }
        }

        $allSheets = [];
        foreach ($sheetFiles as $sheetName => $xmlPath) {
            $content = $zip->getFromName($xmlPath);
            if (!$content) continue;

            $sheetXml = simplexml_load_string($content);
            if (!$sheetXml || !isset($sheetXml->sheetData)) continue;

            $matrix = [];
            foreach ($sheetXml->sheetData->row as $row) {
                $rowData = [];
                foreach ($row->c as $c) {
                    $cellRef = (string) $c['r'];
                    $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                    $colIndex = $this->columnLetterToIndex($colLetters);

                    $cellType = (string) $c['t'];
                    $val = isset($c->v) ? (string) $c->v : '';

                    if ($cellType === 's') {
                        $val = $sharedStrings[(int) $val] ?? $val;
                    } elseif ($cellType === 'inlineStr' && isset($c->is->t)) {
                        $val = (string) $c->is->t;
                    }

                    $rowData[$colIndex] = trim($val);
                }

                if (!empty($rowData)) {
                    $maxCol = max(array_keys($rowData));
                    $normalizedRow = [];
                    for ($ci = 0; $ci <= $maxCol; $ci++) {
                        $normalizedRow[$ci] = $rowData[$ci] ?? '';
                    }
                    $matrix[] = $normalizedRow;
                }
            }

            $allSheets[$sheetName] = $matrix;
        }

        $zip->close();
        return $allSheets;
    }

    /**
     * Parsing file XLSX murni menggunakan ZipArchive bawaan PHP
     */
    protected function parseXlsx(UploadedFile|string $file, ?string $extension = null, ?string $sheetType = null): array
    {
        $allSheets = $this->parseAllSheets($file, $extension);
        if (empty($allSheets)) {
            return [];
        }

        if ($sheetType === 'teachers') {
            $allSheets = $this->filterTeacherSheets($allSheets);
        }

        // Jika terdapat sheet komprehensif/gabungan ("Semua Jadwal" / "Semua Data"), utamakan sheet tersebut
        // agar tidak menduplikasi baris dari sheet pecahan (misal sheet per Putaran)
        if (count($allSheets) > 1) {
            foreach ($allSheets as $sheetName => $sheetRows) {
                $normName = strtolower(trim((string)$sheetName));
                if (str_contains($normName, 'semua') || str_contains($normName, 'all') || str_contains($normName, 'gabung') || str_contains($normName, 'lengkap')) {
                    return $sheetRows;
                }
            }
        }

        // Gabungkan seluruh baris dari semua sheet terpilih
        $combined = [];
        foreach ($allSheets as $sheetRows) {
            foreach ($sheetRows as $r) {
                $combined[] = $r;
            }
        }

        return $combined;
    }

    /**
     * Menyaring lembar kerja (worksheet) khusus data guru
     * Otomatis mengabaikan sheet Tata Usaha, TU, Tendik, Satpam, Outsourching, dll.
     */
    public function filterTeacherSheets(array $allSheets): array
    {
        if (empty($allSheets)) {
            return [];
        }

        // Singkirkan sheet kosong terlebih dahulu
        $nonEmptySheets = [];
        foreach ($allSheets as $name => $rows) {
            if (!empty($rows)) {
                $nonEmptySheets[$name] = $rows;
            }
        }
        if (empty($nonEmptySheets)) {
            return [];
        }

        // 1. Cek apakah ada sheet khusus bernama Guru / Dewan Guru / Data Guru / Pengajar
        $guruSheets = [];
        foreach ($nonEmptySheets as $sheetName => $rows) {
            $norm = strtolower(trim((string)$sheetName));
            if (str_contains($norm, 'guru') || str_contains($norm, 'pendidik') || str_contains($norm, 'pengajar')) {
                if (!str_contains($norm, 'non') && !str_contains($norm, 'bukan') && !str_contains($norm, 'tu')) {
                    $guruSheets[$sheetName] = $rows;
                }
            }
        }

        // Jika sheet guru teridentifikasi dari nama sheet, gunakan HANYA sheet tersebut!
        if (!empty($guruSheets)) {
            return $guruSheets;
        }

        // 1b. Jika nama sheet generic (misal Sheet1, Data), periksa header/konten baris 1-4
        foreach ($nonEmptySheets as $sheetName => $rows) {
            $headerText = '';
            for ($r = 0; $r < min(5, count($rows)); $r++) {
                $headerText .= ' ' . implode(' ', (array)$rows[$r]);
            }
            $normHeader = strtolower($headerText);
            if ((str_contains($normHeader, 'nominatif guru') || str_contains($normHeader, 'data guru') || str_contains($normHeader, 'wali kelas')) 
                && !str_contains($normHeader, 'tata usaha') && !str_contains($normHeader, 'satpam') && !str_contains($normHeader, 'outsourch')) {
                $guruSheets[$sheetName] = $rows;
            }
        }

        if (!empty($guruSheets)) {
            return $guruSheets;
        }

        // 2. Jika tidak ada sheet bernama 'guru', buang sheet yang secara eksplisit adalah Tata Usaha / Tendik / Outsourching / Jarik
        $filtered = [];
        $excluded = [
            'tata usaha', 'tu', 'staf tu', 'staff tu', 'pegawai tu',
            'administrasi', 'tendik', 'tenaga kependidikan', 'non guru',
            'karyawan', 'satpam', 'keamanan', 'kebersihan', 'penjaga',
            'outsourching', 'outsourcing', 'jarik', 'tanda terima'
        ];

        foreach ($nonEmptySheets as $sheetName => $rows) {
            $norm = strtolower(trim((string)$sheetName));
            $isExcluded = false;
            foreach ($excluded as $kw) {
                if ($norm === $kw || str_contains($norm, $kw)) {
                    $isExcluded = true;
                    break;
                }
            }
            if (!$isExcluded) {
                $filtered[$sheetName] = $rows;
            }
        }

        return !empty($filtered) ? $filtered : $nonEmptySheets;
    }

    /**
     * Konversi huruf kolom Excel (A, B, C, ..., AA) ke index integer 0, 1, 2...
     */
    protected function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $index - 1);
    }

    /**
     * Mengubah baris mentah menjadi array asosiatif berdasarkan baris header yang dideteksi secara cerdas
     */
    protected function formatToAssoc(array $matrix): array
    {
        if (count($matrix) < 2) {
            return [];
        }

        // Cari baris header yang paling sesuai (bisa di baris ke-0, 1, 2, dll karena judul laporan/sekolah)
        $headerRowIndex = 0;
        $maxScore = 0;
        $searchLimit = min(15, count($matrix));

        $knownKeywords = [
            'nama', 'name', 'nis', 'nisn', 'nip', 'kelas', 'class', 'rombel', 
            'ruang', 'room', 'mapel', 'subject', 'guru', 'teacher', 'hari', 
            'day', 'jam', 'time', 'email', 'phone', 'hp', 'alamat', 'address', 
            'role', 'peran', 'status', 'jk', 'gender', 'no', 'nomor', 'putaran',
            'bk', 'wali', 'walikelas', 'konseling', 'jabatan', 'pendidikan', 'golongan'
        ];

        for ($i = 0; $i < $searchLimit; $i++) {
            $row = $matrix[$i];
            if (empty($row)) continue;

            $score = 0;
            $nonEmptyCells = 0;
            foreach ($row as $cell) {
                $cellStr = strtolower(trim((string)$cell));
                if ($cellStr === '') continue;
                $nonEmptyCells++;

                $cleanCell = preg_replace('/[^a-z0-9]/', '', $cellStr);
                foreach ($knownKeywords as $kw) {
                    if ($cleanCell === $kw || str_contains($cleanCell, $kw)) {
                        $score += 2;
                        break;
                    }
                }
            }

            // Jika menemukan baris dengan kecocokan kata kunci header yang tinggi
            if ($score > $maxScore && $score >= 2) {
                $maxScore = $score;
                $headerRowIndex = $i;
            } elseif ($score === 0 && $nonEmptyCells >= 3 && $maxScore === 0) {
                // Alternatif baris teks pertama dengan banyak kolom
                $headerRowIndex = $i;
            }
        }

        // Pangkas baris judul/kop sebelum baris header
        if ($headerRowIndex > 0) {
            $matrix = array_slice($matrix, $headerRowIndex);
        }

        // Ambil header baris dan normalisasikan
        $rawHeaders = array_shift($matrix);
        $headers = [];
        foreach ($rawHeaders as $idx => $h) {
            $slug = strtolower(trim((string)$h));
            $slug = str_replace([' ', '-', '/', '.'], '_', $slug);
            $slug = preg_replace('/[^a-z0-9_]/', '', $slug);
            $headers[$idx] = $slug;
        }

        $result = [];
        foreach ($matrix as $row) {
            $item = [];
            $hasData = false;

            foreach ($headers as $idx => $headerName) {
                if (empty($headerName)) {
                    continue;
                }
                $val = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                if ($val !== '') {
                    $hasData = true;
                }
                $item[$headerName] = $val;
            }

            if ($hasData) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Download format template CSV dengan UTF-8 BOM (langsung rapi saat dibuka di Microsoft Excel)
     */
    public function downloadTemplate(string $filename, array $headers, array $samples): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $samples) {
            $handle = fopen('php://output', 'w');
            
            // Tambahkan UTF-8 BOM agar Excel membukanya tanpa karakter aneh
            fputs($handle, "\xEF\xBB\xBF");

            // Tulis Header
            fputcsv($handle, $headers, ';');

            // Tulis Baris Contoh
            foreach ($samples as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
