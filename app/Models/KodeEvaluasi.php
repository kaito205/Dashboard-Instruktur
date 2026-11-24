<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class KodeEvaluasi extends Model
{
    public const KODE_KELAS_LPK = [
        'RPL'  => 'Rekayasa Perangkat Lunak (RPL)',
        'TKJ1' => 'Teknik Komputer & Jaringan 1 (TKJ1)',
        'TKJ2' => 'Teknik Komputer & Jaringan 2 (TKJ2)',
        'TKJ3' => 'Teknik Komputer & Jaringan 3 (TKJ3)',
        'AP1'  => 'Administrasi Perkantoran 1 (AP1)',
        'AP2'  => 'Administrasi Perkantoran 2 (AP2)',
        'AP3'  => 'Administrasi Perkantoran 3 (AP3)',
        'PS1'  => 'Public Speaking 1 (PS1)',
        'PS2'  => 'Public Speaking 2 (PS2)',
    ];

    protected $fillable = [
        'instruktur_id',
        'kelas_id',
        'kode',
        'nama_kelas',
        'is_active',
        'expired_at',
        'max_pengisian',
        'jumlah_diisi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'expired_at'    => 'datetime',
            'max_pengisian' => 'integer',
            'jumlah_diisi'  => 'integer',
        ];
    }

    public static function generateKode(?string $prefix = null): string
    {
        if ($prefix && !self::where('kode', strtoupper(trim($prefix)))->exists()) {
            return strtoupper(trim($prefix));
        }

        do {
            $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $kode = ($prefix ? strtoupper(trim($prefix)) . '-' : 'EV-') . substr(str_shuffle(str_repeat($pool, 3)), 0, 6);
        } while (self::where('kode', $kode)->exists());

        return $kode;
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }
        if ($this->max_pengisian !== null && $this->jumlah_diisi >= $this->max_pengisian) {
            return false;
        }
        return true;
    }

    public function getSisaPengisianAttribute(): ?int
    {
        if ($this->max_pengisian === null) {
            return null;
        }
        return max(0, $this->max_pengisian - $this->jumlah_diisi);
    }

    /**
     * Ambil daftar sesi/jadwal & instruktur yang terkait dengan kode evaluasi kelas ini.
     * Mendukung satu kelas yang diajar beberapa instruktur dan memiliki beberapa sesi/materi.
     */
    public function resolveSesiJadwal(): Collection
    {
        $kodeUpper = strtoupper(trim($this->kode));
        $namaKelas = $this->nama_kelas;
        $kelasModel = $this->kelas_id ? $this->kelas()->first() : null;

        $jadwals = Jadwal::with('instruktur')
            ->where('status', '!=', 'dibatalkan')
            ->where(function ($q) use ($kodeUpper, $namaKelas, $kelasModel) {
                if ($this->kelas_id) {
                    $q->where('kelas_id', $this->kelas_id)
                        ->orWhereRaw('UPPER(kode_kelas) = ?', [$kodeUpper])
                        ->orWhereRaw('UPPER(nama_kelas) = ?', [$kodeUpper]);
                } else {
                    $q->whereRaw('UPPER(kode_kelas) = ?', [$kodeUpper])
                        ->orWhereRaw('UPPER(nama_kelas) = ?', [$kodeUpper]);
                }

                if ($namaKelas) {
                    $q->orWhere('nama_kelas', $namaKelas);
                }

                if ($kelasModel) {
                    $masterKodeUpper = strtoupper(trim((string) $kelasModel->kode_kelas));
                    $masterNama = trim((string) $kelasModel->nama_kelas);
                    if ($masterKodeUpper !== '') {
                        $q->orWhereRaw('UPPER(kode_kelas) = ?', [$masterKodeUpper]);
                    }
                    if ($masterNama !== '') {
                        $q->orWhere('nama_kelas', $masterNama);
                    }
                }
            })
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get();

        if ($jadwals->isEmpty() && $this->instruktur_id) {
            $jadwals = Jadwal::with('instruktur')
                ->where('instruktur_id', $this->instruktur_id)
                ->where('status', '!=', 'dibatalkan')
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get();
        }

        return $jadwals;
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id')->withTrashed();
    }

    public function evaluasis(): HasMany
    {
        return $this->hasMany(Evaluasi::class);
    }
}
