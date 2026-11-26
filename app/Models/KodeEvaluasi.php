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
        $cleanPrefix = $prefix !== null
            ? preg_replace('/[^A-Z0-9._-]/', '', strtoupper(trim($prefix)))
            : '';
        $cleanPrefix = trim((string) $cleanPrefix, '-_.');

        if ($cleanPrefix !== '' && strlen($cleanPrefix) <= 50 && ! self::where('kode', $cleanPrefix)->exists()) {
            return $cleanPrefix;
        }

        $base = $cleanPrefix !== '' ? substr($cleanPrefix, 0, 43) : 'EV';

        do {
            $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $kode = $base . '-' . substr(str_shuffle(str_repeat($pool, 3)), 0, 6);
        } while (self::where('kode', $kode)->exists());

        return $kode;
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }
        if ($this->max_pengisian !== null && $this->jumlah_diisi >= $this->max_pengisian) {
            return false;
        }
        if ($this->instruktur_id) {
            $ins = $this->relationLoaded('instruktur') ? $this->instruktur : $this->instruktur()->first();
            if (! $ins || $ins->trashed() || $ins->status !== 'aktif') {
                return false;
            }
        }
        if ($this->kelas_id) {
            $kls = $this->relationLoaded('kelas') ? $this->kelas : $this->kelas()->first();
            if ($kls && ($kls->trashed() || $kls->status === 'nonaktif')) {
                return false;
            }
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
        $kodeUpper  = strtoupper(trim($this->kode));
        $namaKelas  = $this->nama_kelas;
        $kelasModel = $this->kelas_id ? $this->kelas()->first() : null;

        $jadwals = Jadwal::with('instruktur')
            ->where('status', '!=', 'dibatalkan')
            ->whereHas('instruktur', fn ($iq) => $iq->whereNull('deleted_at')->where('status', 'aktif'))
            ->where(function ($q) use ($kodeUpper, $namaKelas, $kelasModel) {
                if ($this->kelas_id) {
                    $q->where('kelas_id', $this->kelas_id);

                    $legacyKodes = array_values(array_filter(array_unique([
                        $kodeUpper,
                        $kelasModel ? strtoupper(trim((string) $kelasModel->kode_kelas)) : '',
                    ])));

                    if ($legacyKodes !== []) {
                        $q->orWhere(function ($sub) use ($legacyKodes) {
                            $sub->whereNull('kelas_id')
                                ->where(function ($kSub) use ($legacyKodes) {
                                    foreach ($legacyKodes as $lk) {
                                        $kSub->orWhereRaw('UPPER(kode_kelas) = ?', [$lk]);
                                    }
                                });
                        });
                    }
                    return;
                }

                $q->whereRaw('UPPER(kode_kelas) = ?', [$kodeUpper])
                    ->orWhereRaw('UPPER(nama_kelas) = ?', [$kodeUpper]);

                if ($namaKelas) {
                    $q->orWhere('nama_kelas', $namaKelas);
                }
            })
            ->when($this->instruktur_id, fn ($q) => $q->where('instruktur_id', $this->instruktur_id))
            ->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get();

        if ($jadwals->isEmpty() && $this->instruktur_id) {
            $jadwals = Jadwal::with('instruktur')
                ->where('instruktur_id', $this->instruktur_id)
                ->where('status', '!=', 'dibatalkan')
                ->whereHas('instruktur', fn ($iq) => $iq->whereNull('deleted_at')->where('status', 'aktif'))
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
