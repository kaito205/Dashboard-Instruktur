<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Evaluasi;
use App\Models\ImportHistory;
use App\Models\Instruktur;
use App\Models\InstrukturSertifikasi;
use App\Models\Jadwal;
use App\Models\User;
use App\Services\KinerjaCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExportAndReadinessTest extends TestCase
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
            'name'      => 'Admin Import Export',
            'email'     => 'admin.ie@lpkcti.id',
            'password'  => 'Admin@123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name'      => 'Supervisor Import Export',
            'email'     => 'spv.ie@lpkcti.id',
            'password'  => 'Supervisor@123',
            'role'      => 'supervisor',
            'is_active' => true,
        ]);

        $this->instrukturUser = User::create([
            'name'      => 'Budi Santoso',
            'email'     => 'budi.ie@lpkcti.id',
            'password'  => 'Instruktur@123',
            'role'      => 'instruktur',
            'is_active' => true,
        ]);

        $this->instruktur = $this->instrukturUser->syncInstrukturProfile('Jaringan Komputer');
    }

    public function test_download_templates_and_rbac_on_import_module(): void
    {
        // Admin dapat mengunduh template CSV absensi dan evaluasi
        $resAbsensi = $this->actingAs($this->admin)->get(route('admin.import.template', 'absensi'));
        $resAbsensi->assertOk();
        $this->assertStringContainsString('nama_instruktur,tanggal,jam_checkin,jam_checkout,status,keterangan', $resAbsensi->streamedContent());

        $resEvaluasi = $this->actingAs($this->admin)->get(route('admin.import.template', 'evaluasi'));
        $resEvaluasi->assertOk();
        $this->assertStringContainsString('nama_instruktur,nama_pengisi,nilai,penguasaan_materi,cara_penyampaian,catatan_peserta', $resEvaluasi->streamedContent());

        // Supervisor dan Instruktur dilarang mengakses endpoint import
        $this->actingAs($this->supervisor)->get(route('admin.import.index'))->assertForbidden();
        $this->actingAs($this->instrukturUser)->get(route('admin.import.index'))->assertForbidden();
    }

    public function test_import_preview_uses_local_disk_json_and_handles_absensi_edge_cases_and_duplicates(): void
    {
        Storage::fake('local');

        $csvContent = implode("\n", [
            'nama_instruktur,tanggal,jam_checkin,jam_checkout,status,keterangan',
            'Budi Santoso,2026-11-10,08:00,12:00,hadir,Sesi Pagi Normal',
            '   ,   ,   ,   ,   ,   ', // Baris kosong di tengah file harus diabaikan
            'Budi Santoso,2026-11-11,08:00,12:00,izin,Izin Sakit', // Status izin harus mengosongkan jam checkin/checkout
            'Budi Santoso,2026-11-12,13:00,09:00,hadir,Jam Terbalik', // Invalid: checkout < checkin
            'Budi Santoso,2026-11-10,08:05,12:00,hadir,Duplikat Di File', // Duplikat baris pertama
        ]);

        $file = UploadedFile::fake()->createWithContent('absensi_batch.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'absensi',
            'file'  => $file,
        ]);

        $previewResponse->assertOk();
        $previewResponse->assertViewHas('summary', function (array $summary): bool {
            return $summary['total'] === 4
                && $summary['valid'] === 2
                && $summary['invalid'] === 1
                && $summary['duplikat'] === 1;
        });

        // Pastikan payload preview disimpan di disk local (bukan membebani session database)
        $previewPath = 'import_previews/preview_user_' . $this->admin->id . '.json';
        Storage::disk('local')->assertExists($previewPath);
        $this->assertFalse(session()->has('import_preview_' . $this->admin->id));

        // Eksekusi proses import
        $prosesResponse = $this->actingAs($this->admin)->post(route('admin.import.proses'));
        $prosesResponse->assertRedirect(route('admin.import.index'));

        // File JSON preview harus otomatis dihapus setelah proses()
        Storage::disk('local')->assertMissing($previewPath);

        // Hanya 2 baris valid yang tersimpan
        $this->assertEquals(2, Absensi::where('instruktur_id', $this->instruktur->id)->count());

        $absenIzin = Absensi::where('instruktur_id', $this->instruktur->id)
            ->whereDate('tanggal', '2026-11-11')
            ->firstOrFail();
        $this->assertEquals('izin', $absenIzin->status);
        $this->assertNull($absenIzin->jam_checkin);
        $this->assertNull($absenIzin->jam_checkout);

        $history = ImportHistory::latest('id')->firstOrFail();
        $this->assertEquals('parsial', $history->status);
        $this->assertEquals(4, $history->total_baris);
        $this->assertEquals(2, $history->baris_berhasil);
        $this->assertEquals(2, $history->baris_gagal);
    }

    public function test_import_evaluasi_normalizes_likert_1_to_5_scale_and_prevents_database_duplicates(): void
    {
        Storage::fake('local');

        // Baris 1: Skala Likert 1-5 (4 dan 5 -> rata-rata 4.5 -> total_nilai harus 90.00 di skala 100)
        // Baris 2: Skala 0-100 (80 dan 90 -> rata-rata 85.00 -> rata_rata_skala_5 harus 4.25)
        $csvContent = implode("\n", [
            'nama_instruktur,nama_pengisi,tanggal,penguasaan_materi,cara_penyampaian,catatan_peserta',
            'Budi Santoso,Peserta Skala 5,2026-11-15,4,5,Sangat jelas',
            'Budi Santoso,Peserta Skala 100,2026-11-15,80,90,Mudah diikuti',
        ]);

        $file = UploadedFile::fake()->createWithContent('evaluasi_skala.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'evaluasi',
            'file'  => $file,
        ])->assertOk();

        $this->actingAs($this->admin)->post(route('admin.import.proses'))
            ->assertRedirect(route('admin.import.index'));

        $evalSkala5 = Evaluasi::where('instruktur_id', $this->instruktur->id)
            ->where('nama_pengisi', 'Peserta Skala 5')
            ->firstOrFail();
        $this->assertEquals(90.0, (float) $evalSkala5->total_nilai);
        $this->assertEquals(4.5, (float) $evalSkala5->rata_rata_skala5);
        $this->assertEquals(4.0, (float) ($evalSkala5->jawaban['skor_indikator']['indikator_1'] ?? 0));
        $this->assertEquals(5.0, (float) ($evalSkala5->jawaban['skor_indikator']['indikator_2'] ?? 0));

        $evalSkala100 = Evaluasi::where('instruktur_id', $this->instruktur->id)
            ->where('nama_pengisi', 'Peserta Skala 100')
            ->firstOrFail();
        $this->assertEquals(85.0, (float) $evalSkala100->total_nilai);
        $this->assertEquals(4.25, (float) $evalSkala100->rata_rata_skala5);

        // Pastikan KinerjaCalculationService membaca skor evaluasi skala 0-100 dengan akurat ((90 + 85) / 2 = 87.5)
        $kinerja = app(KinerjaCalculationService::class)->evaluateInstruktur(
            $this->instruktur,
            '2026-11-01',
            '2026-11-30'
        );
        $this->assertEquals(87.5, (float) $kinerja['skor_evaluasi']);

        // Mengimpor ulang file yang sama harus mendeteksi duplikat di database dan tidak menggandakan record
        $fileRepeat = UploadedFile::fake()->createWithContent('evaluasi_skala_ulang.csv', $csvContent);
        $previewRepeat = $this->actingAs($this->admin)->post(route('admin.import.preview'), [
            'jenis' => 'evaluasi',
            'file'  => $fileRepeat,
        ]);
        $previewRepeat->assertViewHas('summary', fn (array $s): bool => $s['duplikat'] === 2 && $s['valid'] === 0);

        $this->actingAs($this->admin)->post(route('admin.import.proses'));
        $this->assertEquals(2, Evaluasi::where('instruktur_id', $this->instruktur->id)->count());
    }

    public function test_exports_sanitize_formula_injection_in_csv_and_xlsx_and_handle_empty_data(): void
    {
        // 1. Uji kondisi data kosong (0 baris) pada CSV, Excel, dan halaman Cetak
        $emptyCsv = $this->actingAs($this->admin)->get(route('admin.laporan.export', [
            'jenis'  => 'absensi',
            'dari'   => '2026-01-01',
            'sampai' => '2026-01-31',
        ]));
        $emptyCsv->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $emptyCsv->streamedContent());

        $emptyXlsx = $this->actingAs($this->admin)->get(route('admin.laporan.export-excel', [
            'jenis'  => 'absensi',
            'dari'   => '2026-01-01',
            'sampai' => '2026-01-31',
        ]));
        $emptyXlsx->assertOk();

        $emptyCetak = $this->actingAs($this->admin)->get(route('admin.laporan.cetak', [
            'jenis'  => 'absensi',
            'dari'   => '2026-01-01',
            'sampai' => '2026-01-31',
        ]));
        $emptyCetak->assertOk();

        // 2. Uji sanitasi Formula Injection (=, +, -, @) pada CSV dan XLSX
        Evaluasi::create([
            'instruktur_id'   => $this->instruktur->id,
            'nama_pengisi'    => '=CMD|\' /C calc\'!A0',
            'materi_evaluasi' => '+SUM(1,1)',
            'total_nilai'     => 88.0,
            'komentar'        => '@HYPERLINK("http://evil.local")',
            'jawaban'         => [
                'skor_indikator'    => ['indikator_1' => 4.4],
                'rata_rata_skala_5' => 4.4,
            ],
        ]);

        $csvRes = $this->actingAs($this->admin)->get(route('admin.laporan.export', [
            'jenis'  => 'evaluasi',
            'dari'   => now()->startOfMonth()->toDateString(),
            'sampai' => now()->endOfMonth()->toDateString(),
        ]));
        $csvRes->assertOk();
        $csvBody = $csvRes->streamedContent();

        $this->assertStringContainsString("'=CMD|", $csvBody);
        $this->assertStringContainsString("'+SUM(1,1)", $csvBody);
        $this->assertStringContainsString("'@HYPERLINK", $csvBody);

        $xlsxRes = $this->actingAs($this->admin)->get(route('admin.laporan.export-excel', [
            'jenis'  => 'evaluasi',
            'dari'   => now()->startOfMonth()->toDateString(),
            'sampai' => now()->endOfMonth()->toDateString(),
        ]));
        $xlsxRes->assertOk();

        $tmpXlsx = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tmpXlsx, $xlsxRes->streamedContent());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmpXlsx) === true);
        $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmpXlsx);

        $this->assertStringContainsString('&apos;=CMD|', $sheetXml);
        $this->assertStringContainsString('&apos;+SUM(1,1)', $sheetXml);
        $this->assertStringContainsString('&apos;@HYPERLINK', $sheetXml);
    }

    public function test_individual_report_displays_synchronized_certification_columns_across_web_print_and_export(): void
    {
        InstrukturSertifikasi::create([
            'instruktur_id'    => $this->instruktur->id,
            'nama_sertifikasi' => 'CCNA Routing & Switching',
            'berlaku_sampai'   => today()->addDays(90)->toDateString(),
            'file_path'        => 'instruktur/sertifikasi/ccna.pdf',
            'file_name'        => 'sertifikat_ccna_budi.pdf',
            'keterangan'       => 'Cisco Academy Indonesia',
        ]);

        $params = [
            'jenis'      => 'individual',
            'instruktur' => (string) $this->instruktur->id,
            'dari'       => now()->startOfMonth()->toDateString(),
            'sampai'     => now()->endOfMonth()->toDateString(),
        ];

        // Halaman Web Laporan (Admin & Supervisor)
        $webRes = $this->actingAs($this->admin)->get(route('admin.laporan.index', $params));
        $webRes->assertOk();
        $webRes->assertSee('CCNA Routing & Switching');
        $webRes->assertSee('Cisco Academy Indonesia');
        $webRes->assertSee('sertifikat_ccna_budi.pdf');

        // Halaman Cetak Laporan (Supervisor)
        $cetakRes = $this->actingAs($this->supervisor)->get(route('supervisor.laporan.cetak', $params));
        $cetakRes->assertOk();
        $cetakRes->assertSee('CCNA Routing & Switching');
        $cetakRes->assertSee('Cisco Academy Indonesia');
        $cetakRes->assertSee('sertifikat_ccna_budi.pdf');

        // Export CSV Rapor Individual
        $csvRes = $this->actingAs($this->admin)->get(route('admin.laporan.export', $params));
        $csvRes->assertOk();
        $csvBody = $csvRes->streamedContent();
        $this->assertStringContainsString('CCNA Routing & Switching', $csvBody);
        $this->assertStringContainsString('Cisco Academy Indonesia', $csvBody);
        $this->assertStringContainsString('sertifikat_ccna_budi.pdf', $csvBody);

        // Halaman Cetak Aktivitas Instruktur hanya menampilkan milik sendiri
        Jadwal::create([
            'instruktur_id'    => $this->instruktur->id,
            'nama_kelas'       => 'Jaringan Dasar',
            'materi'           => 'Subnetting IPv4',
            'tanggal'          => today()->toDateString(),
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '10:00',
            'is_pola_mingguan' => false,
            'status'           => 'terjadwal',
        ]);

        $insPdfRes = $this->actingAs($this->instrukturUser)->get(route('instruktur.aktivitas.export.pdf'));
        $insPdfRes->assertOk();
        $insPdfRes->assertSee('Budi Santoso');
    }
}
