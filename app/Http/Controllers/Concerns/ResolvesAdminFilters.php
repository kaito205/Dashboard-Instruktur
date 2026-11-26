<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\Kelas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait ResolvesAdminFilters
{
    protected function parseDateOrDefault(mixed $raw, string $default): string
    {
        $val = trim((string) ($raw ?? ''));
        if ($val === '') {
            return $default;
        }

        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Baca dan normalisasi parameter tanggal 'dari' dan 'sampai' dari request.
     * Menukar posisi secara otomatis bila tanggal 'dari' lebih besar dari 'sampai'.
     *
     * @return array{0: string, 1: string}
     */
    protected function resolveDateRange(
        Request $request,
        ?string $defaultDari = null,
        ?string $defaultSampai = null
    ): array {
        $dari = $this->parseDateOrDefault(
            $request->input('dari'),
            $defaultDari ?? now()->startOfMonth()->toDateString()
        );
        $sampai = $this->parseDateOrDefault(
            $request->input('sampai'),
            $defaultSampai ?? now()->endOfMonth()->toDateString()
        );

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    protected function getActiveInstrukturOptions(
        array $columns = ['id', 'nama_lengkap', 'kode_instruktur']
    ): Collection {
        return Instruktur::aktif()
            ->orderBy('nama_lengkap')
            ->get($columns);
    }

    protected function getKelasOptions(
        array $columns = ['id', 'kode_kelas', 'nama_kelas', 'program_pelatihan', 'status']
    ): Collection {
        if (! Schema::hasTable('kelas')) {
            return collect();
        }

        return Kelas::orderBy('kode_kelas')->get($columns);
    }

    protected function countUniqueKelasFromJadwals(Collection $jadwals): int
    {
        if ($jadwals->isEmpty()) {
            return 0;
        }

        $byKode = [];
        $byNama = [];

        if (Schema::hasTable('kelas')) {
            foreach (Kelas::withTrashed()->get(['id', 'kode_kelas', 'nama_kelas']) as $kls) {
                $kodeKey = strtoupper(trim((string) $kls->kode_kelas));
                $namaKey = mb_strtolower(trim((string) $kls->nama_kelas));
                if ($kodeKey !== '') {
                    $byKode[$kodeKey] = (int) $kls->id;
                }
                if ($namaKey !== '' && ! isset($byNama[$namaKey])) {
                    $byNama[$namaKey] = (int) $kls->id;
                }
            }
        }

        foreach ($jadwals as $j) {
            if (! empty($j->kelas_id)) {
                $kodeKey = strtoupper(trim((string) ($j->kode_kelas ?? '')));
                $namaKey = mb_strtolower(trim((string) ($j->nama_kelas ?? '')));
                if ($kodeKey !== '' && ! isset($byKode[$kodeKey])) {
                    $byKode[$kodeKey] = (int) $j->kelas_id;
                }
                if ($namaKey !== '' && ! isset($byNama[$namaKey])) {
                    $byNama[$namaKey] = (int) $j->kelas_id;
                }
            }
        }

        return $jadwals
            ->map(function (Jadwal $jadwal) use ($byKode, $byNama): string {
                if (! empty($jadwal->kelas_id)) {
                    return 'id:' . (int) $jadwal->kelas_id;
                }

                $kodeKey = strtoupper(trim((string) ($jadwal->kode_kelas ?? '')));
                $namaKey = mb_strtolower(trim((string) ($jadwal->nama_kelas ?? '')));

                if ($kodeKey !== '' && isset($byKode[$kodeKey])) {
                    return 'id:' . $byKode[$kodeKey];
                }
                if ($namaKey !== '' && isset($byNama[$namaKey])) {
                    return 'id:' . $byNama[$namaKey];
                }
                if ($kodeKey !== '') {
                    return 'kode:' . $kodeKey;
                }

                return 'nama:' . $namaKey;
            })
            ->filter(fn (string $key): bool => $key !== 'nama:')
            ->unique()
            ->count();
    }
}
