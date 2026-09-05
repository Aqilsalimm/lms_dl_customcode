<?php

declare(strict_types=1);

namespace App\Services\Spreadsheet;

use ZipArchive;
use Exception;

class DrasthaXlsxReader
{
    /**
     * Parse an XLSX file and return rows as an array of cell values.
     *
     * @param string $file_path
     * @return array
     * @throws Exception
     */
    public static function read(string $file_path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Ekstensi PHP ZipArchive tidak aktif pada server Anda.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file_path) !== true) {
            throw new Exception('Gagal membuka file Excel (.xlsx). File mungkin rusak.');
        }

        // Parse Shared Strings
        $shared_strings = [];
        $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared_strings_xml) {
            // Remove namespaces and prefixes to avoid "mc for Ignorable" errors
            $shared_strings_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $shared_strings_xml);
            $shared_strings_xml = preg_replace('/[a-z0-9]+:([a-z0-9]+)/i', '$1', $shared_strings_xml);
            $xml = @simplexml_load_string($shared_strings_xml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $shared_strings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            if (isset($r->t)) {
                                $text .= (string)$r->t;
                            }
                        }
                        $shared_strings[] = $text;
                    } else {
                        $shared_strings[] = '';
                    }
                }
            }
        }

        // Parse Sheet 1
        $rows = [];
        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet_xml) {
            // Remove namespaces and prefixes to avoid "mc for Ignorable" errors
            $sheet_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sheet_xml);
            $sheet_xml = preg_replace('/[a-z0-9]+:([a-z0-9]+)/i', '$1', $sheet_xml);
            $xml = @simplexml_load_string($sheet_xml);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $row_idx = intval($row['r']) - 1;
                    $row_data = [];
                    
                    foreach ($row->c as $c) {
                        $cell_ref = (string)$c['r'];
                        preg_match('/^[A-Z]+/i', $cell_ref, $matches);
                        if (empty($matches)) continue;
                        
                        $col_str = $matches[0];
                        $col_idx = self::colNameToIndex($col_str);
                        
                        $val = '';
                        $type = (string)$c['t'];
                        
                        if ($type === 's') {
                            $s_idx = intval($c->v);
                            $val = isset($shared_strings[$s_idx]) ? $shared_strings[$s_idx] : '';
                        } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                            $val = (string)$c->is->t;
                        } elseif (isset($c->v)) {
                            $val = (string)$c->v;
                        }
                        
                        $row_data[$col_idx] = $val;
                    }

                    if (!empty($row_data)) {
                        $max_col = max(array_keys($row_data));
                        for ($i = 0; $i <= $max_col; $i++) {
                            if (!isset($row_data[$i])) {
                                $row_data[$i] = '';
                            }
                        }
                        ksort($row_data);
                    }
                    
                    $rows[$row_idx] = $row_data;
                }
            }
        }
        $zip->close();

        if (!empty($rows)) {
            $max_row = max(array_keys($rows));
            for ($i = 0; $i <= $max_row; $i++) {
                if (!isset($rows[$i])) {
                    $rows[$i] = [];
                }
            }
            ksort($rows);
        }

        return $rows;
    }

    private static function colNameToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }
        return $index - 1;
    }
}
