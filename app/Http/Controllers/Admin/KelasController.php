<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKelasRequest;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $search  = trim((string) ($request->input('search') ?? $request->input('cari') ?? ''));
        $status  = (string) ($request->input('status') ?? '');
        $program = (string) ($request->input('program') ?? '');
        $arsip   = $request->boolean('arsip');

        $kelasList = Kelas::query()
            ->when($arsip, fn ($q) => $q->withTrashed())
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('kode_kelas', 'like', "%{$search}%")
                        ->orWhere('nama_kelas', 'like', "%{$search}%")
                        ->orWhere('program_pelatihan', 'like', "%{$search}%")
                        ->orWhere('ruangan_default', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($program !== '', fn ($q) => $q->where('program_pelatihan', $program))
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 WHEN status = 'selesai' THEN 1 ELSE 2 END")
            ->orderBy('kode_kelas')
            ->paginate(15)
            ->withQueryString();

        // Gunakan definisi pencocokan kelas yang sama persis dengan halaman detail (show)
        $kelasList->getCollection()->each(function (Kelas $kelasItem) {
            $matchingJadwals = Jadwal::with('instruktur')
                ->where(fn ($q) => $kelasItem->scopeMatchingJadwal($q))
                ->orderBy('id')
                ->get();

            $kelasItem->setRelation(
                'jadwals',
                $matchingJadwals->where('status', '!=', 'dibatalkan')->values()
            );
            $kelasItem->jadwals_count = $matchingJadwals->count();
            $kelasItem->kode_evaluasis_count = KodeEvaluasi::query()
                ->where(fn ($q) => $kelasItem->scopeMatchingKodeEvaluasi($q))
                ->count();
        });

        $allActiveKelas = Kelas::all(['id', 'kode_kelas', 'nama_kelas']);
        $totalJadwalTerhubung = $allActiveKelas->isEmpty()
            ? 0
            : Jadwal::query()->where(function ($outer) use ($allActiveKelas) {
                foreach ($allActiveKelas as $kelasRow) {
                    $outer->orWhere(fn ($sub) => $kelasRow->scopeMatchingJadwal($sub));
                }
            })->count();

        $ringkasan = [
            'total'        => Kelas::count(),
            'aktif'        => Kelas::where('status', 'aktif')->count(),
            'selesai'      => Kelas::where('status', 'selesai')->count(),
            'arsip'        => Kelas::onlyTrashed()->count(),
            'total_jadwal' => $totalJadwalTerhubung,
        ];

        $programList = Kelas::query()
            ->whereNotNull('program_pelatihan')
            ->where('program_pelatihan', '!=', '')
            ->distinct()
            ->orderBy('program_pelatihan')
            ->pluck('program_pelatihan');

        return view('pages.admin.kelas.index', compact(
            'kelasList',
            'ringkasan',
            'programList',
            'search',
            'status',
            'program',
            'arsip'
        ));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $ruanganList = $this->daftarRuangan();
        $programList = $this->daftarProgram();

        return view('pages.admin.kelas.create', compact('ruanganList', 'programList'));
    }

    public function store(StoreKelasRequest $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validated();
        $validated['ruangan_default'] = $this->normalisasiRuangan($validated['ruangan_default'] ?? null);

        $kelas = DB::transaction(function () use ($validated) {
            $kelas = Kelas::create($validated);
            $this->linkHistoricalRecordsToKelas($kelas);

            return $kelas;
        });

        return redirect()->route('admin.kelas.index')
            ->with('success', "Master data kelas {$kelas->kode_kelas} ({$kelas->nama_kelas}) berhasil ditambahkan.");
    }

    public function show(Kelas $kelas)
    {
        $this->authorizeAdmin();

        $jadwals = Jadwal::with(['instruktur', 'instrukturAsli'])
            ->where(fn ($q) => $kelas->scopeMatchingJadwal($q))
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        $kodeEvaluasis = KodeEvaluasi::with('instruktur')
            ->where(fn ($q) => $kelas->scopeMatchingKodeEvaluasi($q))
            ->withCount('evaluasis')
            ->orderBy('kode')
            ->get();

        $instrukturPengampu = $jadwals
            ->where('status', '!=', 'dibatalkan')
            ->filter(fn (Jadwal $j) => $j->instruktur !== null)
            ->groupBy('instruktur_id')
            ->map(function (Collection $items) {
                $instruktur = $items->first()->instruktur;

                return [
                    'instruktur'   => $instruktur,
                    'jumlah_sesi'  => $items->count(),
                    'jadwal_aktif' => $items->whereIn('status', ['terjadwal', 'berlangsung'])->count(),
                ];
            })
            ->sortBy(fn (array $row) => $row['instruktur']->nama_lengkap)
            ->values();

        $stats = [
            'total_jadwal'        => $jadwals->count(),
            'jadwal_aktif'        => $jadwals->whereIn('status', ['terjadwal', 'berlangsung'])->count(),
            'total_kode_evaluasi' => $kodeEvaluasis->count(),
            'total_evaluasi'      => (int) $kodeEvaluasis->sum('evaluasis_count'),
        ];

        return view('pages.admin.kelas.show', compact(
            'kelas',
            'jadwals',
            'kodeEvaluasis',
            'instrukturPengampu',
            'stats'
        ));
    }

    public function edit(Kelas $kelas)
    {
        $this->authorizeAdmin();

        $ruanganList = $this->daftarRuangan();
        $programList = $this->daftarProgram();

        return view('pages.admin.kelas.edit', compact('kelas', 'ruanganList', 'programList'));
    }

    public function update(StoreKelasRequest $request, Kelas $kelas)
    {
        $this->authorizeAdmin();

        $validated = $request->validated();
        $validated['ruangan_default'] = $this->normalisasiRuangan($validated['ruangan_default'] ?? null);

        DB::transaction(function () use ($validated, $kelas) {
            $oldKode = $kelas->kode_kelas;
            $oldNama = $kelas->nama_kelas;

            $kelas->update($validated);
            $this->linkHistoricalRecordsToKelas($kelas, $oldKode, $oldNama);

            Jadwal::where('kelas_id', $kelas->id)->update([
                'kode_kelas' => $kelas->kode_kelas,
                'nama_kelas' => $kelas->nama_kelas,
            ]);

            KodeEvaluasi::where('kelas_id', $kelas->id)
                ->where(function ($q) use ($oldNama) {
                    $q->whereNull('nama_kelas')
                        ->orWhere('nama_kelas', '')
                        ->orWhere('nama_kelas', $oldNama);
                })
                ->update([
                    'nama_kelas' => $kelas->nama_kelas,
                ]);
        });

        return redirect()->route('admin.kelas.show', $kelas)
            ->with('success', "Master data kelas {$kelas->kode_kelas} berhasil diperbarui dan disinkronkan.");
    }

    public function toggleStatus(Kelas $kelas)
    {
        $this->authorizeAdmin();

        if ($kelas->status === 'selesai') {
            return back()->with(
                'error',
                "Kelas {$kelas->kode_kelas} sudah berstatus Selesai. Gunakan menu Edit Kelas apabila ingin memperpanjang periode atau mengaktifkan kembali."
            );
        }

        if ($kelas->status === 'aktif') {
            $newStatus = 'nonaktif';
            $label     = 'dinonaktifkan';
        } elseif ($kelas->periode_selesai !== null && $kelas->periode_selesai->lt(today())) {
            $newStatus = 'selesai';
            $label     = 'dikembalikan ke status Selesai karena periode kelas telah berakhir';
        } else {
            $newStatus = 'aktif';
            $label     = 'diaktifkan';
        }

        $kelas->update(['status' => $newStatus]);

        return back()->with('success', "Kelas {$kelas->kode_kelas} berhasil {$label}.");
    }

    public function destroy(Kelas $kelas)
    {
        $this->authorizeAdmin();

        $kode = $kelas->kode_kelas;

        DB::transaction(function () use ($kelas) {
            KodeEvaluasi::query()
                ->where(fn ($q) => $kelas->scopeMatchingKodeEvaluasi($q))
                ->where('is_active', true)
                ->update(['is_active' => false]);

            if ($kelas->status === 'aktif') {
                $kelas->update(['status' => 'nonaktif']);
            }

            $kelas->delete();
        });

        return redirect()->route('admin.kelas.index')
            ->with('success', "Kelas {$kode} diarsipkan dan kode evaluasi terkait dinonaktifkan. Riwayat jadwal dan evaluasi tetap terjaga.");
    }

    public function restore(int $id)
    {
        $this->authorizeAdmin();

        $kelas = Kelas::withTrashed()->findOrFail($id);
        $restoredStatus = ($kelas->status === 'selesai' || ($kelas->periode_selesai !== null && $kelas->periode_selesai->lt(today())))
            ? 'selesai'
            : 'aktif';

        DB::transaction(function () use ($kelas, $restoredStatus) {
            $kelas->restore();
            $kelas->update(['status' => $restoredStatus]);
        });

        $statusInfo = $restoredStatus === 'selesai' ? 'dengan status Selesai' : 'dan diaktifkan kembali';

        return redirect()->route('admin.kelas.index')
            ->with('success', "Kelas {$kelas->kode_kelas} berhasil dipulihkan {$statusInfo}.");
    }

    private function linkHistoricalRecordsToKelas(Kelas $kelas, ?string $oldKode = null, ?string $oldNama = null): void
    {
        $kodes = collect([$kelas->kode_kelas, $oldKode])
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();

        $namas = collect([$kelas->nama_kelas, $oldNama])
            ->map(fn ($v) => mb_strtolower(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();

        if ($kodes->isEmpty() && $namas->isEmpty()) {
            return;
        }

        Jadwal::query()
            ->whereNull('kelas_id')
            ->where(function ($q) use ($kodes, $namas) {
                foreach ($kodes as $kode) {
                    $q->orWhereRaw('UPPER(kode_kelas) = ?', [$kode]);
                }
                foreach ($namas as $nama) {
                    $q->orWhereRaw('LOWER(nama_kelas) = ?', [$nama]);
                }
            })
            ->update([
                'kelas_id'   => $kelas->id,
                'kode_kelas' => $kelas->kode_kelas,
                'nama_kelas' => $kelas->nama_kelas,
            ]);

        KodeEvaluasi::query()
            ->whereNull('kelas_id')
            ->where(function ($q) use ($kodes, $namas) {
                foreach ($kodes as $kode) {
                    $q->orWhereRaw('UPPER(kode) = ?', [$kode]);
                }
                foreach ($namas as $nama) {
                    $q->orWhereRaw('LOWER(nama_kelas) = ?', [$nama]);
                }
            })
            ->update(['kelas_id' => $kelas->id]);
    }

    private function daftarRuangan(): Collection
    {
        $ruanganPeta = [];

        foreach (Jadwal::getRuanganMap() as $kode => $label) {
            $ruanganPeta[strtoupper($kode)] = [
                'kode'  => $kode,
                'label' => $label,
            ];
        }

        $dariDb = collect()
            ->merge(Jadwal::query()->whereNotNull('ruangan')->distinct()->pluck('ruangan'))
            ->merge(Kelas::query()->whereNotNull('ruangan_default')->distinct()->pluck('ruangan_default'));

        foreach ($dariDb as $ruang) {
            $trimmed = trim((string) $ruang);
            if ($trimmed === '') {
                continue;
            }
            $key = strtoupper($trimmed);
            if (! isset($ruanganPeta[$key])) {
                $ruanganPeta[$key] = [
                    'kode'  => $trimmed,
                    'label' => $trimmed,
                ];
            }
        }

        return collect($ruanganPeta)->sortBy('kode')->values();
    }

    private function daftarProgram(): Collection
    {
        $defaults = collect([
            'Administrasi Perkantoran',
            'Public Speaking',
            'Rekayasa Perangkat Lunak',
            'Teknik Komputer & Jaringan',
        ]);

        $dariDb = Kelas::query()
            ->whereNotNull('program_pelatihan')
            ->where('program_pelatihan', '!=', '')
            ->distinct()
            ->pluck('program_pelatihan');

        return $defaults
            ->merge($dariDb)
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function normalisasiRuangan(?string $ruangan): ?string
    {
        $trimmed = trim((string) ($ruangan ?? ''));
        if ($trimmed === '') {
            return null;
        }

        $upper = strtoupper($trimmed);

        return array_key_exists($upper, Jadwal::getRuanganMap()) ? $upper : $trimmed;
    }
}
