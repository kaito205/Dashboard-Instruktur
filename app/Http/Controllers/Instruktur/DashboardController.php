<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Evaluasi;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Services\JadwalOccurrenceService;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesOwnInstruktur;

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService,
        ?JadwalOccurrenceService $occurrenceService = null
    ) {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public function index(Request $request)
    {
        $defaultPeriodeSetting = Setting::get('default_periode', 'bulan_ini');
        $fallbackPeriode       = in_array($defaultPeriodeSetting, ['hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini'], true)
            ? $defaultPeriodeSetting
            : 'bulan_ini';

        $rawPeriod = $request->filled('periode')
            ? (string) $request->input('periode')
            : $fallbackPeriode;

        $selectedPeriod = match ($rawPeriod) {
            'bulanan' => 'bulan_ini',
            'tahunan' => 'tahun_ini',
            'hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini' => $rawPeriod,
            default => $fallbackPeriode,
        };

        $selectedStatus = (string) $request->input('status', 'all');
        if (!in_array($selectedStatus, ['all', 'hadir', 'terlambat', 'izin', 'tidak_hadir'], true)) {
            $selectedStatus = 'all';
        }

        $search     = trim((string) $request->get('search', $request->get('cari', '')));
        $instruktur = $this->resolveOwnInstruktur();

        $nowWib     = now('Asia/Jakarta');
        $todayStr   = $nowWib->toDateString();
        $hariIni    = Jadwal::hariDariTanggal($nowWib);
        $todayLabel = $nowWib->translatedFormat('l, d F Y');

        [$dari, $sampai] = match ($selectedPeriod) {
            'hari_ini'   => [$todayStr, $todayStr],
            'minggu_ini' => [
                $nowWib->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $nowWib->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ],
            'tahun_ini'  => [
                $nowWib->copy()->startOfYear()->toDateString(),
                $nowWib->copy()->endOfYear()->toDateString(),
            ],
            default      => [
                $nowWib->copy()->startOfMonth()->toDateString(),
                $nowWib->copy()->endOfMonth()->toDateString(),
            ],
        };

        $periodeLabel = match ($selectedPeriod) {
            'hari_ini'   => 'Hari Ini',
            'minggu_ini' => 'Minggu Ini',
            'tahun_ini'  => 'Tahun Ini',
            default      => 'Bulan Ini',
        };

        if (!$instruktur) {
            $lpkAdminMsg = 'Hubungi Admin ' . Setting::getAppIdentity()['lpk_name'];

            return view('pages.dashboard.instruktur', [
                'instruktur'      => null,
                'metrics'         => [
                    'periode_label'            => $periodeLabel,
                    'total_jam_mengajar'       => 0,
                    'total_jam_mengajar_trend' => 'Profil instruktur belum ditautkan',
                    'kelas_diampu'             => 0,
                    'total_kelas_diampu'       => 0,
                    'kelas_diampu_trend'       => $lpkAdminMsg,
                    'total_kelas_diampu_trend' => $lpkAdminMsg,
                    'rata_evaluasi'            => 0,
                    'rating_penilaian'         => '-',
                    'rating_penilaian_skala'   => '/ 100',
                    'rata_evaluasi_trend'      => 'Belum ada evaluasi',
                    'rating_penilaian_trend'   => 'Belum ada evaluasi',
                    'tingkat_kehadiran'        => 0,
                    'kehadiran_personal'       => 0,
                    'hadir_tepat'              => 0,
                    'terlambat'                => 0,
                    'izin'                     => 0,
                    'tidak_hadir'              => 0,
                    'total_absensi'            => 0,
                    'status_filter_aktif'      => null,
                    'status_filter_label'      => null,
                    'status_filter_count'      => null,
                    'status_filter_persentase' => null,
                    'tingkat_kehadiran_trend'  => 'Belum ada presensi',
                ],
                'mySchedule'        => [],
                'nextSchedule'      => null,
                'todayLabel'        => $todayLabel,
                'studentFeedback'   => [],
                'selectedPeriod'    => $selectedPeriod,
                'selectedStatus'    => $selectedStatus,
                'dari'              => $dari,
                'sampai'            => $sampai,
                'search'            => $search,
                'kinerjaData'       => null,
                'personalChartData' => null,
            ]);
        }

        $kinerjaData = $this->kinerjaService->evaluateInstruktur($instruktur, $dari, $sampai);

        $statusLabelMap = [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'izin'        => 'Izin',
            'tidak_hadir' => 'Tidak Hadir',
        ];
        $statusCountMap = [
            'hadir'       => (int) ($kinerjaData['hadir_tepat'] ?? 0),
            'terlambat'   => (int) ($kinerjaData['terlambat'] ?? 0),
            'izin'        => (int) ($kinerjaData['izin'] ?? 0),
            'tidak_hadir' => (int) ($kinerjaData['tidak_hadir'] ?? 0),
        ];
        $totalAbsensi       = (int) ($kinerjaData['total_absensi'] ?? 0);
        $statusFilterAktif  = $selectedStatus !== 'all' ? $selectedStatus : null;
        $statusFilterLabel  = $statusFilterAktif ? $statusLabelMap[$statusFilterAktif] : null;
        $statusFilterCount  = $statusFilterAktif ? $statusCountMap[$statusFilterAktif] : null;
        $statusFilterPersen = $statusFilterAktif !== null
            ? ($totalAbsensi > 0 ? round(($statusFilterCount / $totalAbsensi) * 100, 1) : 0.0)
            : null;

        $metrics = [
            'periode_label'            => $periodeLabel,
            'total_jam_mengajar'       => $kinerjaData['jam_mengajar'],
            'total_jam_mengajar_trend' => $kinerjaData['total_jadwal'] . ' jadwal aktif/selesai pada periode ini',
            'kelas_diampu'             => $kinerjaData['total_jadwal'],
            'total_kelas_diampu'       => $kinerjaData['total_jadwal'],
            'kelas_diampu_trend'       => $kinerjaData['total_aktivitas'] . ' laporan aktivitas dikonfirmasi',
            'total_kelas_diampu_trend' => $kinerjaData['total_aktivitas'] . ' laporan aktivitas dikonfirmasi',
            'rata_evaluasi'            => $kinerjaData['rata_rata_nilai'] ?? 0,
            'rating_penilaian'         => $kinerjaData['rata_rata_nilai'] !== null ? number_format((float) $kinerjaData['rata_rata_nilai'], 2) : '-',
            'rating_penilaian_skala'   => '/ 100',
            'rata_evaluasi_trend'      => $kinerjaData['total_evaluasi'] > 0
                ? 'Rata-rata ' . number_format((float) $kinerjaData['rata_rata_evaluasi_skala5'], 2) . '/5.00 (' . $kinerjaData['total_evaluasi'] . ' evaluasi)'
                : 'Belum ada evaluasi pada periode ini',
            'rating_penilaian_trend'   => $kinerjaData['total_evaluasi'] > 0
                ? 'Rata-rata ' . number_format((float) $kinerjaData['rata_rata_evaluasi_skala5'], 2) . '/5.00 (' . $kinerjaData['total_evaluasi'] . ' evaluasi)'
                : 'Belum ada evaluasi pada periode ini',
            'tingkat_kehadiran'        => $kinerjaData['persentase_kehadiran'],
            'kehadiran_personal'       => $kinerjaData['persentase_kehadiran'],
            'hadir_tepat'              => $statusCountMap['hadir'],
            'terlambat'                => $statusCountMap['terlambat'],
            'izin'                     => $statusCountMap['izin'],
            'tidak_hadir'              => $statusCountMap['tidak_hadir'],
            'total_absensi'            => $totalAbsensi,
            'status_filter_aktif'      => $statusFilterAktif,
            'status_filter_label'      => $statusFilterLabel,
            'status_filter_count'      => $statusFilterCount,
            'status_filter_persentase' => $statusFilterPersen,
            'tingkat_kehadiran_trend'  => $totalAbsensi > 0
                ? $statusCountMap['hadir'] . ' tepat waktu, ' . $statusCountMap['terlambat'] . ' terlambat'
                : 'Belum ada catatan presensi pada periode ini',
        ];

        $todayJadwals = Jadwal::with([
            'instruktur.cutis',
            'aktivitasMengajars' => fn ($q) => $q->whereDate('tanggal', $todayStr)->orderByDesc('id'),
            'absensis'           => fn ($q) => $q->whereDate('tanggal', $todayStr),
        ])
            ->where('instruktur_id', $instruktur->id)
            ->where(function ($q) use ($todayStr, $hariIni) {
                $q->whereDate('tanggal', $todayStr)
                    ->orWhere(function ($sub) use ($hariIni, $todayStr) {
                        $sub->where('is_pola_mingguan', true)
                            ->where('hari', $hariIni)
                            ->where(fn ($p) => $p->whereNull('periode_mulai')->orWhereDate('periode_mulai', '<=', $todayStr))
                            ->where(fn ($p) => $p->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', $todayStr));
                    });
            })
            ->orderBy('jam_mulai')
            ->get();

        $bukaSebelumMenit = (int) \App\Models\Setting::get('checkin_buka_sebelum_menit', '30');

        $mySchedule = $todayJadwals->map(function (Jadwal $j) use ($todayStr, $nowWib, $bukaSebelumMenit) {
            $aktHariIni   = $j->aktivitasMengajars->first();
            $konfirmasi   = $aktHariIni?->status_konfirmasi ?? 'belum_dikonfirmasi';
            $absenHariIni = $j->absensis->first();
            $liveStatus   = $this->occurrenceService->resolveLiveSessionStatus($j, $todayStr, $absenHariIni, $nowWib);
            $eligibility  = $this->occurrenceService->resolveCheckinEligibility($j, $todayStr, $absenHariIni, $bukaSebelumMenit, $nowWib);
            $hasCheckin   = (bool) ($absenHariIni && $absenHariIni->jam_checkin);
            $hasCheckout  = (bool) ($absenHariIni && $absenHariIni->jam_checkout);
            $isDibatalkan = $j->status === 'dibatalkan';

            return [
                'id'                => 'JDW-' . str_pad((string) $j->id, 3, '0', STR_PAD_LEFT),
                'jadwal_id'         => $j->id,
                'tanggal'           => $j->tanggal_label,
                'time'              => substr((string) $j->jam_mulai, 0, 5) . ' - ' . substr((string) $j->jam_selesai, 0, 5) . ' WIB',
                'course'            => $j->nama_kelas . ($j->materi ? ' - ' . $j->materi : ''),
                'room'              => $j->ruangan_label,
                'participants'      => (int) ($j->jumlah_peserta ?? 0),
                'status'            => $liveStatus['label'],
                'status_color'      => $liveStatus['color'],
                'status_code'       => $liveStatus['code'],
                'is_hari_ini'       => true,
                'konfirmasi_status' => $konfirmasi,
                'konfirmasi_label'  => $aktHariIni?->status_label ?? 'Belum Dikonfirmasi',
                'absensi_hari_ini'  => $absenHariIni,
                'tanggal_sesi'      => $todayStr,
                'has_checkin'       => $hasCheckin,
                'has_checkout'      => $hasCheckout,
                'can_checkin'       => (bool) ($eligibility['can_checkin'] ?? false),
                'can_checkout'      => (bool) ($eligibility['can_checkout'] ?? false),
                'checkin_reason'    => $eligibility['reason'] ?? null,
                'is_dibatalkan'     => $isDibatalkan,
                'can_fill_journal'  => !$isDibatalkan && ($hasCheckin || in_array($liveStatus['code'], ['berlangsung', 'berlangsung_belum_checkin', 'selesai', 'terlewat'], true)),
            ];
        })->all();

        $nextSchedule = null;
        if ($todayJadwals->isEmpty()) {
            $activeJadwals = Jadwal::where('instruktur_id', $instruktur->id)
                ->where('status', '!=', 'dibatalkan')
                ->orderBy('jam_mulai')
                ->get();

            $candidates = [];
            $tomorrow   = $nowWib->copy()->addDay()->startOfDay();

            foreach ($activeJadwals as $j) {
                if (!$j->is_pola_mingguan) {
                    if ($j->tanggal && $j->tanggal->toDateString() > $todayStr) {
                        $candidates[] = [
                            'date'   => $j->tanggal->toDateString(),
                            'jam'    => substr((string) $j->jam_mulai, 0, 5),
                            'jadwal' => $j,
                        ];
                    }
                    continue;
                }

                if (!$j->hari) {
                    continue;
                }

                $startCursor = $tomorrow->copy();
                if ($j->periode_mulai && $j->periode_mulai->copy()->startOfDay()->gt($startCursor)) {
                    $startCursor = $j->periode_mulai->copy()->startOfDay();
                }

                $endLimit = $j->periode_selesai ? $j->periode_selesai->copy()->startOfDay() : null;

                for ($offset = 0; $offset < 7; $offset++) {
                    $checkDate = $startCursor->copy()->addDays($offset);
                    if ($endLimit && $checkDate->gt($endLimit)) {
                        break;
                    }
                    if (Jadwal::hariDariTanggal($checkDate) === $j->hari) {
                        $candidates[] = [
                            'date'   => $checkDate->toDateString(),
                            'jam'    => substr((string) $j->jam_mulai, 0, 5),
                            'jadwal' => $j,
                        ];
                        break;
                    }
                }
            }

            if ($candidates !== []) {
                usort($candidates, function (array $a, array $b) {
                    $cmp = strcmp($a['date'], $b['date']);
                    return $cmp !== 0 ? $cmp : strcmp($a['jam'], $b['jam']);
                });

                $best       = $candidates[0];
                $bestDate   = $best['date'];
                $bestJadwal = $best['jadwal'];

                $nextSchedule = [
                    'tanggal_label' => Carbon::parse($bestDate, 'Asia/Jakarta')->translatedFormat('l, d M Y'),
                    'time'          => substr((string) $bestJadwal->jam_mulai, 0, 5) . ' - ' . substr((string) $bestJadwal->jam_selesai, 0, 5) . ' WIB',
                    'course'        => $bestJadwal->nama_kelas . ($bestJadwal->materi ? ' - ' . $bestJadwal->materi : ''),
                    'room'          => $bestJadwal->ruangan_label,
                    'tanggal_sesi'  => $bestDate,
                ];
            }
        }

        $evaluasiTerbaru = Evaluasi::with('kodeEvaluasi')
            ->where('instruktur_id', $instruktur->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('materi_evaluasi', 'like', "%{$search}%")
                        ->orWhere('komentar', 'like', "%{$search}%")
                        ->orWhereHas('kodeEvaluasi', function ($kodeQ) use ($search) {
                            $kodeQ->where('nama_kelas', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->limit(5)
            ->get();

        $studentFeedback = $evaluasiTerbaru->map(function (Evaluasi $ev) {
            $comment     = $ev->komentar_peserta ?: 'Peserta memberikan penilaian skor tanpa catatan teks tambahan.';
            $courseLabel = ($ev->kodeEvaluasi?->nama_kelas ?? $ev->kodeEvaluasi?->kode ?? 'Evaluasi Kelas') . ($ev->materi_evaluasi ? ' - ' . $ev->materi_evaluasi : '');

            // Akses buta bagi instruktur: identitas peserta disembunyikan pada panel instruktur.
            return [
                'id'           => 'FB-' . $ev->id,
                'student_name' => 'Peserta Anonim',
                'pengisi'      => 'Peserta Anonim',
                'course'       => $courseLabel,
                'course_class' => $courseLabel,
                'date'         => $ev->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y') ?? '-',
                'rating'       => round((float) ($ev->total_nilai ?? 0), 1),
                'skala5'       => $ev->rata_rata_skala5,
                'comment'      => $comment,
            ];
        })->all();

        $sampaiEnd = str_contains($sampai, ':') ? $sampai : ($sampai . ' 23:59:59');

        $allAbsensisPeriodeRaw = Absensi::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->get(['id', 'instruktur_id', 'jadwal_id', 'tanggal', 'status']);
        $allAbsensisPeriode = $this->occurrenceService->filterValidAttendanceRecords($allAbsensisPeriodeRaw);

        $jadwalsPeriode = Jadwal::with('instruktur.cutis')
            ->where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->periode($dari, $sampai)
            ->get();

        $missedDates = $this->occurrenceService
            ->getMissedAttendanceOccurrences($jadwalsPeriode, $allAbsensisPeriode, $dari, $sampai, $nowWib)
            ->pluck('tanggal');

        $hadirDates     = $allAbsensisPeriode->where('status', 'hadir')->map(fn (Absensi $a) => $a->tanggal?->toDateString())->filter()->values();
        $terlambatDates = $allAbsensisPeriode->where('status', 'terlambat')->map(fn (Absensi $a) => $a->tanggal?->toDateString())->filter()->values();
        $izinDates      = $allAbsensisPeriode->where('status', 'izin')->map(fn (Absensi $a) => $a->tanggal?->toDateString())->filter()->values();
        $alpaDates      = $allAbsensisPeriode->where('status', 'tidak_hadir')->map(fn (Absensi $a) => $a->tanggal?->toDateString())->filter()->concat($missedDates)->values();

        $jurnalDates = AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->where('status_konfirmasi', 'dikonfirmasi')
            ->where(function ($q) {
                $q->whereNull('jadwal_id')
                    ->orWhereHas('jadwal', fn ($jq) => $jq->where('status', '!=', 'dibatalkan'));
            })
            ->orderByDesc('id')
            ->get(['id', 'jadwal_id', 'tanggal'])
            ->unique(fn (AktivitasMengajar $ak) => ($ak->jadwal_id ?? '0') . '|' . ($ak->tanggal?->toDateString() ?? (string) $ak->id))
            ->map(fn (AktivitasMengajar $ak) => $ak->tanggal?->toDateString())
            ->filter()
            ->values();

        $labels        = [];
        $tooltipLabels = [];
        $bucketFilter  = [];

        if ($selectedPeriod === 'tahun_ini') {
            $bulanSingkat  = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $tahunBerjalan = (int) $nowWib->year;
            for ($m = 1; $m <= 12; $m++) {
                $labels[]        = $bulanSingkat[$m - 1];
                $tooltipLabels[] = Carbon::create($tahunBerjalan, $m, 1, 0, 0, 0, 'Asia/Jakarta')->translatedFormat('F Y');
                $bucketFilter[]  = fn (string $tgl) => (int) substr($tgl, 5, 2) === $m;
            }
            $periodText = 'per bulan tahun ' . $tahunBerjalan;
        } elseif ($selectedPeriod === 'minggu_ini') {
            $hariSingkat = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            $cursor      = Carbon::parse($dari, 'Asia/Jakarta')->startOfDay();
            for ($i = 0; $i < 7; $i++) {
                $dStr            = $cursor->toDateString();
                $labels[]        = $hariSingkat[$i];
                $tooltipLabels[] = $cursor->translatedFormat('l, d M Y');
                $bucketFilter[]  = fn (string $tgl) => $tgl === $dStr;
                $cursor->addDay();
            }
            $periodText = 'minggu ini';
        } elseif ($selectedPeriod === 'hari_ini') {
            $labels[]        = $nowWib->translatedFormat('d M');
            $tooltipLabels[] = $nowWib->translatedFormat('l, d F Y');
            $bucketFilter[]  = fn (string $tgl) => $tgl === $todayStr;
            $periodText      = 'hari ini';
        } else {
            $daysInMonth = (int) $nowWib->daysInMonth;
            $bulanNama   = $nowWib->translatedFormat('M Y');
            $buckets     = [
                [1, 7],
                [8, 14],
                [15, 21],
                [22, min(28, $daysInMonth)],
            ];
            if ($daysInMonth > 28) {
                $buckets[] = [29, $daysInMonth];
            }
            foreach ($buckets as $idx => [$startDay, $endDay]) {
                $labels[]        = 'Mgg ' . ($idx + 1);
                $tooltipLabels[] = "{$startDay} - {$endDay} {$bulanNama}";
                $bucketFilter[]  = function (string $tgl) use ($startDay, $endDay) {
                    $d = (int) substr($tgl, 8, 2);
                    return $d >= $startDay && $d <= $endDay;
                };
            }
            $periodText = 'per minggu pada ' . $nowWib->translatedFormat('F Y');
        }

        $countSeries = function ($datesCollection) use ($bucketFilter): array {
            $out = [];
            foreach ($bucketFilter as $fn) {
                $out[] = $datesCollection->filter($fn)->count();
            }
            return $out;
        };

        $seriesMap = [
            'hadir'       => ['name' => 'Hadir',     'color' => '#10B981', 'data' => $countSeries($hadirDates)],
            'terlambat'   => ['name' => 'Terlambat', 'color' => '#D97706', 'data' => $countSeries($terlambatDates)],
            'izin'        => ['name' => 'Izin',      'color' => '#0EA5E9', 'data' => $countSeries($izinDates)],
            'tidak_hadir' => ['name' => 'Alpa',      'color' => '#EF4444', 'data' => $countSeries($alpaDates)],
        ];
        $jurnalCounts     = $countSeries($jurnalDates);
        $jurnalSeriesItem = ['name' => 'Jurnal', 'color' => '#033DF6', 'data' => $jurnalCounts];

        if ($selectedStatus === 'all') {
            $stacked       = true;
            $trendSeries   = [$seriesMap['hadir'], $seriesMap['terlambat'], $seriesMap['izin'], $seriesMap['tidak_hadir']];
            $trendSubtitle = 'Rekap Hadir, Terlambat, Izin, dan Alpa ' . $periodText;
        } else {
            $stacked       = false;
            $chosen        = $seriesMap[$selectedStatus];
            $trendSeries   = [$chosen, $jurnalSeriesItem];
            $trendSubtitle = 'Perbandingan ' . $chosen['name'] . ' dan Jurnal ' . $periodText;
        }

        $trendData = [
            'subtitle'       => $trendSubtitle,
            'stacked'        => $stacked,
            'labels'         => $labels,
            'tooltip_labels' => $tooltipLabels,
            'series'         => $trendSeries,
            'jurnal'         => $jurnalCounts,
        ];

        $komponenData = [
            'labels'         => ['Kehadiran', 'Aktivitas', 'Evaluasi', 'Observasi'],
            'values'         => [
                round((float) ($kinerjaData['skor_kehadiran'] ?? 0), 1),
                round((float) ($kinerjaData['skor_aktivitas'] ?? 0), 1),
                round((float) ($kinerjaData['skor_evaluasi'] ?? 0), 1),
                round((float) ($kinerjaData['skor_observasi'] ?? 0), 1),
            ],
            'available'      => [
                $kinerjaData['skor_kehadiran'] !== null,
                $kinerjaData['skor_aktivitas'] !== null,
                $kinerjaData['skor_evaluasi'] !== null,
                $kinerjaData['skor_observasi'] !== null,
            ],
            'skor_akhir'     => $kinerjaData['skor_akhir'],
            'kategori_label' => $kinerjaData['kategori_label'],
        ];

        $personalChartData = [
            'trend'    => $trendData,
            'komponen' => $komponenData,
        ];

        return view('pages.dashboard.instruktur', compact(
            'instruktur',
            'metrics',
            'mySchedule',
            'nextSchedule',
            'todayLabel',
            'studentFeedback',
            'selectedPeriod',
            'selectedStatus',
            'dari',
            'sampai',
            'search',
            'kinerjaData',
            'personalChartData'
        ));
    }
}
