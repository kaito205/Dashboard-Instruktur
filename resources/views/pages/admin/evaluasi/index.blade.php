@extends('layouts.admin.app')
@section('title', 'Hasil Evaluasi Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Evaluasi Instruktur</h1>
    <a href="{{ route('admin.evaluasi.kode.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        Kelola Kode Evaluasi
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('admin.evaluasi.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur</option>
                @foreach($instrukturs as $ins)
                <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari ?? request('dari') }}" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div class="w-full sm:w-auto">
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai ?? request('sampai') }}" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        <a href="{{ route('admin.evaluasi.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

<div class="mb-6 grid grid-cols-1 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-3 dark:border-gray-800 dark:bg-gray-800">
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Responden</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($evaluasis->total()) }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Rata-rata Nilai</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-brand-600 dark:text-brand-300">{{ $rataRataTotal !== null ? number_format((float) $rataRataTotal, 2) . ' / 100' : '-' }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Instruktur Terevaluasi</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-gray-900 dark:text-white">{{ ($rekapPerInstruktur ?? collect())->where('jumlah_evaluasi', '>', 0)->count() }} / {{ ($rekapPerInstruktur ?? collect())->count() }}</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-5">
        <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Skor per Instruktur</h2>
        @if(!empty($chartEvaluasi['labels']))
            <div id="chart-evaluasi-detail" class="min-h-[240px]"></div>
        @else
            <div class="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-800">
                <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada data evaluasi pada rentang tanggal ini.</p>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-7">
        <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Ringkasan per Instruktur</h2>
        <div class="max-h-[260px] overflow-x-auto overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="pb-2 pr-3 font-semibold">Instruktur</th>
                        <th class="pb-2 pr-3 font-semibold">Responden</th>
                        <th class="pb-2 pr-3 font-semibold">Terendah</th>
                        <th class="pb-2 pr-3 font-semibold">Tertinggi</th>
                        <th class="pb-2 text-right font-semibold">Rata-rata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($rekapPerInstruktur ?? [] as $insRekap)
                    <tr>
                        <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">{{ $insRekap['instruktur']->nama_lengkap }} ({{ $insRekap['instruktur']->kode_instruktur }})</td>
                        <td class="py-2 pr-3 font-mono text-gray-700 dark:text-gray-300">{{ $insRekap['jumlah_evaluasi'] }}</td>
                        <td class="py-2 pr-3 font-mono text-gray-600 dark:text-gray-300">{{ $insRekap['nilai_terendah'] !== null ? number_format($insRekap['nilai_terendah'], 1) : '-' }}</td>
                        <td class="py-2 pr-3 font-mono text-gray-600 dark:text-gray-300">{{ $insRekap['nilai_tertinggi'] !== null ? number_format($insRekap['nilai_tertinggi'], 1) : '-' }}</td>
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

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-4 py-3">Instruktur</th>
                    <th class="px-4 py-3">Pengisi</th>
                    <th class="px-4 py-3">Nilai</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Kode / Sumber</th>
                    <th class="px-4 py-3">Tanggal</th>
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
        const isDark = document.documentElement.classList.contains('dark');
        const labelColor = isDark ? '#D0D5DD' : '#475467';
        const gridColor = isDark ? '#1D2939' : '#EAECF0';
        const chart = new ApexCharts(evalEl, {
            chart: { type: 'bar', height: 240, toolbar: { show: false }, fontFamily: 'inherit', foreColor: labelColor },
            series: [{ name: 'Rata-rata Nilai', data: @json($chartEvaluasi['values'] ?? []) }],
            xaxis: { categories: @json($chartEvaluasi['labels'] ?? []), labels: { style: { colors: labelColor } } },
            yaxis: { min: 0, max: 100, labels: { style: { colors: labelColor } } },
            grid: { borderColor: gridColor },
            colors: ['#033DF6'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
            dataLabels: { enabled: true, style: { colors: [isDark ? '#F9FAFB' : '#101828'] }, formatter: (val) => Number(val).toFixed(1) },
            tooltip: { theme: isDark ? 'dark' : 'light' }
        });
        chart.render();
    }
});
</script>
@endif
@endpush
