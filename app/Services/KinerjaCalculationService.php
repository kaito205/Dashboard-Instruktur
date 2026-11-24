<?php

namespace App\Services;

use App\Models\Instruktur;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KinerjaCalculationService
{
    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(?JadwalOccurrenceService $occurrenceService = null)
    {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public static function clearMemo(): void
    {
        static::$memoEvaluateAll = [];
    }

    private function getFloatSetting(string $key, float $default): float
    {
        $val = Setting::get($key);
        if ($val === null || $val === '') {
            return $default;
        }

        return (float) $val;
    }

    /**
     * Periksa apakah perhitungan otomatis kategori kinerja diaktifkan pada pengaturan sistem.
     */
    public function isRuleConfirmed(): bool
    {
        return Setting::get('kinerja_rule_enabled', '1') === '1';
    }

    /**
     * Ambil konfigurasi bobot dan ambang batas kategori kinerja dari tabel settings.
     */
    public function getRuleConfig(): array
    {
        $rawKehadiran = Setting::get('bobot_kehadiran');
        if ($rawKehadiran === null || $rawKehadiran === '') {
            $rawKehadiran = Setting::get('bobot_absensi');
        }
        $bobotKehadiran = ($rawKehadiran !== null && $rawKehadiran !== '') ? (float) $rawKehadiran : 30.0;

        $bobotAktivitas = $this->getFloatSetting('bobot_aktivitas', 20.0);
        $bobotEvaluasi  = $this->getFloatSetting('bobot_evaluasi', 30.0);
        $bobotObservasi = $this->getFloatSetting('bobot_observasi', 20.0);

        return [
            'enabled'           => $this->isRuleConfirmed(),
            'bobot_kehadiran'   => $bobotKehadiran,
            'bobot_aktivitas'   => $bobotAktivitas,
            'bobot_evaluasi'    => $bobotEvaluasi,
            'bobot_observasi'   => $bobotObservasi,
            'batas_sangat_baik' => $this->getFloatSetting('batas_sangat_baik', 85.0),
            'batas_baik'        => $this->getFloatSetting('batas_baik', 75.0),
            'batas_cukup'       => $this->getFloatSetting('batas_cukup', 60.0),
        ];
    }

    /**
     * Ambil parameter presensi (toleransi keterlambatan dan waktu buka check-in) dari tabel settings.
     */
    public function getAttendanceConfig(): array
    {
        return [
            'toleransi_terlambat_menit'  => max(0, (int) Setting::get('toleransi_terlambat_menit', '15')),
            'checkin_buka_sebelum_menit' => max(0, (int) Setting::get('checkin_buka_sebelum_menit', '30')),
        ];
    }

    /**
     * Format angka desimal tanpa nol berlebih (mis. 85.0 -> 85, 85.5 -> 85,5).
     */
    public static function formatAngka(float $value): string
    {
        $clean     = abs($value) < 0.005 ? 0.0 : $value;
        $formatted = number_format($clean, 2, ',', '');
        if (str_contains($formatted, ',')) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted;
    }

    /**
     * Klasifikasikan skor (0-100) ke kategori kinerja berdasarkan ambang batas pada settings.
     */
    public function classifyScore(?float $score, ?array $config = null): array
    {
        if ($score === null) {
            return [
                'key'   => null,
                'label' => 'Belum Ada Data Penilaian',
                'color' => 'gray',
            ];
        }

        $cfg = $config ?? $this->getRuleConfig();

        $key = match (true) {
            $score >= (float) $cfg['batas_sangat_baik'] => 'sangat_baik',
            $score >= (float) $cfg['batas_baik']        => 'baik',
            $score >= (float) $cfg['batas_cukup']       => 'cukup',
            default                                     => 'kurang',
        };

        $label = match ($key) {
            'sangat_baik' => 'Sangat Baik',
            'baik'        => 'Baik',
            'cukup'       => 'Cukup',
            'kurang'      => 'Kurang',
        };

        $color = match ($key) {
            'sangat_baik' => 'emerald',
            'baik'        => 'blue',
            'cukup'       => 'amber',
            'kurang'      => 'red',
        };

        return [
            'key'   => $key,
            'label' => $label,
            'color' => $color,
        ];
    }

    /**
     * Bangun label rentang kategori kinerja sesuai ambang batas aktif pada pengaturan.
     */
    public function getKategoriRangeLabels(?array $config = null): array
    {
        $cfg = $config ?? $this->getRuleConfig();
        $sb  = self::formatAngka((float) $cfg['batas_sangat_baik']);
        $b   = self::formatAngka((float) $cfg['batas_baik']);
        $c   = self::formatAngka((float) $cfg['batas_cukup']);

        $ranges = [
            'sangat_baik' => '>= ' . $sb,
            'baik'        => "{$b} - < {$sb}",
            'cukup'       => "{$c} - < {$b}",
            'kurang'      => '< ' . $c,
        ];

        return [
            'sangat_baik' => "Sangat Baik ({$ranges['sangat_baik']})",
            'baik'        => "Baik ({$ranges['baik']})",
            'cukup'       => "Cukup ({$ranges['cukup']})",
            'kurang'      => "Kurang ({$ranges['kurang']})",
            'ranges'      => $ranges,
        ];
    }

    /**
     * Hitung ringkasan kinerja untuk satu instruktur berdasarkan data aktual di database.
     *
     * Prinsip Wajib LPK CTI:
     * 1. Nilai asli (mentah) absensi, aktivitas mengajar, evaluasi peserta (skala 1-5), dan observasi supervisor (skala 1-5)
     *    tetap dipertahankan utuh dan dikembalikan berdampingan dengan nilai kalkulasi skala 0-100.
     * 2. Jika suatu komponen belum memiliki data pada periode penilaian, bobot komponen tersebut didistribusikan
     *    secara proporsional ke komponen yang tersedia (tidak diberi nilai 0).
     */
    public function evaluateInstruktur(Instruktur $instruktur, string $dari, string $sampai): array
    {
        $absensisRaw = $instruktur->relationLoaded('absensis')
            ? $instruktur->absensis
            : $instruktur->absensis()->with('jadwal')->whereBetween('tanggal', [$dari, $sampai])->get();

        $jadwals = $instruktur->relationLoaded('jadwals')
            ? $instruktur->jadwals
            : $instruktur->jadwals()->periode($dari, $sampai)->get();

        $jadwalById = $jadwals->keyBy('id');
        foreach ($absensisRaw as $absItem) {
            if ($absItem->jadwal_id !== null && ! $absItem->relationLoaded('jadwal') && $jadwalById->has($absItem->jadwal_id)) {
                $absItem->setRelation('jadwal', $jadwalById->get($absItem->jadwal_id));
            }
        }
        $absensis = $this->occurrenceService->filterValidAttendanceRecords($absensisRaw);

        $evaluasis = $instruktur->relationLoaded('evaluasis')
            ? $instruktur->evaluasis
            : $instruktur->evaluasis()->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->get();

        $aktivitas = $instruktur->relationLoaded('aktivitasMengajars')
            ? $instruktur->aktivitasMengajars
            : $instruktur->aktivitasMengajars()->whereBetween('tanggal', [$dari, $sampai])->get();

        $observasis = $instruktur->relationLoaded('observasiSupervisors')
            ? $instruktur->observasiSupervisors
            : $instruktur->observasiSupervisors()->whereBetween('tanggal', [$dari, $sampai])->get();

        $jadwals->each(fn ($j) => $j->setRelation('instruktur', $instruktur));

        // 1. Komponen Kehadiran (Nilai Asli & Skor Skala 0-100)
        $missedOccurrences  = $this->occurrenceService->getMissedAttendanceOccurrences($jadwals, $absensis, $dari, $sampai);
        $tidakHadirOtomatis = $missedOccurrences->count();

        $hadirTepat         = $absensis->where('status', 'hadir')->count();
        $terlambat          = $absensis->where('status', 'terlambat')->count();
        $izin               = $absensis->where('status', 'izin')->count();
        $tidakHadir         = $absensis->where('status', 'tidak_hadir')->count() + $tidakHadirOtomatis;
        $totalAbsensi       = $absensis->count() + $tidakHadirOtomatis;
        $totalHadir         = $hadirTepat + $terlambat;
        $totalMenitLambat   = (int) $absensis->sum('menit_terlambat');

        $persentaseKehadiran = $totalAbsensi > 0
            ? round(($totalHadir / $totalAbsensi) * 100, 1)
            : 0.0;
        $skorKehadiran = $totalAbsensi > 0
            ? round(($totalHadir / $totalAbsensi) * 100, 2)
            : null;

        // 2. Komponen Jadwal & Kepatuhan Laporan Aktivitas Mengajar (Nilai Asli & Skor Skala 0-100)
        $jadwalKonkret      = $jadwals->where('is_pola_mingguan', false);
        $jadwalPolaMingguan = $jadwals->where('is_pola_mingguan', true);
        $totalJadwal        = $jadwals->count();
        $totalJadwalSelesai = $jadwalKonkret->where('status', 'selesai')->count();

        $totalJamMengajar = $jadwals
            ->filter(fn ($j) => $j->status === 'selesai' || $j->is_pola_mingguan)
            ->sum(fn ($j) => Carbon::parse($j->jam_mulai)->diffInMinutes(Carbon::parse($j->jam_selesai)) / 60);
        $totalJamMengajar = round($totalJamMengajar, 1);

        $aktivitasDedup = $aktivitas
            ->sortByDesc('id')
            ->unique(fn ($a) => ($a->jadwal_id ?? '0') . '|' . ($a->tanggal?->toDateString() ?? (string) $a->id))
            ->values();

        $aktivitasTerlapor          = $aktivitasDedup->where('status_konfirmasi', '!=', 'belum_dikonfirmasi');
        $totalCatatanAktivitas      = $aktivitasTerlapor->count();
        $totalAktivitasDikonfirmasi = $aktivitasTerlapor->where('status_konfirmasi', 'dikonfirmasi')->count();
        $totalAktivitasDiverifikasi = $aktivitasTerlapor->where('status_verifikasi', 'diverifikasi')->count();
        $totalAktivitasRevisi       = $aktivitasTerlapor->where('status_verifikasi', 'perlu_revisi')->count();

        $elapsedOccurrences    = $this->occurrenceService->expandElapsedOccurrences($jadwals, $dari, $sampai);
        $missingAktivitasCount = $this->occurrenceService
            ->getMissingActivityOccurrences($jadwals, $aktivitasTerlapor, $dari, $sampai)
            ->count();

        $basisSesiAktivitas = max(
            $elapsedOccurrences->count(),
            $totalCatatanAktivitas + $missingAktivitasCount
        );
        $persentaseAktivitas = $basisSesiAktivitas > 0
            ? min(100.0, round(($totalAktivitasDikonfirmasi / $basisSesiAktivitas) * 100, 2))
            : null;
        $skorAktivitas = $persentaseAktivitas;

        // 3. Komponen Evaluasi Peserta (Nilai Asli Skala 1-5 & Skor Konversi 0-100)
        $totalEvaluasi = $evaluasis->count();
        $avgEvaluasi   = $totalEvaluasi > 0 ? $evaluasis->avg('total_nilai') : null;
        $rataRataNilai = $avgEvaluasi !== null
            ? round((float) $avgEvaluasi, 2)
            : null;

        $skala5EvaluasiValues = $evaluasis
            ->map(fn ($e) => $e->rata_rata_skala5)
            ->filter(fn ($v) => $v !== null)
            ->values();

        $rataRataEvaluasiSkala5 = $skala5EvaluasiValues->isNotEmpty()
            ? round((float) $skala5EvaluasiValues->avg(), 2)
            : ($rataRataNilai !== null ? round($rataRataNilai / 20.0, 2) : null);

        $skorEvaluasi = $rataRataNilai;

        // 4. Komponen Observasi Supervisor (Nilai Asli Skala 1-5 & Skor Konversi 0-100)
        $totalObservasi    = $observasis->count();
        $avgObservasi      = $totalObservasi > 0 ? $observasis->avg('skor_observasi') : null;
        $rataRataObservasi = $avgObservasi !== null
            ? round((float) $avgObservasi, 2)
            : null;

        $skala5ObservasiValues = $observasis
            ->map(fn ($o) => $o->rata_rata_skala5)
            ->filter(fn ($v) => $v !== null)
            ->values();

        $rataRataObservasiSkala5 = $skala5ObservasiValues->isNotEmpty()
            ? round((float) $skala5ObservasiValues->avg(), 2)
            : ($rataRataObservasi !== null ? round($rataRataObservasi / 20.0, 2) : null);

        $skorObservasi = $rataRataObservasi;

        // 5. Kalkulasi Skor Akhir dengan Redistribusi Bobot Proporsional
        $config = $this->getRuleConfig();

        $komponenMap = [
            'kehadiran' => [
                'skor'       => $skorKehadiran,
                'bobot_awal' => $config['bobot_kehadiran'],
                'tersedia'   => $skorKehadiran !== null,
            ],
            'aktivitas' => [
                'skor'       => $skorAktivitas,
                'bobot_awal' => $config['bobot_aktivitas'],
                'tersedia'   => $skorAktivitas !== null,
            ],
            'evaluasi' => [
                'skor'       => $skorEvaluasi,
                'bobot_awal' => $config['bobot_evaluasi'],
                'tersedia'   => $skorEvaluasi !== null,
            ],
            'observasi' => [
                'skor'       => $skorObservasi,
                'bobot_awal' => $config['bobot_observasi'],
                'tersedia'   => $skorObservasi !== null,
            ],
        ];

        $totalBobotTersedia = collect($komponenMap)
            ->filter(fn ($k) => $k['tersedia'] && $k['bobot_awal'] > 0)
            ->sum('bobot_awal');

        $bobotEfektif = [];
        $kontribusi   = [];
        foreach ($komponenMap as $key => $item) {
            if ($item['tersedia'] && $totalBobotTersedia > 0 && $item['bobot_awal'] > 0) {
                $efektif = round(($item['bobot_awal'] / $totalBobotTersedia) * 100.0, 2);
                $bobotEfektif[$key] = $efektif;
                $kontribusi[$key]   = round(($item['skor'] * $item['bobot_awal']) / $totalBobotTersedia, 2);
            } else {
                $bobotEfektif[$key] = 0.0;
                $kontribusi[$key]   = null;
            }
        }

        $skorAkhir     = null;
        $kategori      = null;
        $kategoriLabel = $config['enabled'] ? 'Belum Ada Data Penilaian' : 'Menunggu Konfirmasi LPK';
        $kategoriColor = 'gray';

        if ($config['enabled'] && $totalBobotTersedia > 0) {
            $weightedSum = 0.0;
            foreach ($komponenMap as $item) {
                if ($item['tersedia'] && $item['bobot_awal'] > 0) {
                    $weightedSum += ((float) $item['skor']) * ((float) $item['bobot_awal']);
                }
            }

            $skorAkhir     = round($weightedSum / $totalBobotTersedia, 2);
            $classification = $this->classifyScore($skorAkhir, $config);
            $kategori      = $classification['key'];
            $kategoriLabel = $classification['label'];
            $kategoriColor = $classification['color'];
        }

        $totalKomponenStandar = collect($komponenMap)->filter(fn ($k) => $k['bobot_awal'] > 0)->sum('bobot_awal');
        $isRedistribusi = $totalBobotTersedia > 0 && ($totalKomponenStandar - $totalBobotTersedia) > 0.001;

        $bobotStandar = [
            'kehadiran' => $config['bobot_kehadiran'],
            'aktivitas' => $config['bobot_aktivitas'],
            'evaluasi'  => $config['bobot_evaluasi'],
            'observasi' => $config['bobot_observasi'],
        ];

        $dataMentahLabels = [
            'kehadiran_label' => $totalAbsensi > 0
                ? "{$totalHadir} hadir ({$hadirTepat} tepat waktu, {$terlambat} terlambat) dari {$totalAbsensi} sesi"
                : 'Belum ada data presensi pada periode ini',
            'aktivitas_label' => $basisSesiAktivitas > 0
                ? "{$totalAktivitasDikonfirmasi} laporan dikonfirmasi ({$totalAktivitasDiverifikasi} diverifikasi) dari {$basisSesiAktivitas} sesi"
                : 'Belum ada sesi aktivitas terlaksana pada periode ini',
            'evaluasi_label'  => $totalEvaluasi > 0
                ? 'Rata-rata ' . number_format((float) $rataRataEvaluasiSkala5, 2) . ' / 5.00 (' . $totalEvaluasi . ' responden)'
                : 'Belum ada evaluasi peserta pada periode ini',
            'observasi_label' => $totalObservasi > 0
                ? 'Rata-rata ' . number_format((float) $rataRataObservasiSkala5, 2) . ' / 5.00 (' . $totalObservasi . ' observasi)'
                : 'Belum ada observasi Supervisor pada periode ini',
        ];

        return [
            'instruktur'                  => $instruktur,
            'total_jadwal'                => $totalJadwal,
            'total_jadwal_pola'           => $jadwalPolaMingguan->count(),
            'total_jadwal_selesai'        => $totalJadwalSelesai,
            'jam_mengajar'                => $totalJamMengajar,
            // Data Asli & Kalkulasi Komponen 1: Kehadiran
            'total_absensi'               => $totalAbsensi,
            'hadir_tepat'                 => $hadirTepat,
            'terlambat'                   => $terlambat,
            'izin'                        => $izin,
            'tidak_hadir'                 => $tidakHadir,
            'tidak_hadir_otomatis'        => $tidakHadirOtomatis,
            'total_hadir'                 => $totalHadir,
            'total_menit_terlambat'       => $totalMenitLambat,
            'persentase_kehadiran'        => $persentaseKehadiran,
            'skor_kehadiran'              => $skorKehadiran,
            'status_filter_aktif'         => null,
            'status_filter_label'         => null,
            'status_filter_count'         => null,
            'status_filter_persentase'    => null,
            // Data Asli & Kalkulasi Komponen 2: Kepatuhan Aktivitas
            'basis_sesi_aktivitas'        => $basisSesiAktivitas,
            'total_catatan_aktivitas'     => $totalCatatanAktivitas,
            'total_aktivitas'             => $totalAktivitasDikonfirmasi,
            'total_aktivitas_diverifikasi'=> $totalAktivitasDiverifikasi,
            'total_aktivitas_revisi'      => $totalAktivitasRevisi,
            'persentase_aktivitas'        => $persentaseAktivitas,
            'skor_aktivitas'              => $skorAktivitas,
            // Data Asli & Kalkulasi Komponen 3: Evaluasi Peserta
            'total_evaluasi'              => $totalEvaluasi,
            'rata_rata_evaluasi_skala5'   => $rataRataEvaluasiSkala5,
            'rata_rata_nilai'             => $rataRataNilai,
            'skor_evaluasi'               => $skorEvaluasi,
            // Data Asli & Kalkulasi Komponen 4: Observasi Supervisor
            'total_observasi'             => $totalObservasi,
            'rata_rata_observasi_skala5'  => $rataRataObservasiSkala5,
            'rata_rata_observasi'         => $rataRataObservasi,
            'skor_observasi'              => $skorObservasi,
            // Bobot & Hasil Akhir
            'bobot'                       => $bobotStandar,
            'bobot_awal'                  => $bobotStandar,
            'bobot_efektif'               => $bobotEfektif,
            'redistribusi_bobot'          => $isRedistribusi,
            'data_mentah'                 => $dataMentahLabels,
            'kontribusi_komponen'         => $kontribusi,
            'skor_akhir'                  => $skorAkhir,
            'kategori'                    => $kategori,
            'kategori_label'              => $kategoriLabel,
            'kategori_color'              => $kategoriColor,
        ];
    }

    protected static array $memoEvaluateAll = [];

    /**
     * Evaluasi daftar instruktur sekaligus untuk rentang tanggal tertentu.
     */
    public function evaluateAll(string $dari, string $sampai, ?string $instrukturId = null, ?string $statusFilter = null): Collection
    {
        $memoKey = "{$dari}|{$sampai}|" . ($instrukturId ?: 'all') . '|' . ($statusFilter ?: 'all');
        if (isset(static::$memoEvaluateAll[$memoKey])) {
            return static::$memoEvaluateAll[$memoKey];
        }

        $activeAttendanceStatus = ($statusFilter && in_array($statusFilter, ['hadir', 'terlambat', 'izin', 'tidak_hadir'], true))
            ? $statusFilter
            : null;

        $rows = Instruktur::aktif()
            ->when($instrukturId && $instrukturId !== 'all', fn ($q) => $q->where('id', $instrukturId))
            ->with([
                'absensis'             => fn ($q) => $q->whereBetween('tanggal', [$dari, $sampai]),
                'evaluasis'            => fn ($q) => $q->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']),
                'jadwals'              => fn ($q) => $q->periode($dari, $sampai)
                    ->when($statusFilter && in_array($statusFilter, ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'], true), fn ($sq) => $sq->where('status', $statusFilter)),
                'aktivitasMengajars'   => fn ($q) => $q->whereBetween('tanggal', [$dari, $sampai]),
                'observasiSupervisors' => fn ($q) => $q->whereBetween('tanggal', [$dari, $sampai]),
            ])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn (Instruktur $inst) => $this->evaluateInstruktur($inst, $dari, $sampai));

        if ($activeAttendanceStatus !== null) {
            $statusLabelMap = [
                'hadir'       => 'Hadir',
                'terlambat'   => 'Terlambat',
                'izin'        => 'Izin',
                'tidak_hadir' => 'Tidak Hadir',
            ];
            $statusKeyMap = [
                'hadir'       => 'hadir_tepat',
                'terlambat'   => 'terlambat',
                'izin'        => 'izin',
                'tidak_hadir' => 'tidak_hadir',
            ];

            $rows = $rows
                ->map(function (array $row) use ($activeAttendanceStatus, $statusLabelMap, $statusKeyMap) {
                    $countKey     = $statusKeyMap[$activeAttendanceStatus];
                    $matchCount   = (int) ($row[$countKey] ?? 0);
                    $totalAbsensi = (int) ($row['total_absensi'] ?? 0);

                    $row['status_filter_aktif']      = $activeAttendanceStatus;
                    $row['status_filter_label']      = $statusLabelMap[$activeAttendanceStatus];
                    $row['status_filter_count']      = $matchCount;
                    $row['status_filter_persentase'] = $totalAbsensi > 0
                        ? round(($matchCount / $totalAbsensi) * 100, 1)
                        : 0.0;

                    return $row;
                })
                ->filter(fn (array $row) => $row['status_filter_count'] > 0)
                ->values();
        }

        return static::$memoEvaluateAll[$memoKey] = $rows;
    }
}
