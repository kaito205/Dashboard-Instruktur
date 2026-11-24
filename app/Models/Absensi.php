<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $fillable = [
        'instruktur_id',
        'jadwal_id',
        'tanggal',
        'jam_mulai_jadwal',
        'jam_checkin',
        'jam_checkout',
        'menit_terlambat',
        'checkin_at',
        'checkout_at',
        'status',
        'sumber',
        'latitude_checkin',
        'longitude_checkin',
        'latitude_checkout',
        'longitude_checkout',
        'akurasi_gps',
        'status_gps',
        'is_auto_checkout',
        'keterangan',
        'batch_import',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'            => 'date',
            'checkin_at'         => 'datetime',
            'checkout_at'        => 'datetime',
            'menit_terlambat'    => 'integer',
            'latitude_checkin'   => 'float',
            'longitude_checkin'  => 'float',
            'latitude_checkout'  => 'float',
            'longitude_checkout' => 'float',
            'akurasi_gps'        => 'float',
            'is_auto_checkout'   => 'boolean',
        ];
    }

    public function scopeHadir($query)
    {
        return $query->where('status', 'hadir');
    }

    public function scopePeriode($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    // Menghitung menit keterlambatan dan status hadir/terlambat berdasarkan jam mulai jadwal dan toleransi settings.
    public static function hitungKeterlambatan(string $jamMulaiJadwal, string $jamCheckin, ?int $toleransiMenit = null): array
    {
        $toleransi = max(0, $toleransiMenit ?? (int) Setting::get('toleransi_terlambat_menit', '15'));

        $mulai   = Carbon::createFromFormat('!H:i', substr($jamMulaiJadwal, 0, 5));
        $checkin = Carbon::createFromFormat('!H:i', substr($jamCheckin, 0, 5));

        $selisihMenit   = $mulai->diffInMinutes($checkin, false);
        $menitTerlambat = max(0, (int) $selisihMenit);

        $status = $menitTerlambat > $toleransi ? 'terlambat' : 'hadir';

        return [
            'menit_terlambat' => $menitTerlambat,
            'toleransi_menit' => $toleransi,
            'status'          => $status,
        ];
    }

    // Menutup otomatis sesi absensi yang lupa check-out setelah melewati batas waktu selesai kelas.
    public static function prosesAutoCheckoutSesiLewat(?int $toleransiSelesaiMenit = 30): int
    {
        $openSessions = self::with('jadwal')
            ->whereNotNull('jam_checkin')
            ->whereNull('jam_checkout')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->get();

        $sekarang = now();
        $closedCount = 0;

        foreach ($openSessions as $absensi) {
            $jamSelesai = $absensi->jadwal?->jam_selesai
                ? substr((string) $absensi->jadwal->jam_selesai, 0, 5)
                : '17:00';

            $batasWaktu = Carbon::parse($absensi->tanggal->toDateString() . ' ' . $jamSelesai)
                ->addMinutes($toleransiSelesaiMenit ?? 30);

            if ($sekarang->gt($batasWaktu)) {
                $catatanLama = trim((string) $absensi->keterangan);
                $catatanAuto = 'Auto-Checkout sistem (instruktur belum check-out hingga melewati batas waktu selesai kelas)';

                $absensi->update([
                    'jam_checkout'     => $jamSelesai,
                    'checkout_at'      => $batasWaktu,
                    'is_auto_checkout' => true,
                    'keterangan'       => $catatanLama !== '' ? $catatanLama . ' | ' . $catatanAuto : $catatanAuto,
                ]);
                $closedCount++;
            }
        }

        return $closedCount;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'izin'        => 'Izin',
            'tidak_hadir' => 'Tidak Hadir',
            default       => ucfirst($this->status ?? 'hadir'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'hadir'       => 'emerald',
            'terlambat'   => 'amber',
            'izin'        => 'blue',
            'tidak_hadir' => 'red',
            default       => 'gray',
        };
    }

    public function getStatusGpsNormalizedAttribute(): array
    {
        if ($this->jam_checkin === null) {
            return [
                'code'   => 'belum_hadir',
                'label'  => 'Belum Hadir',
                'color'  => 'amber',
                'detail' => 'Belum ada catatan check-in',
            ];
        }

        if ($this->latitude_checkin !== null && $this->longitude_checkin !== null) {
            return [
                'code'   => 'terdeteksi',
                'label'  => 'Terdeteksi',
                'color'  => 'emerald',
                'detail' => null,
            ];
        }

        $isDitolak = $this->status_gps === 'ditolak_perangkat';

        return [
            'code'   => 'gps_gagal',
            'label'  => 'GPS Gagal',
            'color'  => $isDitolak ? 'red' : 'amber',
            'detail' => $isDitolak
                ? 'Izin lokasi ditolak perangkat'
                : 'Koordinat tidak tersedia saat check-in',
        ];
    }

    public function getStatusGpsLabelAttribute(): string
    {
        return $this->status_gps_normalized['label'];
    }

    public static function hitungJarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

        return (int) round($earthRadius * $c);
    }

    public function getLokasiCheckinInfoAttribute(): ?array
    {
        if ($this->latitude_checkin === null || $this->longitude_checkin === null) {
            return null;
        }

        $refLat      = (float) Setting::get('lpk_latitude', '-7.3308868');
        $refLng      = (float) Setting::get('lpk_longitude', '108.3560168');
        $lpkName     = trim((string) Setting::get('lpk_name', 'LPK Cipta Tungga Indonesia Ciamis'));
        $radiusMeter = max(10, (int) Setting::get('radius_lokasi_meter', '150'));

        if ($lpkName === '') {
            $lpkName = 'LPK Cipta Tungga Indonesia Ciamis';
        }

        $jarakMeter = self::hitungJarakMeter(
            (float) $this->latitude_checkin,
            (float) $this->longitude_checkin,
            $refLat,
            $refLng
        );

        $jarakRingkas = $jarakMeter < 1000
            ? "{$jarakMeter} m"
            : number_format($jarakMeter / 1000, 1, ',', '.') . ' km';

        $jarakLabel = "{$jarakRingkas} dari lokasi LPK";

        $dalamRadius = $jarakMeter <= $radiusMeter;
        $namaLokasi  = $dalamRadius
            ? "Area {$lpkName}"
            : "Di luar area {$lpkName}";

        return [
            'nama_lokasi'   => $namaLokasi,
            'jarak_meter'   => $jarakMeter,
            'jarak_ringkas' => $jarakRingkas,
            'jarak_label'   => $jarakLabel,
            'radius_meter'  => $radiusMeter,
            'dalam_radius'  => $dalamRadius,
            'koordinat_raw' => "{$this->latitude_checkin}, {$this->longitude_checkin}",
        ];
    }

    public function getIsBelumCheckoutAttribute(): bool
    {
        return $this->jam_checkin !== null && $this->jam_checkout === null;
    }

    public function getJamJadwalRangeAttribute(): string
    {
        $jamMulai = $this->jam_mulai_jadwal
            ? substr((string) $this->jam_mulai_jadwal, 0, 5)
            : ($this->jadwal?->jam_mulai ? substr((string) $this->jadwal->jam_mulai, 0, 5) : null);

        $jamSelesai = $this->jadwal?->jam_selesai
            ? substr((string) $this->jadwal->jam_selesai, 0, 5)
            : null;

        if ($jamMulai && $jamSelesai) {
            return "{$jamMulai} - {$jamSelesai} WIB";
        }

        if ($jamMulai) {
            return "{$jamMulai} WIB";
        }

        return '-';
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }
}
