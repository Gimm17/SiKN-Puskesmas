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
}
