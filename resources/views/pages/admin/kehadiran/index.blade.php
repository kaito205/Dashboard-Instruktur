@extends('layouts.admin.app')
@section('title', 'Monitoring Kehadiran & Aktivitas Mengajar')

@section('content')
<div class="space-y-6" x-data="{
    manualModalOpen: false,
    manualMode: 'create',
    manualForm: {
        absensi_id: '',
        instruktur_id: '',
        jadwal_id: '',
        tanggal: '{{ date('Y-m-d') }}',
        jam_checkin: '08:00',
        jam_checkout: '10:00',
        status: 'hadir',
        keterangan: '',
    },
    aktivitasModalOpen: false,
    aktivitasForm: {
        jadwal_id: '',
        tanggal: '{{ date('Y-m-d') }}',
        jam_mulai: '08:00',
        jam_selesai: '10:00',
        materi: '',
    },
    init() {
        const prefill = @js($prefillJurnal ?? null);
        if (prefill && prefill.jadwal_id) {
            this.openAktivitasPreset(prefill);
        }
    },
    openAktivitasCreate() {
        this.aktivitasForm = {
            jadwal_id: '',
            tanggal: '{{ date('Y-m-d') }}',
            jam_mulai: '08:00',
            jam_selesai: '10:00',
            materi: '',
        };
        this.aktivitasModalOpen = true;
    },
    openAktivitasPreset(data) {
        this.aktivitasForm = {
            jadwal_id: data.jadwal_id ? String(data.jadwal_id) : '',
            tanggal: data.tanggal ?? '{{ date('Y-m-d') }}',
            jam_mulai: data.jam_mulai ?? '08:00',
            jam_selesai: data.jam_selesai ?? '10:00',
            materi: data.materi ?? '',
        };
        this.aktivitasModalOpen = true;
    },
    openManualCreate() {
        this.manualMode = 'create';
        this.manualForm = {
            absensi_id: '',
            instruktur_id: '',
            jadwal_id: '',
            tanggal: '{{ date('Y-m-d') }}',
            jam_checkin: '08:00',
            jam_checkout: '10:00',
            status: 'hadir',
            keterangan: '',
        };
        this.manualModalOpen = true;
    },
    openManualEdit(data) {
        this.manualMode = 'edit';
        this.manualForm = {
            absensi_id: String(data.absensi_id ?? ''),
            instruktur_id: String(data.instruktur_id ?? ''),
            jadwal_id: data.jadwal_id ? String(data.jadwal_id) : '',
            tanggal: data.tanggal ?? '{{ date('Y-m-d') }}',
            jam_checkin: data.jam_checkin ?? '',
            jam_checkout: data.jam_checkout ?? '',
            status: data.status ?? 'hadir',
            keterangan: data.keterangan ?? '',
        };
        this.manualModalOpen = true;
    },
    openManualPreset(data) {
        this.manualMode = 'create';
        this.manualForm = {
            absensi_id: '',
            instruktur_id: String(data.instruktur_id ?? ''),
            jadwal_id: data.jadwal_id ? String(data.jadwal_id) : '',
            tanggal: data.tanggal ?? '{{ date('Y-m-d') }}',
            jam_checkin: '',
            jam_checkout: '',
            status: data.status ?? 'tidak_hadir',
            keterangan: data.keterangan ?? '',
        };
        this.manualModalOpen = true;
    }
}" @keydown.escape.window="manualModalOpen = false; aktivitasModalOpen = false">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Kehadiran &amp; Aktivitas</h1>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="openManualCreate()"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Input Absensi Manual
            </button>
            <button type="button" @click="openAktivitasCreate()"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Catat Aktivitas
            </button>
            <form method="POST" action="{{ route('admin.kehadiran.auto-checkout') }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Tutup Sesi Belum Check-out ({{ $ringkasan['belum_checkout'] ?? 0 }})
                </button>
            </form>
        </div>
    </div>

    <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
        <a href="{{ route('admin.kehadiran.index', ['tab' => 'absensi', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId]) }}"
           class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'absensi' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
            Data Kehadiran
        </a>
        <a href="{{ route('admin.kehadiran.index', ['tab' => 'aktivitas', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId]) }}"
           class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'aktivitas' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
            Aktivitas Mengajar
        </a>
    </div>

    @if($tab === 'absensi')
    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-3 lg:grid-cols-6 dark:border-gray-800 dark:bg-gray-800">
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Catatan</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['total'] }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Hadir</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['hadir'] }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-1">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Terlambat</p>
                <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    title="Toleransi keterlambatan: &gt; {{ $ringkasan['toleransi_terlambat_menit'] ?? 15 }} menit dari jam mulai jadwal.">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['terlambat'] }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Izin</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['izin'] }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Tidak Hadir</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['tidak_hadir'] }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-1">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Kehadiran</p>
                @if (!empty($ringkasan['status_filter_aktif']))
                    <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title="Filter {{ $ringkasan['status_filter_label'] }}: {{ $ringkasan['status_filter_count'] }}/{{ $ringkasan['total'] }} ({{ $ringkasan['status_filter_persentase'] }}%)">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    </span>
                @endif
            </div>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['persentase'] }}%</p>
        </div>
    </div>
    @else
    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-3 lg:grid-cols-6 dark:border-gray-800 dark:bg-gray-800">
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Jurnal</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Dikonfirmasi</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['dikonfirmasi'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Belum Dikonfirmasi</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['belum_dikonfirmasi'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Diverifikasi</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['diverifikasi'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Menunggu Verifikasi</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['menunggu'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Perlu Revisi</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasanAktivitas['perlu_revisi'] ?? 0 }}</p>
        </div>
    </div>
    @endif

    @if($tab === 'absensi' && ($jadwalTerlewat ?? collect())->isNotEmpty())
    <div x-data="{ showAllMissed: false }" class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-800/70 dark:bg-amber-950/30">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                    Jadwal Terlewat Tanpa Presensi ({{ $jadwalTerlewat->count() }})
                </h2>
                <p class="text-xs text-amber-800 dark:text-amber-300">
                    Sesi berikut sudah selesai tanpa presensi dan dihitung Tidak Hadir. Klik Input Manual (Bypass) bila instruktur hadir/izin.
                </p>
            </div>
            <a href="{{ route('admin.jadwal.index') }}"
               class="inline-flex min-h-[44px] items-center rounded-lg border border-amber-300 bg-white px-3.5 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-gray-800">
                Kelola Jadwal
            </a>
        </div>
        <div class="divide-y divide-amber-200/70 rounded-lg border border-amber-200 bg-white dark:divide-amber-900/50 dark:border-amber-900/60 dark:bg-gray-900">
            @foreach($jadwalTerlewat as $miss)
                @php
                    $mJadwal = $miss['jadwal'];
                    $mTanggal = $miss['tanggal'];
                    $presetData = [
                        'instruktur_id' => $mJadwal->instruktur_id,
                        'jadwal_id'     => $mJadwal->id,
                        'tanggal'       => $mTanggal,
                        'status'        => 'tidak_hadir',
                        'keterangan'    => 'Rekonsiliasi jadwal terlewat tanpa presensi',
                    ];
                @endphp
                <div x-show="showAllMissed || {{ $loop->index }} < 5"
                     @if($loop->index >= 5) x-cloak @endif
                     class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 text-xs">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $mJadwal->instruktur?->nama_lengkap ?? '-' }}
                            <span class="font-normal text-gray-600 dark:text-gray-300">- {{ $mJadwal->nama_kelas }}</span>
                        </p>
                        <p class="mt-0.5 text-gray-600 dark:text-gray-300">
                            Tanggal {{ \Carbon\Carbon::parse($mTanggal)->format('d/m/Y') }} &middot; Jam {{ substr((string) $mJadwal->jam_mulai, 0, 5) }} - {{ substr((string) $mJadwal->jam_selesai, 0, 5) }} WIB
                        </p>
                    </div>
                    <button type="button"
                            data-preset="{{ json_encode($presetData) }}"
                            @click="openManualPreset(JSON.parse($el.dataset.preset))"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Input Manual (Bypass)
                    </button>
                </div>
            @endforeach
        </div>
        @if($jadwalTerlewat->count() > 5)
            <div class="mt-3 flex justify-center">
                <button type="button"
                        @click="showAllMissed = !showAllMissed"
                        class="inline-flex min-h-[36px] items-center rounded-lg border border-amber-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-gray-800">
                    <span x-show="!showAllMissed">Tampilkan Semua ({{ $jadwalTerlewat->count() }})</span>
                    <span x-show="showAllMissed" x-cloak>Sembunyikan</span>
                </button>
            </div>
        @endif
    </div>
    @endif

    @if($tab === 'aktivitas' && ($jadwalTanpaJurnal ?? collect())->isNotEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-800/70 dark:bg-amber-950/30">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                    Jadwal Terlewat Tanpa Jurnal Aktivitas ({{ $jadwalTanpaJurnalTotal ?? $jadwalTanpaJurnal->count() }})
                </h2>
                <p class="text-xs text-amber-800 dark:text-amber-300">
                    Sesi berikut sudah selesai namun belum memiliki jurnal mengajar. Klik Catat Jurnal untuk melengkapinya.
                </p>
            </div>
        </div>
        <div class="divide-y divide-amber-200/70 rounded-lg border border-amber-200 bg-white dark:divide-amber-900/50 dark:border-amber-900/60 dark:bg-gray-900">
            @foreach($jadwalTanpaJurnal as $miss)
                @php
                    $mJadwal   = $miss['jadwal'];
                    $mTanggal  = $miss['tanggal'];
                    $isTarget  = (string) request('jadwal_id') === (string) $mJadwal->id
                        && (! request()->filled('tanggal') || (string) request('tanggal') === (string) $mTanggal);
                    $jurnalPreset = [
                        'jadwal_id'   => $mJadwal->id,
                        'tanggal'     => $mTanggal,
                        'jam_mulai'   => substr((string) $mJadwal->jam_mulai, 0, 5),
                        'jam_selesai' => substr((string) $mJadwal->jam_selesai, 0, 5),
                        'materi'      => (string) ($mJadwal->materi ?? ''),
                    ];
                @endphp
                <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between {{ $isTarget ? 'bg-brand-50/70 ring-1 ring-inset ring-brand-300 dark:bg-brand-950/40 dark:ring-brand-700' : '' }}">
                    <div class="min-w-0 text-xs">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $mJadwal->instruktur?->nama_lengkap ?? '-' }}
                            <span class="font-normal text-gray-600 dark:text-gray-300">- {{ $mJadwal->nama_kelas }} ({{ $mJadwal->materi ?: 'Umum' }})</span>
                        </p>
                        <p class="mt-0.5 text-gray-600 dark:text-gray-300">
                            Tanggal {{ \Carbon\Carbon::parse($mTanggal)->format('d/m/Y') }} &middot; Jam {{ substr((string) $mJadwal->jam_mulai, 0, 5) }} - {{ substr((string) $mJadwal->jam_selesai, 0, 5) }} WIB
                        </p>
                    </div>
                    <button type="button"
                            data-jurnal-preset="{{ json_encode($jurnalPreset) }}"
                            @click="openAktivitasPreset(JSON.parse($el.dataset.jurnalPreset))"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Catat Jurnal
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @php
        $todayStr       = today()->toDateString();
        $sevenDaysStart = today()->copy()->subDays(6)->toDateString();
        $monthStart     = today()->copy()->startOfMonth()->toDateString();
        $monthEnd       = today()->copy()->endOfMonth()->toDateString();
        $presetBase     = array_filter([
            'tab'        => $tab,
            'instruktur' => $instrukturId ?? '',
            'status'     => $status ?? '',
            'sumber'     => $tab === 'absensi' ? ($sumber ?? '') : '',
            'verifikasi' => $tab === 'aktivitas' ? ($verifikasi ?? '') : '',
        ], fn ($v) => $v !== '');
        $datePresets = [
            ['label' => 'Hari Ini', 'dari' => $todayStr, 'sampai' => $todayStr],
            ['label' => '7 Hari Terakhir', 'dari' => $sevenDaysStart, 'sampai' => $todayStr],
            ['label' => 'Bulan Ini', 'dari' => $monthStart, 'sampai' => $monthEnd],
        ];
    @endphp
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex flex-wrap items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-800">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Periode Cepat:</span>
            @foreach($datePresets as $preset)
                @php
                    $isPresetActive = ($dari === $preset['dari'] && $sampai === $preset['sampai']);
                @endphp
                <a href="{{ route('admin.kehadiran.index', array_merge($presetBase, ['dari' => $preset['dari'], 'sampai' => $preset['sampai']])) }}"
                   class="inline-flex min-h-[36px] items-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $isPresetActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $preset['label'] }}
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.kehadiran.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
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
                    <option value="">Semua Instruktur</option>
                    @foreach($instrukturs as $ins)
                        <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                    @endforeach
                </select>
            </div>
            @if($tab === 'absensi')
                <div class="w-full sm:w-auto">
                    <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Kehadiran</label>
                    <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="hadir" {{ $status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="terlambat" {{ $status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="izin" {{ $status === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="tidak_hadir" {{ $status === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="sumber" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sumber</label>
                    <select id="sumber" name="sumber" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Sumber</option>
                        <option value="checkin_app" {{ $sumber === 'checkin_app' ? 'selected' : '' }}>Check-in Aplikasi</option>
                        <option value="manual" {{ $sumber === 'manual' ? 'selected' : '' }}>Input Manual Admin</option>
                        <option value="import" {{ $sumber === 'import' ? 'selected' : '' }}>Import Excel/CSV</option>
                    </select>
                </div>
            @else
                <div class="w-full sm:w-auto">
                    <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Laporan</label>
                    <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="dikonfirmasi" {{ $status === 'dikonfirmasi' ? 'selected' : '' }}>Dikonfirmasi</option>
                        <option value="belum_dikonfirmasi" {{ $status === 'belum_dikonfirmasi' ? 'selected' : '' }}>Belum Dikonfirmasi</option>
                        <option value="tidak_terlaksana" {{ $status === 'tidak_terlaksana' ? 'selected' : '' }}>Tidak Terlaksana</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="verifikasi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Verifikasi</label>
                    <select id="verifikasi" name="verifikasi" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Verifikasi</option>
                        <option value="menunggu" {{ $verifikasi === 'menunggu' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="diverifikasi" {{ $verifikasi === 'diverifikasi' ? 'selected' : '' }}>Diverifikasi</option>
                        <option value="perlu_revisi" {{ $verifikasi === 'perlu_revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                    </select>
                </div>
            @endif
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
            <a href="{{ route('admin.kehadiran.index', ['tab' => $tab]) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
        </form>
    </div>

    @if($tab === 'absensi')
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Instruktur</th>
                            <th class="px-4 py-3">Kelas &amp; Jadwal</th>
                            <th class="px-4 py-3">Check-in / Out</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Lokasi &amp; Sumber</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($absensis as $abs)
                        @php
                            $sumberLabels = [
                                'checkin_app' => 'Aplikasi',
                                'manual_admin' => 'Manual Admin',
                                'manual' => 'Manual',
                                'import' => 'Import',
                            ];
                            $sumberLabel = $sumberLabels[$abs->sumber] ?? $abs->sumber;
                            $koreksiData = [
                                'absensi_id' => $abs->id,
                                'instruktur_id' => $abs->instruktur_id,
                                'jadwal_id' => $abs->jadwal_id,
                                'tanggal' => $abs->tanggal ? $abs->tanggal->format('Y-m-d') : date('Y-m-d'),
                                'jam_checkin' => $abs->jam_checkin ? substr((string) $abs->jam_checkin, 0, 5) : '',
                                'jam_checkout' => $abs->jam_checkout ? substr((string) $abs->jam_checkout, 0, 5) : '',
                                'status' => $abs->status,
                                'keterangan' => $abs->keterangan ?? '',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $abs->tanggal->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $abs->instruktur?->nama_lengkap ?? '-' }}</p>
                                <p class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $abs->instruktur?->kode_instruktur ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $abs->jadwal?->nama_kelas ?? 'Input Manual / Impor' }}</p>
                                <p>Jadwal: {{ $abs->jam_mulai_jadwal ? substr($abs->jam_mulai_jadwal, 0, 5) : ($abs->jadwal ? substr($abs->jadwal->jam_mulai, 0, 5) : '-') }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                                {{ $abs->jam_checkin ? substr($abs->jam_checkin, 0, 5) : '-' }} s/d {{ $abs->jam_checkout ? substr($abs->jam_checkout, 0, 5) : 'Belum Check-out' }}
                                @if($abs->is_auto_checkout)
                                    <span class="block font-sans text-xs font-medium text-amber-700 dark:text-amber-300">Auto-Checkout Sistem</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $abs->status === 'hadir' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : ($abs->status === 'terlambat' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : ($abs->status === 'izin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300')) }}">
                                    {{ $abs->status_label }}
                                </span>
                                @if((int) $abs->menit_terlambat > 0)
                                    <span class="mt-0.5 block text-xs text-gray-600 dark:text-gray-300">Selisih: {{ $abs->menit_terlambat }} menit</span>
                                @endif
                            </td>
                            @php
                                $lokasiInfo    = $abs->lokasi_checkin_info;
                                $isNonAppInput = in_array($abs->sumber, ['manual', 'manual_admin', 'import', 'import_excel'], true);
                            @endphp
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                @if($lokasiInfo)
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($lokasiInfo['dalam_radius'])
                                            <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                Sesuai Lokasi (&plusmn; {{ $lokasiInfo['jarak_ringkas'] }})
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-950/60 dark:text-red-300">
                                                Di Luar Radius (&plusmn; {{ $lokasiInfo['jarak_ringkas'] }})
                                            </span>
                                        @endif
                                        <span class="text-gray-500 dark:text-gray-400">&middot; {{ $sumberLabel }}</span>
                                    </div>
                                    <a href="https://www.google.com/maps?q={{ $abs->latitude_checkin }},{{ $abs->longitude_checkin }}"
                                       target="_blank" rel="noopener"
                                       title="{{ $abs->latitude_checkin }}, {{ $abs->longitude_checkin }}"
                                       class="mt-1 inline-block font-medium text-brand-700 hover:underline dark:text-brand-300">
                                        Lihat Peta
                                    </a>
                                @elseif($isNonAppInput)
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Tanpa GPS &middot; {{ $sumberLabel }}</p>
                                @else
                                    <p class="font-medium text-amber-700 dark:text-amber-300">GPS Gagal &middot; {{ $sumberLabel }}</p>
                                @endif
                                @if($abs->keterangan)
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $abs->keterangan }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button"
                                        data-koreksi="{{ json_encode($koreksiData) }}"
                                        @click="openManualEdit(JSON.parse($el.dataset.koreksi))"
                                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Koreksi
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada data kehadiran pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($absensis->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $absensis->links() }}</div>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <th class="px-4 py-3">Tanggal &amp; Jam</th>
                            <th class="px-4 py-3">Instruktur &amp; Kelas</th>
                            <th class="px-4 py-3">Materi &amp; Bukti</th>
                            <th class="px-4 py-3">Ringkasan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($aktivitas as $akt)
                        @php
                            $kelasInfo    = $akt->status_kelas_info;
                            $eskalasiInfo = $akt->eskalasi_info;
                            $kelasBadgeCls = match ($kelasInfo['color']) {
                                'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                            };
                        @endphp
                        <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $akt->tanggal->format('d/m/Y') }}</p>
                                @if ($akt->jam_mulai_aktual || $akt->jam_selesai_aktual)
                                    <p class="mt-0.5">{{ $akt->jam_mulai_aktual ? substr($akt->jam_mulai_aktual, 0, 5) : '-' }} - {{ $akt->jam_selesai_aktual ? substr($akt->jam_selesai_aktual, 0, 5) : '-' }} WIB</p>
                                    @if ($akt->durasi_aktual_label && $akt->durasi_aktual_label !== '-')
                                        <p class="font-sans text-xs text-gray-600 dark:text-gray-300">Durasi: {{ $akt->durasi_aktual_label }}</p>
                                    @endif
                                @else
                                    <p class="mt-0.5 text-slate-400">-</p>
                                @endif
                                <p class="font-sans text-xs text-gray-600 dark:text-gray-300">
                                    Peserta:
                                    @if ($akt->jumlah_peserta_hadir !== null)
                                        {{ $akt->jumlah_peserta_hadir }} orang
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $akt->instruktur?->nama_lengkap ?? '-' }}</p>
                                <p class="text-gray-600 dark:text-gray-300">Kelas: {{ $akt->jadwal?->nama_kelas ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $akt->materi_disampaikan ?? '-' }}</p>
                                @if($akt->tautan_materi)
                                    <p class="mt-1">
                                        <a href="{{ $akt->tautan_materi }}" target="_blank" rel="noopener" class="font-medium text-brand-600 underline hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">Buka Modul</a>
                                    </p>
                                @endif
                                @if($akt->file_dokumentasi)
                                    <p class="mt-1">
                                        <a href="{{ $akt->dokumentasi_url }}" target="_blank" rel="noopener" class="font-medium text-emerald-700 underline hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-emerald-300">Lihat Bukti</a>
                                    </p>
                                @else
                                    <p class="mt-1">
                                        <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Tanpa Bukti</span>
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                @if($akt->catatan)
                                    <p><span class="font-medium text-gray-500 dark:text-gray-400">Ringkasan:</span> {{ $akt->catatan }}</p>
                                @endif
                                @if($akt->hasil_pembelajaran)
                                    <p class="mt-0.5"><span class="font-medium text-gray-500 dark:text-gray-400">Hasil:</span> {{ $akt->hasil_pembelajaran }}</p>
                                @endif
                                @if($akt->kendala_kegiatan)
                                    <p class="mt-0.5"><span class="font-medium text-gray-500 dark:text-gray-400">Kendala:</span> {{ $akt->kendala_kegiatan }}</p>
                                @endif
                                @if(!$akt->catatan && !$akt->kendala_kegiatan && !$akt->hasil_pembelajaran)
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 space-y-1 text-xs">
                                <span class="inline-flex rounded-md px-2.5 py-0.5 font-semibold {{ $kelasBadgeCls }}">
                                    {{ $kelasInfo['label'] }}
                                </span>
                                @if($akt->catatan_supervisor)
                                    <p class="text-xs text-gray-700 dark:text-gray-200">Catatan: {{ $akt->catatan_supervisor }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.kehadiran.verifikasi', $akt) }}" class="space-y-2">
                                    @csrf
                                    <select name="status_verifikasi" aria-label="Status verifikasi" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                        <option value="menunggu" {{ $akt->status_verifikasi === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                                        <option value="diverifikasi" {{ $akt->status_verifikasi === 'diverifikasi' ? 'selected' : '' }}>Diverifikasi</option>
                                        <option value="perlu_revisi" {{ $akt->status_verifikasi === 'perlu_revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                                    </select>
                                    <input type="text" name="catatan_supervisor" value="{{ $akt->catatan_supervisor }}" placeholder="Catatan verifikasi..."
                                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada jurnal aktivitas mengajar pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($aktivitas->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $aktivitas->links() }}</div>
            @endif
        </div>
    @endif

    <div x-show="manualModalOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="manual-modal-title" class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto bg-gray-900/60 p-4 sm:items-center">
        <div @click.outside="manualModalOpen = false" class="my-8 w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h2 id="manual-modal-title" class="text-base font-bold text-gray-900 dark:text-white" x-text="manualMode === 'edit' ? 'Koreksi Catatan Kehadiran' : 'Input Absensi Manual'">Input Absensi Manual</h2>
                <button type="button" @click="manualModalOpen = false" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-800" aria-label="Tutup dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.kehadiran.manual') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="absensi_id" :value="manualForm.absensi_id">
                <div>
                    <label for="manual_instruktur_id" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Instruktur <span class="text-red-600 dark:text-red-300">*</span></label>
                    <select id="manual_instruktur_id" name="instruktur_id" x-model="manualForm.instruktur_id" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih Instruktur</option>
                        @foreach($instrukturs as $ins)
                            <option value="{{ $ins->id }}">{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="manual_jadwal_id" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Sesi Jadwal (Opsional)</label>
                    <select id="manual_jadwal_id" name="jadwal_id" x-model="manualForm.jadwal_id" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Tanpa tautan jadwal spesifik</option>
                        @foreach($jadwalsList as $jdw)
                            <option value="{{ $jdw->id }}">{{ $jdw->nama_kelas }} - {{ $jdw->materi }} ({{ $jdw->tanggal_label }}, {{ substr($jdw->jam_mulai, 0, 5) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="manual_tanggal" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Tanggal <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="date" id="manual_tanggal" name="tanggal" x-model="manualForm.tanggal" required class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="manual_checkin" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jam Check-in</label>
                        <input type="time" id="manual_checkin" name="jam_checkin" x-model="manualForm.jam_checkin" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="manual_checkout" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jam Check-out</label>
                        <input type="time" id="manual_checkout" name="jam_checkout" x-model="manualForm.jam_checkout" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
                <div>
                    <label for="manual_status" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Status Kehadiran <span class="text-red-600 dark:text-red-300">*</span></label>
                    <select id="manual_status" name="status" x-model="manualForm.status"
                            @change="if (['izin', 'tidak_hadir'].includes(manualForm.status) && manualMode === 'create') { manualForm.jam_checkin = ''; manualForm.jam_checkout = ''; }"
                            required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="tidak_hadir">Tidak Hadir</option>
                    </select>
                </div>
                <div>
                    <label for="manual_keterangan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Keterangan Bypass Manual</label>
                    <input type="text" id="manual_keterangan" name="keterangan" x-model="manualForm.keterangan" placeholder="Contoh: Kendala jaringan perangkat instruktur saat check-in" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="manualModalOpen = false" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" x-text="manualMode === 'edit' ? 'Perbarui Kehadiran' : 'Simpan Kehadiran'">Simpan Kehadiran</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="aktivitasModalOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="aktivitas-modal-title" class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto bg-gray-900/60 p-4 sm:items-center">
        <div @click.outside="aktivitasModalOpen = false" class="my-8 w-full max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h2 id="aktivitas-modal-title" class="text-base font-bold text-gray-900 dark:text-white">Catat Aktivitas Mengajar</h2>
                <button type="button" @click="aktivitasModalOpen = false" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-800" aria-label="Tutup dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.kehadiran.aktivitas.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="akt_jadwal_id" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jadwal Kelas <span class="text-red-600 dark:text-red-300">*</span></label>
                    <select id="akt_jadwal_id" name="jadwal_id" x-model="aktivitasForm.jadwal_id" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih Jadwal Kelas</option>
                        @foreach($jadwalsList as $jdw)
                            <option value="{{ $jdw->id }}">{{ $jdw->nama_kelas }} - {{ $jdw->materi }} ({{ $jdw->instruktur?->nama_lengkap ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="akt_tanggal" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Tanggal Sesi <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="date" id="akt_tanggal" name="tanggal" x-model="aktivitasForm.tanggal" required class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="akt_jam_mulai" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jam Mulai Aktual <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="time" id="akt_jam_mulai" name="jam_mulai_aktual" x-model="aktivitasForm.jam_mulai" required class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="akt_jam_selesai" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jam Selesai Aktual <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="time" id="akt_jam_selesai" name="jam_selesai_aktual" x-model="aktivitasForm.jam_selesai" required class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="akt_materi" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Materi yang Disampaikan <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="text" id="akt_materi" name="materi_disampaikan" x-model="aktivitasForm.materi" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="akt_tautan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Tautan Modul / Materi (URL)</label>
                        <input type="url" id="akt_tautan" name="tautan_materi" placeholder="https://..." class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="akt_peserta" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Jumlah Peserta Hadir <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="number" id="akt_peserta" name="jumlah_peserta_hadir" value="20" min="0" max="1000" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="akt_konfirmasi" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Status Pelaksanaan <span class="text-red-600 dark:text-red-300">*</span></label>
                        <select id="akt_konfirmasi" name="status_konfirmasi" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="dikonfirmasi">Terlaksana &amp; Dikonfirmasi</option>
                            <option value="tidak_terlaksana">Tidak Terlaksana</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="akt_kendala" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Catatan Kendala Kegiatan</label>
                        <input type="text" id="akt_kendala" name="kendala_kegiatan" placeholder="Kendala teknis atau kelas (jika ada)" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="akt_hasil" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Hasil Pembelajaran</label>
                        <input type="text" id="akt_hasil" name="hasil_pembelajaran" placeholder="Capaian materi peserta" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
                <div>
                    <label for="akt_catatan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Ringkasan / Catatan Kegiatan</label>
                    <input type="text" id="akt_catatan" name="catatan" placeholder="Ringkasan pelaksanaan kelas (opsional)" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="akt_file" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Unggah Bukti Dokumentasi (Foto/PDF)</label>
                    <input type="file" id="akt_file" name="file_dokumentasi" accept=".pdf,image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 file:mr-3 file:min-h-[32px] file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-medium file:text-brand-700 hover:file:bg-brand-100 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-brand-950/60 dark:file:text-brand-300">
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="aktivitasModalOpen = false" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Simpan Aktivitas</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
