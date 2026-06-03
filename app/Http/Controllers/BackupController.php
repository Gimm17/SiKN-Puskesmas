<?php

namespace App\Http\Controllers;

use App\Models\RekapData;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BackupController extends Controller
{
    /**
     * Show the backup & restore page.
     */
    public function index()
    {
        $this->authorizeAdmin();

        $rekapCount = RekapData::count();
        $lastRekap  = RekapData::latest('updated_at')->first();

        return Inertia::render('Backup/Index', [
            'rekapCount' => $rekapCount,
            'lastUpdated' => $lastRekap?->updated_at?->format('d M Y H:i') ?? 'Belum ada data',
        ]);
    }

    /**
     * Export all rekap_data as a JSON file download.
     */
    public function export()
    {
        $this->authorizeAdmin();

        $data = RekapData::orderBy('tahun')->orderBy('bulan')->orderBy('desa_id')->get()
            ->map(fn($row) => $row->only([
                'desa_id', 'bulan', 'tahun',
                'lahir_hidup_l', 'lahir_hidup_p',
                'kn_lengkap_l', 'kn_lengkap_p',
                'screening_hipotiroid_l', 'screening_hipotiroid_p',
                'created_at', 'updated_at',
            ]));

        $payload = [
            'app'        => 'SiKN Puskesmas Mapane',
            'exported_at'=> now()->toIso8601String(),
            'total_rows' => $data->count(),
            'data'       => $data,
        ];

        $filename = 'sikn-backup-' . now()->format('Ymd-His') . '.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type'        => 'application/json',
        ]);
    }

    /**
     * Import rekap_data from an uploaded JSON file.
     */
    public function import(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'backup_file' => 'required|file|mimes:json|max:10240',
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $payload = json_decode($content, true);

        if (!isset($payload['data']) || !is_array($payload['data'])) {
            return back()->with('error', 'Format file backup tidak valid. Pastikan Anda mengunggah file yang diekspor dari SiKN.');
        }

        $userId  = Auth::id();
        $rows    = $payload['data'];
        $imported = 0;

        DB::transaction(function () use ($rows, $userId, &$imported) {
            // Hapus data lama sebelum restore
            RekapData::truncate();

            foreach ($rows as $row) {
                RekapData::create([
                    'desa_id'               => $row['desa_id'],
                    'bulan'                 => $row['bulan'],
                    'tahun'                 => $row['tahun'],
                    'lahir_hidup_l'         => $row['lahir_hidup_l'] ?? 0,
                    'lahir_hidup_p'         => $row['lahir_hidup_p'] ?? 0,
                    'kn_lengkap_l'          => $row['kn_lengkap_l'] ?? 0,
                    'kn_lengkap_p'          => $row['kn_lengkap_p'] ?? 0,
                    'screening_hipotiroid_l'=> $row['screening_hipotiroid_l'] ?? 0,
                    'screening_hipotiroid_p'=> $row['screening_hipotiroid_p'] ?? 0,
                    'created_by'            => $userId,  // Ganti dengan user yang sedang login
                    'updated_by'            => $userId,
                ]);
                $imported++;
            }
        });

        return back()->with('success', "Berhasil! {$imported} baris data berhasil diimpor ke database.");
    }

    private function authorizeAdmin()
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }
    }
}
