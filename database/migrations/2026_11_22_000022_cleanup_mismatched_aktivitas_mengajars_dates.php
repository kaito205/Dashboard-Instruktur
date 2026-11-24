<?php

use App\Models\AktivitasMengajar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mismatchedIds = [];

        AktivitasMengajar::with('jadwal')
            ->whereNotNull('jadwal_id')
            ->chunkById(200, function ($rows) use (&$mismatchedIds) {
                foreach ($rows as $akt) {
                    if ($akt->jadwal && $akt->tanggal && ! $akt->jadwal->berlakuPadaTanggal($akt->tanggal)) {
                        $mismatchedIds[] = $akt->id;
                    }
                }
            });

        if ($mismatchedIds !== []) {
            DB::table('aktivitas_mengajars')->whereIn('id', $mismatchedIds)->delete();
        }
    }

    public function down(): void
    {
        // Pembersihan data aktivitas mismatch bersifat satu arah.
    }
};
