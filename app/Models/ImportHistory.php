<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    protected $fillable = [
        'user_id',
        'jenis',
        'nama_file',
        'total_baris',
        'baris_berhasil',
        'baris_gagal',
        'detail_error',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'detail_error' => 'array',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sukses'  => 'Sukses',
            'gagal'   => 'Gagal',
            'parsial' => 'Parsial',
            default   => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sukses'  => 'emerald',
            'gagal'   => 'red',
            'parsial' => 'amber',
            default   => 'gray',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
