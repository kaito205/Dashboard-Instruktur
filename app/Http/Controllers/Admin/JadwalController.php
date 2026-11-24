<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJadwalRequest;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Setting;
use App\Services\JadwalOccurrenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewPrefix  = 'pages.admin.jadwal';
    protected string $routePrefix = 'admin.jadwal';

    protected function authorizeManageJadwal(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin', 'supervisor'), 403);
    }

    protected function authorizeWriteJadwal(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeManageJadwal();

        $search        = trim((string) ($request->input('search') ?? $request->input('cari') ?? ''));
        $instrukturId  = (string) ($request->input('instruktur') ?? '');
        $kelasId       = trim((string) ($request->input('kelas') ?? ''));
        $status        = (string) ($request->input('status') ?? '');
        $hariFilter    = (string) ($request->input('hari') ?? '');
        $tampilan      = $request->filled('tampilan') ? (string) $request->input('tampilan') : 'tabel';
        $skalaKalender = $request->filled('skala_kalender') ? (string) $request->input('skala_kalender') : 'bulanan';
        if (!in_array($skalaKalender, ['harian', 'mingguan', 'bulanan'], true)) {
            $skalaKalender = 'bulanan';
        }
        [$dari, $sampai] = $this->resolveDateRange($request);

        if ($request->filled('tanggal_kalender')) {
            try {
                $calAnchor = Carbon::parse((string) $request->input('tanggal_kalender'))->startOfDay();
            } catch (\Throwable) {
                $calAnchor = today();
            }
        } elseif ($request->filled('dari')) {
            try {
                $parsedDari = Carbon::parse((string) $request->input('dari'))->startOfDay();
                $calAnchor = $parsedDari->format('Y-m') !== today()->format('Y-m') ? $parsedDari : today();
            } catch (\Throwable) {
                $calAnchor = today();
            }
        } else {
            $calAnchor = today();
        }

        $tanggalKalender = $calAnchor->toDateString();

        if ($skalaKalender === 'harian') {
            $calRangeStart = $calAnchor->copy()->startOfDay();
            $calRangeEnd   = $calAnchor->copy()->endOfDay();
        } elseif ($skalaKalender === 'mingguan') {
            $calRangeStart = $calAnchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $calRangeEnd   = $calAnchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        } else {
            $calRangeStart = $calAnchor->copy()->startOfMonth()->startOfDay();
            $calRangeEnd   = $calAnchor->copy()->endOfMonth()->endOfDay();
        }

        $calMonthStart = $calAnchor->copy()->startOfMonth();
        $calMonthEnd   = $calAnchor->copy()->endOfMonth();

        $selectedKelas = ($kelasId !== '' && ctype_digit($kelasId) && Schema::hasTable('kelas'))
            ? Kelas::withTrashed()->find((int) $kelasId)
            : null;
        if (! $selectedKelas) {
            $kelasId = '';
        }

        $todayWibStr = today('Asia/Jakarta')->toDateString();
        $nowWib      = now('Asia/Jakarta');

        $filteredQuery = Jadwal::with([
            'instruktur',
            'instrukturAsli',
            'instrukturPengganti',
            'aktivitasMengajar',
            'kelas',
            'absensis' => fn ($q) => $q->whereDate('tanggal', $todayWibStr),
        ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nama_kelas', 'like', "%{$search}%")
                    ->orWhere('kode_kelas', 'like', "%{$search}%")
                    ->orWhere('materi', 'like', "%{$search}%")
                    ->orWhere('ruangan', 'like', "%{$search}%")
                    ->orWhere('hari', 'like', "%{$search}%")
                    ->orWhereHas('instruktur', function ($iq) use ($search) {
                        $iq->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('kode_instruktur', 'like', "%{$search}%");
                    });
            }))
            ->when($instrukturId !== '', fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->when($selectedKelas, fn ($q) => $selectedKelas->scopeMatchingJadwal($q))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($hariFilter !== '', fn ($q) => $q->where('hari', $hariFilter));

        $jadwals = $filteredQuery->clone()
            ->periode($dari, $sampai)
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_mulai')
            ->paginate(15)
            ->withQueryString();

        $allEvents = $filteredQuery->clone()
            ->periode($calRangeStart->toDateString(), $calRangeEnd->toDateString())
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        $calendarMap = [];
        foreach ($allEvents->where('is_pola_mingguan', false) as $ev) {
            if ($ev->tanggal) {
                $evDate = $ev->tanggal->copy()->startOfDay();
                if ($evDate->gte($calRangeStart) && $evDate->lte($calRangeEnd)) {
                    $calendarMap[$ev->tanggal->toDateString()][] = $ev;
                }
            }
        }

        $polaEvents = $allEvents->where('is_pola_mingguan', true);
        if ($polaEvents->isNotEmpty()) {
            $cursor = $calRangeStart->copy()->startOfDay();
            while ($cursor->lte($calRangeEnd)) {
                $dateKey = $cursor->toDateString();
                $hariCursor = Jadwal::hariDariTanggal($cursor);
                foreach ($polaEvents->where('hari', $hariCursor) as $pola) {
                    if ($pola->periode_mulai && $cursor->lt($pola->periode_mulai)) {
                        continue;
                    }
                    if ($pola->periode_selesai && $cursor->gt($pola->periode_selesai)) {
                        continue;
                    }
                    $calendarMap[$dateKey][] = $pola;
                }
                $cursor->addDay();
            }
        }

        $calendarEvents = collect($calendarMap)->map(
            fn ($items) => collect($items)->sortBy('jam_mulai')->values()
        );
        $instrukturs      = Instruktur::aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_instruktur', 'status_ketersediaan']);
        $kelasList        = $this->daftarKelas();
        $ruanganList      = $this->daftarRuangan();
        $toleransiMenit   = max(0, (int) Setting::get('toleransi_terlambat_menit', '15'));
        $bukaSebelumMenit = max(0, (int) Setting::get('checkin_buka_sebelum_menit', '30'));

        $occurrenceService = new JadwalOccurrenceService();
        $liveStatusMap     = [];
        foreach ($jadwals as $j) {
            if ($j->status === 'dibatalkan') {
                $liveStatusMap[$j->id] = [
                    'label' => 'Dibatalkan',
                    'color' => 'red',
                    'code'  => 'dibatalkan',
                ];
                continue;
            }

            if ($j->is_pola_mingguan) {
                if ($j->berlakuPadaTanggal($todayWibStr)) {
                    $absenHariIni          = $j->absensis->first();
                    $liveStatusMap[$j->id] = $occurrenceService->resolveLiveSessionStatus($j, $todayWibStr, $absenHariIni, $nowWib);
                } else {
                    $liveStatusMap[$j->id] = [
                        'label' => $j->status_label,
                        'color' => $j->status_color,
                        'code'  => (string) $j->status,
                    ];
                }
                continue;
            }

            $tglJadwal = $j->tanggal?->toDateString();
            if ($tglJadwal === $todayWibStr) {
                $absenHariIni          = $j->absensis->first();
                $liveStatusMap[$j->id] = $occurrenceService->resolveLiveSessionStatus($j, $tglJadwal, $absenHariIni, $nowWib);
            } elseif ($j->status === 'selesai') {
                $liveStatusMap[$j->id] = [
                    'label' => 'Selesai',
                    'color' => 'emerald',
                    'code'  => 'selesai',
                ];
            } elseif ($tglJadwal && $tglJadwal < $todayWibStr) {
                $liveStatusMap[$j->id] = $occurrenceService->resolveLiveSessionStatus($j, $tglJadwal, null, $nowWib);
            } else {
                $liveStatusMap[$j->id] = [
                    'label' => $j->status_label,
                    'color' => $j->status_color,
                    'code'  => (string) $j->status,
                ];
            }
        }

        return view($this->viewPrefix . '.index', compact(
            'jadwals',
            'liveStatusMap',
            'calendarEvents',
            'calAnchor',
            'calRangeStart',
            'calRangeEnd',
            'calMonthStart',
            'calMonthEnd',
            'tanggalKalender',
            'instrukturs',
            'kelasList',
            'kelasId',
            'ruanganList',
            'toleransiMenit',
            'bukaSebelumMenit',
            'search',
            'instrukturId',
            'status',
            'hariFilter',
            'tampilan',
            'skalaKalender',
            'dari',
            'sampai'
        ));
    }

    public function create(Request $request)
    {
        $this->authorizeWriteJadwal();

        $instrukturs = Instruktur::aktif()->orderBy('nama_lengkap')->get([
            'id',
            'nama_lengkap',
            'kode_instruktur',
            'status_ketersediaan',
            'cuti_mulai',
            'cuti_selesai',
        ]);
        $kelasList       = $this->daftarKelas();
        $selectedKelasId = (string) ($request->input('kelas_id') ?? '');
        $ruanganList     = $this->daftarRuangan();

        return view($this->viewPrefix . '.create', compact('instrukturs', 'kelasList', 'selectedKelasId', 'ruanganList'));
    }

    private function validateJadwalAvailabilityAndConflicts(StoreJadwalRequest $request, ?int $ignoreJadwalId = null): array
    {
        $instruktur = Instruktur::find($request->instruktur_id);
        if (! $instruktur || $instruktur->status !== 'aktif') {
            return ['error' => ['instruktur_id' => 'Instruktur yang dipilih sedang berstatus nonaktif dan tidak dapat dijadwalkan.']];
        }

        $isPolaMingguan = (bool) $request->boolean('is_pola_mingguan');
        $tanggalCek     = $isPolaMingguan ? null : $request->input('tanggal');
        $hariCek        = $request->input('hari');
        $periodeMulai   = $request->input('periode_mulai');
        $periodeSelesai = $request->input('periode_selesai');

        if ($instruktur->sedangCutiAtauIzinPada($tanggalCek, $hariCek, $periodeMulai, $periodeSelesai)) {
            return ['error' => ['instruktur_id' => "Instruktur {$instruktur->nama_lengkap} sedang dalam masa {$instruktur->ketersediaan_label} dan tidak dapat dijadwalkan pada periode tersebut."]];
        }

        $konflik = $this->cekKonflikJadwal(
            (int) $request->instruktur_id,
            $tanggalCek,
            $hariCek,
            $isPolaMingguan,
            $request->jam_mulai,
            $request->jam_selesai,
            $ignoreJadwalId,
            $periodeMulai,
            $periodeSelesai
        );

        if ($konflik) {
            return ['error' => ['jam_mulai' => "Jadwal instruktur bertabrakan dengan kelas {$konflik->nama_kelas} (" . substr((string) $konflik->jam_mulai, 0, 5) . ' - ' . substr((string) $konflik->jam_selesai, 0, 5) . ').']];
        }

        $ruanganNorm = $this->normalisasiRuangan($request->input('ruangan'));
        if ($ruanganNorm !== '') {
            $konflikRuangan = $this->cekKonflikRuangan(
                $ruanganNorm,
                $tanggalCek,
                $hariCek,
                $isPolaMingguan,
                $request->jam_mulai,
                $request->jam_selesai,
                $ignoreJadwalId,
                $periodeMulai,
                $periodeSelesai
            );

            if ($konflikRuangan) {
                return ['error' => ['ruangan' => "Ruangan {$ruanganNorm} sudah digunakan oleh kelas {$konflikRuangan->nama_kelas} (" . substr((string) $konflikRuangan->jam_mulai, 0, 5) . ' - ' . substr((string) $konflikRuangan->jam_selesai, 0, 5) . ').']];
            }
        }

        return [
            'error'            => null,
            'is_pola_mingguan' => $isPolaMingguan,
            'ruangan'          => $ruanganNorm,
        ];
    }

    public function store(StoreJadwalRequest $request)
    {
        $this->authorizeWriteJadwal();

        $check = $this->validateJadwalAvailabilityAndConflicts($request);
        if (! empty($check['error'])) {
            return back()->withInput()->withErrors($check['error']);
        }
        $isPolaMingguan = $check['is_pola_mingguan'];
        $ruanganNorm    = $check['ruangan'];

        DB::transaction(function () use ($request, $ruanganNorm, $isPolaMingguan) {
            $validated = $request->validated();
            if ($isPolaMingguan) {
                $validated['tanggal'] = null;
            }
            $validated['ruangan'] = $ruanganNorm !== '' ? $ruanganNorm : null;
            $validated = $this->resolveKelasData($validated);
            if (empty($validated['kode_kelas']) && !empty($validated['nama_kelas'])) {
                $validated['kode_kelas'] = substr(strtoupper(trim((string) $validated['nama_kelas'])), 0, 50);
            }

            $jadwal = Jadwal::create($validated);

            if ($jadwal->tanggal !== null) {
                AktivitasMengajar::create([
                    'jadwal_id'          => $jadwal->id,
                    'instruktur_id'      => $jadwal->instruktur_id,
                    'tanggal'            => $jadwal->tanggal,
                    'materi_disampaikan' => $jadwal->materi,
                    'status_konfirmasi'  => 'belum_dikonfirmasi',
                    'status_verifikasi'  => 'menunggu',
                    'confirmed_at'       => null,
                ]);
            }
        });

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', 'Jadwal mengajar berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal)
    {
        $this->authorizeWriteJadwal();

        $instrukturs = Instruktur::aktif()->orderBy('nama_lengkap')->get([
            'id',
            'nama_lengkap',
            'kode_instruktur',
            'status_ketersediaan',
            'cuti_mulai',
            'cuti_selesai',
        ]);
        $kelasList   = $this->daftarKelas($jadwal->kelas_id);
        $ruanganList = $this->daftarRuangan();

        return view($this->viewPrefix . '.edit', compact('jadwal', 'instrukturs', 'kelasList', 'ruanganList'));
    }

    public function update(StoreJadwalRequest $request, Jadwal $jadwal)
    {
        $this->authorizeWriteJadwal();

        $check = $this->validateJadwalAvailabilityAndConflicts($request, $jadwal->id);
        if (! empty($check['error'])) {
            return back()->withInput()->withErrors($check['error']);
        }
        $isPolaMingguan = $check['is_pola_mingguan'];
        $ruanganNorm    = $check['ruangan'];

        DB::transaction(function () use ($request, $jadwal, $ruanganNorm, $isPolaMingguan) {
            $materiJadwalLama = $jadwal->materi;
            $validated = $request->validated();
            if ($isPolaMingguan) {
                $validated['tanggal'] = null;
            }
            $validated['ruangan'] = $ruanganNorm !== '' ? $ruanganNorm : null;
            $validated = $this->resolveKelasData($validated);
            if (empty($validated['kode_kelas']) && !empty($validated['nama_kelas'])) {
                $validated['kode_kelas'] = substr(strtoupper(trim((string) $validated['nama_kelas'])), 0, 50);
            }

            $jadwal->update($validated);

            if ($jadwal->tanggal === null) {
                return;
            }

            $aktivitas = AktivitasMengajar::where('jadwal_id', $jadwal->id)->first();

            if (! $aktivitas) {
                AktivitasMengajar::create([
                    'jadwal_id'          => $jadwal->id,
                    'instruktur_id'      => $jadwal->instruktur_id,
                    'tanggal'            => $jadwal->tanggal,
                    'materi_disampaikan' => $jadwal->materi,
                    'status_konfirmasi'  => 'belum_dikonfirmasi',
                    'status_verifikasi'  => 'menunggu',
                    'confirmed_at'       => null,
                ]);
                return;
            }

            $sudahDiisiInstruktur = $aktivitas->status_konfirmasi !== 'belum_dikonfirmasi'
                || $aktivitas->confirmed_at !== null
                || $aktivitas->jam_mulai_aktual !== null
                || $aktivitas->jam_selesai_aktual !== null
                || $aktivitas->jumlah_peserta_hadir !== null
                || ($aktivitas->catatan !== null && trim((string) $aktivitas->catatan) !== '')
                || ($aktivitas->materi_disampaikan !== null && $aktivitas->materi_disampaikan !== $materiJadwalLama);

            if (! $sudahDiisiInstruktur) {
                $aktivitas->update([
                    'instruktur_id'      => $jadwal->instruktur_id,
                    'tanggal'            => $jadwal->tanggal,
                    'materi_disampaikan' => $jadwal->materi,
                ]);
            }
        });

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', 'Jadwal mengajar berhasil diperbarui.');
    }

    // Mengalihkan atau menyesuaikan jadwal (instruktur, ruangan, hari/tanggal, atau jam mengajar) dengan validasi bentrok dan riwayat pengalihan.
    public function alihkan(Request $request, Jadwal $jadwal)
    {
        $this->authorizeWriteJadwal();

        if (in_array($jadwal->status, ['selesai', 'dibatalkan'], true)) {
            return back()->withErrors([
                'alasan_pengalihan' => "Jadwal yang sudah berstatus {$jadwal->status_label} tidak dapat dialihkan.",
            ]);
        }

        $isPolaMingguan = (bool) $jadwal->is_pola_mingguan;

        $validated = $request->validate([
            'instruktur_pengganti_id' => ['nullable', 'integer', 'exists:instrukturs,id'],
            'ruangan'                 => ['nullable', 'string', 'max:100'],
            'hari'                    => ['nullable', Rule::in(Jadwal::DAFTAR_HARI)],
            'tanggal'                 => ['nullable', 'date'],
            'jam_mulai'               => ['nullable', 'date_format:H:i'],
            'jam_selesai'             => ['nullable', 'date_format:H:i'],
            'alasan_pengalihan'       => ['required', 'string', 'max:255'],
        ], [
            'instruktur_pengganti_id.exists' => 'Instruktur yang dipilih tidak ditemukan.',
            'hari.in'                        => 'Hari tidak valid.',
            'tanggal.date'                   => 'Format tanggal tidak valid.',
            'jam_mulai.date_format'          => 'Format jam mulai harus HH:MM.',
            'jam_selesai.date_format'        => 'Format jam selesai harus HH:MM.',
            'alasan_pengalihan.required'     => 'Alasan pengalihan jadwal wajib diisi.',
        ]);

        $lamaInstruktur = $jadwal->instruktur;
        $targetInstrukturId = ! empty($validated['instruktur_pengganti_id'])
            ? (int) $validated['instruktur_pengganti_id']
            : (int) $jadwal->instruktur_id;
        $targetInstruktur = $targetInstrukturId === (int) $jadwal->instruktur_id && $lamaInstruktur
            ? $lamaInstruktur
            : Instruktur::findOrFail($targetInstrukturId);

        $lamaRuangan = $this->normalisasiRuangan($jadwal->ruangan);
        $targetRuangan = filled($validated['ruangan'] ?? null)
            ? $this->normalisasiRuangan($validated['ruangan'])
            : $lamaRuangan;

        $lamaTanggal = $jadwal->tanggal?->toDateString();
        $targetTanggal = ! $isPolaMingguan && filled($validated['tanggal'] ?? null)
            ? Carbon::parse($validated['tanggal'])->toDateString()
            : $lamaTanggal;

        $lamaHari = $jadwal->hari;
        if ($isPolaMingguan) {
            $targetHari = filled($validated['hari'] ?? null) ? $validated['hari'] : $lamaHari;
        } else {
            $targetHari = $targetTanggal ? Jadwal::hariDariTanggal($targetTanggal) : $lamaHari;
        }

        $lamaJamMulai = substr((string) $jadwal->jam_mulai, 0, 5);
        $lamaJamSelesai = substr((string) $jadwal->jam_selesai, 0, 5);
        $targetJamMulai = filled($validated['jam_mulai'] ?? null) ? $validated['jam_mulai'] : $lamaJamMulai;
        $targetJamSelesai = filled($validated['jam_selesai'] ?? null) ? $validated['jam_selesai'] : $lamaJamSelesai;

        if ($targetJamSelesai <= $targetJamMulai) {
            return back()->withErrors([
                'jam_selesai' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }

        $instrukturBerubah = $targetInstruktur->id !== (int) $jadwal->instruktur_id;
        $ruanganBerubah = $targetRuangan !== $lamaRuangan;
        $waktuBerubah = $targetJamMulai !== $lamaJamMulai || $targetJamSelesai !== $lamaJamSelesai;
        $hariAtauTanggalBerubah = $isPolaMingguan
            ? ($targetHari !== $lamaHari)
            : ($targetTanggal !== $lamaTanggal);

        $daftarPerubahan = [];
        if ($instrukturBerubah) {
            $namaLama = $lamaInstruktur?->nama_lengkap ?? '-';
            $daftarPerubahan[] = "Instruktur: {$namaLama} -> {$targetInstruktur->nama_lengkap}";
        }
        if ($ruanganBerubah) {
            $ruanganLamaLabel = $lamaRuangan !== '' ? $lamaRuangan : '-';
            $ruanganBaruLabel = $targetRuangan !== '' ? $targetRuangan : '-';
            $daftarPerubahan[] = "Ruangan: {$ruanganLamaLabel} -> {$ruanganBaruLabel}";
        }
        if ($hariAtauTanggalBerubah) {
            if ($isPolaMingguan) {
                $daftarPerubahan[] = "Hari: {$lamaHari} -> {$targetHari}";
            } else {
                $tglLamaLabel = $lamaTanggal ? Carbon::parse($lamaTanggal)->format('d/m/Y') : '-';
                $tglBaruLabel = $targetTanggal ? Carbon::parse($targetTanggal)->format('d/m/Y') : '-';
                $daftarPerubahan[] = "Tanggal: {$tglLamaLabel} -> {$tglBaruLabel}";
            }
        }
        if ($waktuBerubah) {
            $daftarPerubahan[] = "Jam: {$lamaJamMulai}-{$lamaJamSelesai} -> {$targetJamMulai}-{$targetJamSelesai}";
        }

        if ($daftarPerubahan === []) {
            return back()->withErrors([
                'alasan_pengalihan' => 'Belum ada perubahan jadwal yang dipilih. Ubah minimal salah satu dari instruktur, ruangan, hari/tanggal, atau jam mengajar.',
            ]);
        }

        $tanggalCek     = $isPolaMingguan ? null : $targetTanggal;
        $periodeMulai   = $jadwal->periode_mulai?->toDateString();
        $periodeSelesai = $jadwal->periode_selesai?->toDateString();

        if ($targetInstruktur->status !== 'aktif' || $targetInstruktur->sedangCutiAtauIzinPada($tanggalCek, $targetHari, $periodeMulai, $periodeSelesai)) {
            return back()->withErrors([
                'instruktur_pengganti_id' => "Instruktur ({$targetInstruktur->nama_lengkap}) sedang berhalangan/cuti atau nonaktif.",
            ]);
        }

        $konflikInstruktur = $this->cekKonflikJadwal(
            $targetInstruktur->id,
            $tanggalCek,
            $targetHari,
            $isPolaMingguan,
            $targetJamMulai,
            $targetJamSelesai,
            $jadwal->id,
            $periodeMulai,
            $periodeSelesai
        );

        if ($konflikInstruktur) {
            return back()->withErrors([
                'instruktur_pengganti_id' => "Jadwal instruktur ({$targetInstruktur->nama_lengkap}) bentrok dengan kelas {$konflikInstruktur->nama_kelas} (" . substr((string) $konflikInstruktur->jam_mulai, 0, 5) . ' - ' . substr((string) $konflikInstruktur->jam_selesai, 0, 5) . ').',
            ]);
        }

        if ($targetRuangan !== '') {
            $konflikRuangan = $this->cekKonflikRuangan(
                $targetRuangan,
                $tanggalCek,
                $targetHari,
                $isPolaMingguan,
                $targetJamMulai,
                $targetJamSelesai,
                $jadwal->id,
                $periodeMulai,
                $periodeSelesai
            );

            if ($konflikRuangan) {
                return back()->withErrors([
                    'ruangan' => "Ruangan {$targetRuangan} sudah digunakan oleh kelas {$konflikRuangan->nama_kelas} (" . substr((string) $konflikRuangan->jam_mulai, 0, 5) . ' - ' . substr((string) $konflikRuangan->jam_selesai, 0, 5) . ').',
                ]);
            }
        }

        DB::transaction(function () use (
            $jadwal,
            $targetInstruktur,
            $instrukturBerubah,
            $targetRuangan,
            $targetHari,
            $targetTanggal,
            $isPolaMingguan,
            $targetJamMulai,
            $targetJamSelesai,
            $validated,
            $daftarPerubahan
        ) {
            $payload = [
                'instruktur_id'     => $targetInstruktur->id,
                'ruangan'           => $targetRuangan !== '' ? $targetRuangan : null,
                'hari'              => $targetHari,
                'jam_mulai'         => $targetJamMulai,
                'jam_selesai'       => $targetJamSelesai,
                'alasan_pengalihan' => $validated['alasan_pengalihan'],
                'dialihkan_pada'    => now(),
            ];

            if (! $isPolaMingguan && $targetTanggal) {
                $payload['tanggal'] = $targetTanggal;
            }

            if ($instrukturBerubah) {
                $payload['instruktur_asli_id'] = $jadwal->instruktur_asli_id ?: $jadwal->instruktur_id;
                $payload['instruktur_pengganti_id'] = $targetInstruktur->id;
            }

            $riwayat = is_array($jadwal->riwayat_pengalihan) ? $jadwal->riwayat_pengalihan : [];
            $riwayat[] = [
                'waktu'     => now()->format('d/m/Y H:i'),
                'oleh'      => auth()->user()?->name ?? 'Admin',
                'alasan'    => $validated['alasan_pengalihan'],
                'perubahan' => $daftarPerubahan,
            ];
            $payload['riwayat_pengalihan'] = $riwayat;

            $jadwal->update($payload);

            $aktivitas = AktivitasMengajar::where('jadwal_id', $jadwal->id)
                ->where('status_konfirmasi', 'belum_dikonfirmasi')
                ->first();

            if ($aktivitas) {
                $aktivitasUpdate = [];
                if ($instrukturBerubah) {
                    $aktivitasUpdate['instruktur_id'] = $targetInstruktur->id;
                }
                if (! $isPolaMingguan && $targetTanggal) {
                    $aktivitasUpdate['tanggal'] = $targetTanggal;
                }
                if ($aktivitasUpdate !== []) {
                    $aktivitas->update($aktivitasUpdate);
                }
            }
        });

        $ringkasan = implode(', ', $daftarPerubahan);

        return back()->with('success', "Jadwal kelas {$jadwal->nama_kelas} berhasil dialihkan/disesuaikan ({$ringkasan}).");
    }

    public function batalkan(Request $request, Jadwal $jadwal)
    {
        $this->authorizeWriteJadwal();

        if ($jadwal->status === 'selesai') {
            return back()->with('error', 'Jadwal yang sudah selesai tidak dapat dibatalkan.');
        }

        if ($jadwal->status === 'dibatalkan') {
            return back()->with('error', 'Jadwal tersebut sudah berstatus dibatalkan.');
        }

        $validated = $request->validate([
            'alasan_pembatalan' => ['nullable', 'string', 'max:255'],
        ], [
            'alasan_pembatalan.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ]);

        $alasan = trim((string) ($validated['alasan_pembatalan'] ?? ''));

        DB::transaction(function () use ($jadwal, $alasan) {
            $keteranganBaru = $jadwal->keterangan;
            if ($alasan !== '') {
                $catatanBatal   = '[Dibatalkan: ' . $alasan . ']';
                $keteranganBaru = $keteranganBaru ? $keteranganBaru . ' | ' . $catatanBatal : $catatanBatal;
            }

            $jadwal->update([
                'status'     => 'dibatalkan',
                'keterangan' => $keteranganBaru,
            ]);

            AktivitasMengajar::where('jadwal_id', $jadwal->id)
                ->where('status_konfirmasi', 'belum_dikonfirmasi')
                ->update(['status_konfirmasi' => 'tidak_terlaksana']);
        });

        return back()->with('success', "Jadwal kelas {$jadwal->nama_kelas} berhasil dibatalkan.");
    }

    public function destroy(Jadwal $jadwal)
    {
        $this->authorizeWriteJadwal();

        $punyaRiwayatOperasional = $jadwal->absensis()->exists()
            || $jadwal->evaluasis()->exists()
            || $jadwal->observasiSupervisors()->exists()
            || $jadwal->aktivitasMengajars()
                ->where(function ($q) {
                    $q->where('status_konfirmasi', '!=', 'belum_dikonfirmasi')
                        ->orWhereNotNull('confirmed_at')
                        ->orWhereNotNull('jam_mulai_aktual')
                        ->orWhereNotNull('jumlah_peserta_hadir');
                })
                ->exists();

        if ($punyaRiwayatOperasional) {
            return back()->with(
                'error',
                "Jadwal '{$jadwal->nama_kelas}' tidak dapat dihapus permanen karena sudah memiliki riwayat presensi, aktivitas mengajar, evaluasi, atau observasi. Gunakan tombol Batalkan agar riwayat kinerja tetap terjaga."
            );
        }

        DB::transaction(function () use ($jadwal) {
            $jadwal->delete();
        });

        return back()->with('success', "Jadwal '{$jadwal->nama_kelas}' berhasil dihapus.");
    }

    private function cekKonflikJadwal(
        int $instrukturId,
        ?string $tanggal,
        ?string $hari,
        bool $isPolaMingguan,
        string $jamMulai,
        string $jamSelesai,
        ?int $excludeId = null,
        ?string $periodeMulai = null,
        ?string $periodeSelesai = null
    ): ?Jadwal {
        $candidates = Jadwal::query()
            ->where('instruktur_id', $instrukturId)
            ->where('status', '!=', 'dibatalkan')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('id')
            ->get();

        return $candidates->first(function (Jadwal $existing) use (
            $tanggal,
            $hari,
            $isPolaMingguan,
            $periodeMulai,
            $periodeSelesai,
            $jamMulai,
            $jamSelesai
        ): bool {
            return $this->jadwalSalingBentrok(
                $existing,
                $tanggal,
                $hari,
                $isPolaMingguan,
                $periodeMulai,
                $periodeSelesai,
                $jamMulai,
                $jamSelesai
            );
        });
    }

    private function cekKonflikRuangan(
        string $ruangan,
        ?string $tanggal,
        ?string $hari,
        bool $isPolaMingguan,
        string $jamMulai,
        string $jamSelesai,
        ?int $excludeId = null,
        ?string $periodeMulai = null,
        ?string $periodeSelesai = null
    ): ?Jadwal {
        $targetRoom = strtoupper($this->normalisasiRuangan($ruangan));
        if ($targetRoom === '') {
            return null;
        }

        $candidates = Jadwal::query()
            ->whereNotNull('ruangan')
            ->where('ruangan', '!=', '')
            ->where('status', '!=', 'dibatalkan')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('id')
            ->get();

        return $candidates->first(function (Jadwal $existing) use (
            $targetRoom,
            $tanggal,
            $hari,
            $isPolaMingguan,
            $periodeMulai,
            $periodeSelesai,
            $jamMulai,
            $jamSelesai
        ): bool {
            if (strtoupper($this->normalisasiRuangan($existing->ruangan)) !== $targetRoom) {
                return false;
            }

            return $this->jadwalSalingBentrok(
                $existing,
                $tanggal,
                $hari,
                $isPolaMingguan,
                $periodeMulai,
                $periodeSelesai,
                $jamMulai,
                $jamSelesai
            );
        });
    }

    private function jadwalSalingBentrok(
        Jadwal $existing,
        ?string $tanggal,
        ?string $hari,
        bool $isPolaMingguan,
        ?string $periodeMulai,
        ?string $periodeSelesai,
        string $jamMulai,
        string $jamSelesai
    ): bool {
        $existingMulai   = substr((string) $existing->jam_mulai, 0, 5);
        $existingSelesai = substr((string) $existing->jam_selesai, 0, 5);
        $targetMulai     = substr($jamMulai, 0, 5);
        $targetSelesai   = substr($jamSelesai, 0, 5);

        if (! ($existingMulai < $targetSelesai && $existingSelesai > $targetMulai)) {
            return false;
        }

        $existingIsPola = (bool) $existing->is_pola_mingguan;
        $existingTgl    = $existing->tanggal?->toDateString();
        $existingPMulai = $existing->periode_mulai?->toDateString();
        $existingPSel   = $existing->periode_selesai?->toDateString();
        $targetTgl      = ($tanggal !== null && $tanggal !== '') ? Carbon::parse($tanggal)->toDateString() : null;

        if ($isPolaMingguan && $existingIsPola) {
            if (! $hari || $existing->hari !== $hari) {
                return false;
            }

            return $this->rentangTanggalBeririsan($periodeMulai, $periodeSelesai, $existingPMulai, $existingPSel);
        }

        if ($isPolaMingguan && ! $existingIsPola) {
            if (! $hari || ! $existingTgl) {
                return false;
            }

            return Jadwal::hariDariTanggal($existingTgl) === $hari
                && $this->tanggalDalamRentang($existingTgl, $periodeMulai, $periodeSelesai);
        }

        if (! $isPolaMingguan && $existingIsPola) {
            if (! $targetTgl || ! $existing->hari) {
                return false;
            }

            return Jadwal::hariDariTanggal($targetTgl) === $existing->hari
                && $this->tanggalDalamRentang($targetTgl, $existingPMulai, $existingPSel);
        }

        return $targetTgl !== null && $existingTgl !== null && $targetTgl === $existingTgl;
    }

    private function rentangTanggalBeririsan(?string $startA, ?string $endA, ?string $startB, ?string $endB): bool
    {
        $a0 = ($startA !== null && $startA !== '') ? Carbon::parse($startA)->startOfDay() : null;
        $a1 = ($endA !== null && $endA !== '') ? Carbon::parse($endA)->startOfDay() : null;
        $b0 = ($startB !== null && $startB !== '') ? Carbon::parse($startB)->startOfDay() : null;
        $b1 = ($endB !== null && $endB !== '') ? Carbon::parse($endB)->startOfDay() : null;

        if ($a0 && $b1 && $a0->gt($b1)) {
            return false;
        }
        if ($b0 && $a1 && $b0->gt($a1)) {
            return false;
        }

        return true;
    }

    private function tanggalDalamRentang(string $tanggal, ?string $start, ?string $end): bool
    {
        $target = Carbon::parse($tanggal)->startOfDay();
        if ($start !== null && $start !== '' && $target->lt(Carbon::parse($start)->startOfDay())) {
            return false;
        }
        if ($end !== null && $end !== '' && $target->gt(Carbon::parse($end)->startOfDay())) {
            return false;
        }

        return true;
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

        $ruanganTersimpan = Jadwal::query()
            ->whereNotNull('ruangan')
            ->distinct()
            ->pluck('ruangan');

        foreach ($ruanganTersimpan as $ruang) {
            $trimmed = trim((string) $ruang);
            if ($trimmed === '') {
                continue;
            }
            $key = strtoupper($trimmed);
            if (!isset($ruanganPeta[$key])) {
                $ruanganPeta[$key] = [
                    'kode'  => $trimmed,
                    'label' => $trimmed,
                ];
            }
        }

        return collect($ruanganPeta)->sortBy('kode')->values();
    }

    private function daftarKelas(?int $includeId = null): Collection
    {
        if (! Schema::hasTable('kelas')) {
            return collect();
        }

        return Kelas::query()
            ->when($includeId, fn ($q) => $q->withTrashed())
            ->where(function ($q) use ($includeId) {
                $q->whereNull('deleted_at');
                if ($includeId) {
                    $q->orWhere('id', $includeId);
                }
            })
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 WHEN status = 'selesai' THEN 1 ELSE 2 END")
            ->orderBy('kode_kelas')
            ->get(['id', 'kode_kelas', 'nama_kelas', 'program_pelatihan', 'kuota_peserta', 'ruangan_default', 'status']);
    }

    private function resolveKelasData(array $validated): array
    {
        if (! Schema::hasTable('kelas')) {
            return $validated;
        }

        if (! empty($validated['kelas_id'])) {
            $kelas = Kelas::withTrashed()->find((int) $validated['kelas_id']);
            if ($kelas) {
                if (empty($validated['kode_kelas'])) {
                    $validated['kode_kelas'] = $kelas->kode_kelas;
                }
                if (empty($validated['nama_kelas'])) {
                    $validated['nama_kelas'] = $kelas->nama_kelas;
                }
            }

            return $validated;
        }

        $kodeUpper = ! empty($validated['kode_kelas']) ? strtoupper(trim((string) $validated['kode_kelas'])) : '';
        $namaLower = ! empty($validated['nama_kelas']) ? mb_strtolower(trim((string) $validated['nama_kelas'])) : '';

        if ($kodeUpper !== '' || $namaLower !== '') {
            $matched = Kelas::query()
                ->where(function ($q) use ($kodeUpper, $namaLower) {
                    if ($kodeUpper !== '') {
                        $q->whereRaw('UPPER(kode_kelas) = ?', [$kodeUpper]);
                    }
                    if ($namaLower !== '') {
                        if ($kodeUpper !== '') {
                            $q->orWhereRaw('LOWER(nama_kelas) = ?', [$namaLower]);
                        } else {
                            $q->whereRaw('LOWER(nama_kelas) = ?', [$namaLower]);
                        }
                    }
                })
                ->first();

            if ($matched) {
                $validated['kelas_id'] = $matched->id;
            }
        }

        return $validated;
    }

    private function normalisasiRuangan(?string $ruangan): string
    {
        $trimmed = trim((string) $ruangan);
        if ($trimmed === '') {
            return '';
        }

        $upper = strtoupper($trimmed);

        return array_key_exists($upper, Jadwal::getRuanganMap()) ? $upper : $trimmed;
    }
}
