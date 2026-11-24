<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    public const STATUS_LIST = [
        'aktif'    => 'Aktif',
        'selesai'  => 'Selesai',
        'nonaktif' => 'Nonaktif',
    ];

    protected $fillable = [
        'kode_kelas',
        'nama_kelas',
        'program_pelatihan',
        'kuota_peserta',
        'ruangan_default',
        'periode_mulai',
        'periode_selesai',
        'status',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'kuota_peserta'   => 'integer',
            'periode_mulai'   => 'date',
            'periode_selesai' => 'date',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst((string) ($this->status ?? 'aktif'));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'emerald',
            'selesai'  => 'gray',
            'nonaktif' => 'red',
            default    => 'gray',
        };
    }

    public function getRuanganDefaultLabelAttribute(): string
    {
        $trimmed = trim((string) ($this->ruangan_default ?? ''));
        if ($trimmed === '') {
            return '-';
        }

        $upper = strtoupper($trimmed);

        return Jadwal::getRuanganMap()[$upper] ?? $trimmed;
    }

    public function getPeriodeLabelAttribute(): string
    {
        if ($this->periode_mulai && $this->periode_selesai) {
            return $this->periode_mulai->format('d/m/Y') . ' s.d. ' . $this->periode_selesai->format('d/m/Y');
        }
        if ($this->periode_mulai) {
            return 'Mulai ' . $this->periode_mulai->format('d/m/Y');
        }
        if ($this->periode_selesai) {
            return 'Sampai ' . $this->periode_selesai->format('d/m/Y');
        }

        return 'Berkelanjutan';
    }

    public function getDisplayLabelAttribute(): string
    {
        $kode = trim((string) $this->kode_kelas);
        $nama = trim((string) $this->nama_kelas);

        if ($nama === '' || strtoupper($nama) === strtoupper($kode)) {
            return $kode !== '' ? $kode : $nama;
        }

        return "{$kode} - {$nama}";
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }

    public function kodeEvaluasis(): HasMany
    {
        return $this->hasMany(KodeEvaluasi::class, 'kelas_id');
    }

    /**
     * Terapkan kondisi filter Jadwal agar mencakup relasi kelas_id maupun kecocokan kode/nama kelas historis.
     */
    public function scopeMatchingJadwal(Builder $query): void
    {
        $kodeUpper = strtoupper(trim((string) $this->kode_kelas));
        $namaLower = mb_strtolower(trim((string) $this->nama_kelas));

        $query->where(function (Builder $sub) use ($kodeUpper, $namaLower) {
            $sub->where('kelas_id', $this->id);
            if ($kodeUpper !== '') {
                $sub->orWhereRaw('UPPER(kode_kelas) = ?', [$kodeUpper]);
            }
            if ($namaLower !== '') {
                $sub->orWhereRaw('LOWER(nama_kelas) = ?', [$namaLower]);
            }
        });
    }

    /**
     * Terapkan kondisi filter KodeEvaluasi agar mencakup relasi kelas_id maupun kecocokan kode/nama kelas historis.
     */
    public function scopeMatchingKodeEvaluasi(Builder $query): void
    {
        $kodeUpper = strtoupper(trim((string) $this->kode_kelas));
        $namaLower = mb_strtolower(trim((string) $this->nama_kelas));

        $query->where(function (Builder $sub) use ($kodeUpper, $namaLower) {
            $sub->where('kelas_id', $this->id);
            if ($kodeUpper !== '') {
                $sub->orWhereRaw('UPPER(kode) = ?', [$kodeUpper]);
            }
            if ($namaLower !== '') {
                $sub->orWhereRaw('LOWER(nama_kelas) = ?', [$namaLower]);
            }
        });
    }
}
