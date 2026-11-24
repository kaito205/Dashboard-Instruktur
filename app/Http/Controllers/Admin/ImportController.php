<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Evaluasi;
use App\Models\ImportHistory;
use App\Models\Instruktur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    private const MAX_IMPORT_ROWS      = 2000;
    private const PREVIEW_STORAGE_DIR  = 'import_previews';
    private const MAX_XLSX_XML_BYTES   = 20971520; // 20 MB uncompressed XML limit

    protected string $viewPrefix  = 'pages.admin.import';
    protected string $routePrefix = 'admin.import';

    protected function authorizeManageImport(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);
    }

    public function index()
    {
        $this->authorizeManageImport();

        $riwayat = ImportHistory::with('user')->latest()->paginate(10);
        return view($this->viewPrefix . '.index', compact('riwayat'));
    }

    public function downloadTemplate(string $jenis = 'absensi')
    {
        $this->authorizeManageImport();

        if (!in_array($jenis, ['absensi', 'evaluasi'], true)) {
            abort(404);
        }

        $filename = "template_import_{$jenis}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $sampleInstruktur = Instruktur::first()?->nama_lengkap ?? 'Nama Instruktur';

        $callback = function () use ($jenis, $sampleInstruktur) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            if ($jenis === 'absensi') {
                fputcsv($handle, ['nama_instruktur', 'tanggal', 'jam_checkin', 'jam_checkout', 'status', 'keterangan']);
                fputcsv($handle, [$sampleInstruktur, now()->toDateString(), '08:00', '12:00', 'hadir', 'Sesi Pagi']);
            } else {
                fputcsv($handle, ['nama_instruktur', 'nama_pengisi', 'nilai', 'penguasaan_materi', 'cara_penyampaian', 'catatan_peserta']);
                fputcsv($handle, [$sampleInstruktur, 'Peserta Pelatihan', '88', '90', '86', 'Penjelasan materi mudah dipahami']);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {
        $this->authorizeManageImport();

        $request->validate([
            'jenis' => ['required', 'in:absensi,evaluasi'],
            'file'  => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
                        $fail('Berkas harus berformat Excel (.xlsx, .xls) atau CSV (.csv).');
                    }
                },
            ],
        ], [
            'jenis.required' => 'Jenis import wajib dipilih.',
            'jenis.in'       => 'Jenis import tidak valid.',
            'file.required'  => 'Berkas wajib diunggah.',
            'file.mimes'     => 'Berkas harus berformat Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max'       => 'Ukuran berkas maksimal 10MB.',
        ]);

        try {
            $jenis      = $request->jenis;
            $file       = $request->file('file');
            $parsed     = $this->parseFile($file);
            $rows       = $parsed['rows'];
            $isDate1904 = (bool) ($parsed['is_date_1904'] ?? false);

            // Bersihkan baris yang seluruh kolomnya kosong
            $rows = array_values(array_filter($rows, function ($r) {
                if (!is_array($r)) {
                    return false;
                }
                foreach ($r as $cell) {
                    if (trim((string) $cell) !== '') {
                        return true;
                    }
                }
                return false;
            }));

            if (count($rows) < 2) {
                return back()->with('error', 'Berkas kosong atau tidak memiliki baris data di bawah baris judul (header).');
            }

            $rawHeaders = $rows[0];
            $headers    = array_map(fn ($h) => $this->normalizeHeaderName((string) $h), $rawHeaders);
            $dataRows   = array_slice($rows, 1);

            if (count($dataRows) > self::MAX_IMPORT_ROWS) {
                return back()->with(
                    'error',
                    'Jumlah baris data melebihi batas maksimal (' . self::MAX_IMPORT_ROWS . ' baris per unggahan). Silakan bagi berkas menjadi beberapa bagian.'
                );
            }

            $missingColumns = $this->checkRequiredColumns($jenis, $headers);
            if (!empty($missingColumns)) {
                return back()->with(
                    'error',
                    'Kolom wajib tidak ditemukan: ' . implode(', ', $missingColumns) . '. Pastikan berkas memiliki header yang sesuai.'
                );
            }

            $validationResult = $this->validateAndDetectDuplicates($jenis, $headers, $dataRows, $isDate1904);
            $preview          = array_slice($validationResult['rows'], 0, 50);
            $summary          = $validationResult['summary'];

            $this->storePreviewPayload((int) auth()->id(), [
                'jenis'        => $jenis,
                'headers'      => $headers,
                'rows'         => $dataRows,
                'is_date_1904' => $isDate1904,
                'filename'     => $file->getClientOriginalName(),
                'created_at'   => now()->timestamp,
            ]);

            return view($this->viewPrefix . '.preview', compact('preview', 'headers', 'jenis', 'summary'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }
    }

    public function proses(Request $request)
    {
        $this->authorizeManageImport();

        $userId = (int) auth()->id();
        $data   = $this->loadPreviewPayload($userId);

        if (!$data) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'Sesi pratinjau sudah berakhir. Silakan unggah ulang berkas.');
        }

        $berhasil   = 0;
        $gagal      = 0;
        $errors     = [];
        $batchId    = 'import_' . now()->format('YmdHis');
        $isDate1904 = (bool) ($data['is_date_1904'] ?? false);

        DB::transaction(function () use ($data, $batchId, $isDate1904, &$berhasil, &$gagal, &$errors) {
            $headers = $data['headers'];
            $rows    = $data['rows'];
            $jenis   = $data['jenis'];

            foreach ($rows as $index => $row) {
                $nomor  = $index + 2;
                $padded = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                $mapped = array_combine($headers, $padded);

                try {
                    if ($jenis === 'absensi') {
                        $this->prosesAbsensi($mapped, $isDate1904);
                    } else {
                        $this->prosesEvaluasi($mapped, $batchId, $isDate1904);
                    }
                    $berhasil++;
                } catch (\Exception $e) {
                    $gagal++;
                    $errors[] = "Baris {$nomor}: " . $e->getMessage();
                }
            }
        });

        $status = $gagal === 0 ? 'sukses' : ($berhasil === 0 ? 'gagal' : 'parsial');

        ImportHistory::create([
            'user_id'        => $userId,
            'jenis'          => $data['jenis'],
            'nama_file'      => $data['filename'],
            'total_baris'    => count($data['rows']),
            'baris_berhasil' => $berhasil,
            'baris_gagal'    => $gagal,
            'detail_error'   => $errors,
            'status'         => $status,
        ]);

        $this->deletePreviewPayload($userId);

        $message = "Import selesai. Berhasil: {$berhasil} baris, Gagal/Dilewati: {$gagal} baris.";
        return redirect()->route($this->routePrefix . '.index')
            ->with($status === 'gagal' ? 'error' : 'success', $message)
            ->with('import_errors', $errors);
    }

    private function previewFilePath(int $userId): string
    {
        return self::PREVIEW_STORAGE_DIR . '/preview_user_' . $userId . '.json';
    }

    private function cleanupExpiredPreviews(): void
    {
        $disk = Storage::disk('local');
        if (!$disk->exists(self::PREVIEW_STORAGE_DIR)) {
            return;
        }

        $cutoff = now()->subHour()->timestamp;
        foreach ($disk->files(self::PREVIEW_STORAGE_DIR) as $filePath) {
            try {
                if ($disk->lastModified($filePath) < $cutoff) {
                    $disk->delete($filePath);
                }
            } catch (\Throwable) {
                // Abaikan jika file sudah terhapus oleh proses lain
            }
        }
    }

    private function storePreviewPayload(int $userId, array $payload): void
    {
        $this->cleanupExpiredPreviews();

        $path = $this->previewFilePath($userId);
        Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
        session()->forget('import_preview_' . $userId);
    }

    private function loadPreviewPayload(int $userId): ?array
    {
        $this->cleanupExpiredPreviews();

        $disk = Storage::disk('local');
        $path = $this->previewFilePath($userId);

        if ($disk->exists($path)) {
            $raw = $disk->get($path);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['jenis'], $decoded['headers'], $decoded['rows'])) {
                    return $decoded;
                }
            }
        }

        $legacySession = session('import_preview_' . $userId);
        return is_array($legacySession) ? $legacySession : null;
    }

    private function deletePreviewPayload(int $userId): void
    {
        $disk = Storage::disk('local');
        $path = $this->previewFilePath($userId);
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
        session()->forget('import_preview_' . $userId);
    }

    private function normalizeHeaderName(string $header): string
    {
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $clean = strtolower(trim((string) $clean));
        $clean = preg_replace('/[\s\-]+/', '_', $clean);

        return match ($clean) {
            'instruktur', 'nama_pengajar', 'pengajar'              => 'nama_instruktur',
            'kode', 'id_instruktur'                                => 'kode_instruktur',
            'tgl', 'date', 'waktu_absen', 'tanggal_evaluasi', 'tgl_evaluasi' => 'tanggal',
            'waktu_isi', 'waktu_submit', 'submitted_at'            => 'timestamp',
            'check_in', 'jam_masuk', 'masuk'                       => 'jam_checkin',
            'check_out', 'jam_pulang', 'pulang'                    => 'jam_checkout',
            'kehadiran', 'keterangan_hadir'                        => 'status',
            'skor', 'total_nilai', 'rata_rata', 'score'            => 'nilai',
            'responden', 'nama_peserta', 'peserta'                 => 'nama_pengisi',
            default                                                => $clean,
        };
    }

    private function checkRequiredColumns(string $jenis, array $headers): array
    {
        if ($jenis === 'absensi') {
            $missing = [];
            if (!in_array('nama_instruktur', $headers, true) && !in_array('kode_instruktur', $headers, true)) {
                $missing[] = 'nama_instruktur (atau kode_instruktur)';
            }
            foreach (['tanggal', 'status'] as $col) {
                if (!in_array($col, $headers, true)) {
                    $missing[] = $col;
                }
            }
            return $missing;
        }

        // Untuk evaluasi: wajib ada identitas instruktur (nama_instruktur atau kode_instruktur), kolom nilai/pertanyaan fleksibel
        $missing = [];
        if (!in_array('nama_instruktur', $headers, true) && !in_array('kode_instruktur', $headers, true)) {
            $missing[] = 'nama_instruktur (atau kode_instruktur)';
        }
        return $missing;
    }

    private function validateAndDetectDuplicates(
        string $jenis,
        array $headers,
        array $dataRows,
        bool $isDate1904 = false
    ): array {
        $validatedRows = [];
        $seenKeys      = [];
        $summary       = [
            'total'    => count($dataRows),
            'valid'    => 0,
            'duplikat' => 0,
            'invalid'  => 0,
        ];

        $timeColumnLabels = [
            'jam_checkin'      => 'jam check-in',
            'jam_checkout'     => 'jam check-out',
            'jam_mulai_jadwal' => 'jam mulai jadwal',
        ];

        foreach ($dataRows as $idx => $row) {
            $nomor        = $idx + 2;
            $padded       = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $mapped       = array_combine($headers, $padded);
            $displayCells = $padded;

            $rowStatus  = 'valid';
            $rowMessage = 'Siap diimpor';

            $instruktur = $this->resolveInstruktur($mapped);
            if (!$instruktur) {
                $ident = trim((string) ($mapped['nama_instruktur'] ?? $mapped['kode_instruktur'] ?? 'Kosong'));
                $rowStatus  = 'invalid';
                $rowMessage = "Instruktur '{$ident}' tidak ditemukan di database.";
            } elseif ($jenis === 'absensi') {
                $rawTanggal = trim((string) ($mapped['tanggal'] ?? ''));
                $tanggal    = $this->parseDate($rawTanggal, $isDate1904);

                if (!$tanggal) {
                    $rowStatus  = 'invalid';
                    $rowMessage = "Format tanggal tidak valid: '" . ($mapped['tanggal'] ?? '') . "'.";
                } else {
                    foreach ($headers as $colIdx => $colName) {
                        if ($colName === 'tanggal') {
                            $displayCells[$colIdx] = $tanggal;
                        }
                    }

                    $invalidTimeMessage = null;
                    foreach ($headers as $colIdx => $colName) {
                        if (!isset($timeColumnLabels[$colName])) {
                            continue;
                        }

                        $rawTime = trim((string) ($displayCells[$colIdx] ?? ''));
                        if ($rawTime === '' || $rawTime === '-') {
                            continue;
                        }

                        $parsedTime = $this->parseTime($rawTime);
                        if (!$parsedTime) {
                            $invalidTimeMessage = "Format {$timeColumnLabels[$colName]} tidak valid: '{$rawTime}'.";
                            break;
                        }

                        $displayCells[$colIdx] = $parsedTime;
                    }

                    if ($invalidTimeMessage !== null) {
                        $rowStatus  = 'invalid';
                        $rowMessage = $invalidTimeMessage;
                    } elseif (trim((string) ($mapped['status'] ?? '')) === '') {
                        $rowStatus  = 'invalid';
                        $rowMessage = 'Kolom status kehadiran kosong.';
                    } else {
                        $parsedCheckin  = !empty($mapped['jam_checkin']) && trim((string) $mapped['jam_checkin']) !== '-'
                            ? $this->parseTime((string) $mapped['jam_checkin'])
                            : null;
                        $parsedCheckout = !empty($mapped['jam_checkout']) && trim((string) $mapped['jam_checkout']) !== '-'
                            ? $this->parseTime((string) $mapped['jam_checkout'])
                            : null;
                        $normStatus = $this->normalizeStatusAbsensi((string) $mapped['status']);

                        if (
                            in_array($normStatus, ['hadir', 'terlambat'], true)
                            && $parsedCheckin !== null
                            && $parsedCheckout !== null
                            && substr($parsedCheckout, 0, 5) < substr($parsedCheckin, 0, 5)
                        ) {
                            $rowStatus  = 'invalid';
                            $rowMessage = "Jam check-out ({$parsedCheckout}) tidak boleh lebih awal dari jam check-in ({$parsedCheckin}).";
                        } else {
                            $uniqueKey = $instruktur->id . '|' . $tanggal;
                            $existsDb  = Absensi::where('instruktur_id', $instruktur->id)
                                ->whereDate('tanggal', $tanggal)
                                ->exists();

                            if ($existsDb) {
                                $rowStatus  = 'duplikat';
                                $rowMessage = "Duplikat di database: Absensi {$instruktur->nama_lengkap} tanggal {$tanggal} sudah tercatat.";
                            } elseif (isset($seenKeys[$uniqueKey])) {
                                $rowStatus  = 'duplikat';
                                $rowMessage = "Duplikat di dalam berkas (sama dengan baris {$seenKeys[$uniqueKey]}).";
                            } else {
                                $seenKeys[$uniqueKey] = $nomor;
                            }
                        }
                    }
                }
            } else {
                $normalizedMapped = $mapped;
                $dateTimeError    = null;

                foreach ($headers as $colIdx => $colName) {
                    $rawCell = trim((string) ($displayCells[$colIdx] ?? ''));
                    if ($rawCell === '' || $rawCell === '-') {
                        continue;
                    }

                    if ($colName === 'tanggal') {
                        $parsedTanggal = $this->parseDate($rawCell, $isDate1904);
                        if (!$parsedTanggal) {
                            $dateTimeError = "Format tanggal tidak valid: '{$rawCell}'.";
                            break;
                        }
                        $displayCells[$colIdx]       = $parsedTanggal;
                        $normalizedMapped['tanggal'] = $parsedTanggal;
                    } elseif ($colName === 'timestamp') {
                        $parsedDt = $this->parseDateTime($rawCell, $isDate1904);
                        if (!$parsedDt) {
                            $dateTimeError = "Format timestamp tidak valid: '{$rawCell}'.";
                            break;
                        }
                        $formattedDt = ($parsedDt->format('H:i:s') === '00:00:00' && preg_match('/^\d+$/', $rawCell))
                            ? $parsedDt->toDateString()
                            : $parsedDt->format('Y-m-d H:i:s');

                        $displayCells[$colIdx]         = $formattedDt;
                        $normalizedMapped['timestamp'] = $formattedDt;
                    }
                }

                $nilaiRaw = $mapped['nilai'] ?? null;
                if ($dateTimeError !== null) {
                    $rowStatus  = 'invalid';
                    $rowMessage = $dateTimeError;
                } elseif ($nilaiRaw !== null && trim((string) $nilaiRaw) !== '' && !is_numeric($nilaiRaw)) {
                    $rowStatus  = 'invalid';
                    $rowMessage = "Nilai evaluasi harus berupa angka, ditemukan: '{$nilaiRaw}'.";
                } elseif ($nilaiRaw !== null && trim((string) $nilaiRaw) !== '' && ((float) $nilaiRaw < 0 || (float) $nilaiRaw > 100)) {
                    $rowStatus  = 'invalid';
                    $rowMessage = "Nilai evaluasi harus berada di rentang 0-100 (atau 1-5), ditemukan: '{$nilaiRaw}'.";
                } else {
                    $evalCalc  = $this->extractAndNormalizeEvaluasiScores($normalizedMapped);
                    $hashKey   = $instruktur->id . '|' . md5(json_encode($normalizedMapped));
                    $existsDb  = $this->checkDuplicateEvaluasiInDb($instruktur->id, $normalizedMapped, $evalCalc['total_nilai']);

                    if ($existsDb) {
                        $rowStatus  = 'duplikat';
                        $rowMessage = "Duplikat di database: Evaluasi untuk {$instruktur->nama_lengkap} dari pengisi ini sudah tercatat.";
                    } elseif (isset($seenKeys[$hashKey])) {
                        $rowStatus  = 'duplikat';
                        $rowMessage = "Baris evaluasi identik dengan baris {$seenKeys[$hashKey]} di berkas ini.";
                    } else {
                        $seenKeys[$hashKey] = $nomor;
                    }
                }
            }

            if ($rowStatus === 'valid') {
                $summary['valid']++;
            } elseif ($rowStatus === 'duplikat') {
                $summary['duplikat']++;
            } else {
                $summary['invalid']++;
            }

            $validatedRows[] = [
                'nomor'   => $nomor,
                'cells'   => $displayCells,
                'status'  => $rowStatus,
                'message' => $rowMessage,
            ];
        }

        return [
            'rows'    => $validatedRows,
            'summary' => $summary,
        ];
    }

    private function resolveInstruktur(array $row): ?Instruktur
    {
        if (!empty($row['kode_instruktur'])) {
            $kode = trim((string) $row['kode_instruktur']);
            $inst = Instruktur::where('kode_instruktur', $kode)->first();
            if ($inst) {
                return $inst;
            }
        }

        $nama = trim((string) ($row['nama_instruktur'] ?? ''));
        if ($nama === '') {
            return null;
        }

        return Instruktur::where('nama_lengkap', $nama)->first()
            ?? Instruktur::where('nama_lengkap', 'like', '%' . $nama . '%')->first();
    }

    private function parseFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $realPath  = $file->getRealPath();

        if ($extension === 'csv') {
            $rows   = [];
            $handle = fopen($realPath, 'r');
            if (!$handle) {
                throw new \Exception('Tidak dapat membuka berkas CSV.');
            }

            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',')) ? ';' : ',';

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            return [
                'rows'         => $rows,
                'is_date_1904' => false,
            ];
        }

        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($realPath);
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cells        = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cells[] = $cell->getFormattedValue();
                }
                $rows[] = $cells;
            }

            $isDate1904 = false;
            if (class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                $isDate1904 = \PhpOffice\PhpSpreadsheet\Shared\Date::getExcelCalendar() === \PhpOffice\PhpSpreadsheet\Shared\Date::CALENDAR_MAC_1904;
            }

            return [
                'rows'         => $rows,
                'is_date_1904' => $isDate1904,
            ];
        }

        if ($extension === 'xlsx' && class_exists('\ZipArchive')) {
            return $this->parseXlsxNatively($realPath);
        }

        throw new \Exception('Format berkas .xls memerlukan ekstensi PhpSpreadsheet atau silakan simpan sebagai .xlsx / .csv.');
    }

    private function parseXlsxNatively(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Berkas .xlsx rusak atau tidak dapat dibuka.');
        }

        $xmlFlags = LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT;

        $readZipXml = function (string $entryName) use ($zip): string|false {
            $stat = $zip->statName($entryName);
            if ($stat === false) {
                return false;
            }
            if (($stat['size'] ?? 0) > self::MAX_XLSX_XML_BYTES) {
                $zip->close();
                throw new \Exception('Ukuran data XML di dalam berkas .xlsx melebihi batas keamanan (maksimal 20MB).');
            }
            return $zip->getFromName($entryName);
        };

        $isDate1904 = false;
        if (($wbXml = $readZipXml('xl/workbook.xml')) !== false) {
            $wb = @simplexml_load_string($wbXml, 'SimpleXMLElement', $xmlFlags);
            if ($wb && isset($wb->workbookPr['date1904'])) {
                $flag       = strtolower(trim((string) $wb->workbookPr['date1904']));
                $isDate1904 = in_array($flag, ['1', 'true'], true);
            }
        }

        $sharedStrings = [];
        if (($ssXml = $readZipXml('xl/sharedStrings.xml')) !== false) {
            $xml = @simplexml_load_string($ssXml, 'SimpleXMLElement', $xmlFlags);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $readZipXml('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \Exception('Lembar kerja utama tidak ditemukan di berkas .xlsx.');
        }

        $xml  = @simplexml_load_string($sheetXml, 'SimpleXMLElement', $xmlFlags);
        $rows = [];

        if ($xml && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $row) {
                $rowCells     = [];
                $nextColIndex = 0;

                foreach ($row->c as $c) {
                    $refAttr = trim((string) $c['r']);
                    if ($refAttr !== '' && preg_match('/^([A-Z]+)\d+$/i', $refAttr, $m)) {
                        $colIdx = $this->excelColumnToIndex($m[1]);
                    } else {
                        $colIdx = $nextColIndex;
                    }

                    // Pertahankan posisi kolom ketika terdapat sel kosong di tengah baris Excel
                    while ($nextColIndex < $colIdx) {
                        $rowCells[$nextColIndex] = '';
                        $nextColIndex++;
                    }

                    $type  = (string) $c['t'];
                    $value = isset($c->v) ? (string) $c->v : '';

                    if ($type === 's' && isset($sharedStrings[(int) $value])) {
                        $value = $sharedStrings[(int) $value];
                    } elseif ($type === 'inlineStr') {
                        if (isset($c->is->t)) {
                            $value = (string) $c->is->t;
                        } elseif (isset($c->is->r)) {
                            $inlineText = '';
                            foreach ($c->is->r as $run) {
                                $inlineText .= (string) $run->t;
                            }
                            $value = $inlineText;
                        }
                    }

                    $rowCells[$colIdx] = $value;
                    $nextColIndex      = $colIdx + 1;
                }

                $rows[] = array_values($rowCells);
            }
        }

        return [
            'rows'         => $rows,
            'is_date_1904' => $isDate1904,
        ];
    }

    private function excelColumnToIndex(string $colLetters): int
    {
        $colLetters = strtoupper($colLetters);
        $index      = 0;
        $len        = strlen($colLetters);

        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($colLetters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }

    private function prosesAbsensi(array $row, bool $isDate1904 = false): void
    {
        $instruktur = $this->resolveInstruktur($row);
        if (!$instruktur) {
            $ident = $row['nama_instruktur'] ?? $row['kode_instruktur'] ?? '';
            throw new \Exception("Instruktur '{$ident}' tidak ditemukan.");
        }

        $tanggal = $this->parseDate((string) ($row['tanggal'] ?? ''), $isDate1904);
        if (!$tanggal) {
            throw new \Exception("Format tanggal tidak valid: '" . ($row['tanggal'] ?? '') . "'.");
        }

        $jamCheckin  = $this->parseOptionalTime($row['jam_checkin'] ?? null, 'jam check-in');
        $jamCheckout = $this->parseOptionalTime($row['jam_checkout'] ?? null, 'jam check-out');
        $jamMulai    = array_key_exists('jam_mulai_jadwal', $row)
            ? $this->parseOptionalTime($row['jam_mulai_jadwal'], 'jam mulai jadwal')
            : null;

        if (trim((string) ($row['status'] ?? '')) === '') {
            throw new \Exception('Kolom status kehadiran wajib diisi.');
        }

        $status = $this->normalizeStatusAbsensi((string) $row['status']);

        if (in_array($status, ['izin', 'tidak_hadir'], true)) {
            $jamCheckin  = null;
            $jamCheckout = null;
        } elseif (
            $jamCheckin !== null
            && $jamCheckout !== null
            && substr($jamCheckout, 0, 5) < substr($jamCheckin, 0, 5)
        ) {
            throw new \Exception("Jam check-out ({$jamCheckout}) tidak boleh lebih awal dari jam check-in ({$jamCheckin}).");
        }

        $existing = Absensi::where('instruktur_id', $instruktur->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($existing) {
            throw new \Exception("Duplikat: Absensi {$instruktur->nama_lengkap} pada {$tanggal} sudah ada.");
        }

        $payload = [
            'instruktur_id' => $instruktur->id,
            'tanggal'       => $tanggal,
            'jam_checkin'   => $jamCheckin,
            'jam_checkout'  => $jamCheckout,
            'status'        => $status,
            'sumber'        => 'import',
            'keterangan'    => !empty($row['keterangan']) ? trim((string) $row['keterangan']) : null,
        ];

        if ($jamMulai !== null) {
            $payload['jam_mulai_jadwal'] = $jamMulai;

            if ($jamCheckin !== null && in_array($status, ['hadir', 'terlambat'], true)) {
                $payload['menit_terlambat'] = Absensi::hitungKeterlambatan($jamMulai, $jamCheckin)['menit_terlambat'];
            }
        }

        Absensi::create($payload);
    }

    private function prosesEvaluasi(array $row, string $batchId, bool $isDate1904 = false): void
    {
        $instruktur = $this->resolveInstruktur($row);
        if (!$instruktur) {
            $ident = $row['nama_instruktur'] ?? $row['kode_instruktur'] ?? '';
            throw new \Exception("Instruktur '{$ident}' tidak ditemukan.");
        }

        $evaluatedAt = null;
        if (isset($row['tanggal']) && trim((string) $row['tanggal']) !== '' && trim((string) $row['tanggal']) !== '-') {
            $rawTanggal    = trim((string) $row['tanggal']);
            $parsedTanggal = $this->parseDate($rawTanggal, $isDate1904);
            if (!$parsedTanggal) {
                throw new \Exception("Format tanggal evaluasi tidak valid: '{$rawTanggal}'.");
            }
            $row['tanggal'] = $parsedTanggal;
            $evaluatedAt    = Carbon::parse($parsedTanggal . ' 00:00:00');
        }

        if (isset($row['timestamp']) && trim((string) $row['timestamp']) !== '' && trim((string) $row['timestamp']) !== '-') {
            $rawTs    = trim((string) $row['timestamp']);
            $parsedTs = $this->parseDateTime($rawTs, $isDate1904);
            if (!$parsedTs) {
                throw new \Exception("Format timestamp evaluasi tidak valid: '{$rawTs}'.");
            }
            $formattedTs = ($parsedTs->format('H:i:s') === '00:00:00' && preg_match('/^\d+$/', $rawTs))
                ? $parsedTs->toDateString()
                : $parsedTs->format('Y-m-d H:i:s');
            $row['timestamp'] = $formattedTs;
            $evaluatedAt      = Carbon::parse($parsedTs->format('Y-m-d H:i:s'));
        }

        if (isset($row['nilai']) && trim((string) $row['nilai']) !== '') {
            if (!is_numeric($row['nilai'])) {
                throw new \Exception("Nilai evaluasi tidak valid: '{$row['nilai']}'.");
            }
            $numVal = (float) $row['nilai'];
            if ($numVal < 0 || $numVal > 100) {
                throw new \Exception("Nilai evaluasi di luar rentang 0-100: '{$row['nilai']}'.");
            }
        }

        $evalCalc = $this->extractAndNormalizeEvaluasiScores($row);
        $nilai    = $evalCalc['total_nilai'];

        if ($this->checkDuplicateEvaluasiInDb($instruktur->id, $row, $nilai)) {
            $pengisiLabel = !empty($row['nama_pengisi']) ? trim((string) $row['nama_pengisi']) : 'Anonim';
            throw new \Exception("Duplikat: Evaluasi untuk {$instruktur->nama_lengkap} dari '{$pengisiLabel}' sudah ada di database.");
        }

        $komentar = null;
        foreach (['komentar', 'catatan_peserta', 'saran', 'catatan'] as $komKey) {
            if (!empty($row[$komKey]) && trim((string) $row[$komKey]) !== '') {
                $komentar = trim((string) $row[$komKey]);
                break;
            }
        }

        $materiEvaluasi = null;
        foreach (['materi_evaluasi', 'materi', 'topik'] as $matKey) {
            if (!empty($row[$matKey]) && trim((string) $row[$matKey]) !== '') {
                $materiEvaluasi = trim((string) $row[$matKey]);
                break;
            }
        }

        $jawabanPayload = $row;
        if (!empty($evalCalc['skor_indikator'])) {
            $jawabanPayload['skor_indikator'] = $evalCalc['skor_indikator'];
        }
        if ($evalCalc['rata_rata_skala_5'] !== null) {
            $jawabanPayload['rata_rata_skala_5'] = $evalCalc['rata_rata_skala_5'];
        }
        if ($komentar !== null && !isset($jawabanPayload['komentar'])) {
            $jawabanPayload['komentar'] = $komentar;
        }

        $evaluasi = new Evaluasi([
            'instruktur_id'   => $instruktur->id,
            'nama_pengisi'    => !empty($row['nama_pengisi']) ? trim((string) $row['nama_pengisi']) : null,
            'materi_evaluasi' => $materiEvaluasi,
            'total_nilai'     => $nilai,
            'komentar'        => $komentar,
            'batch_import'    => $batchId,
            'jawaban'         => $jawabanPayload,
        ]);

        if ($evaluatedAt !== null) {
            $evaluasi->created_at = $evaluatedAt;
        }

        $evaluasi->save();
    }

    /**
     * Ekstrak nilai indikator dan normalisasikan total_nilai ke skala 0-100,
     * baik ketika berkas berisi skala Likert 1-5 maupun skala 0-100.
     *
     * @return array{total_nilai: ?float, rata_rata_skala_5: ?float, skor_indikator: array<string, float>}
     */
    private function extractAndNormalizeEvaluasiScores(array $row): array
    {
        $indikatorAliases = [
            'indikator_1' => ['indikator_1', 'penguasaan_materi', 'materi_score', 'skor_materi'],
            'indikator_2' => ['indikator_2', 'cara_penyampaian', 'penyampaian', 'komunikasi', 'kejelasan_penyampaian'],
            'indikator_3' => ['indikator_3', 'ketepatan_waktu', 'kedisiplinan', 'disiplin'],
            'indikator_4' => ['indikator_4', 'interaksi', 'pendampingan_praktik', 'interaksi_praktik'],
            'indikator_5' => ['indikator_5', 'sikap_profesional', 'sikap', 'keteladanan'],
        ];

        $rawIndikatorValues = [];
        foreach ($indikatorAliases as $indKey => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($row[$alias]) && trim((string) $row[$alias]) !== '' && is_numeric($row[$alias])) {
                    $val = (float) $row[$alias];
                    if ($val >= 0 && $val <= 100) {
                        $rawIndikatorValues[$indKey] = $val;
                        break;
                    }
                }
            }
        }

        $otherNumeric = [];
        $ignoredCols  = [
            'nama_instruktur', 'kode_instruktur', 'nama_pengisi', 'tanggal',
            'timestamp', 'jam', 'waktu', 'nilai', 'komentar', 'catatan_peserta',
            'saran', 'catatan', 'materi', 'materi_evaluasi', 'kode', 'kode_kelas',
        ];
        foreach ($row as $k => $v) {
            if (!in_array($k, $ignoredCols, true) && is_numeric($v)) {
                $f = (float) $v;
                if ($f >= 0 && $f <= 100) {
                    $otherNumeric[] = $f;
                }
            }
        }

        // Jika tidak ada nama kolom indikator standar tetapi ada 1-5 kolom numerik pertanyaan, petakan berurutan
        if (empty($rawIndikatorValues) && !empty($otherNumeric)) {
            foreach (array_slice($otherNumeric, 0, 5) as $idx => $numVal) {
                $rawIndikatorValues['indikator_' . ($idx + 1)] = $numVal;
            }
        }

        $skorIndikatorSkala5 = [];
        if (!empty($rawIndikatorValues)) {
            $maxIndikator = max($rawIndikatorValues);
            $isSkala5     = $maxIndikator <= 5.0;

            foreach ($rawIndikatorValues as $indKey => $val) {
                $skorIndikatorSkala5[$indKey] = $isSkala5
                    ? round($val, 2)
                    : round($val / 20.0, 2);
            }
        }

        $explicitNilai = null;
        if (isset($row['nilai']) && trim((string) $row['nilai']) !== '' && is_numeric($row['nilai'])) {
            $explicitNilai = (float) $row['nilai'];
        }

        // Tentukan total_nilai (skala 0-100) dan rata_rata_skala_5 (skala 1-5)
        if ($explicitNilai !== null) {
            $isNilaiSkala5 = $explicitNilai > 0 && $explicitNilai <= 5.0
                && (empty($rawIndikatorValues) || max($rawIndikatorValues) <= 5.0);

            $totalNilai100 = $isNilaiSkala5
                ? round($explicitNilai * 20.0, 2)
                : round($explicitNilai, 2);

            $rataSkala5 = !empty($skorIndikatorSkala5)
                ? round(array_sum($skorIndikatorSkala5) / count($skorIndikatorSkala5), 2)
                : round($totalNilai100 / 20.0, 2);
        } elseif (!empty($rawIndikatorValues)) {
            $avgRaw        = array_sum($rawIndikatorValues) / count($rawIndikatorValues);
            $isSkala5      = max($rawIndikatorValues) <= 5.0;
            $totalNilai100 = $isSkala5 ? round($avgRaw * 20.0, 2) : round($avgRaw, 2);
            $rataSkala5    = $isSkala5 ? round($avgRaw, 2) : round($avgRaw / 20.0, 2);
        } elseif (!empty($otherNumeric)) {
            $avgOther      = array_sum($otherNumeric) / count($otherNumeric);
            $isSkala5      = max($otherNumeric) <= 5.0;
            $totalNilai100 = $isSkala5 ? round($avgOther * 20.0, 2) : round($avgOther, 2);
            $rataSkala5    = round($totalNilai100 / 20.0, 2);
        } else {
            $totalNilai100 = null;
            $rataSkala5    = null;
        }

        if (empty($skorIndikatorSkala5) && $rataSkala5 !== null) {
            $skorIndikatorSkala5['indikator_1'] = $rataSkala5;
        }

        return [
            'total_nilai'       => $totalNilai100,
            'rata_rata_skala_5' => $rataSkala5,
            'skor_indikator'    => $skorIndikatorSkala5,
        ];
    }

    private function checkDuplicateEvaluasiInDb(int $instrukturId, array $row, ?float $totalNilai100): bool
    {
        $namaPengisi = !empty($row['nama_pengisi']) ? trim((string) $row['nama_pengisi']) : null;
        $komentar    = null;
        foreach (['komentar', 'catatan_peserta', 'saran', 'catatan'] as $komKey) {
            if (!empty($row[$komKey]) && trim((string) $row[$komKey]) !== '') {
                $komentar = trim((string) $row[$komKey]);
                break;
            }
        }

        $query = Evaluasi::where('instruktur_id', $instrukturId);

        if ($namaPengisi !== null) {
            $query->where('nama_pengisi', $namaPengisi);
        } else {
            $query->whereNull('nama_pengisi');
        }

        if ($totalNilai100 !== null) {
            $query->whereBetween('total_nilai', [$totalNilai100 - 0.01, $totalNilai100 + 0.01]);
        } else {
            $query->whereNull('total_nilai');
        }

        if ($komentar !== null) {
            $query->where(function ($q) use ($komentar) {
                $q->where('komentar', $komentar)
                    ->orWhere('jawaban->catatan_peserta', $komentar)
                    ->orWhere('jawaban->komentar', $komentar);
            });
        }

        return $query->exists();
    }

    private function excelSerialToCarbon(float $serial, bool $isDate1904 = false): Carbon
    {
        $days    = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);
        if ($seconds >= 86400) {
            $days++;
            $seconds = 0;
        }

        if ($isDate1904) {
            return Carbon::create(1904, 1, 1, 0, 0, 0, 'UTC')
                ->addDays($days)
                ->addSeconds($seconds);
        }

        // Excel 1900 date system menyertakan 29 Feb 1900 fiktif (serial 60), sehingga >= 60 bergeser 1 hari ke 1899-12-30.
        $base = $days < 60
            ? Carbon::create(1899, 12, 31, 0, 0, 0, 'UTC')
            : Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');

        return $base->addDays($days)->addSeconds($seconds);
    }

    private function normalizeIndonesianMonthNames(string $value): string
    {
        $map = [
            'januari'   => 'January',
            'jan'       => 'Jan',
            'februari'  => 'February',
            'feb'       => 'Feb',
            'maret'     => 'March',
            'mar'       => 'Mar',
            'april'     => 'April',
            'apr'       => 'Apr',
            'mei'       => 'May',
            'juni'      => 'June',
            'jun'       => 'Jun',
            'juli'      => 'July',
            'jul'       => 'Jul',
            'agustus'   => 'August',
            'agu'       => 'Aug',
            'ags'       => 'Aug',
            'september' => 'September',
            'sep'       => 'Sep',
            'sept'      => 'Sep',
            'oktober'   => 'October',
            'okt'       => 'Oct',
            'november'  => 'November',
            'nopember'  => 'November',
            'nov'       => 'Nov',
            'desember'  => 'December',
            'des'       => 'Dec',
        ];

        return (string) preg_replace_callback('/\b([a-zA-Z]+)\b/', function (array $matches) use ($map) {
            $lower = strtolower($matches[1]);
            return $map[$lower] ?? $matches[1];
        }, $value);
    }

    private function parseDate(string $value, bool $isDate1904 = false): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{8}$/', $value)) {
            try {
                $d = Carbon::createFromFormat('!Ymd', $value);
                if ($d && $d->format('Ymd') === $value) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        if (preg_match('/^\d+([.,]\d+)?$/', $value)) {
            $normalizedNum = str_replace(',', '.', $value);
            if (!str_contains($normalizedNum, '.')) {
                $intVal = (int) $normalizedNum;
                if ($intVal >= 1900 && $intVal <= 2100) {
                    return null;
                }
            }

            $serial = (float) $normalizedNum;
            if ($serial >= 1 && $serial <= 2958465) {
                return $this->excelSerialToCarbon($serial, $isDate1904)->toDateString();
            }

            return null;
        }

        $normalized = $this->normalizeIndonesianMonthNames($value);
        $formats    = [
            'Y-m-d',
            'd/m/Y',
            'j/n/Y',
            'd-m-Y',
            'j-n-Y',
            'm/d/Y',
            'n/j/Y',
            'Y/m/d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd M Y',
            'd F Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $normalized);
                if ($date && strtolower($date->format($format)) === strtolower($normalized)) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseOptionalTime(mixed $rawValue, string $label): ?string
    {
        $raw = trim((string) ($rawValue ?? ''));
        if ($raw === '' || $raw === '-') {
            return null;
        }

        $parsed = $this->parseTime($raw);
        if (!$parsed) {
            throw new \Exception("Format {$label} tidak valid: '{$raw}'.");
        }

        return $parsed;
    }

    private function parseTime(string $value): ?string
    {
        $value = trim((string) preg_replace('/\s+(WIB|WITA|WIT)$/i', '', trim($value)));
        if ($value === '' || $value === '-') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            $s = isset($m[3]) ? (int) $m[3] : 0;
            if ($h > 23 || $i > 59 || $s > 59) {
                return null;
            }
            return $s === 0
                ? sprintf('%02d:%02d', $h, $i)
                : sprintf('%02d:%02d:%02d', $h, $i, $s);
        }

        // Format jam Indonesia memakai titik (mis. 00.30, 8.30, 08.30, 12.00, 08.15.30),
        // sedangkan angka berkepala '0.' (mis. 0.5, 0.333333) tetap diproses sebagai Excel serial time.
        if (preg_match('/^(0\d|[1-9]\d?)\.(\d{2})(?:\.(\d{2}))?$/', $value, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            $s = isset($m[3]) ? (int) $m[3] : 0;
            if ($h > 23 || $i > 59 || $s > 59) {
                return null;
            }
            return $s === 0
                ? sprintf('%02d:%02d', $h, $i)
                : sprintf('%02d:%02d:%02d', $h, $i, $s);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)$/i', $value, $m)) {
            $h    = (int) $m[1];
            $i    = (int) $m[2];
            $s    = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;
            $ampm = strtoupper($m[4]);

            if ($h < 1 || $h > 12 || $i > 59 || $s > 59) {
                return null;
            }
            if ($ampm === 'PM' && $h < 12) {
                $h += 12;
            } elseif ($ampm === 'AM' && $h === 12) {
                $h = 0;
            }

            return $s === 0
                ? sprintf('%02d:%02d', $h, $i)
                : sprintf('%02d:%02d:%02d', $h, $i, $s);
        }

        if (preg_match('/^\d+([.,]\d+)?$/', $value)) {
            $normalized = str_replace(',', '.', $value);
            $num        = (float) $normalized;

            if ($num >= 0 && $num < 1) {
                $fraction = $num;
            } elseif ($num >= 1000 && $num <= 2958465 && str_contains($normalized, '.')) {
                $fraction = $num - floor($num);
            } else {
                return null;
            }

            $totalSeconds = (int) round($fraction * 86400);
            if ($totalSeconds >= 86400) {
                $totalSeconds = 0;
            }

            $hours   = intdiv($totalSeconds, 3600);
            $minutes = intdiv($totalSeconds % 3600, 60);
            $seconds = $totalSeconds % 60;

            return $seconds === 0
                ? sprintf('%02d:%02d', $hours, $minutes)
                : sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        $parsedDt = $this->parseDateTime($value);
        if ($parsedDt) {
            return (int) $parsedDt->second === 0
                ? $parsedDt->format('H:i')
                : $parsedDt->format('H:i:s');
        }

        return null;
    }

    private function parseDateTime(string $value, bool $isDate1904 = false): ?Carbon
    {
        $value = trim((string) preg_replace('/\s+(WIB|WITA|WIT)$/i', '', trim($value)));
        if ($value === '' || $value === '-') {
            return null;
        }

        if (preg_match('/^\d+([.,]\d+)?$/', $value)) {
            $normalizedNum = str_replace(',', '.', $value);
            if (!str_contains($normalizedNum, '.')) {
                $intVal = (int) $normalizedNum;
                if ($intVal >= 1900 && $intVal <= 2100) {
                    return null;
                }
            }

            $serial = (float) $normalizedNum;
            if ($serial >= 1 && $serial <= 2958465) {
                return $this->excelSerialToCarbon($serial, $isDate1904);
            }

            return null;
        }

        $normalized = $this->normalizeIndonesianMonthNames($value);
        $formats    = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'j/n/Y H:i:s',
            'j/n/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'j-n-Y H:i:s',
            'j-n-Y H:i',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'd M Y H:i:s',
            'd M Y H:i',
            'd F Y H:i:s',
            'd F Y H:i',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat('!' . $format, $normalized);
                if ($dt && strtolower($dt->format($format)) === strtolower($normalized)) {
                    return $dt;
                }
            } catch (\Throwable) {
            }
        }

        $dateOnly = $this->parseDate($value, $isDate1904);
        if ($dateOnly) {
            return Carbon::parse($dateOnly . ' 00:00:00');
        }

        return null;
    }

    private function normalizeStatusAbsensi(string $value): string
    {
        $value = strtolower(trim($value));
        return match (true) {
            in_array($value, ['hadir', 'h', 'present', 'tepat_waktu'], true) => 'hadir',
            in_array($value, ['terlambat', 'late', 'l', 'telat'], true)      => 'terlambat',
            in_array($value, ['izin', 'i', 'permit', 'sakit', 's'], true)    => 'izin',
            default                                                          => 'tidak_hadir',
        };
    }
}
