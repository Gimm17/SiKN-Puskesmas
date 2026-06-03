<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\RekapData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrintController extends Controller
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function preview(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $desa     = Desa::orderBy('urutan')->get();
        $rekapMap = RekapData::with(['updatedBy'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('desa_id');

        $rows = $desa->map(function ($d) use ($rekapMap) {
            $r    = $rekapMap->get($d->id);
            $lhL  = $r?->lahir_hidup_l ?? 0;
            $lhP  = $r?->lahir_hidup_p ?? 0;
            $lhT  = $lhL + $lhP;
            $knL  = $r?->kn_lengkap_l ?? 0;
            $knP  = $r?->kn_lengkap_p ?? 0;
            $knT  = $knL + $knP;

            return [
                'urutan'    => $d->urutan,
                'nama'      => ucwords(strtolower($d->nama)),
                'lh_l'      => $lhL,
                'lh_p'      => $lhP,
                'lh_total'  => $lhT,
                'kn_l'      => $knL,
                'kn_p'      => $knP,
                'kn_total'  => $knT,
                'pct_l'     => $lhL > 0 ? round($knL / $lhL * 100, 1) : 0,
                'pct_p'     => $lhP > 0 ? round($knP / $lhP * 100, 1) : 0,
                'pct_total' => $lhT > 0 ? round($knT / $lhT * 100, 1) : 0,
                'has_data'  => $r !== null,
            ];
        });

        $totals = [
            'lh_l'      => $rows->sum('lh_l'),
            'lh_p'      => $rows->sum('lh_p'),
            'lh_total'  => $rows->sum('lh_total'),
            'kn_l'      => $rows->sum('kn_l'),
            'kn_p'      => $rows->sum('kn_p'),
            'kn_total'  => $rows->sum('kn_total'),
        ];
        $totals['pct_l']     = $totals['lh_l'] > 0
            ? round($totals['kn_l'] / $totals['lh_l'] * 100, 1) : 0;
        $totals['pct_p']     = $totals['lh_p'] > 0
            ? round($totals['kn_p'] / $totals['lh_p'] * 100, 1) : 0;
        $totals['pct_total'] = $totals['lh_total'] > 0
            ? round($totals['kn_total'] / $totals['lh_total'] * 100, 1) : 0;

        return Inertia::render('Print/Preview', [
            'rows'      => $rows->values(),
            'totals'    => $totals,
            'bulan'     => $bulan,
            'tahun'     => $tahun,
            'bulanNama' => $this->namaBulan[$bulan] ?? (string) $bulan,
            'namaBulan' => $this->namaBulan,
        ]);
    }
}
