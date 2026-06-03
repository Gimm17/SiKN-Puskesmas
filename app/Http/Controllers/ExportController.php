<?php

namespace App\Http\Controllers;

use App\Services\ExcelExportService;
use App\Models\Desa;
use App\Models\RekapData;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function xlsx(Request $request): StreamedResponse
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $service = new ExcelExportService();
        $spreadsheet = $service->generateKNLengkap($bulan, $tahun);
        $bulanNama = $this->namaBulan[$bulan] ?? $bulan;
        $filename = "KN_Lengkap_{$bulanNama}_{$tahun}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function csv(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $service = new ExcelExportService();
        $csv = $service->generateCSV($bulan, $tahun);
        $bulanNama = $this->namaBulan[$bulan] ?? $bulan;
        $filename = "KN_Lengkap_{$bulanNama}_{$tahun}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function pdf(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $desa  = Desa::orderBy('urutan')->get();
        $rekapMap = RekapData::where('bulan', $bulan)->where('tahun', $tahun)
            ->get()->keyBy('desa_id');

        $rows = $desa->map(function ($d) use ($rekapMap) {
            $r = $rekapMap->get($d->id);
            $lhL = $r?->lahir_hidup_l ?? 0;
            $lhP = $r?->lahir_hidup_p ?? 0;
            $lhT = $lhL + $lhP;
            $knL = $r?->kn_lengkap_l ?? 0;
            $knP = $r?->kn_lengkap_p ?? 0;
            $knT = $knL + $knP;
            return compact('d', 'lhL', 'lhP', 'lhT', 'knL', 'knP', 'knT');
        });

        $bulanNama = $this->namaBulan[$bulan] ?? $bulan;

        // If DomPDF is not available, return a simple HTML response
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->view('pdf.rekap-kn', compact('rows', 'bulan', 'tahun', 'bulanNama'));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rekap-kn', compact('rows', 'bulan', 'tahun', 'bulanNama'));
        return $pdf->download("KN_Lengkap_{$bulanNama}_{$tahun}.pdf");
    }

    public function yearly(Request $request): StreamedResponse
    {
        $tahun = (int) $request->get('tahun', now()->year);

        $service = new ExcelExportService();
        $spreadsheet = $service->generateYearly($tahun);
        $filename = "LH_dan_KN_Lengkap_Tahun_{$tahun}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
