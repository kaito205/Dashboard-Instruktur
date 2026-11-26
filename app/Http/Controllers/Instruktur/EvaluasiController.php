<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use App\Models\Evaluasi;
use Illuminate\Http\Request;

class EvaluasiController extends Controller
{
    use ResolvesOwnInstruktur;

    public function index(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        try {
            $dari = $request->filled('dari')
                ? \Carbon\Carbon::parse((string) $request->input('dari'))->toDateString()
                : '';
        } catch (\Throwable) {
            $dari = '';
        }

        try {
            $sampai = $request->filled('sampai')
                ? \Carbon\Carbon::parse((string) $request->input('sampai'))->toDateString()
                : '';
        } catch (\Throwable) {
            $sampai = '';
        }

        if ($dari !== '' && $sampai !== '' && $dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $baseQuery = Evaluasi::where('instruktur_id', $instruktur->id)
            ->when($dari !== '', fn ($q) => $q->whereDate('created_at', '>=', $dari))
            ->when($sampai !== '', fn ($q) => $q->whereDate('created_at', '<=', $sampai));

        $evaluasis = (clone $baseQuery)
            ->select([
                'id',
                'kode_evaluasi_id',
                'jadwal_id',
                'instruktur_id',
                'materi_evaluasi',
                'jawaban',
                'total_nilai',
                'komentar',
                'created_at',
            ])
            ->with(['kodeEvaluasi', 'jadwal'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $allFiltered     = (clone $baseQuery)->get(['total_nilai', 'jawaban']);
        $totalResponden  = $allFiltered->count();
        $scoredFiltered  = $allFiltered->whereNotNull('total_nilai');
        $rataNilai100    = $scoredFiltered->isNotEmpty() ? round((float) $scoredFiltered->avg('total_nilai'), 2) : null;
        $indikatorLabels = Evaluasi::getIndikatorLabels();

        $sums   = [];
        $counts = [];
        foreach (array_keys($indikatorLabels) as $key) {
            $sums[$key]   = 0.0;
            $counts[$key] = 0;
        }

        foreach ($allFiltered as $ev) {
            $skorMap = $ev->skor_indikator;
            if (!is_array($skorMap)) {
                continue;
            }
            foreach (array_keys($indikatorLabels) as $key) {
                if (isset($skorMap[$key]) && is_numeric($skorMap[$key])) {
                    $sums[$key] += (float) $skorMap[$key];
                    $counts[$key]++;
                }
            }
        }

        $rataPerIndikator = [];
        foreach ($indikatorLabels as $key => $label) {
            $rataPerIndikator[$key] = [
                'label' => $label,
                'avg'   => $counts[$key] > 0 ? round($sums[$key] / $counts[$key], 2) : null,
                'count' => $counts[$key],
            ];
        }

        return view('pages.instruktur.evaluasi', compact(
            'instruktur',
            'evaluasis',
            'indikatorLabels',
            'rataNilai100',
            'totalResponden',
            'rataPerIndikator',
            'dari',
            'sampai'
        ));
    }
}
