<?php

namespace App\Services;

use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
    public function evaluateInstruktur(
        Instruktur $instruktur,
        string $dari,
        string $sampai,
        ?Kelas $selectedKelas = null
    ): array {
        if (Instruktur::hasCutiTable()) {
            $instruktur->loadMissing('cutis');
        }
        $sampaiEnd = Carbon::parse($sampai)->toDateString() . ' 23:59:59';

        if ($selectedKelas !== null) {
            $jadwals = $instruktur->jadwals()
                ->periode($dari, $sampai)
                ->where(fn (Builder $q) => $selectedKelas->scopeMatchingJadwal($q))
                ->get();

            $absensisRaw = $instruktur->absensis()
                ->with('jadwal')
                ->whereBetween('tanggal', [$dari, $sampaiEnd])
                ->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq))
                ->get();

            $evaluasis = $instruktur->evaluasis()
                ->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
                ->where(function (Builder $sub) use ($selectedKelas) {
                    $sub->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq))
                        ->orWhereHas('kodeEvaluasi', fn (Builder $kq) => $selectedKelas->scopeMatchingKodeEvaluasi($kq));
                })
                ->get();

            $aktivitas = $instruktur->aktivitasMengajars()
                ->with('jadwal')
                ->whereBetween('tanggal', [$dari, $sampaiEnd])
                ->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq))
                ->get();

            $observasis = $instruktur->observasiSupervisors()
                ->whereBetween('tanggal', [$dari, $sampaiEnd])
                ->whereHas('jadwal', fn (Builder $jq) => $selectedKelas->scopeMatchingJadwal($jq))
                ->get();
        } else {
            $absensisRaw = $instruktur->relationLoaded('absensis')
                ? $instruktur->absensis
                : $instruktur->absensis()->with('jadwal')->whereBetween('tanggal', [$dari, $sampaiEnd])->get();

            $jadwals = $instruktur->relationLoaded('jadwals')
                ? $instruktur->jadwals
                : $instruktur->jadwals()->periode($dari, $sampai)->get();

            $evaluasis = $instruktur->relationLoaded('evaluasis')
                ? $instruktur->evaluasis
                : $instruktur->evaluasis()->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->get();

            $aktivitas = $instruktur->relationLoaded('aktivitasMengajars')
                ? $instruktur->aktivitasMengajars
                : $instruktur->aktivitasMengajars()->with('jadwal')->whereBetween('tanggal', [$dari, $sampaiEnd])->get();

            $observasis = $instruktur->relationLoaded('observasiSupervisors')
                ? $instruktur->observasiSupervisors
                : $instruktur->observasiSupervisors()->whereBetween('tanggal', [$dari, $sampaiEnd])->get();
        }

        $jadwalById = $jadwals->keyBy('id');
        foreach ($absensisRaw as $absItem) {
            if ($absItem->jadwal_id !== null && ! $absItem->relationLoaded('jadwal') && $jadwalById->has($absItem->jadwal_id)) {
                $absItem->setRelation('jadwal', $jadwalById->get($absItem->jadwal_id));
            }
        }
        $absensis = $this->occurrenceService->filterValidAttendanceRecords($absensisRaw);

        foreach ($aktivitas as $aktItem) {
            if ($aktItem->jadwal_id !== null && ! $aktItem->relationLoaded('jadwal') && $jadwalById->has($aktItem->jadwal_id)) {
                $aktItem->setRelation('jadwal', $jadwalById->get($aktItem->jadwal_id));
            }
        }

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
        $aktivitasDedup = $aktivitas
            ->filter(fn ($a) => $a->jadwal === null || $a->jadwal->status !== 'dibatalkan')
            ->sortByDesc('id')
            ->unique(fn ($a) => ($a->jadwal_id ?? '0') . '|' . ($a->tanggal?->toDateString() ?? (string) $a->id))
            ->values();

        $jadwalAktif        = $jadwals->where('status', '!=', 'dibatalkan')->values();
        $jadwalKonkret      = $jadwalAktif->where('is_pola_mingguan', false)->values();
        $jadwalPolaMingguan = $jadwalAktif->where('is_pola_mingguan', true)->values();

        $scheduledOccurrences = $this->occurrenceService->expandScheduledOccurrences($jadwalAktif, $dari, $sampai);
        $totalJadwal          = $scheduledOccurrences->count();

        $hadirByJadwalDate = [];
        $nonHadirByJadwalDate = [];
        $nonHadirUmumByDate = [];
        foreach ($absensis as $absRow) {
            $dKey = $absRow->tanggal?->toDateString();
            if (! $dKey) {
                continue;
            }
            if (in_array($absRow->status, ['hadir', 'terlambat'], true)) {
                if ($absRow->jadwal_id !== null) {
                    $hadirByJadwalDate[$absRow->jadwal_id . '|' . $dKey] = true;
                }
            } elseif (in_array($absRow->status, ['izin', 'tidak_hadir'], true)) {
                if ($absRow->jadwal_id !== null) {
                    $nonHadirByJadwalDate[$absRow->jadwal_id . '|' . $dKey] = true;
                } else {
                    $nonHadirUmumByDate[$dKey] = true;
                }
            }
        }

        $dikonfirmasiByJadwalDate = [];
        $tidakTerlaksanaByJadwalDate = [];
        foreach ($aktivitasDedup as $aktRow) {
            $dKey = $aktRow->tanggal?->toDateString();
            if (! $dKey || $aktRow->jadwal_id === null) {
                continue;
            }
            if ($aktRow->status_konfirmasi === 'dikonfirmasi') {
                $dikonfirmasiByJadwalDate[$aktRow->jadwal_id . '|' . $dKey] = true;
            } elseif ($aktRow->status_konfirmasi === 'tidak_terlaksana') {
                $tidakTerlaksanaByJadwalDate[$aktRow->jadwal_id . '|' . $dKey] = true;
            }
        }

        $durasiJamJadwal = static function (Jadwal $j): float {
            $m0 = Carbon::parse(substr((string) $j->jam_mulai, 0, 5));
            $m1 = Carbon::parse(substr((string) $j->jam_selesai, 0, 5));

            return max(0.0, $m0->diffInMinutes($m1, false) / 60.0);
        };

        $jamKonkret = 0.0;
        $konkretSelesaiCount = 0;
        foreach ($jadwalKonkret as $jk) {
            $tglK = $jk->tanggal?->toDateString() ?? '';
            $kKey = $jk->id . '|' . $tglK;
            $isRealized = $jk->status === 'selesai'
                || isset($hadirByJadwalDate[$kKey])
                || isset($dikonfirmasiByJadwalDate[$kKey]);
            if ($isRealized) {
                $konkretSelesaiCount++;
                $jamKonkret += $durasiJamJadwal($jk);
            }
        }

        $elapsedPolaOccurrences = $this->occurrenceService
            ->expandElapsedOccurrences($jadwalPolaMingguan, $dari, $sampai)
            ->filter(function (array $occ) use ($instruktur, $nonHadirByJadwalDate, $nonHadirUmumByDate, $tidakTerlaksanaByJadwalDate): bool {
                /** @var Jadwal $jp */
                $jp   = $occ['jadwal'];
                $tglP = $occ['tanggal'];
                $pKey = $jp->id . '|' . $tglP;

                if ($instruktur->sedangCutiAtauIzinPada($tglP, $jp->hari)) {
                    return false;
                }
                if (isset($nonHadirByJadwalDate[$pKey]) || isset($nonHadirUmumByDate[$tglP]) || isset($tidakTerlaksanaByJadwalDate[$pKey])) {
                    return false;
                }

                return true;
            });

        $polaRealizedDates = [];
        $jamPola = 0.0;
        foreach ($elapsedPolaOccurrences as $occ) {
            /** @var Jadwal $jp */
            $jp   = $occ['jadwal'];
            $pKey = $jp->id . '|' . $occ['tanggal'];
            $polaRealizedDates[$pKey] = true;
            $jamPola += $durasiJamJadwal($jp);
        }

        // Sertakan sesi mingguan hari ini yang sedang berlangsung namun sudah check-in atau dikonfirmasi jurnalnya
        foreach ($jadwalPolaMingguan as $jp) {
            $todayStr = today()->toDateString();
            if ($todayStr >= $dari && $todayStr <= $sampai && $jp->berlakuPadaTanggal($todayStr)) {
                $pKey = $jp->id . '|' . $todayStr;
                if (! isset($polaRealizedDates[$pKey]) && (isset($hadirByJadwalDate[$pKey]) || isset($dikonfirmasiByJadwalDate[$pKey]))) {
                    $polaRealizedDates[$pKey] = true;
                    $jamPola += $durasiJamJadwal($jp);
                }
            }
        }

        $totalJadwalSelesai = $konkretSelesaiCount + count($polaRealizedDates);
        $totalJamMengajar   = round($jamKonkret + $jamPola, 1);

        $aktivitasTerlapor          = $aktivitasDedup->where('status_konfirmasi', '!=', 'belum_dikonfirmasi');
        $totalCatatanAktivitas      = $aktivitasTerlapor->count();
        $totalAktivitasDikonfirmasi = $aktivitasTerlapor->where('status_konfirmasi', 'dikonfirmasi')->count();
        $totalAktivitasDiverifikasi = $aktivitasTerlapor->where('status_verifikasi', 'diverifikasi')->count();
        $totalAktivitasRevisi       = $aktivitasTerlapor->where('status_verifikasi', 'perlu_revisi')->count();

        $elapsedOccurrences = $this->occurrenceService
            ->expandElapsedOccurrences($jadwalAktif, $dari, $sampai)
            ->reject(fn (array $occ) => $instruktur->sedangCutiAtauIzinPada($occ['tanggal'], $occ['jadwal']->hari))
            ->values();
        $missingAktivitasCount = $this->occurrenceService
            ->getMissingActivityOccurrences($jadwalAktif, $aktivitasTerlapor, $dari, $sampai)
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
        $totalEvaluasi   = $evaluasis->count();
        $scoredEvaluasis = $evaluasis->whereNotNull('total_nilai');
        $avgEvaluasi     = $scoredEvaluasis->isNotEmpty() ? $scoredEvaluasis->avg('total_nilai') : null;
        $rataRataNilai   = $avgEvaluasi !== null
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
    protected static ?string $memoContextId = null;

    protected static function ensureFreshRequestContext(): void
    {
        $appObj = app();
        $reqId  = $appObj->bound('request') ? (string) spl_object_id($appObj->make('request')) : 'cli';
        $ctxId  = spl_object_id($appObj) . ':' . $reqId;

        if (static::$memoContextId !== $ctxId) {
            static::$memoContextId   = $ctxId;
            static::$memoEvaluateAll = [];
        }
    }

    /**
     * Evaluasi daftar instruktur sekaligus untuk rentang tanggal tertentu.
     */
    public function evaluateAll(
        string $dari,
        string $sampai,
        ?string $instrukturId = null,
        ?string $statusFilter = null,
        ?Kelas $selectedKelas = null,
        bool $includeHistorical = false
    ): Collection {
        static::ensureFreshRequestContext();

        $kelasKey = $selectedKelas ? (string) $selectedKelas->id : 'all';
        $histKey  = $includeHistorical ? 'hist' : 'aktif';
        $memoKey  = "{$dari}|{$sampai}|" . ($instrukturId ?: 'all') . '|' . ($statusFilter ?: 'all') . "|{$kelasKey}|{$histKey}";
        if (isset(static::$memoEvaluateAll[$memoKey])) {
            return static::$memoEvaluateAll[$memoKey];
        }

        $activeAttendanceStatus = ($statusFilter && in_array($statusFilter, ['hadir', 'terlambat', 'izin', 'tidak_hadir'], true))
            ? $statusFilter
            : null;

        $sampaiEnd = Carbon::parse($sampai)->toDateString() . ' 23:59:59';

        $baseInstrukturQuery = $includeHistorical
            ? Instruktur::withTrashed()->where(function (Builder $q) use ($dari, $sampai, $sampaiEnd, $instrukturId) {
                $q->where(fn (Builder $sub) => $sub->whereNull('deleted_at')->where('status', 'aktif'))
                    ->orWhereHas('jadwals', fn (Builder $jq) => $jq->periode($dari, $sampai))
                    ->orWhereHas('absensis', fn (Builder $aq) => $aq->whereBetween('tanggal', [$dari, $sampaiEnd]))
                    ->orWhereHas('aktivitasMengajars', fn (Builder $kq) => $kq->whereBetween('tanggal', [$dari, $sampaiEnd]))
                    ->orWhereHas('evaluasis', fn (Builder $eq) => $eq->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']))
                    ->orWhereHas('observasiSupervisors', fn (Builder $oq) => $oq->whereBetween('tanggal', [$dari, $sampaiEnd]));
                if ($instrukturId && $instrukturId !== 'all') {
                    $q->orWhere('id', $instrukturId);
                }
            })
            : Instruktur::aktif();

        $hasCutiTbl = Instruktur::hasCutiTable();

        $instrukturList = $baseInstrukturQuery
            ->when($instrukturId && $instrukturId !== 'all', fn ($q) => $q->where('id', $instrukturId))
            ->with(
                $selectedKelas !== null
                    ? ($hasCutiTbl ? ['cutis'] : [])
                    : array_merge(
                        $hasCutiTbl ? ['cutis'] : [],
                        [
                            'absensis'             => fn ($q) => $q->with('jadwal')->whereBetween('tanggal', [$dari, $sampaiEnd]),
                            'evaluasis'            => fn ($q) => $q->whereBetween('created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']),
                            'jadwals'              => fn ($q) => $q->periode($dari, $sampai)
                                ->when($statusFilter && in_array($statusFilter, ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'], true), fn ($sq) => $sq->where('status', $statusFilter)),
                            'aktivitasMengajars'   => fn ($q) => $q->with('jadwal')->whereBetween('tanggal', [$dari, $sampaiEnd]),
                            'observasiSupervisors' => fn ($q) => $q->whereBetween('tanggal', [$dari, $sampaiEnd]),
                        ]
                    )
            )
            ->orderBy('nama_lengkap')
            ->get();

        $rows = $instrukturList
            ->map(fn (Instruktur $inst) => $this->evaluateInstruktur($inst, $dari, $sampai, $selectedKelas));

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
