<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\KodeEvaluasi;
use App\Models\ObservasiSupervisor;
use App\Models\Setting;
use App\Models\User;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoreBusinessFlowTest extends TestCase
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
            'name'      => 'Admin Utama',
            'email'     => 'admin.flow@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name'      => 'Supervisor Satu',
            'email'     => 'spv1.flow@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->instrukturUser = User::create([
            'name'      => 'Rina Kusuma',
            'email'     => 'rina.flow@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->instruktur = $this->instrukturUser->syncInstrukturProfile('Pemrograman Web');
    }

    public function test_jadwal_pola_mingguan_clears_tanggal_and_detects_room_and_cross_mode_conflicts(): void
    {
        // 1. Buat jadwal pola mingguan walaupun form mengirim input tanggal default
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id'    => $this->instruktur->id,
            'nama_kelas'       => 'RPL Reguler',
            'materi'           => 'Laravel Dasar',
            'is_pola_mingguan' => '1',
            'hari'             => 'Senin',
            'tanggal'          => '2026-11-24',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'ruangan'          => 'L-01',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        $jadwalMingguan = Jadwal::firstOrFail();
        $this->assertTrue($jadwalMingguan->is_pola_mingguan);
        $this->assertNull($jadwalMingguan->tanggal);
        $this->assertEquals(0, AktivitasMengajar::where('jadwal_id', $jadwalMingguan->id)->count());

        // 2. Sesi sekali pada hari Senin di jam yang beririsan untuk instruktur yang sama harus ditolak
        // 2026-11-30 adalah hari Senin
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id' => $this->instruktur->id,
            'nama_kelas'    => 'RPL Intensif',
            'materi'        => 'Database',
            'tanggal'       => '2026-11-30',
            'jam_mulai'     => '09:00',
            'jam_selesai'   => '11:00',
            'ruangan'       => 'R-01',
            'status'        => 'terjadwal',
        ])->assertSessionHasErrors('jam_mulai');

        // 3. Instruktur berbeda namun memakai ruangan L-01 pada hari Senin dan jam yang beririsan harus ditolak
        $insLain = Instruktur::create([
            'kode_instruktur' => 'INS-CTI99',
            'nama_lengkap'    => 'Dedi Pratama',
            'email'           => 'dedi@lpkcti.id',
            'status'          => 'aktif',
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id' => $insLain->id,
            'nama_kelas'    => 'TKJ 1',
            'materi'        => 'Mikrotik',
            'tanggal'       => '2026-11-30',
            'jam_mulai'     => '08:30',
            'jam_selesai'   => '10:30',
            'ruangan'       => 'l-01',
            'status'        => 'terjadwal',
        ])->assertSessionHasErrors('ruangan');

        // 4. Jadwal yang sudah dibatalkan tidak boleh dialihkan
        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.batal', $jadwalMingguan), ['alasan_pembatalan' => 'Libur nasional'])
            ->assertRedirect();

        $this->assertEquals('dibatalkan', $jadwalMingguan->fresh()->status);

        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.alihkan', $jadwalMingguan), [
                'instruktur_pengganti_id' => $insLain->id,
                'alasan_pengalihan'       => 'Coba alihkan jadwal batal',
            ])
            ->assertSessionHasErrors('alasan_pengalihan');
    }

    public function test_presensi_gps_radius_detection_auto_checkout_and_manual_izin_cleanup(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 08:05:00', 'Asia/Jakarta'));

        Setting::set('lpk_latitude', '-7.3308868', 'absensi');
        Setting::set('lpk_longitude', '108.3560168', 'absensi');
        Setting::set('radius_lokasi_meter', '150', 'absensi');

        $jadwal = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'nama_kelas'    => 'RPL Pagi',
            'materi'        => 'OOP PHP',
            'tanggal'       => '2026-11-24',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // 1. Check-in dengan koordinat jauh di luar radius LPK (misal Bandung)
        $this->actingAs($this->instrukturUser)
            ->post(route('instruktur.jadwal.checkin', $jadwal), [
                'tanggal_sesi' => '2026-11-24',
                'latitude'     => -6.9174639,
                'longitude'    => 107.6191228,
                'akurasi_gps'  => 12,
                'status_gps'   => 'terdeteksi',
            ])
            ->assertRedirect();

        $absensi = Absensi::where('jadwal_id', $jadwal->id)->firstOrFail();
        $this->assertEquals('berlangsung', $jadwal->fresh()->status);
        $this->assertFalse($absensi->lokasi_checkin_info['dalam_radius']);
        $this->assertEquals('diluar_radius', $absensi->status_gps_normalized['code']);

        // 2. Waktu melewati jam selesai + 30 menit -> Auto-Checkout menutup sesi & mengubah status jadwal ke selesai
        Carbon::setTestNow(Carbon::parse('2026-11-24 11:00:00', 'Asia/Jakarta'));
        $closed = Absensi::prosesAutoCheckoutSesiLewat(30);
        $this->assertEquals(1, $closed);

        $absensiFresh = $absensi->fresh();
        $this->assertTrue($absensiFresh->is_auto_checkout);
        $this->assertEquals('10:00', substr((string) $absensiFresh->jam_checkout, 0, 5));
        $this->assertEquals('selesai', $jadwal->fresh()->status);

        // 3. Admin mengoreksi absensi menjadi 'izin' melalui storeManual -> jam_checkin & jam_checkout bersih
        $this->actingAs($this->admin)
            ->post(route('admin.kehadiran.manual'), [
                'absensi_id'    => $absensi->id,
                'instruktur_id' => $this->instruktur->id,
                'jadwal_id'     => $jadwal->id,
                'tanggal'       => '2026-11-24',
                'jam_checkin'   => '08:05',
                'jam_checkout'  => '10:00',
                'status'        => 'izin',
                'keterangan'    => 'Koreksi izin sakit',
            ])
            ->assertRedirect();

        $absensiIzin = $absensi->fresh();
        $this->assertEquals('izin', $absensiIzin->status);
        $this->assertNull($absensiIzin->jam_checkin);
        $this->assertNull($absensiIzin->jam_checkout);
        $this->assertEquals(0, $absensiIzin->menit_terlambat);

        Carbon::setTestNow();
    }

    public function test_aktivitas_mengajar_verification_flow_and_supervisor_observation_ownership(): void
    {
        Storage::fake('local');

        $jadwal = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'nama_kelas'    => 'AP 1',
            'materi'        => 'Korespondensi',
            'tanggal'       => today()->toDateString(),
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        // Placeholder belum_dikonfirmasi saat jadwal dibuat
        $placeholder = AktivitasMengajar::create([
            'jadwal_id'          => $jadwal->id,
            'instruktur_id'      => $this->instruktur->id,
            'tanggal'            => today()->toDateString(),
            'materi_disampaikan' => 'Korespondensi',
            'status_konfirmasi'  => 'belum_dikonfirmasi',
            'status_verifikasi'  => 'menunggu',
        ]);

        // 1. Supervisor tidak boleh memverifikasi ('diverifikasi') placeholder yang belum diisi instruktur
        $this->actingAs($this->supervisor)
            ->post(route('supervisor.kehadiran.verifikasi', $placeholder), [
                'status_verifikasi'  => 'diverifikasi',
                'catatan_supervisor' => 'Verifikasi prematur',
            ])
            ->assertSessionHas('error');

        $this->assertEquals('menunggu', $placeholder->fresh()->status_verifikasi);

        // 2. Instruktur mengisi jurnal aktivitas beserta bukti dokumentasi
        $bukti = UploadedFile::fake()->create('dokumentasi.pdf', 100, 'application/pdf');
        $this->actingAs($this->instrukturUser)
            ->put(route('instruktur.aktivitas.update', $jadwal), [
                'tanggal'              => today()->toDateString(),
                'jam_mulai_aktual'     => '08:00',
                'jam_selesai_aktual'   => '10:00',
                'materi_disampaikan'   => 'Korespondensi Bisnis Resmi',
                'jumlah_peserta_hadir' => 18,
                'status_konfirmasi'    => 'dikonfirmasi',
                'hasil_pembelajaran'   => 'Peserta mampu menyusun surat niaga',
                'file_dokumentasi'     => $bukti,
            ])
            ->assertSessionHas('success');

        $aktFilled = $placeholder->fresh();
        $this->assertEquals('dikonfirmasi', $aktFilled->status_konfirmasi);
        Storage::disk('local')->assertExists($aktFilled->file_dokumentasi);

        // 3. Supervisor memverifikasi jurnal yang sudah dikonfirmasi
        $this->actingAs($this->supervisor)
            ->post(route('supervisor.kehadiran.verifikasi', $aktFilled), [
                'status_verifikasi'  => 'diverifikasi',
                'catatan_supervisor' => 'Dokumentasi dan materi sesuai.',
            ])
            ->assertSessionHas('success');

        $this->assertEquals('diverifikasi', $aktFilled->fresh()->status_verifikasi);

        // 4. Setelah diverifikasi, instruktur tidak bisa mengubah jurnal yang sudah terkunci
        $this->actingAs($this->instrukturUser)
            ->put(route('instruktur.aktivitas.update', $jadwal), [
                'tanggal'              => today()->toDateString(),
                'jam_mulai_aktual'     => '08:00',
                'jam_selesai_aktual'   => '10:00',
                'materi_disampaikan'   => 'Coba ubah setelah terkunci',
                'jumlah_peserta_hadir' => 18,
                'status_konfirmasi'    => 'dikonfirmasi',
            ])
            ->assertSessionHas('error');

        // 5. Supervisor membuat observasi & dicegah mengubah observasi milik supervisor lain
        $this->actingAs($this->supervisor)
            ->post(route('supervisor.observasi.store'), [
                'instruktur_id'     => $this->instruktur->id,
                'jadwal_id'         => $jadwal->id,
                'tanggal'           => today()->toDateString(),
                'indikator_1'       => 5,
                'indikator_2'       => 4,
                'indikator_3'       => 5,
                'indikator_4'       => 4,
                'indikator_5'       => 5,
                'catatan_observasi' => 'Pengajaran interaktif',
            ])
            ->assertRedirect(route('supervisor.observasi.index'));

        $obs = ObservasiSupervisor::firstOrFail();
        $this->assertEquals(92.0, (float) $obs->skor_observasi);

        $supervisorDua = User::create([
            'name'      => 'Supervisor Dua',
            'email'     => 'spv2.flow@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->actingAs($supervisorDua)
            ->delete(route('supervisor.observasi.destroy', $obs))
            ->assertStatus(403);
    }

    public function test_evaluasi_publik_and_kinerja_calculation_handles_zero_data_future_schedules_and_weight_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 14:00:00', 'Asia/Jakarta'));

        $kinerjaService = app(KinerjaCalculationService::class);

        // 1. Instruktur tanpa jadwal/presensi/evaluasi aman dari division-by-zero
        $hasilKosong = $kinerjaService->evaluateInstruktur($this->instruktur, '2026-11-01', '2026-11-30');
        $this->assertNull($hasilKosong['skor_akhir']);
        $this->assertEquals('Belum Ada Data Penilaian', $hasilKosong['kategori_label']);

        // 2. Buat 1 jadwal hari ini (sudah selesai & dikonfirmasi) dan 2 jadwal minggu depan
        $jadwalHariIni = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'RPL',
            'nama_kelas'    => 'RPL',
            'materi'        => 'Algoritma',
            'tanggal'       => '2026-11-15',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        Absensi::create([
            'instruktur_id'    => $this->instruktur->id,
            'jadwal_id'        => $jadwalHariIni->id,
            'tanggal'          => '2026-11-15',
            'jam_mulai_jadwal' => '08:00',
            'jam_checkin'      => '07:55',
            'jam_checkout'     => '10:02',
            'menit_terlambat'  => 0,
            'status'           => 'hadir',
            'sumber'           => 'checkin_app',
        ]);

        AktivitasMengajar::create([
            'jadwal_id'            => $jadwalHariIni->id,
            'instruktur_id'        => $this->instruktur->id,
            'tanggal'              => '2026-11-15',
            'jam_mulai_aktual'     => '08:00',
            'jam_selesai_aktual'   => '10:00',
            'materi_disampaikan'   => 'Algoritma',
            'jumlah_peserta_hadir' => 20,
            'status_konfirmasi'    => 'dikonfirmasi',
            'status_verifikasi'    => 'diverifikasi',
            'confirmed_at'         => now(),
        ]);

        // Jadwal masa depan (25 November 2026) beserta placeholder belum_dikonfirmasi
        $jadwalMasaDepan = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'RPL',
            'nama_kelas'    => 'RPL',
            'materi'        => 'Struktur Data',
            'tanggal'       => '2026-11-25',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);

        AktivitasMengajar::create([
            'jadwal_id'          => $jadwalMasaDepan->id,
            'instruktur_id'      => $this->instruktur->id,
            'tanggal'            => '2026-11-25',
            'materi_disampaikan' => 'Struktur Data',
            'status_konfirmasi'  => 'belum_dikonfirmasi',
            'status_verifikasi'  => 'menunggu',
        ]);

        // 3. Portal Publik Evaluasi: peserta mengisi evaluasi dengan kode valid (kuota 1)
        $kodeEval = KodeEvaluasi::create([
            'instruktur_id' => $this->instruktur->id,
            'kode'          => 'EV-RPL01',
            'nama_kelas'    => 'RPL',
            'is_active'     => true,
            'max_pengisian' => 1,
            'jumlah_diisi'  => 0,
        ]);

        $this->post(route('evaluasi.publik.store'), [
            'kode'         => 'EV-RPL01',
            'jadwal_id'    => $jadwalHariIni->id,
            'nama_pengisi' => 'Andi Peserta',
            'indikator_1'  => 4,
            'indikator_2'  => 4,
            'indikator_3'  => 4,
            'indikator_4'  => 4,
            'indikator_5'  => 4,
            'komentar'     => 'Penyampaian jelas',
        ])->assertRedirect(route('evaluasi.publik.berhasil'));

        $this->assertEquals(1, $kodeEval->fresh()->jumlah_diisi);

        // Pengisian kedua ditolak karena kuota max_pengisian = 1 sudah penuh
        $this->post(route('evaluasi.publik.store'), [
            'kode'        => 'EV-RPL01',
            'jadwal_id'   => $jadwalHariIni->id,
            'indikator_1' => 5,
            'indikator_2' => 5,
            'indikator_3' => 5,
            'indikator_4' => 5,
            'indikator_5' => 5,
        ])->assertSessionHasErrors('kode');

        // 4. Cek hasil perhitungan kinerja:
        // Kehadiran = 100, Aktivitas = 100 (jadwal masa depan tidak menurunkan skor), Evaluasi = 80 (skala 4/5)
        KinerjaCalculationService::clearMemo();
        $hasil = $kinerjaService->evaluateInstruktur($this->instruktur->fresh(), '2026-11-01', '2026-11-30');
        $this->assertEquals(100.0, $hasil['skor_kehadiran']);
        $this->assertEquals(100.0, $hasil['skor_aktivitas']);
        $this->assertEquals(80.0, $hasil['skor_evaluasi']);

        // Default bobot: kehadiran 30, aktivitas 20, evaluasi 30 (total tersedia 80) -> (100*30 + 100*20 + 80*30)/80 = 92.5
        $this->assertEquals(92.5, $hasil['skor_akhir']);

        // 5. Ubah bobot di Pengaturan (Evaluasi menjadi 80%, Kehadiran 10%, Aktivitas 10%, Observasi 0%)
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.kinerja'), [
                'kinerja_rule_enabled'       => '1',
                'bobot_kehadiran'            => 10,
                'bobot_aktivitas'            => 10,
                'bobot_evaluasi'             => 80,
                'bobot_observasi'            => 0,
                'batas_sangat_baik'          => 85,
                'batas_baik'                 => 75,
                'batas_cukup'                => 60,
                'toleransi_terlambat_menit'  => 15,
                'checkin_buka_sebelum_menit' => 30,
                'lpk_latitude'               => '-7.3308868',
                'lpk_longitude'              => '108.3560168',
                'radius_lokasi_meter'        => 150,
            ])
            ->assertRedirect();

        // Skor akhir baru: (100*10 + 100*10 + 80*80)/100 = 84.0 (kategori Baik)
        $hasilBaru = $kinerjaService->evaluateInstruktur($this->instruktur->fresh(), '2026-11-01', '2026-11-30');
        $this->assertEquals(84.0, $hasilBaru['skor_akhir']);
        $this->assertEquals('baik', $hasilBaru['kategori']);

        Carbon::setTestNow();
    }

    public function test_jadwal_specific_date_syncs_hari_and_hari_filter_excludes_mismatched_dates_in_calendar_and_table(): void
    {
        // Buat jadwal tanggal spesifik pada Kamis, 19 November 2026 tetapi form mengirim hari = 'Senin'
        $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'instruktur_id'    => $this->instruktur->id,
            'nama_kelas'       => 'TES TEKNIK KAMIS',
            'materi'           => 'Jaringan Dasar',
            'is_pola_mingguan' => '0',
            'hari'             => 'Senin',
            'tanggal'          => '2026-11-19',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'ruangan'          => 'L-01',
            'status'           => 'terjadwal',
        ])->assertRedirect(route('admin.jadwal.index'));

        $jadwalKamis = Jadwal::where('nama_kelas', 'TES TEKNIK KAMIS')->firstOrFail();
        $this->assertFalse($jadwalKamis->is_pola_mingguan);
        $this->assertEquals('Kamis', $jadwalKamis->hari);

        // Buat jadwal pola mingguan hari Senin
        $jadwalSenin = Jadwal::create([
            'instruktur_id'    => $this->instruktur->id,
            'nama_kelas'       => 'RPL SENIN REGULER',
            'materi'           => 'Algoritma',
            'is_pola_mingguan' => true,
            'hari'             => 'Senin',
            'jam_mulai'        => '13:00',
            'jam_selesai'      => '15:00',
            'ruangan'          => 'R-01',
            'status'           => 'terjadwal',
        ]);

        // Filter hari=Senin di Kalender Admin: hanya jadwal Senin yang muncul di kalender, jadwal Kamis tidak boleh muncul di kalender
        $resAdminCal = $this->actingAs($this->admin)->get(route('admin.jadwal.index', [
            'tampilan'         => 'kalender',
            'skala_kalender'   => 'bulanan',
            'tanggal_kalender' => '2026-11-19',
            'hari'             => 'Senin',
        ]));
        $resAdminCal->assertOk()
            ->assertViewHas('calendarEvents', function ($events): bool {
                $flat = collect($events)->flatten(1);
                return $flat->contains(fn ($j) => $j->nama_kelas === 'RPL SENIN REGULER')
                    && ! $flat->contains(fn ($j) => $j->nama_kelas === 'TES TEKNIK KAMIS');
            })
            ->assertSee('Filter Hari:');

        // Filter hari=Kamis di Tabel Supervisor: hanya jadwal Kamis yang muncul di tabel
        $resSupTable = $this->actingAs($this->supervisor)->get(route('supervisor.jadwal.index', [
            'tampilan' => 'tabel',
            'dari'     => '2026-11-01',
            'sampai'   => '2026-11-30',
            'hari'     => 'Kamis',
        ]));
        $resSupTable->assertOk()
            ->assertViewHas('jadwals', function ($jadwals): bool {
                $items = collect($jadwals->items());
                return $items->contains(fn ($j) => $j->nama_kelas === 'TES TEKNIK KAMIS')
                    && ! $items->contains(fn ($j) => $j->nama_kelas === 'RPL SENIN REGULER');
            });

        // Filter hari=Senin di Kalender Instruktur: jadwal Kamis tidak muncul di kalender
        $resInsCal = $this->actingAs($this->instrukturUser)->get(route('instruktur.jadwal.index', [
            'tampilan'         => 'kalender',
            'skala_kalender'   => 'bulanan',
            'tanggal_kalender' => '2026-11-19',
            'hari'             => 'Senin',
        ]));
        $resInsCal->assertOk()
            ->assertViewHas('calendarEvents', function ($events): bool {
                $flat = collect($events)->flatten(1);
                return $flat->contains(fn ($j) => $j->nama_kelas === 'RPL SENIN REGULER')
                    && ! $flat->contains(fn ($j) => $j->nama_kelas === 'TES TEKNIK KAMIS');
            });
    }
}
