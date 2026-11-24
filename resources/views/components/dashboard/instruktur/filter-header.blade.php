@props(['selectedPeriod' => 'bulan_ini', 'selectedStatus' => 'all', 'dari' => null, 'sampai' => null])
@php
    $currentUser = auth()->user();
    $profilInstruktur = $currentUser?->instruktur;
    $namaTampil = $profilInstruktur?->nama_lengkap ?? $currentUser?->name ?? 'Instruktur';

    $rangeTeks = ($dari && $sampai)
        ? \Carbon\Carbon::parse($dari, 'Asia/Jakarta')->translatedFormat('d M Y') . ' s.d. ' . \Carbon\Carbon::parse($sampai, 'Asia/Jakarta')->translatedFormat('d M Y')
        : match ($selectedPeriod) {
            'hari_ini'   => 'Hari Ini',
            'minggu_ini' => 'Minggu Ini',
            'tahun_ini'  => 'Tahun Ini',
            default      => 'Bulan Ini',
        };

    $hasCustomFilter = ($selectedPeriod !== 'bulan_ini') || ($selectedStatus !== 'all');
@endphp

<div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Dashboard
        </h1>
        <p class="mt-0.5 truncate text-xs text-gray-600 dark:text-gray-300">
            {{ $namaTampil }} &middot; Periode: <span class="font-medium text-gray-900 dark:text-white">{{ $rangeTeks }}</span>
        </p>
    </div>

    <div class="flex flex-wrap items-end gap-2.5">
        <form method="GET" action="{{ route('instruktur.dashboard') }}" class="flex flex-wrap items-end gap-2">
            @if (request()->filled('search'))
                <input type="hidden" name="search" value="{{ request('search') }}" />
            @endif

            <div class="w-full sm:w-auto">
                <label for="instruktur-filter-periode" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Periode</label>
                <select id="instruktur-filter-periode" name="periode"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="hari_ini" {{ $selectedPeriod === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="minggu_ini" {{ $selectedPeriod === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="bulan_ini" {{ $selectedPeriod === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="tahun_ini" {{ $selectedPeriod === 'tahun_ini' ? 'selected' : '' }}>Tahun Ini</option>
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="instruktur-filter-status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Kehadiran</label>
                <select id="instruktur-filter-status" name="status"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="hadir" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ $selectedStatus === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="tidak_hadir" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir (Alpa)</option>
                </select>
            </div>

            <button type="submit"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Filter
            </button>

            @if ($hasCustomFilter)
                <a href="{{ route('instruktur.dashboard', request()->filled('search') ? ['search' => request('search')] : []) }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    Reset
                </a>
            @endif
        </form>

        <a
            href="{{ route('instruktur.jadwal.index') }}"
            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Jadwal &amp; Check-in
        </a>
        <a
            href="{{ route('instruktur.rapor.index') }}"
            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Rapor Kinerja
        </a>
    </div>
</div>
