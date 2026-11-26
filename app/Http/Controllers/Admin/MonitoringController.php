<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\Instruktur;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewName = 'pages.admin.kinerja.index';

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService
    ) {}

    public function index(Request $request)
    {
        $instrukturId   = (string) ($request->input('instruktur') ?? '');
        $kategoriFilter = (string) ($request->input('kategori') ?? '');
        [$dari, $sampai] = $this->resolveDateRange($request);

        // Hitung rentang periode sebelumnya dengan durasi yang sebanding (Bulan Ini vs Bulan Lalu penuh)
        $startCurrent = Carbon::parse($dari)->startOfDay();
        $endCurrent   = Carbon::parse($sampai)->startOfDay();
        if (
            $startCurrent->isSameDay($startCurrent->copy()->startOfMonth())
            && $endCurrent->isSameDay($endCurrent->copy()->endOfMonth())
            && $startCurrent->year === $endCurrent->year
            && $startCurrent->month === $endCurrent->month
        ) {
            $prevStart  = $startCurrent->copy()->subMonthNoOverflow()->startOfMonth();
            $dariLalu   = $prevStart->toDateString();
            $sampaiLalu = $prevStart->copy()->endOfMonth()->toDateString();
        } else {
            $durasiHari = max(1, (int) $startCurrent->diffInDays($endCurrent) + 1);
            $dariLalu   = $startCurrent->copy()->subDays($durasiHari)->toDateString();
            $sampaiLalu = $startCurrent->copy()->subDay()->toDateString();
        }

        $dataKinerjaSaatIni = $this->kinerjaService->evaluateAll($dari, $sampai, $instrukturId ?: null, null, null, true);
        $dataKinerjaLaluMap = $this->kinerjaService
            ->evaluateAll($dariLalu, $sampaiLalu, $instrukturId ?: null, null, null, true)
            ->keyBy(fn ($row) => $row['instruktur']->id);

        $dataKinerja = $dataKinerjaSaatIni->map(function (array $row) use ($dataKinerjaLaluMap) {
            $lalu = $dataKinerjaLaluMap->get($row['instruktur']->id);
            $skorSekarang = $row['skor_akhir'];
            $skorLalu     = $lalu['skor_akhir'] ?? null;

            $row['skor_periode_lalu']     = $skorLalu;
            $row['kategori_periode_lalu'] = $lalu['kategori_label'] ?? 'Belum Ada Data';
            $row['selisih_skor']          = ($skorSekarang !== null && $skorLalu !== null)
                ? round(((float) $skorSekarang) - ((float) $skorLalu), 2)
                : null;

            return $row;
        });

        if ($kategoriFilter !== '') {
            $dataKinerja = $dataKinerja->filter(fn ($row) => $row['kategori'] === $kategoriFilter)->values();
        }

        $ruleConfig      = $this->kinerjaService->getRuleConfig();
        $kategoriRanges  = $this->kinerjaService->getKategoriRangeLabels($ruleConfig);
        $isRuleConfirmed = (bool) ($ruleConfig['enabled'] ?? false);
        $instrukturs = $dataKinerjaSaatIni
            ->pluck('instruktur')
            ->merge(Instruktur::aktif()->get(['id', 'nama_lengkap', 'kode_instruktur']))
            ->unique('id')
            ->sortBy('nama_lengkap')
            ->values();

        $distribusiKategori = [
            'sangat_baik' => $dataKinerja->where('kategori', 'sangat_baik')->count(),
            'baik'        => $dataKinerja->where('kategori', 'baik')->count(),
            'cukup'       => $dataKinerja->where('kategori', 'cukup')->count(),
            'kurang'      => $dataKinerja->where('kategori', 'kurang')->count(),
        ];

        return view($this->viewName, compact(
            'dataKinerja',
            'isRuleConfirmed',
            'ruleConfig',
            'kategoriRanges',
            'instrukturs',
            'instrukturId',
            'kategoriFilter',
            'dari',
            'sampai',
            'dariLalu',
            'sampaiLalu',
            'distribusiKategori'
        ));
    }
}
