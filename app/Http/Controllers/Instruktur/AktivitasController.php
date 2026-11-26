<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Jadwal;
use App\Services\JadwalOccurrenceService;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AktivitasController extends Controller
{
    use ResolvesOwnInstruktur;

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(?JadwalOccurrenceService $occurrenceService = null)
    {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    private function buildFilteredRiwayatQuery(
        int $instrukturId,
        string $dari,
        string $sampai,
        string $jadwalFilter,
        string $sort
    ): Builder {
        $dedupIdsQuery = AktivitasMengajar::query()
            ->selectRaw('MAX(id)')
            ->where('instruktur_id', $instrukturId)
            ->groupBy('jadwal_id', 'tanggal');

        $query = AktivitasMengajar::with(['jadwal.kelas', 'verifier'])
            ->where('instruktur_id', $instrukturId)
            ->whereIn('id', $dedupIdsQuery)
            ->when($dari !== '', fn ($q) => $q->whereDate('tanggal', '>=', $dari))
            ->when($sampai !== '', fn ($q) => $q->whereDate('tanggal', '<=', $sampai))
            ->when($jadwalFilter !== '', fn ($q) => $q->where('jadwal_id', (int) $jadwalFilter));

        if ($sort === 'terlama') {
            $query->orderBy('tanggal')->orderBy('id');
        } else {
            $query->orderByDesc('tanggal')->orderByDesc('id');
        }

        return $query;
    }

    private function resolveOptionalDateRange(Request $request): array
    {
        try {
            $dari = $request->filled('dari')
                ? Carbon::parse((string) $request->input('dari'))->toDateString()
                : '';
        } catch (\Throwable) {
            $dari = '';
        }

        try {
            $sampai = $request->filled('sampai')
                ? Carbon::parse((string) $request->input('sampai'))->toDateString()
                : '';
        } catch (\Throwable) {
            $sampai = '';
        }

        if ($dari !== '' && $sampai !== '' && $dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    public function index(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        [$dari, $sampai] = $this->resolveOptionalDateRange($request);
        $jadwalFilter = $request->filled('jadwal_id') ? (string) $request->input('jadwal_id') : '';
        $sort         = in_array((string) $request->input('sort'), ['terbaru', 'terlama'], true)
            ? (string) $request->input('sort')
            : 'terbaru';

        $jadwals = Jadwal::with('aktivitasMengajar')
            ->where('instruktur_id', $instruktur->id)
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        $baseRiwayatQuery = $this->buildFilteredRiwayatQuery(
            (int) $instruktur->id,
            $dari,
            $sampai,
            $jadwalFilter,
            $sort
        );

        $allStatsRows = (clone $baseRiwayatQuery)->get(['status_verifikasi', 'status_konfirmasi']);

        $riwayatAktivitas = $baseRiwayatQuery
            ->paginate(15)
            ->withQueryString();

        $presensiAktivitasMap = [];
        $pageDates = $riwayatAktivitas->getCollection()
            ->pluck('tanggal')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($pageDates !== []) {
            $absensisPage = Absensi::where('instruktur_id', $instruktur->id)
                ->whereNotNull('jam_checkin')
                ->whereIn('tanggal', $pageDates)
                ->get(['jadwal_id', 'tanggal']);

            $byJadwalDate = [];
            $byDateUmum   = [];
            foreach ($absensisPage as $absRow) {
                $dKey = $absRow->tanggal?->toDateString();
                if (! $dKey) {
                    continue;
                }
                if ($absRow->jadwal_id !== null) {
                    $byJadwalDate[$absRow->jadwal_id . '|' . $dKey] = true;
                } else {
                    $byDateUmum[$dKey] = true;
                }
            }

            foreach ($riwayatAktivitas->getCollection() as $raItem) {
                $dKey = $raItem->tanggal?->toDateString() ?? '';
                $hasPresensi = ($dKey !== '' && isset($byJadwalDate[$raItem->jadwal_id . '|' . $dKey]))
                    || ($dKey !== '' && isset($byDateUmum[$dKey]));
                $presensiAktivitasMap[$raItem->id] = $hasPresensi;
            }
        }

        $nowWib       = now('Asia/Jakarta');
        $occDari      = $dari !== '' ? $dari : $nowWib->copy()->startOfMonth()->toDateString();
        $occSampai    = $sampai !== '' ? $sampai : $nowWib->toDateString();
        $occSampaiEnd = str_contains($occSampai, ':') ? $occSampai : ($occSampai . ' 23:59:59');

        $jadwalsPeriode = Jadwal::with('instruktur.cutis')
            ->where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->periode($occDari, $occSampai)
            ->get();

        $aktivitasPeriode = AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$occDari, $occSampaiEnd])
            ->get(['id', 'jadwal_id', 'tanggal', 'status_konfirmasi']);

        $sesiTerlewatAll   = $this->occurrenceService->getMissingActivityOccurrences($jadwalsPeriode, $aktivitasPeriode, $occDari, $occSampai, $nowWib);
        $sesiTerlewatTotal = $sesiTerlewatAll->count();
        $sesiTerlewat      = $sesiTerlewatAll->map(function (array $miss) use ($nowWib): array {
            $jamTerlewat  = (int) floor($miss['waktu_selesai']->diffInMinutes($nowWib) / 60);
            $isEskalasi   = $jamTerlewat >= 48;
            $miss['jam_terlewat']   = $jamTerlewat;
            $miss['hari_terlewat']  = (int) floor($jamTerlewat / 24);
            $miss['is_eskalasi']    = $isEskalasi;
            $miss['eskalasi_label'] = $isEskalasi
                ? 'Eskalasi > 2x24 jam'
                : 'Menunggu Jurnal (< 48 jam)';

            return $miss;
        })->values();

        $totalSesiPeriode    = $this->occurrenceService->expandElapsedOccurrences($jadwalsPeriode, $occDari, $occSampai, $nowWib)->count();
        $sesiTerlaporPeriode = max(0, $totalSesiPeriode - $sesiTerlewatTotal);
        $persenKepatuhan     = $totalSesiPeriode > 0 ? round(($sesiTerlaporPeriode / $totalSesiPeriode) * 100, 1) : 100.0;
        $kepatuhanColor      = $persenKepatuhan >= 85 ? 'emerald' : ($persenKepatuhan >= 60 ? 'amber' : 'red');
        $kepatuhanLabel      = $persenKepatuhan >= 85 ? 'Patuh' : ($persenKepatuhan >= 60 ? 'Perlu Perhatian' : 'Kritis');
        $eskalasiCount       = $sesiTerlewat->where('is_eskalasi', true)->count();

        $statsAktivitas = [
            'total_laporan'         => $allStatsRows->count(),
            'diverifikasi'          => $allStatsRows->where('status_verifikasi', 'diverifikasi')->count(),
            'menunggu'              => $allStatsRows->filter(fn ($r) => ($r->status_verifikasi ?? 'menunggu') === 'menunggu')->count(),
            'perlu_revisi'          => $allStatsRows->where('status_verifikasi', 'perlu_revisi')->count(),
            'terlaksana'            => $allStatsRows->where('status_konfirmasi', 'dikonfirmasi')->count(),
            'tidak_terlaksana'      => $allStatsRows->where('status_konfirmasi', 'tidak_terlaksana')->count(),
            'persen_kepatuhan'      => $persenKepatuhan,
            'kepatuhan_color'       => $kepatuhanColor,
            'kepatuhan_label'       => $kepatuhanLabel,
            'sesi_terlapor_periode' => $sesiTerlaporPeriode,
            'total_sesi_periode'    => $totalSesiPeriode,
            'eskalasi_count'        => $eskalasiCount,
        ];

        $ownRiwayatJadwalIds = AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereNotNull('jadwal_id')
            ->distinct()
            ->pluck('jadwal_id')
            ->diff($jadwals->pluck('id'))
            ->values();

        $reassignedJadwals = $ownRiwayatJadwalIds->isNotEmpty()
            ? Jadwal::whereIn('id', $ownRiwayatJadwalIds)->get()
            : collect();

        $jadwalOptionsJson = $jadwals->concat($reassignedJadwals)->mapWithKeys(function (Jadwal $j) {
            return [
                $j->id => [
                    'id'               => $j->id,
                    'nama_kelas'       => $j->nama_kelas,
                    'materi'           => $j->materi ?? '',
                    'hari'             => $j->hari ?? '',
                    'is_pola_mingguan' => (bool) $j->is_pola_mingguan,
                    'jam_mulai'        => substr((string) $j->jam_mulai, 0, 5),
                    'jam_selesai'      => substr((string) $j->jam_selesai, 0, 5),
                    'jumlah_peserta'   => (int) ($j->jumlah_peserta ?? 20),
                    'ruangan_label'    => $j->ruangan_label,
                    'tanggal_default'  => $j->tanggal ? $j->tanggal->toDateString() : today()->toDateString(),
                    'update_url'       => route('instruktur.aktivitas.update', $j),
                ],
            ];
        })->all();

        return view('pages.instruktur.aktivitas', compact(
            'instruktur',
            'jadwals',
            'riwayatAktivitas',
            'presensiAktivitasMap',
            'statsAktivitas',
            'sesiTerlewat',
            'sesiTerlewatTotal',
            'dari',
            'sampai',
            'jadwalFilter',
            'sort',
            'occDari',
            'occSampai',
            'jadwalOptionsJson'
        ));
    }

    public function update(Request $request, Jadwal $jadwal)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        $isPemilikJadwal = (int) $jadwal->instruktur_id === (int) $instruktur->id;
        if (! $isPemilikJadwal) {
            $punyaJurnalMilikSendiri = AktivitasMengajar::where('jadwal_id', $jadwal->id)
                ->where('instruktur_id', $instruktur->id)
                ->exists();
            abort_unless($punyaJurnalMilikSendiri, 403);
        }

        if ($isPemilikJadwal && $jadwal->status === 'dibatalkan') {
            return back()->with('error', 'Jadwal yang sudah dibatalkan tidak dapat diisi laporan aktivitas.');
        }

        $validated = $request->validate([
            'tanggal'              => ['required', 'date'],
            'jam_mulai_aktual'     => ['required', 'date_format:H:i'],
            'jam_selesai_aktual'   => ['required', 'date_format:H:i', 'after:jam_mulai_aktual'],
            'materi_disampaikan'   => ['required', 'string', 'max:255'],
            'tautan_materi'        => ['nullable', 'url:http,https', 'max:255'],
            'jumlah_peserta_hadir' => ['required', 'integer', 'min:0', 'max:1000'],
            'status_konfirmasi'    => ['required', 'in:dikonfirmasi,tidak_terlaksana'],
            'catatan'              => ['nullable', 'string', 'max:1000'],
            'kendala_kegiatan'     => ['nullable', 'string', 'max:1000'],
            'hasil_pembelajaran'   => ['nullable', 'string', 'max:1000'],
            'file_dokumentasi'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'materi_disampaikan.required'   => 'Materi yang disampaikan wajib diisi.',
            'tautan_materi.url'             => 'Tautan modul harus berupa URL web yang valid (http:// atau https://).',
            'jumlah_peserta_hadir.required' => 'Jumlah peserta hadir wajib diisi.',
            'jam_selesai_aktual.after'      => 'Jam selesai aktual harus setelah jam mulai aktual.',
        ]);

        $tanggalLaporan = $jadwal->tanggal ? $jadwal->tanggal->toDateString() : $validated['tanggal'];

        $aktivitas = AktivitasMengajar::where('jadwal_id', $jadwal->id)
            ->where('instruktur_id', $instruktur->id)
            ->whereDate('tanggal', $tanggalLaporan)
            ->first();

        if (! $aktivitas && $isPemilikJadwal && ! $jadwal->is_pola_mingguan) {
            $aktivitas = AktivitasMengajar::where('jadwal_id', $jadwal->id)->first();
        }

        if (! $isPemilikJadwal && ! $aktivitas) {
            abort(403, 'Jadwal ini telah dialihkan ke instruktur lain untuk tanggal tersebut.');
        }

        if ($aktivitas && in_array($aktivitas->status_verifikasi, ['diverifikasi', 'disetujui'], true)) {
            return back()->with('error', 'Laporan aktivitas ini sudah diverifikasi Supervisor dan terkunci. Hubungi Supervisor atau Admin jika perlu perubahan.');
        }

        if ($isPemilikJadwal && ! $jadwal->berlakuPadaTanggal($tanggalLaporan)) {
            $alasanPenyesuaian = trim((string) ($validated['catatan'] ?? '')) . trim((string) ($validated['kendala_kegiatan'] ?? ''));
            if (mb_strlen($alasanPenyesuaian) < 10) {
                $hariDipilih = Jadwal::hariDariTanggal($tanggalLaporan);
                return back()
                    ->withInput()
                    ->with('error', "Tanggal {$tanggalLaporan} ({$hariDipilih}) tidak sesuai dengan jadwal ({$jadwal->tanggal_label}). Wajib isi alasan penyesuaian jadwal minimal 10 karakter pada kolom Ringkasan atau Kendala.");
            }
        }

        if ($validated['status_konfirmasi'] === 'dikonfirmasi'
            && ! $request->hasFile('file_dokumentasi')
            && empty($aktivitas?->file_dokumentasi)
        ) {
            return back()
                ->withInput()
                ->with('error', 'Bukti dokumentasi (foto atau PDF) wajib diunggah untuk laporan dengan status Terlaksana.');
        }

        $oldFileToDelete = null;

        DB::transaction(function () use ($request, $instruktur, $jadwal, $validated, $tanggalLaporan, $aktivitas, $isPemilikJadwal, &$oldFileToDelete) {
            $filePath = $aktivitas?->file_dokumentasi;
            if ($request->hasFile('file_dokumentasi')) {
                $newFilePath = $request->file('file_dokumentasi')->store('aktivitas/dokumentasi', 'local');
                if ($filePath && $filePath !== $newFilePath) {
                    $oldFileToDelete = $filePath;
                }
                $filePath = $newFilePath;
            }

            $payload = [
                'jadwal_id'            => $jadwal->id,
                'instruktur_id'        => $instruktur->id,
                'tanggal'              => $tanggalLaporan,
                'jam_mulai_aktual'     => $validated['jam_mulai_aktual'],
                'jam_selesai_aktual'   => $validated['jam_selesai_aktual'],
                'materi_disampaikan'   => $validated['materi_disampaikan'],
                'tautan_materi'        => $validated['tautan_materi'] ?? null,
                'jumlah_peserta_hadir' => (int) $validated['jumlah_peserta_hadir'],
                'status_konfirmasi'    => $validated['status_konfirmasi'],
                'catatan'              => $validated['catatan'] ?? null,
                'kendala_kegiatan'     => $validated['kendala_kegiatan'] ?? null,
                'hasil_pembelajaran'   => $validated['hasil_pembelajaran'] ?? null,
                'file_dokumentasi'     => $filePath,
                'confirmed_at'         => $validated['status_konfirmasi'] === 'dikonfirmasi' ? now() : null,
                'status_verifikasi'    => 'menunggu',
                'verified_by'          => null,
                'verified_at'          => null,
            ];

            if ($aktivitas) {
                $aktivitas->update($payload);
            } else {
                $aktivitas = AktivitasMengajar::create($payload);
            }

            AktivitasMengajar::where('instruktur_id', $instruktur->id)
                ->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $tanggalLaporan)
                ->where('id', '!=', $aktivitas->id)
                ->delete();

            if ($isPemilikJadwal && ! $jadwal->is_pola_mingguan && $jadwal->status !== 'dibatalkan' && $validated['status_konfirmasi'] === 'dikonfirmasi') {
                $jadwal->update(['status' => 'selesai']);
            }
        });

        if ($oldFileToDelete !== null) {
            Storage::disk('local')->delete($oldFileToDelete);
            Storage::disk('public')->delete($oldFileToDelete);
        }

        return back()->with('success', "Laporan aktivitas mengajar untuk kelas {$jadwal->nama_kelas} ({$validated['materi_disampaikan']}) berhasil disimpan dan menunggu verifikasi Supervisor.");
    }

    public function exportExcel(Request $request, SimpleXlsxExporter $exporter)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        [$dari, $sampai] = $this->resolveOptionalDateRange($request);
        $jadwalFilter = $request->filled('jadwal_id') ? (string) $request->input('jadwal_id') : '';
        $sort         = in_array((string) $request->input('sort'), ['terbaru', 'terlama'], true)
            ? (string) $request->input('sort')
            : 'terbaru';

        $rows = $this->buildFilteredRiwayatQuery(
            (int) $instruktur->id,
            $dari,
            $sampai,
            $jadwalFilter,
            $sort
        )->get();

        $excelRows = $rows->map(function (AktivitasMengajar $ra): array {
            $jamMulai   = $ra->jam_mulai_aktual ? substr((string) $ra->jam_mulai_aktual, 0, 5) : '-';
            $jamSelesai = $ra->jam_selesai_aktual ? substr((string) $ra->jam_selesai_aktual, 0, 5) : '-';

            return [
                $ra->tanggal ? $ra->tanggal->format('d/m/Y') : '-',
                $ra->jadwal?->nama_kelas ?? '-',
                $ra->materi_disampaikan ?? '-',
                "{$jamMulai} - {$jamSelesai} WIB",
                $ra->durasi_aktual_label,
                (int) ($ra->jumlah_peserta_hadir ?? 0),
                $ra->status_kelas_info['label'],
                $ra->status_label,
                $ra->verifikasi_label,
                $ra->eskalasi_info['label'] ?? 'Normal',
                $ra->kendala_kegiatan ?? '-',
                $ra->hasil_pembelajaran ?? '-',
            ];
        })->all();

        $kodeSlug = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $instruktur->kode_instruktur) ?: 'instruktur';
        $filename = "riwayat-aktivitas-{$kodeSlug}-" . now('Asia/Jakarta')->format('Ymd') . '.xlsx';

        return $exporter->download($filename, [[
            'name'    => 'Riwayat Aktivitas',
            'headers' => [
                'Tanggal',
                'Kelas',
                'Materi',
                'Jam Aktual',
                'Durasi Aktual',
                'Peserta Hadir',
                'Status Kelas',
                'Konfirmasi',
                'Verifikasi Supervisor',
                'Status Eskalasi',
                'Kendala',
                'Hasil Pembelajaran',
            ],
            'rows'    => $excelRows,
        ]]);
    }

    public function exportPdf(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        [$dari, $sampai] = $this->resolveOptionalDateRange($request);
        $jadwalFilter = $request->filled('jadwal_id') ? (string) $request->input('jadwal_id') : '';
        $sort         = in_array((string) $request->input('sort'), ['terbaru', 'terlama'], true)
            ? (string) $request->input('sort')
            : 'terbaru';

        $riwayatAktivitas = $this->buildFilteredRiwayatQuery(
            (int) $instruktur->id,
            $dari,
            $sampai,
            $jadwalFilter,
            $sort
        )->get();

        $nowWib       = now('Asia/Jakarta');
        $occDari      = $dari !== '' ? $dari : $nowWib->copy()->startOfMonth()->toDateString();
        $occSampai    = $sampai !== '' ? $sampai : $nowWib->toDateString();
        $occSampaiEnd = str_contains($occSampai, ':') ? $occSampai : ($occSampai . ' 23:59:59');

        $jadwalsPeriode = Jadwal::with('instruktur.cutis')
            ->where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->periode($occDari, $occSampai)
            ->get();

        $aktivitasPeriode = AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$occDari, $occSampaiEnd])
            ->get(['id', 'jadwal_id', 'tanggal', 'status_konfirmasi']);

        $sesiTerlewatTotal   = $this->occurrenceService->getMissingActivityOccurrences($jadwalsPeriode, $aktivitasPeriode, $occDari, $occSampai, $nowWib)->count();
        $totalSesiPeriode    = $this->occurrenceService->expandElapsedOccurrences($jadwalsPeriode, $occDari, $occSampai, $nowWib)->count();
        $sesiTerlaporPeriode = max(0, $totalSesiPeriode - $sesiTerlewatTotal);
        $persenKepatuhan     = $totalSesiPeriode > 0 ? round(($sesiTerlaporPeriode / $totalSesiPeriode) * 100, 1) : 100.0;

        return view('pages.instruktur.aktivitas-cetak', compact(
            'instruktur',
            'riwayatAktivitas',
            'dari',
            'sampai',
            'occDari',
            'occSampai',
            'totalSesiPeriode',
            'sesiTerlaporPeriode',
            'persenKepatuhan'
        ));
    }

    public function dokumenAktivitas(Request $request, AktivitasMengajar $aktivitas)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null && (int) $aktivitas->instruktur_id === (int) $instruktur->id, 403);

        $disk = $aktivitas->resolveDokumentasiDisk();
        abort_if($disk === null, 404);

        $safeName = str_replace(['"', '\\', '/', "\r", "\n"], '_', basename($aktivitas->file_dokumentasi));

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($aktivitas->file_dokumentasi, $safeName);
        }

        return Storage::disk($disk)->response($aktivitas->file_dokumentasi, $safeName);
    }
}
