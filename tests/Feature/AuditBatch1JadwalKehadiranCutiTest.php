<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\ObservasiSupervisor;
use App\Models\User;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditBatch1JadwalKehadiranCutiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $userA;
    protected User $userB;
    protected Instruktur $insA;
    protected Instruktur $insB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'Admin Audit',
            'email'     => 'admin.audit1@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name'      => 'Supervisor Audit',
            'email'     => 'spv.audit1@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->userA = User::create([
            'name'      => 'Instruktur A',
            'email'     => 'insa.audit1@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->insA = $this->userA->syncInstrukturProfile('Pemrograman');

        $this->userB = User::create([
            'name'      => 'Instruktur B',
            'email'     => 'insb.audit1@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->insB = $this->userB->syncInstrukturProfile('Jaringan');
    }

    public function test_riwayat_cuti_preserved_when_instruktur_returns_to_tersedia_and_cuti_mulai_required(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        // Jadwal rutin setiap Senin pada bulan November 2026 (Senin: 2, 9, 16 Nov sudah lewat)
        Jadwal::create([
            'instruktur_id'    => $this->insA->id,
            'nama_kelas'       => 'RPL Reguler',
            'materi'           => 'Algoritma',
            'is_pola_mingguan' => true,
            'hari'             => 'Senin',
            'periode_mulai'    => '2026-11-01',
            'periode_selesai'  => '2026-11-30',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ]);

        // Validasi menolak cuti tanpa tanggal mulai
        $this->actingAs($this->admin)
            ->put(route('admin.instruktur.update', $this->insA), [
                'nama_lengkap'        => $this->insA->nama_lengkap,
                'kode_instruktur'     => $this->insA->kode_instruktur,
                'email'               => $this->insA->email,
                'status'              => 'aktif',
                'status_ketersediaan' => 'cuti',
                'cuti_mulai'          => '',
            ])
            ->assertSessionHasErrors('cuti_mulai');

        // Instruktur A cuti pada 1-10 November (mencakup Senin 2 & 9 Nov)
        $this->actingAs($this->admin)
            ->put(route('admin.instruktur.update', $this->insA), [
                'nama_lengkap'        => $this->insA->nama_lengkap,
                'kode_instruktur'     => $this->insA->kode_instruktur,
                'email'               => $this->insA->email,
                'status'              => 'aktif',
                'status_ketersediaan' => 'cuti',
                'cuti_mulai'          => '2026-11-01',
                'cuti_selesai'        => '2026-11-10',
                'keterangan_cuti'     => 'Cuti keluarga',
            ])
            ->assertRedirect(route('admin.instruktur.index'));

        // Pada 20 Nov, Admin mengembalikan status ketersediaan ke 'tersedia'
        $this->actingAs($this->admin)
            ->put(route('admin.instruktur.update', $this->insA), [
                'nama_lengkap'        => $this->insA->nama_lengkap,
                'kode_instruktur'     => $this->insA->kode_instruktur,
                'email'               => $this->insA->email,
                'status'              => 'aktif',
                'status_ketersediaan' => 'tersedia',
            ])
            ->assertRedirect(route('admin.instruktur.index'));

        // Instruktur A hadir pada Senin 16 Nov
        Absensi::create([
            'instruktur_id'    => $this->insA->id,
            'tanggal'          => '2026-11-16',
            'jam_checkin'      => '07:55',
            'jam_checkout'     => '10:00',
            'status'           => 'hadir',
            'sumber'           => 'manual',
        ]);

        KinerjaCalculationService::clearMemo();
        $hasil = app(KinerjaCalculationService::class)->evaluateInstruktur($this->insA->fresh(), '2026-11-01', '2026-11-30');

        // Senin 2 & 9 Nov tidak boleh berubah menjadi Alpa karena tercatat di riwayat cuti -> kehadiran 100%
        $this->assertEquals(100.0, $hasil['skor_kehadiran']);
        $this->assertEquals(0, $hasil['tidak_hadir_otomatis']);

        Carbon::setTestNow();
    }

    public function test_open_start_periode_validation_and_auto_checkout_evening_session_safety(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 19:10:00', 'Asia/Jakarta'));

        // 1. Jadwal pola mingguan dengan periode_mulai kosong dan hanya periode_selesai diisi harus valid
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id'    => $this->insA->id,
            'nama_kelas'       => 'Kelas Malam Terbuka',
            'materi'           => 'Desain Grafis',
            'is_pola_mingguan' => '1',
            'hari'             => 'Jumat',
            'periode_mulai'    => '',
            'periode_selesai'  => '2026-12-31',
            'jam_mulai'        => '18:30',
            'jam_selesai'      => '20:30',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        // 2. Presensi tanpa jadwal di malam hari (18:30) tidak boleh di-auto-checkout ke 17:00
        $absMalam = Absensi::create([
            'instruktur_id' => $this->insA->id,
            'jadwal_id'     => null,
            'tanggal'       => '2026-11-20',
            'jam_checkin'   => '18:30',
            'status'        => 'hadir',
            'sumber'        => 'manual',
        ]);

        // Pada pukul 19:10 (> 18:30 + 30 menit), auto-checkout menutup ke >= jam_checkin (18:30), bukan 17:00
        $closed = Absensi::prosesAutoCheckoutSesiLewat(30);
        $this->assertEquals(1, $closed);
        $this->assertGreaterThanOrEqual('18:30', substr((string) $absMalam->fresh()->jam_checkout, 0, 5));

        // 3. Checkout manual sesi kemarin di pagi hari berikutnya tidak menghasilkan jam_checkout < jam_checkin
        $jadwalKemarin = Jadwal::create([
            'instruktur_id' => $this->insB->id,
            'nama_kelas'    => 'Jaringan Sore',
            'materi'        => 'Mikrotik',
            'tanggal'       => '2026-11-19',
            'jam_mulai'     => '15:00',
            'jam_selesai'   => '17:00',
            'status'        => 'berlangsung',
        ]);
        $absKemarin = Absensi::create([
            'instruktur_id' => $this->insB->id,
            'jadwal_id'     => $jadwalKemarin->id,
            'tanggal'       => '2026-11-19',
            'jam_checkin'   => '15:05',
            'status'        => 'hadir',
            'sumber'        => 'checkin_app',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:15:00', 'Asia/Jakarta'));
        $this->actingAs($this->userB)
            ->post(route('instruktur.jadwal.checkout', $jadwalKemarin), ['tanggal_sesi' => '2026-11-19'])
            ->assertSessionHas('success');

        $this->assertEquals('17:00', substr((string) $absKemarin->fresh()->jam_checkout, 0, 5));

        Carbon::setTestNow();
    }

    public function test_alihkan_weekly_splits_history_reverts_cleanly_and_keeps_reassigned_journal_editable(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        // Jadwal mingguan Senin mulai 1 Nov (Senin 2, 9, 16 Nov milik Instruktur A)
        $jadwalMingguan = Jadwal::create([
            'instruktur_id'    => $this->insA->id,
            'nama_kelas'       => 'Web Reguler',
            'kode_kelas'       => 'WEB-01',
            'materi'           => 'PHP Lanjut',
            'is_pola_mingguan' => true,
            'hari'             => 'Senin',
            'periode_mulai'    => '2026-11-01',
            'periode_selesai'  => '2026-11-30',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'status'           => 'terjadwal',
        ]);

        foreach (['2026-11-02', '2026-11-09', '2026-11-16'] as $tgl) {
            Absensi::create([
                'instruktur_id' => $this->insA->id,
                'jadwal_id'     => $jadwalMingguan->id,
                'tanggal'       => $tgl,
                'jam_checkin'   => '08:00',
                'jam_checkout'  => '10:00',
                'status'        => 'hadir',
                'sumber'        => 'checkin_app',
            ]);
        }

        // Jurnal 16 Nov milik Instruktur A berstatus perlu_revisi
        $jurnalRevisi = AktivitasMengajar::create([
            'jadwal_id'            => $jadwalMingguan->id,
            'instruktur_id'        => $this->insA->id,
            'tanggal'              => '2026-11-16',
            'jam_mulai_aktual'     => '08:00',
            'jam_selesai_aktual'   => '10:00',
            'materi_disampaikan'   => 'PHP OOP',
            'jumlah_peserta_hadir' => 15,
            'status_konfirmasi'    => 'dikonfirmasi',
            'status_verifikasi'    => 'perlu_revisi',
            'file_dokumentasi'     => 'aktivitas/dokumentasi/awal.pdf',
            'confirmed_at'         => now(),
        ]);
        Storage::disk('local')->put('aktivitas/dokumentasi/awal.pdf', 'PDF-AWAL');

        // Alihkan ke Instruktur B pada 20 Nov
        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.alihkan', $jadwalMingguan), [
                'instruktur_pengganti_id' => $this->insB->id,
                'alasan_pengalihan'       => 'Instruktur A tugas luar kota',
            ])
            ->assertSessionHas('success');

        // Jadwal lama ditutup pada 19 Nov, jadwal baru dibuat mulai 20 Nov untuk Instruktur B
        $this->assertEquals('2026-11-19', $jadwalMingguan->fresh()->periode_selesai->toDateString());
        $jadwalBaru = Jadwal::where('instruktur_id', $this->insB->id)->where('kode_kelas', 'WEB-01')->firstOrFail();
        $this->assertEquals('2026-11-20', $jadwalBaru->periode_mulai->toDateString());
        $this->assertEquals($this->insA->id, $jadwalBaru->instruktur_asli_id);

        // Instruktur B tidak terkena penalti Alpa untuk Senin 2, 9, 16 Nov
        KinerjaCalculationService::clearMemo();
        $kinerjaB = app(KinerjaCalculationService::class)->evaluateInstruktur($this->insB->fresh(), '2026-11-01', '2026-11-30');
        $this->assertEquals(0, $kinerjaB['tidak_hadir_otomatis']);

        // Alihkan kembali jadwal baru dari B -> A pada hari yang sama -> instruktur_asli_id & pengganti bersih
        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.alihkan', $jadwalBaru), [
                'instruktur_pengganti_id' => $this->insA->id,
                'alasan_pengalihan'       => 'Kembali ke instruktur utama',
            ])
            ->assertSessionHas('success');

        $jadwalReverted = $jadwalBaru->fresh();
        $this->assertNull($jadwalReverted->instruktur_asli_id);
        $this->assertNull($jadwalReverted->instruktur_pengganti_id);

        // Uji jadwal spesifik yang dialihkan ke B setelah A mengisi jurnal perlu_revisi: A tetap bisa merevisi jurnalnya sendiri
        $jadwalSpesifik = Jadwal::create([
            'instruktur_id' => $this->insB->id,
            'nama_kelas'    => 'Sesi Khusus',
            'materi'        => 'API REST',
            'tanggal'       => '2026-11-18',
            'jam_mulai'     => '13:00',
            'jam_selesai'   => '15:00',
            'status'        => 'terjadwal',
        ]);
        $jurnalSpesifikA = AktivitasMengajar::create([
            'jadwal_id'            => $jadwalSpesifik->id,
            'instruktur_id'        => $this->insA->id,
            'tanggal'              => '2026-11-18',
            'jam_mulai_aktual'     => '13:00',
            'jam_selesai_aktual'   => '15:00',
            'materi_disampaikan'   => 'API REST v1',
            'jumlah_peserta_hadir' => 12,
            'status_konfirmasi'    => 'dikonfirmasi',
            'status_verifikasi'    => 'perlu_revisi',
            'file_dokumentasi'     => 'aktivitas/dokumentasi/awal.pdf',
            'confirmed_at'         => now(),
        ]);

        $aktIndexRes = $this->actingAs($this->userA)->get(route('instruktur.aktivitas.index'));
        $aktIndexRes->assertOk();
        $this->assertArrayHasKey($jadwalSpesifik->id, $aktIndexRes->viewData('jadwalOptionsJson'));

        $fileBaru = UploadedFile::fake()->create('revisi.pdf', 80, 'application/pdf');
        $this->actingAs($this->userA)
            ->put(route('instruktur.aktivitas.update', $jadwalSpesifik), [
                'tanggal'              => '2026-11-18',
                'jam_mulai_aktual'     => '13:00',
                'jam_selesai_aktual'   => '15:00',
                'materi_disampaikan'   => 'API REST v2 Revisi',
                'jumlah_peserta_hadir' => 14,
                'status_konfirmasi'    => 'dikonfirmasi',
                'file_dokumentasi'     => $fileBaru,
            ])
            ->assertSessionHas('success');

        $this->assertEquals('menunggu', $jurnalSpesifikA->fresh()->status_verifikasi);
        $this->assertEquals('API REST v2 Revisi', $jurnalSpesifikA->fresh()->materi_disampaikan);

        Carbon::setTestNow();
    }

    public function test_past_jadwal_live_status_manual_kehadiran_sync_and_observasi_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 14:00:00', 'Asia/Jakarta'));

        $jadwalLalu = Jadwal::create([
            'instruktur_id' => $this->insA->id,
            'nama_kelas'    => 'TKJ Pagi',
            'materi'        => 'Subnetting',
            'tanggal'       => '2026-11-18',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // Input manual pada tanggal yang salah harus ditolak agar tidak dibuang diam-diam oleh perhitungan KPI
        $this->actingAs($this->admin)
            ->post(route('admin.kehadiran.manual'), [
                'instruktur_id' => $this->insA->id,
                'jadwal_id'     => $jadwalLalu->id,
                'tanggal'       => '2026-11-19',
                'jam_checkin'   => '08:00',
                'jam_checkout'  => '10:00',
                'status'        => 'hadir',
            ])
            ->assertSessionHasErrors('tanggal');

        // Input manual pada tanggal yang benar dengan keterlambatan 30 menit otomatis menyinkronkan status ke 'terlambat'
        $this->actingAs($this->admin)
            ->post(route('admin.kehadiran.manual'), [
                'instruktur_id' => $this->insA->id,
                'jadwal_id'     => $jadwalLalu->id,
                'tanggal'       => '2026-11-18',
                'jam_checkin'   => '08:30',
                'jam_checkout'  => '10:00',
                'status'        => 'hadir',
            ])
            ->assertSessionHas('success');

        $absManual = Absensi::where('jadwal_id', $jadwalLalu->id)->firstOrFail();
        $this->assertEquals('terlambat', $absManual->status);
        $this->assertEquals(30, $absManual->menit_terlambat);

        // Tabel Jadwal Admin membaca status live jadwal masa lalu sebagai 'Selesai', bukan 'Terlewat Tanpa Presensi'
        $resJadwalAdmin = $this->actingAs($this->admin)->get(route('admin.jadwal.index', [
            'dari'   => '2026-11-01',
            'sampai' => '2026-11-30',
        ]));
        $resJadwalAdmin->assertOk();
        $liveMap = $resJadwalAdmin->viewData('liveStatusMap');
        $this->assertEquals('selesai', $liveMap[$jadwalLalu->id]['code']);

        // Observasi pada jadwal batal atau tanggal meleset ditolak
        $jadwalBatal = Jadwal::create([
            'instruktur_id' => $this->insA->id,
            'nama_kelas'    => 'TKJ Batal',
            'materi'        => 'VLAN',
            'tanggal'       => '2026-11-19',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'dibatalkan',
        ]);

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.observasi.store'), [
                'instruktur_id' => $this->insA->id,
                'jadwal_id'     => $jadwalBatal->id,
                'tanggal'       => '2026-11-19',
                'indikator_1'   => 4,
                'indikator_2'   => 4,
                'indikator_3'   => 4,
                'indikator_4'   => 4,
                'indikator_5'   => 4,
            ])
            ->assertSessionHasErrors('jadwal_id');

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.observasi.store'), [
                'instruktur_id' => $this->insA->id,
                'jadwal_id'     => $jadwalLalu->id,
                'tanggal'       => '2026-11-19',
                'indikator_1'   => 4,
                'indikator_2'   => 4,
                'indikator_3'   => 4,
                'indikator_4'   => 4,
                'indikator_5'   => 4,
            ])
            ->assertSessionHasErrors('tanggal');

        $this->assertEquals(0, ObservasiSupervisor::count());

        Carbon::setTestNow();
    }
}
