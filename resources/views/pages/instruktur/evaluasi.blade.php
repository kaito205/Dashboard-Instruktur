@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Hasil Evaluasi Pengajaran</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Umpan balik peserta secara anonim (skala 1-5 dan skor konversi 0-100).
            </p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('instruktur.evaluasi.index') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="eval_dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari</label>
                    <input type="date" id="eval_dari" name="dari" value="{{ $dari ?? '' }}"
                        class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label for="eval_sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                    <input type="date" id="eval_sampai" name="sampai" value="{{ $sampai ?? '' }}"
                        class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <button type="submit"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Terapkan
                </button>
                @if (($dari ?? '') !== '' || ($sampai ?? '') !== '')
                    <a href="{{ route('instruktur.evaluasi.index') }}"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Reset
                    </a>
                @endif
            </form>

            @php
                $hasEvalFilter = ($dari ?? '') !== '' || ($sampai ?? '') !== '';
                $evalPeriodeLabel = $hasEvalFilter
                    ? (($dari !== '' ? \Carbon\Carbon::parse($dari)->format('d/m/Y') : 'Awal') . ' s.d. ' . ($sampai !== '' ? \Carbon\Carbon::parse($sampai)->format('d/m/Y') : 'Sekarang'))
                    : 'Semua periode';
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-right dark:border-gray-800 dark:bg-gray-900">
                <span class="block text-xs text-gray-500 dark:text-gray-400">Rata-rata Nilai ({{ $totalResponden ?? $evaluasis->total() }} responden &middot; {{ $evalPeriodeLabel }})</span>
                <span class="font-mono text-lg font-bold text-emerald-700 dark:text-emerald-300">
                    {{ $rataNilai100 !== null ? number_format((float) $rataNilai100, 2) . ' / 100' : 'Belum Ada Data' }}
                </span>
            </div>
        </div>
    </div>

    @if (!empty($rataPerIndikator))
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Rata-rata Nilai per Indikator (Skala 1-5)</h2>
            <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($rataPerIndikator as $indKey => $ind)
                    <div class="flex items-center justify-between gap-4 py-2.5">
                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $ind['label'] }}</span>
                        <span class="shrink-0 font-mono text-xs font-bold text-gray-900 dark:text-white">
                            {{ $ind['avg'] !== null ? number_format((float) $ind['avg'], 2) . ' / 5.00' : '-' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Daftar Evaluasi Peserta ({{ $instruktur->nama_lengkap }})
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Kelas &amp; Materi</th>
                        <th class="px-5 py-3">Pengisi</th>
                        <th class="px-5 py-3">Skala 1-5</th>
                        <th class="px-5 py-3">Skor (0-100)</th>
                        <th class="px-5 py-3">Komentar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($evaluasis as $ev)
                        @php $skorMap = $ev->skor_indikator; @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $ev->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5">
                                @php
                                    $evKode = $ev->kodeEvaluasi?->kode;
                                    $evNamaKelas = $ev->kodeEvaluasi?->nama_kelas ?? 'Evaluasi Kelas';
                                    $showEvKode = $evKode && mb_strtolower(trim($evKode)) !== mb_strtolower(trim($evNamaKelas));
                                @endphp
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $evNamaKelas }}
                                    </p>
                                    @if ($showEvKode)
                                        <span class="inline-flex items-center rounded bg-brand-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">
                                            {{ $evKode }}
                                        </span>
                                    @endif
                                </div>
                                @if ($ev->materi_evaluasi)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $ev->materi_evaluasi }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                Peserta Anonim
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-xs font-bold text-gray-900 dark:text-white">
                                    {{ $ev->rata_rata_skala5 !== null ? number_format($ev->rata_rata_skala5, 2) . ' / 5.00' : '-' }}
                                </p>
                                @if (!empty($skorMap))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($skorMap as $k => $v)
                                            <span title="{{ $indikatorLabels[$k] ?? $k }}" class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                I{{ $loop->iteration }}:{{ $v }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if ($ev->total_nilai === null)
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        -
                                    </span>
                                @else
                                    @php
                                        $skorBadgeCls = match ($ev->nilai_color) {
                                            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                            'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                            'amber'   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                            default   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $skorBadgeCls }}">
                                        {{ number_format((float) $ev->total_nilai, 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="max-w-xs px-5 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                {{ $ev->komentar_peserta ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($hasEvalFilter)
                                    Tidak ada evaluasi peserta yang masuk pada rentang tanggal yang dipilih.
                                @else
                                    Belum ada evaluasi peserta yang masuk untuk akun instruktur Anda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($evaluasis->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $evaluasis->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
