<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lpkcti.id'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('Admin@123'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@lpkcti.id'],
            [
                'name'      => 'Supervisor LPK',
                'password'  => Hash::make('Supervisor@123'),
                'role'      => 'supervisor',
                'is_active' => true,
            ]
        );

        $settings = [
            ['key' => 'app_name',                   'value' => 'Dashboard Instruktur LPK CTI', 'group' => 'general'],
            ['key' => 'app_description',            'value' => 'Sistem Monitoring dan Evaluasi Kinerja Instruktur', 'group' => 'general'],
            ['key' => 'lpk_name',                   'value' => 'LPK Cipta Tungga Indonesia Ciamis', 'group' => 'general'],
            ['key' => 'lpk_alamat',                 'value' => 'Ciamis, Jawa Barat', 'group' => 'general'],
            ['key' => 'default_periode',            'value' => 'bulan_ini', 'group' => 'general'],
            ['key' => 'session_timeout',            'value' => '120', 'group' => 'security'],
            ['key' => 'max_login_attempts',         'value' => '5', 'group' => 'security'],
            ['key' => 'toleransi_terlambat_menit',  'value' => '15', 'group' => 'absensi'],
            ['key' => 'checkin_buka_sebelum_menit', 'value' => '30', 'group' => 'absensi'],
            ['key' => 'lpk_latitude',               'value' => '-7.3308868', 'group' => 'absensi'],
            ['key' => 'lpk_longitude',              'value' => '108.3560168', 'group' => 'absensi'],
            ['key' => 'radius_lokasi_meter',        'value' => '150', 'group' => 'absensi'],
            ['key' => 'kinerja_rule_enabled',       'value' => '1', 'group' => 'kinerja'],
            ['key' => 'bobot_kehadiran',            'value' => '30', 'group' => 'kinerja'],
            ['key' => 'bobot_aktivitas',            'value' => '20', 'group' => 'kinerja'],
            ['key' => 'bobot_evaluasi',             'value' => '30', 'group' => 'kinerja'],
            ['key' => 'bobot_observasi',            'value' => '20', 'group' => 'kinerja'],
            ['key' => 'bobot_absensi',              'value' => '30', 'group' => 'kinerja'],
            ['key' => 'batas_sangat_baik',          'value' => '85', 'group' => 'kinerja'],
            ['key' => 'batas_baik',                 'value' => '75', 'group' => 'kinerja'],
            ['key' => 'batas_cukup',                'value' => '60', 'group' => 'kinerja'],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['group']);
        }

        $this->call(LpkCtiJadwalMingguanSeeder::class);
    }
}
