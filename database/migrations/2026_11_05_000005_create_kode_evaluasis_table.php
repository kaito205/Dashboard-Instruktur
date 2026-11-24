<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_evaluasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->string('kode', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expired_at')->nullable();
            $table->integer('max_pengisian')->nullable()->comment('null = tidak terbatas');
            $table->integer('jumlah_diisi')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('kode');
            $table->index(['instruktur_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_evaluasis');
    }
};
