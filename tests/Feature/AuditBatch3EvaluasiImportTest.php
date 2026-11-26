<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\KodeEvaluasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditBatch3EvaluasiImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $insUser;
    protected Instruktur $instruktur;
    protected Instruktur $instrukturMirip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'Admin Batch3',
            'email'     => 'admin.b3@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->insUser = User::create([
            'name'      => 'Supriyadi',
            'email'     => 'supriyadi.b3@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);
        $this->instruktur = $this->insUser->syncInstrukturProfile('Jaringan Komputer');

        $this->instrukturMirip = Instruktur::create([
            'kode_instruktur' => 'INS-CTI88',
            'nama_lengkap'    => 'Adi Nugroho, S.T.',
            'email'           => 'adi.nugroho@lpkcti.id',
            'status'          => 'aktif',
        ]);
    }

    public function test_instruktur_evaluasi_panel_displays_skor_indikator_and_materi_evaluasi(): void
    {
        Evaluasi::create([
            'instruktur_id'   => $this->instruktur->id,
            'nama_pengisi'    => 'Peserta 1',
            'materi_evaluasi' => 'Routing Dinamis OSPF',
            'total_nilai'     => 92.0,
            'jawaban'         => [
                'skor_indikator' => [
                    'indikator_1' => 5,
                    'indikator_2' => 4,
                    'indikator_3' => 5,
                    'indikator_4' => 4,
                    'indikator_5' => 5,
                ],
                'rata_rata_skala_5' => 4.6,
            ],
        ]);

        // Evaluasi tanpa nilai numerik tidak boleh mengubah rata-rata menjadi 0
        Evaluasi::create([
            'instruktur_id'   => $this->instruktur->id,
            'nama_pengisi'    => 'Peserta Komentar',
            'materi_evaluasi' => 'Switching VLAN',
            'total_nilai'     => null,
            'komentar'        => 'Materi sangat bermanfaat',
            'jawaban'         => ['komentar' => 'Materi sangat bermanfaat'],
        ]);

        $res = $this->actingAs($this->insUser)->get(route('instruktur.evaluasi.index'));
        $res->assertOk()
            ->assertSee('Routing Dinamis OSPF')
            ->assertSee('Switching VLAN')
            ->assertSee('I1:5')
            ->assertSee('I2:4')
            ->assertSee('5.00');

        $rataPerIndikator = $res->viewData('rataPerIndikator');
        $this->assertEquals(5.0, $rataPerIndikator['indikator_1']['avg']);
        $this->assertEquals(92.0, $res->viewData('rataNilai100'));

        // Cek Evaluasi Admin juga tidak memaksa null menjadi 0.0
        $resAdmin = $this->actingAs($this->admin)->get(route('admin.evaluasi.index'));
        $resAdmin->assertOk();
        $rekap = collect($resAdmin->viewData('rekapPerInstruktur'))
            ->first(fn ($r) => $r['instruktur']->id === $this->instruktur->id);
        $this->assertEquals(92.0, $rekap['rata_rata']);
    }

    public function test_import_evaluasi_ignores_nomor_urut_column_and_imports_all_anonymous_likert_rows(): void
    {
        Storage::fake('local');

        $rows = [
            'No,nama_instruktur,nama_pengisi,Pertanyaan 1,Pertanyaan 2,Pertanyaan 3,Pertanyaan 4,Pertanyaan 5',
        ];
        // 8 responden anonim memberi skor Likert 4,4,4,4,4 (konversi skala 100 = 80.00), bahkan saat No >= 6!
        for ($no = 1; $no <= 8; $no++) {
            $rows[] = "{$no},Supriyadi,,4,4,4,4,4";
        }

        $file = UploadedFile::fake()->createWithContent('google_forms_anonim.csv', implode("\n", $rows));

        $previewRes = $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'evaluasi',
            'file'  => $file,
        ]);
        $previewRes->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['total'] === 8 && $s['valid'] === 8 && $s['duplikat'] === 0);

        $token = $previewRes->viewData('previewToken');
        $this->assertNotEmpty($token);

        $this->actingAs($this->admin)->post(route('admin.import.proses'), [
            'preview_token' => $token,
        ])->assertRedirect(route('admin.import.index'));

        $evals = Evaluasi::where('instruktur_id', $this->instruktur->id)->get();
        $this->assertCount(8, $evals);
        foreach ($evals as $ev) {
            $this->assertEquals(80.0, (float) $ev->total_nilai);
            $this->assertEquals(4.0, (float) $ev->rata_rata_skala5);
        }
    }

    public function test_import_absensi_rejects_typo_status_allows_multi_session_blocks_substring_name_and_isolates_multitab_preview(): void
    {
        Storage::fake('local');

        $jadwalPagi = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'JRK-PAGI',
            'nama_kelas'    => 'Jaringan Pagi',
            'materi'        => 'TCP/IP',
            'tanggal'       => '2026-11-18',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'terjadwal',
        ]);
        $jadwalSiang = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kode_kelas'    => 'JRK-SIANG',
            'nama_kelas'    => 'Jaringan Siang',
            'materi'        => 'Firewall',
            'tanggal'       => '2026-11-18',
            'jam_mulai'     => '13:00',
            'jam_selesai'   => '15:00',
            'status'        => 'terjadwal',
        ]);

        // Tab 1: Berkas absensi (2 sesi valid di hari yang sama, 1 salah ketik status 'hadri', 1 nama substring 'Adi' yang tidak boleh nyasar ke Supriyadi)
        $csvTab1 = implode("\n", [
            'nama_instruktur,tanggal,jam_checkin,jam_checkout,status,keterangan',
            'Supriyadi,2026-11-18,08:00,10:00,hadir,Sesi Pagi',
            'Supriyadi,2026-11-18,13:05,15:00,hadir,Sesi Siang',
            'Supriyadi,2026-11-19,08:00,10:00,hadri,Salah Ketik Status',
            'Adi,2026-11-19,08:00,10:00,hadir,Nama Pendek Ambigu',
        ]);
        $fileTab1 = UploadedFile::fake()->createWithContent('tab1_absensi.csv', $csvTab1);

        $prevTab1 = $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'absensi',
            'file'  => $fileTab1,
        ]);
        $prevTab1->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['valid'] === 2 && $s['invalid'] === 2);
        $tokenTab1 = $prevTab1->viewData('previewToken');

        // Tab 2 dibuka sebelum Tab 1 disimpan -> tidak boleh menimpa payload Tab 1
        $csvTab2 = implode("\n", [
            'nama_instruktur,nama_pengisi,nilai',
            'Adi Nugroho,Peserta A,90',
        ]);
        $fileTab2 = UploadedFile::fake()->createWithContent('tab2_evaluasi.csv', $csvTab2);
        $prevTab2 = $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'evaluasi',
            'file'  => $fileTab2,
        ]);
        $prevTab2->assertOk();
        $tokenTab2 = $prevTab2->viewData('previewToken');

        // Proses Tab 1 menggunakan tokenTab1 -> mengimpor 2 sesi absensi milik Supriyadi
        $this->actingAs($this->admin)->post(route('admin.import.proses'), ['preview_token' => $tokenTab1])
            ->assertRedirect(route('admin.import.index'));

        $absRows = Absensi::where('instruktur_id', $this->instruktur->id)->orderBy('jam_checkin')->get();
        $this->assertCount(2, $absRows);
        $this->assertEquals($jadwalPagi->id, $absRows[0]->jadwal_id);
        $this->assertEquals($jadwalSiang->id, $absRows[1]->jadwal_id);
        $this->assertNotNull($absRows[0]->batch_import);

        // Proses Tab 2 menggunakan tokenTab2 -> tetap berhasil mengimpor evaluasi milik Adi Nugroho, S.T.
        $this->actingAs($this->admin)->post(route('admin.import.proses'), ['preview_token' => $tokenTab2])
            ->assertRedirect(route('admin.import.index'));

        $this->assertEquals(1, Evaluasi::where('instruktur_id', $this->instrukturMirip->id)->count());
    }

    public function test_kode_evaluasi_length_safety_today_expiry_archived_guard_and_duplicate_name_protection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 14:00:00', 'Asia/Jakarta'));

        $kelasLama = Kelas::create([
            'kode_kelas'    => 'RPL-2025-GELOMBANG-1-REGULER-PAGI-CIAMIS-PANJANG',
            'nama_kelas'    => 'RPL Reguler Pagi',
            'kuota_peserta' => 20,
            'status'        => 'selesai',
        ]);
        $kelasBaru = Kelas::create([
            'kode_kelas'    => 'RPL-2026-GELOMBANG-2-REGULER-PAGI-CIAMIS-PANJANG',
            'nama_kelas'    => 'RPL Reguler Pagi',
            'kuota_peserta' => 20,
            'status'        => 'aktif',
        ]);

        // Jadwal lama di kelas gelombang 1 (nama_kelas sama persis) vs jadwal baru di gelombang 2
        $jadwalLama = Jadwal::create([
            'instruktur_id' => $this->instrukturMirip->id,
            'kelas_id'      => $kelasLama->id,
            'kode_kelas'    => $kelasLama->kode_kelas,
            'nama_kelas'    => 'RPL Reguler Pagi',
            'materi'        => 'HTML Lama',
            'tanggal'       => '2025-05-10',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);
        $jadwalBaru = Jadwal::create([
            'instruktur_id' => $this->instruktur->id,
            'kelas_id'      => $kelasBaru->id,
            'kode_kelas'    => $kelasBaru->kode_kelas,
            'nama_kelas'    => 'RPL Reguler Pagi',
            'materi'        => 'Laravel 12',
            'tanggal'       => '2026-11-20',
            'jam_mulai'     => '08:00',
            'jam_selesai'   => '10:00',
            'status'        => 'selesai',
        ]);

        // Buat 2 kode evaluasi dengan prefix panjang + expired_at hari ini -> panjang <= 50 & berlaku s/d 23:59:59 hari ini
        KodeEvaluasi::create([
            'kelas_id'   => $kelasBaru->id,
            'kode'       => $kelasBaru->kode_kelas,
            'nama_kelas' => $kelasBaru->nama_kelas,
            'is_active'  => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.evaluasi.kode.store'), [
            'kelas_id'   => $kelasBaru->id,
            'expired_at' => '2026-11-20',
        ])->assertRedirect(route('admin.evaluasi.kode.index'));

        $kodeBaru = KodeEvaluasi::latest('id')->firstOrFail();
        $this->assertLessThanOrEqual(50, strlen($kodeBaru->kode));
        $this->assertTrue($kodeBaru->isValid());

        // resolveSesiJadwal hanya menarik jadwal kelasBaru, tidak bocor ke jadwalLama yang nama_kelas-nya sama
        $sesiIds = $kodeBaru->resolveSesiJadwal()->pluck('id')->all();
        $this->assertContains($jadwalBaru->id, $sesiIds);
        $this->assertNotContains($jadwalLama->id, $sesiIds);

        // Pengiriman pertama atas nama 'Rizky Pratama' berhasil
        $this->post(route('evaluasi.publik.store'), [
            'kode'         => $kodeBaru->kode,
            'jadwal_id'    => $jadwalBaru->id,
            'nama_pengisi' => 'Rizky Pratama',
            'indikator_1'  => 5,
            'indikator_2'  => 5,
            'indikator_3'  => 5,
            'indikator_4'  => 5,
            'indikator_5'  => 5,
        ])->assertRedirect(route('evaluasi.publik.berhasil'));

        // Simulasi mode Incognito (session dikosongkan) oleh nama pengisi yang sama -> tetap ditolak!
        $this->flushSession();
        $this->post(route('evaluasi.publik.store'), [
            'kode'         => $kodeBaru->kode,
            'jadwal_id'    => $jadwalBaru->id,
            'nama_pengisi' => 'rizky pratama',
            'indikator_1'  => 4,
            'indikator_2'  => 4,
            'indikator_3'  => 4,
            'indikator_4'  => 4,
            'indikator_5'  => 4,
        ])->assertSessionHasErrors('nama_pengisi');

        // Jika kelas dinonaktifkan, isValid() langsung false
        $kelasBaru->update(['status' => 'nonaktif']);
        $this->assertFalse($kodeBaru->fresh()->isValid());

        Carbon::setTestNow();
    }
}
