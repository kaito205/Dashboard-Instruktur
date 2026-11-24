<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiTinjauan extends Model
{
    protected $table = 'notifikasi_tinjauans';

    protected $fillable = [
        'user_id',
        'alert_key',
        'kategori',
        'level',
        'judul',
        'ringkasan',
        'url',
        'ditinjau_pada',
    ];

    protected $casts = [
        'ditinjau_pada' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
