<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\ImportLog;
use App\Models\RekapData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        if ($request->has('bulan')) {
            session(['dashboard_bulan' => $request->get('bulan')]);
        }
        if ($request->has('tahun')) {
            session(['dashboard_tahun' => $request->get('tahun')]);
        }

        $rawBulan = session('dashboard_bulan', now()->month);
        $isTahunan = $rawBulan === 'semua';
        $bulan = $isTahunan ? 'semua' : (int) $rawBulan;
        $tahun = (int) session('dashboard_tahun', now()->year);

        // --- Stat Cards ---
        $query = RekapData::where('tahun', $tahun);
        if (!$isTahunan) {
            $query->where('bulan', $bulan);
        }
        $periodData = $query->get();
        $totalLHL   = $periodData->sum('lahir_hidup_l');
        $totalLHP   = $periodData->sum('lahir_hidup_p');
        $totalLH    = $totalLHL + $totalLHP;
        $totalKNL   = $periodData->sum('kn_lengkap_l');
        $totalKNP   = $periodData->sum('kn_lengkap_p');
        $totalKN    = $totalKNL + $totalKNP;
        $coverage   = $totalLH > 0 ? round($totalKN / $totalLH * 100, 1) : 0;

        // Desa coverage per desa
        $desaData = Desa::orderBy('urutan')->get()->map(function ($d) use ($bulan, $tahun, $isTahunan) {
            $q = RekapData::where('desa_id', $d->id)->where('tahun', $tahun);
            if (!$isTahunan) {
                $q->where('bulan', $bulan);
            }
            $r = $q->get();

            $lh_l = $r->sum('lahir_hidup_l');
            $lh_p = $r->sum('lahir_hidup_p');
            $kn_l = $r->sum('kn_lengkap_l');
            $kn_p = $r->sum('kn_lengkap_p');

            $lh = $lh_l + $lh_p;
            $kn = $kn_l + $kn_p;
            $pct = $lh > 0 ? round($kn / $lh * 100, 1) : 0;
            return [
                'nama'    => $d->nama,
                'lh'      => $lh,
                'kn'      => $kn,
                'pct'     => $pct,
                'lh_l'    => $lh_l,
                'lh_p'    => $lh_p,
            ];
        });

        // Coverage tertinggi & terendah (hanya desa yang ada data)
        $withData = $desaData->filter(fn($d) => $d['lh'] > 0);
        $desaTertinggi = $withData->sortByDesc('pct')->first();
        $desaTerendah  = $withData->sortBy('pct')->first();

        // --- Trend Bulanan (12 bulan tahun aktif) ---
        $trend = collect(range(1, 12))->map(function ($m) use ($tahun) {
            $r = RekapData::where('bulan', $m)->where('tahun', $tahun)->get();
            $lh = $r->sum('lahir_hidup_l') + $r->sum('lahir_hidup_p');
            $kn = $r->sum('kn_lengkap_l') + $r->sum('kn_lengkap_p');
            return [
                'bulan'    => $m,
                'lh_total' => $lh,
                'kn_total' => $kn,
                'coverage' => $lh > 0 ? round($kn / $lh * 100, 1) : 0,
            ];
        });

        // --- Last Import ---
        $lastImportQuery = ImportLog::where('tahun', $tahun);
        if (!$isTahunan) {
            $lastImportQuery->where('bulan', $bulan);
        }
        $lastImport = $lastImportQuery->with('user')->latest('created_at')->first();

        return Inertia::render('Dashboard/Index', [
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'namaBulan'  => $this->namaBulan,
            'stats' => [
                'total_lh'      => $totalLH,
                'total_lh_l'    => $totalLHL,
                'total_lh_p'    => $totalLHP,
                'total_kn'      => $totalKN,
                'total_kn_l'    => $totalKNL,
                'total_kn_p'    => $totalKNP,
                'coverage'      => $coverage,
                'desa_tertinggi'=> $desaTertinggi,
                'desa_terendah' => $desaTerendah,
            ],
            'trend'      => $trend,
            'desaData'   => $desaData->values(),
            'lastImport' => $lastImport ? [
                'filename'   => $lastImport->filename,
                'user'       => $lastImport->user?->name,
                'created_at' => $lastImport->created_at?->diffForHumans(),
                'total_rows' => $lastImport->total_rows,
            ] : null,
        ]);
    }
}
