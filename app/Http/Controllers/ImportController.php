<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRekapRequest;
use App\Models\ImportLog;
use App\Models\RekapData;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportController extends Controller
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** Show the import page (Step 1) */
    public function show(Request $request)
    {
        $history = ImportLog::with('user')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'filename'   => $l->filename,
                'bulan'      => $l->bulan,
                'tahun'      => $l->tahun,
                'total_rows' => $l->total_rows,
                'status'     => $l->status,
                'user'       => $l->user?->name,
                'created_at' => $l->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Rekap/Import', [
            'namaBulan' => $this->namaBulan,
            'history'   => $history,
            'lastImportBulan' => session('last_input_bulan'),
            'lastImportTahun' => session('last_input_tahun'),
        ]);
    }

    /** Show the batch/multi-file import page */
    public function batchShow(Request $request)
    {
        return Inertia::render('Rekap/BatchImport', [
            'namaBulan' => $this->namaBulan,
        ]);
    }

    /** Preview extracted data BEFORE saving (Step 2) */
    public function preview(ImportRekapRequest $request)
    {
        set_time_limit(120); // Allow up to 2 minutes for large Excel files
        ini_set('memory_limit', '256M');

        $file = $request->file('file');
        $path = $file->store('imports', 'local');
        $filePath = Storage::disk('local')->path($path);

        $service = new ExcelImportService();

        // Load file ONCE – reuse the Spreadsheet object for both parse & raw
        $loaded      = $service->loadFile($filePath);
        $spreadsheet = $loaded['spreadsheet'];
        $sheetNames  = $loaded['sheetNames'];
        $selectedSheet = $request->input('sheet', $sheetNames[0] ?? null);

        $parsedData = $service->parseFromSpreadsheet($spreadsheet, (int) $request->bulan, (int) $request->tahun, $selectedSheet);
        $rows    = $parsedData['rows'] ?? [];
        $knCols  = $parsedData['knCols'] ?? ['l_lbl' => 'W', 'p_lbl' => 'X'];
        $rawData = $service->getRawFromSpreadsheet($spreadsheet, $selectedSheet);

        // Store temp path in session for confirm step
        session([
            'import_tmp_path' => $path,
            'import_filename' => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'rows'        => $rows,
            'filename'    => $file->getClientOriginalName(),
            'size'        => round($file->getSize() / 1024, 1) . ' KB',
            'sheetNames'  => $sheetNames,
            'activeSheet' => $selectedSheet,
            'rawData'     => $rawData,
            'knCols'      => $knCols,
        ]);
    }

    /** Switch sheet on an already-uploaded file (no re-upload needed) */
    public function switchSheet(Request $request)
    {
        $request->validate([
            'sheet' => 'required|string',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2020,2099',
        ]);

        $tmpPath = session('import_tmp_path');
        if (!$tmpPath || !Storage::disk('local')->exists($tmpPath)) {
            return response()->json(['error' => 'File tidak ditemukan di server. Silakan upload ulang.'], 422);
        }

        $filePath      = Storage::disk('local')->path($tmpPath);
        $service       = new ExcelImportService();
        $selectedSheet = $request->input('sheet');

        // Load file ONCE – reuse for parse & raw
        $loaded      = $service->loadFile($filePath);
        $spreadsheet = $loaded['spreadsheet'];

        $parsedData = $service->parseFromSpreadsheet($spreadsheet, (int) $request->bulan, (int) $request->tahun, $selectedSheet);
        $rows    = $parsedData['rows'] ?? [];
        $knCols  = $parsedData['knCols'] ?? ['l_lbl' => 'W', 'p_lbl' => 'X'];
        $rawData = $service->getRawFromSpreadsheet($spreadsheet, $selectedSheet);

        return response()->json([
            'rows'        => $rows,
            'activeSheet' => $selectedSheet,
            'rawData'     => $rawData,
            'knCols'      => $knCols,
        ]);
    }

    /** Confirm and save the import (Step 3) */
    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2020,2099',
            'rows'  => 'required|array|min:1',
        ]);

        $bulan  = (int) $request->bulan;
        $tahun  = (int) $request->tahun;
        $userId = auth()->id();
        $rows   = $request->rows;
        $filename = session('import_filename', 'unknown.xlsx');

        session(['last_input_bulan' => $bulan, 'last_input_tahun' => $tahun]);

        $berhasil = 0;
        $gagal    = 0;
        $catatan  = [];

        DB::transaction(function () use ($rows, $bulan, $tahun, $userId, &$berhasil, &$gagal, &$catatan) {
            foreach ($rows as $row) {
                try {
                    RekapData::updateOrCreate(
                        ['desa_id' => $row['desa_id'], 'bulan' => $bulan, 'tahun' => $tahun],
                        [
                            'lahir_hidup_l' => max(0, (int)$row['lahir_hidup_l']),
                            'lahir_hidup_p' => max(0, (int)$row['lahir_hidup_p']),
                            'kn_lengkap_l'  => max(0, (int)$row['kn_lengkap_l']),
                            'kn_lengkap_p'  => max(0, (int)$row['kn_lengkap_p']),
                            'created_by'    => $userId,
                            'updated_by'    => $userId,
                        ]
                    );
                    $berhasil++;
                } catch (\Exception $e) {
                    $gagal++;
                    $catatan[] = 'Desa ID ' . $row['desa_id'] . ': ' . $e->getMessage();
                }
            }
        });

        // Cleanup temp file
        if ($tmpPath = session('import_tmp_path')) {
            Storage::disk('local')->delete($tmpPath);
            session()->forget(['import_tmp_path', 'import_filename']);
        }

        // Log import
        $status = $gagal === 0 ? 'success' : ($berhasil === 0 ? 'failed' : 'partial');
        ImportLog::create([
            'user_id'    => $userId,
            'filename'   => $filename,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'total_rows' => $berhasil,
            'status'     => $status,
            'catatan'    => count($catatan) ? implode('; ', $catatan) : null,
        ]);

        return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', "✅ Import selesai: $berhasil baris berhasil" . ($gagal > 0 ? ", $gagal gagal." : '.'));
    }
    /**
     * ==========================================
     * BATCH IMPORT — OPSI A (Multi-File Upload)
     * ==========================================
     */

    /** Detect month number from filename (e.g. "Januari 2023.xlsx" → 1) */
    private function detectMonthFromFilename(string $filename): ?int
    {
        $lower = strtolower($filename);
        foreach ($this->namaBulan as $num => $name) {
            if (str_contains($lower, strtolower($name))) {
                return $num;
            }
        }
        // Try detecting from number patterns like "01_" "02 " etc.
        if (preg_match('/\b(0?[1-9]|1[0-2])\b/', $filename, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /** Preview all uploaded files and detect month for each */
    public function batchPreview(Request $request)
    {
        $request->validate([
            'files'  => 'required|array|min:1|max:12',
            'files.*'=> 'required|file|mimes:xlsx,xls|max:5120',
            'tahun'  => 'required|integer|between:2020,2099',
        ]);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $tahun   = (int) $request->tahun;
        $service = new ExcelImportService();
        $results = [];

        foreach ($request->file('files') as $file) {
            $detectedBulan = $this->detectMonthFromFilename($file->getClientOriginalName());
            $bulanForParse = $detectedBulan ?? 1;

            $path      = $file->store('imports', 'local');
            $filePath  = Storage::disk('local')->path($path);
            $loaded    = $service->loadFile($filePath);
            $sheetNames = $loaded['sheetNames'];

            // Auto-select: prefer sheet whose name contains a month name or "KN"
            $autoSheet = $sheetNames[0]; // default
            foreach ($sheetNames as $sn) {
                $cleanSn = strtoupper(preg_replace('/\s+/', '', $sn));
                if (str_contains($cleanSn, 'KN') || $this->detectMonthFromFilename($sn) !== null) {
                    $autoSheet = $sn;
                    break;
                }
            }

            $parsed = $service->parseFromSpreadsheet($loaded['spreadsheet'], $bulanForParse, $tahun, $autoSheet);

            $results[] = [
                'tmpPath'        => $path,
                'filename'       => $file->getClientOriginalName(),
                'size'           => round($file->getSize() / 1024, 1) . ' KB',
                'detectedBulan'  => $detectedBulan,
                'sheetNames'     => $sheetNames,
                'activeSheet'    => $autoSheet,
                'rows'           => $parsed['rows'],
                'rowCount'       => count($parsed['rows']),
                'hasWarning'     => collect($parsed['rows'])->some('warning'),
                'knCols'         => $parsed['knCols'],
                'hasMultipleSheets' => count($sheetNames) > 1,
            ];

            $loaded['spreadsheet']->disconnectWorksheets();
            unset($loaded);
        }

        session(['batch_import_files' => array_column($results, 'tmpPath')]);

        return response()->json($results);
    }

    /** Re-parse a single batch file with a different sheet selection */
    public function batchSwitchSheet(Request $request)
    {
        $request->validate([
            'tmpPath' => 'required|string',
            'sheet'   => 'required|string',
            'bulan'   => 'required|integer|between:1,12',
            'tahun'   => 'required|integer|between:2020,2099',
        ]);

        $tmpPath = $request->tmpPath;
        if (!Storage::disk('local')->exists($tmpPath)) {
            return response()->json(['error' => 'File tidak ditemukan di server. Silakan upload ulang.'], 422);
        }

        $filePath = Storage::disk('local')->path($tmpPath);
        $service  = new ExcelImportService();
        $loaded   = $service->loadFile($filePath);
        $parsed   = $service->parseFromSpreadsheet($loaded['spreadsheet'], (int)$request->bulan, (int)$request->tahun, $request->sheet);

        return response()->json([
            'activeSheet' => $request->sheet,
            'rows'        => $parsed['rows'],
            'rowCount'    => count($parsed['rows']),
            'hasWarning'  => collect($parsed['rows'])->some('warning'),
            'knCols'      => $parsed['knCols'],
        ]);
    }

    /** Store all batch files */
    public function batchStore(Request $request)
    {
        $request->validate([
            'items'        => 'required|array|min:1',
            'items.*.tmpPath'  => 'required|string',
            'items.*.bulan'    => 'required|integer|between:1,12',
            'items.*.rows'     => 'required|array',
            'items.*.filename' => 'required|string',
            'tahun'        => 'required|integer|between:2020,2099',
        ]);

        $tahun   = (int) $request->tahun;
        $userId  = auth()->id();
        $summary = [];

        foreach ($request->items as $item) {
            $bulan    = (int) $item['bulan'];
            $rows     = $item['rows'];
            $filename = $item['filename'];
            $berhasil = 0;
            $gagal    = 0;
            $catatan  = [];

            DB::transaction(function () use ($rows, $bulan, $tahun, $userId, &$berhasil, &$gagal, &$catatan) {
                foreach ($rows as $row) {
                    try {
                        RekapData::updateOrCreate(
                            ['desa_id' => $row['desa_id'], 'bulan' => $bulan, 'tahun' => $tahun],
                            [
                                'lahir_hidup_l' => max(0, (int)$row['lahir_hidup_l']),
                                'lahir_hidup_p' => max(0, (int)$row['lahir_hidup_p']),
                                'kn_lengkap_l'  => max(0, (int)$row['kn_lengkap_l']),
                                'kn_lengkap_p'  => max(0, (int)$row['kn_lengkap_p']),
                                'created_by'    => $userId,
                                'updated_by'    => $userId,
                            ]
                        );
                        $berhasil++;
                    } catch (\Exception $e) {
                        $gagal++;
                        $catatan[] = 'Desa ID ' . ($row['desa_id'] ?? '?') . ': ' . $e->getMessage();
                    }
                }
            });

            // Log
            $status = $gagal === 0 ? 'success' : ($berhasil === 0 ? 'failed' : 'partial');
            ImportLog::create([
                'user_id'    => $userId,
                'filename'   => $filename,
                'bulan'      => $bulan,
                'tahun'      => $tahun,
                'total_rows' => $berhasil,
                'status'     => $status,
                'catatan'    => count($catatan) ? implode('; ', $catatan) : null,
            ]);

            $summary[] = [
                'filename' => $filename,
                'bulan'    => $this->namaBulan[$bulan],
                'berhasil' => $berhasil,
                'gagal'    => $gagal,
                'status'   => $status,
            ];

            // Cleanup tmp file
            if (isset($item['tmpPath'])) {
                Storage::disk('local')->delete($item['tmpPath']);
            }
        }

        $totalBerhasil = array_sum(array_column($summary, 'berhasil'));
        $totalGagal    = array_sum(array_column($summary, 'gagal'));

        return redirect()->route('rekap.index', ['tahun' => $tahun])
            ->with('success', "✅ Import batch selesai: {$totalBerhasil} baris dari " . count($summary) . " file berhasil diimport." . ($totalGagal > 0 ? " ({$totalGagal} gagal)" : ''));
    }

    /**
     * ==========================================
     * MULTI-SHEET IMPORT — OPSI C (1 File, 12 Sheet)
     * ==========================================
     */

    /** Preview a single file with multiple sheets, one per month */
    public function multisheetPreview(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls|max:10240',
            'tahun' => 'required|integer|between:2020,2099',
        ]);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $file     = $request->file('file');
        $tahun    = (int) $request->tahun;
        $path     = $file->store('imports', 'local');
        $filePath = Storage::disk('local')->path($path);
        $service  = new ExcelImportService();
        $loaded   = $service->loadFile($filePath);
        $spreadsheet = $loaded['spreadsheet'];

        $sheetResults = [];
        foreach ($loaded['sheetNames'] as $sheetName) {
            $detectedBulan = $this->detectMonthFromFilename($sheetName);
            $bulanForParse = $detectedBulan ?? 1;

            $parsed = $service->parseFromSpreadsheet($spreadsheet, $bulanForParse, $tahun, $sheetName);
            $sheetResults[] = [
                'sheetName'     => $sheetName,
                'detectedBulan' => $detectedBulan,
                'rows'          => $parsed['rows'],
                'rowCount'      => count($parsed['rows']),
                'hasWarning'    => collect($parsed['rows'])->some('warning'),
                'knCols'        => $parsed['knCols'],
            ];
        }

        session(['multisheet_tmp_path' => $path, 'multisheet_filename' => $file->getClientOriginalName()]);

        return response()->json([
            'filename'     => $file->getClientOriginalName(),
            'size'         => round($file->getSize() / 1024, 1) . ' KB',
            'sheetResults' => $sheetResults,
        ]);
    }

    /** Store all sheets from a multi-sheet file */
    public function multisheetStore(Request $request)
    {
        $request->validate([
            'sheets'         => 'required|array|min:1',
            'sheets.*.sheetName'  => 'required|string',
            'sheets.*.bulan'      => 'required|integer|between:1,12',
            'sheets.*.rows'       => 'required|array',
            'tahun'          => 'required|integer|between:2020,2099',
        ]);

        $tahun    = (int) $request->tahun;
        $userId   = auth()->id();
        $filename = session('multisheet_filename', 'multi-sheet.xlsx');
        $summary  = [];

        foreach ($request->sheets as $sheet) {
            $bulan    = (int) $sheet['bulan'];
            $rows     = $sheet['rows'];
            $berhasil = 0;
            $gagal    = 0;
            $catatan  = [];

            DB::transaction(function () use ($rows, $bulan, $tahun, $userId, &$berhasil, &$gagal, &$catatan) {
                foreach ($rows as $row) {
                    try {
                        RekapData::updateOrCreate(
                            ['desa_id' => $row['desa_id'], 'bulan' => $bulan, 'tahun' => $tahun],
                            [
                                'lahir_hidup_l' => max(0, (int)$row['lahir_hidup_l']),
                                'lahir_hidup_p' => max(0, (int)$row['lahir_hidup_p']),
                                'kn_lengkap_l'  => max(0, (int)$row['kn_lengkap_l']),
                                'kn_lengkap_p'  => max(0, (int)$row['kn_lengkap_p']),
                                'created_by'    => $userId,
                                'updated_by'    => $userId,
                            ]
                        );
                        $berhasil++;
                    } catch (\Exception $e) {
                        $gagal++;
                        $catatan[] = 'Desa ID ' . ($row['desa_id'] ?? '?') . ': ' . $e->getMessage();
                    }
                }
            });

            $status = $gagal === 0 ? 'success' : ($berhasil === 0 ? 'failed' : 'partial');
            ImportLog::create([
                'user_id'    => $userId,
                'filename'   => $filename . ' [' . ($this->namaBulan[$bulan] ?? $bulan) . ']',
                'bulan'      => $bulan,
                'tahun'      => $tahun,
                'total_rows' => $berhasil,
                'status'     => $status,
                'catatan'    => count($catatan) ? implode('; ', $catatan) : null,
            ]);

            $summary[] = ['bulan' => $this->namaBulan[$bulan], 'berhasil' => $berhasil, 'gagal' => $gagal, 'status' => $status];
        }

        // Cleanup
        if ($tmpPath = session('multisheet_tmp_path')) {
            Storage::disk('local')->delete($tmpPath);
            session()->forget(['multisheet_tmp_path', 'multisheet_filename']);
        }

        $totalBerhasil = array_sum(array_column($summary, 'berhasil'));
        $totalGagal    = array_sum(array_column($summary, 'gagal'));

        return redirect()->route('rekap.index', ['tahun' => $tahun])
            ->with('success', "✅ Import multi-sheet selesai: {$totalBerhasil} baris dari " . count($summary) . " sheet berhasil diimport." . ($totalGagal > 0 ? " ({$totalGagal} gagal)" : ''));
    }
}
