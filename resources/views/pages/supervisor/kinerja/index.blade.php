@extends('layouts.supervisor.app')
@section('title', 'Monitoring Kinerja Instruktur')

@section('content')
@php
    $fmt = static fn (float $v): string => \App\Services\KinerjaCalculationService::formatAngka($v);
@endphp
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Monitoring &amp; Komparasi Kinerja Instruktur (4 Komponen)</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Menampilkan data asli (mentah) berdampingan dengan skor kalkulasi (0-100) untuk Kehadiran ({{ $fmt((float) ($ruleConfig['bobot_kehadiran'] ?? 30)) }}%), Aktivitas ({{ $fmt((float) ($ruleConfig['bobot_aktivitas'] ?? 20)) }}%), Evaluasi Peserta ({{ $fmt((float) ($ruleConfig['bobot_evaluasi'] ?? 30)) }}%), dan Observasi Supervisor ({{ $fmt((float) ($ruleConfig['bobot_observasi'] ?? 20)) }}%)</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('supervisor.observasi.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-brand-600 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100 dark:border-brand-500 dark:bg-brand-950/40 dark:text-brand-300">
            Input Observasi Kelas
        </a>
        <a href="{{ route('supervisor.laporan.export', array_filter(['jenis' => 'kinerja', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId, 'status' => $kategoriFilter], fn ($val) => (string) $val !== '')) }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Ekspor CSV Kinerja
        </a>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $kategoriRanges['sangat_baik'] ?? 'Sangat Baik (>= 85)' }}</p>
        <p class="mt-1 font-mono text-2xl font-bold text-green-700 dark:text-green-400">{{ $distribusiKategori['sangat_baik'] ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $kategoriRanges['baik'] ?? 'Baik (75 - < 85)' }}</p>
        <p class="mt-1 font-mono text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $distribusiKategori['baik'] ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $kategoriRanges['cukup'] ?? 'Cukup (60 - < 75)' }}</p>
        <p class="mt-1 font-mono text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $distribusiKategori['cukup'] ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $kategoriRanges['kurang'] ?? 'Kurang (< 60)' }}</p>
        <p class="mt-1 font-mono text-2xl font-bold text-red-700 dark:text-red-400">{{ $distribusiKategori['kurang'] ?? 0 }}</p>
    </div>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('supervisor.kinerja.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div>
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div class="min-w-[200px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur Aktif</option>
                @foreach($instrukturs as $ins)
                    <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kategori" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kategori Kinerja</label>
            <select id="kategori" name="kategori" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Kategori</option>
                <option value="sangat_baik" {{ $kategoriFilter === 'sangat_baik' ? 'selected' : '' }}>Sangat Baik</option>
                <option value="baik" {{ $kategoriFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                <option value="cukup" {{ $kategoriFilter === 'cukup' ? 'selected' : '' }}>Cukup</option>
                <option value="kurang" {{ $kategoriFilter === 'kurang' ? 'selected' : '' }}>Kurang</option>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Terapkan</button>
        <a href="{{ route('supervisor.kinerja.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

<x-kinerja.monitoring-table
    :data-kinerja="$dataKinerja"
    :dari="$dari"
    :sampai="$sampai"
    :dari-lalu="$dariLalu ?? null"
    :sampai-lalu="$sampaiLalu ?? null"
    route-prefix="supervisor"
/>
@endsection
