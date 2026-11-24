<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrukturs', function (Blueprint $table) {
            if (!Schema::hasColumn('instrukturs', 'riwayat_keahlian')) {
                $table->text('riwayat_keahlian')->nullable()->after('bidang_keahlian');
            }
            if (!Schema::hasColumn('instrukturs', 'nama_sertifikasi')) {
                $table->string('nama_sertifikasi', 150)->nullable()->after('riwayat_keahlian');
            }
            if (!Schema::hasColumn('instrukturs', 'file_sertifikasi')) {
                $table->string('file_sertifikasi', 255)->nullable()->after('nama_sertifikasi');
            }
            if (!Schema::hasColumn('instrukturs', 'sertifikasi_berlaku_sampai')) {
                $table->date('sertifikasi_berlaku_sampai')->nullable()->after('file_sertifikasi');
            }
            if (!Schema::hasColumn('instrukturs', 'status_ketersediaan')) {
                $table->string('status_ketersediaan', 30)->default('tersedia')->after('status');
            }
            if (!Schema::hasColumn('instrukturs', 'cuti_mulai')) {
                $table->date('cuti_mulai')->nullable()->after('status_ketersediaan');
            }
            if (!Schema::hasColumn('instrukturs', 'cuti_selesai')) {
                $table->date('cuti_selesai')->nullable()->after('cuti_mulai');
            }
            if (!Schema::hasColumn('instrukturs', 'keterangan_cuti')) {
                $table->string('keterangan_cuti', 255)->nullable()->after('cuti_selesai');
            }
            if (!Schema::hasColumn('instrukturs', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (!Schema::hasTable('instruktur_audit_logs')) {
            Schema::create('instruktur_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instruktur_id')->constrained('instrukturs')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('aksi', 50);
                $table->string('ringkasan_perubahan', 255);
                $table->json('data_lama')->nullable();
                $table->json('data_baru')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('jadwals', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwals', 'instruktur_asli_id')) {
                $table->foreignId('instruktur_asli_id')->nullable()->after('instruktur_id')->constrained('instrukturs')->nullOnDelete();
            }
            if (!Schema::hasColumn('jadwals', 'instruktur_pengganti_id')) {
                $table->foreignId('instruktur_pengganti_id')->nullable()->after('instruktur_asli_id')->constrained('instrukturs')->nullOnDelete();
            }
            if (!Schema::hasColumn('jadwals', 'alasan_pengalihan')) {
                $table->string('alasan_pengalihan', 255)->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('jadwals', 'dialihkan_pada')) {
                $table->dateTime('dialihkan_pada')->nullable()->after('alasan_pengalihan');
            }
        });

        Schema::table('absensis', function (Blueprint $table) {
            if (!Schema::hasColumn('absensis', 'is_auto_checkout')) {
                $table->boolean('is_auto_checkout')->default(false)->after('status_gps');
            }
        });

        Schema::table('aktivitas_mengajars', function (Blueprint $table) {
            if (!Schema::hasColumn('aktivitas_mengajars', 'tautan_materi')) {
                $table->string('tautan_materi', 255)->nullable()->after('materi_disampaikan');
            }
            if (!Schema::hasColumn('aktivitas_mengajars', 'kendala_kegiatan')) {
                $table->text('kendala_kegiatan')->nullable()->after('catatan');
            }
            if (!Schema::hasColumn('aktivitas_mengajars', 'hasil_pembelajaran')) {
                $table->text('hasil_pembelajaran')->nullable()->after('kendala_kegiatan');
            }
            if (!Schema::hasColumn('aktivitas_mengajars', 'file_dokumentasi')) {
                $table->string('file_dokumentasi', 255)->nullable()->after('hasil_pembelajaran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas_mengajars', function (Blueprint $table) {
            foreach (['tautan_materi', 'kendala_kegiatan', 'hasil_pembelajaran', 'file_dokumentasi'] as $col) {
                if (Schema::hasColumn('aktivitas_mengajars', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('absensis', function (Blueprint $table) {
            if (Schema::hasColumn('absensis', 'is_auto_checkout')) {
                $table->dropColumn('is_auto_checkout');
            }
        });

        Schema::table('jadwals', function (Blueprint $table) {
            if (Schema::hasColumn('jadwals', 'instruktur_pengganti_id')) {
                $table->dropConstrainedForeignId('instruktur_pengganti_id');
            }
            if (Schema::hasColumn('jadwals', 'instruktur_asli_id')) {
                $table->dropConstrainedForeignId('instruktur_asli_id');
            }
            foreach (['alasan_pengalihan', 'dialihkan_pada'] as $col) {
                if (Schema::hasColumn('jadwals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('instruktur_audit_logs');

        Schema::table('instrukturs', function (Blueprint $table) {
            foreach ([
                'riwayat_keahlian',
                'nama_sertifikasi',
                'file_sertifikasi',
                'sertifikasi_berlaku_sampai',
                'status_ketersediaan',
                'cuti_mulai',
                'cuti_selesai',
                'keterangan_cuti',
                'deleted_at',
            ] as $col) {
                if (Schema::hasColumn('instrukturs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
