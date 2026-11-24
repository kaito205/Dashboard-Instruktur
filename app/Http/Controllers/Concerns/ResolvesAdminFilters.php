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
        return $jadwals
            ->map(function (Jadwal $jadwal): string {
                if (! empty($jadwal->kelas_id)) {
                    return 'id:' . (int) $jadwal->kelas_id;
                }

                return 'nama:' . strtolower(trim((string) $jadwal->nama_kelas));
            })
            ->filter(fn (string $key): bool => $key !== 'nama:')
            ->unique()
            ->count();
    }
}
