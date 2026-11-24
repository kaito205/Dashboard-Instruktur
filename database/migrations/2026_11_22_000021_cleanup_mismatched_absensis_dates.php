<?php

use App\Models\Absensi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus baris absensi yang tanggalnya tidak sesuai dengan hari/tanggal jadwal terkait.
        $mismatchedIds = [];
        Absensi::with('jadwal')
            ->whereNotNull('jadwal_id')
            ->chunkById(200, function ($rows) use (&$mismatchedIds) {
                foreach ($rows as $abs) {
                    if ($abs->jadwal && $abs->tanggal && ! $abs->jadwal->berlakuPadaTanggal($abs->tanggal)) {
                        $mismatchedIds[] = $abs->id;
                    }
                }
            });

        if ($mismatchedIds !== []) {
            DB::table('absensis')->whereIn('id', $mismatchedIds)->delete();
        }

        // 2. Hapus duplikasi absensi untuk pasangan (instruktur_id, jadwal_id, tanggal) yang sama.
        $keepIds = DB::table('absensis')
            ->whereNotNull('jadwal_id')
            ->selectRaw('MAX(id) as keep_id')
            ->groupBy('instruktur_id', 'jadwal_id', 'tanggal')
            ->pluck('keep_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($keepIds !== []) {
            DB::table('absensis')
                ->whereNotNull('jadwal_id')
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Pembersihan data mismatch & duplikat bersifat satu arah.
    }
};
