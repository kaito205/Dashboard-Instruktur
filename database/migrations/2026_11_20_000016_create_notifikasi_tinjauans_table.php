<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifikasi_tinjauans')) {
            Schema::create('notifikasi_tinjauans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('alert_key', 120);
                $table->string('kategori', 60);
                $table->string('level', 20)->default('warning');
                $table->string('judul', 200);
                $table->string('ringkasan', 255)->nullable();
                $table->string('url', 255)->nullable();
                $table->timestamp('ditinjau_pada');
                $table->timestamps();

                $table->unique(['user_id', 'alert_key'], 'notifikasi_tinjauans_user_alert_unique');
                $table->index(['user_id', 'ditinjau_pada'], 'notifikasi_tinjauans_user_reviewed_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_tinjauans');
    }
};
