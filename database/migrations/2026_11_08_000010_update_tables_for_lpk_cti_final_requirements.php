<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kode_evaluasis', function (Blueprint $table) {
            $table->foreignId('instruktur_id')->nullable()->change();
            $table->string('kode', 50)->change();
            $table->string('nama_kelas', 100)->nullable()->after('kode');
        });

        Schema::table('evaluasis', function (Blueprint $table) {
            $table->foreignId('jadwal_id')->nullable()->after('kode_evaluasi_id')->constrained('jadwals')->nullOnDelete();
            $table->string('materi_evaluasi', 255)->nullable()->after('nama_pengisi');
            $table->text('komentar')->nullable()->after('total_nilai');
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->change();
            $table->string('kode_kelas', 50)->nullable()->after('instruktur_id');
            $table->string('hari', 20)->nullable()->after('materi');
            $table->boolean('is_pola_mingguan')->default(false)->after('tanggal');
            $table->date('periode_mulai')->nullable()->after('is_pola_mingguan');
            $table->date('periode_selesai')->nullable()->after('periode_mulai');

            $table->index(['kode_kelas', 'hari']);
            $table->index(['is_pola_mingguan', 'hari']);
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->time('jam_mulai_jadwal')->nullable()->after('tanggal');
            $table->integer('menit_terlambat')->default(0)->after('jam_checkout');
            $table->timestamp('checkin_at')->nullable()->after('menit_terlambat');
            $table->timestamp('checkout_at')->nullable()->after('checkin_at');
            $table->decimal('latitude_checkin', 10, 7)->nullable()->after('sumber');
            $table->decimal('longitude_checkin', 10, 7)->nullable()->after('latitude_checkin');
            $table->decimal('latitude_checkout', 10, 7)->nullable()->after('longitude_checkin');
            $table->decimal('longitude_checkout', 10, 7)->nullable()->after('latitude_checkout');
            $table->decimal('akurasi_gps', 8, 2)->nullable()->after('longitude_checkout');
            $table->string('status_gps', 30)->nullable()->after('akurasi_gps');

            $table->index(['jadwal_id', 'tanggal']);
        });

        Schema::table('aktivitas_mengajars', function (Blueprint $table) {
            $table->string('status_verifikasi', 30)->default('menunggu')->after('status_konfirmasi');
            $table->foreignId('verified_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('catatan_supervisor')->nullable()->after('verified_at');

            $table->index(['instruktur_id', 'status_verifikasi']);
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas_mengajars', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['instruktur_id', 'status_verifikasi']);
            $table->dropColumn(['status_verifikasi', 'verified_by', 'verified_at', 'catatan_supervisor']);
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->dropIndex(['jadwal_id', 'tanggal']);
            $table->dropColumn([
                'jam_mulai_jadwal',
                'menit_terlambat',
                'checkin_at',
                'checkout_at',
                'latitude_checkin',
                'longitude_checkin',
                'latitude_checkout',
                'longitude_checkout',
                'akurasi_gps',
                'status_gps',
            ]);
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropIndex(['kode_kelas', 'hari']);
            $table->dropIndex(['is_pola_mingguan', 'hari']);
            $table->dropColumn(['kode_kelas', 'hari', 'is_pola_mingguan', 'periode_mulai', 'periode_selesai']);
        });

        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropForeign(['jadwal_id']);
            $table->dropColumn(['jadwal_id', 'materi_evaluasi', 'komentar']);
        });

        Schema::table('kode_evaluasis', function (Blueprint $table) {
            $table->dropColumn(['nama_kelas']);
        });
    }
};
