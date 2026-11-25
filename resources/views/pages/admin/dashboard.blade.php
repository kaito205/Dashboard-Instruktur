@extends('layouts.' . ($routePrefix ?? 'admin') . '.app')
@section('title', 'Dashboard')

@section('content')
@php
    $rp = $routePrefix ?? 'admin';
    $isSupervisorView = $rp === 'supervisor';
@endphp
<div class="space-y-6" x-data="{ periode: '{{ $selectedPeriod }}' }">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                Periode: <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} s.d. {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</span>
            </p>
        </div>

        <form method="GET" action="{{ route($rp . '.dashboard') }}" class="flex flex-wrap items-end gap-2.5">
            <div class="w-full sm:w-auto">
                <label for="filter-periode" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Periode</label>
                <select id="filter-periode" name="periode" x-model="periode"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="hari_ini" {{ $selectedPeriod === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="minggu_ini" {{ $selectedPeriod === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="bulan_ini" {{ $selectedPeriod === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="tahun_ini" {{ $selectedPeriod === 'tahun_ini' ? 'selected' : '' }}>Tahun Ini</option>
                    <option value="kustom" {{ $selectedPeriod === 'kustom' ? 'selected' : '' }}>Kustom</option>
                </select>
            </div>

            <div x-show="periode === 'kustom'" x-cloak class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                <div class="w-full sm:w-auto">
                    <label for="filter-dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input id="filter-dari" type="date" name="dari" value="{{ $dari }}"
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div class="w-full sm:w-auto">
                    <label for="filter-sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input id="filter-sampai" type="date" name="sampai" value="{{ $sampai }}"
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
            </div>

            <div class="w-full sm:w-auto">
                <label for="filter-instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
                <select id="filter-instruktur" name="instruktur"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="all">Semua Instruktur</option>
                    @foreach ($instructorsList as $inst)
                        <option value="{{ $inst->id }}" {{ (string) $selectedInstructor === (string) $inst->id ? 'selected' : '' }}>
                            {{ $inst->nama_lengkap }} ({{ $inst->kode_instruktur }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="filter-status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select id="filter-status" name="status"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <optgroup label="Kehadiran">
                        <option value="hadir" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="terlambat" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="izin" {{ $selectedStatus === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="tidak_hadir" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </optgroup>
                    <optgroup label="Jadwal">
                        <option value="terjadwal" {{ $selectedStatus === 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                        <option value="berlangsung" {{ $selectedStatus === 'berlangsung' ? 'selected' : '' }}>Sedang Berlangsung</option>
                        <option value="selesai" {{ $selectedStatus === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="dibatalkan" {{ $selectedStatus === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </optgroup>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Filter
                </button>
                <a href="{{ route($rp . '.dashboard') }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-5">
            <div class="bg-white p-4 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Instruktur Aktif</p>
                <p class="mt-1.5 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total_instruktur_aktif'] }}</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $metrics['total_instruktur_aktif_trend'] }}</p>
            </div>

            <div class="bg-white p-4 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Kelas Diampu</p>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total_kelas_diampu'] ?? $metrics['total_kelas'] }}</span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">kelas aktif</span>
                </div>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $metrics['total_kelas_diampu_trend'] ?? $metrics['total_kelas_trend'] }}</p>
            </div>

            <div class="bg-white p-4 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Jam Mengajar</p>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total_jam_mengajar'] }}</span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">jam</span>
                </div>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $metrics['total_jam_mengajar_trend'] }}</p>
            </div>

            <div class="bg-white p-4 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Rata-rata Kehadiran</p>
                <p class="mt-1.5 font-mono text-2xl font-bold {{ ($metrics['total_absensi'] ?? 0) > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-900 dark:text-white' }}">{{ $metrics['persentase_kehadiran'] }}%</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    Hadir {{ $metrics['hadir_tepat'] }} &middot; Terlambat {{ $metrics['hadir_terlambat'] }} &middot; Izin {{ $metrics['total_izin'] }} &middot; Tidak Hadir {{ $metrics['total_alpa'] }}
                </p>
                @if (!empty($metrics['status_filter_aktif']))
                    <p class="mt-1 text-xs font-medium text-brand-700 dark:text-brand-300">
                        Filter {{ $metrics['status_filter_label'] }}: {{ $metrics['status_filter_count'] }}/{{ $metrics['total_absensi'] }} sesi ({{ $metrics['status_filter_persentase'] }}%)
                    </p>
                @endif
            </div>

            <div class="bg-white p-4 sm:col-span-2 lg:col-span-1 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Rata-rata Penilaian</p>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['rata_rata_penilaian'] }}</span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">/ 100</span>
                </div>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $metrics['rata_rata_penilaian_trend'] }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Tren Aktivitas Mengajar</h2>
                <p class="text-xs text-gray-600 dark:text-gray-300">Perbandingan sesi terjadwal terhadap jurnal aktivitas terkonfirmasi pada periode berjalan</p>
            </div>
            <a href="{{ route($rp . '.kehadiran.index', ['tab' => 'aktivitas']) }}"
                class="inline-flex min-h-[44px] items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                Kelola Aktivitas
            </a>
        </div>
        @php
            $hasActivityChart = count($aktivitasChartData['labels'] ?? []) > 0
                && (array_sum($aktivitasChartData['terjadwal'] ?? []) > 0 || array_sum($aktivitasChartData['dikonfirmasi'] ?? []) > 0);
        @endphp
        <div class="flex h-64 items-center justify-center">
            @if ($hasActivityChart)
                <div class="h-full w-full" id="activityTrendChart"></div>
            @else
                <div class="px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada sesi terjadwal atau jurnal aktivitas pada periode ini.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-7 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Kehadiran per Periode</h2>
                <p class="text-xs text-gray-600 dark:text-gray-300">Klik batang untuk melihat detail kehadiran pada tanggal tersebut</p>
            </div>
            <div class="flex h-72 items-center justify-center">
                @if (count($chartData['labels']) > 0 && array_sum($chartData['total']) > 0)
                    <div class="h-full w-full" id="attendanceChart"></div>
                @else
                    <div class="px-4 py-8 text-center">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada data kehadiran pada periode ini.</p>
                        <a href="{{ route($rp . '.kehadiran.index') }}"
                            class="mt-3 inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Kelola Kehadiran
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Rata-rata Evaluasi per Instruktur</h2>
            <div class="flex h-72 items-center justify-center">
                @if (count($evaluasiChartData['labels']) > 0)
                    <div class="h-full w-full" id="evaluationChart"></div>
                @else
                    <div class="px-4 py-8 text-center">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada hasil evaluasi pada periode ini.</p>
                        <a href="{{ route($rp . '.evaluasi.kode.index') }}"
                            class="mt-3 inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Kelola Kode Evaluasi
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Peringkat Instruktur</h2>
                <a href="{{ route($rp . '.kinerja.index') }}"
                    class="inline-flex min-h-[44px] items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                    Detail Kinerja
                </a>
            </div>
            <div class="mt-2 divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($leaderboardInstruktur ?? [] as $idx => $leader)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-mono text-xs font-bold {{ $idx === 0 ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' }}">
                                #{{ $idx + 1 }}
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route($rp . '.instruktur.show', $leader['instruktur']) }}" class="block truncate text-xs font-semibold text-gray-900 hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">
                                    {{ $leader['instruktur']->nama_lengkap }}
                                </a>
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $leader['total_jadwal'] }} sesi ({{ $leader['jam_mengajar'] }} jam) &middot; Kehadiran {{ $leader['persentase_kehadiran'] }}%
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">
                                {{ $leader['skor_akhir'] !== null ? number_format((float) $leader['skor_akhir'], 2) : '-' }}
                            </span>
                            <span class="block text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                {{ $leader['kategori_label'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-gray-600 dark:text-gray-300">Belum ada data peringkat pada periode ini.</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-7 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3 dark:border-gray-800">
                <div class="flex flex-wrap items-center gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Peringatan Operasional</h2>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            Total <span data-alert-count class="font-mono font-semibold text-gray-900 dark:text-white">{{ $alerts['dashboard_total_peringatan'] ?? $alerts['total_peringatan'] ?? 0 }}</span> item perlu tinjauan
                        </p>
                    </div>
                    @if (($alerts['dashboard_total_info'] ?? $alerts['total_info'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                            {{ $alerts['dashboard_total_info'] ?? $alerts['total_info'] }} pengingat jadwal
                        </span>
                    @endif
                </div>
                <a href="{{ route($rp . '.notifikasi.index') }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                    Lihat Semua
                </a>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/50">
                    <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Jurnal &amp; Presensi Tertunda</p>
                    <p class="mt-0.5 font-mono text-lg font-bold text-gray-900 dark:text-white">{{ $alertCategorySummary['kelas_tanpa_laporan'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/50">
                    <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Sertifikasi Perlu Pembaruan</p>
                    <p class="mt-0.5 font-mono text-lg font-bold text-gray-900 dark:text-white">{{ $alertCategorySummary['sertifikasi_hampir_expired'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/50">
                    <p class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Instruktur Di Bawah Standar</p>
                    <p class="mt-0.5 font-mono text-lg font-bold text-gray-900 dark:text-white">{{ $alertCategorySummary['instruktur_dibawah_standar'] ?? 0 }}</p>
                </div>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 font-semibold">Kategori</th>
                            <th class="px-3 py-2.5 font-semibold">Ringkasan</th>
                            <th class="px-3 py-2.5 font-semibold">Info</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse (($alerts['dashboard_items'] ?? $alerts['items_aktif'] ?? collect())->take(5) as $alertItem)
                            <tr data-alert-row="{{ $alertItem['alert_key'] }}" data-alert-level="{{ $alertItem['level'] }}">
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @if (($alertItem['level'] ?? '') === 'info')
                                        <span class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600"></span>
                                            Info {{ $alertItem['kategori'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 font-medium text-gray-800 dark:text-gray-200">
                                            <span class="inline-flex h-2 w-2 shrink-0 rounded-full {{ $alertItem['level'] === 'critical' ? 'bg-red-600' : 'bg-amber-500' }}"></span>
                                            {{ $alertItem['kategori'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 font-semibold text-gray-900 dark:text-white">{{ $alertItem['judul'] }}</td>
                                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300">{{ $alertItem['ringkasan'] }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    <a href="{{ $alertItem['url'] }}" class="inline-flex min-h-[44px] items-center font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">{{ $alertItem['cta_label'] ?? 'Tindak Lanjuti' }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-600 dark:text-gray-300">
                                    Tidak ada peringatan operasional saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-2 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ringkasan Kinerja Instruktur</h2>
                @if (!$isKinerjaRuleConfirmed)
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Aturan pembobotan kinerja belum dikonfirmasi di Pengaturan.</p>
                @endif
            </div>
            <a href="{{ route($rp . '.kinerja.index') }}"
                class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Monitoring Kinerja
            </a>
        </div>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Instruktur</th>
                        <th class="px-4 py-3 text-center font-semibold">Jadwal &amp; Jam</th>
                        <th class="px-4 py-3 text-center font-semibold">Kehadiran</th>
                        <th class="px-4 py-3 text-center font-semibold">Evaluasi</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($kinerjaInstruktur as $item)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item['instruktur']->foto_url }}" alt="{{ $item['instruktur']->nama_lengkap }}" class="h-8 w-8 shrink-0 rounded-full object-cover">
                                    <div>
                                        <a href="{{ route($rp . '.instruktur.show', $item['instruktur']) }}"
                                            class="font-semibold text-gray-900 hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">
                                            {{ $item['instruktur']->nama_lengkap }}
                                        </a>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $item['instruktur']->kode_instruktur }} &middot; {{ $item['instruktur']->bidang_keahlian ?? 'Umum' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-medium text-gray-800 dark:text-gray-200">
                                {{ $item['total_jadwal'] }} sesi ({{ $item['jam_mengajar'] }} jam)
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if (($item['total_absensi'] ?? 0) > 0)
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $item['persentase_kehadiran'] }}%</span>
                                    <span class="block text-gray-600 dark:text-gray-300">({{ $item['total_hadir'] }}/{{ $item['total_absensi'] }} sesi)</span>
                                    @if (!empty($item['status_filter_aktif']))
                                        <span class="block font-medium text-brand-700 dark:text-brand-300">{{ $item['status_filter_label'] }}: {{ $item['status_filter_count'] }}/{{ $item['total_absensi'] }} ({{ $item['status_filter_persentase'] }}%)</span>
                                    @endif
                                @else
                                    <span class="font-semibold text-gray-900 dark:text-white">0%</span>
                                    <span class="block text-gray-600 dark:text-gray-300">(0 sesi)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($item['rata_rata_nilai'] !== null)
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($item['rata_rata_nilai'], 2) }}</span>
                                    <span class="block text-gray-600 dark:text-gray-300">({{ $item['total_evaluasi'] }} responden)</span>
                                @else
                                    <span class="text-gray-600 dark:text-gray-300">Belum ada data</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $katBadge = match ($item['kategori']) {
                                        'sangat_baik' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                        'baik' => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
                                        'cukup' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                        'kurang' => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $katBadge }}">
                                    {{ $item['kategori_label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-600 dark:text-gray-300">
                                @if (!empty($metrics['status_filter_aktif']))
                                    Tidak ada instruktur dengan status {{ $metrics['status_filter_label'] }} pada periode ini.
                                @elseif (!$isSupervisorView)
                                    Belum ada data instruktur aktif. <a href="{{ route('admin.instruktur.create') }}" class="font-semibold text-brand-700 underline dark:text-brand-300">Tambah instruktur baru</a>.
                                @else
                                    Belum ada data instruktur aktif.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Absensi Terbaru</h2>
                <a href="{{ route($rp . '.kehadiran.index') }}"
                    class="inline-flex min-h-[44px] items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                    Kelola Kehadiran
                </a>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 font-semibold">Tanggal</th>
                            <th class="px-3 py-2.5 font-semibold">Instruktur</th>
                            <th class="px-3 py-2.5 font-semibold">Jam</th>
                            <th class="px-3 py-2.5 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($recentAbsensi as $abs)
                            <tr>
                                <td class="px-3 py-2.5 font-mono text-gray-700 dark:text-gray-200">{{ $abs->tanggal->format('d/m/Y') }}</td>
                                <td class="px-3 py-2.5 font-semibold text-gray-900 dark:text-white">{{ $abs->instruktur?->nama_lengkap ?? '-' }}</td>
                                <td class="px-3 py-2.5 font-mono text-gray-700 dark:text-gray-300">
                                    {{ $abs->jam_checkin ? substr($abs->jam_checkin, 0, 5) : '-' }} - {{ $abs->jam_checkout ? substr($abs->jam_checkout, 0, 5) : '-' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                                        {{ $abs->status === 'hadir' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : ($abs->status === 'terlambat' ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : ($abs->status === 'izin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300')) }}">
                                        {{ $abs->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-600 dark:text-gray-300">
                                    Belum ada catatan absensi pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Jadwal Hari Ini</h2>
                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}</p>
                </div>
                @if (!$isSupervisorView)
                    <a href="{{ route('admin.jadwal.create') }}"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Tambah Jadwal
                    </a>
                @else
                    <a href="{{ route('supervisor.jadwal.index') }}"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Lihat Jadwal
                    </a>
                @endif
            </div>

            <div class="mt-2 divide-y divide-gray-200 dark:divide-gray-800">
                @php
                    $nowWibDash = now('Asia/Jakarta');
                    $bukaSebelumMenitDash = (int) \App\Models\Setting::get('checkin_buka_sebelum_menit', '30');
                @endphp
                @forelse ($todaySchedule as $jadwal)
                    @php
                        $liveStatus = $todayScheduleStatusMap[$jadwal->id] ?? [
                            'label' => $jadwal->status_label,
                            'color' => $jadwal->status_color ?? 'gray',
                            'code' => $jadwal->status,
                        ];
                        $jamMulaiDash = substr((string) ($jadwal->jam_mulai ?? '00:00'), 0, 5);
                        $waktuBukaDash = \Carbon\Carbon::parse($nowWibDash->toDateString() . ' ' . $jamMulaiDash . ':00', 'Asia/Jakarta')->subMinutes(max(0, $bukaSebelumMenitDash));
                        $isBelumDimulaiDash = (($liveStatus['code'] ?? '') === 'belum_checkin') && $nowWibDash->lt($waktuBukaDash);
                        $displayStatusLabelDash = $isBelumDimulaiDash ? 'Belum Dimulai' : ($liveStatus['label'] ?? '-');
                        $badgeClass = $isBelumDimulaiDash
                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                            : match ($liveStatus['color'] ?? 'gray') {
                                'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'red' => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                'amber' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                'gray' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                default => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
                            };
                    @endphp
                    <div class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }} WIB
                                </span>
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ $displayStatusLabelDash }}
                                </span>
                            </div>
                            <h3 class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $jadwal->nama_kelas }} {{ $jadwal->materi ? '- ' . $jadwal->materi : '' }}
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $jadwal->instruktur?->nama_lengkap ?? '-' }}@if ($jadwal->ruangan) &middot; {{ $jadwal->ruangan_label }}@endif
                            </p>
                        </div>
                        @if (!$isSupervisorView)
                            <a href="{{ route('admin.jadwal.edit', $jadwal) }}"
                                class="inline-flex min-h-[44px] shrink-0 items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                                Edit
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-600 dark:text-gray-300">
                        Tidak ada jadwal mengajar untuk hari ini.
                        @if (count($recentActivities) > 0)
                            <div class="mt-4 border-t border-gray-200 pt-3 text-left dark:border-gray-800">
                                <p class="mb-2 font-semibold text-gray-800 dark:text-gray-200">Aktivitas Terakhir:</p>
                                @foreach ($recentActivities->take(3) as $akt)
                                    <div class="flex items-center justify-between py-1">
                                        <span>{{ $akt->tanggal->format('d/m/Y') }} - {{ $akt->instruktur?->nama_lengkap ?? '-' }} ({{ $akt->jadwal?->nama_kelas ?? '-' }})</span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $akt->status_label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if (!$isSupervisorView)
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Riwayat Import Terbaru</h2>
            <a href="{{ route('admin.import.index') }}"
                class="inline-flex min-h-[44px] items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                Kelola Import
            </a>
        </div>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <tr>
                        <th class="px-3 py-2.5 font-semibold">Tanggal</th>
                        <th class="px-3 py-2.5 font-semibold">Berkas</th>
                        <th class="px-3 py-2.5 font-semibold">Jenis</th>
                        <th class="px-3 py-2.5 font-semibold">Hasil Baris</th>
                        <th class="px-3 py-2.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($recentImports as $import)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-3 py-2.5 font-mono text-gray-700 dark:text-gray-200">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                            <td class="max-w-xs truncate px-3 py-2.5 font-medium text-gray-900 dark:text-white" title="{{ $import->nama_file }}">{{ $import->nama_file }}</td>
                            <td class="px-3 py-2.5 font-medium uppercase text-gray-700 dark:text-gray-200">{{ $import->jenis }}</td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300">
                                <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $import->baris_berhasil }} berhasil</span> /
                                <span class="font-semibold text-red-700 dark:text-red-300">{{ $import->baris_gagal }} gagal</span>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                                    {{ $import->status === 'sukses' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : ($import->status === 'parsial' ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300') }}">
                                    {{ $import->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-600 dark:text-gray-300">
                                Belum ada riwayat import berkas Excel/CSV.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
@php
    $hasActivityChartScript = count($aktivitasChartData['labels'] ?? []) > 0
        && (array_sum($aktivitasChartData['terjadwal'] ?? []) > 0 || array_sum($aktivitasChartData['dikonfirmasi'] ?? []) > 0);
@endphp
@if ((count($chartData['labels']) > 0 && array_sum($chartData['total']) > 0) || count($evaluasiChartData['labels']) > 0 || $hasActivityChartScript)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        const labelColor = isDark ? '#98a2b3' : '#475467';
        const titleColor = isDark ? '#d0d5dd' : '#344054';
        const gridColor = isDark ? '#1d2939' : '#e4e7ec';

        @if (count($chartData['labels']) > 0 && array_sum($chartData['total']) > 0)
        const attendanceEl = document.querySelector("#attendanceChart");
        if (attendanceEl && typeof ApexCharts !== 'undefined') {
            const attendanceLabels = @json($chartData['labels'] ?? []);
            const attendanceTooltipLabels = @json($chartData['tooltip_labels'] ?? ($chartData['labels'] ?? []));
            const attendanceBucketRanges = @json($chartData['bucket_ranges'] ?? []);
            const kehadiranBaseUrl = @json(route($rp . '.kehadiran.index'));
            const selectedInstrukturFilter = @json($selectedInstructor !== 'all' ? (string) $selectedInstructor : '');
            const attendanceGranularity = @json($chartData['granularity'] ?? 'harian');
            const attendanceSeriesLabel = @json($chartData['series_label'] ?? 'Hadir');
            const attendanceSeriesColor = @json($chartData['series_color'] ?? '#10B981');
            const attendanceHadir = @json($chartData['hadir'] ?? []);
            const attendanceTotal = @json($chartData['total'] ?? []);
            const attendanceStacked = Boolean(@json($chartData['stacked'] ?? true));
            const rawSeriesList = @json($chartData['series'] ?? []);
            const attendanceSeriesList = rawSeriesList.length > 0
                ? rawSeriesList
                : [
                    { name: attendanceSeriesLabel, color: attendanceSeriesColor, data: attendanceHadir },
                    { name: 'Total Pertemuan', color: '#98a2b3', data: attendanceTotal }
                ];
            const totalPoints = attendanceLabels.length;
            const attendanceKey = JSON.stringify({ isDark, attendanceStacked, attendanceLabels, attendanceSeriesList });

            if (!(attendanceEl.dataset.renderedKey === attendanceKey && attendanceEl.childElementCount > 0)) {
                if (window.__adminAttendanceChartInstance && typeof window.__adminAttendanceChartInstance.destroy === 'function') {
                    try { window.__adminAttendanceChartInstance.destroy(); } catch (e) {}
                    window.__adminAttendanceChartInstance = null;
                }
                attendanceEl.innerHTML = '';
                attendanceEl.dataset.renderedKey = attendanceKey;

                let labelStep = 1;
                if (attendanceGranularity === 'bulanan') {
                    labelStep = totalPoints > 12 ? Math.ceil(totalPoints / 6) : 1;
                } else if (totalPoints > 7 && totalPoints <= 15) {
                    labelStep = 2;
                } else if (totalPoints > 15 && totalPoints <= 31) {
                    labelStep = 5;
                } else if (totalPoints > 31) {
                    labelStep = Math.ceil(totalPoints / 6);
                }

                const minEndGap = Math.max(1, Math.floor(labelStep * 0.6));
                const maxAttendance = Math.max(...attendanceTotal, 1);

                window.__adminAttendanceChartInstance = new ApexCharts(attendanceEl, {
                    series: attendanceSeriesList.map((s) => ({ name: s.name, data: s.data || [] })),
                    chart: {
                        type: 'bar',
                        stacked: attendanceStacked,
                        height: 276,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        fontFamily: 'inherit',
                        events: {
                            dataPointSelection: function (event, chartContext, config) {
                                const idx = config?.dataPointIndex;
                                const range = (typeof idx === 'number' && attendanceBucketRanges[idx]) ? attendanceBucketRanges[idx] : null;
                                if (!range) return;
                                const url = new URL(kehadiranBaseUrl, window.location.origin);
                                url.searchParams.set('tab', 'absensi');
                                url.searchParams.set('dari', range.dari);
                                url.searchParams.set('sampai', range.sampai);
                                if (selectedInstrukturFilter !== '') {
                                    url.searchParams.set('instruktur', selectedInstrukturFilter);
                                }
                                window.location.href = url.toString();
                            }
                        }
                    },
                    colors: attendanceSeriesList.map((s) => s.color || '#033DF6'),
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            borderRadiusApplication: 'end',
                            columnWidth: totalPoints <= 7 ? '42%' : '56%'
                        }
                    },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    padding: { top: 8, right: 22, bottom: 8, left: 14 }
                },
                xaxis: {
                    categories: attendanceLabels,
                    axisBorder: { show: true, color: gridColor },
                    axisTicks: { show: true, color: gridColor },
                    labels: {
                        rotate: 0,
                        rotateAlways: false,
                        hideOverlappingLabels: false,
                        trim: false,
                        style: { colors: labelColor, fontSize: '11px' },
                        formatter: function (val) {
                            const idx = attendanceLabels.indexOf(val);
                            if (idx === -1 || labelStep <= 1) return val;
                            if (idx === 0 || idx === totalPoints - 1) return val;
                            if (idx % labelStep === 0 && (totalPoints - 1 - idx) >= minEndGap) return val;
                            return '';
                        }
                    },
                    title: {
                        text: attendanceGranularity === 'bulanan' ? 'Bulan' : 'Tanggal',
                        offsetY: 4,
                        style: { color: titleColor, fontSize: '11px', fontWeight: 500 }
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    min: 0,
                    max: maxAttendance <= 5 ? maxAttendance + 1 : undefined,
                    tickAmount: maxAttendance <= 5 ? maxAttendance + 1 : 5,
                    forceNiceScale: true,
                    decimalsInFloat: 0,
                    labels: {
                        style: { colors: labelColor, fontSize: '11px' },
                        formatter: (val) => Number.isFinite(Number(val)) ? String(Math.round(Number(val))) : ''
                    },
                    title: {
                        text: 'Jumlah Sesi',
                        style: { color: titleColor, fontSize: '11px', fontWeight: 500 }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    offsetY: -4,
                    itemMargin: { horizontal: 14, vertical: 6 },
                    labels: { colors: labelColor }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    x: {
                        formatter: function (val, opts) {
                            const idx = opts?.dataPointIndex;
                            return (typeof idx === 'number' && attendanceTooltipLabels[idx])
                                ? attendanceTooltipLabels[idx]
                                : val;
                        }
                    },
                    y: {
                        formatter: function (val, opts) {
                            const count = Math.round(Number(val || 0));
                            const sIdx = opts?.seriesIndex;
                            const dIdx = opts?.dataPointIndex;
                            const names = (typeof sIdx === 'number' && typeof dIdx === 'number')
                                ? (attendanceSeriesList[sIdx]?.names?.[dIdx] || '')
                                : '';
                            return (count > 0 && names !== '')
                                ? `${count} sesi (${names})`
                                : `${count} sesi`;
                        }
                    }
                },
                dataLabels: { enabled: false }
                });
                window.__adminAttendanceChartInstance.render();
            }
        }
        @endif

        @if (count($evaluasiChartData['labels']) > 0)
        const evalEl = document.querySelector("#evaluationChart");
        if (evalEl && typeof ApexCharts !== 'undefined') {
            new ApexCharts(evalEl, {
                series: [{
                    name: 'Rata-rata Nilai Evaluasi',
                    data: @json($evaluasiChartData['averages'])
                }],
                chart: {
                    type: 'bar',
                    height: 276,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                colors: ['#033DF6'],
                plotOptions: {
                    bar: { borderRadius: 6, horizontal: true, barHeight: '55%' }
                },
                grid: { borderColor: gridColor },
                xaxis: {
                    categories: @json($evaluasiChartData['labels']),
                    max: 100,
                    labels: { style: { colors: labelColor, fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { colors: labelColor, fontSize: '11px' } }
                },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                dataLabels: { enabled: true }
            }).render();
        }
        @endif

        @if ($hasActivityChartScript)
        const activityTrendEl = document.querySelector("#activityTrendChart");
        if (activityTrendEl && typeof ApexCharts !== 'undefined') {
            const actLabels = @json($aktivitasChartData['labels'] ?? []);
            const actTooltipLabels = @json($aktivitasChartData['tooltip_labels'] ?? []);
            const actTerjadwal = @json($aktivitasChartData['terjadwal'] ?? []);
            const actDikonfirmasi = @json($aktivitasChartData['dikonfirmasi'] ?? []);
            const actGranularity = @json($aktivitasChartData['granularity'] ?? 'harian');
            const actTotalPoints = actLabels.length;

            let actStep = 1;
            if (actGranularity === 'bulanan') {
                actStep = actTotalPoints > 12 ? Math.ceil(actTotalPoints / 6) : 1;
            } else if (actTotalPoints > 7 && actTotalPoints <= 15) {
                actStep = 2;
            } else if (actTotalPoints > 15 && actTotalPoints <= 31) {
                actStep = 5;
            } else if (actTotalPoints > 31) {
                actStep = Math.ceil(actTotalPoints / 6);
            }
            const actMinGap = Math.max(1, Math.floor(actStep * 0.6));

            if (window.__adminActivityTrendChartInstance && typeof window.__adminActivityTrendChartInstance.destroy === 'function') {
                try { window.__adminActivityTrendChartInstance.destroy(); } catch (e) {}
                window.__adminActivityTrendChartInstance = null;
            }
            activityTrendEl.innerHTML = '';

            window.__adminActivityTrendChartInstance = new ApexCharts(activityTrendEl, {
                series: [{
                    name: 'Sesi Terjadwal',
                    data: actTerjadwal
                }, {
                    name: 'Jurnal Terkonfirmasi',
                    data: actDikonfirmasi
                }],
                chart: {
                    type: 'area',
                    height: 248,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit'
                },
                colors: ['#667085', '#033DF6'],
                stroke: {
                    curve: 'smooth',
                    width: [2, 2.5]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: isDark ? 0.28 : 0.22,
                        opacityTo: 0.02,
                        stops: [0, 95, 100]
                    }
                },
                markers: {
                    size: 3,
                    strokeWidth: 2,
                    strokeColors: isDark ? '#101828' : '#ffffff',
                    hover: {
                        size: 6,
                        sizeOffset: 2
                    }
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    padding: { top: 8, right: 18, bottom: 6, left: 10 }
                },
                xaxis: {
                    categories: actLabels,
                    axisBorder: { show: true, color: gridColor },
                    axisTicks: { show: true, color: gridColor },
                    labels: {
                        rotate: 0,
                        hideOverlappingLabels: false,
                        trim: false,
                        style: { colors: labelColor, fontSize: '11px' },
                        formatter: function (val) {
                            const idx = actLabels.indexOf(val);
                            if (idx === -1 || actStep <= 1) return val;
                            if (idx === 0 || idx === actTotalPoints - 1) return val;
                            if (idx % actStep === 0 && (actTotalPoints - 1 - idx) >= actMinGap) return val;
                            return '';
                        }
                    }
                },
                yaxis: {
                    min: 0,
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
                    labels: { colors: labelColor }
                },
                dataLabels: { enabled: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    x: {
                        formatter: function (val, opts) {
                            const idx = opts?.dataPointIndex;
                            return (typeof idx === 'number' && actTooltipLabels[idx]) ? actTooltipLabels[idx] : val;
                        }
                    },
                    y: { formatter: (val) => `${Math.round(Number(val))} sesi` }
                }
            });
            window.__adminActivityTrendChartInstance.render();
        }
        @endif
    });
</script>
@endif
@endpush
