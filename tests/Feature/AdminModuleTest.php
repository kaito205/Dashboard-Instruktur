<?php

namespace Tests\Feature;

use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
