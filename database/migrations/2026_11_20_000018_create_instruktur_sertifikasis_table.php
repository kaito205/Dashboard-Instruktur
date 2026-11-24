<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('instruktur_sertifikasis')) {
            Schema::create('instruktur_sertifikasis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
                $table->string('nama_sertifikasi', 150);
                $table->date('berlaku_sampai')->nullable();
                $table->string('file_path', 255)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->index(['instruktur_id', 'berlaku_sampai']);
            });
        }

        // Backfill data sertifikat lama dari kolom ringkasan tabel instrukturs
        $rows = DB::table('instrukturs')
            ->where(function ($q) {
                $q->whereNotNull('nama_sertifikasi')
                    ->orWhereNotNull('sertifikasi_berlaku_sampai')
                    ->orWhereNotNull('file_sertifikasi');
            })
            ->get(['id', 'nama_sertifikasi', 'sertifikasi_berlaku_sampai', 'file_sertifikasi', 'created_at', 'updated_at']);

        foreach ($rows as $row) {
            $nama     = trim((string) ($row->nama_sertifikasi ?? ''));
            $berlaku  = $row->sertifikasi_berlaku_sampai;
            $filePath = trim((string) ($row->file_sertifikasi ?? ''));

            if ($nama === '' && empty($berlaku) && $filePath === '') {
                continue;
            }

            $alreadyExists = DB::table('instruktur_sertifikasis')
                ->where('instruktur_id', $row->id)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DB::table('instruktur_sertifikasis')->insert([
                'instruktur_id'    => $row->id,
                'nama_sertifikasi' => $nama !== '' ? $nama : 'Sertifikasi Kompetensi',
                'berlaku_sampai'   => $berlaku ?: null,
                'file_path'        => $filePath !== '' ? $filePath : null,
                'file_name'        => $filePath !== '' ? basename($filePath) : null,
                'keterangan'       => null,
                'created_at'       => $row->created_at ?? now(),
                'updated_at'       => $row->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instruktur_sertifikasis');
    }
};
