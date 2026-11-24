<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_mengajars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai_aktual')->nullable();
            $table->time('jam_selesai_aktual')->nullable();
            $table->string('materi_disampaikan', 255)->nullable();
            $table->integer('jumlah_peserta_hadir')->nullable();
            $table->enum('status_konfirmasi', ['belum_dikonfirmasi', 'dikonfirmasi', 'tidak_terlaksana'])
                ->default('belum_dikonfirmasi');
            $table->text('catatan')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['instruktur_id', 'tanggal']);
            $table->index(['jadwal_id', 'status_konfirmasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_mengajars');
    }
};
