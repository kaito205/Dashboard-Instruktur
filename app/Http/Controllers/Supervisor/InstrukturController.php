<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Instruktur;
use App\Models\InstrukturSertifikasi;
use App\Services\KinerjaCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class InstrukturController extends Controller
{
    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Instruktur::class);

        $search   = trim((string) ($request->input('search') ?? $request->input('cari') ?? ''));
        $status   = (string) ($request->input('status') ?? '');
        $keahlian = (string) ($request->input('keahlian') ?? '');

        $instrukturs = Instruktur::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('kode_instruktur', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('bidang_keahlian', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($keahlian !== '', fn ($q) => $q->where('bidang_keahlian', 'like', "%{$keahlian}%"))
            ->withCount(['jadwals', 'absensis', 'evaluasis'])
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        $keahlianList = Instruktur::distinct()->pluck('bidang_keahlian')->filter()->sort()->values();

        return view('pages.supervisor.instruktur.index', compact('instrukturs', 'search', 'status', 'keahlian', 'keahlianList'));
    }

    public function show(Instruktur $instruktur)
    {
        Gate::authorize('view', $instruktur);

        $instruktur->load([
            'user',
            'sertifikasis',
            'jadwals'            => fn ($q) => $q->latest('tanggal')->take(10),
            'aktivitasMengajars' => fn ($q) => $q->with('jadwal.kelas')->latest('tanggal')->take(10),
            'kodeEvaluasis'      => fn ($q) => $q->withCount('evaluasis')->latest(),
        ]);

        $dari   = now()->subDays(30)->toDateString();
        $sampai = now()->toDateString();

        $statsAbsensi = $instruktur->absensis()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statsEvaluasi = [
            'total'     => $instruktur->evaluasis()->count(),
            'rata_rata' => round($instruktur->evaluasis()->avg('total_nilai') ?? 0, 2),
        ];

        $kinerjaSummary = $this->kinerjaService->evaluateInstruktur(
            $instruktur,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        return view('pages.supervisor.instruktur.show', compact(
            'instruktur',
            'statsAbsensi',
            'statsEvaluasi',
            'kinerjaSummary'
        ));
    }

    public function dokumenSertifikasi(Request $request, Instruktur $instruktur, InstrukturSertifikasi $sertifikasi)
    {
        Gate::authorize('view', $instruktur);
        abort_unless($sertifikasi->instruktur_id === $instruktur->id, 404);
        $disk = $sertifikasi->resolveStorageDisk();
        abort_if($disk === null, 404);

        $safeName = str_replace(['"', '\\', '/', "\r", "\n"], '_', $sertifikasi->display_file_name ?: basename($sertifikasi->file_path));

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($sertifikasi->file_path, $safeName);
        }

        return Storage::disk($disk)->response($sertifikasi->file_path, $safeName);
    }
}
