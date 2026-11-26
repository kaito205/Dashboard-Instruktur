<?php

namespace Tests\Feature;

use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\NotifikasiTinjauan;
use App\Models\User;
use App\Services\OperationalAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditBatch2AktivitasNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $instrukturUser;
    protected Instruktur $instruktur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'Admin Batch2',
            'email'     => 'admin.b2@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name'      => 'Supervisor Batch2',
            'email'     => 'spv.b2@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->instrukturUser = User::create([
            'name'      => 'Dian Pratama',
            'email'     => 'dian.b2@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->instruktur = $this->instrukturUser->syncInstrukturProfile('Administrasi Perkantoran');
    }

    public function test_placeholder_aktivitas_does_not_trigger_future_alert_or_double_count_overdue_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));

        // 1. Buat jadwal masa depan lewat endpoint store() (otomatis membuat placeholder belum_dikonfirmasi)
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id'    => $this->instruktur->id,
            'kode_kelas'       => 'AP-01',
            'nama_kelas'       => 'AP Masa Depan',
            'materi'           => 'Kearsipan',
            'is_pola_mingguan' => '0',
            'tanggal'          => '2026-11-22',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        // 2. Buat jadwal yang sudah lewat 3 hari lalu lewat endpoint store() (juga membuat placeholder belum_dikonfirmasi)
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id'    => $this->instruktur->id,
            'kode_kelas'       => 'AP-02',
            'nama_kelas'       => 'AP Terlewat',
            'materi'           => 'Surat Menyurat',
            'is_pola_mingguan' => '0',
            'tanggal'          => '2026-11-12',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        $this->assertEquals(2, AktivitasMengajar::where('status_konfirmasi', 'belum_dikonfirmasi')->count());

        OperationalAlertService::clearMemo();
        $alertsAdmin = app(OperationalAlertService::class)->getSummaryAlerts('2026-11-01', '2026-11-30', 'admin', $this->admin->id);
        $jurnalAlerts = collect($alertsAdmin['items_aktif'])->where('kategori', 'Jurnal')->values();

        // Jadwal masa depan (22 Nov) tidak boleh memicu alert, dan jadwal lewat (12 Nov) hanya dihitung 1x (bukan 2x)
        $this->assertCount(1, $jurnalAlerts);
        $this->assertStringContainsString('AP-02', $jurnalAlerts[0]['ringkasan']);

        // Alert instruktur juga hanya 1 (bukan untuk jadwal 22 Nov)
        OperationalAlertService::clearMemo();
        $alertsIns = app(OperationalAlertService::class)->getSummaryAlerts(null, null, 'instruktur', $this->instrukturUser->id);
        $insJurnalAlerts = collect($alertsIns['items_aktif'])->where('kategori', 'Jurnal')->values();
        $this->assertCount(1, $insJurnalAlerts);

        // 3. Jika jadwal masa depan diubah menjadi pola mingguan melalui update(), placeholder lama dibersihkan
        $jadwalFuture = Jadwal::where('kode_kelas', 'AP-01')->firstOrFail();
        $this->actingAs($this->admin)->put(route('admin.jadwal.update', $jadwalFuture), [
            'instruktur_id'    => $this->instruktur->id,
            'kode_kelas'       => 'AP-01',
            'nama_kelas'       => 'AP Masa Depan',
            'materi'           => 'Kearsipan',
            'is_pola_mingguan' => '1',
            'hari'             => 'Minggu',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        $this->assertEquals(0, AktivitasMengajar::where('jadwal_id', $jadwalFuture->id)->count());

        Carbon::setTestNow();
    }

    public function test_instruktur_dashboard_reads_today_weekly_journal_status_instead_of_previous_week(): void
    {
        // Senin, 16 November 2026
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:00:00', 'Asia/Jakarta'));

        $jadwalSenin = Jadwal::create([
            'instruktur_id'    => $this->instruktur->id,
            'nama_kelas'       => 'AP Senin Rutin',
            'materi'           => 'Komunikasi Kantor',
            'is_pola_mingguan' => true,
            'hari'             => 'Senin',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ]);

        // Laporan aktivitas minggu lalu (Senin, 9 November 2026) sudah dikonfirmasi
        AktivitasMengajar::create([
            'jadwal_id'            => $jadwalSenin->id,
            'instruktur_id'        => $this->instruktur->id,
            'tanggal'              => '2026-11-09',
            'jam_mulai_aktual'     => '08:00',
            'jam_selesai_aktual'   => '10:00',
            'materi_disampaikan'   => 'Pertemuan 1',
            'jumlah_peserta_hadir' => 20,
            'status_konfirmasi'    => 'dikonfirmasi',
            'status_verifikasi'    => 'diverifikasi',
            'confirmed_at'         => Carbon::parse('2026-11-09 10:05:00'),
        ]);

        $dashRes = $this->actingAs($this->instrukturUser)->get(route('instruktur.dashboard'));
        $dashRes->assertOk();
        $mySchedule = $dashRes->viewData('mySchedule');

        $this->assertNotEmpty($mySchedule);
        $this->assertEquals('belum_dikonfirmasi', $mySchedule[0]['konfirmasi_status']);
        $this->assertEquals('Belum Dikonfirmasi', $mySchedule[0]['konfirmasi_label']);

        Carbon::setTestNow();
    }

    public function test_notification_review_handles_more_than_150_records_and_verifikasi_menunggu_clears_verifier_and_url_scheme(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        $jadwal = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'AP-10',
            'nama_kelas'    => 'AP Utama',
            'materi'        => 'Pelayanan Prima',
            'tanggal'       => '2026-11-15',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // 1. Validasi tautan_materi menolak skema javascript:
        $bukti = UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf');
        $this->actingAs($this->instrukturUser)
            ->put(route('instruktur.aktivitas.update', $jadwal), [
                'tanggal'              => '2026-11-15',
                'jam_mulai_aktual'     => '08:00',
                'jam_selesai_aktual'   => '10:00',
                'materi_disampaikan'   => 'Pelayanan Prima',
                'tautan_materi'        => 'javascript:alert(1)',
                'jumlah_peserta_hadir' => 15,
                'status_konfirmasi'    => 'dikonfirmasi',
                'file_dokumentasi'     => $bukti,
            ])
            ->assertSessionHasErrors('tautan_materi');

        // 2. Simpan dengan URL https:// valid, verifikasi, lalu kembalikan ke 'menunggu' -> verified_by & verified_at harus null
        $this->actingAs($this->instrukturUser)
            ->put(route('instruktur.aktivitas.update', $jadwal), [
                'tanggal'              => '2026-11-15',
                'jam_mulai_aktual'     => '08:00',
                'jam_selesai_aktual'   => '10:00',
                'materi_disampaikan'   => 'Pelayanan Prima',
                'tautan_materi'        => 'https://lpkcti.id/modul.pdf',
                'jumlah_peserta_hadir' => 15,
                'status_konfirmasi'    => 'dikonfirmasi',
                'file_dokumentasi'     => $bukti,
            ])
            ->assertSessionHas('success');

        $akt = AktivitasMengajar::where('jadwal_id', $jadwal->id)->firstOrFail();

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.kehadiran.verifikasi', $akt), ['status_verifikasi' => 'diverifikasi'])
            ->assertSessionHas('success');
        $this->assertNotNull($akt->fresh()->verified_by);

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.kehadiran.verifikasi', $akt), ['status_verifikasi' => 'menunggu'])
            ->assertSessionHas('success');
        $this->assertNull($akt->fresh()->verified_by);
        $this->assertNull($akt->fresh()->verified_at);

        // 3. Buat jadwal kedua yang belum berjurnal + pengingat jadwal besok, tandai ditinjau, lalu sisipkan 160 riwayat lain
        $jadwalOverdue = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'AP-11',
            'nama_kelas'    => 'AP Tunggakan',
            'materi'        => 'Etika Kantor',
            'tanggal'       => '2026-11-14',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);
        Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'AP-12',
            'nama_kelas'    => 'AP Besok',
            'materi'        => 'Rapat',
            'tanggal'       => '2026-11-21',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        OperationalAlertService::clearMemo();
        $initialAlerts = app(OperationalAlertService::class)->getSummaryAlerts('2026-11-01', '2026-11-30', 'admin', $this->admin->id);
        $this->assertEquals(2, $initialAlerts['total_peringatan']);
        $this->assertEquals(1, $initialAlerts['total_info']);
        $this->assertEquals(3, $initialAlerts['total_semua_aktif']);
        $targetKey = collect($initialAlerts['items_aktif'])->firstWhere('kategori', 'Jurnal')['alert_key'];

        $jsonRes = $this->actingAs($this->admin)->postJson(route('admin.notifikasi.tinjau'), [
            'alert_key' => $targetKey,
            'dari'      => '2026-11-01',
            'sampai'    => '2026-11-30',
        ]);
        $jsonRes->assertOk()
            ->assertJsonPath('total_peringatan', 1)
            ->assertJsonPath('total_info', 1)
            ->assertJsonPath('total_aktif', 2);

        // Tambahkan 160 notifikasi tinjauan baru setelahnya -> notifikasi lama tidak boleh hidup kembali
        $rows = [];
        for ($i = 1; $i <= 160; $i++) {
            $rows[] = [
                'user_id'       => $this->admin->id,
                'alert_key'     => "dummy_hist:{$i}",
                'kategori'      => 'Jurnal',
                'level'         => 'warning',
                'judul'         => "Riwayat {$i}",
                'ringkasan'     => 'Selesai',
                'url'           => '/admin/notifikasi',
                'ditinjau_pada' => now()->addSeconds($i),
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        NotifikasiTinjauan::insert($rows);

        OperationalAlertService::clearMemo();
        $after160 = app(OperationalAlertService::class)->getSummaryAlerts('2026-11-01', '2026-11-30', 'admin', $this->admin->id);
        $this->assertFalse(collect($after160['items_aktif'])->contains('alert_key', $targetKey));

        Carbon::setTestNow();
    }
}
