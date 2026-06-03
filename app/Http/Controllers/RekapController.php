<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRekapRequest;
use App\Http\Requests\UpdateRekapRequest;
use App\Models\Desa;
use App\Models\ImportLog;
use App\Models\RekapData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RekapController extends Controller
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Tabel rekap dengan filter bulan/tahun.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', session('rekap_filter_bulan', now()->month));
        $tahun = (int) $request->get('tahun', session('rekap_filter_tahun', now()->year));

        session(['rekap_filter_bulan' => $bulan, 'rekap_filter_tahun' => $tahun]);

        $desa  = Desa::orderBy('urutan')->get();

        // Ambil rekap untuk semua desa di periode ini (pakai left join supaya desa tanpa data juga muncul)
        $rekapRaw = RekapData::with(['desa', 'updatedBy'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('desa_id');

        $rows = $desa->map(function ($d) use ($rekapRaw, $bulan, $tahun) {
            $r = $rekapRaw->get($d->id);
            $lhL = $r?->lahir_hidup_l ?? 0;
            $lhP = $r?->lahir_hidup_p ?? 0;
            $knL = $r?->kn_lengkap_l ?? 0;
            $knP = $r?->kn_lengkap_p ?? 0;
            $scL = $r?->screening_hipotiroid_l ?? 0;
            $scP = $r?->screening_hipotiroid_p ?? 0;
            $lhT = $lhL + $lhP;
            $knT = $knL + $knP;
            $scT = $scL + $scP;

            return [
                'desa_id'               => $d->id,
                'urutan'                => $d->urutan,
                'nama_desa'             => $d->nama,
                'rekap_id'              => $r?->id,
                'lahir_hidup_l'         => $lhL,
                'lahir_hidup_p'         => $lhP,
                'lahir_hidup_total'     => $lhT,
                'kn_lengkap_l'          => $knL,
                'kn_lengkap_p'          => $knP,
                'kn_lengkap_total'      => $knT,
                // KN1 = same as KN Lengkap (data input yang sama dipakai)
                'kn1_l'                 => $knL,
                'kn1_p'                 => $knP,
                'kn1_total'             => $knT,
                // Persentase KN1
                'kn1_pct_l'             => $lhL > 0 ? round($knL / $lhL * 100, 1) : 0,
                'kn1_pct_p'             => $lhP > 0 ? round($knP / $lhP * 100, 1) : 0,
                'kn1_pct_total'         => $lhT > 0 ? round($knT / $lhT * 100, 1) : 0,
                // Persentase KN Lengkap (sama dengan KN1 karena input sama)
                'kn_pct_l'              => $lhL > 0 ? round($knL / $lhL * 100, 1) : 0,
                'kn_pct_p'             => $lhP > 0 ? round($knP / $lhP * 100, 1) : 0,
                'kn_pct'               => $r?->kn_pct_total ?? 0,
                // Screening
                'screening_l'           => $scL,
                'screening_p'           => $scP,
                'screening_total'       => $scT,
                'screening_pct_l'       => $lhL > 0 ? round($scL / $lhL * 100, 1) : 0,
                'screening_pct_p'       => $lhP > 0 ? round($scP / $lhP * 100, 1) : 0,
                'screening_pct_total'   => $lhT > 0 ? round($scT / $lhT * 100, 1) : 0,
                'has_data'              => $r !== null,
                'updated_by'            => $r?->updatedBy?->name,
                'updated_at'            => $r?->updated_at?->diffForHumans(),
            ];
        });

        // Last import info
        $lastImport = ImportLog::where('bulan', $bulan)->where('tahun', $tahun)
            ->with('user')->latest('created_at')->first();

        // Yearly totals
        $yearlyRaw = RekapData::where('tahun', $tahun)->get();
        $yearlyTotals = [
            'lh_l' => $yearlyRaw->sum('lahir_hidup_l'),
            'lh_p' => $yearlyRaw->sum('lahir_hidup_p'),
            'kn_l' => $yearlyRaw->sum('kn_lengkap_l'),
            'kn_p' => $yearlyRaw->sum('kn_lengkap_p'),
        ];
        $yearlyTotals['lh_t'] = $yearlyTotals['lh_l'] + $yearlyTotals['lh_p'];
        $yearlyTotals['kn_t'] = $yearlyTotals['kn_l'] + $yearlyTotals['kn_p'];

        return Inertia::render('Rekap/Index', [
            'rows'       => $rows,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'namaBulan'  => $this->namaBulan,
            'lastImport' => $lastImport ? [
                'filename'   => $lastImport->filename,
                'total_rows' => $lastImport->total_rows,
                'status'     => $lastImport->status,
                'user'       => $lastImport->user?->name,
                'created_at' => $lastImport->created_at?->diffForHumans(),
            ] : null,
            'yearlyTotals' => $yearlyTotals,
            'lastImportBulan' => session('last_input_bulan'),
            'lastImportTahun' => session('last_input_tahun'),
        ]);
    }

    /**
     * Form input manual - tampilkan 10 desa.
     */
    public function create(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $desa = Desa::orderBy('urutan')->get();

        // Cek apakah sudah ada data untuk periode ini
        $existingCount = RekapData::where('bulan', $bulan)->where('tahun', $tahun)->count();

        $rows = $desa->map(fn($d) => [
            'desa_id'               => $d->id,
            'nama_desa'             => $d->nama,
            'lahir_hidup_l'         => 0,
            'lahir_hidup_p'         => 0,
            'kn_lengkap_l'          => 0,
            'kn_lengkap_p'          => 0,
            'screening_hipotiroid_l'=> 0,
            'screening_hipotiroid_p'=> 0,
        ]);

        return Inertia::render('Rekap/Create', [
            'rows'          => $rows,
            'bulan'         => $bulan,
            'tahun'         => $tahun,
            'namaBulan'     => $this->namaBulan,
            'existingCount' => $existingCount,
        ]);
    }

    /**
     * Simpan input manual (bulk upsert 10 desa).
     */
    public function store(StoreRekapRequest $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $userId = auth()->id();

        session(['last_input_bulan' => $bulan, 'last_input_tahun' => $tahun]);

        DB::transaction(function () use ($request, $bulan, $tahun, $userId) {
            foreach ($request->rows as $row) {
                RekapData::updateOrCreate(
                    ['desa_id' => $row['desa_id'], 'bulan' => $bulan, 'tahun' => $tahun],
                    [
                        'lahir_hidup_l'          => $row['lahir_hidup_l'],
                        'lahir_hidup_p'          => $row['lahir_hidup_p'],
                        'kn_lengkap_l'           => $row['kn_lengkap_l'],
                        'kn_lengkap_p'           => $row['kn_lengkap_p'],
                        'screening_hipotiroid_l' => $row['screening_hipotiroid_l'] ?? 0,
                        'screening_hipotiroid_p' => $row['screening_hipotiroid_p'] ?? 0,
                        'created_by'             => $userId,
                        'updated_by'             => $userId,
                    ]
                );
            }
        });

        return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', 'Data berhasil disimpan.');
    }

    /**
     * Form edit data – tampilkan semua 10 desa periode ini.
     */
    public function edit(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $desa = Desa::orderBy('urutan')->get();
        $rekapRaw = RekapData::with(['createdBy', 'updatedBy'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('desa_id');

        $rows = $desa->map(function ($d) use ($rekapRaw, $bulan, $tahun) {
            $r = $rekapRaw->get($d->id);
            return [
                'id'           => $r?->id,
                'desa_id'      => $d->id,
                'nama_desa'    => $d->nama,
                'lahir_hidup_l'=> $r?->lahir_hidup_l ?? 0,
                'lahir_hidup_p'=> $r?->lahir_hidup_p ?? 0,
                'kn_lengkap_l' => $r?->kn_lengkap_l ?? 0,
                'kn_lengkap_p' => $r?->kn_lengkap_p ?? 0,
                'created_by'   => $r?->createdBy?->name,
                'updated_by'   => $r?->updatedBy?->name,
                'updated_at'   => $r?->updated_at?->diffForHumans(),
                'created_at'   => $r?->created_at?->diffForHumans(),
            ];
        });

        return Inertia::render('Rekap/Edit', [
            'rows'      => $rows,
            'bulan'     => $bulan,
            'tahun'     => $tahun,
            'namaBulan' => $this->namaBulan,
        ]);
    }

    /**
     * Preview Tahunan - Menampilkan data Lahir Hidup & KN Lengkap 12 bulan penuh
     */
    public function yearly(Request $request)
    {
        $tahun = (int) $request->get('tahun', session('yearly_filter_tahun', now()->year));
        session(['yearly_filter_tahun' => $tahun]);
        
        $desa = Desa::orderBy('urutan')->get();

        // Ambil rekap untuk semua desa di tahun ini
        $rekapRaw = RekapData::where('tahun', $tahun)->get();

        // Kelompokkan data berdasarkan desa_id lalu berdasarkan bulan
        // Struktur: $groupedData[desa_id][bulan] = RekapData
        $groupedData = [];
        foreach ($rekapRaw as $r) {
            $groupedData[$r->desa_id][$r->bulan] = $r;
        }

        $rows = $desa->map(function ($d) use ($groupedData) {
            $monthsData = [];
            
            // Inisialisasi data untuk 12 bulan
            for ($m = 1; $m <= 12; $m++) {
                $r = $groupedData[$d->id][$m] ?? null;
                $monthsData[$m] = [
                    'lh_l' => $r?->lahir_hidup_l ?? 0,
                    'lh_p' => $r?->lahir_hidup_p ?? 0,
                    'kn_l' => $r?->kn_lengkap_l ?? 0,
                    'kn_p' => $r?->kn_lengkap_p ?? 0,
                ];
            }

            return [
                'desa_id'   => $d->id,
                'nama_desa' => $d->nama,
                'months'    => $monthsData,
            ];
        });

        // Hitung total per bulan untuk baris paling bawah
        $totals = [];
        for ($m = 1; $m <= 12; $m++) {
            $totals[$m] = [
                'lh_l' => $rows->sum(fn($r) => $r['months'][$m]['lh_l']),
                'lh_p' => $rows->sum(fn($r) => $r['months'][$m]['lh_p']),
                'kn_l' => $rows->sum(fn($r) => $r['months'][$m]['kn_l']),
                'kn_p' => $rows->sum(fn($r) => $r['months'][$m]['kn_p']),
            ];
        }

        return Inertia::render('Rekap/Yearly', [
            'rows'      => $rows,
            'totals'    => $totals,
            'tahun'     => $tahun,
            'namaBulan' => $this->namaBulan,
        ]);
    }

    /**
     * Update data manual (bulk update 10 desa).
     */
    public function update(UpdateRekapRequest $request)
    {
        $userId = auth()->id();

        DB::transaction(function () use ($request, $userId) {
            foreach ($request->rows as $row) {
                RekapData::where('id', $row['id'])->update([
                    'lahir_hidup_l' => $row['lahir_hidup_l'],
                    'lahir_hidup_p' => $row['lahir_hidup_p'],
                    'kn_lengkap_l'  => $row['kn_lengkap_l'],
                    'kn_lengkap_p'  => $row['kn_lengkap_p'],
                    'updated_by'    => $userId,
                ]);
            }
        });

        $first = RekapData::find($request->rows[0]['id']);
        return redirect()->route('rekap.index', ['bulan' => $first->bulan, 'tahun' => $first->tahun])
            ->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Hapus satu rekap baris.
     */
    public function destroy(RekapData $rekap)
    {
        $bulan = $rekap->bulan;
        $tahun = $rekap->tahun;
        $rekap->delete();

        return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Hapus data massal (bulk delete).
     */
    public function bulkDestroy(Request $request)
    {
        $action = $request->input('action');
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        if ($action === 'selected') {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                RekapData::whereIn('id', $ids)->delete();
                return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
                    ->with('success', count($ids) . ' data terpilih berhasil dihapus.');
            }
        } elseif ($action === 'month') {
            $count = RekapData::where('bulan', $bulan)->where('tahun', $tahun)->count();
            RekapData::where('bulan', $bulan)->where('tahun', $tahun)->delete();
            return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
                ->with('success', $count . ' data bulan ini berhasil dihapus.');
        } elseif ($action === 'year') {
            $count = RekapData::where('tahun', $tahun)->count();
            RekapData::where('tahun', $tahun)->delete();
            return redirect()->route('rekap.index', ['bulan' => $bulan, 'tahun' => $tahun])
                ->with('success', $count . ' data tahun ini berhasil dihapus.');
        }

        return back()->with('error', 'Aksi tidak valid atau tidak ada data yang dipilih.');
    }
}
