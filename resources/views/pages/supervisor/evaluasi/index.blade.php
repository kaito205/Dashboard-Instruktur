@extends('layouts.supervisor.app')
@section('title', 'Hasil Evaluasi Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Hasil Evaluasi Instruktur</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pantau rata-rata nilai evaluasi setiap instruktur, grafik hasil evaluasi, dan daftar evaluasi peserta</p>
    </div>
    <a href="{{ route('supervisor.evaluasi.kode.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        Lihat Kode Akses Evaluasi
    </a>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-300">Total Responden Evaluasi</p>
        <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($evaluasis->total()) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-300">Rata-rata Nilai Keseluruhan</p>
        <p class="mt-1 font-mono text-2xl font-bold text-brand-700 dark:text-brand-400">{{ $rataRataTotal !== null ? number_format((float) $rataRataTotal, 2) . ' / 100' : '-' }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-300">Instruktur Terevaluasi</p>
        <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ ($rekapPerInstruktur ?? collect())->where('jumlah_evaluasi', '>', 0)->count() }} / {{ ($rekapPerInstruktur ?? collect())->count() }}</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-5">
        <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Grafik Rata-rata Skor per Instruktur</h2>
        <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">Perbandingan nilai rata-rata evaluasi pada periode terpilih (skala 0-100)</p>
        @if(!empty($chartEvaluasi['labels']))
            <div id="chart-evaluasi-detail" class="min-h-[240px]"></div>
        @else
            <div class="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-800">
                <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada data evaluasi untuk divisualisasikan pada rentang tanggal ini.</p>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-7">
        <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Ringkasan Nilai Evaluasi per Instruktur</h2>
        <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">Perbandingan jumlah responden, nilai terendah, tertinggi, dan rata-rata tiap instruktur</p>
        <div class="max-h-[260px] overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="pb-2 font-medium">Instruktur</th>
                        <th class="pb-2 font-medium">Responden</th>
                        <th class="pb-2 font-medium">Terendah</th>
                        <th class="pb-2 font-medium">Tertinggi</th>
                        <th class="pb-2 text-right font-medium">Rata-rata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($rekapPerInstruktur ?? [] as $insRekap)
                    <tr>
                        <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $insRekap['instruktur']->nama_lengkap }} ({{ $insRekap['instruktur']->kode_instruktur }})</td>
                        <td class="py-2 font-mono text-gray-700 dark:text-gray-300">{{ $insRekap['jumlah_evaluasi'] }}</td>
                        <td class="py-2 font-mono text-gray-600 dark:text-gray-300">{{ $insRekap['nilai_terendah'] !== null ? number_format($insRekap['nilai_terendah'], 1) : '-' }}</td>
                        <td class="py-2 font-mono text-gray-600 dark:text-gray-300">{{ $insRekap['nilai_tertinggi'] !== null ? number_format($insRekap['nilai_tertinggi'], 1) : '-' }}</td>
                        <td class="py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">
                            {{ $insRekap['rata_rata'] !== null ? number_format((float) $insRekap['rata_rata'], 2) : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-600 dark:text-gray-300">Belum ada data instruktur.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('supervisor.evaluasi.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur</option>
                @foreach($instrukturs as $ins)
                <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari ?? request('dari') }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div>
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai ?? request('sampai') }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
        <a href="{{ route('supervisor.evaluasi.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Instruktur</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Nama Pengisi</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Total Nilai</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kategori</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kode / Sumber</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($evaluasis as $ev)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $ev->instruktur->nama_lengkap ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ev->nama_pengisi ?: 'Anonim' }}</td>
                    <td class="px-4 py-3">
                        <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $ev->total_nilai !== null ? number_format((float) $ev->total_nilai, 2) : '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ev->nilai_label }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                        {{ $ev->kodeEvaluasi?->kode ?? ($ev->batch_import ?: 'Web') }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $ev->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada data evaluasi pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($evaluasis->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $evaluasis->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
@if(!empty($chartEvaluasi['labels']))
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const evalEl = document.getElementById('chart-evaluasi-detail');
    if (evalEl && typeof ApexCharts !== 'undefined') {
        const chart = new ApexCharts(evalEl, {
            chart: { type: 'bar', height: 240, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Rata-rata Nilai', data: @json($chartEvaluasi['values'] ?? []) }],
            xaxis: { categories: @json($chartEvaluasi['labels'] ?? []) },
            yaxis: { min: 0, max: 100 },
            colors: ['#033DF6'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
            dataLabels: { enabled: true, formatter: (val) => Number(val).toFixed(1) },
        });
        chart.render();
    }
});
</script>
@endif
@endpush
