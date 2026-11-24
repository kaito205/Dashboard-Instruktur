<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kelas')) {
            Schema::create('kelas', function (Blueprint $table) {
                $table->id();
                $table->string('kode_kelas', 50)->unique();
                $table->string('nama_kelas', 150);
                $table->string('program_pelatihan', 150)->nullable();
                $table->unsignedInteger('kuota_peserta')->default(20);
                $table->string('ruangan_default', 100)->nullable();
                $table->date('periode_mulai')->nullable();
                $table->date('periode_selesai')->nullable();
                $table->string('status', 20)->default('aktif');
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('nama_kelas');
            });
        }

        if (Schema::hasTable('jadwals') && ! Schema::hasColumn('jadwals', 'kelas_id')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->foreignId('kelas_id')
                    ->nullable()
                    ->after('instruktur_pengganti_id')
                    ->constrained('kelas')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('kode_evaluasis') && ! Schema::hasColumn('kode_evaluasis', 'kelas_id')) {
            Schema::table('kode_evaluasis', function (Blueprint $table) {
                $table->foreignId('kelas_id')
                    ->nullable()
                    ->after('instruktur_id')
                    ->constrained('kelas')
                    ->nullOnDelete();
            });
        }

        $this->backfillExistingKelas();
    }

    public function down(): void
    {
        if (Schema::hasTable('kode_evaluasis') && Schema::hasColumn('kode_evaluasis', 'kelas_id')) {
            Schema::table('kode_evaluasis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kelas_id');
            });
        }

        if (Schema::hasTable('jadwals') && Schema::hasColumn('jadwals', 'kelas_id')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kelas_id');
            });
        }

        Schema::dropIfExists('kelas');
    }

    private function backfillExistingKelas(): void
    {
        $programMap = [
            'RPL'  => 'Rekayasa Perangkat Lunak',
            'TKJ1' => 'Teknik Komputer & Jaringan',
            'TKJ2' => 'Teknik Komputer & Jaringan',
            'TKJ3' => 'Teknik Komputer & Jaringan',
            'AP1'  => 'Administrasi Perkantoran',
            'AP2'  => 'Administrasi Perkantoran',
            'AP3'  => 'Administrasi Perkantoran',
            'PS1'  => 'Public Speaking',
            'PS2'  => 'Public Speaking',
        ];

        $normalizeKode = static function (?string $raw): string {
            $upper = strtoupper(trim((string) $raw));
            if ($upper === '') {
                return '';
            }
            $clean = preg_replace('/[^A-Z0-9_-]+/', '-', $upper);
            $clean = trim((string) $clean, '-');

            return $clean !== '' ? substr($clean, 0, 50) : '';
        };

        $candidates = [];

        if (Schema::hasTable('jadwals')) {
            $jadwalRows = DB::table('jadwals')
                ->orderBy('id')
                ->get(['id', 'kode_kelas', 'nama_kelas', 'ruangan', 'jumlah_peserta', 'periode_mulai', 'periode_selesai']);

            foreach ($jadwalRows as $row) {
                $namaRaw = trim((string) ($row->nama_kelas ?? ''));
                $kode = $normalizeKode($row->kode_kelas);
                if ($kode === '') {
                    $kode = $normalizeKode($namaRaw);
                }
                if ($kode === '') {
                    $kode = 'KLS-' . $row->id;
                }

                $nama = $namaRaw !== '' ? substr($namaRaw, 0, 150) : $kode;
                $ruang = trim((string) ($row->ruangan ?? ''));
                $peserta = (int) ($row->jumlah_peserta ?? 0);
                $mulai = ! empty($row->periode_mulai) ? substr((string) $row->periode_mulai, 0, 10) : null;
                $selesai = ! empty($row->periode_selesai) ? substr((string) $row->periode_selesai, 0, 10) : null;

                if (! isset($candidates[$kode])) {
                    $candidates[$kode] = [
                        'kode_kelas'        => $kode,
                        'nama_kelas'        => $nama,
                        'program_pelatihan' => $programMap[$kode] ?? null,
                        'kuota_peserta'     => $peserta > 0 ? $peserta : 20,
                        'ruangan_default'   => $ruang !== '' ? substr($ruang, 0, 100) : null,
                        'periode_mulai'     => $mulai,
                        'periode_selesai'   => $selesai,
                        'status'            => 'aktif',
                        'keterangan'        => null,
                    ];
                } else {
                    if ($candidates[$kode]['nama_kelas'] === $kode && $nama !== $kode) {
                        $candidates[$kode]['nama_kelas'] = $nama;
                    }
                    if ($candidates[$kode]['ruangan_default'] === null && $ruang !== '') {
                        $candidates[$kode]['ruangan_default'] = substr($ruang, 0, 100);
                    }
                    if ($peserta > $candidates[$kode]['kuota_peserta']) {
                        $candidates[$kode]['kuota_peserta'] = $peserta;
                    }
                    if ($mulai !== null && ($candidates[$kode]['periode_mulai'] === null || $mulai < $candidates[$kode]['periode_mulai'])) {
                        $candidates[$kode]['periode_mulai'] = $mulai;
                    }
                    if ($selesai !== null && ($candidates[$kode]['periode_selesai'] === null || $selesai > $candidates[$kode]['periode_selesai'])) {
                        $candidates[$kode]['periode_selesai'] = $selesai;
                    }
                }
            }
        }

        if (Schema::hasTable('kode_evaluasis')) {
            $kodeRows = DB::table('kode_evaluasis')
                ->orderBy('id')
                ->get(['id', 'kode', 'nama_kelas', 'is_active', 'keterangan']);

            foreach ($kodeRows as $row) {
                $kode = $normalizeKode($row->kode);
                if ($kode === '') {
                    continue;
                }

                $namaRaw = trim((string) ($row->nama_kelas ?? ''));
                $inProgramMap = array_key_exists($kode, $programMap);

                if (! isset($candidates[$kode]) && $namaRaw === '' && ! $inProgramMap) {
                    continue;
                }

                $ket = trim((string) ($row->keterangan ?? ''));

                if (! isset($candidates[$kode])) {
                    $candidates[$kode] = [
                        'kode_kelas'        => $kode,
                        'nama_kelas'        => $namaRaw !== '' ? substr($namaRaw, 0, 150) : $kode,
                        'program_pelatihan' => $programMap[$kode] ?? null,
                        'kuota_peserta'     => 20,
                        'ruangan_default'   => null,
                        'periode_mulai'     => null,
                        'periode_selesai'   => null,
                        'status'            => ! empty($row->is_active) ? 'aktif' : 'nonaktif',
                        'keterangan'        => $ket !== '' ? $ket : null,
                    ];
                } else {
                    if ($candidates[$kode]['nama_kelas'] === $kode && $namaRaw !== '') {
                        $candidates[$kode]['nama_kelas'] = substr($namaRaw, 0, 150);
                    }
                    if ($candidates[$kode]['keterangan'] === null && $ket !== '') {
                        $candidates[$kode]['keterangan'] = $ket;
                    }
                }
            }
        }

        $now = now();
        $existingRows = DB::table('kelas')->get(['id', 'kode_kelas', 'nama_kelas']);
        $kodeToId = [];
        $namaLowerToId = [];

        foreach ($existingRows as $existing) {
            $kNorm = $normalizeKode($existing->kode_kelas);
            if ($kNorm !== '') {
                $kodeToId[$kNorm] = (int) $existing->id;
            }
            $nLower = mb_strtolower(trim((string) $existing->nama_kelas));
            if ($nLower !== '' && ! isset($namaLowerToId[$nLower])) {
                $namaLowerToId[$nLower] = (int) $existing->id;
            }
        }

        foreach ($candidates as $kode => $payload) {
            if (! isset($kodeToId[$kode])) {
                $id = (int) DB::table('kelas')->insertGetId([
                    'kode_kelas'        => $payload['kode_kelas'],
                    'nama_kelas'        => $payload['nama_kelas'],
                    'program_pelatihan' => $payload['program_pelatihan'],
                    'kuota_peserta'     => $payload['kuota_peserta'],
                    'ruangan_default'   => $payload['ruangan_default'],
                    'periode_mulai'     => $payload['periode_mulai'],
                    'periode_selesai'   => $payload['periode_selesai'],
                    'status'            => $payload['status'],
                    'keterangan'        => $payload['keterangan'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
                $kodeToId[$kode] = $id;
                $nLower = mb_strtolower(trim((string) $payload['nama_kelas']));
                if ($nLower !== '' && ! isset($namaLowerToId[$nLower])) {
                    $namaLowerToId[$nLower] = $id;
                }
            }
        }

        if (Schema::hasTable('jadwals')) {
            $unlinkedJadwals = DB::table('jadwals')
                ->whereNull('kelas_id')
                ->get(['id', 'kode_kelas', 'nama_kelas']);

            foreach ($unlinkedJadwals as $j) {
                $kNorm = $normalizeKode($j->kode_kelas);
                $nLower = mb_strtolower(trim((string) ($j->nama_kelas ?? '')));
                $nAsKode = $normalizeKode($j->nama_kelas);

                $matchedId = $kodeToId[$kNorm]
                    ?? ($nLower !== '' ? ($namaLowerToId[$nLower] ?? null) : null)
                    ?? ($nAsKode !== '' ? ($kodeToId[$nAsKode] ?? null) : null);

                if ($matchedId) {
                    DB::table('jadwals')
                        ->where('id', $j->id)
                        ->update(['kelas_id' => $matchedId]);
                }
            }
        }

        if (Schema::hasTable('kode_evaluasis')) {
            $unlinkedKodes = DB::table('kode_evaluasis')
                ->whereNull('kelas_id')
                ->get(['id', 'kode', 'nama_kelas']);

            foreach ($unlinkedKodes as $k) {
                $kNorm = $normalizeKode($k->kode);
                $nLower = mb_strtolower(trim((string) ($k->nama_kelas ?? '')));

                $matchedId = $kodeToId[$kNorm]
                    ?? ($nLower !== '' ? ($namaLowerToId[$nLower] ?? null) : null);

                if ($matchedId) {
                    DB::table('kode_evaluasis')
                        ->where('id', $k->id)
                        ->update(['kelas_id' => $matchedId]);
                }
            }
        }
    }
};
