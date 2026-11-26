<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JadwalOccurrenceService
{
    /**
     * Saring catatan absensi agar hanya menyertakan presensi yang valid terhadap hari/tanggal jadwalnya
     * serta bebas duplikasi untuk pasangan (instruktur_id, jadwal_id, tanggal) yang sama.
     */
    public function filterValidAttendanceRecords(Collection $absensis): Collection
    {
        $valid = $absensis->filter(function ($abs): bool {
            if (! $abs instanceof Absensi) {
                return false;
            }
            if ($abs->jadwal_id !== null && $abs->jadwal !== null && $abs->tanggal !== null) {
                return $abs->jadwal->berlakuPadaTanggal($abs->tanggal);
            }

            return true;
        });

        $deduped = collect();
        $seen    = [];

        foreach ($valid->sortByDesc('id') as $abs) {
            if ($abs->jadwal_id !== null && $abs->tanggal !== null) {
                $key = $abs->instruktur_id . '|' . $abs->jadwal_id . '|' . $abs->tanggal->toDateString();
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
            }
            $deduped->push($abs);
        }

        return $deduped->sortBy('tanggal')->values();
    }

    /**
     * Uraikan seluruh sesi jadwal (spesifik maupun pola mingguan) yang jam selesainya
     * sudah terlewati hingga $now dalam rentang [$dari, $sampai].
     */
    public function expandElapsedOccurrences(
        Collection $jadwals,
        string $dari,
        string $sampai,
        ?Carbon $now = null
    ): Collection {
        $now = ($now ?? now())->copy();
        $todayStr = $now->toDateString();
        $effectiveEnd = $sampai < $todayStr ? $sampai : $todayStr;

        if ($effectiveEnd < $dari || $jadwals->isEmpty()) {
            return collect();
        }

        $occurrences = collect();

        foreach ($jadwals as $jadwal) {
            if (! $jadwal instanceof Jadwal || $jadwal->status === 'dibatalkan') {
                continue;
            }

            $jamSelesai = $jadwal->jam_selesai ? substr((string) $jadwal->jam_selesai, 0, 5) : '23:59';

            if (! $jadwal->is_pola_mingguan) {
                if (! $jadwal->tanggal) {
                    continue;
                }
                $tgl = $jadwal->tanggal->toDateString();
                if ($tgl < $dari || $tgl > $effectiveEnd) {
                    continue;
                }

                $waktuSelesai = Carbon::parse($tgl . ' ' . $jamSelesai);
                if ($waktuSelesai->lt($now)) {
                    $occurrences->push([
                        'jadwal'        => $jadwal,
                        'tanggal'       => $tgl,
                        'waktu_selesai' => $waktuSelesai,
                    ]);
                }
                continue;
            }

            if (! $jadwal->hari) {
                continue;
            }

            $rangeStart = $dari;
            if ($jadwal->periode_mulai && $jadwal->periode_mulai->toDateString() > $rangeStart) {
                $rangeStart = $jadwal->periode_mulai->toDateString();
            }

            $rangeEnd = $effectiveEnd;
            if ($jadwal->periode_selesai && $jadwal->periode_selesai->toDateString() < $rangeEnd) {
                $rangeEnd = $jadwal->periode_selesai->toDateString();
            }

            if ($rangeEnd < $rangeStart) {
                continue;
            }

            $cursor = Carbon::parse($rangeStart)->startOfDay();
            $endCursor = Carbon::parse($rangeEnd)->startOfDay();
            $foundFirst = false;

            for ($i = 0; $i < 7 && $cursor->lte($endCursor); $i++) {
                if (Jadwal::hariDariTanggal($cursor) === $jadwal->hari) {
                    $foundFirst = true;
                    break;
                }
                $cursor->addDay();
            }

            if (! $foundFirst) {
                continue;
            }

            while ($cursor->lte($endCursor)) {
                $tgl = $cursor->toDateString();
                $waktuSelesai = Carbon::parse($tgl . ' ' . $jamSelesai);
                if ($waktuSelesai->lt($now)) {
                    $occurrences->push([
                        'jadwal'        => $jadwal,
                        'tanggal'       => $tgl,
                        'waktu_selesai' => $waktuSelesai,
                    ]);
                }
                $cursor->addDays(7);
            }
        }

            return $occurrences->sortByDesc(fn (array $item) => $item['waktu_selesai']->timestamp)->values();
    }

    /**
     * Uraikan seluruh pertemuan terjadwal (spesifik maupun pola mingguan) dalam rentang [$dari, $sampai]
     * yang tidak dibatalkan dan tidak jatuh pada masa cuti/izin instruktur.
     */
    public function expandScheduledOccurrences(
        Collection $jadwals,
        string $dari,
        string $sampai
    ): Collection {
        if ($sampai < $dari || $jadwals->isEmpty()) {
            return collect();
        }

        $occurrences = collect();

        foreach ($jadwals as $jadwal) {
            if (! $jadwal instanceof Jadwal || $jadwal->status === 'dibatalkan') {
                continue;
            }

            if (! $jadwal->is_pola_mingguan) {
                if (! $jadwal->tanggal) {
                    continue;
                }
                $tgl = $jadwal->tanggal->toDateString();
                if ($tgl < $dari || $tgl > $sampai) {
                    continue;
                }
                if ($jadwal->instruktur?->sedangCutiAtauIzinPada($tgl, $jadwal->hari)) {
                    continue;
                }
                $occurrences->push([
                    'jadwal'  => $jadwal,
                    'tanggal' => $tgl,
                ]);
                continue;
            }

            if (! $jadwal->hari) {
                continue;
            }

            $rangeStart = $dari;
            if ($jadwal->periode_mulai && $jadwal->periode_mulai->toDateString() > $rangeStart) {
                $rangeStart = $jadwal->periode_mulai->toDateString();
            }

            $rangeEnd = $sampai;
            if ($jadwal->periode_selesai && $jadwal->periode_selesai->toDateString() < $rangeEnd) {
                $rangeEnd = $jadwal->periode_selesai->toDateString();
            }

            if ($rangeEnd < $rangeStart) {
                continue;
            }

            $cursor     = Carbon::parse($rangeStart)->startOfDay();
            $endCursor  = Carbon::parse($rangeEnd)->startOfDay();
            $foundFirst = false;

            for ($i = 0; $i < 7 && $cursor->lte($endCursor); $i++) {
                if (Jadwal::hariDariTanggal($cursor) === $jadwal->hari) {
                    $foundFirst = true;
                    break;
                }
                $cursor->addDay();
            }

            if (! $foundFirst) {
                continue;
            }

            while ($cursor->lte($endCursor)) {
                $tgl = $cursor->toDateString();
                if (! $jadwal->instruktur?->sedangCutiAtauIzinPada($tgl, $jadwal->hari)) {
                    $occurrences->push([
                        'jadwal'  => $jadwal,
                        'tanggal' => $tgl,
                    ]);
                }
                $cursor->addDays(7);
            }
        }

        return $occurrences->values();
    }

    /**
     * Dapatkan daftar sesi jadwal yang sudah lewat jam selesai namun belum memiliki
     * catatan presensi sama sekali (dan instruktur tidak sedang cuti/izin).
     */
    public function getMissedAttendanceOccurrences(
        Collection $jadwals,
        Collection $absensis,
        string $dari,
        string $sampai,
        ?Carbon $now = null
    ): Collection {
        $elapsed = $this->expandElapsedOccurrences($jadwals, $dari, $sampai, $now);
        if ($elapsed->isEmpty()) {
            return collect();
        }

        $byJadwalTanggal = [];
        $byInstrukturUmum = [];

        foreach ($absensis as $abs) {
            if (! $abs->tanggal) {
                continue;
            }
            $tgl = $abs->tanggal->toDateString();
            if ($abs->jadwal_id !== null) {
                $byJadwalTanggal[$abs->jadwal_id . '|' . $tgl] = true;
            } else {
                $byInstrukturUmum[$abs->instruktur_id . '|' . $tgl] = true;
            }
        }

        return $elapsed->filter(function (array $item) use ($byJadwalTanggal, $byInstrukturUmum): bool {
            /** @var Jadwal $jadwal */
            $jadwal = $item['jadwal'];
            $tgl    = $item['tanggal'];

            if (isset($byJadwalTanggal[$jadwal->id . '|' . $tgl])) {
                return false;
            }
            if (isset($byInstrukturUmum[$jadwal->instruktur_id . '|' . $tgl])) {
                return false;
            }
            if ($jadwal->instruktur?->sedangCutiAtauIzinPada($tgl, $jadwal->hari)) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Dapatkan daftar sesi jadwal yang sudah lewat jam selesai namun belum memiliki
     * baris laporan aktivitas mengajar sama sekali (dan instruktur tidak sedang cuti/izin).
     */
    public function getMissingActivityOccurrences(
        Collection $jadwals,
        Collection $aktivitas,
        string $dari,
        string $sampai,
        ?Carbon $now = null
    ): Collection {
        $elapsed = $this->expandElapsedOccurrences($jadwals, $dari, $sampai, $now);
        if ($elapsed->isEmpty()) {
            return collect();
        }

        $byJadwalTanggal = [];
        foreach ($aktivitas as $akt) {
            if ($akt->jadwal_id !== null && $akt->tanggal && ($akt->status_konfirmasi ?? 'belum_dikonfirmasi') !== 'belum_dikonfirmasi') {
                $byJadwalTanggal[$akt->jadwal_id . '|' . $akt->tanggal->toDateString()] = true;
            }
        }

        return $elapsed->filter(function (array $item) use ($byJadwalTanggal): bool {
            /** @var Jadwal $jadwal */
            $jadwal = $item['jadwal'];
            $tgl    = $item['tanggal'];

            if (isset($byJadwalTanggal[$jadwal->id . '|' . $tgl])) {
                return false;
            }
            if ($jadwal->instruktur?->sedangCutiAtauIzinPada($tgl, $jadwal->hari)) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Tentukan status sesi real-time untuk tampilan Instruktur (Dashboard & Halaman Jadwal).
     */
    public function resolveLiveSessionStatus(
        Jadwal $jadwal,
        string $tanggalSesi,
        ?Absensi $absensi,
        ?Carbon $now = null
    ): array {
        if ($jadwal->status === 'dibatalkan') {
            return [
                'label' => 'Dibatalkan',
                'color' => 'red',
                'code'  => 'dibatalkan',
            ];
        }

        if ($absensi !== null) {
            if ($absensi->jam_checkin && ! $absensi->jam_checkout) {
                return [
                    'label' => 'Check-in Aktif',
                    'color' => 'blue',
                    'code'  => 'checkin_aktif',
                ];
            }
            if ($absensi->jam_checkin && $absensi->jam_checkout) {
                return [
                    'label' => 'Selesai',
                    'color' => 'emerald',
                    'code'  => 'selesai',
                ];
            }

            return [
                'label' => $absensi->status_label,
                'color' => $absensi->status === 'tidak_hadir' ? 'red' : 'amber',
                'code'  => (string) $absensi->status,
            ];
        }

        if (! $jadwal->berlakuPadaTanggal($tanggalSesi)) {
            return [
                'label' => 'Tidak Ada Sesi Hari Ini',
                'color' => 'gray',
                'code'  => 'bukan_jadwal_hari_ini',
            ];
        }

        if ($jadwal->instruktur?->sedangCutiAtauIzinPada($tanggalSesi, $jadwal->hari)) {
            return [
                'label' => 'Cuti / Izin',
                'color' => 'blue',
                'code'  => 'cuti_izin',
            ];
        }

        $now = $now ?? now();
        $jamMulai   = $jadwal->jam_mulai ? substr((string) $jadwal->jam_mulai, 0, 5) : '00:00';
        $jamSelesai = $jadwal->jam_selesai ? substr((string) $jadwal->jam_selesai, 0, 5) : '23:59';

        $waktuMulai   = Carbon::parse($tanggalSesi . ' ' . $jamMulai);
        $waktuSelesai = Carbon::parse($tanggalSesi . ' ' . $jamSelesai);

        if ($now->gt($waktuSelesai)) {
            return [
                'label' => 'Terlewat Tanpa Presensi',
                'color' => 'red',
                'code'  => 'terlewat',
            ];
        }

        if ($now->between($waktuMulai, $waktuSelesai)) {
            return [
                'label' => 'Sedang Berlangsung',
                'color' => 'amber',
                'code'  => 'berlangsung_belum_checkin',
            ];
        }

        return [
            'label' => 'Belum Hadir',
            'color' => 'amber',
            'code'  => 'belum_checkin',
        ];
    }

    /**
     * Validasi kelayakan Check-in dan Check-out untuk sebuah sesi pada tanggal tertentu.
     */
    public function resolveCheckinEligibility(
        Jadwal $jadwal,
        string $tanggalSesi,
        ?Absensi $absensi,
        int $bukaSebelumMenit = 30,
        ?Carbon $now = null
    ): array {
        $now      = ($now ?? now())->copy();
        $todayStr = $now->toDateString();

        if ($jadwal->status === 'dibatalkan') {
            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => 'Jadwal dibatalkan.',
                'code'         => 'dibatalkan',
            ];
        }

        if ($absensi !== null) {
            if ($absensi->jam_checkin && ! $absensi->jam_checkout) {
                return [
                    'can_checkin'  => false,
                    'can_checkout' => true,
                    'reason'       => null,
                    'code'         => 'checkin_aktif',
                ];
            }

            if ($absensi->jam_checkin && $absensi->jam_checkout) {
                return [
                    'can_checkin'  => false,
                    'can_checkout' => false,
                    'reason'       => 'Sesi sudah selesai.',
                    'code'         => 'selesai',
                ];
            }

            if (in_array($absensi->status, ['izin', 'sakit', 'tidak_hadir', 'alpha'], true)) {
                return [
                    'can_checkin'  => false,
                    'can_checkout' => false,
                    'reason'       => 'Presensi sudah tercatat (' . $absensi->status_label . ').',
                    'code'         => 'sudah_tercatat',
                ];
            }
        }

        if (! $jadwal->berlakuPadaTanggal($tanggalSesi)) {
            $hariTarget = Jadwal::hariDariTanggal($tanggalSesi);
            $infoJadwal = $jadwal->is_pola_mingguan
                ? "hari {$jadwal->hari}"
                : ('tanggal ' . ($jadwal->tanggal?->format('d/m/Y') ?? '-'));

            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => "Tidak sesuai jadwal ({$infoJadwal}, dipilih {$hariTarget}).",
                'code'         => 'tidak_sesuai_hari',
            ];
        }

        if ($tanggalSesi > $todayStr) {
            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => 'Check-in hanya tersedia pada hari jadwal berlangsung.',
                'code'         => 'belum_hari_jadwal',
            ];
        }

        if ($tanggalSesi < $todayStr) {
            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => 'Tanggal sesi sudah terlewat.',
                'code'         => 'tanggal_terlewat',
            ];
        }

        $jamMulai   = $jadwal->jam_mulai ? substr((string) $jadwal->jam_mulai, 0, 5) : '00:00';
        $jamSelesai = $jadwal->jam_selesai ? substr((string) $jadwal->jam_selesai, 0, 5) : '23:59';

        $waktuMulai   = Carbon::parse($tanggalSesi . ' ' . $jamMulai);
        $waktuSelesai = Carbon::parse($tanggalSesi . ' ' . $jamSelesai);
        $waktuBuka    = $waktuMulai->copy()->subMinutes(max(0, $bukaSebelumMenit));

        if ($now->lt($waktuBuka)) {
            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => "Dibuka pukul {$waktuBuka->format('H:i')} WIB.",
                'code'         => 'belum_dibuka',
            ];
        }

        if ($now->gt($waktuSelesai)) {
            return [
                'can_checkin'  => false,
                'can_checkout' => false,
                'reason'       => "Sesi berakhir pukul {$waktuSelesai->format('H:i')} WIB.",
                'code'         => 'sesi_berakhir',
            ];
        }

        return [
            'can_checkin'  => true,
            'can_checkout' => false,
            'reason'       => null,
            'code'         => 'dibuka',
        ];
    }
}
