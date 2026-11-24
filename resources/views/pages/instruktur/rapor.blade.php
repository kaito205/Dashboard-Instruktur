@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Rapor Kinerja Pribadi</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                {{ $instruktur->nama_lengkap }} ({{ $instruktur->kode_instruktur }}) - Ringkasan 4 komponen kinerja.
            </p>
        </div>

        <form method="GET" action="{{ route('instruktur.rapor.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari</label>
                <input type="date" id="dari" name="dari" value="{{ $dari }}"
                    class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
            </div>
            <div>
                <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                <input type="date" id="sampai" name="sampai" value="{{ $sampai }}"
                    class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
            </div>
            <button type="submit"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Terapkan
            </button>
            @if (request()->filled('dari') || request()->filled('sampai'))
                <a href="{{ route('instruktur.rapor.index') }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Reset
                </a>
            @endif
        </form>
    </div>

    @php
        $fmtBobot = static fn (float $v): string => \App\Services\KinerjaCalculationService::formatAngka($v);
        $bobotStandarStr = implode('/', [
            $fmtBobot((float) ($rapor['bobot']['kehadiran'] ?? ($ruleConfig['bobot_kehadiran'] ?? 30))),
            $fmtBobot((float) ($rapor['bobot']['aktivitas'] ?? ($ruleConfig['bobot_aktivitas'] ?? 20))),
            $fmtBobot((float) ($rapor['bobot']['evaluasi'] ?? ($ruleConfig['bobot_evaluasi'] ?? 30))),
            $fmtBobot((float) ($rapor['bobot']['observasi'] ?? ($ruleConfig['bobot_observasi'] ?? 20))),
        ]);
        $kategoriColorClass = match ($rapor['kategori_color'] ?? 'gray') {
            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
            'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
            'amber'   => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
            'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
            default   => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Skor Akhir Kinerja (0-100)</p>
            <p class="mt-2 font-mono text-3xl font-bold text-gray-900 dark:text-white">
                {{ $rapor['skor_akhir'] !== null ? number_format((float) $rapor['skor_akhir'], 2) : '-' }}
            </p>
            @if (isset($deltaSkor) && $deltaSkor !== null)
                <p class="mt-1 text-xs font-semibold {{ $deltaSkor >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $deltaSkor >= 0 ? '+' : '' }}{{ number_format($deltaSkor, 2) }} vs periode sebelumnya
                </p>
            @else
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($dari)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($sampai)->format('d/m/Y') }}
                </p>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kategori Kinerja</p>
            <div class="mt-2">
                <span class="inline-flex items-center rounded-lg px-3 py-1 text-sm font-bold {{ $kategoriColorClass }}">
                    {{ $rapor['kategori_label'] ?? 'Belum Ada Data Penilaian' }}
                </span>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ $kategoriRanges['sangat_baik'] ?? 'Sangat Baik (>= 85)' }} | {{ $kategoriRanges['baik'] ?? 'Baik (75 - < 85)' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Capaian Pengajaran</p>
            <p class="mt-2 font-mono text-2xl font-bold text-gray-900 dark:text-white">
                {{ $totalPesertaDilatih ?? 0 }} Peserta
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $rapor['total_jadwal'] ?? 0 }} kelas &middot; {{ $rapor['jam_mengajar'] ?? 0 }} jam mengajar
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Distribusi Bobot</p>
            <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white">
                {{ $rapor['redistribusi_bobot'] ? 'Redistribusi Proporsional Aktif' : "Bobot Standar ({$bobotStandarStr})" }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Komponen tanpa data tidak diberi nilai 0.
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian 4 Komponen Penilaian</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Komponen</th>
                        <th class="px-5 py-3">Data Mentah</th>
                        <th class="px-5 py-3">Skor (0-100)</th>
                        <th class="px-5 py-3">Bobot Standar</th>
                        <th class="px-5 py-3">Bobot Efektif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <tr>
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">1. Kehadiran Mengajar</td>
                        <td class="px-5 py-4 text-xs text-gray-700 dark:text-gray-300">{{ $rapor['data_mentah']['kehadiran_label'] }}</td>
                        <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">
                            {{ $rapor['skor_kehadiran'] !== null ? number_format((float) $rapor['skor_kehadiran'], 2) : 'Belum Ada Data' }}
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $rapor['bobot']['kehadiran'] }}%</td>
                        <td class="px-5 py-4 text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $rapor['bobot_efektif']['kehadiran'] }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">2. Kepatuhan Laporan Aktivitas</td>
                        <td class="px-5 py-4 text-xs text-gray-700 dark:text-gray-300">{{ $rapor['data_mentah']['aktivitas_label'] }}</td>
                        <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">
                            {{ $rapor['skor_aktivitas'] !== null ? number_format((float) $rapor['skor_aktivitas'], 2) : 'Belum Ada Data' }}
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $rapor['bobot']['aktivitas'] }}%</td>
                        <td class="px-5 py-4 text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $rapor['bobot_efektif']['aktivitas'] }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">3. Evaluasi Peserta</td>
                        <td class="px-5 py-4 text-xs text-gray-700 dark:text-gray-300">{{ $rapor['data_mentah']['evaluasi_label'] }}</td>
                        <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">
                            {{ $rapor['skor_evaluasi'] !== null ? number_format((float) $rapor['skor_evaluasi'], 2) : 'Belum Ada Data' }}
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $rapor['bobot']['evaluasi'] }}%</td>
                        <td class="px-5 py-4 text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $rapor['bobot_efektif']['evaluasi'] }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">4. Observasi Supervisor</td>
                        <td class="px-5 py-4 text-xs text-gray-700 dark:text-gray-300">{{ $rapor['data_mentah']['observasi_label'] }}</td>
                        <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">
                            {{ $rapor['skor_observasi'] !== null ? number_format((float) $rapor['skor_observasi'], 2) : 'Belum Ada Data' }}
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $rapor['bobot']['observasi'] }}%</td>
                        <td class="px-5 py-4 text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $rapor['bobot_efektif']['observasi'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Catatan Observasi &amp; Rekomendasi Supervisor</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse ($observasis as $obs)
                <div class="space-y-2 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-gray-900 dark:text-white">
                            {{ $obs->tanggal->format('d/m/Y') }} - {{ $obs->supervisor?->name ?? 'Supervisor' }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">
                                Skala 5: {{ number_format((float) $obs->rata_rata_skala5, 2) }}
                            </span>
                            <span class="inline-flex items-center rounded bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                Skor: {{ number_format((float) $obs->skor_observasi, 2) }}
                            </span>
                        </div>
                    </div>
                    @if ($obs->catatan_observasi)
                        <p class="text-xs text-gray-700 dark:text-gray-300">
                            <strong>Catatan:</strong> {{ $obs->catatan_observasi }}
                        </p>
                    @endif
                    @if ($obs->rekomendasi)
                        <p class="text-xs text-gray-700 dark:text-gray-300">
                            <strong>Rekomendasi:</strong> {{ $obs->rekomendasi }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada catatan observasi pengajaran dari Supervisor pada periode ini.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
