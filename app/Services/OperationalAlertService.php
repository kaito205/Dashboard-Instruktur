<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\InstrukturSertifikasi;
use App\Models\Jadwal;
use App\Models\NotifikasiTinjauan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OperationalAlertService
{
    /**
     * Memo per-request untuk mencegah eksekusi ulang query peringatan antara Controller dan Header Notification.
     */
    protected static array $memo = [];
    protected static array $latestByPrefix = [];
    protected static ?string $memoContextId = null;

    private readonly JadwalOccurrenceService $occurrenceService;

    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService,
        ?JadwalOccurrenceService $occurrenceService = null
    ) {
        $this->occurrenceService = $occurrenceService ?? new JadwalOccurrenceService();
    }

    public static function clearMemo(): void
    {
        static::$memo = [];
        static::$latestByPrefix = [];
    }

    protected static function ensureFreshRequestContext(): void
    {
        $appObj = app();
        $reqId  = $appObj->bound('request') ? (string) spl_object_id($appObj->make('request')) : 'cli';
        $ctxId  = spl_object_id($appObj) . ':' . $reqId;

        if (static::$memoContextId !== $ctxId) {
            static::$memoContextId = $ctxId;
            static::clearMemo();
        }
    }

    // Mengumpulkan peringatan operasional faktual untuk Dashboard dan Pusat Notifikasi serta menyaring item yang sudah ditinjau.
    public function getSummaryAlerts(
        ?string $dari = null,
        ?string $sampai = null,
        ?string $role = 'admin',
        ?int $userId = null
    ): array {
        static::ensureFreshRequestContext();

        if ($role === 'instruktur') {
            return $this->getInstrukturAlerts($userId ?? auth()->id());
        }

        $prefix         = $role === 'supervisor' ? 'supervisor' : 'admin';
        $resolvedUserId = $userId ?? auth()->id();
        $latestKey      = "{$prefix}|" . ($resolvedUserId ?? 0);

        if ($dari === null && $sampai === null && isset(static::$latestByPrefix[$latestKey])) {
            return static::$latestByPrefix[$latestKey];
        }

        $dari    = $dari ?: now()->startOfMonth()->toDateString();
        $sampai  = $sampai ?: now()->endOfMonth()->toDateString();
        $memoKey = "{$prefix}|" . ($resolvedUserId ?? 0) . "|{$dari}|{$sampai}";

        if (isset(static::$memo[$memoKey])) {
            return static::$memo[$memoKey];
        }

        // 1. Kelas tanpa laporan aktivitas mengajar & Eskalasi > 2x24 jam (48 jam)
        $sekarang      = now();
        $batasEskalasi = $sekarang->copy()->subHours(48);
        $sampaiEnd     = str_contains($sampai, ':') ? $sampai : ($sampai . ' 23:59:59');
        $aktivitasBelumDikonfirmasiRaw = AktivitasMengajar::with(['instruktur.cutis', 'jadwal'])
            ->where('status_konfirmasi', 'belum_dikonfirmasi')
            ->whereBetween('tanggal', [$dari, $sampaiEnd])
            ->where(function ($q) {
                $q->whereNull('jadwal_id')
                    ->orWhereHas('jadwal', fn ($jq) => $jq->where('status', '!=', 'dibatalkan'));
            })
            ->orderBy('tanggal')
            ->get()
            ->filter(function (AktivitasMengajar $akt) use ($sekarang): bool {
                if (! $akt->tanggal) {
                    return false;
                }
                if ($akt->jadwal && ! $akt->jadwal->berlakuPadaTanggal($akt->tanggal)) {
                    return false;
                }
                $jamSelesai = $akt->jadwal?->jam_selesai ? substr((string) $akt->jadwal->jam_selesai, 0, 5) : '17:00';
                $waktuSesi  = Carbon::parse($akt->tanggal->toDateString() . ' ' . $jamSelesai);
                if ($waktuSesi->gte($sekarang)) {
                    return false;
                }
                if ($akt->instruktur?->sedangCutiAtauIzinPada($akt->tanggal->toDateString(), $akt->jadwal?->hari)) {
                    return false;
                }

                return true;
            })
            ->unique(fn (AktivitasMengajar $akt) => ($akt->jadwal_id ?? '0') . '|' . $akt->tanggal->toDateString())
            ->values();

        $seenJadwalTanggal = [];
        $aktivitasBelumDikonfirmasiAll = $aktivitasBelumDikonfirmasiRaw
            ->map(function (AktivitasMengajar $akt) use ($batasEskalasi, $sekarang, &$seenJadwalTanggal) {
                $tglRaw = $akt->tanggal->toDateString();
                if ($akt->jadwal_id !== null) {
                    $seenJadwalTanggal[$akt->jadwal_id . '|' . $tglRaw] = true;
                }

                $jamSelesai = $akt->jadwal?->jam_selesai ? substr((string) $akt->jadwal->jam_selesai, 0, 5) : '17:00';
                $waktuSesi  = Carbon::parse($tglRaw . ' ' . $jamSelesai);
                $isEskalasi = $waktuSesi->lte($batasEskalasi);
                $jamLewat   = max(0, (int) $waktuSesi->diffInHours($sekarang, false));
                $hariLewat  = max(0, (int) $akt->tanggal->copy()->startOfDay()->diffInDays(today(), false));
                $kodeKelas  = $akt->jadwal?->kode_kelas ?: ($akt->jadwal?->nama_kelas ?? '-');

                return [
                    'id'            => $akt->id,
                    'jadwal_id'     => $akt->jadwal_id,
                    'instruktur_id' => $akt->instruktur_id,
                    'alert_key'     => $isEskalasi ? "eskalasi_aktivitas:{$akt->id}" : "aktivitas_belum:{$akt->id}",
                    'instruktur'    => $akt->instruktur?->nama_lengkap ?? '-',
                    'kelas'         => $akt->jadwal?->nama_kelas ?? '-',
                    'kode_kelas'    => $kodeKelas,
                    'materi'        => $akt->materi_disampaikan ?? $akt->jadwal?->materi ?? '-',
                    'tanggal'       => $akt->tanggal->format('d/m/Y'),
                    'tanggal_raw'   => $tglRaw,
                    'jam_lewat'     => $jamLewat,
                    'hari_lewat'    => $hariLewat,
                    'is_eskalasi'   => $isEskalasi,
                    'source'        => 'aktivitas',
                ];
            });

        $jadwalsPeriode = Jadwal::with('instruktur.cutis')
            ->where('status', '!=', 'dibatalkan')
            ->periode($dari, $sampai)
            ->get();

        $aktivitasPeriode = AktivitasMengajar::whereBetween('tanggal', [$dari, $sampaiEnd])
            ->get(['id', 'jadwal_id', 'tanggal', 'status_konfirmasi']);

        $missingAktivitasAlerts = $this->occurrenceService
            ->getMissingActivityOccurrences($jadwalsPeriode, $aktivitasPeriode, $dari, $sampai, $sekarang)
            ->reject(function (array $occ) use ($seenJadwalTanggal): bool {
                /** @var Jadwal $jadwal */
                $jadwal = $occ['jadwal'];
                $tgl    = $occ['tanggal'];

                return isset($seenJadwalTanggal[$jadwal->id . '|' . $tgl]);
            })
            ->map(function (array $occ) use ($batasEskalasi, $sekarang) {
                /** @var Jadwal $jadwal */
                $jadwal     = $occ['jadwal'];
                $tgl        = $occ['tanggal'];
                $waktuSesi  = $occ['waktu_selesai'];
                $isEskalasi = $waktuSesi->lte($batasEskalasi);
                $jamLewat   = max(0, (int) $waktuSesi->diffInHours($sekarang, false));
                $hariLewat  = max(0, (int) Carbon::parse($tgl)->startOfDay()->diffInDays(today(), false));
                $kodeKelas  = $jadwal->kode_kelas ?: ($jadwal->nama_kelas ?? '-');

                return [
                    'id'            => null,
                    'jadwal_id'     => $jadwal->id,
                    'instruktur_id' => $jadwal->instruktur_id,
                    'alert_key'     => $isEskalasi
                        ? "eskalasi_jadwal_tanpa_laporan:{$jadwal->id}:{$tgl}"
                        : "jadwal_tanpa_laporan:{$jadwal->id}:{$tgl}",
                    'instruktur'    => $jadwal->instruktur?->nama_lengkap ?? '-',
                    'kelas'         => $jadwal->nama_kelas ?? '-',
                    'kode_kelas'    => $kodeKelas,
                    'materi'        => $jadwal->materi ?: 'Umum',
                    'tanggal'       => Carbon::parse($tgl)->format('d/m/Y'),
                    'tanggal_raw'   => $tgl,
                    'jam_lewat'     => $jamLewat,
                    'hari_lewat'    => $hariLewat,
                    'is_eskalasi'   => $isEskalasi,
                    'source'        => 'jadwal_terlewat',
                ];
            });

        $aktivitasBelumDikonfirmasiAll = $aktivitasBelumDikonfirmasiAll
            ->concat($missingAktivitasAlerts)
            ->sortByDesc('jam_lewat')
            ->take(25)
            ->values();

        // 2. Sertifikasi instruktur hampir kedaluwarsa (<= 30 hari) atau sudah kedaluwarsa
        if (Schema::hasTable('instruktur_sertifikasis')) {
            $sertifikasiAlertsAll = InstrukturSertifikasi::query()
                ->with('instruktur')
                ->whereNotNull('berlaku_sampai')
                ->whereDate('berlaku_sampai', '<=', today()->addDays(30)->toDateString())
                ->whereHas('instruktur')
                ->orderBy('berlaku_sampai')
                ->get()
                ->map(function (InstrukturSertifikasi $sert) {
                    $sisaHari = (int) today()->diffInDays($sert->berlaku_sampai->copy()->startOfDay(), false);

                    return [
                        'alert_key'          => "sertifikasi:{$sert->instruktur_id}:{$sert->id}:" . $sert->berlaku_sampai->toDateString(),
                        'instruktur_id'      => $sert->instruktur_id,
                        'nama_lengkap'       => $sert->instruktur?->nama_lengkap ?? '-',
                        'kode_instruktur'    => $sert->instruktur?->kode_instruktur ?? '-',
                        'nama_sertifikasi'   => $sert->nama_sertifikasi ?: 'Sertifikasi Kompetensi',
                        'berlaku_sampai'     => $sert->berlaku_sampai->format('d/m/Y'),
                        'sisa_hari'          => $sisaHari,
                        'status_sertifikasi' => $sisaHari < 0 ? 'kedaluwarsa' : 'hampir_habis',
                    ];
                });
        } else {
            $sertifikasiAlertsAll = Instruktur::query()
                ->whereNotNull('sertifikasi_berlaku_sampai')
                ->whereDate('sertifikasi_berlaku_sampai', '<=', today()->addDays(30)->toDateString())
                ->orderBy('sertifikasi_berlaku_sampai')
                ->get()
                ->map(function (Instruktur $ins) {
                    $sisaHari = (int) today()->diffInDays($ins->sertifikasi_berlaku_sampai, false);

                    return [
                        'alert_key'          => "sertifikasi:{$ins->id}:" . $ins->sertifikasi_berlaku_sampai->toDateString(),
                        'instruktur_id'      => $ins->id,
                        'nama_lengkap'       => $ins->nama_lengkap,
                        'kode_instruktur'    => $ins->kode_instruktur,
                        'nama_sertifikasi'   => $ins->nama_sertifikasi ?: 'Sertifikasi Kompetensi',
                        'berlaku_sampai'     => $ins->sertifikasi_berlaku_sampai->format('d/m/Y'),
                        'sisa_hari'          => $sisaHari,
                        'status_sertifikasi' => $sisaHari < 0 ? 'kedaluwarsa' : 'hampir_habis',
                    ];
                });
        }

        // 3. Instruktur di bawah standar batas Cukup / Kategori Kurang
        $ruleConfig       = $this->kinerjaService->getRuleConfig();
        $batasCukupLabel  = KinerjaCalculationService::formatAngka((float) $ruleConfig['batas_cukup']);
        $kinerjaRows      = $this->kinerjaService->evaluateAll($dari, $sampai);
        $periodeBulanKey  = substr($sampai, 0, 7);
        $instrukturBawahStandarAll = $kinerjaRows
            ->filter(fn ($row) => ($row['kategori'] ?? null) === 'kurang')
            ->map(function (array $row) use ($periodeBulanKey) {
                $row['alert_key'] = "kinerja_rendah:{$row['instruktur']->id}:{$periodeBulanKey}";

                return $row;
            })
            ->values();

        // 4. Anomali kehadiran: Lupa check-out pada periode aktif
        $belumCheckoutAll = Absensi::with(['instruktur', 'jadwal'])
            ->whereNotNull('jam_checkin')
            ->whereNull('jam_checkout')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->orderByDesc('tanggal')
            ->limit(15)
            ->get();

        // 5. Pengingat jadwal mengajar besok (H-1)
        $hariBesok = Jadwal::hariDariTanggal(today()->addDay());
        $besokStr  = today()->addDay()->toDateString();
        $jadwalBesokCount = Jadwal::where('status', '!=', 'dibatalkan')
            ->where(function ($q) use ($besokStr, $hariBesok) {
                $q->whereDate('tanggal', $besokStr)
                    ->orWhere(function ($sub) use ($hariBesok, $besokStr) {
                        $sub->where('is_pola_mingguan', true)
                            ->where('hari', $hariBesok)
                            ->where(fn ($p) => $p->whereNull('periode_mulai')->orWhereDate('periode_mulai', '<=', $besokStr))
                            ->where(fn ($p) => $p->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', $besokStr));
                    });
            })
            ->count();

        // Susun item notifikasi granular dengan kunci deterministik agar dapat ditandai selesai per item
        $rawItems = [];

        foreach ($aktivitasBelumDikonfirmasiAll as $akt) {
            $targetUrl = route($prefix . '.kehadiran.index', array_filter([
                'tab'         => 'aktivitas',
                'instruktur'  => $akt['instruktur_id'] ?? null,
                'jadwal_id'   => $akt['jadwal_id'] ?? null,
                'tanggal'     => $akt['tanggal_raw'] ?? null,
                'open_jurnal' => ($prefix === 'admin' && ! empty($akt['jadwal_id'])) ? 1 : null,
                'dari'        => $dari,
                'sampai'      => $sampai,
            ], fn ($v) => $v !== null && $v !== ''));

            $isEskalasi = (bool) ($akt['is_eskalasi'] ?? false);
            $jamLewat   = (int) ($akt['jam_lewat'] ?? 0);
            $hariLewat  = (int) ($akt['hari_lewat'] ?? 0);
            $durasiInfo = $this->formatDurasiTerlambat($jamLewat, $hariLewat);
            $labelKelas = $akt['kode_kelas'] ?? $akt['kelas'] ?? '-';
            $ringkasan  = $durasiInfo !== ''
                ? "{$labelKelas} · {$akt['tanggal']} · {$durasiInfo}"
                : "{$labelKelas} · {$akt['tanggal']}";

            $rawItems[] = [
                'alert_key'     => $akt['alert_key'],
                'instruktur_id' => $akt['instruktur_id'] ?? null,
                'kategori'      => 'Jurnal',
                'level'         => $isEskalasi ? 'critical' : 'warning',
                'judul'         => "{$akt['instruktur']} belum mengisi jurnal",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'waktu'         => $durasiInfo !== '' ? $durasiInfo : $akt['tanggal'],
                'url'           => $targetUrl,
                'cta_label'     => 'Tindak Lanjuti',
            ];
        }

        foreach ($belumCheckoutAll as $abs) {
            $jamMasuk  = $abs->jam_checkin ? substr((string) $abs->jam_checkin, 0, 5) : '-';
            $kelasNama = $abs->jadwal?->kode_kelas ?: ($abs->jadwal?->nama_kelas ?? 'Sesi mengajar');
            $tglStr    = $abs->tanggal ? $abs->tanggal->format('d/m/Y') : '-';
            $ringkasan = "{$kelasNama} · {$tglStr} · Masuk {$jamMasuk} WIB";

            $rawItems[] = [
                'alert_key'     => "belum_checkout:{$abs->id}",
                'instruktur_id' => $abs->instruktur_id,
                'kategori'      => 'Kehadiran',
                'level'         => 'warning',
                'judul'         => ($abs->instruktur?->nama_lengkap ?? '-') . ' belum check-out',
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'waktu'         => $tglStr,
                'url'           => route($prefix . '.kehadiran.index', ['tab' => 'absensi', 'instruktur' => $abs->instruktur_id]),
                'cta_label'     => 'Periksa',
            ];
        }

        foreach ($sertifikasiAlertsAll as $sert) {
            $isExpired = $sert['status_sertifikasi'] === 'kedaluwarsa';
            $sisaHari  = (int) ($sert['sisa_hari'] ?? 0);
            $sisaLabel = $isExpired
                ? 'Terlambat ' . abs($sisaHari) . ' hari'
                : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Sisa ' . $sisaHari . ' hari');
            $ringkasan = "{$sert['nama_sertifikasi']} · Berlaku s.d. {$sert['berlaku_sampai']} · {$sisaLabel}";

            $rawItems[] = [
                'alert_key'     => $sert['alert_key'],
                'instruktur_id' => $sert['instruktur_id'] ?? null,
                'kategori'      => 'Sertifikasi',
                'level'         => $isExpired ? 'critical' : 'warning',
                'judul'         => "{$sert['nama_lengkap']} " . ($isExpired ? 'memiliki sertifikat kedaluwarsa' : 'memiliki sertifikat hampir kedaluwarsa'),
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'waktu'         => $sisaLabel,
                'url'           => $prefix === 'supervisor'
                    ? route('supervisor.instruktur.show', $sert['instruktur_id']) . '#sertifikasi'
                    : route('admin.instruktur.show', $sert['instruktur_id']) . '#sertifikasi',
                'cta_label'     => $prefix === 'supervisor' ? 'Lihat Profil' : 'Perbarui',
            ];
        }

        foreach ($instrukturBawahStandarAll as $low) {
            $skorFmt   = number_format((float) $low['skor_akhir'], 2);
            $ringkasan = "Skor {$skorFmt} · Kategori {$low['kategori_label']} (standar minimal {$batasCukupLabel})";

            $rawItems[] = [
                'alert_key'     => $low['alert_key'],
                'instruktur_id' => $low['instruktur']->id ?? null,
                'kategori'      => 'Kinerja',
                'level'         => 'critical',
                'judul'         => "{$low['instruktur']->nama_lengkap} berada di bawah standar kinerja",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'waktu'         => 'Periode aktif',
                'url'           => route($prefix . '.kinerja.index', ['instruktur' => $low['instruktur']->id]),
                'cta_label'     => 'Tinjau',
            ];
        }

        if ($jadwalBesokCount > 0) {
            $tglBesok  = today()->addDay()->translatedFormat('d M Y');
            $ringkasan = "{$hariBesok}, {$tglBesok} · Pastikan kesiapan instruktur & ruangan";

            $rawItems[] = [
                'alert_key' => "jadwal_besok:{$besokStr}",
                'kategori'  => 'Jadwal',
                'level'     => 'info',
                'judul'     => "{$jadwalBesokCount} jadwal mengajar besok",
                'ringkasan' => $ringkasan,
                'deskripsi' => $ringkasan,
                'waktu'     => 'Pengingat H-1',
                'url'       => route($prefix . '.jadwal.index', [
                    'tampilan'         => 'kalender',
                    'skala_kalender'   => 'harian',
                    'tanggal_kalender' => $besokStr,
                ]),
                'cta_label' => 'Lihat Jadwal',
            ];
        }

        $levelOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $allCurrentItems = collect($rawItems)
            ->sortBy(fn (array $item) => $levelOrder[$item['level']] ?? 9)
            ->values();
        $allCurrentByKey = $allCurrentItems->keyBy('alert_key');

        $reviewedRecords    = $this->getReviewedRecords($resolvedUserId, $prefix);
        $reviewedKeys       = $this->getAllReviewedKeyMap($resolvedUserId, $prefix);
        $totalRiwayatUnique = $reviewedKeys->count();

        $itemsFaktualPerluTinjauan = $allCurrentItems
            ->filter(fn (array $item) => in_array($item['level'] ?? '', ['critical', 'warning'], true))
            ->values();
        $itemsFaktualInfo = $allCurrentItems
            ->filter(fn (array $item) => ($item['level'] ?? '') === 'info')
            ->values();

        $itemsAktif = $allCurrentItems
            ->filter(fn (array $item) => ! $reviewedKeys->has($item['alert_key']))
            ->values();

        $itemsPerluTinjauan = $itemsAktif
            ->filter(fn (array $item) => in_array($item['level'] ?? '', ['critical', 'warning'], true))
            ->values();
        $itemsInfo = $itemsAktif
            ->filter(fn (array $item) => ($item['level'] ?? '') === 'info')
            ->values();

        $itemsRiwayat = $reviewedRecords->map(function (NotifikasiTinjauan $rec) use ($allCurrentByKey, $prefix) {
            $rawRingkasan = $rec->ringkasan ?? '';
            $ringkasan    = $this->normalizeLegacyRingkasan($rawRingkasan);
            $currentUrl   = $allCurrentByKey->get($rec->alert_key)['url'] ?? $rec->url;

            return [
                'alert_key'           => $rec->alert_key,
                'kategori'            => $rec->kategori,
                'level'               => $rec->level,
                'judul'               => $this->normalizeLegacyJudul((string) $rec->judul, $ringkasan),
                'ringkasan'           => $ringkasan,
                'url'                 => $this->normalizeAlertUrlForRole($currentUrl, $prefix),
                'ditinjau_pada'       => $rec->ditinjau_pada,
                'ditinjau_pada_label' => $rec->ditinjau_pada ? $rec->ditinjau_pada->translatedFormat('d M Y, H:i') : '-',
                'masih_aktif'         => $allCurrentByKey->has($rec->alert_key),
            ];
        })->values();

        // Filter daftar per-kategori untuk Pusat Notifikasi & Lonceng Header
        $aktivitasAktif = $aktivitasBelumDikonfirmasiAll
            ->filter(fn (array $akt) => ! $reviewedKeys->has($akt['alert_key']))
            ->values();
        $eskalasiAktif = $aktivitasAktif->where('is_eskalasi', true)->values();
        $sertifikasiAktif = $sertifikasiAlertsAll
            ->filter(fn (array $sert) => ! $reviewedKeys->has($sert['alert_key']))
            ->values();
        $kinerjaRendahAktif = $instrukturBawahStandarAll
            ->filter(fn (array $row) => ! $reviewedKeys->has($row['alert_key']))
            ->values();
        $belumCheckoutAktif = $belumCheckoutAll
            ->filter(fn (Absensi $abs) => ! $reviewedKeys->has("belum_checkout:{$abs->id}"))
            ->values();

        $jadwalBesokReviewed = $reviewedKeys->has("jadwal_besok:{$besokStr}");

        $result = [
            'kelas_tanpa_laporan'            => $aktivitasAktif,
            'eskalasi_2x24_jam'              => $eskalasiAktif,
            'sertifikasi_expired'            => $sertifikasiAktif,
            'instruktur_bawah_standar'       => $kinerjaRendahAktif,
            'belum_checkout'                 => $belumCheckoutAktif,
            'jadwal_besok_count'             => $jadwalBesokReviewed ? 0 : $jadwalBesokCount,
            'hari_besok'                     => $hariBesok,
            'feed'                           => $itemsAktif->all(),
            'items_aktif'                    => $itemsAktif,
            'items_perlu_tinjauan'           => $itemsPerluTinjauan,
            'items_info'                     => $itemsInfo,
            'items_faktual'                  => $allCurrentItems,
            'items_faktual_perlu_tinjauan'   => $itemsFaktualPerluTinjauan,
            'items_faktual_info'             => $itemsFaktualInfo,
            'items_riwayat'                  => $itemsRiwayat,
            'total_peringatan'               => $itemsPerluTinjauan->count(),
            'total_info'                     => $itemsInfo->count(),
            'total_semua_aktif'              => $itemsAktif->count(),
            'total_faktual_peringatan'       => $itemsFaktualPerluTinjauan->count(),
            'total_faktual_info'             => $itemsFaktualInfo->count(),
            'total_faktual_semua'            => $allCurrentItems->count(),
            'total_riwayat'                  => $totalRiwayatUnique,
        ];

        static::$memo[$memoKey] = $result;
        if ($dari === now()->startOfMonth()->toDateString() && $sampai === now()->endOfMonth()->toDateString()) {
            static::$latestByPrefix[$latestKey] = $result;
        }

        return $result;
    }

    public function markReviewed(int $userId, array $item): NotifikasiTinjauan
    {
        $record = NotifikasiTinjauan::updateOrCreate(
            [
                'user_id'   => $userId,
                'alert_key' => $item['alert_key'],
            ],
            [
                'kategori'      => substr((string) ($item['kategori'] ?? 'Notifikasi'), 0, 60),
                'level'         => in_array($item['level'] ?? '', ['critical', 'warning', 'info'], true)
                    ? $item['level']
                    : 'warning',
                'judul'         => substr((string) ($item['judul'] ?? 'Notifikasi operasional'), 0, 200),
                'ringkasan'     => isset($item['ringkasan']) ? substr((string) $item['ringkasan'], 0, 255) : null,
                'url'           => isset($item['url']) ? substr((string) $item['url'], 0, 255) : null,
                'ditinjau_pada' => now(),
            ]
        );

        static::clearMemo();

        return $record;
    }

    public function markAllReviewed(
        int $userId,
        ?string $dari = null,
        ?string $sampai = null,
        string $role = 'admin'
    ): int {
        $alerts     = $this->getSummaryAlerts($dari, $sampai, $role, $userId);
        $activeList = $alerts['items_aktif'] ?? collect();

        if ($activeList->isEmpty()) {
            return 0;
        }

        $now = now();
        foreach ($activeList as $item) {
            NotifikasiTinjauan::updateOrCreate(
                [
                    'user_id'   => $userId,
                    'alert_key' => $item['alert_key'],
                ],
                [
                    'kategori'      => substr((string) $item['kategori'], 0, 60),
                    'level'         => $item['level'],
                    'judul'         => substr((string) $item['judul'], 0, 200),
                    'ringkasan'     => substr((string) ($item['ringkasan'] ?? ''), 0, 255),
                    'url'           => substr((string) ($item['url'] ?? ''), 0, 255),
                    'ditinjau_pada' => $now,
                ]
            );
        }

        static::clearMemo();

        return $activeList->count();
    }

    public function restoreReviewed(int $userId, string $alertKey): bool
    {
        if (! Schema::hasTable('notifikasi_tinjauans')) {
            return false;
        }

        $userIds = $this->resolveSharedOperationalUserIds($userId);

        $deleted = NotifikasiTinjauan::whereIn('user_id', $userIds)
            ->where('alert_key', $alertKey)
            ->delete() > 0;

        static::clearMemo();

        return $deleted;
    }

    private function getInstrukturAlerts(?int $userId): array
    {
        static::ensureFreshRequestContext();

        $memoKey = 'instruktur|' . ($userId ?? 0);
        if (isset(static::$memo[$memoKey])) {
            return static::$memo[$memoKey];
        }

        $emptyResult = [
            'total_peringatan' => 0,
            'total_aktif'      => 0,
            'total_riwayat'    => 0,
            'feed'             => [],
            'items_aktif'      => collect(),
            'items_riwayat'    => collect(),
        ];

        if (! $userId) {
            return static::$memo[$memoKey] = $emptyResult;
        }

        $instruktur = Instruktur::where('user_id', $userId)->first();
        if (! $instruktur) {
            return static::$memo[$memoKey] = $emptyResult;
        }

        $rawItems = [];

        // 1. Sesi yang sudah Check-in namun belum Check-out
        $belumCheckout = Absensi::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->whereNotNull('jam_checkin')
            ->whereNull('jam_checkout')
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        foreach ($belumCheckout as $abs) {
            $kelas     = $abs->jadwal?->nama_kelas ?? 'Sesi Mengajar';
            $tglStr    = $abs->tanggal ? $abs->tanggal->toDateString() : today()->toDateString();
            $ringkasan = "Check-in pukul {$abs->jam_checkin} WIB pada " . ($abs->tanggal?->format('d/m/Y') ?? $tglStr) . ' belum ditutup.';
            $rawItems[] = [
                'alert_key'     => "instruktur_belum_checkout:{$abs->id}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Kehadiran',
                'level'         => 'warning',
                'judul'         => "Belum Check-out - {$kelas}",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.jadwal.index', ['tanggal_sesi' => $tglStr]),
                'cta_label'     => 'Check-out',
                'waktu'         => $abs->tanggal?->format('d/m/Y') ?? $tglStr,
            ];
        }

        // 2. Jurnal Aktivitas yang Perlu Revisi dari Supervisor
        $perluRevisi = AktivitasMengajar::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->where('status_verifikasi', 'perlu_revisi')
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        foreach ($perluRevisi as $akt) {
            $kelas     = $akt->jadwal?->nama_kelas ?? 'Kelas';
            $ringkasan = $akt->catatan_supervisor ?: 'Supervisor meminta perbaikan pada laporan aktivitas mengajar ini.';
            $rawItems[] = [
                'alert_key'     => "instruktur_jurnal_revisi:{$akt->id}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jurnal',
                'level'         => 'critical',
                'judul'         => "Jurnal Perlu Revisi - {$kelas}",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.aktivitas.index') . '#jadwal-' . $akt->jadwal_id,
                'cta_label'     => 'Perbaiki',
                'waktu'         => $akt->tanggal?->format('d/m/Y') ?? '-',
            ];
        }

        // 3. Jurnal Aktivitas Belum Dikonfirmasi (hanya sesi yang sudah lewat jam selesainya)
        $sekarangIns = now();
        $instruktur->loadMissing('cutis');
        $belumJurnal = AktivitasMengajar::with('jadwal')
            ->where('instruktur_id', $instruktur->id)
            ->where('status_konfirmasi', 'belum_dikonfirmasi')
            ->where(function ($q) {
                $q->whereNull('jadwal_id')
                    ->orWhereHas('jadwal', fn ($jq) => $jq->where('status', '!=', 'dibatalkan'));
            })
            ->orderBy('tanggal')
            ->get()
            ->filter(function (AktivitasMengajar $akt) use ($sekarangIns, $instruktur): bool {
                if (! $akt->tanggal) {
                    return false;
                }
                if ($akt->jadwal && ! $akt->jadwal->berlakuPadaTanggal($akt->tanggal)) {
                    return false;
                }
                $jamSelesai = $akt->jadwal?->jam_selesai ? substr((string) $akt->jadwal->jam_selesai, 0, 5) : '17:00';
                $waktuSesi  = Carbon::parse($akt->tanggal->toDateString() . ' ' . $jamSelesai);
                if ($waktuSesi->gte($sekarangIns)) {
                    return false;
                }
                if ($instruktur->sedangCutiAtauIzinPada($akt->tanggal->toDateString(), $akt->jadwal?->hari)) {
                    return false;
                }

                return true;
            })
            ->unique(fn (AktivitasMengajar $akt) => ($akt->jadwal_id ?? '0') . '|' . $akt->tanggal->toDateString())
            ->take(10)
            ->values();

        $seenInsJadwalTanggal = [];
        foreach ($belumJurnal as $akt) {
            if ($akt->jadwal_id && $akt->tanggal) {
                $seenInsJadwalTanggal[$akt->jadwal_id . '|' . $akt->tanggal->toDateString()] = true;
            }
            $kelas     = $akt->jadwal?->nama_kelas ?? 'Kelas';
            $ringkasan = 'Segera lengkapi laporan materi dan kehadiran peserta untuk sesi ini.';
            $rawItems[] = [
                'alert_key'     => "instruktur_jurnal_belum:{$akt->id}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jurnal',
                'level'         => 'warning',
                'judul'         => "Belum Isi Jurnal - {$kelas}",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.aktivitas.index', array_filter([
                    'jadwal_id' => $akt->jadwal_id,
                    'tanggal'   => $akt->tanggal?->toDateString(),
                ])),
                'cta_label'     => 'Isi Jurnal',
                'waktu'         => $akt->tanggal?->format('d/m/Y') ?? '-',
            ];
        }

        $awalBulanIns  = $sekarangIns->copy()->startOfMonth()->toDateString();
        $akhirBulanIns = $sekarangIns->copy()->endOfMonth()->toDateString();
        $jadwalsInsBulan = Jadwal::where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->periode($awalBulanIns, $akhirBulanIns)
            ->get();
        $jadwalsInsBulan->each(fn (Jadwal $j) => $j->setRelation('instruktur', $instruktur));

        $aktInsBulan = AktivitasMengajar::where('instruktur_id', $instruktur->id)
            ->whereBetween('tanggal', [$awalBulanIns, $akhirBulanIns . ' 23:59:59'])
            ->get(['id', 'jadwal_id', 'tanggal', 'status_konfirmasi']);

        $missingInsOccurrences = $this->occurrenceService
            ->getMissingActivityOccurrences($jadwalsInsBulan, $aktInsBulan, $awalBulanIns, $akhirBulanIns, $sekarangIns)
            ->reject(fn (array $occ) => isset($seenInsJadwalTanggal[$occ['jadwal']->id . '|' . $occ['tanggal']]))
            ->take(max(0, 10 - count($seenInsJadwalTanggal)));

        foreach ($missingInsOccurrences as $occ) {
            /** @var Jadwal $jMiss */
            $jMiss     = $occ['jadwal'];
            $tglMiss   = $occ['tanggal'];
            $kelas     = $jMiss->nama_kelas ?? 'Kelas';
            $ringkasan = 'Segera lengkapi laporan materi dan kehadiran peserta untuk sesi ini.';
            $rawItems[] = [
                'alert_key'     => "instruktur_jadwal_tanpa_jurnal:{$jMiss->id}:{$tglMiss}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jurnal',
                'level'         => 'warning',
                'judul'         => "Belum Isi Jurnal - {$kelas}",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.aktivitas.index', ['jadwal_id' => $jMiss->id, 'tanggal' => $tglMiss]),
                'cta_label'     => 'Isi Jurnal',
                'waktu'         => Carbon::parse($tglMiss)->format('d/m/Y'),
            ];
        }

        // 4. Pengingat Jadwal Mengajar Hari Ini
        $jadwalHariIni = Jadwal::hariIni()
            ->where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->orderBy('jam_mulai')
            ->get();

        if ($jadwalHariIni->isNotEmpty()) {
            $jamPertama = substr((string) $jadwalHariIni->first()->jam_mulai, 0, 5);
            $ringkasan  = "Sesi pertama dimulai pukul {$jamPertama} WIB. Jangan lupa Check-in tepat waktu.";
            $rawItems[] = [
                'alert_key'     => 'instruktur_jadwal_hari_ini:' . today()->toDateString(),
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jadwal',
                'level'         => 'info',
                'judul'         => "Jadwal Hari Ini ({$jadwalHariIni->count()} Sesi)",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.jadwal.index'),
                'cta_label'     => 'Lihat Jadwal',
                'waktu'         => today()->format('d/m/Y'),
            ];
        }

        // 4b. Pengingat Jadwal Mengajar Besok (H-1)
        $besokStr  = today()->addDay()->toDateString();
        $hariBesok = Jadwal::hariDariTanggal($besokStr);
        $jadwalBesokCount = Jadwal::where('instruktur_id', $instruktur->id)
            ->where('status', '!=', 'dibatalkan')
            ->where(function ($q) use ($besokStr, $hariBesok) {
                $q->where(function ($sub) use ($besokStr) {
                    $sub->where('is_pola_mingguan', false)->whereDate('tanggal', $besokStr);
                })->orWhere(function ($sub) use ($besokStr, $hariBesok) {
                    $sub->where('is_pola_mingguan', true)
                        ->where('hari', $hariBesok)
                        ->where(fn ($p) => $p->whereNull('periode_mulai')->orWhereDate('periode_mulai', '<=', $besokStr))
                        ->where(fn ($p) => $p->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', $besokStr));
                });
            })
            ->count();

        if ($jadwalBesokCount > 0) {
            $ringkasan  = today()->addDay()->translatedFormat('d M Y') . ' - Persiapkan materi dan kehadiran tepat waktu.';
            $rawItems[] = [
                'alert_key'     => "instruktur_jadwal_besok:{$besokStr}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jadwal',
                'level'         => 'info',
                'judul'         => "Jadwal Besok ({$jadwalBesokCount} Sesi)",
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.jadwal.index', ['tanggal_sesi' => $besokStr]),
                'cta_label'     => 'Cek Jadwal',
                'waktu'         => today()->addDay()->format('d/m/Y'),
            ];
        }

        // 4c. Perubahan / Pembatalan Jadwal pada Bulan Berjalan
        $awalBulan  = now()->startOfMonth()->toDateString();
        $akhirBulan = now()->endOfMonth()->toDateString();
        $perubahanJadwal = Jadwal::where(function ($q) use ($instruktur) {
                $q->where('instruktur_id', $instruktur->id)
                    ->orWhere('instruktur_asli_id', $instruktur->id);
            })
            ->where(function ($q) {
                $q->where('status', 'dibatalkan')
                    ->orWhereNotNull('instruktur_pengganti_id');
            })
            ->where(function ($q) use ($awalBulan, $akhirBulan) {
                $q->whereBetween('tanggal', [$awalBulan, $akhirBulan . ' 23:59:59'])
                    ->orWhere('is_pola_mingguan', true);
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        foreach ($perubahanJadwal as $pj) {
            $isBatal   = $pj->status === 'dibatalkan';
            $judul     = $isBatal ? "Jadwal Dibatalkan - {$pj->nama_kelas}" : "Pengalihan Jadwal - {$pj->nama_kelas}";
            $ringkasan = $isBatal
                ? ($pj->keterangan ?: 'Sesi ini dibatalkan oleh Admin.')
                : ($pj->alasan_pengalihan ?: 'Jadwal dialihkan ke instruktur pengganti.');
            $rawItems[] = [
                'alert_key'     => "instruktur_perubahan_jadwal:{$pj->id}",
                'instruktur_id' => $instruktur->id,
                'kategori'      => 'Jadwal',
                'level'         => $isBatal ? 'warning' : 'info',
                'judul'         => $judul,
                'ringkasan'     => $ringkasan,
                'deskripsi'     => $ringkasan,
                'url'           => route('instruktur.jadwal.index'),
                'cta_label'     => 'Lihat Jadwal',
                'waktu'         => $pj->tanggal?->format('d/m/Y') ?? ($pj->hari ?: '-'),
            ];
        }

        // 5. Sertifikasi Pribadi Kedaluwarsa / Hampir Kedaluwarsa (<= 30 hari)
        if (Schema::hasTable('instruktur_sertifikasis')) {
            $batas = now()->addDays(30)->toDateString();
            $certs = InstrukturSertifikasi::where('instruktur_id', $instruktur->id)
                ->whereNotNull('berlaku_sampai')
                ->whereDate('berlaku_sampai', '<=', $batas)
                ->orderBy('berlaku_sampai')
                ->limit(10)
                ->get();

            foreach ($certs as $cert) {
                $isExpired = $cert->berlaku_sampai->lt(today());
                $ringkasan = 'Berlaku hingga ' . $cert->berlaku_sampai->format('d/m/Y') . '. Hubungi Admin untuk pembaruan.';
                $rawItems[] = [
                    'alert_key'     => "instruktur_sertifikasi:{$cert->id}",
                    'instruktur_id' => $instruktur->id,
                    'kategori'      => 'Sertifikasi',
                    'level'         => $isExpired ? 'critical' : 'warning',
                    'judul'         => ($isExpired ? 'Sertifikat Kedaluwarsa' : 'Sertifikat Hampir Habis') . ' - ' . $cert->nama_sertifikasi,
                    'ringkasan'     => $ringkasan,
                    'deskripsi'     => $ringkasan,
                    'url'           => route('instruktur.profil.index'),
                    'cta_label'     => 'Lihat Profil',
                    'waktu'         => $cert->berlaku_sampai->format('d/m/Y'),
                ];
            }
        }

        $reviewedRecords    = $this->getReviewedRecords($userId, 'instruktur');
        $reviewedKeyMap     = $this->getAllReviewedKeyMap($userId, 'instruktur');

        $itemsAktif = collect($rawItems)
            ->reject(fn (array $item) => $reviewedKeyMap->has($item['alert_key']))
            ->values();

        $itemsRiwayat = $reviewedRecords->map(function (NotifikasiTinjauan $rec) {
            return [
                'alert_key'     => $rec->alert_key,
                'kategori'      => $rec->kategori,
                'level'         => $rec->level,
                'judul'         => $rec->judul,
                'ringkasan'     => $rec->ringkasan,
                'url'           => $rec->url ?: route('instruktur.dashboard'),
                'ditinjau_pada' => $rec->ditinjau_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
            ];
        })->values();

        $itemsPerluTinjauanIns = $itemsAktif
            ->filter(fn (array $item) => in_array($item['level'] ?? '', ['critical', 'warning'], true))
            ->values();
        $itemsInfoIns = $itemsAktif
            ->filter(fn (array $item) => ($item['level'] ?? '') === 'info')
            ->values();

        $result = [
            'total_peringatan'  => $itemsPerluTinjauanIns->count(),
            'total_info'        => $itemsInfoIns->count(),
            'total_semua_aktif' => $itemsAktif->count(),
            'total_aktif'       => $itemsAktif->count(),
            'total_riwayat'     => $reviewedKeyMap->count(),
            'feed'              => $itemsAktif->take(10)->values()->all(),
            'items_aktif'       => $itemsAktif,
            'items_riwayat'     => $itemsRiwayat,
        ];

        return static::$memo[$memoKey] = $result;
    }

    private function formatDurasiTerlambat(int $jamLewat, int $hariLewat = 0): string
    {
        if ($jamLewat <= 0 && $hariLewat <= 0) {
            return '';
        }

        if ($jamLewat >= 24) {
            $hari = max($hariLewat, intdiv($jamLewat, 24));
            return "Terlambat {$hari} hari";
        }

        return "Terlambat {$jamLewat} jam";
    }

    private function normalizeLegacyRingkasan(string $ringkasan): string
    {
        $clean = trim($ringkasan);
        if ($clean === '') {
            return '';
        }

        if (preg_match('/^(.*?)\s*\(Eskalasi\s*>\s*2x24\s*jam\s*·\s*lewat\s*(\d+)\s*jam\)$/i', $clean, $m)) {
            $base       = trim($m[1]);
            $jamLewat   = (int) $m[2];
            $durasiInfo = $this->formatDurasiTerlambat($jamLewat);

            return $durasiInfo !== '' ? "{$base} · {$durasiInfo}" : $base;
        }

        return $clean;
    }

    private function normalizeLegacyJudul(string $judul, string $ringkasan): string
    {
        $clean = trim($judul);

        if (preg_match('/^(.+?)\s*-\s*(Eskalasi jurnal\s*>\s*2x24\s*jam|Belum isi jurnal)$/i', $clean, $m)) {
            return trim($m[1]) . ' belum mengisi jurnal';
        }
        if (preg_match('/^(.+?)\s*-\s*Belum check-out$/i', $clean, $m)) {
            return trim($m[1]) . ' belum check-out';
        }
        if (preg_match('/^(.+?)\s*-\s*Sertifikat kedaluwarsa$/i', $clean, $m)) {
            return trim($m[1]) . ' memiliki sertifikat kedaluwarsa';
        }
        if (preg_match('/^(.+?)\s*-\s*Sertifikat hampir habis$/i', $clean, $m)) {
            return trim($m[1]) . ' memiliki sertifikat hampir kedaluwarsa';
        }
        if (preg_match('/^(.+?)\s*-\s*Skor kinerja rendah/i', $clean, $m)) {
            return trim($m[1]) . ' berada di bawah standar kinerja';
        }

        if (! str_contains($clean, ': ')) {
            return $clean;
        }

        [$prefix, $nama] = explode(': ', $clean, 2);
        $prefix = trim($prefix);
        $nama   = trim($nama);

        return match ($prefix) {
            'Jurnal > 48 jam', 'Belum isi jurnal' => "{$nama} belum mengisi jurnal",
            'Belum check-out'                     => "{$nama} belum check-out",
            'Sertifikat kedaluwarsa'              => "{$nama} memiliki sertifikat kedaluwarsa",
            'Sertifikat hampir habis'             => "{$nama} memiliki sertifikat hampir kedaluwarsa",
            'Skor kinerja rendah'                 => "{$nama} berada di bawah standar kinerja",
            default                               => $clean,
        };
    }

    private function normalizeAlertUrlForRole(?string $url, string $prefix): ?string
    {
        if ($url === null || trim($url) === '') {
            return $url;
        }

        $otherPrefix = $prefix === 'supervisor' ? 'admin' : 'supervisor';
        $normalized  = preg_replace('#/' . $otherPrefix . '(/|$|\?)#', '/' . $prefix . '$1', $url) ?? $url;

        if ($prefix === 'supervisor') {
            $normalized = preg_replace('/([?&])open_jurnal=[^&#]*(&|$)/', '$1', $normalized) ?? $normalized;
            $normalized = str_replace(['?&', '&&'], ['?', '&'], $normalized);
            $normalized = rtrim($normalized, '?&');
        }

        return $normalized;
    }

    private function resolveSharedOperationalUserIds(?int $userId, ?string $role = null): array
    {
        if ($role === 'instruktur') {
            return $userId ? [$userId] : [];
        }

        if ($role === null && $userId) {
            $userRole = User::whereKey($userId)->value('role');
            if ($userRole === 'instruktur') {
                return [$userId];
            }
        }

        $sharedIds = User::whereIn('role', ['admin', 'supervisor'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($userId && ! in_array($userId, $sharedIds, true)) {
            $sharedIds[] = $userId;
        }

        return $sharedIds;
    }

    private function getAllReviewedKeyMap(?int $userId, ?string $role = null): Collection
    {
        if (! $userId || ! Schema::hasTable('notifikasi_tinjauans')) {
            return collect();
        }

        $userIds = $this->resolveSharedOperationalUserIds($userId, $role);
        if (empty($userIds)) {
            return collect();
        }

        return NotifikasiTinjauan::whereIn('user_id', $userIds)
            ->distinct()
            ->pluck('alert_key')
            ->flip();
    }

    private function getReviewedRecords(?int $userId, ?string $role = null): Collection
    {
        if (! $userId || ! Schema::hasTable('notifikasi_tinjauans')) {
            return collect();
        }

        $userIds = $this->resolveSharedOperationalUserIds($userId, $role);
        if (empty($userIds)) {
            return collect();
        }

        return NotifikasiTinjauan::whereIn('user_id', $userIds)
            ->orderByDesc('ditinjau_pada')
            ->limit(500)
            ->get()
            ->unique('alert_key')
            ->values();
    }
}
