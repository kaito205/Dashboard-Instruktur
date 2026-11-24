<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
            $table->string('nama_kelas', 150);
            $table->string('materi', 255)->nullable();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruangan', 100)->nullable();
            $table->integer('jumlah_peserta')->default(0);
            $table->enum('status', ['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'])->default('terjadwal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['instruktur_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
