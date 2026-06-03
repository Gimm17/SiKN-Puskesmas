<?php

namespace App\Services;

use App\Models\Desa;
use App\Models\RekapData;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExcelExportService
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Generate full report XLSX matching CONTOH LAPORAN DESEMBER.xlsx format.
     * Columns: NO | Puskesmas | Desa | LH(L,P,L+P) | KN1(L,% L, P,% P, L+P,% L+P) |
     *          KN Lengkap(L,% L, P,% P, L+P,% L+P) | Screening(L,% L, P,% P, L+P,% L+P)
     * Total 24 data columns + 3 label columns = 27 columns (A..AA)
     */
    public function generateKNLengkap(int $bulan, int $tahun): Spreadsheet
    {
        $templatePath = storage_path('app/templates/TEMPLATE-EXPORT.xlsx');
        if (!file_exists($templatePath)) {
            throw new \Exception("File template tidak ditemukan di " . $templatePath);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $bulanNama = $this->namaBulan[$bulan] ?? $bulan;
        $bulanUpper = strtoupper($bulanNama);

        $desa = Desa::orderBy('urutan')->get();
        $rekapMap = RekapData::where('bulan', $bulan)->where('tahun', $tahun)
            ->get()->keyBy('desa_id');

        // Set Header
        $sheet->setCellValue('H4', $bulanUpper);
        $sheet->setCellValue('K4', $tahun);
        $sheet->setCellValue('Q22', 'Mapane,................................' . $tahun);

        $dataRow = 10;
        foreach ($desa as $d) {
            $r   = $rekapMap->get($d->id);
            $lhL = $r?->lahir_hidup_l ?? 0;
            $lhP = $r?->lahir_hidup_p ?? 0;
            $knL = $r?->kn_lengkap_l ?? 0;
            $knP = $r?->kn_lengkap_p ?? 0;
            $scL = $r?->screening_hipotiroid_l ?? 0;
            $scP = $r?->screening_hipotiroid_p ?? 0;

            // Only insert the raw data (L and P for each category). 
            // The template already contains formulas for % and L+P sums.
            
            // Lahir Hidup
            $sheet->setCellValue('D' . $dataRow, $lhL);
            $sheet->setCellValue('E' . $dataRow, $lhP);

            // KN 1 (Assumed same as KN Lengkap based on current data structure)
            $sheet->setCellValue('G' . $dataRow, $knL);
            $sheet->setCellValue('I' . $dataRow, $knP);

            // KN Lengkap
            $sheet->setCellValue('M' . $dataRow, $knL);
            $sheet->setCellValue('O' . $dataRow, $knP);

            // Screening
            $sheet->setCellValue('S' . $dataRow, $scL);
            $sheet->setCellValue('U' . $dataRow, $scP);

            $dataRow++;
        }

        // Let PhpSpreadsheet calculate the formulas before saving if possible, or just let Excel handle it
        return $spreadsheet;
    }

    /**
     * Generate yearly export matching LAHIR HIDUP & KN LENGKAP PER BULAN.xlsx format
     */
    public function generateYearly(int $tahun): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $desa = Desa::orderBy('urutan')->get();
        $rekapRaw = RekapData::where('tahun', $tahun)->get();

        $groupedData = [];
        foreach ($rekapRaw as $r) {
            $groupedData[$r->desa_id][$r->bulan] = $r;
        }

        // --- STYLING DEFAULTS ---
        $sheet->getDefaultColumnDimension()->setWidth(8);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(3)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(25);

        // Header Style Blue
        $headerStyleBlue = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ];

        // --- ROW 1, 2, 3, 4 HEADERS ---
        // Bulan (C1 - AX1)
        $sheet->mergeCells('C1:AX1');
        $sheet->setCellValue('C1', 'Bulan');
        $sheet->getStyle('C1')->applyFromArray($headerStyleBlue);
        $sheet->getStyle('C1')->getFont()->setSize(14);

        // No and Desa merges
        $sheet->mergeCells('A1:A4');
        $sheet->setCellValue('A1', 'No');
        $sheet->mergeCells('B1:B4');
        $sheet->setCellValue('B1', 'Desa');

        // Apply style to A1:B4
        $sheet->getStyle('A1:B4')->applyFromArray($headerStyleBlue);

        // Month Headers
        $colIndex = 3; // C
        for ($m = 1; $m <= 12; $m++) {
            $monthStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $monthEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 3);
            
            // Row 2: Nama Bulan
            $sheet->mergeCells("{$monthStartCol}2:{$monthEndCol}2");
            $sheet->setCellValue("{$monthStartCol}2", $this->namaBulan[$m]);
            $sheet->getStyle("{$monthStartCol}2:{$monthEndCol}2")->applyFromArray($headerStyleBlue);
            
            // Row 3: Lahir Hidup & KN Lengkap
            $lhEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $knStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2);
            $sheet->mergeCells("{$monthStartCol}3:{$lhEndCol}3");
            $sheet->setCellValue("{$monthStartCol}3", 'Lahir Hidup');
            $sheet->mergeCells("{$knStartCol}3:{$monthEndCol}3");
            $sheet->setCellValue("{$knStartCol}3", 'KN Lengkap');
            $sheet->getStyle("{$monthStartCol}3:{$monthEndCol}3")->applyFromArray($headerStyleBlue);
            $sheet->getStyle("{$monthStartCol}3:{$monthEndCol}3")->getFont()->setSize(11);

            // Row 4: L P L P
            $sheet->setCellValue("{$monthStartCol}4", 'L');
            $sheet->setCellValue("{$lhEndCol}4", 'P');
            $sheet->setCellValue("{$knStartCol}4", 'L');
            $sheet->setCellValue("{$monthEndCol}4", 'P');
            $sheet->getStyle("{$monthStartCol}4:{$monthEndCol}4")->applyFromArray($headerStyleBlue);
            $sheet->getStyle("{$monthStartCol}4:{$monthEndCol}4")->getFont()->setSize(11);

            $colIndex += 4;
        }

        // Add "Jumlah" column at the end (AY - BB)
        $monthStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
        $monthEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 3);
        $sheet->mergeCells("{$monthStartCol}1:{$monthEndCol}2");
        $sheet->setCellValue("{$monthStartCol}1", 'Jumlah');
        $sheet->getStyle("{$monthStartCol}1:{$monthEndCol}2")->applyFromArray($headerStyleBlue);
        $sheet->getStyle("{$monthStartCol}1")->getFont()->setSize(14);

        $lhEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
        $knStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2);
        $sheet->mergeCells("{$monthStartCol}3:{$lhEndCol}3");
        $sheet->setCellValue("{$monthStartCol}3", 'Lahir Hidup');
        $sheet->mergeCells("{$knStartCol}3:{$monthEndCol}3");
        $sheet->setCellValue("{$knStartCol}3", 'KN Lengkap');
        $sheet->getStyle("{$monthStartCol}3:{$monthEndCol}3")->applyFromArray($headerStyleBlue);
        $sheet->getStyle("{$monthStartCol}3:{$monthEndCol}3")->getFont()->setSize(11);

        $sheet->setCellValue("{$monthStartCol}4", 'L');
        $sheet->setCellValue("{$lhEndCol}4", 'P');
        $sheet->setCellValue("{$knStartCol}4", 'L');
        $sheet->setCellValue("{$monthEndCol}4", 'P');
        $sheet->getStyle("{$monthStartCol}4:{$monthEndCol}4")->applyFromArray($headerStyleBlue);
        $sheet->getStyle("{$monthStartCol}4:{$monthEndCol}4")->getFont()->setSize(11);

        // --- ROW DATA ---
        $dataStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ];
        $villageNameStyle = array_merge($dataStyle, ['font' => ['bold' => true]]);

        $rowNum = 5;
        $totals = [];
        
        foreach ($desa as $i => $d) {
            $sheet->setCellValue("A{$rowNum}", $i + 1);
            $sheet->setCellValue("B{$rowNum}", $d->nama);
            $sheet->getStyle("A{$rowNum}")->applyFromArray($dataStyle);
            $sheet->getStyle("B{$rowNum}")->applyFromArray($villageNameStyle);

            $colIdx = 3;
            $sumLHL = 0; $sumLHP = 0; $sumKNL = 0; $sumKNP = 0;

            for ($m = 1; $m <= 12; $m++) {
                $r = $groupedData[$d->id][$m] ?? null;
                $lh_l = $r?->lahir_hidup_l ?? 0;
                $lh_p = $r?->lahir_hidup_p ?? 0;
                $kn_l = $r?->kn_lengkap_l ?? 0;
                $kn_p = $r?->kn_lengkap_p ?? 0;

                $sumLHL += $lh_l; $sumLHP += $lh_p;
                $sumKNL += $kn_l; $sumKNP += $kn_p;

                // Init totals array
                if (!isset($totals[$m])) {
                    $totals[$m] = ['lh_l' => 0, 'lh_p' => 0, 'kn_l' => 0, 'kn_p' => 0];
                }
                $totals[$m]['lh_l'] += $lh_l;
                $totals[$m]['lh_p'] += $lh_p;
                $totals[$m]['kn_l'] += $kn_l;
                $totals[$m]['kn_p'] += $kn_p;

                $colLHL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $colLHP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $colKNL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);
                $colKNP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3);

                $sheet->setCellValue("{$colLHL}{$rowNum}", $lh_l);
                $sheet->setCellValue("{$colLHP}{$rowNum}", $lh_p);
                $sheet->setCellValue("{$colKNL}{$rowNum}", $kn_l);
                $sheet->setCellValue("{$colKNP}{$rowNum}", $kn_p);

                $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->applyFromArray($dataStyle);

                // Highlight Gap
                if ($kn_l !== $lh_l) {
                    $sheet->getStyle("{$colKNL}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFCCCC');
                    $sheet->getStyle("{$colKNL}{$rowNum}")->getFont()->getColor()->setARGB('FFD8000C');
                }
                if ($kn_p !== $lh_p) {
                    $sheet->getStyle("{$colKNP}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFCCCC');
                    $sheet->getStyle("{$colKNP}{$rowNum}")->getFont()->getColor()->setARGB('FFD8000C');
                }

                $colIdx += 4;
            }

            // Total per village (Jumlah column)
            if (!isset($totals['sum'])) {
                $totals['sum'] = ['lh_l' => 0, 'lh_p' => 0, 'kn_l' => 0, 'kn_p' => 0];
            }
            $totals['sum']['lh_l'] += $sumLHL;
            $totals['sum']['lh_p'] += $sumLHP;
            $totals['sum']['kn_l'] += $sumKNL;
            $totals['sum']['kn_p'] += $sumKNP;

            $colLHL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $colLHP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $colKNL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);
            $colKNP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3);

            $sheet->setCellValue("{$colLHL}{$rowNum}", $sumLHL);
            $sheet->setCellValue("{$colLHP}{$rowNum}", $sumLHP);
            $sheet->setCellValue("{$colKNL}{$rowNum}", $sumKNL);
            $sheet->setCellValue("{$colKNP}{$rowNum}", $sumKNP);

            $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->applyFromArray($dataStyle);
            $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->getFont()->setBold(true);

            $rowNum++;
        }

        // --- TOTAL ROW ---
        $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", "Jumlah");
        $sheet->getStyle("A{$rowNum}")->applyFromArray($dataStyle);
        $sheet->getStyle("A{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $colIdx = 3;
        for ($m = 1; $m <= 12; $m++) {
            $colLHL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $colLHP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $colKNL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);
            $colKNP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3);

            $sheet->setCellValue("{$colLHL}{$rowNum}", $totals[$m]['lh_l']);
            $sheet->setCellValue("{$colLHP}{$rowNum}", $totals[$m]['lh_p']);
            $sheet->setCellValue("{$colKNL}{$rowNum}", $totals[$m]['kn_l']);
            $sheet->setCellValue("{$colKNP}{$rowNum}", $totals[$m]['kn_p']);

            $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->applyFromArray($dataStyle);
            $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->getFont()->setBold(true);

            $colIdx += 4;
        }

        // Grand Total
        $colLHL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $colLHP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
        $colKNL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2);
        $colKNP = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3);

        $sheet->setCellValue("{$colLHL}{$rowNum}", $totals['sum']['lh_l']);
        $sheet->setCellValue("{$colLHP}{$rowNum}", $totals['sum']['lh_p']);
        $sheet->setCellValue("{$colKNL}{$rowNum}", $totals['sum']['kn_l']);
        $sheet->setCellValue("{$colKNP}{$rowNum}", $totals['sum']['kn_p']);

        $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->applyFromArray($dataStyle);
        $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("{$colLHL}{$rowNum}:{$colKNP}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDEBF7');

        return $spreadsheet;
    }

    /**
     * Generate CSV with full column structure.
     */
    public function generateCSV(int $bulan, int $tahun): string
    {
        $namaBulan = $this->namaBulan[$bulan] ?? $bulan;
        $desa = Desa::orderBy('urutan')->get();
        $rekapMap = RekapData::where('bulan', $bulan)->where('tahun', $tahun)
            ->get()->keyBy('desa_id');

        $pct = fn($n, $d) => $d > 0 ? round($n / $d * 100, 1) : 0;

        $header = [
            'No', 'Puskesmas', 'Desa', 'Bulan', 'Tahun',
            'LH_L', 'LH_P', 'LH_Total',
            'KN1_L', 'KN1_PctL', 'KN1_P', 'KN1_PctP', 'KN1_Total', 'KN1_PctTotal',
            'KN_L', 'KN_PctL', 'KN_P', 'KN_PctP', 'KN_Total', 'KN_PctTotal',
            'SC_L', 'SC_PctL', 'SC_P', 'SC_PctP', 'SC_Total', 'SC_PctTotal',
        ];
        $lines = [implode(',', $header)];

        foreach ($desa as $d) {
            $r   = $rekapMap->get($d->id);
            $lhL = $r?->lahir_hidup_l ?? 0;
            $lhP = $r?->lahir_hidup_p ?? 0;
            $lhT = $lhL + $lhP;
            $knL = $r?->kn_lengkap_l ?? 0;
            $knP = $r?->kn_lengkap_p ?? 0;
            $knT = $knL + $knP;
            $scL = $r?->screening_hipotiroid_l ?? 0;
            $scP = $r?->screening_hipotiroid_p ?? 0;
            $scT = $scL + $scP;

            $lines[] = implode(',', [
                $d->urutan, 'Mapane', $d->nama, $bulan, $tahun,
                $lhL, $lhP, $lhT,
                $knL, $pct($knL, $lhL), $knP, $pct($knP, $lhP), $knT, $pct($knT, $lhT),
                $knL, $pct($knL, $lhL), $knP, $pct($knP, $lhP), $knT, $pct($knT, $lhT),
                $scL, $pct($scL, $lhL), $scP, $pct($scP, $lhP), $scT, $pct($scT, $lhT),
            ]);
        }

        return implode("\n", $lines);
    }
}
