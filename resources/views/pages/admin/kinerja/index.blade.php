@extends('layouts.admin.app')
@section('title', 'Monitoring Kinerja Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Kinerja Instruktur</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.observasi.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Observasi
        </a>
        <a href="{{ route('admin.pengaturan.index') }}#pengaturan-kinerja" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Parameter Kinerja
        </a>
        <a href="{{ route('admin.laporan.export', array_filter(['jenis' => 'kinerja', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId, 'status' => $kategoriFilter], fn ($val) => (string) $val !== '')) }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Ekspor CSV Kinerja
        </a>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 lg:grid-cols-4 dark:border-gray-800 dark:bg-gray-800">
    <div class="bg-white p-4 dark:bg-gray-900" title="Rentang skor: {{ $kategoriRanges['sangat_baik'] ?? 'Sangat Baik (>= 85)' }}">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Sangat Baik</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $distribusiKategori['sangat_baik'] ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900" title="Rentang skor: {{ $kategoriRanges['baik'] ?? 'Baik (75 - < 85)' }}">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Baik</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-brand-600 dark:text-brand-300">{{ $distribusiKategori['baik'] ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900" title="Rentang skor: {{ $kategoriRanges['cukup'] ?? 'Cukup (60 - < 75)' }}">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Cukup</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $distribusiKategori['cukup'] ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900" title="Rentang skor: {{ $kategoriRanges['kurang'] ?? 'Kurang (< 60)' }}">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Kurang</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-red-700 dark:text-red-300">{{ $distribusiKategori['kurang'] ?? 0 }}</p>
    </div>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('admin.kinerja.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-auto">
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div class="w-full sm:w-auto">
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div class="min-w-[200px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur Aktif</option>
                @foreach($instrukturs as $ins)
                    <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="kategori" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kategori</label>
            <select id="kategori" name="kategori" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Kategori</option>
                <option value="sangat_baik" {{ $kategoriFilter === 'sangat_baik' ? 'selected' : '' }}>Sangat Baik</option>
                <option value="baik" {{ $kategoriFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                <option value="cukup" {{ $kategoriFilter === 'cukup' ? 'selected' : '' }}>Cukup</option>
                <option value="kurang" {{ $kategoriFilter === 'kurang' ? 'selected' : '' }}>Kurang</option>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        <a href="{{ route('admin.kinerja.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

<x-kinerja.monitoring-table
    :data-kinerja="$dataKinerja"
    :dari="$dari"
    :sampai="$sampai"
    :dari-lalu="$dariLalu ?? null"
    :sampai-lalu="$sampaiLalu ?? null"
    route-prefix="admin"
/>
@endsection
