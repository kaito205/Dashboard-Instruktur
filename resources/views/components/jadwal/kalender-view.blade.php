@props([
    'routePrefix' => 'admin.jadwal',
    'calendarEvents',
    'calAnchor',
    'calRangeStart',
    'calRangeEnd',
    'skalaKalender' => 'bulanan',
])

@php
    $skala = in_array($skalaKalender, ['harian', 'mingguan', 'bulanan'], true) ? $skalaKalender : 'bulanan';
    $eventsByDate = $calendarEvents ?? collect();

    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $bulanSingkat = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    $hariSingkatMap = [
        'Senin' => 'Sen',
        'Selasa' => 'Sel',
        'Rabu' => 'Rab',
        'Kamis' => 'Kam',
        'Jumat' => 'Jum',
        'Sabtu' => 'Sab',
        'Minggu' => 'Min',
    ];

    $anchor = ($calAnchor ?? today())->copy();
    $rangeStart = ($calRangeStart ?? $anchor->copy()->startOfMonth())->copy()->startOfDay();
    $rangeEnd = ($calRangeEnd ?? $anchor->copy()->endOfMonth())->copy()->endOfDay();

    if ($skala === 'harian') {
        $judulKalender = \App\Models\Jadwal::hariDariTanggal($anchor) . ', ' . $anchor->day . ' ' . $bulanIndo[(int) $anchor->month] . ' ' . $anchor->year;
        $prevTanggal = $anchor->copy()->subDay()->toDateString();
        $nextTanggal = $anchor->copy()->addDay()->toDateString();
        $labelPrev = 'Hari Sebelumnya';
        $labelNext = 'Hari Berikutnya';
    } elseif ($skala === 'mingguan') {
        if ($rangeStart->year === $rangeEnd->year && $rangeStart->month === $rangeEnd->month) {
            $judulKalender = $rangeStart->day . ' - ' . $rangeEnd->day . ' ' . $bulanIndo[(int) $rangeStart->month] . ' ' . $rangeStart->year;
        } elseif ($rangeStart->year === $rangeEnd->year) {
            $judulKalender = $rangeStart->day . ' ' . $bulanIndo[(int) $rangeStart->month] . ' - ' . $rangeEnd->day . ' ' . $bulanIndo[(int) $rangeEnd->month] . ' ' . $rangeStart->year;
        } else {
            $judulKalender = $rangeStart->day . ' ' . $bulanIndo[(int) $rangeStart->month] . ' ' . $rangeStart->year . ' - ' . $rangeEnd->day . ' ' . $bulanIndo[(int) $rangeEnd->month] . ' ' . $rangeEnd->year;
        }
        $prevTanggal = $anchor->copy()->subDays(7)->toDateString();
        $nextTanggal = $anchor->copy()->addDays(7)->toDateString();
        $labelPrev = 'Minggu Sebelumnya';
        $labelNext = 'Minggu Berikutnya';
    } else {
        $judulKalender = $bulanIndo[(int) $anchor->month] . ' ' . $anchor->year;
        $prevTanggal = $anchor->copy()->subMonthNoOverflow()->toDateString();
        $nextTanggal = $anchor->copy()->addMonthNoOverflow()->toDateString();
        $labelPrev = 'Bulan Sebelumnya';
        $labelNext = 'Bulan Berikutnya';
    }

    $todayStr = today()->toDateString();
    $baseFilterQuery = collect(request()->query())
        ->except(['page', 'dari', 'sampai'])
        ->toArray();

    $calUrl = fn (array $overrides = []) => route(
        $routePrefix . '.index',
        array_merge($baseFilterQuery, ['tampilan' => 'kalender', 'skala_kalender' => $skala], $overrides)
    );

    $datesInRange = [];
    $totalSesiTampil = 0;
    $dateCursor = $rangeStart->copy()->startOfDay();
    while ($dateCursor->lte($rangeEnd)) {
        $dStr = $dateCursor->toDateString();
        $datesInRange[] = $dateCursor->copy();
        $totalSesiTampil += $eventsByDate->get($dStr, collect())->count();
        $dateCursor->addDay();
    }

    $defaultSelectedDay = $anchor->toDateString();
    $activeHariFilter = trim((string) request()->input('hari', ''));
    $hasActiveFilter = request()->filled('search') || request()->filled('instruktur') || request()->filled('hari') || request()->filled('status');
    $resetHariUrl = route(
        $routePrefix . '.index',
        array_merge(
            collect($baseFilterQuery)->except(['hari'])->toArray(),
            ['tampilan' => 'kalender', 'skala_kalender' => $skala, 'tanggal_kalender' => $anchor->toDateString()]
        )
    );
    if ($skala !== 'harian' && $eventsByDate->get($defaultSelectedDay, collect())->isEmpty() && (!request()->filled('tanggal_kalender') || $hasActiveFilter)) {
        $foundWithEvents = false;
        foreach ($datesInRange as $candidateDate) {
            $candidateKey = $candidateDate->toDateString();
            if ($eventsByDate->get($candidateKey, collect())->isNotEmpty()) {
                $defaultSelectedDay = $candidateKey;
                $foundWithEvents = true;
                break;
            }
        }
        if (!$foundWithEvents && $activeHariFilter !== '') {
            foreach ($datesInRange as $candidateDate) {
                if (\App\Models\Jadwal::hariDariTanggal($candidateDate) === $activeHariFilter) {
                    $defaultSelectedDay = $candidateDate->toDateString();
                    break;
                }
            }
        }
    }

    $canManage = $routePrefix === 'admin.jadwal' && \Illuminate\Support\Facades\Route::has($routePrefix . '.create');

    $buildAlihkanData = function (\App\Models\Jadwal $ev) use ($routePrefix, $canManage): array {
        return [
            'url' => $canManage ? route($routePrefix . '.alihkan', $ev) : '',
            'judul' => $ev->nama_kelas . ($ev->materi ? ' - ' . $ev->materi : ''),
            'instruktur_id' => $ev->instruktur_id,
            'instruktur_nama' => $ev->instruktur->nama_lengkap ?? '-',
            'nama_kelas' => $ev->nama_kelas,
            'ruangan' => $ev->ruangan ?? '',
            'is_pola_mingguan' => (bool) $ev->is_pola_mingguan,
            'hari' => $ev->hari ?? 'Senin',
            'tanggal' => $ev->tanggal?->format('Y-m-d') ?? '',
            'tanggal_display' => $ev->tanggal_label,
            'jam_mulai' => substr((string) $ev->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $ev->jam_selesai, 0, 5),
        ];
    };
@endphp

<div data-live-filter-content
     x-data="{ selectedDay: '{{ $defaultSelectedDay }}' }"
     class="rounded-xl border border-gray-200 bg-white p-3.5 sm:p-5 dark:border-gray-800 dark:bg-gray-900">

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $judulKalender }}
            </h2>
            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                {{ $totalSesiTampil > 0 ? $totalSesiTampil . ' sesi terjadwal' : 'Belum ada sesi' }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-gray-300 bg-white p-0.5 dark:border-gray-700 dark:bg-gray-800">
                <a href="{{ $calUrl(['tanggal_kalender' => $prevTanggal]) }}"
                   aria-label="{{ $labelPrev }}"
                   title="{{ $labelPrev }}"
                   class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md px-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-200 dark:hover:bg-gray-700">
                    &lsaquo;
                </a>
                <a href="{{ $calUrl(['tanggal_kalender' => $todayStr]) }}"
                   class="inline-flex min-h-[44px] items-center rounded-md px-3 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $anchor->toDateString() === $todayStr ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    Hari Ini
                </a>
                <a href="{{ $calUrl(['tanggal_kalender' => $nextTanggal]) }}"
                   aria-label="{{ $labelNext }}"
                   title="{{ $labelNext }}"
                   class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md px-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-200 dark:hover:bg-gray-700">
                    &rsaquo;
                </a>
            </div>

            <div class="inline-flex rounded-lg border border-gray-300 bg-gray-50 p-0.5 dark:border-gray-700 dark:bg-gray-800">
                @foreach(['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan'] as $keySkala => $lblSkala)
                    <a href="{{ $calUrl(['skala_kalender' => $keySkala, 'tanggal_kalender' => $anchor->toDateString()]) }}"
                       class="inline-flex min-h-[44px] items-center rounded-md px-3 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $skala === $keySkala ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-200 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                        {{ $lblSkala }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="{{ $skala === 'bulanan' ? 'grid grid-cols-1 gap-5 xl:grid-cols-12' : 'space-y-5' }}">

        @if($skala === 'bulanan')
            @php
                $daysInMonth = $rangeStart->daysInMonth;
                $leadingBlanks = $rangeStart->dayOfWeekIso - 1;
                $trailingBlanks = (7 - (($leadingBlanks + $daysInMonth) % 7)) % 7;
            @endphp
            <div class="xl:col-span-8">
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                    <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-medium text-gray-600 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300">
                        <div class="py-2.5">Sen</div>
                        <div class="py-2.5">Sel</div>
                        <div class="py-2.5">Rab</div>
                        <div class="py-2.5">Kam</div>
                        <div class="py-2.5">Jum</div>
                        <div class="py-2.5">Sab</div>
                        <div class="py-2.5">Min</div>
                    </div>
                    <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-800">
                        @for($blank = 0; $blank < $leadingBlanks; $blank++)
                            <div class="min-h-[56px] bg-gray-50/70 sm:min-h-[96px] lg:min-h-[108px] dark:bg-gray-900/50"></div>
                        @endfor

                        @foreach($datesInRange as $cellDate)
                            @php
                                $dateKey = $cellDate->toDateString();
                                $dayEvents = $eventsByDate->get($dateKey, collect());
                                $isToday = $dateKey === $todayStr;
                            @endphp
                            <button type="button"
                                    @click="selectedDay = '{{ $dateKey }}'"
                                    :aria-pressed="selectedDay === '{{ $dateKey }}'"
                                    :class="selectedDay === '{{ $dateKey }}'
                                        ? 'bg-brand-50/70 ring-2 ring-inset ring-brand-600 dark:bg-brand-950/35 dark:ring-brand-400'
                                        : 'bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800/70'"
                                    class="flex min-h-[56px] w-full flex-col items-start justify-start p-1.5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600 sm:min-h-[96px] sm:p-2 lg:min-h-[108px]">
                                <div class="flex w-full items-center justify-between gap-1">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full font-mono text-xs font-semibold {{ $isToday ? 'bg-brand-600 text-white' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ $cellDate->day }}
                                    </span>
                                    @if($dayEvents->isNotEmpty())
                                        <span class="hidden text-[11px] font-medium text-gray-500 sm:inline dark:text-gray-400">
                                            {{ $dayEvents->count() }}
                                        </span>
                                    @endif
                                </div>

                                @if($dayEvents->isNotEmpty())
                                    <div class="mt-1 flex items-center gap-1 sm:hidden">
                                        <span class="h-1.5 w-1.5 rounded-full bg-brand-600 dark:bg-brand-300"></span>
                                        <span class="font-mono text-[10px] font-semibold text-brand-700 dark:text-brand-300">{{ $dayEvents->count() }}</span>
                                    </div>

                                    <div class="mt-1.5 hidden w-full flex-col gap-1 sm:flex">
                                        @foreach($dayEvents->take(2) as $ev)
                                            @php
                                                $riwayatList = is_array($ev->riwayat_pengalihan) ? $ev->riwayat_pengalihan : [];
                                                $isDialihkan = $ev->dialihkan_pada !== null || $riwayatList !== [] || $ev->instruktur_asli_id !== null;
                                                $chipClass = match (true) {
                                                    $ev->status === 'dibatalkan' => 'bg-red-50 text-red-800 line-through dark:bg-red-950/40 dark:text-red-300',
                                                    $isDialihkan => 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                                                };
                                            @endphp
                                            <span class="block w-full truncate rounded px-1.5 py-0.5 text-[11px] leading-tight {{ $chipClass }}">
                                                <span class="font-mono font-medium">{{ substr((string) $ev->jam_mulai, 0, 5) }}</span>
                                                {{ $ev->nama_kelas }}
                                            </span>
                                        @endforeach
                                        @if($dayEvents->count() > 2)
                                            <span class="px-1 text-[11px] font-medium text-brand-700 dark:text-brand-300">
                                                +{{ $dayEvents->count() - 2 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </button>
                        @endforeach

                        @for($blank = 0; $blank < $trailingBlanks; $blank++)
                            <div class="min-h-[56px] bg-gray-50/70 sm:min-h-[96px] lg:min-h-[108px] dark:bg-gray-900/50"></div>
                        @endfor
                    </div>
                </div>
            </div>
        @elseif($skala === 'mingguan')
            <div>
                <div class="grid grid-cols-7 gap-1 lg:hidden">
                    @foreach($datesInRange as $curDay)
                        @php
                            $dKey = $curDay->toDateString();
                            $dEvents = $eventsByDate->get($dKey, collect());
                            $isToday = $dKey === $todayStr;
                            $hariNama = \App\Models\Jadwal::hariDariTanggal($curDay);
                        @endphp
                        <button type="button"
                                @click="selectedDay = '{{ $dKey }}'"
                                :aria-pressed="selectedDay === '{{ $dKey }}'"
                                :class="selectedDay === '{{ $dKey }}'
                                    ? 'border-brand-600 bg-brand-50/80 ring-2 ring-brand-600 dark:border-brand-400 dark:bg-brand-950/40 dark:ring-brand-400'
                                    : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800/70'"
                                class="flex min-h-[60px] flex-col items-center justify-center rounded-lg border px-1 py-1.5 text-center transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            <span class="text-[11px] font-medium text-gray-600 dark:text-gray-300">{{ $hariSingkatMap[$hariNama] ?? $hariNama }}</span>
                            <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full font-mono text-xs font-bold {{ $isToday ? 'bg-brand-600 text-white' : 'text-gray-900 dark:text-white' }}">
                                {{ $curDay->day }}
                            </span>
                            @if($dEvents->isNotEmpty())
                                <span class="mt-0.5 font-mono text-[10px] font-semibold text-brand-700 dark:text-brand-300">{{ $dEvents->count() }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="hidden overflow-hidden rounded-xl border border-gray-200 lg:block dark:border-gray-800">
                    <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-800">
                        @foreach($datesInRange as $curDay)
                            @php
                                $dKey = $curDay->toDateString();
                                $dEvents = $eventsByDate->get($dKey, collect());
                                $isToday = $dKey === $todayStr;
                                $hariNama = \App\Models\Jadwal::hariDariTanggal($curDay);
                            @endphp
                            <div class="flex flex-col bg-white dark:bg-gray-900">
                                <button type="button"
                                        @click="selectedDay = '{{ $dKey }}'"
                                        :aria-pressed="selectedDay === '{{ $dKey }}'"
                                        :class="selectedDay === '{{ $dKey }}'
                                            ? 'bg-brand-50/70 dark:bg-brand-950/35'
                                            : 'bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/60 dark:hover:bg-gray-800'"
                                        class="flex min-h-[52px] w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500 dark:border-gray-800">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $hariNama }}</p>
                                        <p class="font-mono text-[11px] text-gray-600 dark:text-gray-300">
                                            {{ $curDay->day }} {{ $bulanSingkat[(int) $curDay->month] }}
                                        </p>
                                    </div>
                                    @if($isToday)
                                        <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold text-white">Hari ini</span>
                                    @elseif($dEvents->isNotEmpty())
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $dEvents->count() }}</span>
                                    @endif
                                </button>

                                <div class="min-h-[168px] flex-1 space-y-1.5 p-2">
                                    @forelse($dEvents as $ev)
                                        @php
                                            $riwayatList = is_array($ev->riwayat_pengalihan) ? $ev->riwayat_pengalihan : [];
                                            $isDialihkan = $ev->dialihkan_pada !== null || $riwayatList !== [] || $ev->instruktur_asli_id !== null;
                                        @endphp
                                        <button type="button"
                                                @click="selectedDay = '{{ $dKey }}'"
                                                class="flex min-h-[44px] w-full flex-col rounded-lg border border-gray-200 bg-gray-50/80 px-2.5 py-2 text-left transition hover:border-brand-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-800 dark:bg-gray-800/50 dark:hover:border-gray-700 dark:hover:bg-gray-800">
                                            <span class="font-mono text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                {{ substr((string) $ev->jam_mulai, 0, 5) }} - {{ substr((string) $ev->jam_selesai, 0, 5) }}
                                            </span>
                                            <span class="mt-0.5 block w-full truncate text-xs font-semibold text-gray-900 dark:text-white {{ $ev->status === 'dibatalkan' ? 'line-through text-red-700 dark:text-red-300' : '' }}">
                                                {{ $ev->nama_kelas }}
                                            </span>
                                            <span class="block w-full truncate text-[11px] text-gray-600 dark:text-gray-300">
                                                {{ $ev->ruangan ?: '-' }}@if($isDialihkan) &middot; Dialihkan @endif
                                            </span>
                                        </button>
                                    @empty
                                        <p class="px-1 py-2 text-xs text-gray-500 dark:text-gray-400">Tidak ada sesi</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $skala === 'bulanan' ? 'xl:col-span-4' : '' }}">
            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/30">
                @foreach($datesInRange as $detailDate)
                    @php
                        $detailKey = $detailDate->toDateString();
                        $detailEvents = $eventsByDate->get($detailKey, collect());
                        $detailHari = \App\Models\Jadwal::hariDariTanggal($detailDate);
                        $detailLabel = $detailHari . ', ' . $detailDate->day . ' ' . $bulanIndo[(int) $detailDate->month] . ' ' . $detailDate->year;
                    @endphp
                    <div x-show="selectedDay === '{{ $detailKey }}'"
                         @if($detailKey !== $defaultSelectedDay) x-cloak @endif>
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3 dark:border-gray-800">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $detailLabel }}
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $detailEvents->isNotEmpty() ? $detailEvents->count() . ' sesi terjadwal' : 'Tidak ada jadwal' }}
                                </p>
                            </div>
                            @if($skala !== 'harian')
                                <a href="{{ $calUrl(['skala_kalender' => 'harian', 'tanggal_kalender' => $detailKey]) }}"
                                   class="inline-flex min-h-[44px] items-center rounded-lg px-2.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300 dark:hover:bg-gray-800">
                                    Fokus Harian
                                </a>
                            @endif
                        </div>

                        @if($detailEvents->isNotEmpty())
                            <div class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
                                @foreach($detailEvents as $ev)
                                    @php
                                        $riwayatList = is_array($ev->riwayat_pengalihan) ? $ev->riwayat_pengalihan : [];
                                        $riwayatTerakhir = $riwayatList !== [] ? $riwayatList[array_key_last($riwayatList)] : null;
                                        $isDialihkan = $ev->dialihkan_pada !== null || $riwayatTerakhir !== null || $ev->instruktur_asli_id !== null;
                                        $statusTextClass = match ($ev->status) {
                                            'berlangsung' => 'text-emerald-700 dark:text-emerald-300',
                                            'dibatalkan' => 'text-red-700 dark:text-red-300',
                                            default => 'text-gray-600 dark:text-gray-300',
                                        };
                                    @endphp
                                    <div class="flex flex-col gap-3 p-3.5 {{ $skala === 'bulanan' ? '' : 'sm:flex-row sm:items-center sm:justify-between' }}">
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs">
                                                <span class="font-mono font-bold text-gray-900 dark:text-white">
                                                    {{ substr((string) $ev->jam_mulai, 0, 5) }} - {{ substr((string) $ev->jam_selesai, 0, 5) }} WIB
                                                </span>
                                                <span class="font-semibold {{ $statusTextClass }}">
                                                    {{ $ev->status_label }}
                                                </span>
                                                @if($isDialihkan)
                                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-800">
                                                        Dialihkan
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="text-sm font-semibold text-gray-900 dark:text-white {{ $ev->status === 'dibatalkan' ? 'line-through' : '' }}">
                                                {{ $ev->nama_kelas }}@if($ev->materi): {{ $ev->materi }}@endif
                                            </p>

                                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                                {{ $ev->instruktur->nama_lengkap ?? '-' }} &middot; {{ $ev->ruangan_label }}
                                            </p>

                                            @if($ev->instrukturAsli)
                                                <p class="text-[11px] text-amber-800 dark:text-amber-300">
                                                    Menggantikan {{ $ev->instrukturAsli->nama_lengkap }}
                                                </p>
                                            @endif

                                            @if($riwayatTerakhir)
                                                <p class="text-[11px] text-brand-700 dark:text-brand-300">
                                                    Dialihkan ({{ $riwayatTerakhir['waktu'] ?? '-' }}): {{ implode(', ', $riwayatTerakhir['perubahan'] ?? []) }}
                                                </p>
                                            @endif
                                        </div>

                                        @if($canManage)
                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <button type="button"
                                                    data-alihkan="{{ json_encode($buildAlihkanData($ev)) }}"
                                                    @click="openAlihkan(JSON.parse($el.dataset.alihkan))"
                                                    class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Alihkan
                                            </button>
                                            <a href="{{ route($routePrefix . '.edit', $ev) }}"
                                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-800 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Edit
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-5 text-center dark:border-gray-700 dark:bg-gray-900">
                                @if($activeHariFilter !== '' && $detailHari !== $activeHariFilter)
                                    <p class="text-xs font-medium leading-relaxed text-gray-700 dark:text-gray-300">
                                        Filter Hari: <span class="font-semibold text-gray-900 dark:text-white">{{ $activeHariFilter }}</span> sedang aktif. Ubah filter ke Semua Hari atau pilih tanggal di hari {{ $activeHariFilter }} untuk melihat jadwal.
                                    </p>
                                    <a href="{{ $resetHariUrl }}"
                                       class="mt-3 inline-flex min-h-[38px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Reset Filter Hari
                                    </a>
                                @else
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Tidak ada jadwal mengajar pada {{ $detailLabel }}.
                                    </p>
                                    @if($canManage)
                                    <a href="{{ route($routePrefix . '.create') }}"
                                       class="mt-3 inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-800 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Tambah Jadwal
                                    </a>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
