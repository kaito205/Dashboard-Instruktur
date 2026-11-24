<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluasis')) {
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->index('jadwal_id', 'evaluasis_jadwal_id_idx');
                $table->index(['instruktur_id', 'created_at'], 'evaluasis_instruktur_created_at_idx');
            });
        }

        if (Schema::hasTable('aktivitas_mengajars')) {
            Schema::table('aktivitas_mengajars', function (Blueprint $table) {
                $table->index('tanggal', 'aktivitas_mengajars_tanggal_idx');
                $table->index('status_verifikasi', 'aktivitas_mengajars_status_verifikasi_idx');
            });
        }

        if (Schema::hasTable('jadwals')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->index(['periode_mulai', 'periode_selesai'], 'jadwals_periode_range_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evaluasis')) {
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->dropIndex('evaluasis_jadwal_id_idx');
                $table->dropIndex('evaluasis_instruktur_created_at_idx');
            });
        }

        if (Schema::hasTable('aktivitas_mengajars')) {
            Schema::table('aktivitas_mengajars', function (Blueprint $table) {
                $table->dropIndex('aktivitas_mengajars_tanggal_idx');
                $table->dropIndex('aktivitas_mengajars_status_verifikasi_idx');
            });
        }

        if (Schema::hasTable('jadwals')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropIndex('jadwals_periode_range_idx');
            });
        }
    }
};
