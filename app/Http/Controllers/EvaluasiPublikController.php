<?php

namespace App\Http\Controllers;

use App\Models\Evaluasi;
use App\Models\Jadwal;
use App\Models\KodeEvaluasi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class EvaluasiPublikController extends Controller
{
    public function index(Request $request)
    {
        $inputKode    = strtoupper(trim((string) $request->get('kode', '')));
        $kodeEvaluasi = null;
        $sesiList     = collect();
        $kodeError    = null;

        if ($inputKode !== '') {
            $lookupKey = 'evaluasi-lookup:' . ($request->ip() ?? '127.0.0.1');
            if (RateLimiter::tooManyAttempts($lookupKey, 30)) {
                $seconds = RateLimiter::availableIn($lookupKey);
                $kodeError = "Terlalu banyak percobaan pencarian kode evaluasi. Silakan tunggu {$seconds} detik.";
            } else {
                $kodeEvaluasi = KodeEvaluasi::with('instruktur')
                    ->whereRaw('UPPER(kode) = ?', [$inputKode])
                    ->first();

                if (!$kodeEvaluasi) {
                    RateLimiter::hit($lookupKey, 60);
                    $kodeError = "Kode evaluasi kelas \"{$inputKode}\" tidak ditemukan. Pastikan kode yang Anda masukkan sesuai dengan kode yang diberikan instruktur atau admin kelas.";
                } elseif (!$kodeEvaluasi->isValid()) {
                    RateLimiter::hit($lookupKey, 60);
                    $kodeError = "Kode evaluasi kelas \"{$inputKode}\" saat ini tidak aktif atau telah melampaui batas waktu/kuota pengisian.";
                    $kodeEvaluasi = null;
                } else {
                    $sesiList = $this->groupSesiJadwal($kodeEvaluasi->resolveSesiJadwal());
                }
            }
        }

        $indikatorLabels = Evaluasi::getIndikatorLabels();

        return view('pages.evaluasi.index', compact(
            'inputKode',
            'kodeEvaluasi',
            'sesiList',
            'kodeError',
            'indikatorLabels'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'          => ['required', 'string', 'max:50'],
            'jadwal_id'     => ['nullable', 'integer', 'exists:jadwals,id'],
            'nama_pengisi'  => ['nullable', 'string', 'max:150'],
            'indikator_1'   => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_2'   => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_3'   => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_4'   => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_5'   => ['required', 'integer', 'min:1', 'max:5'],
            'komentar'      => ['nullable', 'string', 'max:2000'],
        ], [
            'kode.required'        => 'Kode evaluasi kelas wajib diisi.',
            'indikator_1.required' => 'Penilaian Indikator 1 wajib dipilih (skala 1-5).',
            'indikator_2.required' => 'Penilaian Indikator 2 wajib dipilih (skala 1-5).',
            'indikator_3.required' => 'Penilaian Indikator 3 wajib dipilih (skala 1-5).',
            'indikator_4.required' => 'Penilaian Indikator 4 wajib dipilih (skala 1-5).',
            'indikator_5.required' => 'Penilaian Indikator 5 wajib dipilih (skala 1-5).',
        ]);

        $inputKode = strtoupper(trim($validated['kode']));

        // Pembatasan spam otomatis (rate limiter per IP tanpa memblokir penggunaan bersama PC lab komputer)
        $throttleKey = 'evaluasi-publik:' . ($request->ip() ?? '127.0.0.1');
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput()
                ->withErrors(['kode' => "Terlalu banyak pengiriman dalam waktu singkat. Silakan tunggu {$seconds} detik sebelum mengirim kembali."]);
        }

        $skorIndikator = [
            'indikator_1' => (int) $validated['indikator_1'],
            'indikator_2' => (int) $validated['indikator_2'],
            'indikator_3' => (int) $validated['indikator_3'],
            'indikator_4' => (int) $validated['indikator_4'],
            'indikator_5' => (int) $validated['indikator_5'],
        ];

        $rataRataSkala5 = round(array_sum($skorIndikator) / 5.0, 2);
        $totalNilai100  = Evaluasi::hitungNilaiKonversi100($skorIndikator);
        $namaPengisi    = trim((string) ($validated['nama_pengisi'] ?? '')) !== ''
            ? trim((string) $validated['nama_pengisi'])
            : 'Anonim';
        $komentar       = isset($validated['komentar']) && trim((string) $validated['komentar']) !== ''
            ? trim((string) $validated['komentar'])
            : null;

        $txResult = DB::transaction(function () use (
            $inputKode,
            $validated,
            $namaPengisi,
            $skorIndikator,
            $rataRataSkala5,
            $totalNilai100,
            $komentar,
            $request
        ) {
            $kodeEvaluasi = KodeEvaluasi::with('instruktur')
                ->whereRaw('UPPER(kode) = ?', [$inputKode])
                ->lockForUpdate()
                ->first();

            if (! $kodeEvaluasi) {
                return ['error_field' => 'kode', 'error' => "Kode evaluasi \"{$inputKode}\" tidak ditemukan."];
            }

            if (! $kodeEvaluasi->isValid()) {
                return ['error_field' => 'kode', 'error' => "Kode evaluasi \"{$inputKode}\" sudah tidak aktif atau kuota pengisian telah penuh."];
            }

            $sesiValid            = $kodeEvaluasi->resolveSesiJadwal();
            $groupedSesi          = $this->groupSesiJadwal($sesiValid);
            $selectedJadwal       = null;
            $resolvedInstrukturId = null;
            $materiEvaluasi       = null;

            if (! empty($validated['jadwal_id'])) {
                $selectedJadwal = $sesiValid->firstWhere('id', (int) $validated['jadwal_id']);
                if (! $selectedJadwal) {
                    return ['error_field' => 'jadwal_id', 'error' => 'Sesi atau materi yang dipilih tidak sesuai dengan kode kelas ini.'];
                }
                $resolvedInstrukturId = $selectedJadwal->instruktur_id;
                $materiEvaluasi       = $selectedJadwal->materi;
            } elseif ($groupedSesi->count() > 1) {
                return ['error_field' => 'jadwal_id', 'error' => 'Kelas ini memiliki beberapa sesi/instruktur. Silakan pilih sesi dan instruktur yang ingin Anda evaluasi.'];
            } elseif ($groupedSesi->count() === 1) {
                $selectedJadwal       = $groupedSesi->first();
                $resolvedInstrukturId = $selectedJadwal->instruktur_id;
                $materiEvaluasi       = $selectedJadwal->materi;
            } elseif ($kodeEvaluasi->instruktur_id) {
                $resolvedInstrukturId = $kodeEvaluasi->instruktur_id;
                $materiEvaluasi       = $kodeEvaluasi->keterangan;
            }

            if (! $resolvedInstrukturId) {
                return ['error_field' => 'jadwal_id', 'error' => 'Instruktur tujuan evaluasi belum terhubung pada kode kelas ini. Hubungi Admin LPK.'];
            }

            $lockKey = 'eval_lock_' . $kodeEvaluasi->id . '_' . $resolvedInstrukturId . '_' . ($selectedJadwal?->id ?? 0) . '_' . today()->toDateString();
            if ($request->hasSession() && $request->session()->has($lockKey)) {
                return ['error_field' => 'kode', 'error' => 'Anda sudah mengirimkan evaluasi untuk sesi kelas dan instruktur ini pada hari ini. Terima kasih atas partisipasi Anda.'];
            }

            $eval = Evaluasi::create([
                'instruktur_id'    => $resolvedInstrukturId,
                'kode_evaluasi_id' => $kodeEvaluasi->id,
                'jadwal_id'        => $selectedJadwal?->id,
                'nama_pengisi'     => $namaPengisi,
                'materi_evaluasi'  => $materiEvaluasi,
                'jawaban'          => [
                    'skala'             => '1-5',
                    'skor_indikator'    => $skorIndikator,
                    'label_indikator'   => Evaluasi::getIndikatorLabels(),
                    'rata_rata_skala_5' => $rataRataSkala5,
                    'rumus_konversi'    => '(rata_rata_skala_5 / 5) * 100',
                    'komentar'          => $komentar,
                ],
                'total_nilai'      => $totalNilai100,
                'komentar'         => $komentar,
                'ip_address'       => $request->ip(),
            ]);

            $kodeEvaluasi->increment('jumlah_diisi');

            return [
                'evaluasi'     => $eval,
                'kodeEvaluasi' => $kodeEvaluasi,
                'materi'       => $materiEvaluasi,
                'lockKey'      => $lockKey,
            ];
        });

        if (isset($txResult['error'])) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput()
                ->withErrors([$txResult['error_field'] => $txResult['error']]);
        }

        $evaluasi       = $txResult['evaluasi'];
        $kodeEvaluasi   = $txResult['kodeEvaluasi'];
        $materiEvaluasi = $txResult['materi'];
        $lockKey        = $txResult['lockKey'];

        RateLimiter::hit($throttleKey, 60);
        if ($request->hasSession()) {
            $request->session()->put($lockKey, true);
        }

        return redirect()->route('evaluasi.publik.berhasil')->with('evaluasi_summary', [
            'kode'              => $kodeEvaluasi->kode,
            'nama_kelas'        => $kodeEvaluasi->nama_kelas ?? $kodeEvaluasi->kode,
            'instruktur'        => $evaluasi->instruktur?->nama_lengkap ?? '-',
            'materi'            => $materiEvaluasi ?? '-',
            'nama_pengisi'      => $namaPengisi,
            'rata_rata_skala_5' => $rataRataSkala5,
            'total_nilai_100'   => $totalNilai100,
            'waktu'             => $evaluasi->created_at->format('d/m/Y H:i'),
        ]);
    }

    public function berhasil()
    {
        $summary = session('evaluasi_summary');
        return view('pages.evaluasi.berhasil', compact('summary'));
    }

    /**
     * Gabungkan (deduplikasi) jadwal berdasarkan kombinasi instruktur_id + materi
     * agar instruktur yang mengajar materi sama pada beberapa hari berbeda
     * dalam satu kelas hanya muncul sekali dengan ringkasan jadwal tergabung.
     */
    private function groupSesiJadwal(Collection $rawSesi): Collection
    {
        if ($rawSesi->isEmpty()) {
            return collect();
        }

        $urutanHari = [
            'Senin'  => 1,
            'Selasa' => 2,
            'Rabu'   => 3,
            'Kamis'  => 4,
            'Jumat'  => 5,
            'Sabtu'  => 6,
            'Minggu' => 7,
        ];

        return $rawSesi
            ->groupBy(function (Jadwal $j): string {
                $materiKey = mb_strtolower(trim((string) ($j->materi ?? '')));
                return ((int) $j->instruktur_id) . '|' . $materiKey;
            })
            ->map(function (Collection $group) use ($urutanHari): Jadwal {
                $sorted = $group->sortBy(function (Jadwal $j) use ($urutanHari): string {
                    $hariOrder = $j->is_pola_mingguan
                        ? sprintf('0-%02d', $urutanHari[$j->hari ?? ''] ?? 99)
                        : '1-' . ($j->tanggal?->format('Y-m-d') ?? '9999-99-99');

                    return $hariOrder . '-' . substr((string) $j->jam_mulai, 0, 5);
                })->values();

                /** @var Jadwal $primary */
                $primary = $sorted->first();

                $slotEntries = $sorted->map(function (Jadwal $j): array {
                    $jamMulai   = substr((string) $j->jam_mulai, 0, 5);
                    $jamSelesai = substr((string) $j->jam_selesai, 0, 5);
                    $jamLabel   = "{$jamMulai} - {$jamSelesai} WIB";
                    $hariLabel  = $j->is_pola_mingguan && $j->hari
                        ? $j->hari
                        : ($j->tanggal ? $j->tanggal->format('d/m/Y') : ($j->tanggal_label ?: '-'));

                    return [
                        'is_mingguan' => (bool) $j->is_pola_mingguan && ! empty($j->hari),
                        'hari'        => $hariLabel,
                        'jam'         => $jamLabel,
                        'key'         => $hariLabel . '|' . $jamLabel,
                    ];
                })->unique('key')->values();

                $uniqueTimes = $slotEntries->pluck('jam')->unique()->values();
                $allWeekly   = $slotEntries->every(fn (array $s): bool => $s['is_mingguan']);

                if ($slotEntries->count() === 1) {
                    $entry = $slotEntries->first();
                    $jadwalRingkas = ($entry['is_mingguan'] ? 'Setiap ' . $entry['hari'] : $entry['hari'])
                        . ', Pukul ' . $entry['jam'];
                } elseif ($uniqueTimes->count() === 1) {
                    $days = $slotEntries->pluck('hari')->unique()->values();
                    $daysText = $days->count() === 2
                        ? $days[0] . ' & ' . $days[1]
                        : $days->implode(', ');
                    $jadwalRingkas = ($allWeekly ? 'Setiap ' : '') . $daysText . ', Pukul ' . $uniqueTimes->first();
                } else {
                    $jadwalRingkas = $slotEntries
                        ->map(fn (array $s): string => $s['hari'] . ' (' . $s['jam'] . ')')
                        ->implode(', ');
                }

                $ruanganRingkas = $sorted
                    ->pluck('ruangan')
                    ->map(fn ($r) => trim((string) $r))
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $primary->setAttribute('jadwal_ringkas', $jadwalRingkas);
                $primary->setAttribute('ruangan_ringkas', $ruanganRingkas !== '' ? $ruanganRingkas : '-');

                return $primary;
            })
            ->values();
    }
}
