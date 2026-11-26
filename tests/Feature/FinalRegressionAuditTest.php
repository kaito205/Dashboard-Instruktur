<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use App\Models\ObservasiSupervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalRegressionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_konsistensi_kpi_lintas_halaman_dan_seluruh_role_end_to_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 14:00:00', 'Asia/Jakarta'));
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::create([
            'name'      => 'Admin Regresi',
            'email'     => 'admin.reg@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $supervisor = User::create([
            'name'      => 'Supervisor Regresi',
            'email'     => 'spv.reg@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $userIns = User::create([
            'name'      => 'Siti Instruktur',
            'email'     => 'siti.reg@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $instruktur = $userIns->syncInstrukturProfile('Administrasi Perkantoran');

        $kelas = Kelas::create([
            'kode_kelas'        => 'ADM-2026-B1',
            'nama_kelas'        => 'Administrasi Perkantoran B1',
            'program_pelatihan' => 'Administrasi Perkantoran',
            'kuota_peserta'     => 25,
            'ruangan_default'   => 'R1',
            'periode_mulai'     => '2026-11-01',
            'periode_selesai'   => '2026-11-30',
            'status'            => 'aktif',
        ]);

        // Sesi 1: Sudah lewat (10 Nov 2026) -> Hadir + Jurnal diverifikasi
        $jadwal1 = Jadwal::create([
            'instruktur_id' => $instruktur->id,
            'kelas_id'      => $kelas->id,
            'kode_kelas'    => $kelas->kode_kelas,
            'nama_kelas'    => $kelas->nama_kelas,
            'materi'        => 'Korespondensi Bisnis',
            'ruangan'       => 'R1',
            'tanggal'       => '2026-11-10',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        Absensi::create([
            'instruktur_id' => $instruktur->id,
            'jadwal_id'     => $jadwal1->id,
            'tanggal'       => '2026-11-10',
            'jam_checkin'   => '07:55',
            'jam_checkout'  => '10:02',
            'status'        => 'hadir',
            'sumber'        => 'checkin_app',
        ]);

        $dokFile = UploadedFile::fake()->create('bukti-sesi1.pdf', 100, 'application/pdf')->store('aktivitas/dokumentasi', 'local');
        $akt1 = AktivitasMengajar::create([
            'jadwal_id'            => $jadwal1->id,
            'instruktur_id'        => $instruktur->id,
            'tanggal'              => '2026-11-10',
            'jam_mulai_aktual'     => '08:00',
            'jam_selesai_aktual'   => '10:00',
            'materi_disampaikan'   => 'Korespondensi Bisnis Resmi',
            'tautan_materi'        => 'https://lpkcti.id/modul/korespondensi',
            'jumlah_peserta_hadir' => 22,
            'status_konfirmasi'    => 'dikonfirmasi',
            'confirmed_at'         => Carbon::parse('2026-11-10 10:15:00'),
            'file_dokumentasi'     => $dokFile,
            'status_verifikasi'    => 'menunggu',
        ]);

        // Supervisor memverifikasi aktivitas mengajar
        $this->actingAs($supervisor)
            ->post(route('supervisor.kehadiran.verifikasi', $akt1), [
                'status_verifikasi'  => 'diverifikasi',
                'catatan_verifikasi' => 'Materi lengkap dan dokumentasi jelas.',
            ])
            ->assertRedirect();

        $this->assertSame('diverifikasi', $akt1->fresh()->status_verifikasi);
        $this->assertSame($supervisor->id, $akt1->fresh()->verified_by);

        // Sesi 2: Hari ini pagi (20 Nov 2026 08:00-10:00, sudah lewat pukul 14:00) -> Terlambat + Jurnal dikonfirmasi
        $jadwal2 = Jadwal::create([
            'instruktur_id' => $instruktur->id,
            'kelas_id'      => $kelas->id,
            'kode_kelas'    => $kelas->kode_kelas,
            'nama_kelas'    => $kelas->nama_kelas,
            'materi'        => 'Pengarsipan Digital',
            'ruangan'       => 'R1',
            'tanggal'       => '2026-11-20',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        Absensi::create([
            'instruktur_id'   => $instruktur->id,
            'jadwal_id'       => $jadwal2->id,
            'tanggal'         => '2026-11-20',
            'jam_checkin'     => '08:25',
            'jam_checkout'    => '10:05',
            'status'          => 'terlambat',
            'menit_terlambat' => 25,
            'sumber'          => 'manual',
        ]);

        // Sisipkan tautan legacy non-http untuk menguji defense-in-depth rendering di Blade
        AktivitasMengajar::create([
            'jadwal_id'            => $jadwal2->id,
            'instruktur_id'        => $instruktur->id,
            'tanggal'              => '2026-11-20',
            'jam_mulai_aktual'     => '08:25',
            'jam_selesai_aktual'   => '10:05',
            'materi_disampaikan'   => 'Pengarsipan Digital Modern',
            'tautan_materi'        => 'javascript:alert(1)',
            'jumlah_peserta_hadir' => 20,
            'status_konfirmasi'    => 'dikonfirmasi',
            'confirmed_at'         => Carbon::parse('2026-11-20 10:20:00'),
            'file_dokumentasi'     => $dokFile,
            'status_verifikasi'    => 'diverifikasi',
            'verified_by'          => $supervisor->id,
            'verified_at'          => now(),
        ]);

        // Evaluasi publik responden (skala 1-5: semua 5 -> nilai 100)
        $kodeEval = KodeEvaluasi::create([
            'kode'          => 'ADM26B1',
            'instruktur_id' => $instruktur->id,
            'kelas_id'      => $kelas->id,
            'nama_kelas'    => $kelas->nama_kelas,
            'is_active'     => true,
            'expired_at'    => Carbon::parse('2026-11-30')->endOfDay(),
        ]);

        $this->post(route('evaluasi.publik.store'), [
            'kode'           => 'ADM26B1',
            'nama_pengisi'   => 'Peserta Uji Satu',
            'jadwal_id'      => $jadwal1->id,
            'indikator_1'    => 5,
            'indikator_2'    => 5,
            'indikator_3'    => 4,
            'indikator_4'    => 5,
            'indikator_5'    => 5,
            'komentar_saran' => 'Penyampaian sangat jelas.',
        ])->assertRedirect(route('evaluasi.publik.berhasil'));

        // Observasi Supervisor (skala Likert 1-5)
        $this->actingAs($supervisor)
            ->post(route('supervisor.observasi.store'), [
                'instruktur_id'     => $instruktur->id,
                'jadwal_id'         => $jadwal1->id,
                'tanggal'           => '2026-11-10',
                'indikator_1'       => 5,
                'indikator_2'       => 4,
                'indikator_3'       => 5,
                'indikator_4'       => 4,
                'indikator_5'       => 5,
                'catatan_observasi' => 'Penguasaan kelas sangat baik.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, ObservasiSupervisor::count());

        // Verifikasi Kehadiran default hari_ini (20 Nov 2026) menangkap absensi hari ini & memblokir href javascript:
        $kehadiranHariIni = $this->actingAs($admin)->get(route('admin.kehadiran.index', ['tab' => 'aktivitas']));
        $kehadiranHariIni->assertStatus(200)
            ->assertDontSee('href="javascript:alert(1)"', false);
        $this->assertSame(1, $kehadiranHariIni->viewData('ringkasan')['total']);
        $this->assertSame(1, $kehadiranHariIni->viewData('ringkasan')['terlambat']);

        // Verifikasi konsistensi KPI bulan berjalan (1 Nov s.d. 30 Nov 2026) lintas seluruh modul
        $periodeParams = [
            'periode' => 'kustom',
            'dari'    => '2026-11-01',
            'sampai'  => '2026-11-30',
        ];

        $dashAdmin = $this->actingAs($admin)->get(route('admin.dashboard', $periodeParams))->assertStatus(200);
        $dashSpv   = $this->actingAs($supervisor)->get(route('supervisor.dashboard', $periodeParams))->assertStatus(200);
        $kehadiranBulan = $this->actingAs($admin)->get(route('admin.kehadiran.index', $periodeParams))->assertStatus(200);
        $monitoringAdmin = $this->actingAs($admin)->get(route('admin.kinerja.index', $periodeParams))->assertStatus(200);
        $monitoringSpv   = $this->actingAs($supervisor)->get(route('supervisor.kinerja.index', $periodeParams))->assertStatus(200);
        $laporanKinerja  = $this->actingAs($admin)->get(route('admin.laporan.index', array_merge($periodeParams, ['jenis' => 'kinerja'])))->assertStatus(200);
        $laporanIndividu = $this->actingAs($admin)->get(route('admin.laporan.index', array_merge($periodeParams, ['jenis' => 'individual', 'instruktur' => $instruktur->id])))->assertStatus(200);
        $laporanManajerial = $this->actingAs($admin)->get(route('admin.laporan.index', array_merge($periodeParams, ['jenis' => 'manajerial'])))->assertStatus(200);
        $raporInstruktur = $this->actingAs($userIns)->get(route('instruktur.rapor.index', $periodeParams))->assertStatus(200);

        $monRowAdmin = collect($monitoringAdmin->viewData('dataKinerja'))->first(fn ($r) => $r['instruktur']->id === $instruktur->id);
        $monRowSpv   = collect($monitoringSpv->viewData('dataKinerja'))->first(fn ($r) => $r['instruktur']->id === $instruktur->id);
        $lapRow      = collect($laporanKinerja->viewData('data'))->first(fn ($r) => $r['instruktur']->id === $instruktur->id);
        $indEval     = $laporanIndividu->viewData('individualReport')['kinerja'];
        $raporEval   = $raporInstruktur->viewData('rapor');

        // 2 sesi x 2 jam = 4.0 jam, kehadiran 2/2 = 100%, aktivitas 2/2 = 100%, peserta dilatih = 22 + 20 = 42
        $this->assertEquals(4.0, $monRowAdmin['jam_mengajar']);
        $this->assertEquals(4.0, $dashAdmin->viewData('metrics')['total_jam_mengajar']);
        $this->assertEquals(100.0, $monRowAdmin['persentase_kehadiran']);
        $this->assertEquals(100.0, $kehadiranBulan->viewData('ringkasan')['persentase']);
        $this->assertEquals(100.0, $dashAdmin->viewData('metrics')['persentase_kehadiran']);
        $this->assertEquals($monRowAdmin['skor_akhir'], $monRowSpv['skor_akhir']);
        $this->assertEquals($monRowAdmin['skor_akhir'], $lapRow['skor_akhir']);
        $this->assertEquals($monRowAdmin['skor_akhir'], $indEval['skor_akhir']);
        $this->assertEquals($monRowAdmin['skor_akhir'], $raporEval['skor_akhir']);
        $this->assertEquals($dashAdmin->viewData('metrics')['persentase_kehadiran'], $dashSpv->viewData('metrics')['persentase_kehadiran']);
        $this->assertEquals(100.0, $laporanManajerial->viewData('manajerialReport')['kpi']['rata_kehadiran']);
        $this->assertSame(42, $raporInstruktur->viewData('totalPesertaDilatih'));

        // Verifikasi seluruh halaman utama, ekspor, dan notifikasi pada ketiga role berjalan tanpa error 500
        $this->actingAs($admin)->get(route('admin.pengguna.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.instruktur.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.kelas.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.jadwal.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.import.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.evaluasi.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.evaluasi.kode.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.observasi.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.pengaturan.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.notifikasi.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.laporan.cetak', array_merge($periodeParams, ['jenis' => 'kinerja'])))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.laporan.export', array_merge($periodeParams, ['jenis' => 'kinerja'])))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.laporan.export-excel', array_merge($periodeParams, ['jenis' => 'kinerja'])))->assertStatus(200);

        $this->actingAs($supervisor)->get(route('supervisor.instruktur.index'))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.instruktur.show', $instruktur))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.jadwal.index'))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.kehadiran.index', ['tab' => 'aktivitas']))
            ->assertStatus(200)
            ->assertDontSee('href="javascript:alert(1)"', false);
        $this->actingAs($supervisor)->get(route('supervisor.evaluasi.index'))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.observasi.index'))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.notifikasi.index'))->assertStatus(200);
        $this->actingAs($supervisor)->get(route('supervisor.profil.index'))->assertStatus(200);

        $this->actingAs($userIns)->get(route('instruktur.dashboard'))->assertStatus(200);
        $this->actingAs($userIns)->get(route('instruktur.jadwal.index'))->assertStatus(200);
        $this->actingAs($userIns)->get(route('instruktur.aktivitas.index'))
            ->assertStatus(200)
            ->assertDontSee('href="javascript:alert(1)"', false);
        $this->actingAs($userIns)->get(route('instruktur.aktivitas.export.excel'))->assertStatus(200);
        $this->actingAs($userIns)->get(route('instruktur.aktivitas.export.pdf'))->assertStatus(200);
        $this->actingAs($userIns)->get(route('instruktur.evaluasi.index'))
            ->assertStatus(200)
            ->assertSee('Korespondensi Bisnis');
        $this->actingAs($userIns)->get(route('instruktur.profil.index'))->assertStatus(200);
        $this->actingAs($userIns)->get(route('instruktur.notifikasi.index'))->assertStatus(200);

        Carbon::setTestNow();
    }
}
