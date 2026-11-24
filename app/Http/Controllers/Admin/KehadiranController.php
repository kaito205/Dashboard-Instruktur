<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Services\JadwalOccurrenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KehadiranController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewName    = 'pages.admin.kehadiran.index';
    protected string $routePrefix = 'admin.kehadiran';

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(?JadwalOccurrenceService $occurrenceService = null)
    {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public function index(Request $request)
    {
        if (Cache::add('absensi_auto_checkout_throttled', true, now()->addMinutes(10))) {
            try {
                Absensi::prosesAutoCheckoutSesiLewat(30);
            } catch (\Throwable) {
                // Abaikan jika gagal sementara agar halaman kehadiran tetap dapat dimuat
            }
        }

        $instrukturId = (string) ($request->input('instruktur') ?? '');
        $status       = (string) ($request->input('status') ?? '');
        $verifikasi   = (string) ($request->input('verifikasi') ?? '');
        $sumber = (string) ($request->input('sumber') ?? '');
        if ($sumber === 'manual_admin') {
            $sumber = 'manual';
        } elseif ($sumber === 'import_excel') {
            $sumber = 'import';
        }
        $tab = $request->input('tab') === 'aktivitas' ? 'aktivitas' : 'absensi';
        $hariIni = today()->toDateString();
        [$dari, $sampai] = $this->resolveDateRange($request, $hariIni, $hariIni);

        $baseAbsensi = Absensi::with(['instruktur', 'jadwal'])
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->when($tab === 'absensi' && $sumber, fn ($q) => $q->where('sumber', $sumber))
            ->whereBetween('tanggal', [$dari, $sampai]);

        $jadwalsPeriode = Jadwal::with('instruktur')
            ->where('status', '!=', 'dibatalkan')
            ->periode($dari, $sampai)
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->get();

        $absensisTanpaSumber = Absensi::query()
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get(['id', 'instruktur_id', 'jadwal_id', 'tanggal', 'status']);

        $jadwalTerlewatAll = $this->occurrenceService
            ->getMissedAttendanceOccurrences($jadwalsPeriode, $absensisTanpaSumber, $dari, $sampai);
        $missedCount = $sumber === '' ? $jadwalTerlewatAll->count() : 0;
        $jadwalTerlewat = $jadwalTerlewatAll->values();

        $totalAbsen = $baseAbsensi->clone()->count() + $missedCount;
        $hadirTepat = $baseAbsensi->clone()->where('status', 'hadir')->count();
        $terlambat  = $baseAbsensi->clone()->where('status', 'terlambat')->count();
        $izin       = $baseAbsensi->clone()->where('status', 'izin')->count();
        $tidakHadir = $baseAbsensi->clone()->where('status', 'tidak_hadir')->count() + $missedCount;
        $totalHadir = $hadirTepat + $terlambat;
        $belumCheckoutCount = Absensi::whereNotNull('jam_checkin')
            ->whereNull('jam_checkout')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $activeAttendanceStatus = ($tab === 'absensi' && in_array($status, ['hadir', 'terlambat', 'izin', 'tidak_hadir'], true))
            ? $status
            : null;
        $statusFilterLabels = [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'izin'        => 'Izin',
            'tidak_hadir' => 'Tidak Hadir',
        ];
        $statusFilterCounts = [
            'hadir'       => $hadirTepat,
            'terlambat'   => $terlambat,
            'izin'        => $izin,
            'tidak_hadir' => $tidakHadir,
        ];
        $statusFilterLabel = $activeAttendanceStatus ? $statusFilterLabels[$activeAttendanceStatus] : null;
        $statusFilterCount = $activeAttendanceStatus ? $statusFilterCounts[$activeAttendanceStatus] : null;
        $statusFilterPersentase = $activeAttendanceStatus !== null
            ? ($totalAbsen > 0 ? round(($statusFilterCount / $totalAbsen) * 100, 1) : 0.0)
            : null;

        $ringkasan = [
            'total'                     => $totalAbsen,
            'total_hadir'               => $totalHadir,
            'hadir'                     => $hadirTepat,
            'terlambat'                 => $terlambat,
            'izin'                      => $izin,
            'tidak_hadir'               => $tidakHadir,
            'belum_checkout'            => $belumCheckoutCount,
            'persentase'                => $totalAbsen > 0 ? round(($totalHadir / $totalAbsen) * 100, 1) : 0.0,
            'toleransi_terlambat_menit' => max(0, (int) Setting::get('toleransi_terlambat_menit', '15')),
            'status_filter_aktif'       => $activeAttendanceStatus,
            'status_filter_label'       => $statusFilterLabel,
            'status_filter_count'       => $statusFilterCount,
            'status_filter_persentase'  => $statusFilterPersentase,
        ];

        $absensis = $baseAbsensi->clone()
            ->when($activeAttendanceStatus !== null, fn ($q) => $q->where('status', $activeAttendanceStatus))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();

        $dedupAktivitasIds = AktivitasMengajar::query()
            ->selectRaw('MAX(id)')
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->groupBy('instruktur_id', 'jadwal_id', 'tanggal');

        $baseAktivitas = AktivitasMengajar::with(['instruktur', 'jadwal.kelas', 'verifier'])
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereIn('id', $dedupAktivitasIds)
            ->whereBetween('tanggal', [$dari, $sampai]);

        $ringkasanAktivitas = [
            'total'              => $baseAktivitas->clone()->count(),
            'dikonfirmasi'       => $baseAktivitas->clone()->where('status_konfirmasi', 'dikonfirmasi')->count(),
            'belum_dikonfirmasi' => $baseAktivitas->clone()->where('status_konfirmasi', 'belum_dikonfirmasi')->count(),
            'tidak_terlaksana'   => $baseAktivitas->clone()->where('status_konfirmasi', 'tidak_terlaksana')->count(),
            'diverifikasi'       => $baseAktivitas->clone()->where('status_verifikasi', 'diverifikasi')->count(),
            'menunggu'           => $baseAktivitas->clone()->where('status_verifikasi', 'menunggu')->count(),
            'perlu_revisi'       => $baseAktivitas->clone()->where('status_verifikasi', 'perlu_revisi')->count(),
        ];

        $aktivitas = $baseAktivitas->clone()
            ->when($tab === 'aktivitas' && $status, fn ($q) => $q->where('status_konfirmasi', $status))
            ->when($tab === 'aktivitas' && $verifikasi, fn ($q) => $q->where('status_verifikasi', $verifikasi))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(5, ['*'], 'aktivitas_page')
            ->withQueryString();

        $presensiAktivitasMap = [];
        $aktPageItems = $aktivitas->getCollection();
        if ($aktPageItems->isNotEmpty()) {
            $aktInstrukturIds = $aktPageItems->pluck('instruktur_id')->filter()->unique()->values()->all();
            $aktDates         = $aktPageItems->pluck('tanggal')
                ->filter()
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->unique()
                ->values()
                ->all();

            $absMatchRows = ($aktInstrukturIds !== [] && $aktDates !== [])
                ? Absensi::whereIn('instruktur_id', $aktInstrukturIds)
                    ->whereNotNull('jam_checkin')
                    ->whereIn(DB::raw('DATE(tanggal)'), $aktDates)
                    ->get(['instruktur_id', 'jadwal_id', 'tanggal'])
                : collect();

            $byInstJadwalDate = [];
            $byInstDateUmum   = [];
            foreach ($absMatchRows as $absRow) {
                $dKey = $absRow->tanggal?->toDateString();
                if (! $dKey) {
                    continue;
                }
                if ($absRow->jadwal_id !== null) {
                    $byInstJadwalDate[$absRow->instruktur_id . '|' . $absRow->jadwal_id . '|' . $dKey] = true;
                } else {
                    $byInstDateUmum[$absRow->instruktur_id . '|' . $dKey] = true;
                }
            }

            foreach ($aktPageItems as $aktItem) {
                $dKey = $aktItem->tanggal?->toDateString() ?? '';
                $hasPresensi = ($dKey !== '' && isset($byInstJadwalDate[$aktItem->instruktur_id . '|' . $aktItem->jadwal_id . '|' . $dKey]))
                    || ($dKey !== '' && isset($byInstDateUmum[$aktItem->instruktur_id . '|' . $dKey]));
                $presensiAktivitasMap[$aktItem->id] = $hasPresensi;
            }
        }

        $aktivitasTanpaFilter = AktivitasMengajar::query()
            ->when($instrukturId, fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get(['id', 'jadwal_id', 'tanggal', 'status_konfirmasi']);

        $jadwalTanpaJurnalAll = $this->occurrenceService
            ->getMissingActivityOccurrences($jadwalsPeriode, $aktivitasTanpaFilter, $dari, $sampai);
        $jadwalTanpaJurnalTotal = $jadwalTanpaJurnalAll->count();
        $jadwalTanpaJurnal      = $jadwalTanpaJurnalAll->take(10)->values();

        $prefillJurnal = null;
        if ($request->boolean('open_jurnal') && $request->filled('jadwal_id')) {
            $targetJadwal = Jadwal::find((int) $request->input('jadwal_id'));
            if ($targetJadwal) {
                $prefillJurnal = [
                    'jadwal_id'   => (string) $targetJadwal->id,
                    'tanggal'     => $request->filled('tanggal')
                        ? (string) $request->input('tanggal')
                        : ($targetJadwal->tanggal?->toDateString() ?? today()->toDateString()),
                    'jam_mulai'   => substr((string) $targetJadwal->jam_mulai, 0, 5),
                    'jam_selesai' => substr((string) $targetJadwal->jam_selesai, 0, 5),
                    'materi'      => (string) ($targetJadwal->materi ?? ''),
                ];
            }
        }

        $instrukturs = Instruktur::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_instruktur']);
        $jadwalsList = Jadwal::with('instruktur')
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderBy('jam_mulai')
            ->get();

        return view($this->viewName, compact(
            'absensis',
            'aktivitas',
            'presensiAktivitasMap',
            'ringkasan',
            'ringkasanAktivitas',
            'jadwalTerlewat',
            'jadwalTanpaJurnal',
            'jadwalTanpaJurnalTotal',
            'prefillJurnal',
            'instrukturs',
            'jadwalsList',
            'instrukturId',
            'status',
            'verifikasi',
            'sumber',
            'tab',
            'dari',
            'sampai'
        ));
    }

    // Input atau koreksi absensi manual oleh Admin sebagai bypass apabila terjadi kendala teknis perangkat instruktur.
    public function storeManual(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'absensi_id'    => ['nullable', 'integer', 'exists:absensis,id'],
            'instruktur_id' => ['required', 'integer', 'exists:instrukturs,id'],
            'jadwal_id'     => ['nullable', 'integer', 'exists:jadwals,id'],
            'tanggal'       => ['required', 'date'],
            'jam_checkin'   => ['nullable', 'date_format:H:i'],
            'jam_checkout'  => ['nullable', 'date_format:H:i'],
            'status'        => ['required', 'in:hadir,terlambat,izin,tidak_hadir'],
            'keterangan'    => ['nullable', 'string', 'max:255'],
        ], [
            'instruktur_id.required' => 'Instruktur wajib dipilih.',
            'tanggal.required'       => 'Tanggal kehadiran wajib diisi.',
            'status.required'        => 'Status kehadiran wajib dipilih.',
        ]);

        if (!empty($validated['jam_checkin']) && !empty($validated['jam_checkout']) && $validated['jam_checkout'] < $validated['jam_checkin']) {
            return back()
                ->withInput()
                ->withErrors(['jam_checkout' => 'Jam check-out harus sama dengan atau setelah jam check-in.']);
        }

        $jadwal = !empty($validated['jadwal_id']) ? Jadwal::find($validated['jadwal_id']) : null;
        if ($jadwal && (int) $jadwal->instruktur_id !== (int) $validated['instruktur_id']) {
            return back()
                ->withInput()
                ->withErrors(['jadwal_id' => 'Jadwal kelas yang dipilih tidak sesuai dengan instruktur yang dipilih.']);
        }
        $jamMulaiJadwal = $jadwal ? substr((string) $jadwal->jam_mulai, 0, 5) : null;
        $menitTerlambat = 0;

        if ($jamMulaiJadwal && !empty($validated['jam_checkin']) && in_array($validated['status'], ['hadir', 'terlambat'], true)) {
            $kalkulasi = Absensi::hitungKeterlambatan($jamMulaiJadwal, $validated['jam_checkin']);
            $menitTerlambat = $kalkulasi['menit_terlambat'];
        }

        $isCorrection = false;

        DB::transaction(function () use ($validated, $jamMulaiJadwal, $menitTerlambat, &$isCorrection) {
            $byId = !empty($validated['absensi_id']) ? Absensi::find((int) $validated['absensi_id']) : null;

            $existing = $byId ?: Absensi::where('instruktur_id', $validated['instruktur_id'])
                ->whereDate('tanggal', $validated['tanggal'])
                ->when(
                    !empty($validated['jadwal_id']),
                    fn ($q) => $q->where('jadwal_id', $validated['jadwal_id']),
                    fn ($q) => $q->whereNull('jadwal_id')
                )
                ->first();

            $isCorrection = $existing !== null;
            $aktorNama    = auth()->user()?->name ?? 'Admin';

            $payload = [
                'instruktur_id'    => (int) $validated['instruktur_id'],
                'jadwal_id'        => !empty($validated['jadwal_id']) ? (int) $validated['jadwal_id'] : null,
                'tanggal'          => $validated['tanggal'],
                'jam_mulai_jadwal' => $jamMulaiJadwal,
                'jam_checkin'      => $validated['jam_checkin'] ?? null,
                'jam_checkout'     => $validated['jam_checkout'] ?? null,
                'menit_terlambat'  => $menitTerlambat,
                'status'           => $validated['status'],
                'sumber'           => $byId ? ($byId->sumber ?: 'manual') : 'manual',
                'status_gps'       => $byId ? ($byId->status_gps ?: 'tidak_tersedia') : 'tidak_tersedia',
                'keterangan'       => $validated['keterangan'] ?: ($byId ? ($byId->keterangan ?: "Koreksi kehadiran oleh {$aktorNama}") : "Input absensi manual oleh {$aktorNama}"),
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Absensi::create($payload);
            }
        });

        return back()->with(
            'success',
            $isCorrection ? 'Catatan kehadiran berhasil diperbarui.' : 'Catatan kehadiran manual berhasil disimpan.'
        );
    }

    // Menjalankan proses Auto-Checkout untuk sesi kehadiran terbuka yang sudah melewati batas waktu selesai kelas.
    public function autoCheckout()
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $closed = Absensi::prosesAutoCheckoutSesiLewat(30);

        if ($closed === 0) {
            return back()->with('success', 'Tidak ada sesi presensi terbuka yang melewati batas waktu selesai kelas.');
        }

        return back()->with('success', "Auto-Checkout berhasil menutup {$closed} sesi presensi yang melewati jam selesai kelas.");
    }

    // Mencatat atau memperbarui jurnal aktivitas mengajar oleh Admin lengkap dengan tautan materi, kendala, hasil, dan bukti dokumentasi.
    public function storeAktivitas(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'jadwal_id'            => ['required', 'integer', 'exists:jadwals,id'],
            'tanggal'              => ['required', 'date'],
            'jam_mulai_aktual'     => ['required', 'date_format:H:i'],
            'jam_selesai_aktual'   => ['required', 'date_format:H:i', 'after:jam_mulai_aktual'],
            'materi_disampaikan'   => ['required', 'string', 'max:255'],
            'tautan_materi'        => ['nullable', 'url', 'max:255'],
            'jumlah_peserta_hadir' => ['required', 'integer', 'min:0', 'max:1000'],
            'status_konfirmasi'    => ['required', 'in:dikonfirmasi,tidak_terlaksana'],
            'status_verifikasi'    => ['nullable', 'in:menunggu,diverifikasi,perlu_revisi'],
            'catatan'              => ['nullable', 'string', 'max:1000'],
            'kendala_kegiatan'     => ['nullable', 'string', 'max:1000'],
            'hasil_pembelajaran'   => ['nullable', 'string', 'max:1000'],
            'file_dokumentasi'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'jadwal_id.required'          => 'Jadwal kelas wajib dipilih.',
            'materi_disampaikan.required' => 'Materi yang disampaikan wajib diisi.',
            'tautan_materi.url'           => 'Tautan modul/materi harus berupa URL yang valid (contoh: https://...).',
            'jam_selesai_aktual.after'    => 'Jam selesai aktual harus setelah jam mulai aktual.',
        ]);

        $jadwal = Jadwal::findOrFail((int) $validated['jadwal_id']);

        DB::transaction(function () use ($request, $validated, $jadwal) {
            $aktivitas = AktivitasMengajar::where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $validated['tanggal'])
                ->first();

            $filePath = $aktivitas?->file_dokumentasi;
            if ($request->hasFile('file_dokumentasi')) {
                if ($filePath) {
                    Storage::disk('local')->delete($filePath);
                    Storage::disk('public')->delete($filePath);
                }
                $filePath = $request->file('file_dokumentasi')->store('aktivitas/dokumentasi', 'local');
            }

            $statusVerif = $validated['status_verifikasi'] ?? 'diverifikasi';

            $payload = [
                'jadwal_id'            => $jadwal->id,
                'instruktur_id'        => $jadwal->instruktur_id,
                'tanggal'              => $validated['tanggal'],
                'jam_mulai_aktual'     => $validated['jam_mulai_aktual'],
                'jam_selesai_aktual'   => $validated['jam_selesai_aktual'],
                'materi_disampaikan'   => $validated['materi_disampaikan'],
                'tautan_materi'        => $validated['tautan_materi'] ?? null,
                'jumlah_peserta_hadir' => (int) $validated['jumlah_peserta_hadir'],
                'status_konfirmasi'    => $validated['status_konfirmasi'],
                'status_verifikasi'    => $statusVerif,
                'catatan'              => $validated['catatan'] ?? null,
                'kendala_kegiatan'     => $validated['kendala_kegiatan'] ?? null,
                'hasil_pembelajaran'   => $validated['hasil_pembelajaran'] ?? null,
                'file_dokumentasi'     => $filePath,
                'confirmed_at'         => $validated['status_konfirmasi'] === 'dikonfirmasi' ? now() : null,
                'verified_by'          => $statusVerif !== 'menunggu' ? auth()->id() : null,
                'verified_at'          => $statusVerif !== 'menunggu' ? now() : null,
            ];

            if ($aktivitas) {
                $aktivitas->update($payload);
            } else {
                AktivitasMengajar::create($payload);
            }
        });

        return back()->with('success', 'Jurnal aktivitas mengajar berhasil disimpan.');
    }

    public function verifikasi(Request $request, AktivitasMengajar $aktivitas)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin', 'supervisor'), 403);

        $validated = $request->validate([
            'status_verifikasi'  => ['required', 'in:menunggu,diverifikasi,perlu_revisi'],
            'catatan_supervisor' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($aktivitas, $validated) {
            $aktivitas->update([
                'status_verifikasi'  => $validated['status_verifikasi'],
                'verified_by'        => auth()->id(),
                'verified_at'        => now(),
                'catatan_supervisor' => $validated['catatan_supervisor'] ?? null,
            ]);
        });

        return back()->with('success', "Verifikasi aktivitas mengajar berhasil disimpan ({$aktivitas->fresh()->verifikasi_label}).");
    }

    public function dokumenAktivitas(Request $request, AktivitasMengajar $aktivitas)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin', 'supervisor'), 403);

        $disk = $aktivitas->resolveDokumentasiDisk();
        abort_if($disk === null, 404);

        $safeName = str_replace(['"', '\\', '/', "\r", "\n"], '_', basename($aktivitas->file_dokumentasi));

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($aktivitas->file_dokumentasi, $safeName);
        }

        return Storage::disk($disk)->response($aktivitas->file_dokumentasi, $safeName);
    }
}
