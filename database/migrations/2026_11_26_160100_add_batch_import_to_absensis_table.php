<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('absensis') && ! Schema::hasColumn('absensis', 'batch_import')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->string('batch_import', 80)->nullable()->after('keterangan');
                $table->index('batch_import', 'idx_absensis_batch_import');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('absensis') && Schema::hasColumn('absensis', 'batch_import')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropIndex('idx_absensis_batch_import');
                $table->dropColumn('batch_import');
            });
        }
    }
};
