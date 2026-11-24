<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'foto_profil')) {
                $table->string('foto_profil', 255)->nullable()->after('email');
            }
        });

        // Sinkronkan foto_profil instruktur yang sudah ada ke tabel users
        if (Schema::hasTable('instrukturs') && Schema::hasColumn('instrukturs', 'foto_profil')) {
            $rows = DB::table('instrukturs')
                ->whereNotNull('user_id')
                ->whereNotNull('foto_profil')
                ->get(['user_id', 'foto_profil']);

            foreach ($rows as $row) {
                DB::table('users')
                    ->where('id', $row->user_id)
                    ->whereNull('foto_profil')
                    ->update(['foto_profil' => $row->foto_profil]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'foto_profil')) {
                $table->dropColumn('foto_profil');
            }
        });
    }
};
