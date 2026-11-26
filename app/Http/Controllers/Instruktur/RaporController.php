<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use App\Models\AktivitasMengajar;
use App\Models\ObservasiSupervisor;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RaporController extends Controller
{
    use ResolvesAdminFilters;
    use ResolvesOwnInstruktur;

    public function __construct(private readonly KinerjaCalculationService $kinerjaService)
    {
    }

    public function index(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        [$dari, $sampai] = $this->resolveDateRange($request);

        $rapor          = $this->kinerjaService->evaluateInstruktur($instruktur, $dari, $sampai);
        $ruleConfig     = $this->kinerjaService->getRuleConfig();
        $kategoriRanges = $this->kinerjaService->getKategoriRangeLabels($ruleConfig);

        $startDt = Carbon::parse($dari)->startOfDay();
        $endDt   = Carbon::parse($sampai)->startOfDay();

        if (
            $startDt->isSameDay($startDt->copy()->startOfMonth())
            && $endDt->isSameDay($endDt->copy()->endOfMonth())
            && $startDt->year === $endDt->year
            && $startDt->month === $endDt->month
        ) {
            $prevStart = $startDt->copy()->subMonthNoOverflow()->startOfMonth();
            $prevEnd   = $prevStart->copy()->endOfMonth();
        } else {
            $daysSpan  = max(1, (int) $startDt->diffInDays($endDt) + 1);
            $prevEnd   = $startDt->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($daysSpan - 1);
        }

        $prevDari        = $prevStart->toDateString();
        $prevSampai      = $prevEnd->toDateString();
        $raporSebelumnya = $this->kinerjaService->evaluateInstruktur($instruktur, $prevDari, $prevSampai);

        $deltaSkor = ($rapor['skor_akhir'] !== null && $raporSebelumnya['skor_akhir'] !== null)
            ? round((float) $rapor['skor_akhir'] - (float) $raporSebelumnya['skor_akhir'], 2)
            : null;

        $sampaiEnd = Carbon::parse($sampai)->toDateString() . ' 23:59:59';
        $dedupPesertaIds = AktivitasMengajar::query()
            ->selectRaw('MAX(id)')
            ->where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->groupBy('jadwal_id', 'tanggal');

        $totalPesertaDilatih = (int) AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereIn('id', $dedupPesertaIds)
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->where('status_konfirmasi', 'dikonfirmasi')
            ->where(function ($q) {
                $q->whereNull('jadwal_id')
                    ->orWhereHas('jadwal', fn ($jq) => $jq->where('status', '!=', 'dibatalkan'));
            })
            ->sum('jumlah_peserta_hadir');

        $observasis = ObservasiSupervisor::with(['supervisor', 'jadwal'])
            ->where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->orderByDesc('tanggal')
            ->get();

        return view('pages.instruktur.rapor', compact(
            'instruktur',
            'rapor',
            'raporSebelumnya',
            'prevDari',
            'prevSampai',
            'deltaSkor',
            'totalPesertaDilatih',
            'ruleConfig',
            'kategoriRanges',
            'observasis',
            'dari',
            'sampai'
        ));
    }
}
