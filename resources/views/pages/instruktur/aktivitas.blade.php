@extends('layouts.app')

@section('content')
@php
    $stats = $statsAktivitas ?? [
        'total_laporan'         => 0,
        'diverifikasi'          => 0,
        'menunggu'              => 0,
        'perlu_revisi'          => 0,
        'terlaksana'            => 0,
        'tidak_terlaksana'      => 0,
        'persen_kepatuhan'      => 100.0,
        'kepatuhan_color'       => 'emerald',
        'kepatuhan_label'       => 'Patuh',
        'sesi_terlapor_periode' => 0,
        'total_sesi_periode'    => 0,
        'eskalasi_count'        => 0,
    ];
    $kepatuhanTextCls = match ($stats['kepatuhan_color'] ?? 'emerald') {
        'red'   => 'text-red-700 dark:text-red-400',
        'amber' => 'text-amber-700 dark:text-amber-400',
        default => 'text-emerald-700 dark:text-emerald-400',
    };
    $kepatuhanBadgeCls = match ($stats['kepatuhan_color'] ?? 'emerald') {
        'red'   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
        'amber' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
    };
    $kepatuhanBarCls = match ($stats['kepatuhan_color'] ?? 'emerald') {
        'red'   => 'bg-red-600 dark:bg-red-500',
        'amber' => 'bg-amber-500 dark:bg-amber-400',
        default => 'bg-emerald-600 dark:bg-emerald-500',
    };
    $hasActiveFilter = ($dari ?? '') !== '' || ($sampai ?? '') !== '' || ($jadwalFilter ?? '') !== '' || ($sort ?? 'terbaru') !== 'terbaru';
@endphp
<div class="space-y-8"
    x-data="aktivitasPage(@js($jadwalOptionsJson ?? []))"
    @keydown.escape.window="closeModal()">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Aktivitas Mengajar</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Catat realisasi materi, jam aktual, peserta hadir, kendala, dan dokumentasi pembelajaran per sesi.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($jadwals->isNotEmpty())
                <button type="button" @click="openNew()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    + Catat Jurnal Baru
                </button>
            @endif

            {{-- Dropdown Export Gabungan --}}
            <div x-data="{ exportOpen: false }" class="relative" @click.outside="exportOpen = false">
                <button type="button" @click="exportOpen = !exportOpen"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export</span>
                    <svg class="h-3.5 w-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': exportOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="exportOpen" x-cloak
                    class="absolute right-0 z-30 mt-1.5 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <a href="{{ route('instruktur.aktivitas.export.excel', request()->query()) }}"
                        class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                        <span>Export Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('instruktur.aktivitas.export.pdf', request()->query()) }}" target="_blank" rel="noopener"
                        class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                        <span>Export PDF / Cetak</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('instruktur.jadwal.index') }}"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Jadwal &amp; Presensi
            </a>
        </div>
    </div>

    {{-- Kartu Statistik Ringkas (Tinggi Sejajar & Angka Netral) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kepatuhan Pelaporan</p>
                    <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title="Hanya menghitung jurnal yang cocok dengan sesi terjadwal pada periode aktif ({{ \Carbon\Carbon::parse($occDari)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($occSampai)->format('d/m/Y') }}).">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $kepatuhanBadgeCls }}">
                    {{ $stats['kepatuhan_label'] }}
                </span>
            </div>
            <div>
                <p class="mt-1.5 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['persen_kepatuhan'] }}%</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    {{ $stats['sesi_terlapor_periode'] }}/{{ $stats['total_sesi_periode'] }} sesi terjadwal
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Laporan</p>
                <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    title="Total seluruh riwayat laporan jurnal yang tercatat sesuai filter tabel.">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <div>
                <p class="mt-1.5 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_laporan'] }}</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    {{ $stats['terlaksana'] }} terlaksana
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Terverifikasi</p>
            <div>
                <p class="mt-1.5 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['diverifikasi'] }}</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    Disetujui Supervisor
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Menunggu / Revisi</p>
            <div>
                <p class="mt-1.5 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                    {{ $stats['menunggu'] + $stats['perlu_revisi'] }}
                </p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    {{ $stats['menunggu'] }} tunggu &middot; {{ $stats['perlu_revisi'] }} revisi
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Belum Ada Jurnal</p>
                @if (($stats['eskalasi_count'] ?? 0) > 0)
                    <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title="{{ $stats['eskalasi_count'] }} sesi sudah melewati batas waktu pelaporan.">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    </span>
                @endif
            </div>
            <div>
                <p class="mt-1.5 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                    {{ $sesiTerlewatTotal ?? 0 }}
                </p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    Periode aktif
                </p>
            </div>
        </div>
    </div>

    {{-- Sesi Terlewat Belum Ada Jurnal (Default 4 Teratas Kompak + Toggle Scrollable) --}}
    @if (($sesiTerlewat ?? collect())->isNotEmpty())
        @php $jumlahEskalasi = (int) ($stats['eskalasi_count'] ?? 0); @endphp
        <div x-data="{ showAllMissed: false }" class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-800/70 dark:bg-amber-950/30">
            <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                        Sesi Terlewat Belum Ada Jurnal ({{ $sesiTerlewatTotal }} Sesi)
                    </h2>
                    <p class="text-xs text-amber-800 dark:text-amber-300">
                        <span x-show="!showAllMissed">
                            Menampilkan {{ min(4, $sesiTerlewat->count()) }} dari {{ $sesiTerlewatTotal }} sesi ({{ \Carbon\Carbon::parse($occDari)->format('d/m') }} - {{ \Carbon\Carbon::parse($occSampai)->format('d/m/Y') }})
                        </span>
                        <span x-show="showAllMissed" x-cloak>
                            Seluruh {{ $sesiTerlewat->count() }} sesi ({{ \Carbon\Carbon::parse($occDari)->format('d/m') }} - {{ \Carbon\Carbon::parse($occSampai)->format('d/m/Y') }})
                        </span>
                        @if ($jumlahEskalasi > 0)
                            <span>&middot; <strong class="font-semibold text-red-700 dark:text-red-300">{{ $jumlahEskalasi }} lewat &gt;48 jam</strong></span>
                        @endif
                    </p>
                </div>
                @if ($sesiTerlewat->count() > 4)
                    <button type="button" @click="showAllMissed = !showAllMissed"
                        class="inline-flex min-h-[38px] items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-amber-950/60">
                        <span x-text="showAllMissed ? 'Tampilkan lebih sedikit' : 'Tampilkan semua ({{ $sesiTerlewatTotal }} sesi)'"></span>
                    </button>
                @endif
            </div>
            <div class="divide-y divide-amber-200/70 rounded-lg border border-amber-200 bg-white dark:divide-amber-900/50 dark:border-amber-900/60 dark:bg-gray-900"
                :class="showAllMissed ? 'max-h-[360px] overflow-y-auto' : ''">
                @foreach ($sesiTerlewat as $idx => $miss)
                    @php
                        $mJadwal  = $miss['jadwal'];
                        $mTanggal = $miss['tanggal'];
                        $mCarbon  = \Carbon\Carbon::parse($mTanggal)->locale('id');
                    @endphp
                    <div x-show="showAllMissed || {{ $idx }} < 4" {{ $idx >= 4 ? 'x-cloak' : '' }}
                        class="flex flex-col gap-2 px-3.5 py-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-2.5 text-xs">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-amber-100 font-mono text-[11px] font-semibold text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                {{ $idx + 1 }}
                            </span>
                            <div class="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $mJadwal->nama_kelas }}</span>
                                <span class="text-gray-500 dark:text-gray-400">({{ $mJadwal->materi ?: 'Umum' }})</span>
                                <span class="text-gray-400">&middot;</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $mCarbon->translatedFormat('D, d M Y') }}</span>
                                <span class="font-mono text-gray-600 dark:text-gray-400">{{ substr((string) $mJadwal->jam_mulai, 0, 5) }}-{{ substr((string) $mJadwal->jam_selesai, 0, 5) }} WIB</span>
                            </div>
                        </div>
                        <button type="button"
                            @click="openForOccurrence({{ $mJadwal->id }}, '{{ $mTanggal }}')"
                            class="inline-flex min-h-[36px] shrink-0 items-center justify-center rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            Isi Jurnal
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Riwayat Laporan Aktivitas --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Riwayat Laporan Aktivitas ({{ $riwayatAktivitas->total() }})
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Satu entri per sesi kelas dan tanggal. Klik Edit untuk memperbarui laporan atau memperbaiki revisi.
                    </p>
                </div>
            </div>

            <form method="GET" action="{{ route('instruktur.aktivitas.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
                <div class="sm:col-span-2 xl:col-span-4">
                    <label for="filter_jadwal_id" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Kelas / Materi</label>
                    <select id="filter_jadwal_id" name="jadwal_id"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Kelas &amp; Materi</option>
                        @foreach ($jadwals as $jOpt)
                            <option value="{{ $jOpt->id }}" {{ (string) ($jadwalFilter ?? '') === (string) $jOpt->id ? 'selected' : '' }}>
                                {{ $jOpt->nama_kelas }} - {{ $jOpt->materi }} ({{ $jOpt->hari ?? ($jOpt->tanggal?->format('d/m') ?? '-') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <label for="aktivitas_dari" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" id="aktivitas_dari" name="dari" value="{{ $dari ?? '' }}"
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div class="xl:col-span-2">
                    <label for="aktivitas_sampai" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" id="aktivitas_sampai" name="sampai" value="{{ $sampai ?? '' }}"
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div class="xl:col-span-2">
                    <label for="aktivitas_sort" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Urutkan Tanggal</label>
                    <select id="aktivitas_sort" name="sort"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="terbaru" {{ ($sort ?? 'terbaru') === 'terbaru' ? 'selected' : '' }}>Tanggal Terbaru</option>
                        <option value="terlama" {{ ($sort ?? 'terbaru') === 'terlama' ? 'selected' : '' }}>Tanggal Terlama</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-2">
                    <button type="submit"
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-brand-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Terapkan
                    </button>
                    @if ($hasActiveFilter)
                        <a href="{{ route('instruktur.aktivitas.index') }}"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Kelas &amp; Materi</th>
                        <th class="px-5 py-3">Jam &amp; Durasi Aktual</th>
                        <th class="px-5 py-3">Peserta</th>
                        <th class="px-5 py-3">Status Kelas &amp; Laporan</th>
                        <th class="px-5 py-3">Verifikasi Supervisor</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($riwayatAktivitas as $ra)
                        @php
                            $isRevisi     = $ra->status_verifikasi === 'perlu_revisi';
                            $isLocked     = in_array($ra->status_verifikasi, ['diverifikasi', 'disetujui'], true);
                            $hasPresensi  = $presensiAktivitasMap[$ra->id] ?? true;
                            $kelasInfo    = $ra->status_kelas_info;
                            $eskalasiInfo = $ra->eskalasi_info;
                            $kelasBadgeCls = match ($kelasInfo['color']) {
                                'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                            };
                            $raEditPayload = [
                                'jadwal_id'            => $ra->jadwal_id,
                                'tanggal'              => $ra->tanggal?->toDateString() ?? today()->toDateString(),
                                'jam_mulai_aktual'     => $ra->jam_mulai_aktual ? substr((string) $ra->jam_mulai_aktual, 0, 5) : '08:00',
                                'jam_selesai_aktual'   => $ra->jam_selesai_aktual ? substr((string) $ra->jam_selesai_aktual, 0, 5) : '10:00',
                                'materi_disampaikan'   => $ra->materi_disampaikan ?? '',
                                'tautan_materi'        => $ra->tautan_materi ?? '',
                                'jumlah_peserta_hadir' => (int) ($ra->jumlah_peserta_hadir ?? 0),
                                'status_konfirmasi'    => $ra->status_konfirmasi ?? 'dikonfirmasi',
                                'kendala_kegiatan'     => $ra->kendala_kegiatan ?? '',
                                'hasil_pembelajaran'   => $ra->hasil_pembelajaran ?? '',
                                'catatan'              => $ra->catatan ?? '',
                                'catatan_supervisor'   => $ra->catatan_supervisor ?? '',
                                'is_locked'            => $isLocked,
                                'has_dokumentasi'      => ! empty($ra->file_dokumentasi),
                                'dokumentasi_url'      => $ra->dokumentasi_url ?? '',
                            ];
                        @endphp
                        <tr class="{{ $isRevisi ? 'bg-red-50/30 dark:bg-red-950/15' : 'hover:bg-gray-50/70 dark:hover:bg-gray-800/40' }}">
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ $ra->tanggal ? $ra->tanggal->copy()->locale('id')->translatedFormat('l, d M Y') : '-' }}
                                </p>
                                <p class="font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $ra->tanggal ? $ra->tanggal->format('d/m/Y') : '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ra->jadwal?->nama_kelas ?? '-' }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $ra->materi_disampaikan }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-xs">
                                    @if ($ra->tautan_materi && preg_match('#^https?://#i', $ra->tautan_materi))
                                        <a href="{{ $ra->tautan_materi }}" target="_blank" rel="noopener" class="font-medium text-brand-600 underline dark:text-brand-400">Buka Modul</a>
                                    @endif
                                    @if ($ra->file_dokumentasi)
                                        <a href="{{ $ra->dokumentasi_url }}" target="_blank" rel="noopener" class="font-medium text-emerald-700 underline dark:text-emerald-300">Lihat Bukti</a>
                                    @else
                                        <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Tanpa Bukti</span>
                                    @endif
                                </div>
                                @if ($ra->catatan || $ra->hasil_pembelajaran || $ra->kendala_kegiatan)
                                    <details class="mt-1.5 text-xs">
                                        <summary class="cursor-pointer select-none font-medium text-gray-500 hover:text-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:text-gray-200">
                                            Detail catatan
                                        </summary>
                                        <div class="mt-1 space-y-0.5 rounded-md border border-gray-200 bg-gray-50/70 p-2 dark:border-gray-800 dark:bg-gray-800/40">
                                            @if ($ra->catatan)
                                                <p class="text-gray-700 dark:text-gray-300"><span class="font-semibold">Ringkasan:</span> {{ $ra->catatan }}</p>
                                            @endif
                                            @if ($ra->hasil_pembelajaran)
                                                <p class="text-emerald-700 dark:text-emerald-300"><span class="font-semibold">Hasil:</span> {{ $ra->hasil_pembelajaran }}</p>
                                            @endif
                                            @if ($ra->kendala_kegiatan)
                                                <p class="text-amber-700 dark:text-amber-300"><span class="font-semibold">Kendala:</span> {{ $ra->kendala_kegiatan }}</p>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                @if ($ra->jam_mulai_aktual || $ra->jam_selesai_aktual)
                                    <p class="font-mono">
                                        {{ $ra->jam_mulai_aktual ? substr($ra->jam_mulai_aktual, 0, 5) : '-' }} - {{ $ra->jam_selesai_aktual ? substr($ra->jam_selesai_aktual, 0, 5) : '-' }} WIB
                                    </p>
                                    @if ($ra->durasi_aktual_label && $ra->durasi_aktual_label !== '-')
                                        <p class="mt-0.5 text-[11px] font-medium text-gray-600 dark:text-gray-400">
                                            Durasi: {{ $ra->durasi_aktual_label }}
                                        </p>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                @if ($ra->jumlah_peserta_hadir !== null)
                                    {{ $ra->jumlah_peserta_hadir }} orang
                                @else
                                    <span class="font-normal text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 font-semibold {{ $kelasBadgeCls }}">
                                    {{ $kelasInfo['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 font-semibold
                                    {{ $ra->status_verifikasi === 'diverifikasi' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : ($isRevisi ? 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                    {{ $ra->verifikasi_label }}
                                </span>
                                @if ($ra->catatan_supervisor)
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $ra->catatan_supervisor }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <button type="button"
                                    @click='openForEdit(@json($raEditPayload))'
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $isLocked ? 'border-blue-200 bg-blue-50/50 text-blue-700 hover:bg-blue-100/70 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-300' : ($isRevisi ? 'border-red-300 bg-white text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-gray-900 dark:text-red-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700') }}">
                                    {{ $isLocked ? 'Lihat' : ($isRevisi ? 'Perbaiki' : 'Edit') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada riwayat aktivitas mengajar yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayatAktivitas->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $riwayatAktivitas->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Formulir Jurnal Aktivitas Terpadu --}}
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-jurnal-title"
        @click.self="closeModal()">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900 sm:p-6">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 id="modal-jurnal-title" class="text-base font-bold text-gray-900 dark:text-white" x-text="modalTitle"></h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Lengkapi realisasi pelaksanaan kelas untuk dikirim ke Supervisor.
                    </p>
                </div>
                <button type="button" @click="closeModal()"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Tutup formulir">
                    Tutup
                </button>
            </div>

            <template x-if="readOnly">
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200">
                    <strong>Laporan Terkunci:</strong> Laporan ini sudah diverifikasi Supervisor dan tidak dapat diubah lagi. Hubungi Supervisor atau Admin jika diperlukan koreksi.
                </div>
            </template>

            <template x-if="!readOnly && dayMismatchMessage()">
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    <strong>Perhatian Jadwal:</strong> <span x-text="dayMismatchMessage()"></span>
                </div>
            </template>

            <template x-if="form.catatan_supervisor">
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    <strong>Catatan Supervisor:</strong> <span x-text="form.catatan_supervisor"></span>
                </div>
            </template>

            <form method="POST" :action="currentActionUrl()" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                @csrf
                @method('PUT')

                <div class="sm:col-span-2 lg:col-span-6">
                    <label for="modal_jadwal_id" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelas &amp; Jadwal</label>
                    <select id="modal_jadwal_id" x-model="form.jadwal_id" @change="onJadwalChange()" :disabled="readOnly"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60">
                        @foreach ($jadwals as $jOpt)
                            <option value="{{ $jOpt->id }}">
                                {{ $jOpt->nama_kelas }} - {{ $jOpt->materi }} ({{ $jOpt->tanggal_label }} &middot; {{ substr((string) $jOpt->jam_mulai, 0, 5) }}-{{ substr((string) $jOpt->jam_selesai, 0, 5) }} WIB)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="modal_tanggal" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tanggal Sesi</label>
                    <input id="modal_tanggal" type="date" name="tanggal" x-model="form.tanggal" :disabled="readOnly" required
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>
                <div class="lg:col-span-2">
                    <label for="modal_jam_mulai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Jam Mulai Aktual</label>
                    <input id="modal_jam_mulai" type="time" name="jam_mulai_aktual" x-model="form.jam_mulai_aktual" :disabled="readOnly" required
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>
                <div class="lg:col-span-2">
                    <label for="modal_jam_selesai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Jam Selesai Aktual</label>
                    <input id="modal_jam_selesai" type="time" name="jam_selesai_aktual" x-model="form.jam_selesai_aktual" :disabled="readOnly" required
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>

                <div class="sm:col-span-2 lg:col-span-4">
                    <label for="modal_materi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Materi Disampaikan</label>
                    <input id="modal_materi" type="text" name="materi_disampaikan" x-model="form.materi_disampaikan" :disabled="readOnly" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>
                <div class="lg:col-span-2">
                    <label for="modal_peserta" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Peserta Hadir</label>
                    <input id="modal_peserta" type="number" name="jumlah_peserta_hadir" x-model="form.jumlah_peserta_hadir" :disabled="readOnly" min="0" max="1000" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="modal_tautan" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tautan Modul (URL, Opsional)</label>
                    <input id="modal_tautan" type="url" name="tautan_materi" x-model="form.tautan_materi" :disabled="readOnly" placeholder="https://..."
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="modal_kendala" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kendala Kegiatan (Opsional)</label>
                    <input id="modal_kendala" type="text" name="kendala_kegiatan" x-model="form.kendala_kegiatan" :disabled="readOnly" placeholder="Kendala perangkat, jaringan, atau alasan penyesuaian jadwal"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="modal_hasil" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Hasil Pembelajaran (Opsional)</label>
                    <input id="modal_hasil" type="text" name="hasil_pembelajaran" x-model="form.hasil_pembelajaran" :disabled="readOnly" placeholder="Capaian praktik peserta"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="modal_catatan" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Ringkasan Kegiatan (Wajib jika beda hari jadwal)</label>
                    <input id="modal_catatan" type="text" name="catatan" x-model="form.catatan" :disabled="readOnly" placeholder="Ringkasan pelaksanaan kelas atau alasan jadwal pengganti"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60" />
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="modal_dokumentasi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Bukti Dokumentasi (Foto/PDF)
                        <span x-show="!readOnly && form.status_konfirmasi === 'dikonfirmasi' && !form.has_dokumentasi" class="text-red-600 dark:text-red-400">* Wajib saat Terlaksana</span>
                    </label>
                    <template x-if="!readOnly">
                        <input id="modal_dokumentasi" type="file" name="file_dokumentasi" accept=".jpg,.jpeg,.png,.webp,.pdf"
                            :required="form.status_konfirmasi === 'dikonfirmasi' && !form.has_dokumentasi"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </template>
                    <template x-if="form.dokumentasi_url">
                        <p class="mt-1 text-xs">
                            <a :href="form.dokumentasi_url" target="_blank" rel="noopener" class="font-medium text-emerald-700 underline dark:text-emerald-300">Lihat bukti dokumentasi terunggah</a>
                        </p>
                    </template>
                    <template x-if="readOnly && !form.dokumentasi_url">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak ada berkas dokumentasi.</p>
                    </template>
                </div>
                <div class="lg:col-span-3">
                    <label for="modal_status_konfirmasi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Pelaksanaan</label>
                    <select id="modal_status_konfirmasi" name="status_konfirmasi" x-model="form.status_konfirmasi" :disabled="readOnly"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-800/60">
                        <option value="dikonfirmasi">Terlaksana (Dikonfirmasi)</option>
                        <option value="tidak_terlaksana">Tidak Terlaksana</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 sm:col-span-2 lg:col-span-6 dark:border-gray-800">
                    <button type="button" @click="closeModal()"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        x-text="readOnly ? 'Tutup' : 'Batal'">
                    </button>
                    <button type="submit" x-show="!readOnly"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Simpan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function aktivitasPage(jadwalOptions) {
        const optionKeys = Object.keys(jadwalOptions || {});
        const firstId = optionKeys.length > 0 ? Number(optionKeys[0]) : 0;

        const hariNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return {
            jadwalOptions: jadwalOptions || {},
            modalOpen: false,
            readOnly: false,
            modalTitle: 'Catat Jurnal Aktivitas Mengajar',
            form: {
                jadwal_id: firstId,
                tanggal: '',
                jam_mulai_aktual: '08:00',
                jam_selesai_aktual: '10:00',
                materi_disampaikan: '',
                tautan_materi: '',
                jumlah_peserta_hadir: 20,
                status_konfirmasi: 'dikonfirmasi',
                kendala_kegiatan: '',
                hasil_pembelajaran: '',
                catatan: '',
                catatan_supervisor: '',
                has_dokumentasi: false,
                dokumentasi_url: '',
            },
            dayMismatchMessage() {
                const j = this.jadwalOptions[this.form.jadwal_id];
                if (!j || !this.form.tanggal || !j.is_pola_mingguan || !j.hari) {
                    return '';
                }
                const parts = String(this.form.tanggal).split('-');
                if (parts.length !== 3) return '';
                const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                const hariDipilih = hariNames[dt.getDay()] || '';
                if (hariDipilih && hariDipilih !== j.hari) {
                    return `Tanggal yang dipilih adalah hari ${hariDipilih}, sedangkan jadwal rutin kelas ini adalah hari ${j.hari}. Wajib isi alasan penyesuaian jadwal di kolom Ringkasan atau Kendala.`;
                }
                return '';
            },
            init() {
                const params = new URLSearchParams(window.location.search);
                const qJadwalId = parseInt(params.get('jadwal_id') || '', 10);
                const qTanggal = params.get('tanggal') || '';
                if (!Number.isNaN(qJadwalId) && qJadwalId > 0) {
                    this.openForOccurrence(qJadwalId, qTanggal);
                    return;
                }
                const hash = window.location.hash;
                if (hash && hash.startsWith('#jadwal-')) {
                    const parsed = parseInt(hash.replace('#jadwal-', ''), 10);
                    if (!Number.isNaN(parsed) && parsed > 0) {
                        this.openForOccurrence(parsed, '');
                    }
                }
            },
            currentActionUrl() {
                const j = this.jadwalOptions[this.form.jadwal_id];
                return j ? j.update_url : '#';
            },
            applyJadwalDefaults(jadwalId, tanggalOverride) {
                const j = this.jadwalOptions[jadwalId];
                if (!j) return;
                this.form.jadwal_id = Number(jadwalId);
                this.form.tanggal = tanggalOverride || j.tanggal_default || '';
                this.form.jam_mulai_aktual = j.jam_mulai || '08:00';
                this.form.jam_selesai_aktual = j.jam_selesai || '10:00';
                this.form.materi_disampaikan = j.materi || '';
                this.form.jumlah_peserta_hadir = j.jumlah_peserta ?? 20;
            },
            onJadwalChange() {
                this.applyJadwalDefaults(this.form.jadwal_id, this.form.tanggal);
            },
            resetOptionalFields() {
                this.form.tautan_materi = '';
                this.form.status_konfirmasi = 'dikonfirmasi';
                this.form.kendala_kegiatan = '';
                this.form.hasil_pembelajaran = '';
                this.form.catatan = '';
                this.form.catatan_supervisor = '';
                this.form.has_dokumentasi = false;
                this.form.dokumentasi_url = '';
            },
            openNew() {
                this.readOnly = false;
                this.modalTitle = 'Catat Jurnal Aktivitas Mengajar';
                this.resetOptionalFields();
                if (firstId > 0) {
                    this.applyJadwalDefaults(firstId, '');
                }
                this.modalOpen = true;
            },
            openForOccurrence(jadwalId, tanggal) {
                this.readOnly = false;
                this.modalTitle = 'Isi Jurnal Sesi Mengajar';
                this.resetOptionalFields();
                this.applyJadwalDefaults(jadwalId, tanggal);
                this.modalOpen = true;
            },
            openForEdit(payload) {
                this.readOnly = Boolean(payload.is_locked);
                this.modalTitle = this.readOnly
                    ? 'Detail Laporan Aktivitas (Terverifikasi)'
                    : 'Perbarui Laporan Aktivitas Mengajar';
                this.form = {
                    jadwal_id: Number(payload.jadwal_id || firstId),
                    tanggal: payload.tanggal || '',
                    jam_mulai_aktual: payload.jam_mulai_aktual || '08:00',
                    jam_selesai_aktual: payload.jam_selesai_aktual || '10:00',
                    materi_disampaikan: payload.materi_disampaikan || '',
                    tautan_materi: payload.tautan_materi || '',
                    jumlah_peserta_hadir: payload.jumlah_peserta_hadir ?? 0,
                    status_konfirmasi: payload.status_konfirmasi || 'dikonfirmasi',
                    kendala_kegiatan: payload.kendala_kegiatan || '',
                    hasil_pembelajaran: payload.hasil_pembelajaran || '',
                    catatan: payload.catatan || '',
                    catatan_supervisor: payload.catatan_supervisor || '',
                    has_dokumentasi: Boolean(payload.has_dokumentasi),
                    dokumentasi_url: payload.dokumentasi_url || '',
                };
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
            },
        };
    }
</script>
@endpush
@endsection
