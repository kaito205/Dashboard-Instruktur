<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Evaluasi;
use App\Models\Instruktur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Membangun file .xlsx valid di direktori temporary dengan ZipArchive untuk pengujian importer.
 *
 * @param  array<int, array<int, mixed>>  $rows
 */
function buildTestXlsx(array $rows, bool $isDate1904 = false): string
{
    $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';

    $sharedStrings = [];
    $sharedIndex   = [];

    $colLetter = function (int $idx): string {
        $letter = '';
        $n      = $idx + 1;
        while ($n > 0) {
            $rem    = ($n - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $n      = intdiv($n - 1, 26);
        }
        return $letter;
    };

    $sheetRowsXml = '';
    foreach ($rows as $rIdx => $row) {
        $rowNum   = $rIdx + 1;
        $cellsXml = '';

        foreach ($row as $cIdx => $val) {
            if ($val === null || $val === '') {
                continue; // Uji sparse cell kosong di tengah baris
            }

            $ref = $colLetter($cIdx) . $rowNum;
            if (is_int($val) || is_float($val)) {
                $cellsXml .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
            } else {
                $strVal = (string) $val;
                if (! isset($sharedIndex[$strVal])) {
                    $sharedIndex[$strVal] = count($sharedStrings);
                    $sharedStrings[]      = $strVal;
                }
                $cellsXml .= '<c r="' . $ref . '" t="s"><v>' . $sharedIndex[$strVal] . '</v></c>';
            }
        }

        $sheetRowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
    }

    $siXml = '';
    foreach ($sharedStrings as $s) {
        $siXml .= '<si><t>' . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
    }

    $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">'
        . $siXml
        . '</sst>';

    $date1904Attr = $isDate1904 ? ' date1904="1"' : '';
    $workbookXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<workbookPr' . $date1904Attr . '/>'
        . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets>'
        . '</workbook>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . $sheetRowsXml . '</sheetData>'
        . '</worksheet>';

    $zip = new \ZipArchive();
    $zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    return $tmpPath;
}

function createAdminAndInstruktur(): array
{
    $admin = User::create([
        'name'      => 'Admin Test',
        'email'     => 'admin.import@lpkcti.id',
        'password'  => 'Admin@123',
        'role'      => 'admin',
        'is_active' => true,
    ]);

    $instruktur = Instruktur::create([
        'kode_instruktur'     => 'INS-CTI01',
        'nama_lengkap'        => 'Sandi Yana, S.Kom',
        'email'               => 'sandi.yana@lpkcti.id',
        'status'              => 'aktif',
        'status_ketersediaan' => 'tersedia',
    ]);

    return [$admin, $instruktur];
}

test('importer absensi mengonversi Excel serial date, serial time, sparse empty cell, dan format teks pada preview dan simpan', function () {
    [$admin, $instruktur] = createAdminAndInstruktur();

    $xlsxPath = buildTestXlsx([
        ['nama_instruktur', 'tanggal', 'jam_checkin', 'jam_checkout', 'status', 'keterangan'],
        ['Sandi Yana, S.Kom', 46336, 0.333333333333333, 0.5, 'hadir', 'Serial Excel 1'],
        ['Sandi Yana, S.Kom', 46346, '', null, 'izin', 'Sel kosong izin'],
        ['Sandi Yana, S.Kom', '23/11/2026', '08.30', '12.30', 'terlambat', 'Format teks Indonesia'],
    ]);

    try {
        $file = new UploadedFile(
            $xlsxPath,
            'absensi_serial.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $previewResponse = $this->actingAs($admin)->post('/admin/import/preview', [
            'jenis' => 'absensi',
            'file'  => $file,
        ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('2026-11-10');
        $previewResponse->assertSee('08:00');
        $previewResponse->assertSee('12:00');
        $previewResponse->assertSee('2026-11-20');
        $previewResponse->assertSee('2026-11-23');
        $previewResponse->assertSee('08:30');
        $previewResponse->assertSee('12:30');
        $previewResponse->assertViewHas('summary', fn (array $summary) => $summary['valid'] === 3 && $summary['invalid'] === 0);

        $prosesResponse = $this->actingAs($admin)->post('/admin/import/proses');
        $prosesResponse->assertRedirect('/admin/import');
        $prosesResponse->assertSessionHas('success');

        $rows = Absensi::where('instruktur_id', $instruktur->id)->orderBy('tanggal')->get();
        expect($rows)->toHaveCount(3);
        expect($rows[0]->tanggal->toDateString())->toBe('2026-11-10');
        expect(substr((string) $rows[0]->jam_checkin, 0, 5))->toBe('08:00');
        expect(substr((string) $rows[0]->jam_checkout, 0, 5))->toBe('12:00');
        expect($rows[0]->status)->toBe('hadir');

        expect($rows[1]->tanggal->toDateString())->toBe('2026-11-20');
        expect($rows[1]->jam_checkin)->toBeNull();
        expect($rows[1]->jam_checkout)->toBeNull();
        expect($rows[1]->status)->toBe('izin');

        expect($rows[2]->tanggal->toDateString())->toBe('2026-11-23');
        expect(substr((string) $rows[2]->jam_checkin, 0, 5))->toBe('08:30');
        expect(substr((string) $rows[2]->jam_checkout, 0, 5))->toBe('12:30');
        expect($rows[2]->status)->toBe('terlambat');
    } finally {
        @unlink($xlsxPath);
    }
});

test('importer absensi menolak tanggal atau jam tidak valid saat preview', function () {
    [$admin] = createAdminAndInstruktur();

    $xlsxPath = buildTestXlsx([
        ['nama_instruktur', 'tanggal', 'jam_checkin', 'jam_checkout', 'status', 'keterangan'],
        ['Sandi Yana, S.Kom', 46346, '25:99', 0.5, 'hadir', 'Jam tidak valid'],
        ['Sandi Yana, S.Kom', 'bukan-tanggal', 0.333333333333333, 0.5, 'hadir', 'Tanggal tidak valid'],
    ]);

    try {
        $file = new UploadedFile(
            $xlsxPath,
            'absensi_invalid.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $previewResponse = $this->actingAs($admin)->post('/admin/import/preview', [
            'jenis' => 'absensi',
            'file'  => $file,
        ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Format jam check-in tidak valid');
        $previewResponse->assertSee('Format tanggal tidak valid');
        $previewResponse->assertViewHas('summary', fn (array $summary) => $summary['valid'] === 0 && $summary['invalid'] === 2);
    } finally {
        @unlink($xlsxPath);
    }
});

test('importer evaluasi mengonversi Excel serial tanggal dan timestamp tanpa mencampurnya ke rata-rata nilai', function () {
    [$admin, $instruktur] = createAdminAndInstruktur();

    $xlsxPath = buildTestXlsx([
        ['nama_instruktur', 'nama_pengisi', 'tanggal', 'timestamp', 'penguasaan_materi', 'cara_penyampaian'],
        ['Sandi Yana, S.Kom', 'Peserta Uji', 46346, 46346.3333333333, 80, 90],
    ]);

    try {
        $file = new UploadedFile(
            $xlsxPath,
            'evaluasi_serial.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $previewResponse = $this->actingAs($admin)->post('/admin/import/preview', [
            'jenis' => 'evaluasi',
            'file'  => $file,
        ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('2026-11-20');
        $previewResponse->assertSee('2026-11-20 08:00:00');
        $previewResponse->assertViewHas('summary', fn (array $summary) => $summary['valid'] === 1 && $summary['invalid'] === 0);

        $prosesResponse = $this->actingAs($admin)->post('/admin/import/proses');
        $prosesResponse->assertRedirect('/admin/import');
        $prosesResponse->assertSessionHas('success');

        $eval = Evaluasi::where('instruktur_id', $instruktur->id)->first();
        expect($eval)->not->toBeNull();
        expect((float) $eval->total_nilai)->toBe(85.0);
        expect($eval->jawaban['tanggal'])->toBe('2026-11-20');
        expect($eval->jawaban['timestamp'])->toBe('2026-11-20 08:00:00');
        expect($eval->created_at->format('Y-m-d H:i:s'))->toBe('2026-11-20 08:00:00');
    } finally {
        @unlink($xlsxPath);
    }
});
