<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jadwals', 'riwayat_pengalihan')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->text('riwayat_pengalihan')->nullable()->after('alasan_pengalihan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jadwals', 'riwayat_pengalihan')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropColumn('riwayat_pengalihan');
            });
        }
    }
};
