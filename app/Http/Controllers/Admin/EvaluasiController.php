<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EvaluasiController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewPrefix  = 'pages.admin.evaluasi';
    protected string $routePrefix = 'admin.evaluasi';

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService
    ) {}

    protected function authorizeManageEvaluasi(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin', 'supervisor'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeManageEvaluasi();

        $instrukturId = (string) ($request->input('instruktur') ?? '');
        [$dari, $sampai] = $this->resolveDateRange($request);

        $baseQuery = Evaluasi::with(['instruktur', 'kodeEvaluasi', 'jadwal'])
            ->when($instrukturId !== '', fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']);

        $evaluasis = $baseQuery->clone()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $instrukturs = Instruktur::withTrashed()
            ->where(function ($q) use ($dari, $sampai, $instrukturId) {
                $q->where(fn ($sub) => $sub->whereNull('deleted_at')->where('status', 'aktif'))
                    ->orWhereHas('evaluasis', fn ($eq) => $eq->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']));
                if ($instrukturId !== '') {
                    $q->orWhere('id', $instrukturId);
                }
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'kode_instruktur']);
        $rataRataTotal = $baseQuery->clone()->whereNotNull('total_nilai')->avg('total_nilai');

        $rekapPerInstruktur = Instruktur::withTrashed()
            ->where(function ($q) use ($dari, $sampai, $instrukturId) {
                $q->where(fn ($sub) => $sub->whereNull('deleted_at')->where('status', 'aktif'))
                    ->orWhereHas('evaluasis', fn ($eq) => $eq->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']));
                if ($instrukturId !== '') {
                    $q->orWhere('id', $instrukturId);
                }
            })
            ->when($instrukturId, fn ($q) => $q->where('id', $instrukturId))
            ->with(['evaluasis' => fn ($q) => $q->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Instruktur $inst) {
                $count  = $inst->evaluasis->count();
                $scored = $inst->evaluasis->whereNotNull('total_nilai');
                $avg    = $scored->isNotEmpty() ? round((float) $scored->avg('total_nilai'), 2) : null;
                $min    = $scored->isNotEmpty() ? round((float) $scored->min('total_nilai'), 2) : null;
                $max    = $scored->isNotEmpty() ? round((float) $scored->max('total_nilai'), 2) : null;

                $skala5Values = $inst->evaluasis
                    ->map(fn (Evaluasi $e) => $e->rata_rata_skala5 ?? ($e->total_nilai !== null ? ($e->total_nilai / 20.0) : null))
                    ->filter(fn ($v) => $v !== null)
                    ->values();
                $skala5 = $skala5Values->isNotEmpty() ? round((float) $skala5Values->avg(), 2) : null;

                return [
                    'instruktur'       => $inst,
                    'jumlah_evaluasi'  => $count,
                    'rata_rata_skala5' => $skala5,
                    'rata_rata'        => $avg,
                    'nilai_terendah'   => $min,
                    'nilai_tertinggi'  => $max,
                ];
            });

        $chartEvaluasi = [
            'labels' => $rekapPerInstruktur->filter(fn ($r) => $r['rata_rata'] !== null)->map(fn ($r) => $r['instruktur']->nama_lengkap)->values()->toArray(),
            'values' => $rekapPerInstruktur->filter(fn ($r) => $r['rata_rata'] !== null)->pluck('rata_rata')->values()->toArray(),
            'counts' => $rekapPerInstruktur->filter(fn ($r) => $r['rata_rata'] !== null)->pluck('jumlah_evaluasi')->values()->toArray(),
        ];

        $isRuleConfirmed = $this->kinerjaService->isRuleConfirmed();

        return view($this->viewPrefix . '.index', compact(
            'evaluasis',
            'instrukturs',
            'instrukturId',
            'dari',
            'sampai',
            'rataRataTotal',
            'rekapPerInstruktur',
            'chartEvaluasi',
            'isRuleConfirmed'
        ));
    }

    public function kodeIndex(Request $request)
    {
        $this->authorizeManageEvaluasi();

        $instrukturId = (string) ($request->input('instruktur') ?? '');
        $kelasId      = trim((string) ($request->input('kelas') ?? ''));
        $statusFilter = (string) ($request->input('status') ?? '');

        $selectedKelas = ($kelasId !== '' && ctype_digit($kelasId) && Schema::hasTable('kelas'))
            ? Kelas::withTrashed()->find((int) $kelasId)
            : null;
        if (! $selectedKelas) {
            $kelasId = '';
        }

        $kodes = KodeEvaluasi::with(['instruktur', 'kelas'])
            ->when($instrukturId !== '', function ($q) use ($instrukturId) {
                if ($instrukturId === 'multi') {
                    $q->whereNull('instruktur_id');
                } else {
                    $q->where('instruktur_id', $instrukturId);
                }
            })
            ->when($selectedKelas, fn ($q) => $selectedKelas->scopeMatchingKodeEvaluasi($q))
            ->when(in_array($statusFilter, ['0', '1'], true), fn ($q) => $q->where('is_active', $statusFilter === '1'))
            ->withCount('evaluasis')
            ->orderByRaw('CASE WHEN nama_kelas IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        $instrukturs = Instruktur::aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_instruktur']);
        $kelasList = Schema::hasTable('kelas')
            ? Kelas::orderByRaw("CASE WHEN status = 'aktif' THEN 0 WHEN status = 'selesai' THEN 1 ELSE 2 END")
                ->orderBy('kode_kelas')
                ->get(['id', 'kode_kelas', 'nama_kelas', 'program_pelatihan', 'status'])
            : collect();

        return view($this->viewPrefix . '.kode', compact(
            'kodes',
            'instrukturs',
            'kelasList',
            'instrukturId',
            'kelasId',
            'statusFilter'
        ));
    }

    public function kodeStore(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $customKode = $request->filled('kode') ? strtoupper(trim((string) $request->input('kode'))) : null;
        if ($customKode !== null) {
            $request->merge(['kode' => $customKode]);
        }

        $kelasRule = Schema::hasTable('kelas')
            ? ['nullable', 'integer', Rule::exists('kelas', 'id')->whereNull('deleted_at')]
            : ['nullable'];

        $request->validate([
            'kode'          => ['nullable', 'string', 'max:50', Rule::unique('kode_evaluasis', 'kode')],
            'kelas_id'      => $kelasRule,
            'nama_kelas'    => ['nullable', 'string', 'max:100'],
            'instruktur_id' => ['nullable', Rule::exists('instrukturs', 'id')->whereNull('deleted_at')],
            'keterangan'    => ['nullable', 'string', 'max:255'],
            'max_pengisian' => ['nullable', 'integer', 'min:1'],
            'expired_at'    => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'kode.unique'               => 'Kode evaluasi tersebut sudah terdaftar.',
            'kode.max'                  => 'Kode evaluasi maksimal 50 karakter.',
            'kelas_id.exists'           => 'Master data kelas yang dipilih tidak ditemukan atau telah diarsipkan.',
            'instruktur_id.exists'      => 'Instruktur tidak ditemukan atau telah diarsipkan.',
            'max_pengisian.integer'     => 'Maksimal pengisian harus berupa angka.',
            'max_pengisian.min'         => 'Maksimal pengisian minimal 1.',
            'expired_at.after_or_equal' => 'Tanggal kedaluwarsa harus hari ini atau di masa mendatang.',
        ]);

        if (! $customKode && ! $request->filled('nama_kelas') && ! $request->filled('instruktur_id') && ! $request->filled('kelas_id')) {
            return back()
                ->withInput()
                ->withErrors(['kode' => 'Pilih Master Data Kelas, isi Kode / Nama Kelas, atau pilih Instruktur untuk membuat kode evaluasi.']);
        }

        $selectedKelas = null;
        if ($request->filled('kelas_id') && Schema::hasTable('kelas')) {
            $selectedKelas = Kelas::find((int) $request->input('kelas_id'));
        }

        if ($customKode) {
            $finalKode = $customKode;
        } elseif ($selectedKelas) {
            $finalKode = strlen($selectedKelas->kode_kelas) <= 50 && ! KodeEvaluasi::where('kode', $selectedKelas->kode_kelas)->exists()
                ? $selectedKelas->kode_kelas
                : KodeEvaluasi::generateKode($selectedKelas->kode_kelas);
        } else {
            $finalKode = KodeEvaluasi::generateKode($request->nama_kelas ?: null);
        }

        $namaKelas = $request->filled('nama_kelas')
            ? trim((string) $request->nama_kelas)
            : ($selectedKelas?->nama_kelas ?: ($customKode ?: null));

        $resolvedKelasId = $selectedKelas?->id;
        if (! $resolvedKelasId && Schema::hasTable('kelas')) {
            $resolvedKelasId = Kelas::whereRaw('UPPER(kode_kelas) = ?', [strtoupper($finalKode)])->value('id');
        }

        $expiredAt = null;
        if ($request->filled('expired_at')) {
            $rawExp    = trim((string) $request->input('expired_at'));
            $expiredAt = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawExp)
                ? Carbon::parse($rawExp)->endOfDay()
                : Carbon::parse($rawExp);
        }

        $kode = KodeEvaluasi::create([
            'instruktur_id' => $request->instruktur_id ?: null,
            'kelas_id'      => $resolvedKelasId,
            'kode'          => $finalKode,
            'nama_kelas'    => $namaKelas ? substr($namaKelas, 0, 100) : null,
            'is_active'     => true,
            'keterangan'    => $request->keterangan,
            'max_pengisian' => $request->max_pengisian ?: null,
            'expired_at'    => $expiredAt,
            'jumlah_diisi'  => 0,
        ]);

        return redirect()->route($this->routePrefix . '.kode.index')
            ->with('success', "Kode evaluasi berhasil dibuat: {$kode->kode}");
    }

    public function kodeToggle(KodeEvaluasi $kodeEvaluasi)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $kodeEvaluasi->update(['is_active' => !$kodeEvaluasi->is_active]);
        $status = $kodeEvaluasi->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Kode evaluasi {$kodeEvaluasi->kode} berhasil {$status}.");
    }
}
