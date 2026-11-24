<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrukturs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_instruktur', 20)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('foto_profil')->nullable();
            $table->string('bidang_keahlian', 100)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('kode_instruktur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrukturs');
    }
};
