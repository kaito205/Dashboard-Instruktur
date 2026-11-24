<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InstrukturSertifikasi extends Model
{
    protected $fillable = [
        'instruktur_id',
        'nama_sertifikasi',
        'berlaku_sampai',
        'file_path',
        'file_name',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_sampai' => 'date',
        ];
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function getStatusInfoAttribute(): array
    {
        if (! $this->berlaku_sampai) {
            return [
                'status'    => 'aktif',
                'badge'     => 'Aktif',
                'detail'    => 'Tanpa batas waktu',
                'color'     => 'emerald',
                'sisa_hari' => null,
            ];
        }

        $sisaHari = (int) today()->diffInDays($this->berlaku_sampai->copy()->startOfDay(), false);

        if ($sisaHari < 0) {
            return [
                'status'    => 'kedaluwarsa',
                'badge'     => 'Kedaluwarsa',
                'detail'    => 'Lewat ' . abs($sisaHari) . ' hari',
                'color'     => 'red',
                'sisa_hari' => $sisaHari,
            ];
        }

        if ($sisaHari <= 30) {
            return [
                'status'    => 'hampir_kedaluwarsa',
                'badge'     => 'Hampir Kedaluwarsa',
                'detail'    => $sisaHari === 0 ? 'Berakhir hari ini' : 'Sisa ' . $sisaHari . ' hari',
                'color'     => 'amber',
                'sisa_hari' => $sisaHari,
            ];
        }

        return [
            'status'    => 'aktif',
            'badge'     => 'Aktif',
            'detail'    => 'Berlaku ' . $sisaHari . ' hari lagi',
            'color'     => 'emerald',
            'sisa_hari' => $sisaHari,
        ];
    }

    public function getHasFileAttribute(): bool
    {
        return ! empty($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }

    public function getDisplayFileNameAttribute(): string
    {
        if (! empty($this->file_name)) {
            return $this->file_name;
        }

        return $this->file_path ? basename($this->file_path) : '';
    }
}
