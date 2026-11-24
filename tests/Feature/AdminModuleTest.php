<?php

namespace Tests\Feature;

use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\InstrukturSertifikasi;
use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name'      => 'Admin Test',
            'email'     => 'admin@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/admin/pengguna')->assertRedirect('/login');
        $this->get('/admin/instruktur')->assertRedirect('/login');
        $this->get('/admin/jadwal')->assertRedirect('/login');
        $this->get('/admin/import')->assertRedirect('/login');
        $this->get('/admin/evaluasi')->assertRedirect('/login');
        $this->get('/admin/kinerja')->assertRedirect('/login');
        $this->get('/admin/laporan')->assertRedirect('/login');
        $this->get('/admin/pengaturan')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $supervisor = User::create([
            'name'      => 'Supervisor Test',
            'email'     => 'spv@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($supervisor)->get('/admin/pengguna')->assertStatus(403);
    }

    public function test_admin_can_login_and_view_dashboard(): void
    {
        $response = $this->post('/login', [
            'email'    => 'admin@lpkcti.id',
            'password' => 'Admin@123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertStatus(200);
    }

    public function test_admin_cannot_change_own_role_or_delete_self(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $this->admin), [
                'name'  => 'Admin Updated',
                'email' => 'admin@lpkcti.id',
                'role'  => 'instruktur',
            ]);

        $this->assertEquals('admin', $this->admin->fresh()->role);

        $this->actingAs($this->admin)
            ->delete(route('admin.pengguna.destroy', $this->admin));

        $this->assertNotNull($this->admin->fresh());
    }

    public function test_jadwal_conflict_validation_blocks_overlapping_schedule(): void
    {
        $ins = Instruktur::create([
            'nama_lengkap'    => 'Budi Santoso',
            'kode_instruktur' => 'INS-0001',
            'email'           => 'budi@lpkcti.id',
            'status'          => 'aktif',
        ]);

        Jadwal::create([
            'instruktur_id' => $ins->id,
            'nama_kelas'    => 'Kelas A',
            'materi'        => 'Bahasa Jepang N5',
            'tanggal'       => '2026-11-10',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id' => $ins->id,
            'nama_kelas'    => 'Kelas B',
            'materi'        => 'Hiragana',
            'tanggal'       => '2026-11-10',
            'jam_mulai'     => '09:00',
            'jam_selesai'   => '11:00',
            'status'        => 'terjadwal',
        ]);

        $response->assertSessionHasErrors('jam_mulai');
        $this->assertEquals(1, Jadwal::count());
    }

    public function test_instruktur_cannot_manipulate_other_instruktur_jadwal_or_documents(): void
    {
        $userA = User::create([
            'name'      => 'Instruktur A',
            'email'     => 'insa@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $insA = $userA->syncInstrukturProfile();

        $userB = User::create([
            'name'      => 'Instruktur B',
            'email'     => 'insb@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $insB = $userB->syncInstrukturProfile();

        $jadwalB = Jadwal::create([
            'instruktur_id' => $insB->id,
            'nama_kelas'    => 'Kelas B',
            'materi'        => 'Jaringan Dasar',
            'tanggal'       => today()->toDateString(),
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // Instruktur A mencoba check-in, check-out, dan update aktivitas milik Instruktur B (IDOR)
        $this->actingAs($userA)
            ->post(route('instruktur.jadwal.checkin', $jadwalB))
            ->assertStatus(403);

        $this->actingAs($userA)
            ->post(route('instruktur.jadwal.checkout', $jadwalB))
            ->assertStatus(403);

        $this->actingAs($userA)
            ->put(route('instruktur.aktivitas.update', $jadwalB), [
                'tanggal'              => today()->toDateString(),
                'jam_mulai_aktual'     => '08:00',
                'jam_selesai_aktual'   => '10:00',
                'materi_disampaikan'   => 'Materi Manipulasi',
                'jumlah_peserta_hadir' => 10,
                'status_konfirmasi'    => 'tidak_terlaksana',
            ])
            ->assertStatus(403);
    }

    public function test_internal_documents_are_stored_on_local_disk_and_blocked_from_public_storage_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $userIns = User::create([
            'name'      => 'Instruktur Dokumen',
            'email'     => 'insdoc@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $ins = $userIns->syncInstrukturProfile();

        // Admin mengunggah sertifikasi -> tersimpan di disk 'local', bukan 'public'
        $certFile = UploadedFile::fake()->create('sertifikat.pdf', 120, 'application/pdf');
        $this->actingAs($this->admin)
            ->post(route('admin.instruktur.sertifikasi.store', $ins), [
                'nama_sertifikasi' => 'BNSP Web Developer',
                'berlaku_sampai'   => '2028-12-31',
                'file_sertifikasi' => $certFile,
            ])
            ->assertRedirect();

        $sert = InstrukturSertifikasi::where('instruktur_id', $ins->id)->firstOrFail();
        Storage::disk('local')->assertExists($sert->file_path);
        Storage::disk('public')->assertMissing($sert->file_path);

        // Route publik /storage/{path} menolak akses ke folder sertifikasi & dokumentasi
        $this->get('/storage/' . $sert->file_path)->assertStatus(403);

        // Admin & pemilik instruktur dapat mengakses lewat route terautentikasi
        $this->actingAs($this->admin)
            ->get(route('admin.instruktur.sertifikasi.dokumen', [$ins, $sert]))
            ->assertStatus(200);

        $this->actingAs($userIns)
            ->get(route('instruktur.profil.sertifikasi.dokumen', $sert))
            ->assertStatus(200);

        // Fallback: file lama yang masih di disk 'public' tetap terbaca lewat route terautentikasi
        Storage::disk('public')->put('aktivitas/dokumentasi/legacy.pdf', 'PDF-LEGACY');
        $jadwal = Jadwal::create([
            'instruktur_id' => $ins->id,
            'nama_kelas'    => 'Kelas Legacy',
            'materi'        => 'Materi Legacy',
            'tanggal'       => today()->toDateString(),
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);
        $akt = AktivitasMengajar::create([
            'jadwal_id'            => $jadwal->id,
            'instruktur_id'        => $ins->id,
            'tanggal'              => today()->toDateString(),
            'jam_mulai_aktual'     => '08:00',
            'jam_selesai_aktual'   => '10:00',
            'materi_disampaikan'   => 'Materi Legacy',
            'jumlah_peserta_hadir' => 15,
            'status_konfirmasi'    => 'dikonfirmasi',
            'file_dokumentasi'     => 'aktivitas/dokumentasi/legacy.pdf',
            'status_verifikasi'    => 'menunggu',
        ]);

        $this->get('/storage/aktivitas/dokumentasi/legacy.pdf')->assertStatus(403);
        $this->actingAs($userIns)
            ->get(route('instruktur.aktivitas.dokumentasi', $akt))
            ->assertStatus(200);
    }

    public function test_deactivating_user_revokes_active_sessions_and_blocks_access(): void
    {
        $userIns = User::create([
            'name'           => 'Instruktur Nonaktif',
            'email'          => 'nonaktif@lpkcti.id',
            'password'       => 'Instruktur@123',
            'role'           => 'instruktur',
            'is_active'      => true,
            'remember_token' => 'token-awal-123',
        ]);
        $userIns->syncInstrukturProfile();

        DB::table('sessions')->insert([
            'id'            => 'sess-test-123',
            'user_id'       => $userIns->id,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'PHPUnit',
            'payload'       => 'test',
            'last_activity' => time(),
        ]);

        // Admin menonaktifkan akun melalui toggle-status
        $this->actingAs($this->admin)
            ->post(route('admin.pengguna.toggle-status', $userIns))
            ->assertRedirect();

        $freshUser = $userIns->fresh();
        $this->assertFalse($freshUser->is_active);
        $this->assertNull($freshUser->remember_token);
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $userIns->id)->count());

        // Jika sesi masih terbawa di request berikutnya, middleware CheckRole langsung memutus sesi
        $this->actingAs($freshUser)
            ->get(route('instruktur.dashboard'))
            ->assertRedirect(route('login'));
    }
}
