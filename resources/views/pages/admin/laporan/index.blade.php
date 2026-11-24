@extends('layouts.' . ($routePrefix ?? 'admin') . '.app')
@section('title', 'Laporan Monitoring & Evaluasi')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Laporan</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route(($routePrefix ?? 'admin') . '.laporan.export', $activeFilterParams) }}"
           class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Ekspor CSV
        </a>
        <a href="{{ route(($routePrefix ?? 'admin') . '.laporan.export-excel', $activeFilterParams) }}"
           class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-emerald-600 bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6a2 2 0 012-2h6l6 6v8a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
            Ekspor Excel (.xlsx)
        </a>
        <a href="{{ route(($routePrefix ?? 'admin') . '.laporan.cetak', $activeFilterParams) }}"
           target="_blank" rel="noopener"
           class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / PDF
        </a>
    </div>
</div>

@php
    $tabs = [
        'absensi'    => 'Absensi',
        'evaluasi'   => 'Evaluasi',
        'jadwal'     => 'Jadwal',
        'aktivitas'  => 'Aktivitas',
        'observasi'  => 'Observasi',
        'kinerja'    => 'Kinerja',
        'individual' => 'Rapor Individual',
        'manajerial' => 'Ringkasan Manajerial',
    ];
    $kategoriBadge = [
        'Sangat Baik' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
        'Baik' => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
        'Cukup' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
        'Kurang' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
    ];
@endphp

<div class="mb-4 inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900 no-print">
    @foreach($tabs as $key => $label)
        @php
            $tabParams = array_filter([
                'jenis' => $key,
                'dari' => $dari,
                'sampai' => $sampai,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'instruktur' => $instrukturId,
                'kelas' => $kelasId ?? '',
            ], fn ($val) => (string) $val !== '');
        @endphp
        <a href="{{ route(($routePrefix ?? 'admin') . '.laporan.index', $tabParams) }}"
           class="inline-flex min-h-[44px] items-center rounded-md px-3.5 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $jenis === $key ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 no-print">
    <form method="GET" action="{{ route(($routePrefix ?? 'admin') . '.laporan.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="jenis" value="{{ $jenis }}">
        <div class="w-full sm:w-auto">
            <label for="bulan" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Bulan (Opsional)</label>
            <select id="bulan" name="bulan" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Sesuai Rentang Tanggal</option>
                @foreach($namaBulan as $num => $lbl)
                    <option value="{{ $num }}" {{ (string) ($bulan ?? '') === (string) $num ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="tahun" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tahun</label>
            <select id="tahun" name="tahun" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua</option>
                @foreach($daftarTahun as $yr)
                    <option value="{{ $yr }}" {{ (string) ($tahun ?? '') === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}"
                   onchange="const f=this.form;if(f){const b=f.querySelector('#bulan');const t=f.querySelector('#tahun');if(b)b.value='';if(t)t.value='';}"
                   class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div class="w-full sm:w-auto">
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}"
                   onchange="const f=this.form;if(f){const b=f.querySelector('#bulan');const t=f.querySelector('#tahun');if(b)b.value='';if(t)t.value='';}"
                   class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div class="min-w-[180px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur</option>
                @foreach($instrukturs as $ins)
                    <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                @endforeach
            </select>
        </div>
        @if(($kelasList ?? collect())->isNotEmpty())
        <div class="w-full sm:w-auto">
            <label for="kelas" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelas</label>
            <select id="kelas" name="kelas" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kls)
                    <option value="{{ $kls->id }}" {{ (string) ($kelasId ?? '') === (string) $kls->id ? 'selected' : '' }}>{{ $kls->display_label }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if(!empty($statusOptions))
        <div class="w-full sm:w-auto">
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                @foreach($statusOptions as $optKey => $optLabel)
                    <option value="{{ $optKey }}" {{ ($statusFilter ?? '') === $optKey ? 'selected' : '' }}>{{ $optLabel }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        <a href="{{ route(($routePrefix ?? 'admin') . '.laporan.index', ['jenis' => $jenis]) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
    @if($jenis === 'individual' && empty($selectedInstruktur))
        <p class="mt-2.5 text-xs font-medium text-brand-700 dark:text-brand-300">
            Petunjuk: Pilih satu nama pada filter Instruktur lalu klik Terapkan untuk menampilkan Rapor Individual lengkap.
        </p>
    @endif
</div>

@if(!empty($distribusiChart['labels']))
<div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
    <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $distribusiChart['title'] }}</h2>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-center">
        <div class="lg:col-span-7">
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200 sm:grid-cols-4 dark:border-gray-800 dark:bg-gray-800">
                @php
                    $totalSeries = array_sum($distribusiChart['series']);
                    $sumSeries = max(1, $totalSeries);
                    $isScoreProfile = $jenis === 'individual';
                @endphp
                @foreach($distribusiChart['labels'] as $idx => $lbl)
                    @php
                        $valCount = (float) ($distribusiChart['series'][$idx] ?? 0);
                        $pct = $isScoreProfile
                            ? min(100, max(0, round($valCount, 1)))
                            : round(($valCount / $sumSeries) * 100, 1);
                    @endphp
                    <div class="bg-white p-3.5 dark:bg-gray-900">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $lbl }}</p>
                        <div class="mt-1 flex items-baseline justify-between">
                            <span class="font-mono text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $isScoreProfile ? number_format($valCount, 1) : (int) $valCount }}
                            </span>
                            <span class="text-xs font-semibold text-brand-600 dark:text-brand-300">
                                {{ $isScoreProfile ? '/ 100' : ($totalSeries > 0 ? $pct . '%' : '0%') }}
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                            <div class="h-full bg-brand-600" style="width: {{ ($isScoreProfile || $totalSeries > 0) ? $pct : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="lg:col-span-5">
            <div id="laporanDistribusiChart" class="h-56 w-full"></div>
        </div>
    </div>
</div>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Rekap {{ $tabs[$jenis] ?? 'Laporan' }}</h2>
        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
            Periode: {{ $periodeLabel }}
            <span class="mx-1.5 text-gray-400" aria-hidden="true">|</span>
            {{ $selectedInstruktur ? $selectedInstruktur->nama_lengkap . ' (' . $selectedInstruktur->kode_instruktur . ')' : 'Seluruh Instruktur' }}
            @if(!empty($selectedKelas))
                <span class="mx-1.5 text-gray-400" aria-hidden="true">|</span>
                Kelas: {{ $selectedKelas->display_label }}
            @endif
            @if($statusFilterLabel !== '')
                <span class="mx-1.5 text-gray-400" aria-hidden="true">|</span>
                Filter: {{ $statusFilterLabel }}
            @endif
        </p>
    </div>

    <div class="overflow-x-auto">
        @if($jenis === 'absensi')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Check-in</th>
                        <th class="px-4 py-3">Check-out</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $row->instruktur?->nama_lengkap ?? '-' }}</p>
                            @if($row->instruktur?->kode_instruktur)
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->instruktur->kode_instruktur }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->jam_checkin ? substr((string) $row->jam_checkin, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->jam_checkout ? substr((string) $row->jam_checkout, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $row->status === 'hadir' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : ($row->status === 'terlambat' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : ($row->status === 'izin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300')) }}">
                                {{ $row->status_label }}
                            </span>
                            @if((int) ($row->menit_terlambat ?? 0) > 0)
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">Terlambat {{ (int) $row->menit_terlambat }} menit</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $sumberLabels[$row->sumber] ?? ($row->sumber ?: '-') }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row->keterangan ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Tidak ada data absensi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'evaluasi')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Pengisi</th>
                        <th class="px-4 py-3">Skala 1-5</th>
                        <th class="px-4 py-3">Nilai (0-100)</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Kode Kelas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $row->instruktur?->nama_lengkap ?? '-' }}</p>
                            @if($row->instruktur?->kode_instruktur)
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->instruktur->kode_instruktur }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row->nama_pengisi ?: 'Anonim' }}</td>
                        <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $row->rata_rata_skala5 !== null ? number_format((float) $row->rata_rata_skala5, 2) . ' / 5.00' : '-' }}</td>
                        <td class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">{{ $row->total_nilai !== null ? number_format((float) $row->total_nilai, 2) : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $kategoriBadge[$row->nilai_label] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                {{ $row->nilai_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $row->kodeEvaluasi?->kode ?? ($row->batch_import ?: '-') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Tidak ada data evaluasi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'jadwal')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Hari / Tanggal</th>
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Kelas &amp; Materi</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Ruangan</th>
                        <th class="px-4 py-3">Peserta</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->tanggal_label }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $row->instruktur?->nama_lengkap ?? '-' }}</p>
                            @if($row->instrukturAsli)
                                <p class="text-xs text-gray-500 dark:text-gray-400">Pengalihan dari {{ $row->instrukturAsli->nama_lengkap }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $row->nama_kelas ?? '-' }} - {{ $row->materi ?: 'Umum' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ substr((string) $row->jam_mulai, 0, 5) }} - {{ substr((string) $row->jam_selesai, 0, 5) }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row->ruangan_label }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ (int) ($row->jumlah_peserta ?? 0) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $row->status === 'berlangsung' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : ($row->status === 'terjadwal' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : ($row->status === 'dibatalkan' ? 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300')) }}">
                                {{ $row->status_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Tidak ada jadwal mengajar pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'aktivitas')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Kelas &amp; Peserta</th>
                        <th class="px-4 py-3">Materi</th>
                        <th class="px-4 py-3">Status &amp; Verifikasi</th>
                        <th class="px-4 py-3">Kendala / Hasil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $row)
                    @php
                        $rincianAktivitas = [];
                        if (!empty($row->kendala_kegiatan)) {
                            $rincianAktivitas[] = 'Kendala: ' . $row->kendala_kegiatan;
                        }
                        if (!empty($row->hasil_pembelajaran)) {
                            $rincianAktivitas[] = 'Hasil: ' . $row->hasil_pembelajaran;
                        } elseif (!empty($row->catatan)) {
                            $rincianAktivitas[] = $row->catatan;
                        }
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $row->instruktur?->nama_lengkap ?? '-' }}</p>
                            @if($row->instruktur?->kode_instruktur)
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->instruktur->kode_instruktur }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            <p>{{ $row->jadwal?->nama_kelas ?? '-' }} ({{ (int) ($row->jumlah_peserta_hadir ?? 0) }} peserta)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Durasi: {{ $row->durasi_aktual_label }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row->materi_disampaikan ?: '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                            @php
                                $lapKelasInfo = $row->status_kelas_info;
                                $lapKelasCls  = match ($lapKelasInfo['color']) {
                                    'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                    'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                    'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                    default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                };
                            @endphp
                            <span class="inline-flex rounded-md px-2 py-0.5 font-medium {{ $lapKelasCls }}">
                                Kelas: {{ $lapKelasInfo['label'] }}
                            </span>
                            <p class="mt-1">{{ $row->status_label }} ({{ $row->verifikasi_label }})</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ !empty($rincianAktivitas) ? implode(' | ', $rincianAktivitas) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada aktivitas mengajar yang tercatat pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'observasi')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Supervisor</th>
                        <th class="px-4 py-3">Skala 1-5</th>
                        <th class="px-4 py-3">Skor (0-100)</th>
                        <th class="px-4 py-3">Catatan &amp; Rekomendasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $row->instruktur?->nama_lengkap ?? '-' }}</p>
                            @if($row->instruktur?->kode_instruktur)
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->instruktur->kode_instruktur }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row->supervisor?->name ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-gray-800 dark:text-gray-200">{{ $row->rata_rata_skala5 !== null ? number_format((float) $row->rata_rata_skala5, 2) . ' / 5.00' : '-' }}</td>
                        <td class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">{{ $row->skor_observasi !== null ? number_format((float) $row->skor_observasi, 2) : '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row->catatan_observasi ?: '-' }}{{ $row->rekomendasi ? ' | Rekomendasi: ' . $row->rekomendasi : '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada data observasi supervisor pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'kinerja')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Instruktur</th>
                        <th class="px-4 py-3">Bidang Keahlian</th>
                        <th class="px-4 py-3">Jadwal &amp; Jam</th>
                        <th class="px-4 py-3">Kehadiran</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Evaluasi</th>
                        <th class="px-4 py-3">Observasi</th>
                        <th class="px-4 py-3">Skor &amp; Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($data as $item)
                    @php
                        $kBadge = [
                            'sangat_baik' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            'baik' => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
                            'cukup' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                            'kurang' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                        ];
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $item['instruktur']->nama_lengkap }}</p>
                            <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['instruktur']->kode_instruktur }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['instruktur']->bidang_keahlian ?: '-' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ (int) $item['total_jadwal'] }} sesi</p>
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ number_format((float) $item['jam_mengajar'], 1, '.', '') }} jam</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['skor_kehadiran'] !== null ? number_format((float) $item['skor_kehadiran'], 2) . '%' : '-' }}</p>
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ (int) $item['total_absensi'] > 0 ? $item['total_hadir'] . ' dari ' . $item['total_absensi'] . ' sesi' : 'Belum ada presensi' }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['skor_aktivitas'] !== null ? number_format((float) $item['skor_aktivitas'], 2) . '%' : '-' }}</p>
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ (int) $item['basis_sesi_aktivitas'] > 0 ? $item['total_aktivitas'] . ' dari ' . $item['basis_sesi_aktivitas'] . ' sesi' : 'Belum ada sesi' }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['skor_evaluasi'] !== null ? number_format((float) $item['skor_evaluasi'], 2) : '-' }}</p>
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ (int) $item['total_evaluasi'] > 0 && $item['rata_rata_evaluasi_skala5'] !== null ? number_format((float) $item['rata_rata_evaluasi_skala5'], 2) . '/5.00 (' . $item['total_evaluasi'] . ' resp.)' : 'Belum ada evaluasi' }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['skor_observasi'] !== null ? number_format((float) $item['skor_observasi'], 2) : '-' }}</p>
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ (int) $item['total_observasi'] > 0 && $item['rata_rata_observasi_skala5'] !== null ? number_format((float) $item['rata_rata_observasi_skala5'], 2) . '/5.00 (' . $item['total_observasi'] . ' obs.)' : 'Belum ada observasi' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $item['skor_akhir'] !== null ? number_format((float) $item['skor_akhir'], 2) : '-' }}</span>
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $kBadge[$item['kategori'] ?? ''] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ $item['kategori_label'] }}</span>
                            </div>
                            @if(!empty($item['redistribusi_bobot']))
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Bobot disesuaikan</p>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Tidak ada data kinerja instruktur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif($jenis === 'individual')
            @if(empty($individualReport))
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Pilih satu instruktur pada filter di atas untuk menampilkan Rapor Individual.</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Rapor Individual merangkum profil, sertifikasi, jadwal &amp; jam mengajar, presensi, aktivitas, evaluasi peserta, observasi supervisor, dan skor kinerja dalam satu tampilan siap cetak.</p>
                </div>
            @else
                @php
                    $ind = $individualReport['instruktur'];
                    $indKinerja = $individualReport['kinerja'];
                @endphp
                <div class="space-y-6 p-5">
                    <div class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-4 dark:border-gray-800 dark:bg-gray-800/50">
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Instruktur</p>
                            <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">{{ $ind->nama_lengkap }}</p>
                            <p class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $ind->kode_instruktur }} &middot; {{ $ind->bidang_keahlian ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Beban Mengajar</p>
                            <p class="mt-0.5 font-mono text-lg font-bold text-gray-900 dark:text-white">{{ (int) ($indKinerja['total_jadwal'] ?? 0) }} sesi</p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ number_format((float) ($indKinerja['jam_mengajar'] ?? 0), 1) }} jam mengajar</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Kehadiran &amp; Aktivitas</p>
                            <p class="mt-0.5 font-mono text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ number_format((float) ($indKinerja['persentase_kehadiran'] ?? 0), 1) }}%</p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">Jurnal: {{ (int) ($indKinerja['total_aktivitas'] ?? 0) }}/{{ (int) ($indKinerja['basis_sesi_aktivitas'] ?? 0) }} sesi</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Skor Akhir Kinerja</p>
                            <p class="mt-0.5 font-mono text-lg font-bold text-brand-700 dark:text-brand-300">
                                {{ $indKinerja['skor_akhir'] !== null ? number_format((float) $indKinerja['skor_akhir'], 2) : '-' }}
                            </p>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $indKinerja['kategori_label'] ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Sertifikasi Kompetensi ({{ $individualReport['sertifikasis']->count() }})</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-gray-200 bg-gray-50 font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                                    <tr>
                                        <th class="px-3 py-2.5">Nama Sertifikasi</th>
                                        <th class="px-3 py-2.5">Nomor</th>
                                        <th class="px-3 py-2.5">Penerbit</th>
                                        <th class="px-3 py-2.5">Berlaku Sampai</th>
                                        <th class="px-3 py-2.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @forelse($individualReport['sertifikasis'] as $sert)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $sert->nama_sertifikasi }}</td>
                                            <td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $sert->nomor_sertifikat ?: '-' }}</td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $sert->penerbit ?: '-' }}</td>
                                            <td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $sert->berlaku_sampai?->format('d/m/Y') ?? '-' }}</td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $sert->status_kedaluwarsa_label ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada data sertifikasi tercatat.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Ringkasan Jadwal ({{ $individualReport['jadwals']->count() }})</h3>
                            <div class="space-y-2 text-xs">
                                @forelse($individualReport['jadwals']->take(6) as $j)
                                    <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-1.5 dark:border-gray-800">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $j->nama_kelas }} {{ $j->materi ? '- ' . $j->materi : '' }}</p>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $j->tanggal_label }} &middot; {{ substr((string) $j->jam_mulai, 0, 5) }} - {{ substr((string) $j->jam_selesai, 0, 5) }}</p>
                                        </div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $j->status_label }}</span>
                                    </div>
                                @empty
                                    <p class="text-gray-500 dark:text-gray-400">Tidak ada jadwal pada periode ini.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Evaluasi &amp; Observasi</h3>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/60">
                                    <p class="text-gray-600 dark:text-gray-400">Evaluasi Peserta ({{ $individualReport['evaluasis']->count() }})</p>
                                    <p class="mt-1 font-mono text-base font-bold text-gray-900 dark:text-white">{{ $indKinerja['skor_evaluasi'] !== null ? number_format((float) $indKinerja['skor_evaluasi'], 2) : '-' }}</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/60">
                                    <p class="text-gray-600 dark:text-gray-400">Observasi Supervisor ({{ $individualReport['observasis']->count() }})</p>
                                    <p class="mt-1 font-mono text-base font-bold text-gray-900 dark:text-white">{{ $indKinerja['skor_observasi'] !== null ? number_format((float) $indKinerja['skor_observasi'], 2) : '-' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1.5 text-xs">
                                @foreach($individualReport['observasis']->take(3) as $obs)
                                    <p class="text-gray-700 dark:text-gray-300">
                                        <span class="font-mono font-semibold">{{ $obs->tanggal?->format('d/m/Y') }}</span>:
                                        {{ $obs->catatan_observasi ?: ($obs->rekomendasi ?: '-') }}
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @elseif($jenis === 'manajerial')
            @php
                $mKpi = $manajerialReport['kpi'] ?? [];
                $mAlerts = $manajerialReport['alerts'] ?? [];
            @endphp
            <div class="space-y-6 p-5">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Instruktur Aktif</p>
                        <p class="mt-1 font-mono text-xl font-bold text-gray-900 dark:text-white">{{ $mKpi['total_instruktur_aktif'] ?? 0 }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">dari {{ $mKpi['total_instruktur'] ?? 0 }} terdaftar</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Kelas Diampu</p>
                        <p class="mt-1 font-mono text-xl font-bold text-gray-900 dark:text-white">{{ $mKpi['kelas_diampu'] ?? 0 }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $mKpi['total_sesi'] ?? 0 }} sesi terjadwal</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Total Jam Mengajar</p>
                        <p class="mt-1 font-mono text-xl font-bold text-gray-900 dark:text-white">{{ number_format((float) ($mKpi['total_jam_mengajar'] ?? 0), 1) }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">jam periode berjalan</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Rata-rata Kehadiran</p>
                        <p class="mt-1 font-mono text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format((float) ($mKpi['rata_kehadiran'] ?? 0), 1) }}%</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Rata-rata Evaluasi</p>
                        <p class="mt-1 font-mono text-xl font-bold text-gray-900 dark:text-white">{{ $mKpi['rata_evaluasi'] !== null ? number_format((float) $mKpi['rata_evaluasi'], 2) : '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Rata-rata Skor Kinerja</p>
                        <p class="mt-1 font-mono text-xl font-bold text-brand-700 dark:text-brand-300">{{ $mKpi['rata_skor_kinerja'] !== null ? number_format((float) $mKpi['rata_skor_kinerja'], 2) : '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Peringkat Atas (Top 5 Instruktur)</h3>
                        <div class="divide-y divide-gray-200 text-xs dark:divide-gray-800">
                            @forelse($manajerialReport['top_instruktur'] ?? [] as $idx => $top)
                                <div class="flex items-center justify-between py-2">
                                    <span><strong>#{{ $idx + 1 }}</strong> {{ $top['instruktur']->nama_lengkap }}</span>
                                    <span class="font-mono font-semibold text-emerald-700 dark:text-emerald-300">{{ $top['skor_akhir'] !== null ? number_format((float) $top['skor_akhir'], 2) : '-' }} ({{ $top['kategori_label'] }})</span>
                                </div>
                            @empty
                                <p class="py-4 text-gray-500 dark:text-gray-400">Belum ada data kinerja.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Perlu Pembinaan (Bottom 5 Instruktur)</h3>
                        <div class="divide-y divide-gray-200 text-xs dark:divide-gray-800">
                            @forelse($manajerialReport['bottom_instruktur'] ?? [] as $bot)
                                <div class="flex items-center justify-between py-2">
                                    <span>{{ $bot['instruktur']->nama_lengkap }}</span>
                                    <span class="font-mono font-semibold text-amber-700 dark:text-amber-300">{{ $bot['skor_akhir'] !== null ? number_format((float) $bot['skor_akhir'], 2) : '-' }} ({{ $bot['kategori_label'] }})</span>
                                </div>
                            @empty
                                <p class="py-4 text-gray-500 dark:text-gray-400">Belum ada data kinerja.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Ringkasan Peringatan Operasional ({{ $mAlerts['total'] ?? 0 }} aktif)</h3>
                    <div class="mb-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                        <div class="rounded bg-gray-50 px-3 py-2 dark:bg-gray-800/60">Kelas Tanpa Laporan: <strong class="font-mono">{{ $mAlerts['kelas_tanpa_laporan'] ?? 0 }}</strong></div>
                        <div class="rounded bg-gray-50 px-3 py-2 dark:bg-gray-800/60">Sertifikasi Hampir Kedaluwarsa: <strong class="font-mono">{{ $mAlerts['sertifikasi_hampir_expired'] ?? 0 }}</strong></div>
                        <div class="rounded bg-gray-50 px-3 py-2 dark:bg-gray-800/60">Instruktur Di Bawah Standar: <strong class="font-mono">{{ $mAlerts['instruktur_dibawah_standar'] ?? 0 }}</strong></div>
                    </div>
                    <div class="divide-y divide-gray-200 text-xs dark:divide-gray-800">
                        @forelse($mAlerts['items'] ?? [] as $al)
                            <div class="flex items-center justify-between gap-2 py-2">
                                <span class="font-medium text-gray-900 dark:text-white">[{{ $al['kategori'] }}] {{ $al['judul'] }}</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ $al['ringkasan'] }}</span>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500 dark:text-gray-400">Tidak ada peringatan operasional aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(is_object($data) && method_exists($data, 'hasPages') && $data->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800 no-print">{{ $data->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
@if(!empty($distribusiChart['labels']))
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.querySelector('#laporanDistribusiChart');
        if (!el || typeof ApexCharts === 'undefined') return;
        const isDark = document.documentElement.classList.contains('dark');
        const labelColor = isDark ? '#98a2b3' : '#475467';
        const gridColor = isDark ? '#1d2939' : '#e4e7ec';
        const labels = @json($distribusiChart['labels'] ?? []);
        const series = @json($distribusiChart['series'] ?? []);
        const isScoreProfile = @json($jenis === 'individual');

        new ApexCharts(el, {
            series: [{
                name: isScoreProfile ? 'Skor' : 'Jumlah',
                data: series
            }],
            chart: {
                type: 'bar',
                height: 220,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#033DF6'],
            plotOptions: {
                bar: { borderRadius: 6, columnWidth: '48%', distributed: true }
            },
            grid: { borderColor: gridColor, strokeDashArray: 3 },
            xaxis: {
                categories: labels,
                labels: { style: { colors: labelColor, fontSize: '11px' } }
            },
            yaxis: {
                min: 0,
                max: isScoreProfile ? 100 : undefined,
                forceNiceScale: true,
                labels: { style: { colors: labelColor, fontSize: '11px' } }
            },
            legend: { show: false },
            dataLabels: { enabled: true },
            tooltip: { theme: isDark ? 'dark' : 'light' }
        }).render();
    });
</script>
@endif
@endpush
