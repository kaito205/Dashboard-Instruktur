<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('instruktur_cutis')) {
            Schema::create('instruktur_cutis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
                $table->string('status_ketersediaan', 20)->default('cuti');
                $table->date('mulai_tanggal');
                $table->date('selesai_tanggal')->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->index(['instruktur_id', 'mulai_tanggal', 'selesai_tanggal'], 'idx_instruktur_cutis_rentang');
            });
        }

        if (Schema::hasTable('instrukturs')) {
            $activeLeaves = DB::table('instrukturs')
                ->whereIn('status_ketersediaan', ['cuti', 'izin'])
                ->whereNotNull('cuti_mulai')
                ->get(['id', 'status_ketersediaan', 'cuti_mulai', 'cuti_selesai', 'keterangan_cuti']);

            $now = now();
            foreach ($activeLeaves as $row) {
                $exists = DB::table('instruktur_cutis')
                    ->where('instruktur_id', $row->id)
                    ->whereDate('mulai_tanggal', $row->cuti_mulai)
                    ->exists();

                if (! $exists) {
                    DB::table('instruktur_cutis')->insert([
                        'instruktur_id'       => $row->id,
                        'status_ketersediaan' => $row->status_ketersediaan,
                        'mulai_tanggal'       => $row->cuti_mulai,
                        'selesai_tanggal'     => $row->cuti_selesai,
                        'keterangan'          => $row->keterangan_cuti,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instruktur_cutis');
    }
};
