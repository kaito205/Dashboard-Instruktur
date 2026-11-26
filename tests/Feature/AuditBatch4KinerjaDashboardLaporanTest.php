<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\User;
use App\Services\KinerjaCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBatch4KinerjaDashboardLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $insUser;
    protected Instruktur $instruktur;
    protected Instruktur $instrukturKosong;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'Admin Batch4',
            'email'     => 'admin.b4@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name'      => 'Supervisor Batch4',
            'email'     => 'spv.b4@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->insUser = User::create([
            'name'      => 'Hendra Wijaya',
            'email'     => 'hendra.b4@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->instruktur = $this->insUser->syncInstrukturProfile('Pemrograman Web');

        // Instruktur aktif tanpa jadwal sama sekali (tidak boleh menekan rata-rata kehadiran LPK)
        $this->instrukturKosong = Instruktur::create([
            'kode_instruktur' => 'INS-CTI77',
            'nama_lengkap'    => 'Instruktur Cadangan',
            'email'           => 'cadangan@lpkcti.id',
            'status'          => 'aktif',
        ]);
    }

    public function test_weekly_jam_mengajar_multiplies_elapsed_occurrences_and_activity_chart_excludes_future_dates(): void
    {
        // Jumat, 20 November 2026 -> Senin yang sudah lewat di bulan Nov adalah 2, 9, 16 Nov (3 pertemuan x 2 jam = 6.0 jam)
        // Sedangkan Senin 23 & 30 Nov masih masa depan (total_jadwal terjadwal sebulan = 5 sesi)
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00', 'Asia/Jakarta'));

        $jadwalMingguan = Jadwal::create([
            'instruktur_id'    => $this->instruktur->id,
            'kode_kelas'       => 'WEB-REG',
            'nama_kelas'       => 'Web Reguler',
            'materi'           => 'Fullstack Laravel',
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
                'instruktur_id' => $this->instruktur->id,
                'jadwal_id'     => $jadwalMingguan->id,
                'tanggal'       => $tgl,
                'jam_checkin'   => '08:00',
                'jam_checkout'  => '10:00',
                'status'        => 'hadir',
                'sumber'        => 'checkin_app',
            ]);
        }

        KinerjaCalculationService::clearMemo();
        $hasil = app(KinerjaCalculationService::class)->evaluateInstruktur($this->instruktur->fresh(), '2026-11-01', '2026-11-30');

        $this->assertEquals(5, $hasil['total_jadwal']);
        $this->assertEquals(3, $hasil['total_jadwal_selesai']);
        $this->assertEquals(6.0, $hasil['jam_mengajar']);

        // Grafik Aktivitas di Dashboard hanya menghitung 3 pertemuan yang sudah lewat, bukan 5 (tidak menghitung 23 & 30 Nov)
        $dashRes = $this->actingAs($this->admin)->get(route('admin.dashboard', ['periode' => 'bulan_ini']));
        $dashRes->assertOk();
        $aktChart = $dashRes->viewData('aktivitasChartData');
        $this->assertEquals(3, array_sum($aktChart['terjadwal']));
        $this->assertEquals(6.0, $dashRes->viewData('metrics')['total_jam_mengajar']);

        Carbon::setTestNow();
    }

    public function test_laporan_recalculates_per_kelas_preserves_archived_instruktur_and_deduplicates_kelas_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 15:00:00', 'Asia/Jakarta'));

        $kelasA = Kelas::create([
            'kode_kelas'    => 'KLS-A',
            'nama_kelas'    => 'Kelas Unggulan A',
            'kuota_peserta' => 20,
            'status'        => 'aktif',
        ]);
        $kelasB = Kelas::create([
            'kode_kelas'    => 'KLS-B',
            'nama_kelas'    => 'Kelas Reguler B',
            'kuota_peserta' => 20,
            'status'        => 'aktif',
        ]);

        // Kelas A: 1 jadwal selesai (2 jam), hadir 100%, evaluasi 90.0
        // Ditambah 1 jadwal historis Kelas A tanpa kelas_id (nama_kelas sama) -> Kelas Diampu tetap terhitung 1 kelas unik untuk Kelas A!
        $jadwalA1 = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kelas_id'      => $kelasA->id,
            'kode_kelas'    => 'KLS-A',
            'nama_kelas'    => 'Kelas Unggulan A',
            'materi'        => 'Modul 1',
            'tanggal'       => '2026-11-10',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);
        $jadwalAHistoris = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kelas_id'      => null,
            'kode_kelas'    => 'KLS-A',
            'nama_kelas'    => 'Kelas Unggulan A',
            'materi'        => 'Modul 2',
            'tanggal'       => '2026-11-12',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        foreach ([$jadwalA1, $jadwalAHistoris] as $ja) {
            Absensi::create([
                'instruktur_id' => $this->instruktur->id,
                'jadwal_id'     => $ja->id,
                'tanggal'       => $ja->tanggal->toDateString(),
                'jam_checkin'   => '08:00',
                'jam_checkout'  => '10:00',
                'status'        => 'hadir',
                'sumber'        => 'checkin_app',
            ]);
        }
        Evaluasi::create([
            'instruktur_id' => $this->instruktur->id,
            'jadwal_id'     => $jadwalA1->id,
            'nama_pengisi'  => 'Peserta A',
            'total_nilai'   => 90.0,
            'jawaban'       => ['skor_indikator' => ['indikator_1' => 4.5], 'rata_rata_skala_5' => 4.5],
        ]);

        // Kelas B: 1 jadwal terlewat tanpa absensi (Alpa -> 0%), evaluasi 60.0
        $jadwalB1 = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kelas_id'      => $kelasB->id,
            'kode_kelas'    => 'KLS-B',
            'nama_kelas'    => 'Kelas Reguler B',
            'materi'        => 'Modul B1',
            'tanggal'       => '2026-11-14',
            'jam_mulai'     => '13:00',
            'jam_selesai'   => '15:00',
            'status'        => 'terjadwal',
        ]);
        Evaluasi::create([
            'instruktur_id' => $this->instruktur->id,
            'jadwal_id'     => $jadwalB1->id,
            'nama_pengisi'  => 'Peserta B',
            'total_nilai'   => 60.0,
            'jawaban'       => ['skor_indikator' => ['indikator_1' => 3.0], 'rata_rata_skala_5' => 3.0],
        ]);

        // 1. Cek deduplikasi Kelas Diampu di Dashboard: hanya 2 kelas unik (KLS-A & KLS-B), bukan 3!
        $dash = $this->actingAs($this->admin)->get(route('admin.dashboard', ['periode' => 'bulan_ini']));
        $dash->assertOk();
        $this->assertEquals(2, $dash->viewData('metrics')['total_kelas_diampu']);

        // 2. Laporan Kinerja difilter khusus Kelas A -> kehadiran 100%, evaluasi 90.0, jam mengajar 4.0 jam (tidak tercampur Kelas B!)
        KinerjaCalculationService::clearMemo();
        $lapKelasA = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'jenis'  => 'kinerja',
            'kelas'  => (string) $kelasA->id,
            'dari'   => '2026-11-01',
            'sampai' => '2026-11-30',
        ]));
        $lapKelasA->assertOk();
        $rowA = collect($lapKelasA->viewData('data'))->firstWhere(fn ($r) => $r['instruktur']->id === $this->instruktur->id);
        $this->assertNotNull($rowA);
        $this->assertEquals(4.0, $rowA['jam_mengajar']);
        $this->assertEquals(100.0, $rowA['skor_kehadiran']);
        $this->assertEquals(90.0, $rowA['skor_evaluasi']);

        // 3. Rapor Individual untuk instrukturKosong pada Kelas A -> skor_akhir harus null (tidak fallback ke skor lain)
        $indKosong = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'jenis'      => 'individual',
            'instruktur' => (string) $this->instrukturKosong->id,
            'kelas'      => (string) $kelasA->id,
            'dari'       => '2026-11-01',
            'sampai'     => '2026-11-30',
        ]));
        $indKosong->assertOk();
        $this->assertNull($indKosong->viewData('individualReport')['kinerja']['skor_akhir']);

        // 4. Ringkasan Manajerial pada Kelas A -> rata_kehadiran = 100.0 (instrukturKosong yang tanpa jadwal tidak menarik rata-rata ke 50%)
        $manajerialA = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'jenis'  => 'manajerial',
            'kelas'  => (string) $kelasA->id,
            'dari'   => '2026-11-01',
            'sampai' => '2026-11-30',
        ]));
        $manajerialA->assertOk();
        $this->assertEquals(100.0, $manajerialA->viewData('manajerialReport')['kpi']['rata_kehadiran']);

        // 5. Arsipkan instruktur (Soft Delete) -> riwayat kinerjanya tetap muncul di Monitoring Kinerja & Laporan Kinerja!
        $this->actingAs($this->admin)->delete(route('admin.instruktur.destroy', $this->instruktur))
            ->assertRedirect(route('admin.instruktur.index'));
        $this->assertSoftDeleted('instrukturs', ['id' => $this->instruktur->id]);

        KinerjaCalculationService::clearMemo();
        $monRes = $this->actingAs($this->admin)->get(route('admin.kinerja.index', [
            'dari'   => '2026-11-01',
            'sampai' => '2026-11-30',
        ]));
        $monRes->assertOk();
        $this->assertTrue(collect($monRes->viewData('dataKinerja'))->contains(fn ($r) => $r['instruktur']->id === $this->instruktur->id));

        Carbon::setTestNow();
    }

    public function test_rapor_instruktur_deduplicates_peserta_and_supervisor_show_handles_null_evaluasi(): void
    {
        $jadwal = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'nama_kelas'    => 'Web Pagi',
            'materi'        => 'CSS Grid',
            'tanggal'       => today()->toDateString(),
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        // Dua baris AktivitasMengajar untuk jadwal & tanggal yang sama -> tidak boleh dijumlahkan ganda
        AktivitasMengajar::create([
            'jadwal_id'            => $jadwal->id,
            'instruktur_id'        => $this->instruktur->id,
            'tanggal'              => today()->toDateString(),
            'materi_disampaikan'   => 'CSS Grid Draft',
            'jumlah_peserta_hadir' => 18,
            'status_konfirmasi'    => 'dikonfirmasi',
        ]);
        AktivitasMengajar::create([
            'jadwal_id'            => $jadwal->id,
            'instruktur_id'        => $this->instruktur->id,
            'tanggal'              => today()->toDateString(),
            'materi_disampaikan'   => 'CSS Grid Final',
            'jumlah_peserta_hadir' => 20,
            'status_konfirmasi'    => 'dikonfirmasi',
        ]);

        $raporRes = $this->actingAs($this->insUser)->get(route('instruktur.rapor.index'));
        $raporRes->assertOk();
        $this->assertEquals(20, $raporRes->viewData('totalPesertaDilatih'));

        // Evaluasi komentar tanpa total_nilai tidak ditampilkan sebagai 0.00 di halaman Detail Instruktur Supervisor
        Evaluasi::create([
            'instruktur_id' => $this->instruktur->id,
            'nama_pengisi'  => 'Peserta Komentar',
            'total_nilai'   => null,
            'komentar'      => 'Bagus sekali',
        ]);

        $spvShow = $this->actingAs($this->supervisor)->get(route('supervisor.instruktur.show', $this->instruktur));
        $spvShow->assertOk();
        $this->assertNull($spvShow->viewData('statsEvaluasi')['rata_rata']);
    }
}
