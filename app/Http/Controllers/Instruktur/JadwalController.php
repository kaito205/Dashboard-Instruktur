<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Services\JadwalOccurrenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalController extends Controller
{
    use ResolvesOwnInstruktur;

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(?JadwalOccurrenceService $occurrenceService = null)
    {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public function index(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403, 'Profil instruktur belum ditautkan ke akun Anda.');

        $search     = trim((string) ($request->input('search') ?? $request->input('cari') ?? ''));
        $hariFilter = (string) ($request->input('hari') ?? '');
        try {
            $tanggalSesi = $request->filled('tanggal_sesi')
                ? Carbon::parse((string) $request->input('tanggal_sesi'))->toDateString()
                : today()->toDateString();
        } catch (\Throwable) {
            $tanggalSesi = today()->toDateString();
        }
        $hariTanggal = Jadwal::hariDariTanggal($tanggalSesi);

        $tampilan = in_array((string) $request->input('tampilan'), ['daftar', 'kalender'], true)
            ? (string) $request->input('tampilan')
            : 'daftar';
        $skalaKalender = in_array((string) $request->input('skala_kalender'), ['harian', 'mingguan', 'bulanan'], true)
            ? (string) $request->input('skala_kalender')
            : 'bulanan';

        try {
            $calAnchor = $request->filled('tanggal_kalender')
                ? Carbon::parse((string) $request->input('tanggal_kalender'))->startOfDay()
                : today();
        } catch (\Throwable) {
            $calAnchor = today();
        }

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

        $allInstrukturJadwals = Jadwal::with([
            'instruktur',
            'instrukturAsli',
            'instrukturPengganti',
            'kelas',
            'aktivitasMengajar',
            'absensis' => fn ($q) => $q->whereDate('tanggal', $tanggalSesi),
        ])
            ->where('instruktur_id', $instruktur->id)
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderBy('jam_mulai')
            ->get();

        $jadwals = $allInstrukturJadwals
            ->filter(function (Jadwal $j) use ($search, $hariFilter): bool {
                if ($hariFilter !== '' && $j->hari !== $hariFilter) {
                    return false;
                }
                if ($search !== '') {
                    $needle = mb_strtolower($search);
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $j->nama_kelas,
                        $j->kode_kelas,
                        $j->materi,
                        $j->ruangan,
                        $j->hari,
                    ])));
                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }
                return true;
            })
            ->values();

        $jadwalPadaTanggal = $jadwals
            ->filter(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tanggalSesi))
            ->sortBy('jam_mulai')
            ->values();

        $jadwalPolaMingguan = $jadwals
            ->where('is_pola_mingguan', true)
            ->values();

        $jadwalBerikutnya = null;
        if ($jadwalPadaTanggal->isEmpty()) {
            $mulaiCari = Carbon::parse($tanggalSesi)->addDay()->startOfDay();
            $kandidatBerikutnya = [];
            foreach ($allInstrukturJadwals as $j) {
                $tglNext = $j->tanggalTerdekatBerikutnya($mulaiCari, 30);
                if ($tglNext !== null) {
                    $kandidatBerikutnya[] = [
                        'jadwal'        => $j,
                        'tanggal'       => $tglNext,
                        'tanggal_sesi'  => $tglNext->toDateString(),
                        'hari'          => Jadwal::hariDariTanggal($tglNext),
                    ];
                }
            }
            if ($kandidatBerikutnya !== []) {
                usort($kandidatBerikutnya, function (array $a, array $b): int {
                    $cmp = strcmp($a['tanggal_sesi'], $b['tanggal_sesi']);
                    return $cmp !== 0 ? $cmp : strcmp((string) $a['jadwal']->jam_mulai, (string) $b['jadwal']->jam_mulai);
                });
                $jadwalBerikutnya = $kandidatBerikutnya[0];
            }
        }

        $calendarEvents = collect();
        if ($tampilan === 'kalender') {
            $calendarMap = [];
            foreach ($jadwals->where('is_pola_mingguan', false) as $ev) {
                if ($ev->tanggal) {
                    $evDate = $ev->tanggal->copy()->startOfDay();
                    if ($evDate->gte($calRangeStart) && $evDate->lte($calRangeEnd)) {
                        $calendarMap[$ev->tanggal->toDateString()][] = $ev;
                    }
                }
            }

            $polaEvents = $jadwals->where('is_pola_mingguan', true);
            if ($polaEvents->isNotEmpty()) {
                $cursor = $calRangeStart->copy()->startOfDay();
                while ($cursor->lte($calRangeEnd)) {
                    $dateKey    = $cursor->toDateString();
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
        }

        $belumCheckoutList = Absensi::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->whereNotNull('jam_checkin')
            ->whereNull('jam_checkout')
            ->orderByDesc('tanggal')
            ->limit(5)
            ->get();

        try {
            $dariAbsensi = $request->filled('dari_absensi')
                ? Carbon::parse((string) $request->input('dari_absensi'))->toDateString()
                : '';
        } catch (\Throwable) {
            $dariAbsensi = '';
        }
        try {
            $sampaiAbsensi = $request->filled('sampai_absensi')
                ? Carbon::parse((string) $request->input('sampai_absensi'))->toDateString()
                : '';
        } catch (\Throwable) {
            $sampaiAbsensi = '';
        }
        if ($dariAbsensi !== '' && $sampaiAbsensi !== '' && $dariAbsensi > $sampaiAbsensi) {
            [$dariAbsensi, $sampaiAbsensi] = [$sampaiAbsensi, $dariAbsensi];
        }

        $riwayatAbsensiQuery = Absensi::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->when($dariAbsensi !== '', fn ($q) => $q->whereDate('tanggal', '>=', $dariAbsensi))
            ->when($sampaiAbsensi !== '', fn ($q) => $q->whereDate('tanggal', '<=', $sampaiAbsensi));

        $allFilteredAbsensi = (clone $riwayatAbsensiQuery)->get(['status', 'tanggal', 'jam_checkin', 'jam_checkout']);
        $totalTercatat      = $allFilteredAbsensi->count();
        $hadirTepatAbs      = $allFilteredAbsensi->where('status', 'hadir')->count();
        $terlambatAbs       = $allFilteredAbsensi->where('status', 'terlambat')->count();
        $izinAbs            = $allFilteredAbsensi->whereIn('status', ['izin', 'sakit'])->count();
        $tidakHadirAbs      = $allFilteredAbsensi->whereIn('status', ['tidak_hadir', 'alpha'])->count();
        $belumCheckoutAbs   = $allFilteredAbsensi
            ->filter(fn ($row) => $row->jam_checkin !== null && $row->jam_checkout === null)
            ->count();

        $earliestAbsensiDate = $allFilteredAbsensi
            ->pluck('tanggal')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->min();

        $periodeDari = $dariAbsensi !== ''
            ? $dariAbsensi
            : ($earliestAbsensiDate
                ? min(today()->startOfMonth()->toDateString(), $earliestAbsensiDate)
                : today()->startOfMonth()->toDateString());
        $periodeSampai = $sampaiAbsensi !== '' ? $sampaiAbsensi : today()->toDateString();

        $totalTerjadwal = $this->occurrenceService
            ->expandElapsedOccurrences($allInstrukturJadwals, $periodeDari, $periodeSampai)
            ->count();

        $totalSesiPeriode        = max($totalTerjadwal, $totalTercatat);
        $belumHadirTanpaCatatan  = max(0, $totalSesiPeriode - $totalTercatat);
        $persenHadirAbs          = $totalSesiPeriode > 0
            ? round((($hadirTepatAbs + $terlambatAbs) / $totalSesiPeriode) * 100, 1)
            : 0.0;

        $rekapAbsensi = [
            'total'                     => $totalSesiPeriode,
            'total_terjadwal'           => $totalTerjadwal,
            'total_tercatat'            => $totalTercatat,
            'hadir'                     => $hadirTepatAbs,
            'terlambat'                 => $terlambatAbs,
            'izin'                      => $izinAbs,
            'tidak_hadir'               => $tidakHadirAbs,
            'belum_hadir_tanpa_catatan' => $belumHadirTanpaCatatan,
            'belum_checkout'            => $belumCheckoutAbs,
            'persentase'                => $persenHadirAbs,
            'periode_dari'              => $periodeDari,
            'periode_sampai'            => $periodeSampai,
        ];

        $riwayatAbsensi = $riwayatAbsensiQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $toleransiMenit   = (int) Setting::get('toleransi_terlambat_menit', '15');
        $bukaSebelumMenit = (int) Setting::get('checkin_buka_sebelum_menit', '30');

        $statusSesiMap  = [];
        $eligibilityMap = [];
        foreach ($jadwalPadaTanggal as $j) {
            $tglTarget             = $j->tanggal ? $j->tanggal->toDateString() : $tanggalSesi;
            $absenSesi             = $j->absensis->first();
            $statusSesiMap[$j->id]  = $this->occurrenceService->resolveLiveSessionStatus($j, $tglTarget, $absenSesi);
            $eligibilityMap[$j->id] = $this->occurrenceService->resolveCheckinEligibility($j, $tglTarget, $absenSesi, $bukaSebelumMenit);
        }

        return view('pages.instruktur.jadwal', compact(
            'instruktur',
            'jadwals',
            'jadwalPadaTanggal',
            'jadwalPolaMingguan',
            'jadwalBerikutnya',
            'statusSesiMap',
            'eligibilityMap',
            'riwayatAbsensi',
            'rekapAbsensi',
            'dariAbsensi',
            'sampaiAbsensi',
            'belumCheckoutList',
            'tampilan',
            'skalaKalender',
            'calAnchor',
            'calRangeStart',
            'calRangeEnd',
            'calendarEvents',
            'search',
            'hariFilter',
            'tanggalSesi',
            'hariTanggal',
            'toleransiMenit',
            'bukaSebelumMenit'
        ));
    }

    public function checkin(Request $request, Jadwal $jadwal)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur && (int) $jadwal->instruktur_id === (int) $instruktur->id, 403);

        if ($jadwal->status === 'dibatalkan') {
            return back()->with('error', 'Jadwal yang dibatalkan tidak dapat dilakukan Check-in.');
        }

        $validated = $request->validate([
            'tanggal_sesi' => ['nullable', 'date'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi_gps'  => ['nullable', 'numeric', 'min:0'],
            'status_gps'   => ['nullable', 'in:terdeteksi,tidak_tersedia,ditolak_perangkat'],
            'keterangan'   => ['nullable', 'string', 'max:255'],
        ]);

        $tanggalSesi = $jadwal->tanggal
            ? $jadwal->tanggal->toDateString()
            : ($validated['tanggal_sesi'] ?? today()->toDateString());

        $jamMulaiJadwal   = substr((string) $jadwal->jam_mulai, 0, 5);
        $bukaSebelumMenit = (int) Setting::get('checkin_buka_sebelum_menit', '30');
        $sekarang         = now();

        $jamCheckin      = $sekarang->format('H:i');
        $kalkulasiLambat = Absensi::hitungKeterlambatan($jamMulaiJadwal, $jamCheckin);

        $hasKoordinat = isset($validated['latitude'], $validated['longitude'])
            && is_numeric($validated['latitude'])
            && is_numeric($validated['longitude']);

        $statusGps = $hasKoordinat
            ? 'terdeteksi'
            : ($validated['status_gps'] ?? 'tidak_tersedia');

        $keteranganDefault = $kalkulasiLambat['status'] === 'terlambat'
            ? "Check-in aplikasi (terlambat {$kalkulasiLambat['menit_terlambat']} menit dari jadwal {$jamMulaiJadwal})"
            : "Check-in aplikasi tepat waktu (jadwal {$jamMulaiJadwal})";

        $checkinError = DB::transaction(function () use (
            $instruktur,
            $jadwal,
            $tanggalSesi,
            $jamMulaiJadwal,
            $bukaSebelumMenit,
            $jamCheckin,
            $kalkulasiLambat,
            $sekarang,
            $hasKoordinat,
            $validated,
            $statusGps,
            $keteranganDefault
        ): ?string {
            Jadwal::whereKey($jadwal->id)->lockForUpdate()->first();

            $existing = Absensi::where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $tanggalSesi)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->jam_checkin !== null) {
                return "Anda sudah melakukan Check-in untuk sesi kelas {$jadwal->nama_kelas} ({$jadwal->materi}) pada tanggal {$tanggalSesi}.";
            }

            $eligibility = $this->occurrenceService->resolveCheckinEligibility(
                $jadwal,
                $tanggalSesi,
                $existing,
                $bukaSebelumMenit,
                $sekarang
            );

            if (! $eligibility['can_checkin']) {
                return $eligibility['reason'] ?? 'Check-in tidak tersedia untuk sesi ini.';
            }

            $payload = [
                'instruktur_id'     => $instruktur->id,
                'jadwal_id'         => $jadwal->id,
                'tanggal'           => $tanggalSesi,
                'jam_mulai_jadwal'  => $jamMulaiJadwal,
                'jam_checkin'       => $jamCheckin,
                'menit_terlambat'   => $kalkulasiLambat['menit_terlambat'],
                'checkin_at'        => $sekarang,
                'status'            => $kalkulasiLambat['status'],
                'sumber'            => 'checkin_app',
                'latitude_checkin'  => $hasKoordinat ? (float) $validated['latitude'] : null,
                'longitude_checkin' => $hasKoordinat ? (float) $validated['longitude'] : null,
                'akurasi_gps'       => isset($validated['akurasi_gps']) && $validated['akurasi_gps'] !== '' ? (float) $validated['akurasi_gps'] : null,
                'status_gps'        => $statusGps,
                'keterangan'        => $validated['keterangan'] ?? $keteranganDefault,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Absensi::create($payload);
            }

            if (! $jadwal->is_pola_mingguan && $jadwal->status !== 'dibatalkan') {
                $jadwal->update(['status' => 'berlangsung']);
            }

            return null;
        });

        if ($checkinError !== null) {
            return back()->with('error', $checkinError);
        }

        $infoLambat = $kalkulasiLambat['status'] === 'terlambat'
            ? "Status: Terlambat ({$kalkulasiLambat['menit_terlambat']} menit, melebihi toleransi {$kalkulasiLambat['toleransi_menit']} menit)."
            : "Status: Hadir Tepat Waktu (selisih {$kalkulasiLambat['menit_terlambat']} menit, dalam batas toleransi {$kalkulasiLambat['toleransi_menit']} menit).";

        return back()->with('success', "Check-in berhasil pada pukul {$jamCheckin} WIB untuk kelas {$jadwal->nama_kelas} ({$jadwal->materi}). {$infoLambat} Status GPS: {$statusGps}.");
    }

    public function checkout(Request $request, Jadwal $jadwal)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur && (int) $jadwal->instruktur_id === (int) $instruktur->id, 403);

        $validated = $request->validate([
            'tanggal_sesi' => ['nullable', 'date'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi_gps'  => ['nullable', 'numeric', 'min:0'],
            'status_gps'   => ['nullable', 'in:terdeteksi,tidak_tersedia,ditolak_perangkat'],
        ]);

        $tanggalSesi = $jadwal->tanggal
            ? $jadwal->tanggal->toDateString()
            : ($validated['tanggal_sesi'] ?? today()->toDateString());

        $absensi = Absensi::where('jadwal_id', $jadwal->id)
            ->whereDate('tanggal', $tanggalSesi)
            ->first();

        if (!$absensi || $absensi->jam_checkin === null) {
            return back()->with('error', 'Anda belum melakukan Check-in pada sesi kelas ini. Lakukan Check-in terlebih dahulu sebelum Check-out.');
        }

        if ($absensi->jam_checkout !== null) {
            return back()->with('error', "Anda sudah melakukan Check-out untuk sesi kelas {$jadwal->nama_kelas} pada pukul {$absensi->jam_checkout}.");
        }

        $hasKoordinat = isset($validated['latitude'], $validated['longitude'])
            && is_numeric($validated['latitude'])
            && is_numeric($validated['longitude']);

        $sekarang    = now();
        $jamCheckout = $sekarang->format('H:i');

        DB::transaction(function () use ($absensi, $jadwal, $jamCheckout, $sekarang, $hasKoordinat, $validated) {
            $updateData = [
                'jam_checkout' => $jamCheckout,
                'checkout_at'  => $sekarang,
            ];

            if ($hasKoordinat) {
                $updateData['latitude_checkout']  = (float) $validated['latitude'];
                $updateData['longitude_checkout'] = (float) $validated['longitude'];
                $updateData['status_gps']         = 'terdeteksi';
                if (isset($validated['akurasi_gps']) && $validated['akurasi_gps'] !== '') {
                    $updateData['akurasi_gps'] = (float) $validated['akurasi_gps'];
                }
            } elseif (!$absensi->status_gps && !empty($validated['status_gps'])) {
                $updateData['status_gps'] = $validated['status_gps'];
            }

            $absensi->update($updateData);

            if (! $jadwal->is_pola_mingguan && $jadwal->status !== 'dibatalkan') {
                $jadwal->update(['status' => 'selesai']);
            }
        });

        return back()->with('success', "Check-out berhasil dicatat pada pukul {$jamCheckout} WIB untuk sesi kelas {$jadwal->nama_kelas}.");
    }
}
