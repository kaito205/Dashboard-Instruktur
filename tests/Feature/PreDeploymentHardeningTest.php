<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ImportController;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LpkCtiJadwalMingguanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreDeploymentHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_uses_config_never_creates_empty_or_public_default_password_and_excludes_demo_data(): void
    {
        // Simulasikan kondisi config:cache di mana variabel initial_passwords kosong
        config([
            'auth.initial_passwords.admin'      => '',
            'auth.initial_passwords.supervisor' => null,
        ]);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@lpkcti.id')->firstOrFail();
        $spv   = User::where('email', 'supervisor@lpkcti.id')->firstOrFail();

        // Tidak boleh password kosong ("") dan tidak boleh password publik lama ("Admin@123" / "Supervisor@123")
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertFalse(Hash::check('Admin@123', $admin->password));
        $this->assertFalse(Hash::check('', $spv->password));
        $this->assertFalse(Hash::check('Supervisor@123', $spv->password));

        // Seeder produksi tidak boleh menyisipkan akun contoh "Mahasiswa Magang"
        $this->assertNull(User::where('email', 'mahasiswa.magang@lpkcti.id')->first());
        $this->assertSame(0, Instruktur::count());

        // Jika config diisi secara eksplisit, seeder menggunakan nilai config tersebut
        User::query()->delete();
        config([
            'auth.initial_passwords.admin'      => 'KuatUnikAdmin#2026',
            'auth.initial_passwords.supervisor' => 'KuatUnikSpv#2026',
        ]);

        $this->seed(AdminSeeder::class);
        $this->assertTrue(Hash::check('KuatUnikAdmin#2026', User::where('email', 'admin@lpkcti.id')->firstOrFail()->password));
        $this->assertTrue(Hash::check('KuatUnikSpv#2026', User::where('email', 'supervisor@lpkcti.id')->firstOrFail()->password));
    }

    public function test_seeder_rerun_preserves_customized_kpi_settings_and_existing_account_attributes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@lpkcti.id')->firstOrFail();
        $spv   = User::where('email', 'supervisor@lpkcti.id')->firstOrFail();

        // Kustomisasi nama, status aktif, dan password pada akun yang sudah ada
        $admin->update([
            'name'     => 'Budi Raharjo (Admin Utama)',
            'password' => Hash::make('PasswordBaruAdmin#99'),
        ]);
        $spv->update([
            'name'      => 'Sari Dewi (Supervisor Cuti)',
            'is_active' => false,
            'password'  => Hash::make('PasswordBaruSpv#88'),
        ]);

        // Kustomisasi bobot KPI, batas predikat, dan identitas LPK oleh Admin
        Setting::set('bobot_kehadiran', '40', 'kinerja');
        Setting::set('bobot_aktivitas', '15', 'kinerja');
        Setting::set('bobot_evaluasi', '25', 'kinerja');
        Setting::set('bobot_observasi', '20', 'kinerja');
        Setting::set('bobot_absensi', '40', 'kinerja');
        Setting::set('batas_sangat_baik', '90', 'kinerja');
        Setting::set('toleransi_terlambat_menit', '10', 'absensi');
        Setting::set('lpk_name', 'LPK CTI Kampus Pusat Ciamis', 'general');

        // Hapus satu kunci untuk memastikan kunci yang belum ada tetap dibuat saat re-seed
        Setting::where('key', 'batas_cukup')->delete();
        Setting::clearMemo();

        // Jalankan ulang DatabaseSeeder dan LpkCtiJadwalMingguanSeeder
        $this->seed(DatabaseSeeder::class);
        $this->seed(LpkCtiJadwalMingguanSeeder::class);
        Setting::clearMemo();

        $adminAfter = User::where('email', 'admin@lpkcti.id')->firstOrFail();
        $spvAfter   = User::where('email', 'supervisor@lpkcti.id')->firstOrFail();

        // Nama, status is_active, dan password akun lama tidak boleh tertimpa
        $this->assertSame('Budi Raharjo (Admin Utama)', $adminAfter->name);
        $this->assertTrue((bool) $adminAfter->is_active);
        $this->assertTrue(Hash::check('PasswordBaruAdmin#99', $adminAfter->password));

        $this->assertSame('Sari Dewi (Supervisor Cuti)', $spvAfter->name);
        $this->assertFalse((bool) $spvAfter->is_active);
        $this->assertTrue(Hash::check('PasswordBaruSpv#88', $spvAfter->password));

        // Seluruh pengaturan KPI dan identitas yang sudah dikustomisasi tetap utuh
        $this->assertSame('40', Setting::get('bobot_kehadiran'));
        $this->assertSame('15', Setting::get('bobot_aktivitas'));
        $this->assertSame('25', Setting::get('bobot_evaluasi'));
        $this->assertSame('20', Setting::get('bobot_observasi'));
        $this->assertSame('40', Setting::get('bobot_absensi'));
        $this->assertSame('90', Setting::get('batas_sangat_baik'));
        $this->assertSame('10', Setting::get('toleransi_terlambat_menit'));
        $this->assertSame('LPK CTI Kampus Pusat Ciamis', Setting::get('lpk_name'));

        // Pengaturan default yang sebelumnya belum tersedia berhasil ditambahkan
        $this->assertSame('60', Setting::get('batas_cukup'));
    }

    public function test_import_preview_cancel_and_scheduler_cleanup_respect_active_import_lock(): void
    {
        Storage::fake('local');

        $admin = User::create([
            'name'      => 'Admin Import Lock',
            'email'     => 'admin.lock@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        Instruktur::create([
            'kode_instruktur' => 'INS-CTI50',
            'nama_lengkap'    => 'Teguh Santoso',
            'email'           => 'teguh@lpkcti.id',
            'status'          => 'aktif',
        ]);

        $csvContent = "nama_instruktur,tanggal,jam_checkin,jam_checkout,status,keterangan\nTeguh Santoso,2026-11-18,08:00,10:00,hadir,Sesi Pagi";
        $file       = UploadedFile::fake()->createWithContent('absensi_uji.csv', $csvContent);

        $previewRes = $this->actingAs($admin)->post(route('admin.import.preview'), [
            'jenis' => 'absensi',
            'file'  => $file,
        ]);
        $previewRes->assertOk();
        $token = $previewRes->viewData('previewToken');
        $this->assertNotEmpty($token);

        $tokenPath = "import_previews/preview_user_{$admin->id}_{$token}.json";
        Storage::disk('local')->assertExists($tokenPath);

        // 1. Majukan waktu 65 menit (melewati batas 60 menit), lalu simulasikan proses import aktif yang memegang lock (600 detik)
        $this->travel(65)->minutes();
        $activeLock = Cache::lock(ImportController::previewLockKey($tokenPath), 600);
        $this->assertTrue($activeLock->get());

        try {
            $this->actingAs($admin)
                ->post(route('admin.import.batal'), ['preview_token' => $token])
                ->assertSessionHas('warning');

            Storage::disk('local')->assertExists($tokenPath);

            Artisan::call('import:cleanup-previews', ['--minutes' => 60]);
            Storage::disk('local')->assertExists($tokenPath);
        } finally {
            $activeLock->release();
        }

        // 2. Setelah lock dilepas, scheduler membersihkan berkas kedaluwarsa dengan aman
        Artisan::call('import:cleanup-previews', ['--minutes' => 60]);
        Storage::disk('local')->assertMissing($tokenPath);
        $this->travelBack();

        // 3. Uji tombol Batal menghapus berkas pratinjau segera saat tidak terkunci
        $previewRes2 = $this->actingAs($admin)->post(route('admin.import.preview'), [
            'jenis' => 'absensi',
            'file'  => UploadedFile::fake()->createWithContent('absensi_uji2.csv', $csvContent),
        ]);
        $token2     = $previewRes2->viewData('previewToken');
        $tokenPath2 = "import_previews/preview_user_{$admin->id}_{$token2}.json";
        Storage::disk('local')->assertExists($tokenPath2);

        $this->actingAs($admin)
            ->post(route('admin.import.batal'), ['preview_token' => $token2])
            ->assertRedirect(route('admin.import.index'))
            ->assertSessionHas('success');

        Storage::disk('local')->assertMissing($tokenPath2);
    }

    public function test_schema_guard_keeps_pages_working_when_instruktur_cutis_table_not_yet_migrated(): void
    {
        $admin = User::create([
            'name'      => 'Admin Guard',
            'email'     => 'admin.guard@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $insUser = User::create([
            'name'      => 'Instruktur Guard',
            'email'     => 'ins.guard@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $ins = $insUser->syncInstrukturProfile('Jaringan');

        Jadwal::create([
            'instruktur_id' => $ins->id,
            'nama_kelas'    => 'TKJ Guard',
            'materi'        => 'Mikrotik',
            'tanggal'       => today()->toDateString(),
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // Simulasikan kondisi zero-downtime deploy sebelum php artisan migrate membuat tabel instruktur_cutis
        Schema::dropIfExists('instruktur_cutis');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.jadwal.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.kehadiran.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.kinerja.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.instruktur.show', $ins))->assertOk();
        $this->actingAs($insUser)->get(route('instruktur.dashboard'))->assertOk();
        $this->actingAs($insUser)->get(route('instruktur.jadwal.index'))->assertOk();
    }
}
