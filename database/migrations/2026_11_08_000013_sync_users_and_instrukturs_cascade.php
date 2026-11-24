<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan data dummy lama (Ahmad Fauzi INS-0001, Siti Nurhaliza INS-0002, Test User)
        // serta baris instruktur yatim (orphan) yang akun user-nya sudah dihapus di database.
        $dummyInstrukturIds = DB::table('instrukturs')
            ->whereIn('kode_instruktur', ['INS-0001', 'INS-0002'])
            ->orWhereIn('email', ['ahmad.fauzi@lpkcti.id', 'siti.nurhaliza@lpkcti.id'])
            ->orWhereNull('user_id')
            ->pluck('id')
            ->toArray();

        if (!empty($dummyInstrukturIds)) {
            DB::table('kode_evaluasis')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            DB::table('evaluasis')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            DB::table('aktivitas_mengajars')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            DB::table('absensis')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            DB::table('jadwals')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            if (Schema::hasTable('instruktur_audit_logs')) {
                DB::table('instruktur_audit_logs')->whereIn('instruktur_id', $dummyInstrukturIds)->delete();
            }
            DB::table('instrukturs')->whereIn('id', $dummyInstrukturIds)->delete();
        }

        DB::table('kode_evaluasis')->where('kode', 'EV-CTI00001')->delete();

        DB::table('users')
            ->whereIn('email', [
                'ahmad.fauzi@lpkcti.id',
                'siti.nurhaliza@lpkcti.id',
                'test@example.com',
            ])
            ->delete();

        // Hapus juga baris instruktur yang user_id-nya tidak lagi ditemukan di tabel users
        $validUserIds = DB::table('users')->pluck('id')->toArray();
        if (!empty($validUserIds)) {
            DB::table('instrukturs')->whereNotIn('user_id', $validUserIds)->delete();
        }

        // 2. Ubah foreign key user_id pada tabel instrukturs dari nullOnDelete menjadi cascadeOnDelete
        // agar penghapusan akun instruktur langsung di tabel users otomatis menghapus profilnya di instrukturs.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('instrukturs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table('instrukturs', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('instrukturs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table('instrukturs', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }
};
