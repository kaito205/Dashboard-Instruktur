<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->foreignId('kode_evaluasi_id')->nullable()->constrained('kode_evaluasis')->nullOnDelete();
            $table->string('nama_pengisi', 100)->nullable();
            $table->json('jawaban')->nullable()->comment('Simpan jawaban per pertanyaan dalam format JSON');
            $table->decimal('total_nilai', 5, 2)->nullable();
            $table->string('batch_import', 100)->nullable()->comment('Identifier batch saat import dari Google Form');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('instruktur_id');
            $table->index('kode_evaluasi_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasis');
    }
};
