<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Jadwal extends Model
{
    public const HARI_LIST = [
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu',
    ];

    public const DAFTAR_HARI = self::HARI_LIST;

    public const RUANGAN_LPK = [
        'L-01' => 'L-01: Ruang Laboratorium Komputer',
        'R-01' => 'R-01: Ruang Teori',
    ];

    protected $fillable = [
        'instruktur_id',
        'instruktur_asli_id',
        'instruktur_pengganti_id',
        'kelas_id',
        'kode_kelas',
        'nama_kelas',
        'materi',
        'hari',
        'tanggal',
        'is_pola_mingguan',
        'periode_mulai',
        'periode_selesai',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'jumlah_peserta',
        'status',
        'keterangan',
        'alasan_pengalihan',
        'riwayat_pengalihan',
        'dialihkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'            => 'date',
            'is_pola_mingguan'   => 'boolean',
            'periode_mulai'      => 'date',
            'periode_selesai'    => 'date',
            'jam_mulai'          => 'string',
            'jam_selesai'        => 'string',
            'riwayat_pengalihan' => 'array',
            'dialihkan_pada'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $jadwal): void {
            if ($jadwal->is_pola_mingguan) {
                $jadwal->tanggal = null;
            } elseif ($jadwal->tanggal !== null) {
                $jadwal->is_pola_mingguan = false;
                $jadwal->hari = self::hariDariTanggal($jadwal->tanggal);
                $jadwal->periode_mulai = null;
                $jadwal->periode_selesai = null;
            }
        });
    }

    public static function hariDariTanggal($tanggal): string
    {
        $dt = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);
        return match ((int) $dt->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => 'Senin',
        };
    }

    public function berlakuPadaTanggal(string|Carbon $tanggal): bool
    {
        if ($this->status === 'dibatalkan') {
            return false;
        }

        $dt     = $tanggal instanceof Carbon ? $tanggal->copy()->startOfDay() : Carbon::parse($tanggal)->startOfDay();
        $tglStr = $dt->toDateString();

        if (! $this->is_pola_mingguan) {
            return $this->tanggal !== null && $this->tanggal->toDateString() === $tglStr;
        }

        if (! $this->hari || self::hariDariTanggal($dt) !== $this->hari) {
            return false;
        }

        if ($this->periode_mulai && $dt->lt($this->periode_mulai->copy()->startOfDay())) {
            return false;
        }

        if ($this->periode_selesai && $dt->gt($this->periode_selesai->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    public function tanggalTerdekatBerikutnya(?Carbon $mulaiDari = null, int $maxHari = 30): ?Carbon
    {
        if ($this->status === 'dibatalkan') {
            return null;
        }

        $cursor = ($mulaiDari ?? today())->copy()->startOfDay();

        for ($i = 0; $i <= $maxHari; $i++) {
            if ($this->berlakuPadaTanggal($cursor)) {
                return $cursor;
            }
            $cursor->addDay();
        }

        return null;
    }

    public function scopeFilterHari($query, string $hari)
    {
        if (! in_array($hari, self::HARI_LIST, true)) {
            return $query;
        }

        $mysqlWeekdayMap = [
            'Senin'  => 0,
            'Selasa' => 1,
            'Rabu'   => 2,
            'Kamis'  => 3,
            'Jumat'  => 4,
            'Sabtu'  => 5,
            'Minggu' => 6,
        ];
        $sqliteStrftimeMap = [
            'Minggu' => '0',
            'Senin'  => '1',
            'Selasa' => '2',
            'Rabu'   => '3',
            'Kamis'  => '4',
            'Jumat'  => '5',
            'Sabtu'  => '6',
        ];

        $driver = $query->getConnection()->getDriverName();

        return $query->where(function ($q) use ($hari, $driver, $mysqlWeekdayMap, $sqliteStrftimeMap) {
            $q->where(function ($sub) use ($hari) {
                $sub->where('is_pola_mingguan', true)->where('hari', $hari);
            })->orWhere(function ($sub) use ($hari, $driver, $mysqlWeekdayMap, $sqliteStrftimeMap) {
                $sub->where(function ($p) {
                    $p->where('is_pola_mingguan', false)->orWhereNull('is_pola_mingguan');
                })->whereNotNull('tanggal');
                if (in_array($driver, ['mysql', 'mariadb'], true) && isset($mysqlWeekdayMap[$hari])) {
                    $sub->whereRaw('WEEKDAY(tanggal) = ?', [$mysqlWeekdayMap[$hari]]);
                } elseif ($driver === 'sqlite' && isset($sqliteStrftimeMap[$hari])) {
                    $sub->whereRaw("strftime('%w', tanggal) = ?", [$sqliteStrftimeMap[$hari]]);
                } else {
                    $sub->where('hari', $hari);
                }
            });
        });
    }

    public function scopeHariIni($query)
    {
        $hariIni = self::hariDariTanggal(today());
        $todayStr = today()->toDateString();

        return $query->where(function ($q) use ($todayStr, $hariIni) {
            $q->whereDate('tanggal', $todayStr)
                ->orWhere(function ($sub) use ($hariIni, $todayStr) {
                    $sub->where('is_pola_mingguan', true)
                        ->where('hari', $hariIni)
                        ->where(fn ($p) => $p->whereNull('periode_mulai')->orWhereDate('periode_mulai', '<=', $todayStr))
                        ->where(fn ($p) => $p->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', $todayStr));
                });
        });
    }

    public function scopePeriode($query, $dari, $sampai)
    {
        $hariDalamRentang = [];
        try {
            $start = Carbon::parse($dari)->startOfDay();
            $end   = Carbon::parse($sampai)->startOfDay();
            if ($start->lte($end) && $start->diffInDays($end) < 6) {
                $curr = $start->copy();
                while ($curr->lte($end)) {
                    $hariDalamRentang[] = self::hariDariTanggal($curr);
                    $curr->addDay();
                }
                $hariDalamRentang = array_values(array_unique($hariDalamRentang));
            }
        } catch (\Throwable) {
            $hariDalamRentang = [];
        }

        $dariDate   = Carbon::parse($dari)->toDateString();
        $sampaiEnd  = Carbon::parse($sampai)->toDateString() . ' 23:59:59';

        return $query->where(function ($q) use ($dariDate, $sampaiEnd, $dari, $sampai, $hariDalamRentang) {
            $q->whereBetween('tanggal', [$dariDate, $sampaiEnd])
                ->orWhere(function ($sub) use ($dari, $sampai, $hariDalamRentang) {
                    $sub->where('is_pola_mingguan', true)
                        ->when(!empty($hariDalamRentang), fn ($h) => $h->whereIn('hari', $hariDalamRentang))
                        ->where(fn ($p) => $p->whereNull('periode_mulai')->orWhereDate('periode_mulai', '<=', $sampai))
                        ->where(fn ($p) => $p->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', $dari));
                });
        });
    }

    public function getTanggalLabelAttribute(): string
    {
        if ($this->is_pola_mingguan && $this->hari) {
            return 'Setiap ' . $this->hari . ($this->tanggal ? ' (' . $this->tanggal->format('d/m/Y') . ')' : '');
        }

        if ($this->tanggal) {
            $hariAktual = self::hariDariTanggal($this->tanggal);
            return $hariAktual . ', ' . $this->tanggal->format('d/m/Y');
        }

        return $this->hari ? 'Setiap ' . $this->hari : '-';
    }

    public static function getRuanganMap(): array
    {
        $raw = Setting::get('ruangan_lpk');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $mapped = [];
                foreach ($decoded as $kode => $label) {
                    $cleanKode  = strtoupper(trim((string) $kode));
                    $cleanLabel = is_string($label) ? trim($label) : '';
                    if ($cleanKode !== '' && $cleanLabel !== '') {
                        $mapped[$cleanKode] = $cleanLabel;
                    }
                }
                if ($mapped !== []) {
                    return $mapped;
                }
            }
        }

        return self::RUANGAN_LPK;
    }

    public function getRuanganLabelAttribute(): string
    {
        if (!$this->ruangan) {
            return '-';
        }
        $trimmed = trim((string) $this->ruangan);
        $upper = strtoupper($trimmed);

        return self::getRuanganMap()[$upper] ?? $trimmed;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'terjadwal'   => 'Terjadwal',
            'berlangsung' => 'Sedang Berlangsung',
            'selesai'     => 'Selesai',
            'dibatalkan'  => 'Dibatalkan',
            default       => ucfirst($this->status ?? 'terjadwal'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'terjadwal'   => 'blue',
            'berlangsung' => 'emerald',
            'selesai'     => 'gray',
            'dibatalkan'  => 'red',
            default       => 'gray',
        };
    }

    public function getDurasiJamAttribute(): float
    {
        $mulai   = Carbon::parse($this->jam_mulai);
        $selesai = Carbon::parse($this->jam_selesai);
        return round($mulai->diffInMinutes($selesai) / 60, 1);
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function instrukturAsli(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_asli_id')->withTrashed();
    }

    public function instrukturPengganti(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_pengganti_id')->withTrashed();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id')->withTrashed();
    }

    public function absensi(): HasOne
    {
        return $this->hasOne(Absensi::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function aktivitasMengajar(): HasOne
    {
        return $this->hasOne(AktivitasMengajar::class);
    }

    public function aktivitasMengajars(): HasMany
    {
        return $this->hasMany(AktivitasMengajar::class);
    }

    public function evaluasis(): HasMany
    {
        return $this->hasMany(Evaluasi::class);
    }

    public function observasiSupervisors(): HasMany
    {
        return $this->hasMany(ObservasiSupervisor::class);
    }
}
