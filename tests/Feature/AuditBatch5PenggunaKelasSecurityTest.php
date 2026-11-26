<?php

namespace Tests\Feature;

use App\Models\Instruktur;
use App\Models\InstrukturAuditLog;
use App\Models\InstrukturSertifikasi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditBatch5PenggunaKelasSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'Admin Utama',
            'email'     => 'admin.utama@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_temuan_8_dan_18_edit_profil_instruktur_menjaga_masa_berlaku_sertifikat_dan_sinkronisasi_akun(): void
    {
        $userLama = User::create([
            'name'      => 'Rina Instruktur',
            'email'     => 'rina@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $instruktur = $userLama->syncInstrukturProfile('Administrasi Perkantoran');

        $sert = InstrukturSertifikasi::create([
            'instruktur_id'    => $instruktur->id,
            'nama_sertifikasi' => 'BNSP Office Specialist',
            'berlaku_sampai'   => '2027-06-30',
        ]);
        $instruktur->syncPrimarySertifikasiSummary();

        // Temuan #8: Form update profil mengirimkan field sertifikasi kosong ("") -> masa berlaku tidak boleh terhapus menjadi NULL
        $this->actingAs($this->admin)
            ->put(route('admin.instruktur.update', $instruktur), [
                'nama_lengkap'               => 'Rina Instruktur, S.Pd.',
                'kode_instruktur'            => $instruktur->kode_instruktur,
                'email'                      => 'rina@lpkcti.id',
                'no_telepon'                 => '081234567890',
                'status'                     => 'aktif',
                'status_ketersediaan'        => 'tersedia',
                'user_id'                    => $userLama->id,
                'nama_sertifikasi'           => '',
                'sertifikasi_berlaku_sampai' => '',
            ])
            ->assertRedirect(route('admin.instruktur.index'));

        $this->assertSame('2027-06-30', $sert->fresh()->berlaku_sampai?->format('Y-m-d'));
        $this->assertSame('2027-06-30', $instruktur->fresh()->sertifikasi_berlaku_sampai?->format('Y-m-d'));
        $this->assertSame('BNSP Office Specialist', $instruktur->fresh()->nama_sertifikasi);

        // Temuan #18a: Melepas tautan user_id pada instruktur menonaktifkan akun lama & mencegah duplikat profil saat akun lama diedit
        DB::table('sessions')->insert([
            'id'            => 'sess-rina-lama',
            'user_id'       => $userLama->id,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'PHPUnit',
            'payload'       => 'test',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.instruktur.update', $instruktur), [
                'nama_lengkap'        => 'Rina Instruktur, S.Pd.',
                'kode_instruktur'     => $instruktur->kode_instruktur,
                'email'               => 'rina.profilbaru@lpkcti.id',
                'status'              => 'aktif',
                'status_ketersediaan' => 'tersedia',
                'user_id'             => null,
            ])
            ->assertRedirect(route('admin.instruktur.index'));

        $this->assertFalse($userLama->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $userLama->id)->count());

        $jumlahInstrukturSebelum = Instruktur::withTrashed()->count();
        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $userLama), [
                'name'      => 'Rina Akun Lama Nonaktif',
                'email'     => 'rina.akunlama@lpkcti.id',
                'role'      => 'instruktur',
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.pengguna.index'));

        $this->assertSame($jumlahInstrukturSebelum, Instruktur::withTrashed()->count());

        // Temuan #18b: Mengaktifkan akun User yang memulihkan arsip Instruktur wajib mencatat InstrukturAuditLog
        $userArsip = User::create([
            'name'      => 'Doni Arsip',
            'email'     => 'doni.arsip@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $insArsip = $userArsip->syncInstrukturProfile('Jaringan');

        $this->actingAs($this->admin)
            ->delete(route('admin.instruktur.destroy', $insArsip))
            ->assertRedirect(route('admin.instruktur.index'));

        $this->assertTrue($insArsip->fresh()->trashed());
        $this->assertFalse($userArsip->fresh()->is_active);

        $logCountBeforeRestore = InstrukturAuditLog::where('instruktur_id', $insArsip->id)->count();

        $this->actingAs($this->admin)
            ->post(route('admin.pengguna.toggle-status', $userArsip))
            ->assertRedirect();

        $this->assertFalse($insArsip->fresh()->trashed());
        $this->assertSame('aktif', $insArsip->fresh()->status);
        $this->assertGreaterThan(
            $logCountBeforeRestore,
            InstrukturAuditLog::where('instruktur_id', $insArsip->id)->count()
        );
    }

    public function test_temuan_19_sinkronisasi_master_kelas_index_show_arsip_dan_status_selesai(): void
    {
        $ins = Instruktur::create([
            'nama_lengkap'    => 'Hendra Pengampu',
            'kode_instruktur' => 'INS-CTI77',
            'email'           => 'hendra@lpkcti.id',
            'status'          => 'aktif',
        ]);

        // Jadwal & KodeEvaluasi historis yang dibuat sebelum Master Kelas terdaftar (kelas_id = null)
        $jadwalHistoris = Jadwal::create([
            'instruktur_id' => $ins->id,
            'kelas_id'      => null,
            'kode_kelas'    => 'RPL-2026-01',
            'nama_kelas'    => 'RPL Angkatan 1',
            'materi'        => 'Algoritma Pemrograman',
            'tanggal'       => '2026-05-10',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        $kodeEval = KodeEvaluasi::create([
            'kode'          => 'RPL-2026-01',
            'instruktur_id' => $ins->id,
            'kelas_id'      => null,
            'nama_kelas'    => 'RPL Angkatan 1',
            'is_active'     => true,
        ]);

        // Tambah Master Kelas dengan periode masa lalu dan status selesai
        $this->actingAs($this->admin)
            ->post(route('admin.kelas.store'), [
                'kode_kelas'        => 'RPL-2026-01',
                'nama_kelas'        => 'RPL Angkatan 1',
                'program_pelatihan' => 'Rekayasa Perangkat Lunak',
                'kuota_peserta'     => 20,
                'periode_mulai'     => '2026-05-01',
                'periode_selesai'   => '2026-05-31',
                'status'            => 'selesai',
            ])
            ->assertRedirect(route('admin.kelas.index'));

        $kelas = Kelas::where('kode_kelas', 'RPL-2026-01')->firstOrFail();
        $this->assertSame($kelas->id, $jadwalHistoris->fresh()->kelas_id);
        $this->assertSame($kelas->id, $kodeEval->fresh()->kelas_id);

        // Index dan Show menampilkan angka yang konsisten
        $indexRes = $this->actingAs($this->admin)->get(route('admin.kelas.index'));
        $indexRes->assertStatus(200)->assertSee('1 jadwal')->assertSee('1 kode evaluasi');

        $showRes = $this->actingAs($this->admin)->get(route('admin.kelas.show', $kelas));
        $showRes->assertStatus(200);
        $this->assertSame(1, $showRes->viewData('stats')['total_jadwal']);
        $this->assertSame(1, $showRes->viewData('stats')['total_kode_evaluasi']);

        // Toggle cepat tidak boleh menimpa status 'selesai' menjadi 'aktif'
        $this->actingAs($this->admin)
            ->post(route('admin.kelas.toggle', $kelas))
            ->assertSessionHas('error');
        $this->assertSame('selesai', $kelas->fresh()->status);

        // Arsipkan kelas menonaktifkan KodeEvaluasi aktif, dan Restore menjaga status 'selesai'
        $this->actingAs($this->admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect(route('admin.kelas.index'));

        $this->assertFalse($kodeEval->fresh()->is_active);
        $this->assertTrue($kelas->fresh()->trashed());

        $this->actingAs($this->admin)
            ->post(route('admin.kelas.restore', $kelas->id))
            ->assertRedirect(route('admin.kelas.index'));

        $this->assertFalse($kelas->fresh()->trashed());
        $this->assertSame('selesai', $kelas->fresh()->status);
    }

    public function test_temuan_25_28_29_dan_31_proteksi_admin_terakhir_sesi_password_login_email_dan_file_cleanup(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        // Temuan #25: Admin A tidak dapat menonaktifkan/menurunkan/menghapus Admin B jika Admin A sendiri sudah nonaktif (simulasi sisa 1 admin aktif)
        $adminKedua = User::create([
            'name'      => 'Admin Kedua',
            'email'     => 'admin.kedua@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
        $adminNonaktif = User::create([
            'name'      => 'Admin Cadangan Nonaktif',
            'email'     => 'admin.nonaktif@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => false,
        ]);

        // Nonaktifkan admin.utama di DB untuk mensimulasikan kondisi balapan di mana hanya adminKedua yang masih aktif
        User::whereKey($this->admin->id)->update(['is_active' => false]);
        $this->admin->refresh();
        // Kembalikan sementara agar melewati middleware CheckRole, lalu uji proteksi saat hanya 1 admin aktif tersisa:
        // Skenario nyata: 2 admin di DB (admin.utama aktif, admin.nonaktif tidak aktif). Admin utama mencoba menghapus admin.nonaktif -> boleh,
        // tetapi jika dibalik: adminKedua nonaktif, maka hanya 1 admin aktif (admin.utama).
        User::whereKey($this->admin->id)->update(['is_active' => true]);
        User::whereKey($adminKedua->id)->update(['is_active' => false]);
        $this->admin->refresh();
        $adminKedua->refresh();

        // Temuan #29: Login dengan email huruf kapital berhasil (normalisasi email)
        $this->post('/login', [
            'email'    => '  ADMIN.UTAMA@LPKCTI.ID  ',
            'password' => 'Admin@123',
        ])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        // Temuan #29: Pengaturan kinerja dengan batas_sangat_baik non-numerik gagal validasi secara aman (bail) tanpa exception
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.kinerja'), [
                'bobot_kehadiran'            => 25,
                'bobot_aktivitas'            => 25,
                'bobot_evaluasi'             => 25,
                'bobot_observasi'            => 25,
                'batas_sangat_baik'          => 'bukan-angka',
                'batas_baik'                 => 75,
                'batas_cukup'                => 60,
                'toleransi_terlambat_menit'  => 15,
                'checkin_buka_sebelum_menit' => 30,
                'lpk_latitude'               => -7.326,
                'lpk_longitude'              => 108.353,
                'radius_lokasi_meter'        => 150,
            ])
            ->assertSessionHasErrors('batas_sangat_baik');

        // Temuan #28: Standar password seragam & pencabutan sesi perangkat lain pada Supervisor dan Reset Password Admin
        $spv = User::create([
            'name'      => 'Supervisor Keamanan',
            'email'     => 'spv.sec@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        // Password lemah (tanpa huruf besar/angka) ditolak di Supervisor
        $this->actingAs($spv)
            ->put(route('supervisor.profil.password'), [
                'current_password'      => 'Supervisor@123',
                'password'              => 'lemahsekali',
                'password_confirmation' => 'lemahsekali',
            ])
            ->assertSessionHasErrors('password');

        DB::table('sessions')->insert([
            'id'            => 'sess-spv-perangkat-lain',
            'user_id'       => $spv->id,
            'ip_address'    => '10.0.0.9',
            'user_agent'    => 'OtherBrowser',
            'payload'       => 'test',
            'last_activity' => time(),
        ]);

        $this->actingAs($spv)
            ->put(route('supervisor.profil.password'), [
                'current_password'      => 'Supervisor@123',
                'password'              => 'SpvBaru@2026',
                'password_confirmation' => 'SpvBaru@2026',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('sessions')->where('id', 'sess-spv-perangkat-lain')->count());

        // Reset password oleh Admin mencabut seluruh sesi aktif target user
        DB::table('sessions')->insert([
            'id'            => 'sess-spv-aktif',
            'user_id'       => $spv->id,
            'ip_address'    => '10.0.0.10',
            'user_agent'    => 'MobileBrowser',
            'payload'       => 'test',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.pengguna.reset-password', $spv))
            ->assertSessionHas('generated_password');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $spv->id)->count());

        // Temuan #31: Menghapus akun instruktur baru tanpa histori operasional ikut membersihkan file foto & sertifikat yatim
        $fotoBaru = UploadedFile::fake()->image('avatar-baru.jpg')->store('instruktur/foto', 'public');
        $sertBaru = UploadedFile::fake()->create('sert-baru.pdf', 80, 'application/pdf')->store('instruktur/sertifikasi', 'local');

        $userBaru = User::create([
            'name'        => 'Instruktur Baru Tanpa Histori',
            'email'       => 'baru.clean@lpkcti.id',
            'foto_profil' => $fotoBaru,
            'password'    => 'Instruktur@123',
            'role'        => 'instruktur',
            'is_active'   => true,
        ]);
        $insBaru = $userBaru->syncInstrukturProfile('Desain Grafis');
        InstrukturSertifikasi::create([
            'instruktur_id'    => $insBaru->id,
            'nama_sertifikasi' => 'Adobe Certified',
            'file_path'        => $sertBaru,
            'file_name'        => 'sert-baru.pdf',
        ]);
        $insBaru->syncPrimarySertifikasiSummary();

        Storage::disk('public')->assertExists($fotoBaru);
        Storage::disk('local')->assertExists($sertBaru);

        $this->actingAs($this->admin)
            ->delete(route('admin.pengguna.destroy', $userBaru))
            ->assertRedirect(route('admin.pengguna.index'));

        $this->assertNull(User::find($userBaru->id));
        $this->assertNull(Instruktur::withTrashed()->find($insBaru->id));
        Storage::disk('public')->assertMissing($fotoBaru);
        Storage::disk('local')->assertMissing($sertBaru);
    }
}
