@props(['chartData' => null])
@php
    $trend = $chartData['trend'] ?? [
        'subtitle'       => 'Rekap Hadir, Terlambat, Izin, Alpa, dan Jurnal pada periode aktif',
        'labels'         => [],
        'tooltip_labels' => [],
        'series'         => [],
    ];
    $komponen = $chartData['komponen'] ?? [
        'labels'         => ['Kehadiran', 'Aktivitas', 'Evaluasi', 'Observasi'],
        'values'         => [0, 0, 0, 0],
        'available'      => [false, false, false, false],
        'skor_akhir'     => null,
        'kategori_label' => null,
    ];

    $trendSeriesList = $trend['series'] ?? [];
    $hasTrendData = count($trend['labels'] ?? []) > 0
        && collect($trendSeriesList)->contains(fn ($s) => array_sum(array_map('intval', $s['data'] ?? [])) > 0);

    $hasKomponenData = in_array(true, $komponen['available'] ?? [], true);
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
    {{-- Grafik Kiri (7 kolom): Tren Kehadiran & Jurnal --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 lg:col-span-7 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    Tren Kehadiran &amp; Jurnal
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    {{ $trend['subtitle'] }}
                </p>
            </div>
            <a href="{{ route('instruktur.jadwal.index') }}"
                class="inline-flex min-h-[44px] shrink-0 items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                Lihat Jadwal
            </a>
        </div>

        <div class="flex h-64 items-center justify-center">
            @if ($hasTrendData)
                <div id="instrukturTrendChart" class="h-full w-full"></div>
            @else
                <div class="px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Belum ada catatan kehadiran atau jurnal terkonfirmasi pada periode ini.
                    </p>
                    <a href="{{ route('instruktur.jadwal.index') }}"
                        class="mt-3 inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Buka Jadwal &amp; Presensi
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Grafik Kanan (5 kolom): Skor 4 Komponen Kinerja --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 lg:col-span-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    Skor 4 Komponen Kinerja
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    @if (($komponen['skor_akhir'] ?? null) !== null)
                        Skor Akhir: <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ number_format((float) $komponen['skor_akhir'], 2) }}</span> ({{ $komponen['kategori_label'] }})
                    @else
                        Skala 0-100 pada periode aktif
                    @endif
                </p>
            </div>
            <a href="{{ route('instruktur.rapor.index') }}"
                class="inline-flex min-h-[44px] shrink-0 items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                Rapor Kinerja
            </a>
        </div>

        <div class="flex h-64 items-center justify-center">
            @if ($hasKomponenData)
                <div id="instrukturKomponenChart" class="h-full w-full"></div>
            @else
                <div class="px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Belum ada data komponen kinerja pada periode ini.
                    </p>
                    <a href="{{ route('instruktur.rapor.index') }}"
                        class="mt-3 inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Lihat Rapor Kinerja
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@if ($hasTrendData || $hasKomponenData)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const labelColor = isDark ? '#98a2b3' : '#475467';
    const gridColor = isDark ? '#1d2939' : '#e4e7ec';

    @if ($hasTrendData)
    const trendEl = document.getElementById('instrukturTrendChart');
    if (trendEl) {
        const trendLabels = @json($trend['labels'] ?? []);
        const trendTooltipLabels = @json($trend['tooltip_labels'] ?? []);
        const trendStacked = Boolean(@json($trend['stacked'] ?? true));
        const trendSeriesList = @json($trendSeriesList);
        const trendJurnal = @json($trend['jurnal'] ?? []);
        const trendKey = JSON.stringify({ isDark, trendStacked, trendLabels, trendSeriesList });

        if (!(trendEl.dataset.renderedKey === trendKey && trendEl.childElementCount > 0)) {
            if (window.__instrukturTrendChartInstance && typeof window.__instrukturTrendChartInstance.destroy === 'function') {
                try { window.__instrukturTrendChartInstance.destroy(); } catch (e) {}
                window.__instrukturTrendChartInstance = null;
            }
            trendEl.innerHTML = '';
            trendEl.dataset.renderedKey = trendKey;

            const bucketTotals = trendLabels.map((_, i) =>
                trendStacked
                    ? trendSeriesList.reduce((acc, s) => acc + Number((s.data || [])[i] || 0), 0)
                    : Math.max(...trendSeriesList.map((s) => Number((s.data || [])[i] || 0)), 0)
            );
            const maxTrend = Math.max(...bucketTotals, 1);

            window.__instrukturTrendChartInstance = new ApexCharts(trendEl, {
                series: trendSeriesList.map((s) => ({ name: s.name, data: s.data || [] })),
                colors: trendSeriesList.map((s) => s.color || '#033DF6'),
                chart: {
                    type: 'bar',
                    stacked: trendStacked,
                    height: 248,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit',
                    foreColor: labelColor
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        borderRadiusApplication: 'end',
                        columnWidth: trendLabels.length <= 7 ? '44%' : '56%'
                    }
                },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: { top: 8, right: 16, bottom: 10, left: 12 }
                },
                xaxis: {
                    categories: trendLabels,
                    axisBorder: { show: true, color: gridColor },
                    axisTicks: { show: false },
                    labels: {
                        rotate: 0,
                        hideOverlappingLabels: false,
                        trim: false,
                        style: { colors: labelColor, fontSize: '11px' }
                    }
                },
                yaxis: {
                    min: 0,
                    max: maxTrend <= 5 ? maxTrend + 1 : undefined,
                    tickAmount: maxTrend <= 5 ? maxTrend + 1 : 4,
                    forceNiceScale: true,
                    decimalsInFloat: 0,
                    labels: {
                        style: { colors: labelColor, fontSize: '11px' },
                        formatter: (val) => Number.isFinite(Number(val)) ? String(Math.round(Number(val))) : ''
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    offsetY: -4,
                    itemMargin: { horizontal: 12, vertical: 4 },
                    labels: { colors: labelColor }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    x: {
                        formatter: function (val, opts) {
                            const idx = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : -1;
                            return (idx >= 0 && trendTooltipLabels[idx]) ? trendTooltipLabels[idx] : val;
                        }
                    },
                    y: {
                        formatter: (val) => Math.round(Number(val || 0)) + ' sesi'
                    }
                }
            });
            window.__instrukturTrendChartInstance.render();
        }
    }
    @endif

    @if ($hasKomponenData)
    const kompEl = document.getElementById('instrukturKomponenChart');
    if (kompEl) {
        const kompLabels = @json($komponen['labels'] ?? []);
        const kompValues = @json($komponen['values'] ?? []);
        const kompAvailable = @json($komponen['available'] ?? []);
        const kompKey = JSON.stringify({ isDark, kompLabels, kompValues, kompAvailable });

        if (!(kompEl.dataset.renderedKey === kompKey && kompEl.childElementCount > 0)) {
            if (window.__instrukturKomponenChartInstance && typeof window.__instrukturKomponenChartInstance.destroy === 'function') {
                try { window.__instrukturKomponenChartInstance.destroy(); } catch (e) {}
                window.__instrukturKomponenChartInstance = null;
            }
            kompEl.innerHTML = '';
            kompEl.dataset.renderedKey = kompKey;

            window.__instrukturKomponenChartInstance = new ApexCharts(kompEl, {
                series: [
                    { name: 'Skor Komponen', data: kompValues }
                ],
                colors: ['#033DF6'],
                chart: {
                    type: 'bar',
                    height: 248,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    foreColor: labelColor
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 5,
                        barHeight: '48%'
                    }
                },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    padding: { top: 4, right: 16, bottom: 4, left: 6 }
                },
                xaxis: {
                    categories: kompLabels,
                    min: 0,
                    max: 100,
                    tickAmount: 5,
                    labels: {
                        style: { colors: labelColor, fontSize: '11px' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: labelColor, fontSize: '12px' }
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: function (val, opts) {
                            const idx = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : -1;
                            if (idx >= 0 && kompAvailable[idx] === false) {
                                return 'Belum ada data';
                            }
                            return Number(val || 0).toFixed(1) + ' / 100';
                        }
                    }
                }
            });
            window.__instrukturKomponenChartInstance.render();
        }
    }
    @endif
});
</script>
@endpush
@endif
