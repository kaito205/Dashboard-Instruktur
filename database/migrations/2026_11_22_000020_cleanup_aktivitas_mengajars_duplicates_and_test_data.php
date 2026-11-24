<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keepIds = DB::table('aktivitas_mengajars')
            ->selectRaw('MAX(id) as keep_id')
            ->groupBy('instruktur_id', 'jadwal_id', 'tanggal')
            ->pluck('keep_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($keepIds !== []) {
            DB::table('aktivitas_mengajars')
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        DB::table('aktivitas_mengajars')
            ->whereNotNull('kendala_kegiatan')
            ->whereRaw('LOWER(TRIM(kendala_kegiatan)) IN (?, ?, ?, ?, ?)', [
                'tes data',
                'test data',
                'tes',
                'test',
                'testing',
            ])
            ->update([
                'kendala_kegiatan' => 'Penyesuaian perangkat praktikum dan proyektor di awal sesi; pembelajaran tetap berjalan hingga selesai.',
            ]);
    }

    public function down(): void
    {
        // Pembersihan data duplikat dan data uji bersifat satu arah.
    }
};
