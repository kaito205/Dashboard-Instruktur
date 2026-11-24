<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrukturAuditLog extends Model
{
    protected $fillable = [
        'instruktur_id',
        'user_id',
        'aksi',
        'ringkasan_perubahan',
        'data_lama',
        'data_baru',
    ];

    protected function casts(): array
    {
        return [
            'data_lama' => 'array',
            'data_baru' => 'array',
        ];
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAksiLabelAttribute(): string
    {
        return match ($this->aksi) {
            'dibuat'              => 'Profil Dibuat',
            'diperbarui'          => 'Profil Diperbarui',
            'status_diubah'       => 'Status Diubah',
            'cuti_diubah'         => 'Status Cuti/Izin Diperbarui',
            'sertifikasi_diubah'  => 'Dokumen Sertifikasi Diperbarui',
            'dihapus_sementara'   => 'Dinonaktifkan (Soft Delete)',
            default               => ucfirst(str_replace('_', ' ', (string) $this->aksi)),
        };
    }
}
