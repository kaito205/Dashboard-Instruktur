<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observasi_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->nullOnDelete();
            $table->date('tanggal');
            $table->json('indikator_penilaian');
            $table->decimal('skor_observasi', 5, 2);
            $table->text('catatan_observasi')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->timestamps();

            $table->index(['instruktur_id', 'tanggal']);
            $table->index(['supervisor_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observasi_supervisors');
    }
};
