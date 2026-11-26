<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrukturCuti extends Model
{
    protected $table = 'instruktur_cutis';

    protected $fillable = [
        'instruktur_id',
        'status_ketersediaan',
        'mulai_tanggal',
        'selesai_tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'mulai_tanggal'   => 'date',
            'selesai_tanggal' => 'date',
        ];
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }
}
