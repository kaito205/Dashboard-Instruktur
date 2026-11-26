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

        $relations = [
            'user',
            'sertifikasis',
            'jadwals'            => fn ($q) => $q->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')->latest('tanggal')->take(10),
            'aktivitasMengajars' => fn ($q) => $q->with('jadwal.kelas')->latest('tanggal')->take(10),
            'kodeEvaluasis'      => fn ($q) => $q->withCount('evaluasis')->latest(),
        ];
        if (Instruktur::hasCutiTable()) {
            $relations[] = 'cutis';
        }
        $instruktur->load($relations);

        $dari      = now()->subDays(30)->toDateString();
        $sampai    = now()->toDateString();
        $sampaiEnd = $sampai . ' 23:59:59';

        $occurrenceService = new \App\Services\JadwalOccurrenceService();
        $validAbsensis = $occurrenceService->filterValidAttendanceRecords(
            $instruktur->absensis()->with('jadwal')->whereBetween('tanggal', [$dari, $sampaiEnd])->get()
        );
        $jadwals30 = $instruktur->jadwals()->where('status', '!=', 'dibatalkan')->periode($dari, $sampai)->get();
        $jadwals30->each(fn ($j) => $j->setRelation('instruktur', $instruktur));
        $missed30 = $occurrenceService->getMissedAttendanceOccurrences($jadwals30, $validAbsensis, $dari, $sampai)->count();

        $statsAbsensi = collect([
            'hadir'       => $validAbsensis->where('status', 'hadir')->count(),
            'terlambat'   => $validAbsensis->where('status', 'terlambat')->count(),
            'izin'        => $validAbsensis->whereIn('status', ['izin', 'sakit'])->count(),
            'tidak_hadir' => $validAbsensis->whereIn('status', ['tidak_hadir', 'alpha'])->count() + $missed30,
        ]);

        $avgEval = $instruktur->evaluasis()->whereNotNull('total_nilai')->avg('total_nilai');
        $statsEvaluasi = [
            'total'     => $instruktur->evaluasis()->count(),
            'rata_rata' => $avgEval !== null ? round((float) $avgEval, 2) : null,
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
