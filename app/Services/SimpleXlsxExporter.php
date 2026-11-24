<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Generator file Excel (.xlsx) OOXML ringan berbasis ZipArchive tanpa dependensi eksternal.
 */
class SimpleXlsxExporter
{
    /**
     * @param array<int, array{name: string, headers: array<int, string>, rows: array<int, array<int, mixed>>}> $sheets
     */
    public function download(string $filename, array $sheets): BinaryFileResponse
    {
        if (empty($sheets)) {
            $sheets = [['name' => 'Data', 'headers' => ['Info'], 'rows' => [['Tidak ada data']]]];
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpPath === false) {
            throw new \RuntimeException('Gagal membuat berkas sementara untuk ekspor Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            @unlink($tmpPath);
            throw new \RuntimeException('Gagal membuka arsip ZipArchive untuk ekspor Excel.');
        }

        $sheetFilesXml   = '';
        $workbookSheets  = '';
        $workbookRelsXml = '';
        $usedNames       = [];

        foreach ($sheets as $index => $sheet) {
            $sheetNo   = $index + 1;
            $sheetName = $this->sanitizeSheetName((string) ($sheet['name'] ?? "Sheet{$sheetNo}"), $usedNames);
            $usedNames[] = $sheetName;

            $headers = $sheet['headers'] ?? [];
            $rows    = $sheet['rows'] ?? [];

            $zip->addFromString("xl/worksheets/sheet{$sheetNo}.xml", $this->buildWorksheetXml($headers, $rows));

            $sheetFilesXml .= '<Override PartName="/xl/worksheets/sheet' . $sheetNo . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="' . $this->escapeXml($sheetName) . '" sheetId="' . $sheetNo . '" r:id="rId' . $sheetNo . '"/>';
            $workbookRelsXml .= '<Relationship Id="rId' . $sheetNo . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetNo . '.xml"/>';
        }

        $styleRelId = count($sheets) + 1;
        $workbookRelsXml .= '<Relationship Id="rId' . $styleRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXml($sheetFilesXml));
        $zip->addFromString('_rels/.rels', $this->buildRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXml($workbookSheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelsXml($workbookRelsXml));
        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());

        $zip->close();

        return response()
            ->download($tmpPath, $filename, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
                'Pragma'              => 'no-cache',
            ])
            ->deleteFileAfterSend(true);
    }

    private function sanitizeSheetName(string $name, array $usedNames): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', trim($name)) ?: 'Sheet';
        $clean = mb_substr(preg_replace('/\s+/', ' ', $clean), 0, 28);
        $base  = $clean !== '' ? $clean : 'Sheet';

        $candidate = $base;
        $suffix    = 2;
        while (in_array($candidate, $usedNames, true)) {
            $candidate = mb_substr($base, 0, 25) . ' ' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function buildWorksheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $rowNum = 1;
        if (! empty($headers)) {
            $xml .= $this->buildRowXml($rowNum, $headers, true);
            $rowNum++;
        }

        foreach ($rows as $row) {
            $xml .= $this->buildRowXml($rowNum, is_array($row) ? array_values($row) : [$row], false);
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private function buildRowXml(int $rowNum, array $cells, bool $isHeader): string
    {
        $xml = '<row r="' . $rowNum . '">';
        foreach (array_values($cells) as $colIdx => $value) {
            $ref   = $this->columnLetters($colIdx + 1) . $rowNum;
            $style = $isHeader ? ' s="1"' : '';

            if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $str = $this->sanitizeText((string) ($value ?? '-'));
                $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t>' . $this->escapeXml($str) . '</t></is></c>';
            }
        }
        $xml .= '</row>';

        return $xml;
    }

    private function columnLetters(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod     = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index   = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private function sanitizeText(string $value): string
    {
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $value);
        $cleaned = preg_replace('/\s*\r?\n\s*|\r|\t/', ' ', (string) $cleaned);
        $cleaned = trim((string) $cleaned);

        if ($cleaned === '') {
            return '-';
        }

        if ($cleaned !== '-' && preg_match('/^[=+@\-]/', $cleaned) === 1 && !is_numeric($cleaned)) {
            return "'" . $cleaned;
        }

        return $cleaned;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function buildContentTypesXml(string $sheetOverrides): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-officedocument.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $sheetOverrides
            . '</Types>';
    }

    private function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function buildWorkbookXml(string $sheetsXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelsXml(string $relsXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relsXml
            . '</Relationships>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEFF4FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}
