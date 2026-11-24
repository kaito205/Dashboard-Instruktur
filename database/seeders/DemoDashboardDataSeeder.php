<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\AktivitasMengajar;
use App\Models\Evaluasi;
use App\Models\ImportHistory;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\KodeEvaluasi;
use App\Models\ObservasiSupervisor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        $instrukturs = Instruktur::aktif()->orderBy('id')->get();
        if ($instrukturs->isEmpty()) {
            $this->command?->warn('Tidak ada instruktur aktif. DemoDashboardDataSeeder dilewati.');
            return;
        }

        $supervisor = User::where('role', 'supervisor')->first()
            ?? User::where('role', 'admin')->first()
            ?? User::first();
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (!$supervisor || !$admin) {
            $this->command?->warn('Data user admin/supervisor belum tersedia. DemoDashboardDataSeeder dilewati.');
            return;
        }

        $today = Carbon::today();
        $jadwalsByInstruktur = Jadwal::where('status', '!=', 'dibatalkan')
            ->orderBy('id')
            ->get()
            ->groupBy('instruktur_id');
        $kodes = KodeEvaluasi::orderBy('id')->get();

        // Pastikan minimal ada 1 data peringatan sertifikasi mendekati kedaluwarsa jika belum terisi
        if (Instruktur::whereNotNull('sertifikasi_berlaku_sampai')->doesntExist()) {
            $firstInstruktur = $instrukturs->first();
            if ($firstInstruktur) {
                $firstInstruktur->update([
                    'nama_sertifikasi'           => 'Sertifikat Kompetensi Instruktur BNSP',
                    'sertifikasi_berlaku_sampai' => $today->copy()->addDays(18)->toDateString(),
                ]);
            }
        }

        // Susun 12 hari kerja pada bulan berjalan (awal bulan s.d. hari ini, tanpa hari Minggu)
        $workdaysBulanIni = $this->collectWorkdays($today->copy()->startOfMonth(), $today, 12);
        if (count($workdaysBulanIni) < 12) {
            $cursor = $today->copy()->startOfMonth()->subDay();
            while (count($workdaysBulanIni) < 12) {
                if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                    array_unshift($workdaysBulanIni, $cursor->copy());
                }
                $cursor->subDay();
            }
        }

        // Susun 4 hari kerja pada bulan lalu sebagai pembanding periode
        $lastMonth = $today->copy()->subMonthNoOverflow();
        $workdaysBulanLalu = $this->collectWorkdays(
            $lastMonth->copy()->startOfMonth(),
            $lastMonth->copy()->endOfMonth(),
            4
        );

        $this->seedAbsensi($instrukturs, $jadwalsByInstruktur, $workdaysBulanIni, $workdaysBulanLalu, $today);
        $this->seedAktivitas($instrukturs, $jadwalsByInstruktur, $workdaysBulanIni, $workdaysBulanLalu, $supervisor);
        $this->seedEvaluasi($instrukturs, $jadwalsByInstruktur, $kodes, $workdaysBulanIni, $workdaysBulanLalu);
        $this->seedObservasi($instrukturs, $jadwalsByInstruktur, $workdaysBulanIni, $workdaysBulanLalu, $supervisor);
        $this->seedImportHistory($admin, $today);

        $this->command?->info('DemoDashboardDataSeeder berhasil mengisi data absensi, aktivitas, evaluasi, observasi, dan riwayat import.');
    }

    /**
     * Kumpulkan N hari kerja terakhir (bukan Minggu) dalam rentang tanggal.
     *
     * @return array<int, Carbon>
     */
    private function collectWorkdays(Carbon $start, Carbon $end, int $limit): array
    {
        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $days[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        if (count($days) > $limit) {
            return array_slice($days, -$limit);
        }

        return $days;
    }

    private function seedAbsensi($instrukturs, $jadwalsByInstruktur, array $workdaysBulanIni, array $workdaysBulanLalu, Carbon $today): void
    {
        foreach ($instrukturs as $idx => $ins) {
            $jadwalList = $jadwalsByInstruktur->get($ins->id, collect());
            $jadwalUtama = $jadwalList->first();
            $jamMulaiRaw = $jadwalUtama ? substr((string) $jadwalUtama->jam_mulai, 0, 5) : '08:00';
            $jamSelesaiRaw = $jadwalUtama ? substr((string) $jadwalUtama->jam_selesai, 0, 5) : '10:00';
            $jamMulaiTime = $jamMulaiRaw . ':00';

            $totalHari = count($workdaysBulanIni);

            foreach ($workdaysBulanIni as $dIdx => $tanggal) {
                // Variasikan jumlah kehadiran per hari agar grafik ApexCharts memiliki dinamika alami
                if ($idx === 1 && $dIdx === 2) {
                    continue;
                }
                if ($idx === 2 && in_array($dIdx, [1, 6], true)) {
                    continue;
                }
                if ($idx >= 3 && in_array($dIdx, [0, 5, 9], true)) {
                    continue;
                }

                $status = 'hadir';
                $menitTerlambat = 0;
                $keterangan = null;

                if ($idx === 0) {
                    if ($dIdx === 4) {
                        $status = 'terlambat';
                        $menitTerlambat = 20;
                        $keterangan = 'Lalu lintas padat menuju lokasi LPK';
                    }
                } elseif ($idx === 1) {
                    if ($dIdx === 3) {
                        $status = 'terlambat';
                        $menitTerlambat = 22;
                        $keterangan = 'Penyesuaian perangkat ajar di lab';
                    } elseif ($dIdx === 7) {
                        $status = 'terlambat';
                        $menitTerlambat = 18;
                        $keterangan = 'Keterlambatan transportasi pagi';
                    } elseif ($dIdx === 9) {
                        $status = 'izin';
                        $keterangan = 'Izin keperluan keluarga telah dikonfirmasi admin';
                    }
                } elseif ($idx === 2) {
                    if ($dIdx === 4) {
                        $status = 'terlambat';
                        $menitTerlambat = 25;
                        $keterangan = 'Hujan deras di perjalanan';
                    } elseif ($dIdx === 8) {
                        $status = 'terlambat';
                        $menitTerlambat = 19;
                        $keterangan = 'Menyiapkan bahan praktik kelas';
                    } elseif ($dIdx === 5) {
                        $status = 'izin';
                        $keterangan = 'Izin menghadiri uji kompetensi eksternal';
                    } elseif ($dIdx === 10) {
                        $status = 'tidak_hadir';
                        $keterangan = 'Tidak hadir tanpa konfirmasi';
                    }
                } else {
                    if ($dIdx === 2) {
                        $status = 'terlambat';
                        $menitTerlambat = 30;
                        $keterangan = 'Kendala perjalanan pagi';
                    } elseif ($dIdx === 7) {
                        $status = 'terlambat';
                        $menitTerlambat = 21;
                        $keterangan = 'Terlambat check-in sesi kelas';
                    } elseif (in_array($dIdx, [4, 10], true)) {
                        $status = 'tidak_hadir';
                        $keterangan = 'Absensi tidak tercatat pada sesi jadwal';
                    }
                }

                $isTodayOpenSession = ($idx === 0 && $dIdx === ($totalHari - 1) && $tanggal->isSameDay($today) && $status === 'hadir');

                $sumberPool = ['checkin_app', 'checkin_app', 'manual', 'import'];
                $sumber = $sumberPool[($idx + $dIdx) % count($sumberPool)];
                if ($isTodayOpenSession) {
                    $sumber = 'checkin_app';
                }

                $jadwalHariIni = $jadwalList
                    ->filter(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tanggal))
                    ->values();
                if ($jadwalHariIni->isEmpty()) {
                    continue;
                }

                $jadwalDipakai = $jadwalHariIni->get($dIdx % $jadwalHariIni->count());
                $jamMulaiJadwal = $jadwalDipakai ? substr((string) $jadwalDipakai->jam_mulai, 0, 5) : $jamMulaiRaw;
                $jamSelesaiJadwal = $jadwalDipakai ? substr((string) $jadwalDipakai->jam_selesai, 0, 5) : $jamSelesaiRaw;
                $jamMulaiJadwalTime = $jamMulaiJadwal . ':00';

                $payload = $this->buildAbsensiPayload(
                    $tanggal,
                    $jadwalDipakai?->id,
                    $jamMulaiJadwal,
                    $jamSelesaiJadwal,
                    $status,
                    $menitTerlambat,
                    $sumber,
                    $keterangan,
                    $isTodayOpenSession
                );

                Absensi::updateOrCreate(
                    [
                        'instruktur_id'    => $ins->id,
                        'tanggal'          => $tanggal->toDateString(),
                        'jam_mulai_jadwal' => $jamMulaiJadwalTime,
                    ],
                    $payload
                );
            }

            // 4 hari absensi bulan lalu untuk perbandingan periode di halaman Kinerja
            foreach ($workdaysBulanLalu as $lIdx => $tglLalu) {
                $jadwalLalu = $jadwalList->first(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tglLalu));
                if (! $jadwalLalu) {
                    continue;
                }
                $jamMulaiLalu = substr((string) $jadwalLalu->jam_mulai, 0, 5);
                $jamSelesaiLalu = substr((string) $jadwalLalu->jam_selesai, 0, 5);
                $statusLalu = ($idx >= 2 && $lIdx === 2) ? 'terlambat' : 'hadir';
                $menitLalu = $statusLalu === 'terlambat' ? 18 : 0;

                $payloadLalu = $this->buildAbsensiPayload(
                    $tglLalu,
                    $jadwalLalu->id,
                    $jamMulaiLalu,
                    $jamSelesaiLalu,
                    $statusLalu,
                    $menitLalu,
                    'checkin_app',
                    $statusLalu === 'terlambat' ? 'Terlambat persiapan materi' : null,
                    false
                );

                Absensi::updateOrCreate(
                    [
                        'instruktur_id'    => $ins->id,
                        'tanggal'          => $tglLalu->toDateString(),
                        'jam_mulai_jadwal' => $jamMulaiLalu . ':00',
                    ],
                    $payloadLalu
                );
            }
        }
    }

    private function buildAbsensiPayload(
        Carbon $tanggal,
        ?int $jadwalId,
        string $jamMulaiJadwal,
        string $jamSelesaiJadwal,
        string $status,
        int $menitTerlambat,
        string $sumber,
        ?string $keterangan,
        bool $biarkanBelumCheckout
    ): array {
        if (in_array($status, ['izin', 'tidak_hadir'], true)) {
            return [
                'jadwal_id'          => $jadwalId,
                'jam_checkin'        => null,
                'jam_checkout'       => null,
                'menit_terlambat'    => 0,
                'checkin_at'         => null,
                'checkout_at'        => null,
                'status'             => $status,
                'sumber'             => $sumber === 'checkin_app' ? 'manual' : $sumber,
                'latitude_checkin'   => null,
                'longitude_checkin'  => null,
                'latitude_checkout'  => null,
                'longitude_checkout' => null,
                'akurasi_gps'        => null,
                'status_gps'         => 'tidak_tersedia',
                'is_auto_checkout'   => false,
                'keterangan'         => $keterangan,
            ];
        }

        $baseMulai = Carbon::createFromFormat('Y-m-d H:i', $tanggal->toDateString() . ' ' . $jamMulaiJadwal);
        $checkinAt = $status === 'terlambat'
            ? $baseMulai->copy()->addMinutes($menitTerlambat)
            : $baseMulai->copy()->subMinutes(5);
        $checkoutAt = $biarkanBelumCheckout
            ? null
            : Carbon::createFromFormat('Y-m-d H:i', $tanggal->toDateString() . ' ' . $jamSelesaiJadwal)->addMinutes(4);

        $isApp = $sumber === 'checkin_app';

        return [
            'jadwal_id'          => $jadwalId,
            'jam_checkin'        => $checkinAt->format('H:i:s'),
            'jam_checkout'       => $checkoutAt ? $checkoutAt->format('H:i:s') : null,
            'menit_terlambat'    => $menitTerlambat,
            'checkin_at'         => $checkinAt,
            'checkout_at'        => $checkoutAt,
            'status'             => $status,
            'sumber'             => $sumber,
            'latitude_checkin'   => $isApp ? -7.3308868 : null,
            'longitude_checkin'  => $isApp ? 108.3560168 : null,
            'latitude_checkout'  => ($isApp && $checkoutAt) ? -7.3308868 : null,
            'longitude_checkout' => ($isApp && $checkoutAt) ? 108.3560168 : null,
            'akurasi_gps'        => $isApp ? 15.0 : null,
            'status_gps'         => $isApp ? 'terdeteksi' : 'tidak_tersedia',
            'is_auto_checkout'   => false,
            'keterangan'         => $keterangan,
        ];
    }

    private function seedAktivitas($instrukturs, $jadwalsByInstruktur, array $workdaysBulanIni, array $workdaysBulanLalu, User $supervisor): void
    {
        $topikCatatan = [
            'Penyampaian teori inti dan demonstrasi langsung di depan kelas.',
            'Sesi praktik terstruktur diikuti evaluasi hasil latihan peserta.',
            'Pembahasan studi kasus lapangan dan pendampingan individu.',
            'Review materi pertemuan sebelumnya dan uji praktik singkat.',
        ];
        $hasilList = [
            'Mayoritas peserta menyelesaikan modul latihan sesuai target waktu.',
            'Peserta memahami alur kerja dasar dan mampu mengulangi langkah praktik.',
            'Capaian tugas praktik mencapai standar kelulusan harian kelas.',
            'Seluruh peserta aktif berdiskusi dan menyerahkan berkas latihan.',
        ];

        foreach ($instrukturs as $idx => $ins) {
            $jadwalList = $jadwalsByInstruktur->get($ins->id, collect());
            if ($jadwalList->isEmpty()) {
                continue;
            }

            $validDaysIni = array_values(array_filter(
                $workdaysBulanIni,
                fn (Carbon $tgl): bool => $jadwalList->contains(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tgl))
            ));
            $sampleDaysIni = array_slice($validDaysIni, 0, 4);

            foreach ($sampleDaysIni as $k => $tgl) {
                $jadwalHariIni = $jadwalList
                    ->filter(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tgl))
                    ->values();
                if ($jadwalHariIni->isEmpty()) {
                    continue;
                }
                $jadwal = $jadwalHariIni->get($k % $jadwalHariIni->count());
                $jamMulai = substr((string) $jadwal->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $jadwal->jam_selesai, 0, 5);
                $confirmedAt = Carbon::createFromFormat('Y-m-d H:i', $tgl->toDateString() . ' ' . $jamSelesai)->addMinutes(10);

                $statusVerifikasi = 'diverifikasi';
                $verifiedBy = $supervisor->id;
                $verifiedAt = $confirmedAt->copy()->addHours(2);
                $catatanSupervisor = null;

                if (($idx === 1 && $k === 3) || ($idx === 2 && $k === 2)) {
                    $statusVerifikasi = 'menunggu';
                    $verifiedBy = null;
                    $verifiedAt = null;
                } elseif ($idx === 3 && $k === 3) {
                    $statusVerifikasi = 'perlu_revisi';
                    $catatanSupervisor = 'Lengkapi ringkasan capaian praktik peserta.';
                }

                AktivitasMengajar::updateOrCreate(
                    [
                        'jadwal_id'     => $jadwal->id,
                        'instruktur_id' => $ins->id,
                        'tanggal'       => $tgl->toDateString(),
                    ],
                    [
                        'jam_mulai_aktual'     => $jamMulai . ':00',
                        'jam_selesai_aktual'   => $jamSelesai . ':00',
                        'materi_disampaikan'   => $jadwal->materi ?: ('Praktik ' . $jadwal->nama_kelas),
                        'tautan_materi'        => null,
                        'jumlah_peserta_hadir' => 17 + (($idx + $k) % 7),
                        'status_konfirmasi'    => 'dikonfirmasi',
                        'status_verifikasi'    => $statusVerifikasi,
                        'catatan'              => $topikCatatan[$k % count($topikCatatan)],
                        'kendala_kegiatan'     => $k === 2 ? 'Penyesuaian proyektor dan perangkat praktikum di awal sesi.' : null,
                        'hasil_pembelajaran'   => $hasilList[$k % count($hasilList)],
                        'confirmed_at'         => $confirmedAt,
                        'verified_by'          => $verifiedBy,
                        'verified_at'          => $verifiedAt,
                        'catatan_supervisor'   => $catatanSupervisor,
                    ]
                );
            }

            // 2 aktivitas pada bulan lalu untuk komparasi periode
            $validDaysLalu = array_values(array_filter(
                $workdaysBulanLalu,
                fn (Carbon $tgl): bool => $jadwalList->contains(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tgl))
            ));
            foreach (array_slice($validDaysLalu, 0, 2) as $k => $tglLalu) {
                $jadwalHariLalu = $jadwalList
                    ->filter(fn (Jadwal $j): bool => $j->berlakuPadaTanggal($tglLalu))
                    ->values();
                if ($jadwalHariLalu->isEmpty()) {
                    continue;
                }
                $jadwal = $jadwalHariLalu->get($k % $jadwalHariLalu->count());
                $jamMulai = substr((string) $jadwal->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $jadwal->jam_selesai, 0, 5);
                $confirmedAt = Carbon::createFromFormat('Y-m-d H:i', $tglLalu->toDateString() . ' ' . $jamSelesai)->addMinutes(10);

                AktivitasMengajar::updateOrCreate(
                    [
                        'jadwal_id'     => $jadwal->id,
                        'instruktur_id' => $ins->id,
                        'tanggal'       => $tglLalu->toDateString(),
                    ],
                    [
                        'jam_mulai_aktual'     => $jamMulai . ':00',
                        'jam_selesai_aktual'   => $jamSelesai . ':00',
                        'materi_disampaikan'   => $jadwal->materi ?: ('Materi ' . $jadwal->nama_kelas),
                        'tautan_materi'        => null,
                        'jumlah_peserta_hadir' => 16 + (($idx + $k) % 6),
                        'status_konfirmasi'    => 'dikonfirmasi',
                        'status_verifikasi'    => 'diverifikasi',
                        'catatan'              => $topikCatatan[$k % count($topikCatatan)],
                        'kendala_kegiatan'     => null,
                        'hasil_pembelajaran'   => $hasilList[$k % count($hasilList)],
                        'confirmed_at'         => $confirmedAt,
                        'verified_by'          => $supervisor->id,
                        'verified_at'          => $confirmedAt->copy()->addHour(),
                        'catatan_supervisor'   => null,
                    ]
                );
            }
        }
    }

    private function seedEvaluasi($instrukturs, $jadwalsByInstruktur, $kodes, array $workdaysBulanIni, array $workdaysBulanLalu): void
    {
        $pesertaBulanIni = [
            ['Rina Kartika', 'Deden Kurniawan', 'Siti Nurhaliza', 'Ahmad Fauzi', 'Dewi Anggraini'],
            ['Hendra Wijaya', 'Nadia Putri', 'Yusuf Maulana', 'Fitriani Lestari', 'Fajar Pratama'],
            ['Lestari Wulandari', 'Reza Firmansyah', 'Indah Permata', 'Agus Setiawan', 'Maya Sari'],
            ['Bayu Saputra', 'Novi Rahmawati', 'Ilham Ramadhan', 'Wulan Dari', 'Rizky Hidayat'],
        ];

        $pesertaBulanLalu = [
            ['Taufik Hidayat', 'Anisa Fitri'],
            ['Galih Pradipta', 'Siska Amalia'],
            ['Bagus Nugroho', 'Ratna Dewi'],
            ['Rendi Kusuma', 'Vina Melati'],
        ];

        // Profil skor 5 indikator agar grafik rata-rata evaluasi menampilkan variasi jelas (~92, ~86, ~78, ~68)
        $profilSkorIni = [
            0 => [
                [5, 5, 4, 5, 4], // 92.0
                [5, 4, 5, 5, 4], // 92.0
                [5, 5, 5, 4, 4], // 92.0
                [5, 5, 4, 5, 5], // 96.0
                [4, 5, 4, 5, 4], // 88.0 -> rata-rata 92.0
            ],
            1 => [
                [4, 4, 5, 4, 4], // 84.0
                [5, 4, 4, 5, 4], // 88.0
                [4, 5, 4, 4, 5], // 88.0
                [4, 4, 4, 5, 4], // 84.0
                [4, 5, 4, 4, 4], // 86.0 -> rata-rata ~86.0
            ],
            2 => [
                [4, 4, 4, 4, 3], // 76.0
                [4, 4, 4, 4, 4], // 80.0
                [4, 3, 4, 4, 4], // 76.0
                [4, 4, 4, 4, 4], // 80.0
                [4, 4, 3, 4, 4], // 78.0 -> rata-rata ~78.0
            ],
            3 => [
                [3, 4, 3, 4, 3], // 68.0
                [4, 3, 3, 3, 4], // 68.0
                [3, 3, 4, 3, 4], // 68.0
                [3, 4, 3, 3, 4], // 68.0
                [4, 3, 4, 3, 3], // 68.0 -> rata-rata 68.0
            ],
        ];

        $komentarPositif = [
            'Penjelasan mudah dipahami dan contoh praktik sangat membantu.',
            'Instruktur sabar membimbing saat peserta mengalami kesulitan.',
            'Materi tersampaikan runtut dari dasar hingga latihan.',
            'Suasana kelas interaktif dan tepat waktu.',
            'Tambahan porsi studi kasus akan membuat sesi makin optimal.',
        ];

        $sampleEvalDays = [
            $workdaysBulanIni[1] ?? $workdaysBulanIni[0],
            $workdaysBulanIni[3] ?? $workdaysBulanIni[0],
            $workdaysBulanIni[5] ?? $workdaysBulanIni[0],
            $workdaysBulanIni[8] ?? $workdaysBulanIni[0],
            $workdaysBulanIni[10] ?? end($workdaysBulanIni),
        ];

        foreach ($instrukturs as $idx => $ins) {
            $jadwalList = $jadwalsByInstruktur->get($ins->id, collect());
            $jadwalUtama = $jadwalList->first();
            $kodeEvaluasi = $this->resolveKodeEvaluasi($ins, $jadwalList, $kodes);
            $materi = $jadwalUtama?->materi ?: ($kodeEvaluasi?->nama_kelas ?? 'Evaluasi Pembelajaran');

            $namaPoolIni = $pesertaBulanIni[$idx % count($pesertaBulanIni)];
            $skorPoolIni = $profilSkorIni[min($idx, 3)];

            foreach ($namaPoolIni as $rIdx => $namaPengisi) {
                $skorVals = $skorPoolIni[$rIdx];
                $skorMap = [
                    'indikator_1' => $skorVals[0],
                    'indikator_2' => $skorVals[1],
                    'indikator_3' => $skorVals[2],
                    'indikator_4' => $skorVals[3],
                    'indikator_5' => $skorVals[4],
                ];
                $totalNilai = Evaluasi::hitungNilaiKonversi100($skorMap);
                $komentar = $komentarPositif[$rIdx % count($komentarPositif)];
                $tglEval = $sampleEvalDays[$rIdx]->copy()->setTime(14 + ($rIdx % 3), 15, 0);

                $ev = Evaluasi::firstOrNew([
                    'instruktur_id'   => $ins->id,
                    'nama_pengisi'    => $namaPengisi,
                    'materi_evaluasi' => $materi,
                ]);
                $ev->kode_evaluasi_id = $kodeEvaluasi?->id;
                $ev->jadwal_id = $jadwalUtama?->id;
                $ev->jawaban = [
                    'skor_indikator' => $skorMap,
                    'komentar'       => $komentar,
                ];
                $ev->total_nilai = $totalNilai;
                $ev->komentar = $komentar;
                $ev->batch_import = null;
                $ev->ip_address = '127.0.0.1';
                $ev->created_at = $tglEval;
                $ev->updated_at = $tglEval;
                $ev->save();
            }

            // 2 responden bulan lalu per instruktur untuk pembanding Kinerja periode lalu
            $namaPoolLalu = $pesertaBulanLalu[$idx % count($pesertaBulanLalu)];
            foreach ($namaPoolLalu as $lIdx => $namaLalu) {
                $baseSkor = max(3, 5 - min($idx, 2) - $lIdx);
                $skorMap = [
                    'indikator_1' => $baseSkor,
                    'indikator_2' => min(5, $baseSkor + ($idx === 0 ? 0 : 1)),
                    'indikator_3' => $baseSkor,
                    'indikator_4' => $baseSkor,
                    'indikator_5' => 4,
                ];
                $totalNilai = Evaluasi::hitungNilaiKonversi100($skorMap);
                $tglLalu = ($workdaysBulanLalu[$lIdx] ?? $workdaysBulanLalu[0])->copy()->setTime(15, 30, 0);
                $materiLalu = $materi . ' (Periode Lalu)';

                $ev = Evaluasi::firstOrNew([
                    'instruktur_id'   => $ins->id,
                    'nama_pengisi'    => $namaLalu,
                    'materi_evaluasi' => $materiLalu,
                ]);
                $ev->kode_evaluasi_id = $kodeEvaluasi?->id;
                $ev->jadwal_id = $jadwalUtama?->id;
                $ev->jawaban = [
                    'skor_indikator' => $skorMap,
                    'komentar'       => 'Sesi pembelajaran berjalan baik dan terstruktur.',
                ];
                $ev->total_nilai = $totalNilai;
                $ev->komentar = 'Sesi pembelajaran berjalan baik dan terstruktur.';
                $ev->batch_import = null;
                $ev->ip_address = '127.0.0.1';
                $ev->created_at = $tglLalu;
                $ev->updated_at = $tglLalu;
                $ev->save();
            }
        }

        foreach (KodeEvaluasi::all() as $kode) {
            $kode->update([
                'jumlah_diisi' => Evaluasi::where('kode_evaluasi_id', $kode->id)->count(),
            ]);
        }
    }

    private function resolveKodeEvaluasi(Instruktur $ins, $jadwalList, $kodes): ?KodeEvaluasi
    {
        $byInstruktur = $kodes->firstWhere('instruktur_id', $ins->id);
        if ($byInstruktur) {
            return $byInstruktur;
        }

        foreach ($jadwalList as $jadwal) {
            $kodeKelas = strtoupper(trim((string) $jadwal->kode_kelas));
            $namaKelas = strtoupper(trim((string) $jadwal->nama_kelas));

            $matched = $kodes->first(function ($k) use ($kodeKelas, $namaKelas) {
                $kode = strtoupper(trim((string) $k->kode));
                $nama = strtoupper(trim((string) $k->nama_kelas));
                return ($kodeKelas !== '' && $kode === $kodeKelas)
                    || ($namaKelas !== '' && ($kode === $namaKelas || $nama === $namaKelas));
            });

            if ($matched) {
                return $matched;
            }
        }

        return $kodes->first();
    }

    private function seedObservasi($instrukturs, $jadwalsByInstruktur, array $workdaysBulanIni, array $workdaysBulanLalu, User $supervisor): void
    {
        $profilObservasi = [
            0 => [[5, 5, 5, 4, 5], [5, 4, 5, 5, 5]], // ~96
            1 => [[4, 5, 4, 4, 4], [4, 4, 5, 4, 4]], // ~84
            2 => [[4, 4, 3, 4, 4], [4, 3, 4, 4, 4]], // ~76
            3 => [[3, 3, 3, 4, 3], [3, 4, 3, 3, 3]], // ~64
        ];

        $catatanPool = [
            'Penyampaian materi terstruktur, manajemen kelas terjaga sepanjang sesi.',
            'Peserta aktif praktik langsung dengan pendampingan langkah demi langkah.',
        ];
        $rekomendasiPool = [
            'Pertahankan ritme pembukaan sesi dan berikan rangkuman evaluasi di akhir pertemuan.',
            'Tambahkan sesi tanya jawab singkat sebelum perpindahan topik praktik.',
        ];

        foreach ($instrukturs as $idx => $ins) {
            $jadwalUtama = $jadwalsByInstruktur->get($ins->id, collect())->first();
            $skorProfil = $profilObservasi[min($idx, 3)];

            $tanggalObsIni = [
                $workdaysBulanIni[3] ?? $workdaysBulanIni[0],
                $workdaysBulanIni[9] ?? end($workdaysBulanIni),
            ];

            foreach ($tanggalObsIni as $oIdx => $tglObs) {
                $nilai = $skorProfil[$oIdx];
                $skorMap = [
                    'indikator_1' => $nilai[0],
                    'indikator_2' => $nilai[1],
                    'indikator_3' => $nilai[2],
                    'indikator_4' => $nilai[3],
                    'indikator_5' => $nilai[4],
                ];

                ObservasiSupervisor::updateOrCreate(
                    [
                        'instruktur_id' => $ins->id,
                        'tanggal'       => $tglObs->toDateString(),
                    ],
                    [
                        'supervisor_id'       => $supervisor->id,
                        'jadwal_id'           => $jadwalUtama?->id,
                        'indikator_penilaian' => ['skor' => $skorMap],
                        'skor_observasi'      => ObservasiSupervisor::hitungSkorSkala100($skorMap),
                        'catatan_observasi'   => $catatanPool[$oIdx % count($catatanPool)],
                        'rekomendasi'         => $rekomendasiPool[$oIdx % count($rekomendasiPool)],
                    ]
                );
            }

            // 1 observasi bulan lalu per instruktur untuk komparasi skor
            $tglObsLalu = $workdaysBulanLalu[1] ?? $workdaysBulanLalu[0];
            $nilaiLalu = $skorProfil[0];
            $skorMapLalu = [
                'indikator_1' => max(3, $nilaiLalu[0] - ($idx > 0 ? 1 : 0)),
                'indikator_2' => $nilaiLalu[1],
                'indikator_3' => $nilaiLalu[2],
                'indikator_4' => $nilaiLalu[3],
                'indikator_5' => $nilaiLalu[4],
            ];

            ObservasiSupervisor::updateOrCreate(
                [
                    'instruktur_id' => $ins->id,
                    'tanggal'       => $tglObsLalu->toDateString(),
                ],
                [
                    'supervisor_id'       => $supervisor->id,
                    'jadwal_id'           => $jadwalUtama?->id,
                    'indikator_penilaian' => ['skor' => $skorMapLalu],
                    'skor_observasi'      => ObservasiSupervisor::hitungSkorSkala100($skorMapLalu),
                    'catatan_observasi'   => 'Observasi berkala pelaksanaan kelas bulan sebelumnya.',
                    'rekomendasi'         => 'Tingkatkan alokasi waktu diskusi studi kasus peserta.',
                ]
            );
        }
    }

    private function seedImportHistory(User $admin, Carbon $today): void
    {
        $entries = [
            [
                'nama_file'      => 'rekap_absensi_november_2026.xlsx',
                'jenis'          => 'absensi',
                'total_baris'    => 32,
                'baris_berhasil' => 32,
                'baris_gagal'    => 0,
                'detail_error'   => null,
                'status'         => 'sukses',
                'created_at'     => $today->copy()->subDays(5)->setTime(9, 15, 0),
            ],
            [
                'nama_file'      => 'ekspor_evaluasi_kelas_november_2026.csv',
                'jenis'          => 'evaluasi',
                'total_baris'    => 20,
                'baris_berhasil' => 20,
                'baris_gagal'    => 0,
                'detail_error'   => null,
                'status'         => 'sukses',
                'created_at'     => $today->copy()->subDays(3)->setTime(14, 40, 0),
            ],
            [
                'nama_file'      => 'absensi_tambahan_minggu2_2026.csv',
                'jenis'          => 'absensi',
                'total_baris'    => 15,
                'baris_berhasil' => 13,
                'baris_gagal'    => 2,
                'detail_error'   => [
                    'Baris 7: Data duplikat dilewati',
                    'Baris 12: Format tanggal tidak dikenali',
                ],
                'status'         => 'parsial',
                'created_at'     => $today->copy()->subDay()->setTime(16, 10, 0),
            ],
        ];

        foreach ($entries as $entry) {
            $waktu = $entry['created_at'];
            $record = ImportHistory::updateOrCreate(
                ['nama_file' => $entry['nama_file']],
                [
                    'user_id'        => $admin->id,
                    'jenis'          => $entry['jenis'],
                    'total_baris'    => $entry['total_baris'],
                    'baris_berhasil' => $entry['baris_berhasil'],
                    'baris_gagal'    => $entry['baris_gagal'],
                    'detail_error'   => $entry['detail_error'],
                    'status'         => $entry['status'],
                ]
            );
            $record->created_at = $waktu;
            $record->updated_at = $waktu;
            $record->save();
        }
    }
}
