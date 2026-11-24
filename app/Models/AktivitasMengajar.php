<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AktivitasMengajar extends Model
{
    protected $fillable = [
        'jadwal_id',
        'instruktur_id',
        'tanggal',
        'jam_mulai_aktual',
        'jam_selesai_aktual',
        'materi_disampaikan',
        'tautan_materi',
        'jumlah_peserta_hadir',
        'status_konfirmasi',
        'status_verifikasi',
        'catatan',
        'kendala_kegiatan',
        'hasil_pembelajaran',
        'file_dokumentasi',
        'confirmed_at',
        'verified_by',
        'verified_at',
        'catatan_supervisor',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'              => 'date',
            'confirmed_at'         => 'datetime',
            'verified_at'          => 'datetime',
            'jumlah_peserta_hadir' => 'integer',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_konfirmasi) {
            'belum_dikonfirmasi' => 'Belum Dikonfirmasi',
            'dikonfirmasi'       => 'Dikonfirmasi',
            'tidak_terlaksana'   => 'Tidak Terlaksana',
            default              => ucfirst($this->status_konfirmasi ?? 'belum_dikonfirmasi'),
        };
    }

    public function getVerifikasiLabelAttribute(): string
    {
        return match ($this->status_verifikasi ?? 'menunggu') {
            'menunggu'     => 'Menunggu Verifikasi',
            'diverifikasi' => 'Diverifikasi',
            'perlu_revisi' => 'Perlu Revisi',
            default        => ucfirst((string) $this->status_verifikasi),
        };
    }

    public function getDurasiAktualMenitAttribute(): ?int
    {
        if (!$this->jam_mulai_aktual || !$this->jam_selesai_aktual) {
            return null;
        }

        $mulai   = Carbon::parse(substr((string) $this->jam_mulai_aktual, 0, 5));
        $selesai = Carbon::parse(substr((string) $this->jam_selesai_aktual, 0, 5));

        return max(0, (int) $mulai->diffInMinutes($selesai, false));
    }

    public function getDurasiAktualLabelAttribute(): string
    {
        $menit = $this->durasi_aktual_menit;
        if ($menit === null) {
            return '-';
        }
        if ($menit < 60) {
            return "{$menit} menit";
        }
        $jam  = intdiv($menit, 60);
        $sisa = $menit % 60;

        return $sisa === 0 ? "{$jam} jam" : "{$jam} jam {$sisa} mnt";
    }

    public function getStatusKelasInfoAttribute(): array
    {
        if ($this->jadwal?->status === 'dibatalkan') {
            return [
                'code'  => 'dibatalkan',
                'label' => 'Dibatalkan',
                'color' => 'red',
            ];
        }

        $now     = now('Asia/Jakarta');
        $tgl     = $this->tanggal?->toDateString() ?? today('Asia/Jakarta')->toDateString();
        $mulai   = $this->jam_mulai_aktual
            ? substr((string) $this->jam_mulai_aktual, 0, 5)
            : ($this->jadwal?->jam_mulai ? substr((string) $this->jadwal->jam_mulai, 0, 5) : '00:00');
        $selesai = $this->jam_selesai_aktual
            ? substr((string) $this->jam_selesai_aktual, 0, 5)
            : ($this->jadwal?->jam_selesai ? substr((string) $this->jadwal->jam_selesai, 0, 5) : '23:59');

        $startAt = Carbon::parse("{$tgl} {$mulai}", 'Asia/Jakarta');
        $endAt   = Carbon::parse("{$tgl} {$selesai}", 'Asia/Jakarta');

        if ($now->lt($startAt)) {
            return [
                'code'  => 'belum',
                'label' => 'Belum Dimulai',
                'color' => 'amber',
            ];
        }

        if ($now->between($startAt, $endAt)) {
            return [
                'code'  => 'sedang',
                'label' => 'Sedang Berlangsung',
                'color' => 'blue',
            ];
        }

        return [
            'code'  => 'selesai',
            'label' => 'Selesai',
            'color' => 'emerald',
        ];
    }

    public function getEskalasiInfoAttribute(): ?array
    {
        $now = now('Asia/Jakarta');

        if (($this->status_verifikasi ?? 'menunggu') === 'menunggu') {
            $ref = $this->confirmed_at ?? $this->created_at;
            if ($ref && $ref->copy()->timezone('Asia/Jakarta')->diffInHours($now) >= 48) {
                return [
                    'code'  => 'eskalasi_verifikasi',
                    'label' => 'Eskalasi > 2x24 jam (Belum Diverifikasi)',
                    'color' => 'red',
                ];
            }
        }

        if ($this->tanggal && $this->jadwal?->jam_selesai && $this->created_at) {
            $endAt = Carbon::parse(
                $this->tanggal->toDateString() . ' ' . substr((string) $this->jadwal->jam_selesai, 0, 5),
                'Asia/Jakarta'
            );
            $createdWib = $this->created_at->copy()->timezone('Asia/Jakarta');
            if ($endAt->diffInHours($createdWib, false) >= 48) {
                return [
                    'code'  => 'terlambat_lapor',
                    'label' => 'Terlambat Lapor (> 2x24 jam)',
                    'color' => 'amber',
                ];
            }
        }

        return null;
    }

    public function resolveDokumentasiDisk(): ?string
    {
        if (empty($this->file_dokumentasi)) {
            return null;
        }

        if (Storage::disk('local')->exists($this->file_dokumentasi)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($this->file_dokumentasi)) {
            return 'public';
        }

        return null;
    }

    public function getDokumentasiUrlAttribute(): ?string
    {
        if (empty($this->file_dokumentasi)) {
            return null;
        }

        $role = auth()->user()?->role;

        return match ($role) {
            'admin'      => route('admin.kehadiran.aktivitas.dokumentasi', $this),
            'supervisor' => route('supervisor.kehadiran.aktivitas.dokumentasi', $this),
            'instruktur' => route('instruktur.aktivitas.dokumentasi', $this),
            default      => null,
        };
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
