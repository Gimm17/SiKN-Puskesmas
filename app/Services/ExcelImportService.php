<?php

namespace App\Services;

use App\Models\Desa;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelImportService
{
    /** Map nama desa lowercase → desa_id */
    private array $desaMap = [];

    public function __construct()
    {
        Desa::all()->each(function ($d) {
            $this->desaMap[strtolower(trim($d->nama))] = $d->id;
        });
    }

    /**
     * Load spreadsheet ONCE and return it along with sheet names.
     * Returns ['spreadsheet' => ..., 'sheetNames' => [...]]
     */
    public function loadFile(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true); // Drastically improves performance and memory
        $spreadsheet = $reader->load($filePath);
        
        return [
            'spreadsheet' => $spreadsheet,
            'sheetNames'  => $spreadsheet->getSheetNames(),
        ];
    }

    private function detectKnColumns($sheet): array
    {
        // Default fallback (Format 2023)
        $knCols = ['l_idx' => 22, 'p_idx' => 23, 'l_lbl' => 'W', 'p_lbl' => 'X'];
        
        $maxRow = min($sheet->getHighestRow(), 20); // Scan first 20 rows
        for ($r = 1; $r <= $maxRow; $r++) {
            foreach (range('A', 'Z') as $i => $col) {
                $cell = $sheet->getCell($col . $r);
                try {
                    $val = trim((string)$cell->getCalculatedValue());
                } catch (\Exception $e) {
                    $val = trim((string)$cell->getValue());
                }
                
                // Remove all whitespace/newlines and check
                $cleanVal = strtoupper(preg_replace('/\s+/', '', $val));
                if (strpos($cleanVal, 'KNLENGKAP') !== false) {
                    $knCols['l_idx'] = $i;
                    $knCols['p_idx'] = $i + 1;
                    $knCols['l_lbl'] = $col;
                    $knCols['p_lbl'] = chr(ord($col) + 1); // Works fine up to Z
                    return $knCols;
                }
            }
        }
        return $knCols;
    }

    /**
     * Parse sheet for LH & KN data.
     * Accepts a pre-loaded spreadsheet to avoid double file load.
     */
    public function parseFromSpreadsheet(Spreadsheet $spreadsheet, int $bulan, int $tahun, ?string $sheetName = null): array
    {
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
        if (!$sheet) {
            return ['rows' => [], 'knCols' => ['l_lbl' => 'W', 'p_lbl' => 'X']];
        }

        $results = [];
        $foundHeader = false;

        $knCols = $this->detectKnColumns($sheet);
        $lIdx = $knCols['l_idx'];
        $pIdx = $knCols['p_idx'];

        // Prevent infinite loops on corrupted files with formatting to row 1,048,576
        $highestRow = $sheet->getHighestRow();
        $maxRow = min($highestRow, 1000);

        foreach ($sheet->getRowIterator(1, $maxRow) as $row) {
            $ci = $row->getRowIndex();
            $cells = $this->getRowValues($ci, $sheet);

            $desaNama = strtolower(trim($cells[1] ?? ''));

            if (!$foundHeader && !isset($this->desaMap[$desaNama])) {
                continue;
            }
            $foundHeader = true;

            if (!isset($this->desaMap[$desaNama])) {
                if (count($results) >= 10) break;
                continue;
            }

            $lhL = max(0, (int)($cells[2] ?? 0));
            $lhP = max(0, (int)($cells[3] ?? 0));
            $knL = max(0, (int)($cells[$lIdx] ?? 0));
            $knP = max(0, (int)($cells[$pIdx] ?? 0));

            $results[] = [
                'desa_id'       => $this->desaMap[$desaNama],
                'nama_desa'     => strtoupper($desaNama),
                'bulan'         => $bulan,
                'tahun'         => $tahun,
                'lahir_hidup_l' => $lhL,
                'lahir_hidup_p' => $lhP,
                'kn_lengkap_l'  => $knL,
                'kn_lengkap_p'  => $knP,
                'warning'       => ($knL > $lhL || $knP > $lhP),
            ];
        }

        return [
            'rows' => $results,
            'knCols' => $knCols
        ];
    }

    /**
     * Parse file Excel (old signature – kept for backward compat).
     */
    public function parse(string $filePath, int $bulan, int $tahun, ?string $sheetName = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        return $this->parseFromSpreadsheet($spreadsheet, $bulan, $tahun, $sheetName);
    }

    /**
     * Get raw sheet data for preview using rangeToArray() (fast).
     */
    public function getRawFromSpreadsheet(Spreadsheet $spreadsheet, ?string $sheetName = null, int $maxRows = 40): array
    {
        $sheet = $sheetName
            ? ($spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet())
            : $spreadsheet->getActiveSheet();

        $totalRows      = $sheet->getHighestRow();
        $highestColumn  = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

        // Limit columns to 30 for performance
        $limitedColIndex  = min($highestColIndex, 30);
        $limitedHighestCol = Coordinate::stringFromColumnIndex($limitedColIndex);
        $previewRows      = min($totalRows, $maxRows);

        // rangeToArray with calculate formulas enabled so it doesn't show '=U14'
        $allData = $sheet->rangeToArray(
            'A1:' . $limitedHighestCol . $previewRows,
            null,   // null value for empty cells
            true,   // calculate formulas (so we get actual numbers instead of =U14)
            true,   // format data
            false   // don't use cell references as keys
        );

        $headers = [];
        for ($col = 1; $col <= $limitedColIndex; $col++) {
            $headers[] = Coordinate::stringFromColumnIndex($col);
        }

        // Convert nulls to empty string for JSON
        $rows = array_map(
            fn($row) => array_map(fn($cell) => (string)($cell ?? ''), $row),
            $allData
        );

        return [
            'headers'    => $headers,
            'rows'       => $rows,
            'totalRows'  => $totalRows,
            'totalCols'  => $highestColIndex,
            'sheetTitle' => $sheet->getTitle(),
        ];
    }

    private function getRowValues(int $rowIndex, $sheet): array
    {
        $values = [];
        foreach (range('A', 'Z') as $i => $col) {
            $cell = $sheet->getCell($col . $rowIndex);
            // Use getCalculatedValue to evaluate formulas like "=U14", fallback to getValue if it fails
            try {
                $values[$i] = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $values[$i] = $cell->getValue();
            }
        }
        return $values;
    }
}
