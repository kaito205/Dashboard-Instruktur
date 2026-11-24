<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use App\Models\ObservasiSupervisor;
use App\Services\KinerjaCalculationService;
use App\Services\OperationalAlertService;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LaporanController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewName      = 'pages.admin.laporan.index';
    protected string $cetakViewName = 'pages.admin.laporan.cetak';
    protected string $routePrefix   = 'admin';

    private const VALID_JENIS = ['absensi', 'evaluasi', 'jadwal', 'aktivitas', 'observasi', 'kinerja', 'individual', 'manajerial'];

    private const JENIS_LABELS = [
        'absensi'    => 'Absensi',
        'evaluasi'   => 'Evaluasi',
        'jadwal'     => 'Jadwal',
        'aktivitas'  => 'Aktivitas',
        'observasi'  => 'Observasi',
        'kinerja'    => 'Kinerja',
        'individual' => 'Rapor Individual',
        'manajerial' => 'Ringkasan Manajerial',
    ];

    private const SUMBER_ABSENSI_LABELS = [
        'checkin_app'  => 'Aplikasi',
        'manual_admin' => 'Manual Admin',
        'manual'       => 'Manual',
        'import'       => 'Import',
    ];

    private const STATUS_OPTIONS_BY_JENIS = [
        'absensi' => [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'izin'        => 'Izin',
            'tidak_hadir' => 'Tidak Hadir',
        ],
        'jadwal' => [
            'terjadwal'   => 'Terjadwal',
            'berlangsung' => 'Sedang Berlangsung',
            'selesai'     => 'Selesai',
            'dibatalkan'  => 'Dibatalkan',
        ],
        'aktivitas' => [
            'dikonfirmasi'       => 'Dikonfirmasi',
            'belum_dikonfirmasi' => 'Belum Dikonfirmasi',
            'tidak_terlaksana'   => 'Tidak Terlaksana',
        ],
        'kinerja' => [
            'sangat_baik' => 'Sangat Baik',
            'baik'        => 'Baik',
            'cukup'       => 'Cukup',
            'kurang'      => 'Kurang',
        ],
    ];

    private const NAMA_BULAN = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private readonly OperationalAlertService $alertService;
    private readonly SimpleXlsxExporter $xlsxExporter;

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService,
        ?OperationalAlertService $alertService = null,
        ?SimpleXlsxExporter $xlsxExporter = null
    ) {
        $this->alertService = $alertService ?? app(OperationalAlertService::class);
        $this->xlsxExporter = $xlsxExporter ?? new SimpleXlsxExporter();
    }

    private function resolveRentangTanggal(Request $request): array
    {
        $bulanRaw = trim((string) ($request->input('bulan') ?? ''));
        $tahunRaw = trim((string) ($request->input('tahun') ?? ''));

        $tahunValid = ($tahunRaw !== '' && ctype_digit($tahunRaw) && (int) $tahunRaw >= 2000 && (int) $tahunRaw <= 2100)
            ? (int) $tahunRaw
            : null;

        if ($bulanRaw !== '' && ctype_digit($bulanRaw) && (int) $bulanRaw >= 1 && (int) $bulanRaw <= 12) {
            $targetYear = $tahunValid ?? (int) now()->year;
            $dt         = Carbon::createFromDate($targetYear, (int) $bulanRaw, 1);

            return [
                $dt->copy()->startOfMonth()->toDateString(),
                $dt->copy()->endOfMonth()->toDateString(),
                (string) ((int) $bulanRaw),
                (string) $targetYear,
            ];
        }

        if ($tahunValid !== null) {
            return [
                Carbon::createFromDate($tahunValid, 1, 1)->toDateString(),
                Carbon::createFromDate($tahunValid, 12, 31)->toDateString(),
                '',
                (string) $tahunValid,
            ];
        }

        $dari   = $this->parseDateOrDefault($request->input('dari'), now()->startOfMonth()->toDateString());
        $sampai = $this->parseDateOrDefault($request->input('sampai'), now()->endOfMonth()->toDateString());

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai, '', ''];
    }

    /**
     * Satukan pembacaan dan normalisasi parameter filter untuk layar, cetak PDF, dan ekspor CSV.
     * Cast eksplisit ke string mencegah bug null !== '' akibat middleware ConvertEmptyStringsToNull.
     */
    private function resolveFilters(Request $request): array
    {
        $rawJenis = trim((string) ($request->input('jenis') ?? ''));
        $jenis    = in_array($rawJenis, self::VALID_JENIS, true) ? $rawJenis : 'absensi';

        $rawInstruktur = trim((string) ($request->input('instruktur') ?? ''));
        $instrukturId  = (ctype_digit($rawInstruktur) && (int) $rawInstruktur > 0) ? $rawInstruktur : '';

        $rawKelas = trim((string) ($request->input('kelas') ?? ''));
        $kelasId  = (ctype_digit($rawKelas) && (int) $rawKelas > 0) ? $rawKelas : '';

        $statusOptions     = self::STATUS_OPTIONS_BY_JENIS[$jenis] ?? [];
        $rawStatus         = trim((string) ($request->input('status') ?? ''));
        $statusFilter      = array_key_exists($rawStatus, $statusOptions) ? $rawStatus : '';
        $statusFilterLabel = $statusFilter !== '' ? ($statusOptions[$statusFilter] ?? '') : '';

        [$dari, $sampai, $bulan, $tahun] = $this->resolveRentangTanggal($request);

        $selectedInstruktur = $instrukturId !== ''
            ? Instruktur::withTrashed()->find($instrukturId)
            : null;
        if ($instrukturId !== '' && ! $selectedInstruktur) {
            $instrukturId = '';
        }

        $selectedKelas = ($kelasId !== '' && Schema::hasTable('kelas'))
            ? Kelas::withTrashed()->find((int) $kelasId)
            : null;
        if (! $selectedKelas) {
            $kelasId = '';
        }

        $rangeText = Carbon::parse($dari)->format('d/m/Y') . ' s/d ' . Carbon::parse($sampai)->format('d/m/Y');
        if ($bulan !== '' && isset(self::NAMA_BULAN[(int) $bulan])) {
            $periodeLabel = self::NAMA_BULAN[(int) $bulan] . ' ' . $tahun . ' (' . $rangeText . ')';
        } elseif ($tahun !== '') {
            $periodeLabel = 'Tahun ' . $tahun . ' (' . $rangeText . ')';
        } else {
            $periodeLabel = $rangeText;
        }

        $activeFilterParams = array_filter([
            'jenis'      => $jenis,
            'dari'       => $dari,
            'sampai'     => $sampai,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'instruktur' => $instrukturId,
            'kelas'      => $kelasId,
            'status'     => $statusFilter,
        ], fn (string $val): bool => $val !== '');

        return [
            'jenis'              => $jenis,
            'jenisLabel'         => self::JENIS_LABELS[$jenis] ?? ucfirst($jenis),
            'instrukturId'       => $instrukturId,
            'selectedInstruktur' => $selectedInstruktur,
            'kelasId'            => $kelasId,
            'selectedKelas'      => $selectedKelas,
            'statusFilter'       => $statusFilter,
            'statusFilterLabel'  => $statusFilterLabel,
            'statusOptions'      => $statusOptions,
            'dari'               => $dari,
            'sampai'             => $sampai,
            'bulan'              => $bulan,
            'tahun'              => $tahun,
            'periodeLabel'       => $periodeLabel,
            'activeFilterParams' => $activeFilterParams,
        ];
    }

    private function buildReportQuery(string $jenis, string $dari, string $sampai, string $instrukturId, string $statusFilter, ?Kelas $selectedKelas = null): Builder
    {
        return match ($jenis) {
            'absensi' => Absensi::with(['instruktur', 'jadwal'])
                ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
                ->when($statusFilter !== '', fn (Builder $q) => $q->where('status', $statusFilter))
                ->when($selectedKelas, fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq)))
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderByDesc('tanggal')
                ->orderByDesc('id'),

            'jadwal' => Jadwal::with(['instruktur', 'instrukturAsli', 'instrukturPengganti'])
                ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
                ->when($statusFilter !== '', fn (Builder $q) => $q->where('status', $statusFilter))
                ->when($selectedKelas, fn (Builder $q) => $selectedKelas->scopeMatchingJadwal($q))
                ->periode($dari, $sampai)
                ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
                ->orderByDesc('tanggal')
                ->orderBy('jam_mulai')
                ->orderByDesc('id'),

            'aktivitas' => AktivitasMengajar::with(['instruktur', 'jadwal', 'verifier'])
                ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
                ->when($statusFilter !== '', fn (Builder $q) => $q->where('status_konfirmasi', $statusFilter))
                ->when($selectedKelas, fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq)))
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderByDesc('tanggal')
                ->orderByDesc('id'),

            'evaluasi' => Evaluasi::with(['instruktur', 'kodeEvaluasi', 'jadwal'])
                ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
                ->when($selectedKelas, fn (Builder $q) => $q->where(function (Builder $sub) use ($selectedKelas) {
                    $sub->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq))
                        ->orWhereHas('kodeEvaluasi', fn (Builder $kq) => $selectedKelas->scopeMatchingKodeEvaluasi($kq));
                }))
                ->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
                ->orderByDesc('created_at')
                ->orderByDesc('id'),

            'observasi' => ObservasiSupervisor::with(['instruktur', 'supervisor', 'jadwal'])
                ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
                ->when($selectedKelas, fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq)))
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderByDesc('tanggal')
                ->orderByDesc('id'),
        };
    }

    private function buildKinerjaCollection(string $dari, string $sampai, string $instrukturId, string $statusFilter, ?Kelas $selectedKelas = null): Collection
    {
        $records = $this->kinerjaService->evaluateAll(
            $dari,
            $sampai,
            $instrukturId !== '' ? $instrukturId : null
        );

        if ($selectedKelas !== null) {
            $jadwalInstrukturIds = Jadwal::periode($dari, $sampai)
                ->where(fn (Builder $q) => $selectedKelas->scopeMatchingJadwal($q))
                ->pluck('instruktur_id');

            $kodeInstrukturIds = KodeEvaluasi::where(fn (Builder $q) => $selectedKelas->scopeMatchingKodeEvaluasi($q))
                ->whereNotNull('instruktur_id')
                ->pluck('instruktur_id');

            $allowedIds = $jadwalInstrukturIds
                ->merge($kodeInstrukturIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $records = $records
                ->filter(fn (array $row): bool => in_array((int) ($row['instruktur']->id ?? 0), $allowedIds, true))
                ->values();
        }

        if ($statusFilter !== '') {
            $records = $records->filter(fn (array $row): bool => ($row['kategori'] ?? '') === $statusFilter)->values();
        }

        return $records->values();
    }

    private function buildDistribusiChartFromQuery(string $jenis, Builder $baseQuery): array
    {
        $query = $baseQuery->clone()->with([])->reorder();

        if (in_array($jenis, ['absensi', 'jadwal', 'aktivitas'], true)) {
            $col    = $jenis === 'aktivitas' ? 'status_konfirmasi' : 'status';
            $counts = $query->selectRaw("{$col} as bucket, COUNT(*) as total")
                ->groupBy($col)
                ->pluck('total', 'bucket');

            return match ($jenis) {
                'absensi' => [
                    'title'  => 'Distribusi Status Kehadiran Instruktur',
                    'labels' => ['Hadir Tepat Waktu', 'Terlambat', 'Izin', 'Tidak Hadir'],
                    'series' => [
                        (int) ($counts['hadir'] ?? 0),
                        (int) ($counts['terlambat'] ?? 0),
                        (int) ($counts['izin'] ?? 0),
                        (int) ($counts['tidak_hadir'] ?? 0),
                    ],
                ],
                'jadwal' => [
                    'title'  => 'Distribusi Status Jadwal Mengajar',
                    'labels' => ['Terjadwal', 'Sedang Berlangsung', 'Selesai', 'Dibatalkan'],
                    'series' => [
                        (int) ($counts['terjadwal'] ?? 0),
                        (int) ($counts['berlangsung'] ?? 0),
                        (int) ($counts['selesai'] ?? 0),
                        (int) ($counts['dibatalkan'] ?? 0),
                    ],
                ],
                'aktivitas' => [
                    'title'  => 'Distribusi Status Laporan Aktivitas Mengajar',
                    'labels' => ['Dikonfirmasi', 'Belum Dikonfirmasi', 'Tidak Terlaksana'],
                    'series' => [
                        (int) ($counts['dikonfirmasi'] ?? 0),
                        (int) ($counts['belum_dikonfirmasi'] ?? 0),
                        (int) ($counts['tidak_terlaksana'] ?? 0),
                    ],
                ],
            };
        }

        if (in_array($jenis, ['evaluasi', 'observasi'], true)) {
            $ruleConfig     = $this->kinerjaService->getRuleConfig();
            $kategoriRanges = $this->kinerjaService->getKategoriRangeLabels($ruleConfig);
            $sb             = (float) $ruleConfig['batas_sangat_baik'];
            $b              = (float) $ruleConfig['batas_baik'];
            $c              = (float) $ruleConfig['batas_cukup'];
            $col            = $jenis === 'evaluasi' ? 'total_nilai' : 'skor_observasi';

            $agg = $query->whereNotNull($col)
                ->selectRaw(
                    "SUM(CASE WHEN {$col} >= ? THEN 1 ELSE 0 END) as sb_count, "
                    . "SUM(CASE WHEN {$col} >= ? AND {$col} < ? THEN 1 ELSE 0 END) as b_count, "
                    . "SUM(CASE WHEN {$col} >= ? AND {$col} < ? THEN 1 ELSE 0 END) as c_count, "
                    . "SUM(CASE WHEN {$col} < ? THEN 1 ELSE 0 END) as k_count",
                    [$sb, $b, $sb, $c, $b, $c]
                )
                ->first();

            return [
                'title'  => $jenis === 'evaluasi'
                    ? 'Distribusi Kategori Skor Evaluasi Peserta'
                    : 'Distribusi Skor Observasi Supervisor',
                'labels' => [
                    $kategoriRanges['sangat_baik'],
                    $kategoriRanges['baik'],
                    $kategoriRanges['cukup'],
                    $kategoriRanges['kurang'],
                ],
                'series' => [
                    (int) ($agg->sb_count ?? 0),
                    (int) ($agg->b_count ?? 0),
                    (int) ($agg->c_count ?? 0),
                    (int) ($agg->k_count ?? 0),
                ],
            ];
        }

        return ['title' => '', 'labels' => [], 'series' => []];
    }

    private function buildDistribusiChart(string $jenis, Collection $allRows): array
    {
        $ruleConfig     = $this->kinerjaService->getRuleConfig();
        $kategoriRanges = $this->kinerjaService->getKategoriRangeLabels($ruleConfig);
        $sb             = (float) $ruleConfig['batas_sangat_baik'];
        $b              = (float) $ruleConfig['batas_baik'];
        $c              = (float) $ruleConfig['batas_cukup'];
        $kategoriLabels = [
            $kategoriRanges['sangat_baik'],
            $kategoriRanges['baik'],
            $kategoriRanges['cukup'],
            $kategoriRanges['kurang'],
        ];

        return match ($jenis) {
            'absensi' => [
                'title'  => 'Distribusi Status Kehadiran Instruktur',
                'labels' => ['Hadir Tepat Waktu', 'Terlambat', 'Izin', 'Tidak Hadir'],
                'series' => [
                    $allRows->where('status', 'hadir')->count(),
                    $allRows->where('status', 'terlambat')->count(),
                    $allRows->where('status', 'izin')->count(),
                    $allRows->where('status', 'tidak_hadir')->count(),
                ],
            ],
            'jadwal' => [
                'title'  => 'Distribusi Status Jadwal Mengajar',
                'labels' => ['Terjadwal', 'Sedang Berlangsung', 'Selesai', 'Dibatalkan'],
                'series' => [
                    $allRows->where('status', 'terjadwal')->count(),
                    $allRows->where('status', 'berlangsung')->count(),
                    $allRows->where('status', 'selesai')->count(),
                    $allRows->where('status', 'dibatalkan')->count(),
                ],
            ],
            'aktivitas' => [
                'title'  => 'Distribusi Status Laporan Aktivitas Mengajar',
                'labels' => ['Dikonfirmasi', 'Belum Dikonfirmasi', 'Tidak Terlaksana'],
                'series' => [
                    $allRows->where('status_konfirmasi', 'dikonfirmasi')->count(),
                    $allRows->where('status_konfirmasi', 'belum_dikonfirmasi')->count(),
                    $allRows->where('status_konfirmasi', 'tidak_terlaksana')->count(),
                ],
            ],
            'evaluasi' => [
                'title'  => 'Distribusi Kategori Skor Evaluasi Peserta',
                'labels' => $kategoriLabels,
                'series' => [
                    $allRows->filter(fn ($e) => $e->total_nilai !== null && (float) $e->total_nilai >= $sb)->count(),
                    $allRows->filter(fn ($e) => $e->total_nilai !== null && (float) $e->total_nilai >= $b && (float) $e->total_nilai < $sb)->count(),
                    $allRows->filter(fn ($e) => $e->total_nilai !== null && (float) $e->total_nilai >= $c && (float) $e->total_nilai < $b)->count(),
                    $allRows->filter(fn ($e) => $e->total_nilai !== null && (float) $e->total_nilai < $c)->count(),
                ],
            ],
            'observasi' => [
                'title'  => 'Distribusi Skor Observasi Supervisor',
                'labels' => $kategoriLabels,
                'series' => [
                    $allRows->filter(fn ($o) => $o->skor_observasi !== null && (float) $o->skor_observasi >= $sb)->count(),
                    $allRows->filter(fn ($o) => $o->skor_observasi !== null && (float) $o->skor_observasi >= $b && (float) $o->skor_observasi < $sb)->count(),
                    $allRows->filter(fn ($o) => $o->skor_observasi !== null && (float) $o->skor_observasi >= $c && (float) $o->skor_observasi < $b)->count(),
                    $allRows->filter(fn ($o) => $o->skor_observasi !== null && (float) $o->skor_observasi < $c)->count(),
                ],
            ],
            'kinerja' => [
                'title'  => 'Distribusi Kategori Kinerja Instruktur',
                'labels' => $kategoriLabels,
                'series' => [
                    $allRows->where('kategori', 'sangat_baik')->count(),
                    $allRows->where('kategori', 'baik')->count(),
                    $allRows->where('kategori', 'cukup')->count(),
                    $allRows->where('kategori', 'kurang')->count(),
                ],
            ],
            'manajerial' => [
                'title'  => 'Distribusi Kategori Kinerja Lembaga',
                'labels' => $kategoriLabels,
                'series' => [
                    $allRows->where('kategori', 'sangat_baik')->count(),
                    $allRows->where('kategori', 'baik')->count(),
                    $allRows->where('kategori', 'cukup')->count(),
                    $allRows->where('kategori', 'kurang')->count(),
                ],
            ],
            default => ['title' => '', 'labels' => [], 'series' => []],
        };
    }

    private function buildIndividualDistribusiChart(?array $individualReport): array
    {
        if ($individualReport === null || empty($individualReport['kinerja'])) {
            return ['title' => '', 'labels' => [], 'series' => []];
        }

        $k = $individualReport['kinerja'];

        return [
            'title'  => 'Profil Skor Komponen Kinerja Instruktur',
            'labels' => ['Kehadiran (%)', 'Aktivitas (%)', 'Evaluasi (0-100)', 'Observasi (0-100)'],
            'series' => [
                round((float) ($k['skor_kehadiran'] ?? 0), 2),
                round((float) ($k['skor_aktivitas'] ?? 0), 2),
                round((float) ($k['skor_evaluasi'] ?? 0), 2),
                round((float) ($k['skor_observasi'] ?? 0), 2),
            ],
        ];
    }

    private function buildIndividualReport(?Instruktur $instruktur, string $dari, string $sampai, ?Kelas $selectedKelas = null): ?array
    {
        if (! $instruktur) {
            return null;
        }

        $instruktur->loadMissing('sertifikasis');

        $kinerjaRows = $this->buildKinerjaCollection($dari, $sampai, (string) $instruktur->id, '', $selectedKelas);
        $kinerja     = $kinerjaRows->first();
        if ($kinerja === null) {
            $kinerja = $this->kinerjaService->evaluateInstruktur($instruktur, $dari, $sampai);
        }

        $jadwals    = $this->buildReportQuery('jadwal', $dari, $sampai, (string) $instruktur->id, '', $selectedKelas)->get();
        $absensis   = $this->buildReportQuery('absensi', $dari, $sampai, (string) $instruktur->id, '', $selectedKelas)->get();
        $aktivitas  = $this->buildReportQuery('aktivitas', $dari, $sampai, (string) $instruktur->id, '', $selectedKelas)->get();
        $evaluasis  = $this->buildReportQuery('evaluasi', $dari, $sampai, (string) $instruktur->id, '', $selectedKelas)->get();
        $observasis = $this->buildReportQuery('observasi', $dari, $sampai, (string) $instruktur->id, '', $selectedKelas)->get();

        return [
            'instruktur'   => $instruktur,
            'sertifikasis' => $instruktur->sertifikasis,
            'kinerja'      => $kinerja,
            'jadwals'      => $jadwals,
            'absensis'     => $absensis,
            'aktivitas'    => $aktivitas,
            'evaluasis'    => $evaluasis,
            'observasis'   => $observasis,
        ];
    }

    private function buildManajerialReport(string $dari, string $sampai, string $instrukturId, ?Kelas $selectedKelas = null): array
    {
        $kinerjaRows = $this->buildKinerjaCollection($dari, $sampai, $instrukturId, '', $selectedKelas);

        $totalInstruktur      = Instruktur::count();
        $totalInstrukturAktif = Instruktur::aktif()->count();

        $jadwalsAktif = Jadwal::where('status', '!=', 'dibatalkan')
            ->periode($dari, $sampai)
            ->when($instrukturId !== '', fn (Builder $q) => $q->where('instruktur_id', $instrukturId))
            ->when($selectedKelas, fn (Builder $q) => $selectedKelas->scopeMatchingJadwal($q))
            ->get(['id', 'kelas_id', 'nama_kelas']);

        $kelasUnik = $this->countUniqueKelasFromJadwals($jadwalsAktif);

        $totalJamMengajar = round((float) $kinerjaRows->sum('jam_mengajar'), 1);
        $totalSesi        = (int) $kinerjaRows->sum('total_jadwal');
        $avgKehadiran     = $kinerjaRows->isNotEmpty()
            ? round((float) $kinerjaRows->avg('persentase_kehadiran'), 1)
            : 0.0;

        $evaluasiScores  = $kinerjaRows->pluck('skor_evaluasi')->filter(fn ($v) => $v !== null);
        $avgEvaluasi     = $evaluasiScores->isNotEmpty() ? round((float) $evaluasiScores->avg(), 2) : null;

        $skorAkhirValues = $kinerjaRows->pluck('skor_akhir')->filter(fn ($v) => $v !== null);
        $avgSkorKinerja  = $skorAkhirValues->isNotEmpty() ? round((float) $skorAkhirValues->avg(), 2) : null;

        $sortedDesc = $kinerjaRows
            ->sortByDesc(fn (array $item) => ($item['skor_akhir'] ?? -1) * 1000 + ($item['persentase_kehadiran'] ?? 0))
            ->values();
        $sortedAsc = $kinerjaRows
            ->sortBy(fn (array $item) => ($item['skor_akhir'] ?? 999) * 1000 + ($item['persentase_kehadiran'] ?? 0))
            ->values();

        $roleContext = $this->routePrefix === 'supervisor' ? 'supervisor' : 'admin';
        $alerts      = $this->alertService->getSummaryAlerts($dari, $sampai, $roleContext);
        $alertItems  = collect($alerts['items_faktual_perlu_tinjauan'] ?? $alerts['items_perlu_tinjauan'] ?? $alerts['items_aktif'] ?? []);

        if ($instrukturId !== '') {
            $filterInsId = (int) $instrukturId;
            $alertItems  = $alertItems
                ->filter(fn (array $item): bool => (int) ($item['instruktur_id'] ?? 0) === $filterInsId)
                ->values();
        }

        $alertRingkasan = [
            'total'                      => $alertItems->count(),
            'kelas_tanpa_laporan'        => $alertItems->whereIn('kategori', ['Jurnal', 'Kehadiran'])->count(),
            'sertifikasi_hampir_expired' => $alertItems->where('kategori', 'Sertifikasi')->count(),
            'instruktur_dibawah_standar' => $alertItems->where('kategori', 'Kinerja')->count(),
            'items'                      => $alertItems->take(8)->values(),
        ];

        $distribusiKategori = [
            'sangat_baik' => $kinerjaRows->where('kategori', 'sangat_baik')->count(),
            'baik'        => $kinerjaRows->where('kategori', 'baik')->count(),
            'cukup'       => $kinerjaRows->where('kategori', 'cukup')->count(),
            'kurang'      => $kinerjaRows->where('kategori', 'kurang')->count(),
        ];

        return [
            'kpi' => [
                'total_instruktur'       => $totalInstruktur,
                'total_instruktur_aktif' => $totalInstrukturAktif,
                'kelas_diampu'           => $kelasUnik,
                'total_sesi'             => $totalSesi,
                'total_jam_mengajar'     => $totalJamMengajar,
                'rata_kehadiran'         => $avgKehadiran,
                'rata_evaluasi'          => $avgEvaluasi,
                'rata_skor_kinerja'      => $avgSkorKinerja,
            ],
            'distribusi_kategori' => $distribusiKategori,
            'top_instruktur'      => $sortedDesc->take(5)->values(),
            'bottom_instruktur'   => $sortedAsc->take(5)->values(),
            'kinerja_rows'        => $sortedDesc,
            'alerts'              => $alertRingkasan,
        ];
    }

    private function getReportRecords(string $jenis, string $dari, string $sampai, string $instrukturId, string $statusFilter, ?Kelas $selectedKelas = null): Collection
    {
        if ($jenis === 'kinerja') {
            return $this->buildKinerjaCollection($dari, $sampai, $instrukturId, $statusFilter, $selectedKelas);
        }

        return $this->buildReportQuery($jenis, $dari, $sampai, $instrukturId, $statusFilter, $selectedKelas)->get();
    }

    private function formatSumberAbsensi(?string $sumber): string
    {
        if ($sumber === null || trim($sumber) === '') {
            return '-';
        }

        return self::SUMBER_ABSENSI_LABELS[$sumber] ?? $sumber;
    }

    private function formatJam(?string $jam): string
    {
        $trimmed = trim((string) ($jam ?? ''));
        if ($trimmed === '') {
            return '-';
        }

        return substr($trimmed, 0, 5);
    }

    private function formatDecimal(mixed $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    private function sanitizeCsvCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', (string) ($value ?? ''));
        $text = preg_replace('/\s*\r?\n\s*|\r|\t/', ' ', (string) $text);
        $text = trim((string) $text);

        if ($text === '') {
            return '-';
        }

        if ($text !== '-' && preg_match('/^[=+@\-]/', $text) === 1 && !is_numeric($text)) {
            return "'" . $text;
        }

        return $text;
    }

    private function writeCsvRow($handle, array $row): void
    {
        fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row), ',', '"', '');
    }

    private function buildDaftarTahun(string $dari, string $sampai, string $tahunAktif): array
    {
        $years = [(int) now()->year];

        $pushYear = static function (mixed $rawDate) use (&$years): void {
            if ($rawDate === null || $rawDate === '') {
                return;
            }
            try {
                $yr = (int) Carbon::parse($rawDate)->year;
                if ($yr >= 2000 && $yr <= 2100) {
                    $years[] = $yr;
                }
            } catch (\Throwable) {
                // Abaikan format tanggal yang tidak dapat diparsing
            }
        };

        $pushYear($dari);
        $pushYear($sampai);
        if ($tahunAktif !== '' && ctype_digit($tahunAktif)) {
            $yr = (int) $tahunAktif;
            if ($yr >= 2000 && $yr <= 2100) {
                $years[] = $yr;
            }
        }

        $dateQueries = [
            [Absensi::query(), 'tanggal'],
            [Jadwal::query(), 'tanggal'],
            [Jadwal::query(), 'periode_mulai'],
            [Jadwal::query(), 'periode_selesai'],
            [AktivitasMengajar::query(), 'tanggal'],
            [ObservasiSupervisor::query(), 'tanggal'],
            [Evaluasi::query(), 'created_at'],
        ];

        foreach ($dateQueries as [$query, $column]) {
            $pushYear($query->clone()->whereNotNull($column)->min($column));
            $pushYear($query->clone()->whereNotNull($column)->max($column));
        }

        $minYear = min($years);
        $maxYear = max($years);
        $range   = range($minYear, $maxYear);
        rsort($range);

        return $range;
    }

    public function index(Request $request)
    {
        $filters            = $this->resolveFilters($request);
        $jenis              = $filters['jenis'];
        $jenisLabel         = $filters['jenisLabel'];
        $instrukturId       = $filters['instrukturId'];
        $selectedInstruktur = $filters['selectedInstruktur'];
        $kelasId            = $filters['kelasId'];
        $selectedKelas      = $filters['selectedKelas'];
        $statusFilter       = $filters['statusFilter'];
        $statusFilterLabel  = $filters['statusFilterLabel'];
        $statusOptions      = $filters['statusOptions'];
        $dari               = $filters['dari'];
        $sampai             = $filters['sampai'];
        $bulan              = $filters['bulan'];
        $tahun              = $filters['tahun'];
        $periodeLabel       = $filters['periodeLabel'];
        $activeFilterParams = $filters['activeFilterParams'];

        $instrukturs     = Instruktur::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_instruktur']);
        $kelasList       = Schema::hasTable('kelas')
            ? Kelas::orderBy('kode_kelas')->get(['id', 'kode_kelas', 'nama_kelas', 'program_pelatihan', 'status'])
            : collect();
        $ruleConfig      = $this->kinerjaService->getRuleConfig();
        $isRuleConfirmed = (bool) ($ruleConfig['enabled'] ?? false);
        $sumberLabels    = self::SUMBER_ABSENSI_LABELS;
        $namaBulan       = self::NAMA_BULAN;
        $daftarTahun     = $this->buildDaftarTahun($dari, $sampai, $tahun);
        $routePrefix     = $this->routePrefix;

        $individualReport = null;
        $manajerialReport = null;

        if ($jenis === 'kinerja') {
            $data            = $this->buildKinerjaCollection($dari, $sampai, $instrukturId, $statusFilter, $selectedKelas);
            $distribusiChart = $this->buildDistribusiChart('kinerja', $data);
        } elseif ($jenis === 'individual') {
            $individualReport = $this->buildIndividualReport($selectedInstruktur, $dari, $sampai, $selectedKelas);
            $distribusiChart  = $this->buildIndividualDistribusiChart($individualReport);
            $data             = collect();
        } elseif ($jenis === 'manajerial') {
            $manajerialReport = $this->buildManajerialReport($dari, $sampai, $instrukturId, $selectedKelas);
            $distribusiChart  = $this->buildDistribusiChart('manajerial', $manajerialReport['kinerja_rows']);
            $data             = $manajerialReport['kinerja_rows'];
        } else {
            $query           = $this->buildReportQuery($jenis, $dari, $sampai, $instrukturId, $statusFilter, $selectedKelas);
            $distribusiChart = $this->buildDistribusiChartFromQuery($jenis, $query);
            $data            = $query->paginate(20)->withQueryString();
        }

        return view($this->viewName, compact(
            'jenis',
            'jenisLabel',
            'instrukturs',
            'selectedInstruktur',
            'instrukturId',
            'kelasList',
            'kelasId',
            'selectedKelas',
            'statusFilter',
            'statusFilterLabel',
            'statusOptions',
            'bulan',
            'tahun',
            'dari',
            'sampai',
            'periodeLabel',
            'namaBulan',
            'daftarTahun',
            'activeFilterParams',
            'sumberLabels',
            'ruleConfig',
            'data',
            'distribusiChart',
            'individualReport',
            'manajerialReport',
            'isRuleConfirmed',
            'routePrefix'
        ));
    }

    // Halaman khusus cetak laporan resmi / simpan PDF untuk Admin maupun Supervisor.
    public function cetak(Request $request)
    {
        $filters            = $this->resolveFilters($request);
        $jenis              = $filters['jenis'];
        $jenisLabel         = $filters['jenisLabel'];
        $instrukturId       = $filters['instrukturId'];
        $selectedInstruktur = $filters['selectedInstruktur'];
        $selectedKelas      = $filters['selectedKelas'];
        $statusFilter       = $filters['statusFilter'];
        $statusFilterLabel  = $filters['statusFilterLabel'];
        $statusOptions      = $filters['statusOptions'];
        $dari               = $filters['dari'];
        $sampai             = $filters['sampai'];
        $bulan              = $filters['bulan'];
        $tahun              = $filters['tahun'];
        $periodeLabel       = $filters['periodeLabel'];
        $activeFilterParams = $filters['activeFilterParams'];

        $individualReport = $jenis === 'individual'
            ? $this->buildIndividualReport($selectedInstruktur, $dari, $sampai, $selectedKelas)
            : null;
        $manajerialReport = $jenis === 'manajerial'
            ? $this->buildManajerialReport($dari, $sampai, $instrukturId, $selectedKelas)
            : null;

        $records         = in_array($jenis, ['individual', 'manajerial'], true)
            ? collect()
            : $this->getReportRecords($jenis, $dari, $sampai, $instrukturId, $statusFilter, $selectedKelas);
        $sumberLabels    = self::SUMBER_ABSENSI_LABELS;
        $ruleConfig      = $this->kinerjaService->getRuleConfig();
        $isRuleConfirmed = (bool) ($ruleConfig['enabled'] ?? false);
        $routePrefix     = $this->routePrefix;

        return view($this->cetakViewName, compact(
            'jenis',
            'jenisLabel',
            'dari',
            'sampai',
            'bulan',
            'tahun',
            'periodeLabel',
            'statusFilter',
            'statusFilterLabel',
            'statusOptions',
            'selectedInstruktur',
            'selectedKelas',
            'records',
            'individualReport',
            'manajerialReport',
            'activeFilterParams',
            'sumberLabels',
            'ruleConfig',
            'isRuleConfirmed',
            'routePrefix'
        ));
    }

    /**
     * Bangun struktur tabel (headers + rows) untuk jenis laporan tabular standar.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    private function buildTabularDataset(string $jenis, Collection $records): array
    {
        if ($jenis === 'absensi') {
            $headers = [
                'No', 'Tanggal', 'Kode Instruktur', 'Nama Instruktur', 'Jam Jadwal',
                'Jam Check-in', 'Jam Check-out', 'Menit Terlambat', 'Status',
                'Status GPS', 'Auto-Checkout', 'Sumber', 'Keterangan',
            ];
            $rows = [];
            foreach ($records as $i => $record) {
                $rows[] = [
                    $i + 1,
                    $record->tanggal?->format('d/m/Y') ?? '-',
                    $record->instruktur?->kode_instruktur ?? '-',
                    $record->instruktur?->nama_lengkap ?? '-',
                    $this->formatJam($record->jam_mulai_jadwal),
                    $this->formatJam($record->jam_checkin),
                    $this->formatJam($record->jam_checkout),
                    (int) ($record->menit_terlambat ?? 0),
                    $record->status_label,
                    $record->status_gps_label,
                    $record->is_auto_checkout ? 'Ya' : 'Tidak',
                    $this->formatSumberAbsensi($record->sumber),
                    $record->keterangan ?: '-',
                ];
            }
            return compact('headers', 'rows');
        }

        if ($jenis === 'jadwal') {
            $headers = [
                'No', 'Hari / Tanggal', 'Kode Instruktur', 'Nama Instruktur',
                'Instruktur Asli (Pengalihan)', 'Nama Kelas', 'Materi',
                'Jam Mulai', 'Jam Selesai', 'Ruangan', 'Jumlah Peserta', 'Status',
            ];
            $rows = [];
            foreach ($records as $i => $record) {
                $rows[] = [
                    $i + 1,
                    $record->tanggal_label,
                    $record->instruktur?->kode_instruktur ?? '-',
                    $record->instruktur?->nama_lengkap ?? '-',
                    $record->instrukturAsli?->nama_lengkap ?? '-',
                    $record->nama_kelas ?? '-',
                    $record->materi ?: 'Umum',
                    $this->formatJam($record->jam_mulai),
                    $this->formatJam($record->jam_selesai),
                    $record->ruangan_label,
                    (int) ($record->jumlah_peserta ?? 0),
                    $record->status_label,
                ];
            }
            return compact('headers', 'rows');
        }

        if ($jenis === 'aktivitas') {
            $headers = [
                'No', 'Tanggal', 'Kode Instruktur', 'Nama Instruktur', 'Kelas',
                'Peserta Hadir', 'Materi Disampaikan', 'Status Konfirmasi', 'Status Verifikasi',
                'Kendala Kegiatan', 'Hasil Pembelajaran', 'Catatan', 'Catatan Supervisor',
            ];
            $rows = [];
            foreach ($records as $i => $record) {
                $rows[] = [
                    $i + 1,
                    $record->tanggal?->format('d/m/Y') ?? '-',
                    $record->instruktur?->kode_instruktur ?? '-',
                    $record->instruktur?->nama_lengkap ?? '-',
                    $record->jadwal?->nama_kelas ?? '-',
                    (int) ($record->jumlah_peserta_hadir ?? 0),
                    $record->materi_disampaikan ?: '-',
                    $record->status_label,
                    $record->verifikasi_label,
                    $record->kendala_kegiatan ?: '-',
                    $record->hasil_pembelajaran ?: '-',
                    $record->catatan ?: '-',
                    $record->catatan_supervisor ?: '-',
                ];
            }
            return compact('headers', 'rows');
        }

        if ($jenis === 'evaluasi') {
            $headers = [
                'No', 'Tanggal Pengisian', 'Kode Instruktur', 'Nama Instruktur',
                'Pengisi', 'Kode Kelas / Batch', 'Materi', 'Skala 1-5',
                'Nilai (0-100)', 'Kategori', 'Komentar',
            ];
            $rows = [];
            foreach ($records as $i => $record) {
                $rows[] = [
                    $i + 1,
                    $record->created_at?->format('d/m/Y H:i') ?? '-',
                    $record->instruktur?->kode_instruktur ?? '-',
                    $record->instruktur?->nama_lengkap ?? '-',
                    $record->nama_pengisi ?: 'Anonim',
                    $record->kodeEvaluasi?->kode ?? ($record->batch_import ?: '-'),
                    $record->materi_evaluasi ?: '-',
                    $this->formatDecimal($record->rata_rata_skala5, 2),
                    $this->formatDecimal($record->total_nilai, 2),
                    $record->nilai_label,
                    $record->komentar_peserta ?: '-',
                ];
            }
            return compact('headers', 'rows');
        }

        if ($jenis === 'observasi') {
            $headers = [
                'No', 'Tanggal', 'Kode Instruktur', 'Nama Instruktur',
                'Supervisor Penilai', 'Skala 1-5', 'Skor (0-100)',
                'Catatan Observasi', 'Rekomendasi',
            ];
            $rows = [];
            foreach ($records as $i => $record) {
                $rows[] = [
                    $i + 1,
                    $record->tanggal?->format('d/m/Y') ?? '-',
                    $record->instruktur?->kode_instruktur ?? '-',
                    $record->instruktur?->nama_lengkap ?? '-',
                    $record->supervisor?->name ?? '-',
                    $this->formatDecimal($record->rata_rata_skala5, 2),
                    $this->formatDecimal($record->skor_observasi, 2),
                    $record->catatan_observasi ?: '-',
                    $record->rekomendasi ?: '-',
                ];
            }
            return compact('headers', 'rows');
        }

        $headers = [
            'No', 'Kode Instruktur', 'Nama Instruktur', 'Bidang Keahlian',
            'Total Jadwal (Sesi)', 'Jam Mengajar', 'Sesi Hadir', 'Total Sesi Absensi',
            'Skor Kehadiran (%)', 'Aktivitas Dikonfirmasi', 'Basis Sesi Aktivitas',
            'Skor Aktivitas (%)', 'Jumlah Evaluasi', 'Rata-rata Evaluasi (Skala 1-5)',
            'Skor Evaluasi (0-100)', 'Jumlah Observasi', 'Rata-rata Observasi (Skala 1-5)',
            'Skor Observasi (0-100)', 'Skor Akhir', 'Kategori Kinerja',
        ];
        $rows = [];
        foreach ($records as $i => $item) {
            $rows[] = [
                $i + 1,
                $item['instruktur']->kode_instruktur,
                $item['instruktur']->nama_lengkap,
                $item['instruktur']->bidang_keahlian ?: '-',
                (int) ($item['total_jadwal'] ?? 0),
                $this->formatDecimal($item['jam_mengajar'] ?? 0, 1),
                (int) ($item['total_hadir'] ?? 0),
                (int) ($item['total_absensi'] ?? 0),
                $this->formatDecimal($item['skor_kehadiran'] ?? null, 2),
                (int) ($item['total_aktivitas'] ?? 0),
                (int) ($item['basis_sesi_aktivitas'] ?? 0),
                $this->formatDecimal($item['skor_aktivitas'] ?? null, 2),
                (int) ($item['total_evaluasi'] ?? 0),
                $this->formatDecimal($item['rata_rata_evaluasi_skala5'] ?? null, 2),
                $this->formatDecimal($item['skor_evaluasi'] ?? null, 2),
                (int) ($item['total_observasi'] ?? 0),
                $this->formatDecimal($item['rata_rata_observasi_skala5'] ?? null, 2),
                $this->formatDecimal($item['skor_observasi'] ?? null, 2),
                $this->formatDecimal($item['skor_akhir'] ?? null, 2),
                $item['kategori_label'] ?? '-',
            ];
        }
        return compact('headers', 'rows');
    }

    private function buildExcelSheets(array $filters): array
    {
        $jenis              = $filters['jenis'];
        $instrukturId       = $filters['instrukturId'];
        $selectedInstruktur = $filters['selectedInstruktur'];
        $selectedKelas      = $filters['selectedKelas'];
        $statusFilter       = $filters['statusFilter'];
        $dari               = $filters['dari'];
        $sampai             = $filters['sampai'];
        $periodeLabel       = $filters['periodeLabel'];

        if ($jenis === 'individual') {
            $rep = $this->buildIndividualReport($selectedInstruktur, $dari, $sampai, $selectedKelas);
            if (! $rep) {
                return [[
                    'name'    => 'Rapor Individual',
                    'headers' => ['Keterangan'],
                    'rows'    => [['Pilih satu instruktur pada filter untuk mengekspor Rapor Individual.']],
                ]];
            }

            $ins = $rep['instruktur'];
            $k   = $rep['kinerja'];
            $summaryRows = [
                ['Kode Instruktur', $ins->kode_instruktur],
                ['Nama Lengkap', $ins->nama_lengkap],
                ['Bidang Keahlian', $ins->bidang_keahlian ?: '-'],
                ['Status', $ins->status_label],
                ['Periode', $periodeLabel],
                ['Total Sesi Jadwal', (int) ($k['total_jadwal'] ?? 0)],
                ['Total Jam Mengajar', $this->formatDecimal($k['jam_mengajar'] ?? 0, 1)],
                ['Persentase Kehadiran (%)', $this->formatDecimal($k['persentase_kehadiran'] ?? 0, 1)],
                ['Skor Aktivitas (%)', $this->formatDecimal($k['skor_aktivitas'] ?? null, 2)],
                ['Skor Evaluasi (0-100)', $this->formatDecimal($k['skor_evaluasi'] ?? null, 2)],
                ['Skor Observasi (0-100)', $this->formatDecimal($k['skor_observasi'] ?? null, 2)],
                ['Skor Akhir Kinerja', $this->formatDecimal($k['skor_akhir'] ?? null, 2)],
                ['Kategori Kinerja', $k['kategori_label'] ?? '-'],
            ];

            $sertRows = [];
            foreach ($rep['sertifikasis'] as $i => $s) {
                $statusInfo = $s->status_info;
                $sertRows[] = [
                    $i + 1,
                    $s->nama_sertifikasi,
                    $s->keterangan ?: '-',
                    $s->file_name ?: '-',
                    $s->berlaku_sampai?->format('d/m/Y') ?? '-',
                    ($statusInfo['badge'] ?? 'Aktif') . (!empty($statusInfo['detail']) ? ' (' . $statusInfo['detail'] . ')' : ''),
                ];
            }

            return [
                ['name' => 'Ringkasan Rapor', 'headers' => ['Komponen', 'Nilai'], 'rows' => $summaryRows],
                ['name' => 'Sertifikasi', 'headers' => ['No', 'Nama Sertifikasi', 'Keterangan / Penerbit', 'Berkas Dokumen', 'Berlaku Sampai', 'Status'], 'rows' => $sertRows],
                array_merge(['name' => 'Jadwal'], $this->buildTabularDataset('jadwal', $rep['jadwals'])),
                array_merge(['name' => 'Absensi'], $this->buildTabularDataset('absensi', $rep['absensis'])),
                array_merge(['name' => 'Aktivitas'], $this->buildTabularDataset('aktivitas', $rep['aktivitas'])),
                array_merge(['name' => 'Evaluasi'], $this->buildTabularDataset('evaluasi', $rep['evaluasis'])),
                array_merge(['name' => 'Observasi'], $this->buildTabularDataset('observasi', $rep['observasis'])),
            ];
        }

        if ($jenis === 'manajerial') {
            $m   = $this->buildManajerialReport($dari, $sampai, $instrukturId, $selectedKelas);
            $kpi = $m['kpi'];
            $kpiRows = [
                ['Periode', $periodeLabel],
                ['Instruktur Aktif', $kpi['total_instruktur_aktif'] . ' / ' . $kpi['total_instruktur']],
                ['Kelas Unik Diampu', (int) $kpi['kelas_diampu']],
                ['Total Sesi Jadwal', (int) $kpi['total_sesi']],
                ['Total Jam Mengajar', $this->formatDecimal($kpi['total_jam_mengajar'], 1)],
                ['Rata-rata Kehadiran (%)', $this->formatDecimal($kpi['rata_kehadiran'], 1)],
                ['Rata-rata Evaluasi (0-100)', $this->formatDecimal($kpi['rata_evaluasi'], 2)],
                ['Rata-rata Skor Kinerja', $this->formatDecimal($kpi['rata_skor_kinerja'], 2)],
                ['Kategori Sangat Baik', (int) ($m['distribusi_kategori']['sangat_baik'] ?? 0)],
                ['Kategori Baik', (int) ($m['distribusi_kategori']['baik'] ?? 0)],
                ['Kategori Cukup', (int) ($m['distribusi_kategori']['cukup'] ?? 0)],
                ['Kategori Kurang', (int) ($m['distribusi_kategori']['kurang'] ?? 0)],
                ['Peringatan Kelas Tanpa Laporan', (int) ($m['alerts']['kelas_tanpa_laporan'] ?? 0)],
                ['Peringatan Sertifikasi Kedaluwarsa', (int) ($m['alerts']['sertifikasi_hampir_expired'] ?? 0)],
                ['Peringatan Instruktur Di Bawah Standar', (int) ($m['alerts']['instruktur_dibawah_standar'] ?? 0)],
            ];

            $alertRows = [];
            foreach ($m['alerts']['items'] as $i => $a) {
                $alertRows[] = [
                    $i + 1,
                    $a['kategori'] ?? '-',
                    $a['judul'] ?? '-',
                    $a['ringkasan'] ?? '-',
                ];
            }

            return [
                ['name' => 'KPI Manajerial', 'headers' => ['Indikator Eksekutif', 'Nilai'], 'rows' => $kpiRows],
                array_merge(['name' => 'Rekap Kinerja'], $this->buildTabularDataset('kinerja', $m['kinerja_rows'])),
                ['name' => 'Peringatan Aktif', 'headers' => ['No', 'Kategori', 'Judul', 'Ringkasan'], 'rows' => $alertRows],
            ];
        }

        $records = $this->getReportRecords($jenis, $dari, $sampai, $instrukturId, $statusFilter, $selectedKelas);
        $table   = $this->buildTabularDataset($jenis, $records);

        return [
            [
                'name'    => self::JENIS_LABELS[$jenis] ?? ucfirst($jenis),
                'headers' => $table['headers'],
                'rows'    => $table['rows'],
            ],
        ];
    }

    public function export(Request $request)
    {
        $filters       = $this->resolveFilters($request);
        $jenis         = $filters['jenis'];
        $instrukturId  = $filters['instrukturId'];
        $selectedKelas = $filters['selectedKelas'];
        $statusFilter  = $filters['statusFilter'];
        $dari          = $filters['dari'];
        $sampai        = $filters['sampai'];

        $filename = "laporan_{$jenis}_{$dari}_{$sampai}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ];

        $sheets = in_array($jenis, ['individual', 'manajerial'], true)
            ? $this->buildExcelSheets($filters)
            : [$this->buildTabularDataset($jenis, $this->getReportRecords($jenis, $dari, $sampai, $instrukturId, $statusFilter, $selectedKelas))];

        $callback = function () use ($sheets, $jenis) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            if (in_array($jenis, ['individual', 'manajerial'], true)) {
                foreach ($sheets as $sheet) {
                    $this->writeCsvRow($handle, ['=== ' . ($sheet['name'] ?? 'Bagian') . ' ===']);
                    if (! empty($sheet['headers'])) {
                        $this->writeCsvRow($handle, $sheet['headers']);
                    }
                    foreach ($sheet['rows'] ?? [] as $row) {
                        $this->writeCsvRow($handle, $row);
                    }
                    $this->writeCsvRow($handle, []);
                }
            } else {
                $table = $sheets[0] ?? ['headers' => [], 'rows' => []];
                if (! empty($table['headers'])) {
                    $this->writeCsvRow($handle, $table['headers']);
                }
                foreach ($table['rows'] as $row) {
                    $this->writeCsvRow($handle, $row);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $filters  = $this->resolveFilters($request);
        $jenis    = $filters['jenis'];
        $dari     = $filters['dari'];
        $sampai   = $filters['sampai'];
        $filename = "laporan_{$jenis}_{$dari}_{$sampai}.xlsx";

        return $this->xlsxExporter->download($filename, $this->buildExcelSheets($filters));
    }
}
