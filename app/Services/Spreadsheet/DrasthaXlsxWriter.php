<?php

declare(strict_types=1);

namespace App\Services\Spreadsheet;

use ZipArchive;

class DrasthaXlsxWriter
{
    /**
     * Generate an XLSX spreadsheet file from headers and data rows.
     *
     * @param string $filename
     * @param array $headers
     * @param array $data
     * @return bool
     */
    public static function generate(string $filename, array $headers, array $data): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // [Content_Types].xml
        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $content_types);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Tutor LMS Import Template" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/_rels/workbook.xml.rels
        $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);

        // xl/worksheets/sheet1.xml
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>';

        $colLetter = function(int $idx): string {
            $letter = '';
            while ($idx >= 0) {
                $letter = chr(65 + ($idx % 26)) . $letter;
                $idx = intval($idx / 26) - 1;
            }
            return $letter;
        };

        // Headers
        $sheet .= '<row r="1">';
        foreach ($headers as $c_idx => $header_text) {
            $ref = $colLetter($c_idx) . '1';
            $esc_text = htmlspecialchars($header_text, ENT_QUOTES, 'UTF-8');
            $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $esc_text . '</t></is></c>';
        }
        $sheet .= '</row>';

        // Data Rows
        foreach ($data as $r_idx => $row_data) {
            $row_num = $r_idx + 2;
            $sheet .= '<row r="' . $row_num . '">';
            foreach ($row_data as $c_idx => $val) {
                $ref = $colLetter($c_idx) . $row_num;
                $esc_val = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $esc_val . '</t></is></c>';
            }
            $sheet .= '</row>';
        }

        $sheet .= '  </sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $zip->close();
        return true;
    }
}
