<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->nullOnDelete();
            $table->date('tanggal');
            $table->time('jam_checkin')->nullable();
            $table->time('jam_checkout')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'tidak_hadir', 'izin'])->default('hadir');
            $table->enum('sumber', ['manual', 'import', 'checkin_app'])->default('manual');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['instruktur_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
