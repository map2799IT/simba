<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use Phar;
use PharData;
use RuntimeException;
use ZipArchive;

class StudentSpreadsheetService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        if ($extension === 'csv' || $extension === 'txt') {
            return $this->readCsv($path);
        }

        if ($extension !== 'xlsx') {
            throw new RuntimeException(
                'Format file harus XLSX atau CSV.'
            );
        }

        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->readWithPhpSpreadsheet($path);
        }

        return $this->readNativeXlsx($path);
    }

    /**
     * @param Collection<int, Student> $students
     */
    public function export(Collection $students): string
    {
        $headers = [
            'NISN',
            'NIS',
            'Nama',
            'Kode Jurusan',
            'Jurusan',
            'Kelas',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Email',
            'Telepon',
            'Tahun Ajaran',
            'Status Data',
            'Status Akun',
        ];

        $rows = [$headers];

        foreach ($students as $student) {
            $rows[] = [
                $student->nisn,
                $student->nis,
                $student->name,
                $student->workshop?->code,
                $student->workshop?->name,
                $student->class_name,
                $student->genderLabel(),
                $student->birth_date?->format('Y-m-d'),
                $student->email,
                $student->phone,
                $student->school_year,
                $student->is_active ? 'Aktif' : 'Nonaktif',
                $student->user_id ? 'Terdaftar' : 'Belum Registrasi',
            ];
        }

        return $this->writeNativeXlsx(
            $rows,
            'Data Siswa'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readWithPhpSpreadsheet(string $path): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Data Siswa')
            ?? $spreadsheet->getActiveSheet();

        $raw = $sheet->toArray(null, true, true, false);

        return $this->rowsToAssociative($raw);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $path): array
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $firstLine = fgets($stream);
        rewind($stream);

        $delimiter = ';';

        if (is_string($firstLine)) {
            $counts = [
                ';' => substr_count($firstLine, ';'),
                ',' => substr_count($firstLine, ','),
                "\t" => substr_count($firstLine, "\t"),
            ];

            arsort($counts);
            $delimiter = (string) array_key_first($counts);
        }

        $rows = [];

        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            if (isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $this->rowsToAssociative($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readNativeXlsx(string $path): array
    {
        $archive = null;
        $closeArchive = false;

        if (class_exists(ZipArchive::class)) {
            $archive = new ZipArchive();

            if ($archive->open($path) !== true) {
                throw new RuntimeException('File XLSX tidak dapat dibuka.');
            }

            $closeArchive = true;
        } elseif (class_exists(PharData::class)) {
            try {
                $archive = new PharData($path);
            } catch (\Throwable $error) {
                throw new RuntimeException(
                    'File XLSX tidak dapat dibuka: '.$error->getMessage(),
                    previous: $error
                );
            }
        } else {
            throw new RuntimeException(
                'PHP tidak memiliki dukungan ZipArchive maupun PharData.'
            );
        }

        try {
            $sharedStrings = $this->sharedStrings($archive);
            $sheetPath = $this->dataSheetPath($archive);
            $xml = $this->archiveGet($archive, $sheetPath);

            if ($xml === false) {
                throw new RuntimeException('Sheet Data Siswa tidak ditemukan.');
            }

            preg_match_all(
                '/<(?:[A-Za-z0-9_]+:)?row\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?row>/si',
                $xml,
                $rowMatches
            );

            $rawRows = [];

            foreach ($rowMatches[1] ?? [] as $rowXml) {
                preg_match_all(
                    '/<(?:[A-Za-z0-9_]+:)?c\b([^>]*?)(?:\/\s*>|>(.*?)<\/(?:[A-Za-z0-9_]+:)?c>)/si',
                    $rowXml,
                    $cellMatches,
                    PREG_SET_ORDER
                );

                $values = [];

                foreach ($cellMatches as $cellMatch) {
                    $attributes = $this->xmlAttributes($cellMatch[1] ?? '');
                    $reference = $attributes['r'] ?? 'A1';
                    $type = $attributes['t'] ?? '';
                    $cellXml = $cellMatch[2] ?? '';
                    $column = $this->columnIndex($reference);
                    $value = '';

                    if ($type === 'inlineStr') {
                        preg_match_all(
                            '/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/si',
                            $cellXml,
                            $textMatches
                        );
                        $value = implode('', array_map(
                            fn (string $part): string => $this->decodeXml($part),
                            $textMatches[1] ?? []
                        ));
                    } else {
                        preg_match(
                            '/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si',
                            $cellXml,
                            $valueMatch
                        );
                        $rawValue = $this->decodeXml($valueMatch[1] ?? '');

                        if ($type === 's') {
                            $value = $sharedStrings[(int) $rawValue] ?? '';
                        } elseif ($type === 'b') {
                            $value = $rawValue === '1' ? '1' : '0';
                        } else {
                            $value = $rawValue;
                        }
                    }

                    $values[$column] = $value;
                }

                if ($values === []) {
                    continue;
                }

                $max = max(array_keys($values));
                $normalized = [];

                for ($index = 0; $index <= $max; $index++) {
                    $normalized[] = $values[$index] ?? '';
                }

                $rawRows[] = $normalized;
            }

            return $this->rowsToAssociative($rawRows);
        } finally {
            if ($closeArchive && $archive instanceof ZipArchive) {
                $archive->close();
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive|PharData $archive): array
    {
        $xml = $this->archiveGet($archive, 'xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        preg_match_all(
            '/<(?:[A-Za-z0-9_]+:)?si\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?si>/si',
            $xml,
            $itemMatches
        );

        $strings = [];

        foreach ($itemMatches[1] ?? [] as $itemXml) {
            preg_match_all(
                '/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/si',
                $itemXml,
                $textMatches
            );

            $strings[] = implode('', array_map(
                fn (string $part): string => $this->decodeXml($part),
                $textMatches[1] ?? []
            ));
        }

        return $strings;
    }

    private function dataSheetPath(ZipArchive|PharData $archive): string
    {
        $workbookXml = $this->archiveGet($archive, 'xl/workbook.xml');
        $relationshipsXml = $this->archiveGet(
            $archive,
            'xl/_rels/workbook.xml.rels'
        );

        if ($workbookXml === false || $relationshipsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        preg_match_all(
            '/<(?:[A-Za-z0-9_]+:)?Relationship\b([^>]*)\/?\s*>/si',
            $relationshipsXml,
            $relationshipMatches
        );

        $map = [];

        foreach ($relationshipMatches[1] ?? [] as $attributesText) {
            $attributes = $this->xmlAttributes($attributesText);

            if (isset($attributes['Id'], $attributes['Target'])) {
                $map[$attributes['Id']] = $attributes['Target'];
            }
        }

        preg_match_all(
            '/<(?:[A-Za-z0-9_]+:)?sheet\b([^>]*)\/?\s*>/si',
            $workbookXml,
            $sheetMatches
        );

        $selectedRelationshipId = null;
        $fallbackRelationshipId = null;

        foreach ($sheetMatches[1] ?? [] as $attributesText) {
            $attributes = $this->xmlAttributes($attributesText);
            $relationshipId = $attributes['r:id']
                ?? $attributes['id']
                ?? null;

            if ($relationshipId === null) {
                continue;
            }

            $fallbackRelationshipId ??= $relationshipId;

            if (strtolower(trim($attributes['name'] ?? '')) === 'data siswa') {
                $selectedRelationshipId = $relationshipId;
                break;
            }
        }

        $target = $map[$selectedRelationshipId ?? $fallbackRelationshipId]
            ?? 'worksheets/sheet1.xml';
        $target = ltrim($target, '/');

        return str_starts_with($target, 'xl/')
            ? $target
            : 'xl/'.$target;
    }

    /**
     * @return array<string, string>
     */
    private function xmlAttributes(string $attributesText): array
    {
        preg_match_all(
            '/([A-Za-z_][A-Za-z0-9_.:-]*)\s*=\s*(["\'])(.*?)\2/s',
            $attributesText,
            $matches,
            PREG_SET_ORDER
        );

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[$match[1]] = $this->decodeXml($match[3]);
        }

        return $attributes;
    }

    private function decodeXml(string $value): string
    {
        return html_entity_decode(
            $value,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
    }

    private function archiveGet(
        ZipArchive|PharData $archive,
        string $name
    ): string|false {
        if ($archive instanceof ZipArchive) {
            return $archive->getFromName($name);
        }

        if (! isset($archive[$name])) {
            return false;
        }

        return $archive[$name]->getContent();
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^([A-Z]+)/i', $reference, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function rowsToAssociative(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $headers = array_map(
            fn (mixed $header): string => $this->normalizeHeader((string) $header),
            array_shift($rows)
        );

        $result = [];

        foreach ($rows as $rowIndex => $row) {
            $values = [];

            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }

                $values[$header] = isset($row[$column])
                    ? trim((string) $row[$column])
                    : '';
            }

            $hasValue = false;

            foreach ($values as $value) {
                if ($value !== '') {
                    $hasValue = true;
                    break;
                }
            }

            if (! $hasValue) {
                continue;
            }

            $values['_row'] = $rowIndex + 2;
            $result[] = $values;
        }

        return $result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '/', '.'], '_', $header);
        $header = preg_replace('/[^a-z0-9_]+/', '', $header) ?? '';

        return match ($header) {
            'nama_siswa', 'nama_lengkap' => 'nama',
            'bengkel', 'kode_bengkel' => 'kode_jurusan',
            'kelas_rombel', 'rombel' => 'kelas',
            'jk', 'kelamin' => 'jenis_kelamin',
            'tgl_lahir', 'tanggallahir' => 'tanggal_lahir',
            'no_hp', 'nomor_hp', 'hp' => 'telepon',
            'tahun_pelajaran' => 'tahun_ajaran',
            'status_aktif', 'status_data' => 'aktif',
            default => $header,
        };
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function writeNativeXlsx(array $rows, string $sheetName): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'simba-students-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Gagal membuat file sementara export.');
        }

        @unlink($temporaryPath);
        $xlsxPath = $temporaryPath.'.xlsx';
        $escapedSheetName = htmlspecialchars(
            $sheetName,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="'.$escapedSheetName.'" sheetId="1" r:id="rId1"/></sheets>
</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/></cellXfs>
</styleSheet>',
            'xl/worksheets/sheet1.xml' => $this->sheetXml($rows),
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>SIMBA</Application></Properties>',
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>SIMBA</dc:creator><cp:lastModifiedBy>SIMBA</cp:lastModifiedBy></cp:coreProperties>',
        ];

        if (class_exists(ZipArchive::class)) {
            $archive = new ZipArchive();

            if ($archive->open(
                $xlsxPath,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            ) !== true) {
                throw new RuntimeException('Gagal membuat file XLSX.');
            }

            foreach ($files as $name => $contents) {
                $archive->addFromString($name, $contents);
            }

            $archive->close();

            return $xlsxPath;
        }

        if (! class_exists(PharData::class)) {
            throw new RuntimeException(
                'PHP tidak memiliki dukungan ZipArchive maupun PharData.'
            );
        }

        $zipPath = $temporaryPath.'.zip';

        try {
            $archive = new PharData(
                $zipPath,
                0,
                null,
                Phar::ZIP
            );

            foreach ($files as $name => $contents) {
                $archive->addFromString($name, $contents);
            }

            unset($archive);

            if (! rename($zipPath, $xlsxPath)) {
                throw new RuntimeException(
                    'Gagal menyelesaikan file XLSX.'
                );
            }
        } catch (\Throwable $error) {
            @unlink($zipPath);
            @unlink($xlsxPath);

            throw new RuntimeException(
                'Gagal membuat XLSX: '.$error->getMessage(),
                previous: $error
            );
        }

        return $xlsxPath;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<cols>';

        for ($column = 1; $column <= 13; $column++) {
            $width = in_array($column, [3, 5], true) ? 28 : 18;
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="'.$excelRow.'">';

            foreach (array_values($row) as $columnIndex => $value) {
                $reference = $this->columnLetters($columnIndex + 1).$excelRow;
                $escaped = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $style = $excelRow === 1 ? ' s="1"' : '';
                $xml .= '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData><autoFilter ref="A1:M'.max(1, count($rows)).'"/></worksheet>';

        return $xml;
    }

    private function columnLetters(int $column): string
    {
        $letters = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $column = intdiv($column - 1, 26);
        }

        return $letters;
    }
}
