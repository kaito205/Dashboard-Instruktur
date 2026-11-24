<?php

namespace Database\Seeders;

use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\KodeEvaluasi;
use App\Models\ObservasiSupervisor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LpkCtiJadwalMingguanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed 4 Instruktur Resmi LPK CTI Agustus 2026 (Idempotent via email & kode_instruktur)
        $instrukturData = [
            'sandi' => [
                'kode_instruktur' => 'INS-CTI01',
                'nama_lengkap'    => 'Sandi Yana, S.Kom',
                'email'           => 'sandi.yana@lpkcti.id',
                'no_telepon'      => '081220001001',
                'alamat'          => 'LPK Cipta Tungga Indonesia, Ciamis',
                'bidang_keahlian' => 'Jaringan Komputer & Mikrotik',
            ],
            'rustin' => [
                'kode_instruktur' => 'INS-CTI02',
                'nama_lengkap'    => 'Rustin Kania Dewi, S.Kom',
                'email'           => 'rustin.kania@lpkcti.id',
                'no_telepon'      => '081220001002',
                'alamat'          => 'LPK Cipta Tungga Indonesia, Ciamis',
                'bidang_keahlian' => 'Microsoft Office & Administrasi Perkantoran',
            ],
            'jubaedi' => [
                'kode_instruktur' => 'INS-CTI03',
                'nama_lengkap'    => 'Jubaedi Saleh, A.Md.Kom',
                'email'           => 'jubaedi.saleh@lpkcti.id',
                'no_telepon'      => '081220001003',
                'alamat'          => 'LPK Cipta Tungga Indonesia, Ciamis',
                'bidang_keahlian' => 'Public Speaking & Pemrograman',
            ],
            'magang' => [
                'kode_instruktur' => 'INS-CTI04',
                'nama_lengkap'    => 'Mahasiswa Magang',
                'email'           => 'mahasiswa.magang@lpkcti.id',
                'no_telepon'      => '081220001004',
                'alamat'          => 'LPK Cipta Tungga Indonesia, Ciamis',
                'bidang_keahlian' => 'Pemrograman & Sharing Session',
            ],
        ];

        $instrukturs = [];
        foreach ($instrukturData as $key => $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name'      => $row['nama_lengkap'],
                    'password'  => Hash::make('Instruktur@123'),
                    'role'      => 'instruktur',
                    'is_active' => true,
                ]
            );

            $instrukturs[$key] = Instruktur::updateOrCreate(
                ['kode_instruktur' => $row['kode_instruktur']],
                [
                    'user_id'         => $user->id,
                    'nama_lengkap'    => $row['nama_lengkap'],
                    'email'           => $row['email'],
                    'no_telepon'      => $row['no_telepon'],
                    'alamat'          => $row['alamat'],
                    'bidang_keahlian' => $row['bidang_keahlian'],
                    'status'          => 'aktif',
                ]
            );
        }

        // 2. Seed 9 Kode Evaluasi Kelas Bersama LPK CTI (Idempotent via kode)
        // Relasi instruktur_id dibuat nullable untuk kelas yang diampu beberapa instruktur (RPL, AP1, AP2, AP3)
        $kodeKelasList = [
            [
                'kode'          => 'RPL',
                'nama_kelas'    => 'RPL',
                'instruktur_id' => null,
                'keterangan'    => 'Kelas Rekayasa Perangkat Lunak (Pemrograman - Mahasiswa Magang & Jubaedi Saleh, A.Md.Kom)',
            ],
            [
                'kode'          => 'TKJ1',
                'nama_kelas'    => 'TKJ1',
                'instruktur_id' => $instrukturs['sandi']->id,
                'keterangan'    => 'Kelas Teknik Komputer & Jaringan 1 (Jaringan Komputer & Mikrotik - Sandi Yana, S.Kom)',
            ],
            [
                'kode'          => 'TKJ2',
                'nama_kelas'    => 'TKJ2',
                'instruktur_id' => $instrukturs['sandi']->id,
                'keterangan'    => 'Kelas Teknik Komputer & Jaringan 2 (Jaringan Komputer & Mikrotik - Sandi Yana, S.Kom)',
            ],
            [
                'kode'          => 'TKJ3',
                'nama_kelas'    => 'TKJ3',
                'instruktur_id' => $instrukturs['sandi']->id,
                'keterangan'    => 'Kelas Teknik Komputer & Jaringan 3 (Jaringan Komputer & Mikrotik - Sandi Yana, S.Kom)',
            ],
            [
                'kode'          => 'AP1',
                'nama_kelas'    => 'AP1',
                'instruktur_id' => null,
                'keterangan'    => 'Kelas Administrasi Perkantoran 1 (Microsoft Office & Sharing Session)',
            ],
            [
                'kode'          => 'AP2',
                'nama_kelas'    => 'AP2',
                'instruktur_id' => null,
                'keterangan'    => 'Kelas Administrasi Perkantoran 2 (Microsoft Office & Sharing Session)',
            ],
            [
                'kode'          => 'AP3',
                'nama_kelas'    => 'AP3',
                'instruktur_id' => null,
                'keterangan'    => 'Kelas Administrasi Perkantoran 3 (Microsoft Office & Sharing Session)',
            ],
            [
                'kode'          => 'PS1',
                'nama_kelas'    => 'PS1',
                'instruktur_id' => $instrukturs['jubaedi']->id,
                'keterangan'    => 'Kelas Public Speaking 1 (Public Speaking - Jubaedi Saleh, A.Md.Kom)',
            ],
            [
                'kode'          => 'PS2',
                'nama_kelas'    => 'PS2',
                'instruktur_id' => $instrukturs['jubaedi']->id,
                'keterangan'    => 'Kelas Public Speaking 2 (Public Speaking - Jubaedi Saleh, A.Md.Kom)',
            ],
        ];

        foreach ($kodeKelasList as $item) {
            KodeEvaluasi::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama_kelas'    => $item['nama_kelas'],
                    'instruktur_id' => $item['instruktur_id'],
                    'is_active'     => true,
                    'max_pengisian' => null,
                    'expired_at'    => null,
                    'keterangan'    => $item['keterangan'],
                ]
            );
        }

        // 3. Seed 19 Pola Jadwal Mingguan LPK CTI Agustus 2026 (Idempotent, tanpa mengarang tanggal mulai/berakhir)
        $polaMingguan = [
            // SENIN
            ['hari' => 'Senin',  'kode_kelas' => 'RPL',  'materi' => 'Pemrograman',       'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'ruangan' => 'L-01', 'instruktur' => 'magang'],
            ['hari' => 'Senin',  'kode_kelas' => 'TKJ1', 'materi' => 'Jaringan Komputer', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],
            ['hari' => 'Senin',  'kode_kelas' => 'TKJ2', 'materi' => 'Jaringan Komputer', 'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],
            ['hari' => 'Senin',  'kode_kelas' => 'TKJ3', 'materi' => 'Jaringan Komputer', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],

            // SELASA
            ['hari' => 'Selasa', 'kode_kelas' => 'AP1',  'materi' => 'Microsoft Office',  'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],
            ['hari' => 'Selasa', 'kode_kelas' => 'AP2',  'materi' => 'Microsoft Office',  'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],
            ['hari' => 'Selasa', 'kode_kelas' => 'AP1',  'materi' => 'Sharing Session',   'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'R-01', 'instruktur' => 'magang'],
            ['hari' => 'Selasa', 'kode_kelas' => 'AP3',  'materi' => 'Microsoft Office',  'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],

            // RABU
            ['hari' => 'Rabu',   'kode_kelas' => 'PS2',  'materi' => 'Public Speaking',   'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'L-01', 'instruktur' => 'jubaedi'],
            ['hari' => 'Rabu',   'kode_kelas' => 'PS1',  'materi' => 'Public Speaking',   'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'L-01', 'instruktur' => 'jubaedi'],
            ['hari' => 'Rabu',   'kode_kelas' => 'TKJ2', 'materi' => 'Mikrotik',          'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],
            ['hari' => 'Rabu',   'kode_kelas' => 'TKJ3', 'materi' => 'Mikrotik',          'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],
            ['hari' => 'Rabu',   'kode_kelas' => 'TKJ1', 'materi' => 'Mikrotik',          'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'R-01', 'instruktur' => 'sandi'],

            // KAMIS
            ['hari' => 'Kamis',  'kode_kelas' => 'AP3',  'materi' => 'Microsoft Office',  'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],
            ['hari' => 'Kamis',  'kode_kelas' => 'AP1',  'materi' => 'Microsoft Office',  'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],
            ['hari' => 'Kamis',  'kode_kelas' => 'AP2',  'materi' => 'Sharing Session',   'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'R-01', 'instruktur' => 'magang'],
            ['hari' => 'Kamis',  'kode_kelas' => 'AP2',  'materi' => 'Microsoft Office',  'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'L-01', 'instruktur' => 'rustin'],

            // SABTU
            ['hari' => 'Sabtu',  'kode_kelas' => 'RPL',  'materi' => 'Pemrograman',       'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => 'L-01', 'instruktur' => 'jubaedi'],
            ['hari' => 'Sabtu',  'kode_kelas' => 'AP3',  'materi' => 'Sharing Session',   'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'R-01', 'instruktur' => 'magang'],
        ];

        foreach ($polaMingguan as $slot) {
            $ins = $instrukturs[$slot['instruktur']];

            Jadwal::updateOrCreate(
                [
                    'is_pola_mingguan' => true,
                    'hari'             => $slot['hari'],
                    'kode_kelas'       => $slot['kode_kelas'],
                    'materi'           => $slot['materi'],
                    'jam_mulai'        => $slot['jam_mulai'],
                    'jam_selesai'      => $slot['jam_selesai'],
                    'ruangan'          => $slot['ruangan'],
                    'instruktur_id'    => $ins->id,
                ],
                [
                    'nama_kelas'      => $slot['kode_kelas'],
                    'tanggal'         => null,
                    'periode_mulai'   => null,
                    'periode_selesai' => null,
                    'jumlah_peserta'  => 20,
                    'status'          => 'terjadwal',
                    'keterangan'      => 'Pola Jadwal Mingguan LPK CTI Agustus 2026 (' . (Jadwal::RUANGAN_LPK[$slot['ruangan']] ?? $slot['ruangan']) . ')',
                ]
            );
        }

        // 4. Pengaturan Dinamis sesuai Keputusan Final LPK CTI
        $settings = [
            ['key' => 'lpk_name',                      'value' => 'LPK Cipta Tungga Indonesia Ciamis', 'group' => 'general'],
            ['key' => 'toleransi_terlambat_menit',     'value' => '15', 'group' => 'absensi'],
            ['key' => 'checkin_buka_sebelum_menit',    'value' => '30', 'group' => 'absensi'],
            ['key' => 'kinerja_rule_enabled',          'value' => '1',  'group' => 'kinerja'],
            ['key' => 'bobot_kehadiran',               'value' => '30', 'group' => 'kinerja'],
            ['key' => 'bobot_aktivitas',               'value' => '20', 'group' => 'kinerja'],
            ['key' => 'bobot_evaluasi',                'value' => '30', 'group' => 'kinerja'],
            ['key' => 'bobot_observasi',               'value' => '20', 'group' => 'kinerja'],
            ['key' => 'bobot_absensi',                 'value' => '30', 'group' => 'kinerja'],
            ['key' => 'batas_sangat_baik',             'value' => '85', 'group' => 'kinerja'],
            ['key' => 'batas_baik',                    'value' => '75', 'group' => 'kinerja'],
            ['key' => 'batas_cukup',                   'value' => '60', 'group' => 'kinerja'],
            [
                'key'   => 'ruangan_lpk',
                'value' => json_encode(Jadwal::RUANGAN_LPK, JSON_UNESCAPED_UNICODE),
                'group' => 'general',
            ],
            [
                'key'   => 'indikator_evaluasi_peserta',
                'value' => json_encode(Evaluasi::getIndikatorLabels(), JSON_UNESCAPED_UNICODE),
                'group' => 'evaluasi',
            ],
            [
                'key'   => 'indikator_observasi_supervisor',
                'value' => json_encode(ObservasiSupervisor::getIndikatorLabels(), JSON_UNESCAPED_UNICODE),
                'group' => 'observasi',
            ],
        ];

        foreach ($settings as $s) {
            Setting::set($s['key'], $s['value'], $s['group']);
        }
    }
}
