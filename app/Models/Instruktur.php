<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Instruktur extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (self $instruktur): void {
            $instruktur->syncRiwayatCuti();
        });
    }

    protected $fillable = [
        'user_id',
        'kode_instruktur',
        'nama_lengkap',
        'email',
        'no_telepon',
        'alamat',
        'foto_profil',
        'bidang_keahlian',
        'riwayat_keahlian',
        'nama_sertifikasi',
        'file_sertifikasi',
        'sertifikasi_berlaku_sampai',
        'status',
        'status_ketersediaan',
        'cuti_mulai',
        'cuti_selesai',
        'keterangan_cuti',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'status'                     => 'string',
            'status_ketersediaan'        => 'string',
            'sertifikasi_berlaku_sampai' => 'date',
            'cuti_mulai'                 => 'date',
            'cuti_selesai'               => 'date',
        ];
    }

    public static function generateNextCode(): string
    {
        $max = 0;
        $codes = static::withTrashed()->lockForUpdate()->pluck('kode_instruktur');

        foreach ($codes as $code) {
            if (preg_match('/^INS-CTI(\d+)$/i', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        $nextNumber = $max + 1;
        do {
            $kode = 'INS-CTI' . str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (static::withTrashed()->lockForUpdate()->where('kode_instruktur', $kode)->exists());

        return $kode;
    }

    public function hasOperationalHistory(): bool
    {
        return $this->jadwals()->exists()
            || $this->absensis()->exists()
            || $this->aktivitasMengajars()->exists()
            || $this->evaluasis()->exists()
            || $this->kodeEvaluasis()->exists()
            || $this->observasiSupervisors()->exists();
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            default    => ucfirst($this->status),
        };
    }

    public function getKetersediaanLabelAttribute(): string
    {
        return match ($this->status_ketersediaan ?? 'tersedia') {
            'cuti'     => 'Sedang Cuti',
            'izin'     => 'Sedang Izin',
            default    => 'Tersedia',
        };
    }

    // Menolak penjadwalan serta mengecualikan Alpa/jurnal kosong apabila tanggal berada dalam masa cuti/izin aktif maupun riwayat cuti.
    public function sedangCutiAtauIzinPada(
        ?string $tanggal = null,
        ?string $hari = null,
        ?string $periodeMulai = null,
        ?string $periodeSelesai = null
    ): bool {
        $intervals = [];

        $ketersediaan = $this->status_ketersediaan ?? 'tersedia';
        if (in_array($ketersediaan, ['cuti', 'izin'], true)) {
            $intervals[] = [
                'start' => $this->cuti_mulai?->copy()->startOfDay(),
                'end'   => $this->cuti_selesai?->copy()->startOfDay(),
            ];
        }

        if ($this->exists && Schema::hasTable('instruktur_cutis')) {
            $riwayatCutis = $this->relationLoaded('cutis')
                ? $this->cutis
                : $this->cutis()->get();

            foreach ($riwayatCutis as $cuti) {
                if (! $cuti->mulai_tanggal) {
                    continue;
                }
                $intervals[] = [
                    'start' => $cuti->mulai_tanggal->copy()->startOfDay(),
                    'end'   => $cuti->selesai_tanggal?->copy()->startOfDay(),
                ];
            }
        }

        if ($intervals === []) {
            return false;
        }

        foreach ($intervals as $interval) {
            if ($this->intervalCutiBeririsan($interval['start'], $interval['end'], $tanggal, $hari, $periodeMulai, $periodeSelesai)) {
                return true;
            }
        }

        return false;
    }

    private function intervalCutiBeririsan(
        ?Carbon $leaveStart,
        ?Carbon $leaveEnd,
        ?string $tanggal,
        ?string $hari,
        ?string $periodeMulai,
        ?string $periodeSelesai
    ): bool {
        if ($tanggal !== null && $tanggal !== '') {
            $target = Carbon::parse($tanggal)->startOfDay();
            if ($leaveStart && $target->lt($leaveStart)) {
                return false;
            }
            if ($leaveEnd && $target->gt($leaveEnd)) {
                return false;
            }

            return true;
        }

        $schedStart = ($periodeMulai !== null && $periodeMulai !== '')
            ? Carbon::parse($periodeMulai)->startOfDay()
            : today();
        $schedEnd = ($periodeSelesai !== null && $periodeSelesai !== '')
            ? Carbon::parse($periodeSelesai)->startOfDay()
            : null;

        if ($leaveEnd !== null && $leaveEnd->lt($schedStart)) {
            return false;
        }

        if ($leaveStart !== null && $schedEnd !== null && $leaveStart->gt($schedEnd)) {
            return false;
        }

        if ($leaveStart !== null && $leaveEnd !== null && $hari !== null && $hari !== '') {
            $overlapStart = $schedStart->gt($leaveStart) ? $schedStart->copy() : $leaveStart->copy();
            $overlapEnd   = ($schedEnd !== null && $schedEnd->lt($leaveEnd)) ? $schedEnd->copy() : $leaveEnd->copy();

            if ($overlapStart->gt($overlapEnd)) {
                return false;
            }

            $cursor = $overlapStart->copy();
            $maxDay = $overlapStart->copy()->addDays(6);
            $limit  = $overlapEnd->lt($maxDay) ? $overlapEnd : $maxDay;

            while ($cursor->lte($limit)) {
                if (Jadwal::hariDariTanggal($cursor) === $hari) {
                    return true;
                }
                $cursor->addDay();
            }

            return false;
        }

        return true;
    }

    public function syncRiwayatCuti(): void
    {
        if (! $this->exists || ! Schema::hasTable('instruktur_cutis')) {
            return;
        }

        $ketersediaan = $this->status_ketersediaan ?? 'tersedia';
        $todayStr     = today()->toDateString();
        $yesterdayStr = today()->subDay()->toDateString();

        if (in_array($ketersediaan, ['cuti', 'izin'], true)) {
            $mulaiStr   = $this->cuti_mulai?->toDateString() ?? $todayStr;
            $selesaiStr = $this->cuti_selesai?->toDateString();

            $existing = $this->cutis()
                ->whereDate('mulai_tanggal', $mulaiStr)
                ->first();

            if ($existing) {
                $existing->update([
                    'status_ketersediaan' => $ketersediaan,
                    'selesai_tanggal'     => $selesaiStr,
                    'keterangan'          => $this->keterangan_cuti,
                ]);
            } else {
                $this->cutis()->create([
                    'status_ketersediaan' => $ketersediaan,
                    'mulai_tanggal'       => $mulaiStr,
                    'selesai_tanggal'     => $selesaiStr,
                    'keterangan'          => $this->keterangan_cuti,
                ]);
            }
        } else {
            // Jika dikembalikan ke 'tersedia', batalkan cuti masa depan yang belum dimulai
            // dan tutup cuti berjalan/terbuka ke kemarin agar histori cuti masa lalu tetap utuh.
            $this->cutis()
                ->whereDate('mulai_tanggal', '>=', $todayStr)
                ->delete();

            $this->cutis()
                ->whereDate('mulai_tanggal', '<', $todayStr)
                ->where(function ($q) use ($todayStr) {
                    $q->whereNull('selesai_tanggal')
                        ->orWhereDate('selesai_tanggal', '>=', $todayStr);
                })
                ->update(['selesai_tanggal' => $yesterdayStr]);
        }

        $this->unsetRelation('cutis');
    }

    public function cutis(): HasMany
    {
        return $this->hasMany(InstrukturCuti::class)->orderByDesc('mulai_tanggal');
    }

    // Mendeteksi sertifikasi yang akan habis masa berlakunya dalam 30 hari ke depan atau sudah kedaluwarsa.
    public function isSertifikasiHampirKedaluwarsa(int $batasHari = 30): bool
    {
        if (!$this->sertifikasi_berlaku_sampai) {
            return false;
        }

        return $this->sertifikasi_berlaku_sampai->lte(today()->addDays($batasHari));
    }

    public function getStatusSertifikasiAttribute(): array
    {
        if (!$this->nama_sertifikasi && !$this->sertifikasi_berlaku_sampai && !$this->file_sertifikasi) {
            return ['status' => 'belum_ada', 'label' => 'Belum Ada Sertifikasi', 'color' => 'gray'];
        }

        if (!$this->sertifikasi_berlaku_sampai) {
            return ['status' => 'aktif', 'label' => 'Seumur Hidup / Aktif', 'color' => 'emerald'];
        }

        $sisaHari = (int) today()->diffInDays($this->sertifikasi_berlaku_sampai, false);
        if ($sisaHari < 0) {
            return ['status' => 'kedaluwarsa', 'label' => 'Kedaluwarsa (' . abs($sisaHari) . ' hari lalu)', 'color' => 'red'];
        }
        if ($sisaHari <= 30) {
            return ['status' => 'hampir_habis', 'label' => 'Hampir Habis (' . $sisaHari . ' hari lagi)', 'color' => 'amber'];
        }

        return ['status' => 'aktif', 'label' => 'Berlaku s/d ' . $this->sertifikasi_berlaku_sampai->format('d/m/Y'), 'color' => 'emerald'];
    }

    public function getSertifikasiUrlAttribute(): ?string
    {
        return $this->file_sertifikasi ? asset('storage/' . $this->file_sertifikasi) : null;
    }

    public function getInisialAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->nama_lengkap ?? 'I'));
        $first = mb_substr($words[0] ?? 'I', 0, 1);
        $second = isset($words[1]) ? mb_substr($words[1], 0, 1) : '';
        return mb_strtoupper($first . $second);
    }

    public function getDefaultAvatarUrlAttribute(): string
    {
        $initials = htmlspecialchars($this->inisial, ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" rx="64" fill="#3641F5"/>'
            . '<text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="#FFFFFF" font-family="sans-serif" font-size="48" font-weight="600">'
            . $initials
            . '</text></svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    public function getFotoUrlAttribute(): string
    {
        $path = $this->foto_profil;
        if (empty($path) && $this->user_id) {
            $path = $this->relationLoaded('user') ? $this->user?->foto_profil : $this->user()->value('foto_profil');
        }

        $normalized = ltrim(preg_replace('#^storage/#i', '', str_replace('\\', '/', trim((string) $path))), '/');
        if ($normalized !== '' && !str_contains($normalized, '..') && Storage::disk('public')->exists($normalized)) {
            $version = null;
            try {
                $version = Storage::disk('public')->lastModified($normalized) ?: $this->updated_at?->timestamp;
            } catch (\Throwable) {
                $version = $this->updated_at?->timestamp;
            }

            $baseUrl = asset('storage/' . $normalized);

            return $version ? $baseUrl . '?v=' . $version : $baseUrl;
        }

        return $this->default_avatar_url;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function aktivitasMengajars(): HasMany
    {
        return $this->hasMany(AktivitasMengajar::class);
    }

    public function evaluasis(): HasMany
    {
        return $this->hasMany(Evaluasi::class);
    }

    public function kodeEvaluasis(): HasMany
    {
        return $this->hasMany(KodeEvaluasi::class);
    }

    public function observasiSupervisors(): HasMany
    {
        return $this->hasMany(ObservasiSupervisor::class);
    }

    public function sertifikasis(): HasMany
    {
        return $this->hasMany(InstrukturSertifikasi::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function syncPrimarySertifikasiSummary(): void
    {
        $primary = $this->sertifikasis()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $this->updateQuietly([
            'nama_sertifikasi'           => $primary?->nama_sertifikasi,
            'sertifikasi_berlaku_sampai' => $primary?->berlaku_sampai?->toDateString(),
            'file_sertifikasi'           => $primary?->file_path,
        ]);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(InstrukturAuditLog::class)->latest();
    }

    public function getTotalJadwalAttribute(): int
    {
        return $this->jadwals()->count();
    }

    public function getTotalHadirAttribute(): int
    {
        return $this->absensis()->where('status', 'hadir')->count();
    }

    public function getPersentaseKehadiranAttribute(): float
    {
        $total = $this->absensis()->count();
        if ($total === 0) {
            return 0;
        }
        $hadir = $this->absensis()->whereIn('status', ['hadir', 'terlambat'])->count();
        return round(($hadir / $total) * 100, 1);
    }

    public function getRataRataNilaiAttribute(): float
    {
        return round($this->evaluasis()->avg('total_nilai') ?? 0, 2);
    }
}
