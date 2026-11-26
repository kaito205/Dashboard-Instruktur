<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Evaluasi;
use App\Models\ImportHistory;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Services\JadwalOccurrenceService;
use App\Services\KinerjaCalculationService;
use App\Services\OperationalAlertService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewName = 'pages.admin.dashboard';

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService,
        private readonly OperationalAlertService $alertService,
        ?JadwalOccurrenceService $occurrenceService = null
    ) {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public function index(Request $request)
    {
        if (Cache::add('absensi_auto_checkout_throttled', true, now()->addMinutes(10))) {
            try {
                Absensi::prosesAutoCheckoutSesiLewat(30);
            } catch (\Throwable) {
                // Abaikan jika gagal sementara agar dashboard tetap dapat dimuat
            }
        }

        $defaultPeriode     = (string) Setting::get('default_periode', 'bulan_ini');
        $selectedPeriod     = $request->filled('periode') ? (string) $request->input('periode') : $defaultPeriode;
        $selectedInstructor = $request->filled('instruktur') ? (string) $request->input('instruktur') : 'all';
        $selectedStatus     = $request->filled('status') ? (string) $request->input('status') : 'all';

        [$dari, $sampai] = $this->getPeriodeRange($selectedPeriod, $request);
        $sampaiEnd       = Carbon::parse($sampai)->toDateString() . ' 23:59:59';

        $baseAbsensiQuery = Absensi::whereBetween('tanggal', [$dari, $sampaiEnd]);
        $jadwalQuery      = Jadwal::periode($dari, $sampai);
        $evaluasiQuery    = Evaluasi::whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']);
        $aktivitasQuery   = AktivitasMengajar::whereBetween('tanggal', [$dari, $sampaiEnd]);

        if ($selectedInstructor !== 'all' && $selectedInstructor !== '') {
            $baseAbsensiQuery->where('instruktur_id', $selectedInstructor);
            $jadwalQuery->where('instruktur_id', $selectedInstructor);
            $evaluasiQuery->where('instruktur_id', $selectedInstructor);
            $aktivitasQuery->where('instruktur_id', $selectedInstructor);
        }

        $activeAttendanceStatus = in_array($selectedStatus, ['hadir', 'terlambat', 'izin', 'tidak_hadir'], true)
            ? $selectedStatus
            : null;
        $filteredAbsensiQuery = $baseAbsensiQuery->clone()
            ->when($activeAttendanceStatus !== null, fn ($q) => $q->where('status', $activeAttendanceStatus));

        if ($selectedStatus !== 'all' && $selectedStatus !== '') {
            if (in_array($selectedStatus, ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'], true)) {
                $jadwalQuery->where('status', $selectedStatus);
            }
        }

        $totalInstruktur      = Instruktur::count();
        $totalInstrukturAktif = Instruktur::aktif()->count();

        $totalJadwal            = $jadwalQuery->clone()->count();
        $totalJadwalTerjadwal   = $jadwalQuery->clone()->where('status', 'terjadwal')->count();
        $totalJadwalBerlangsung = $jadwalQuery->clone()->where('status', 'berlangsung')->count();
        $totalJadwalSelesai     = $jadwalQuery->clone()->where('status', 'selesai')->count();
        $totalJadwalBatal       = $jadwalQuery->clone()->where('status', 'dibatalkan')->count();

        $activeJadwalStatus = in_array($selectedStatus, ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'], true)
            ? $selectedStatus
            : null;

        $jadwalsAktifPeriode = $activeJadwalStatus === 'dibatalkan'
            ? collect()
            : Jadwal::with('instruktur.cutis')
                ->where('status', '!=', 'dibatalkan')
                ->when($activeJadwalStatus !== null, fn ($q) => $q->where('status', $activeJadwalStatus))
                ->periode($dari, $sampai)
                ->when($selectedInstructor !== 'all' && $selectedInstructor !== '', fn ($q) => $q->where('instruktur_id', $selectedInstructor))
                ->get();

        $absensisPeriodeRaw = $baseAbsensiQuery->clone()
            ->with('jadwal')
            ->get(['id', 'instruktur_id', 'jadwal_id', 'tanggal', 'status']);
        $absensisPeriode = $this->occurrenceService->filterValidAttendanceRecords($absensisPeriodeRaw);

        $missedAbsensiCount = $this->occurrenceService
            ->getMissedAttendanceOccurrences($jadwalsAktifPeriode, $absensisPeriode, $dari, $sampai)
            ->count();

        $totalAbsensi   = $absensisPeriode->count() + $missedAbsensiCount;
        $hadirTepat     = $absensisPeriode->where('status', 'hadir')->count();
        $hadirTerlambat = $absensisPeriode->where('status', 'terlambat')->count();
        $totalIzin      = $absensisPeriode->where('status', 'izin')->count();
        $totalAlpa      = $absensisPeriode->where('status', 'tidak_hadir')->count() + $missedAbsensiCount;
        $totalHadir     = $hadirTepat + $hadirTerlambat;

        $persentaseKehadiran = $totalAbsensi > 0
            ? round(($totalHadir / $totalAbsensi) * 100, 1)
            : 0.0;

        $statusFilterLabels = [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'izin'        => 'Izin',
            'tidak_hadir' => 'Tidak Hadir',
        ];
        $statusFilterCounts = [
            'hadir'       => $hadirTepat,
            'terlambat'   => $hadirTerlambat,
            'izin'        => $totalIzin,
            'tidak_hadir' => $totalAlpa,
        ];
        $statusFilterLabel = $activeAttendanceStatus ? $statusFilterLabels[$activeAttendanceStatus] : null;
        $statusFilterCount = $activeAttendanceStatus ? $statusFilterCounts[$activeAttendanceStatus] : null;
        $statusFilterPersentase = $activeAttendanceStatus !== null
            ? ($totalAbsensi > 0 ? round(($statusFilterCount / $totalAbsensi) * 100, 1) : 0.0)
            : null;

        $totalEvaluasi     = $evaluasiQuery->clone()->count();
        $avgTotalNilai     = $evaluasiQuery->clone()->whereNotNull('total_nilai')->avg('total_nilai');
        $rataRataPenilaian = $avgTotalNilai !== null ? round((float) $avgTotalNilai, 2) : null;

        $allKinerja = $this->kinerjaService->evaluateAll($dari, $sampai, $selectedInstructor, $selectedStatus);

        $totalKelasDiampu  = $this->countUniqueKelasFromJadwals($jadwalsAktifPeriode);
        $totalJamMengajar    = round((float) $allKinerja->sum('jam_mengajar'), 1);
        $totalSesiPeriode    = (int) $allKinerja->sum('total_jadwal');
        $totalImportSukses   = ImportHistory::where('status', '!=', 'gagal')->sum('baris_berhasil');
        $totalFileImport     = ImportHistory::count();

        $metrics = [
            'total_instruktur'             => $totalInstruktur,
            'total_instruktur_aktif'       => $totalInstrukturAktif,
            'total_instruktur_aktif_trend' => "{$totalInstrukturAktif} aktif dari {$totalInstruktur} terdaftar",
            'total_kelas'                  => $totalJadwal,
            'total_kelas_diampu'           => $totalKelasDiampu,
            'total_kelas_diampu_trend'     => "{$totalJadwal} sesi ({$totalJadwalSelesai} selesai)",
            'total_kelas_selesai'          => $totalJadwalSelesai,
            'total_kelas_terjadwal'        => $totalJadwalTerjadwal,
            'total_kelas_berlangsung'      => $totalJadwalBerlangsung,
            'total_kelas_batal'            => $totalJadwalBatal,
            'total_kelas_trend'            => "{$totalJadwalSelesai} selesai, {$totalJadwalTerjadwal} terjadwal",
            'total_jam_mengajar'           => $totalJamMengajar,
            'total_jam_mengajar_trend'     => "Dari {$totalSesiPeriode} sesi periode berjalan",
            'total_absensi'                => $totalAbsensi,
            'total_hadir'                  => $totalHadir,
            'hadir_tepat'                  => $hadirTepat,
            'hadir_terlambat'              => $hadirTerlambat,
            'total_izin'                   => $totalIzin,
            'total_alpa'                   => $totalAlpa,
            'persentase_kehadiran'         => $persentaseKehadiran,
            'persentase_kehadiran_trend'   => "{$totalHadir} hadir dari {$totalAbsensi} catatan absensi",
            'status_filter_aktif'          => $activeAttendanceStatus,
            'status_filter_label'          => $statusFilterLabel,
            'status_filter_count'          => $statusFilterCount,
            'status_filter_persentase'     => $statusFilterPersentase,
            'total_evaluasi'               => $totalEvaluasi,
            'rata_rata_penilaian'          => $rataRataPenilaian !== null ? number_format($rataRataPenilaian, 2) : '-',
            'rata_rata_penilaian_trend'    => "Dari {$totalEvaluasi} evaluasi peserta",
            'total_import_baris'           => $totalImportSukses,
            'total_import_file'            => $totalFileImport,
        ];

        $kinerjaInstruktur = $allKinerja
            ->sortByDesc(fn ($item) => $item['skor_akhir'] ?? $item['persentase_kehadiran'])
            ->take(8)
            ->values();

        // Papan peringkat (Leaderboard) instruktur dengan skor kinerja dan jam mengajar tertinggi
        $leaderboardInstruktur = $allKinerja
            ->sortByDesc(fn ($item) => ($item['skor_akhir'] ?? 0) * 1000 + ($item['jam_mengajar'] ?? 0))
            ->take(5)
            ->values();

        $roleContext = str_contains($this->viewName, 'supervisor') ? 'supervisor' : 'admin';
        $alerts      = $this->alertService->getSummaryAlerts($dari, $sampai, $roleContext);

        if ($selectedInstructor !== 'all' && $selectedInstructor !== '') {
            $filterInsId = (int) $selectedInstructor;
            $filterByIns = fn (array $item): bool => (int) ($item['instruktur_id'] ?? 0) === $filterInsId;

            $alerts['items_aktif']                  = collect($alerts['items_aktif'] ?? [])->filter($filterByIns)->values();
            $alerts['items_perlu_tinjauan']         = $alerts['items_aktif']->filter(fn (array $item): bool => in_array($item['level'] ?? '', ['critical', 'warning'], true))->values();
            $alerts['items_info']                   = $alerts['items_aktif']->filter(fn (array $item): bool => ($item['level'] ?? '') === 'info')->values();
            $alerts['items_faktual']                = collect($alerts['items_faktual'] ?? [])->filter($filterByIns)->values();
            $alerts['items_faktual_perlu_tinjauan'] = $alerts['items_faktual']->filter(fn (array $item): bool => in_array($item['level'] ?? '', ['critical', 'warning'], true))->values();
            $alerts['items_faktual_info']           = $alerts['items_faktual']->filter(fn (array $item): bool => ($item['level'] ?? '') === 'info')->values();

            if (isset($alerts['feed'])) {
                $alerts['feed'] = collect($alerts['feed'])->filter($filterByIns)->values();
            }

            $alerts['total_peringatan']         = $alerts['items_perlu_tinjauan']->count();
            $alerts['total_info']               = $alerts['items_info']->count();
            $alerts['total_semua_aktif']        = $alerts['items_aktif']->count();
            $alerts['total_faktual_peringatan'] = $alerts['items_faktual_perlu_tinjauan']->count();
            $alerts['total_faktual_info']       = $alerts['items_faktual_info']->count();
            $alerts['total_faktual_semua']      = $alerts['items_faktual']->count();
        }

        // Gunakan kondisi operasional faktual untuk panel Dashboard agar Admin & Supervisor selalu sinkron 100%
        // dengan metrik Peringkat Instruktur / Kinerja walaupun lonceng notifikasi sudah pernah ditandai dibaca.
        $dashboardReviewItems = collect($alerts['items_faktual_perlu_tinjauan'] ?? $alerts['items_perlu_tinjauan'] ?? $alerts['items_aktif'] ?? []);
        $dashboardTableItems  = collect($alerts['items_faktual'] ?? $alerts['items_aktif'] ?? []);
        $dashboardInfoCount   = (int) ($alerts['total_faktual_info'] ?? $alerts['total_info'] ?? 0);

        $alerts['dashboard_items']            = $dashboardTableItems;
        $alerts['dashboard_total_peringatan'] = $dashboardReviewItems->count();
        $alerts['dashboard_total_info']       = $dashboardInfoCount;

        $alertCategorySummary = [
            'kelas_tanpa_laporan'        => $dashboardReviewItems->whereIn('kategori', ['Jurnal', 'Kehadiran'])->count(),
            'sertifikasi_hampir_expired' => $dashboardReviewItems->where('kategori', 'Sertifikasi')->count(),
            'instruktur_dibawah_standar' => $dashboardReviewItems->where('kategori', 'Kinerja')->count(),
            'pengingat_info'             => $dashboardInfoCount,
        ];

        $isKinerjaRuleConfirmed = $this->kinerjaService->isRuleConfirmed();

        $todayStr = today()->toDateString();

        $todaySchedule = Jadwal::with([
            'instruktur',
            'absensis' => fn ($q) => $q->whereDate('tanggal', $todayStr),
        ])
            ->hariIni()
            ->when($selectedInstructor !== 'all' && $selectedInstructor !== '', fn ($q) => $q->where('instruktur_id', $selectedInstructor))
            ->when(in_array($selectedStatus, ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'], true), fn ($q) => $q->where('status', $selectedStatus))
            ->orderBy('jam_mulai')
            ->get();

        $todayScheduleStatusMap = [];
        foreach ($todaySchedule as $jadwal) {
            $absenHariIni = $jadwal->absensis->first();
            $todayScheduleStatusMap[$jadwal->id] = $this->occurrenceService->resolveLiveSessionStatus(
                $jadwal,
                $todayStr,
                $absenHariIni
            );
        }

        $recentAbsensi = $filteredAbsensiQuery->clone()
            ->with(['instruktur', 'jadwal'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $recentActivities = $aktivitasQuery->clone()
            ->with(['instruktur', 'jadwal'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $instructorsList = Instruktur::orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'kode_instruktur', 'bidang_keahlian', 'status']);

        $recentImports = ImportHistory::with('user')
            ->latest()
            ->take(5)
            ->get();

        $chartData          = $this->getAttendanceChartData($baseAbsensiQuery->clone(), $dari, $sampai, $activeAttendanceStatus, $jadwalsAktifPeriode);
        $evaluasiChartData  = $this->getEvaluationChartData($dari, $sampai, $selectedInstructor);
        $aktivitasChartData = $this->getActivityChartData($jadwalsAktifPeriode, $aktivitasQuery->clone(), $dari, $sampai);

        return view($this->viewName, compact(
            'metrics',
            'instructorsList',
            'kinerjaInstruktur',
            'leaderboardInstruktur',
            'alerts',
            'alertCategorySummary',
            'isKinerjaRuleConfirmed',
            'todaySchedule',
            'todayScheduleStatusMap',
            'recentAbsensi',
            'recentActivities',
            'selectedPeriod',
            'selectedInstructor',
            'selectedStatus',
            'recentImports',
            'chartData',
            'evaluasiChartData',
            'aktivitasChartData',
            'dari',
            'sampai',
        ));
    }

    private function getPeriodeRange(string $periode, Request $request): array
    {
        if ($periode === 'kustom') {
            $defaultDari   = now()->startOfMonth()->toDateString();
            $defaultSampai = now()->toDateString();
            $rawDari       = $request->filled('dari') ? (string) $request->input('dari') : $defaultDari;
            $rawSampai     = $request->filled('sampai') ? (string) $request->input('sampai') : $defaultSampai;

            try {
                $d = Carbon::parse($rawDari)->toDateString();
            } catch (\Throwable) {
                $d = $defaultDari;
            }

            try {
                $s = Carbon::parse($rawSampai)->toDateString();
            } catch (\Throwable) {
                $s = $defaultSampai;
            }

            return $d <= $s ? [$d, $s] : [$s, $d];
        }

        return match ($periode) {
            'hari_ini'   => [today()->toDateString(), today()->toDateString()],
            'minggu_ini' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'bulan_ini'  => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'tahun_ini'  => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default      => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }

    private function getAttendanceChartData(
        Builder $baseAbsensiQuery,
        string $dari,
        string $sampai,
        ?string $activeAttendanceStatus = null,
        ?Collection $jadwalsAktifPeriode = null
    ): array {
        $recordsRaw = $baseAbsensiQuery
            ->with(['instruktur:id,nama_lengkap', 'jadwal'])
            ->orderBy('tanggal')
            ->get(['id', 'instruktur_id', 'jadwal_id', 'tanggal', 'status']);
        $records = $this->occurrenceService->filterValidAttendanceRecords($recordsRaw);

        $missedOccurrences = $jadwalsAktifPeriode !== null
            ? $this->occurrenceService
                ->getMissedAttendanceOccurrences($jadwalsAktifPeriode, $records, $dari, $sampai)
            : collect();

        $formatNames = function (Collection $names): string {
            $unique = $names
                ->map(fn ($n) => trim((string) $n))
                ->filter(fn ($n) => $n !== '')
                ->unique()
                ->values();

            $total = $unique->count();
            if ($total === 0) {
                return '';
            }
            $shown = $unique->take(3)->implode(', ');
            return $total > 3 ? $shown . ' +' . ($total - 3) . ' lainnya' : $shown;
        };

        $seriesMeta = match ($activeAttendanceStatus) {
            'hadir'       => ['label' => 'Hadir Tepat Waktu', 'color' => '#10B981'],
            'terlambat'   => ['label' => 'Terlambat', 'color' => '#D97706'],
            'izin'        => ['label' => 'Izin', 'color' => '#0EA5E9'],
            'tidak_hadir' => ['label' => 'Alpa', 'color' => '#EF4444'],
            default       => ['label' => 'Hadir', 'color' => '#10B981'],
        };

        $start = Carbon::parse($dari)->startOfDay();
        $end   = Carbon::parse($sampai)->startOfDay();

        $labels          = [];
        $tooltipLabels   = [];
        $bucketRanges    = [];
        $hadirSeries     = [];
        $terlambatSeries = [];
        $izinSeries      = [];
        $alpaSeries      = [];
        $totalData       = [];
        $hadirNames      = [];
        $terlambatNames  = [];
        $izinNames       = [];
        $alpaNames       = [];

        if ($start->diffInDays($end) > 60) {
            $grouped     = $records->groupBy(fn ($item) => $item->tanggal->format('Y-m'));
            $missedByYm  = $missedOccurrences->groupBy(fn (array $occ) => substr($occ['tanggal'], 0, 7));
            $cursor      = $start->copy()->startOfMonth();
            $endMonth    = $end->copy()->startOfMonth();
            $sameYear    = $start->year === $end->year;
            $granularity = 'bulanan';

            while ($cursor->lte($endMonth)) {
                $ym                = $cursor->format('Y-m');
                $monthItems        = $grouped->get($ym, collect());
                $missedItems       = $missedByYm->get($ym, collect());
                $missedCount       = $missedItems->count();
                $labels[]          = $sameYear ? $cursor->translatedFormat('M') : $cursor->translatedFormat('M Y');
                $tooltipLabels[]   = $cursor->translatedFormat('F Y');
                $bucketRanges[]    = [
                    'dari'   => $cursor->copy()->startOfMonth()->toDateString(),
                    'sampai' => $cursor->copy()->endOfMonth()->toDateString(),
                ];

                $hItems = $monthItems->where('status', 'hadir');
                $tItems = $monthItems->where('status', 'terlambat');
                $iItems = $monthItems->where('status', 'izin');
                $aItems = $monthItems->where('status', 'tidak_hadir');

                $hadirSeries[]     = $hItems->count();
                $terlambatSeries[] = $tItems->count();
                $izinSeries[]      = $iItems->count();
                $alpaSeries[]      = $aItems->count() + $missedCount;
                $totalData[]       = $monthItems->count() + $missedCount;

                $hadirNames[]     = $formatNames($hItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $terlambatNames[] = $formatNames($tItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $izinNames[]      = $formatNames($iItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $alpaNames[]      = $formatNames(
                    $aItems->map(fn ($r) => $r->instruktur?->nama_lengkap)
                        ->concat($missedItems->map(fn (array $occ) => $occ['jadwal']->instruktur?->nama_lengkap))
                );

                $cursor->addMonth();
            }
        } else {
            $grouped     = $records->groupBy(fn ($item) => $item->tanggal->toDateString());
            $missedByDay = $missedOccurrences->groupBy(fn (array $occ) => $occ['tanggal']);
            $current     = $start->copy();
            $granularity = 'harian';

            while ($current->lte($end)) {
                $dateStr           = $current->toDateString();
                $dayItems          = $grouped->get($dateStr, collect());
                $missedItems       = $missedByDay->get($dateStr, collect());
                $missedCount       = $missedItems->count();
                $labels[]          = $current->format('d/m');
                $tooltipLabels[]   = $current->translatedFormat('d M Y');
                $bucketRanges[]    = [
                    'dari'   => $dateStr,
                    'sampai' => $dateStr,
                ];

                $hItems = $dayItems->where('status', 'hadir');
                $tItems = $dayItems->where('status', 'terlambat');
                $iItems = $dayItems->where('status', 'izin');
                $aItems = $dayItems->where('status', 'tidak_hadir');

                $hadirSeries[]     = $hItems->count();
                $terlambatSeries[] = $tItems->count();
                $izinSeries[]      = $iItems->count();
                $alpaSeries[]      = $aItems->count() + $missedCount;
                $totalData[]       = $dayItems->count() + $missedCount;

                $hadirNames[]     = $formatNames($hItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $terlambatNames[] = $formatNames($tItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $izinNames[]      = $formatNames($iItems->map(fn ($r) => $r->instruktur?->nama_lengkap));
                $alpaNames[]      = $formatNames(
                    $aItems->map(fn ($r) => $r->instruktur?->nama_lengkap)
                        ->concat($missedItems->map(fn (array $occ) => $occ['jadwal']->instruktur?->nama_lengkap))
                );

                $current->addDay();
            }
        }

        $seriesMap = [
            'hadir'       => ['name' => 'Hadir',     'color' => '#10B981', 'data' => $hadirSeries,     'names' => $hadirNames],
            'terlambat'   => ['name' => 'Terlambat', 'color' => '#D97706', 'data' => $terlambatSeries, 'names' => $terlambatNames],
            'izin'        => ['name' => 'Izin',      'color' => '#0EA5E9', 'data' => $izinSeries,      'names' => $izinNames],
            'tidak_hadir' => ['name' => 'Alpa',      'color' => '#EF4444', 'data' => $alpaSeries,      'names' => $alpaNames],
        ];
        $totalSeriesItem = ['name' => 'Total Pertemuan', 'color' => '#98a2b3', 'data' => $totalData, 'names' => []];

        if ($activeAttendanceStatus === null) {
            $stacked    = true;
            $seriesList = [
                $seriesMap['hadir'],
                $seriesMap['terlambat'],
                $seriesMap['izin'],
                $seriesMap['tidak_hadir'],
            ];
            $legacyHadirData = array_map(
                fn (int $h, int $t) => $h + $t,
                $hadirSeries,
                $terlambatSeries
            );
        } else {
            $stacked         = false;
            $chosen          = $seriesMap[$activeAttendanceStatus];
            $seriesList      = [$chosen, $totalSeriesItem];
            $legacyHadirData = $chosen['data'];
        }

        return [
            'labels'         => $labels,
            'tooltip_labels' => $tooltipLabels,
            'bucket_ranges'  => $bucketRanges,
            'granularity'    => $granularity,
            'series_label'   => $seriesMeta['label'],
            'series_color'   => $seriesMeta['color'],
            'stacked'        => $stacked,
            'hadir'          => $legacyHadirData,
            'total'          => $totalData,
            'series'         => $seriesList,
        ];
    }

    private function getEvaluationChartData(string $dari, string $sampai, string $instrukturId): array
    {
        $evaluasis = Evaluasi::with('instruktur')
            ->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->when($instrukturId !== 'all' && $instrukturId !== '', fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereNotNull('total_nilai')
            ->get();

        $grouped  = $evaluasis->groupBy('instruktur_id');
        $labels   = [];
        $averages = [];
        $counts   = [];

        foreach ($grouped as $items) {
            $inst = $items->first()?->instruktur;
            if (!$inst) {
                continue;
            }
            $labels[]   = $inst->nama_lengkap;
            $averages[] = round((float) $items->avg('total_nilai'), 2);
            $counts[]   = $items->count();
        }

        return [
            'labels'   => $labels,
            'averages' => $averages,
            'counts'   => $counts,
        ];
    }

    private function getActivityChartData(
        Collection $jadwalsAktifPeriode,
        Builder $aktivitasQuery,
        string $dari,
        string $sampai
    ): array {
        $occurrences = $this->occurrenceService
            ->expandElapsedOccurrences($jadwalsAktifPeriode, $dari, $sampai)
            ->reject(fn (array $occ) => $occ['jadwal']->instruktur?->sedangCutiAtauIzinPada($occ['tanggal'], $occ['jadwal']->hari))
            ->values();

        $confirmedActivities = $aktivitasQuery
            ->where('status_konfirmasi', 'dikonfirmasi')
            ->where(function ($q) {
                $q->whereNull('jadwal_id')
                    ->orWhereHas('jadwal', fn ($jq) => $jq->where('status', '!=', 'dibatalkan'));
            })
            ->orderByDesc('id')
            ->get(['id', 'instruktur_id', 'jadwal_id', 'tanggal'])
            ->unique(fn ($item) => ($item->instruktur_id ?? '0') . '|' . ($item->jadwal_id ?? '0') . '|' . ($item->tanggal?->toDateString() ?? (string) $item->id))
            ->values();

        $start = Carbon::parse($dari)->startOfDay();
        $end   = Carbon::parse($sampai)->startOfDay();

        if ($start->diffInDays($end) > 60) {
            $schedByMonth = $occurrences->groupBy(fn (array $item): string => substr((string) $item['tanggal'], 0, 7));
            $actByMonth   = $confirmedActivities->groupBy(fn ($item): string => $item->tanggal?->format('Y-m') ?? '');

            $cursor        = $start->copy()->startOfMonth();
            $endMonth      = $end->copy()->startOfMonth();
            $sameYear      = $start->year === $end->year;
            $labels        = [];
            $tooltipLabels = [];
            $terjadwal     = [];
            $dikonfirmasi  = [];

            while ($cursor->lte($endMonth)) {
                $ym              = $cursor->format('Y-m');
                $labels[]        = $sameYear ? $cursor->translatedFormat('M') : $cursor->translatedFormat('M Y');
                $tooltipLabels[] = $cursor->translatedFormat('F Y');
                $terjadwal[]     = $schedByMonth->get($ym, collect())->count();
                $dikonfirmasi[]  = $actByMonth->get($ym, collect())->count();
                $cursor->addMonth();
            }

            return [
                'labels'         => $labels,
                'tooltip_labels' => $tooltipLabels,
                'granularity'    => 'bulanan',
                'terjadwal'      => $terjadwal,
                'dikonfirmasi'   => $dikonfirmasi,
            ];
        }

        $schedByDate = $occurrences->groupBy('tanggal');
        $actByDate   = $confirmedActivities->groupBy(fn ($item): string => $item->tanggal?->toDateString() ?? '');

        $current       = $start->copy();
        $labels        = [];
        $tooltipLabels = [];
        $terjadwal     = [];
        $dikonfirmasi  = [];

        while ($current->lte($end)) {
            $dateStr         = $current->toDateString();
            $labels[]        = $current->format('d/m');
            $tooltipLabels[] = $current->translatedFormat('d M Y');
            $terjadwal[]     = $schedByDate->get($dateStr, collect())->count();
            $dikonfirmasi[]  = $actByDate->get($dateStr, collect())->count();
            $current->addDay();
        }

        return [
            'labels'         => $labels,
            'tooltip_labels' => $tooltipLabels,
            'granularity'    => 'harian',
            'terjadwal'      => $terjadwal,
            'dikonfirmasi'   => $dikonfirmasi,
        ];
    }
}
