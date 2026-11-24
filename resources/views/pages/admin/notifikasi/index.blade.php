@extends('layouts.admin.app')
@section('title', 'Pusat Notifikasi Operasional')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pusat Notifikasi</h1>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                Total <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $alerts['total_peringatan'] ?? 0 }}</span> item perlu tinjauan
                @if (($alerts['total_info'] ?? 0) > 0)
                    &middot; <span class="font-medium text-brand-700 dark:text-brand-300">{{ $alerts['total_info'] }} pengingat jadwal</span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (($alerts['total_semua_aktif'] ?? $alerts['total_peringatan'] ?? 0) > 0)
                <form method="POST" action="{{ route('admin.notifikasi.tinjau-semua') }}">
                    @csrf
                    <input type="hidden" name="dari" value="{{ $dari }}">
                    <input type="hidden" name="sampai" value="{{ $sampai }}">
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.kehadiran.index', ['tab' => 'aktivitas']) }}"
               class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Kelola Kehadiran
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 lg:grid-cols-4 dark:border-gray-800 dark:bg-gray-800">
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Perlu Tinjauan</p>
            <p class="mt-1 font-mono text-2xl font-semibold {{ ($alerts['total_peringatan'] ?? 0) > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-900 dark:text-white' }}">
                {{ $alerts['total_peringatan'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                @if (($alerts['total_info'] ?? 0) > 0)
                    + {{ $alerts['total_info'] }} pengingat jadwal
                @else
                    Peringatan operasional aktif
                @endif
            </p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Belum Mengisi Jurnal</p>
            <p class="mt-1 font-mono text-2xl font-semibold text-red-700 dark:text-red-300">{{ count($alerts['kelas_tanpa_laporan'] ?? []) }}</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Sesi terlewat tanpa laporan</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Sertifikasi &amp; Check-out</p>
            <p class="mt-1 font-mono text-2xl font-semibold text-gray-900 dark:text-white">
                {{ count($alerts['sertifikasi_expired'] ?? []) + count($alerts['belum_checkout'] ?? []) }}
            </p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Masa berlaku &amp; sesi terbuka</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Sudah Ditinjau</p>
            <p class="mt-1 font-mono text-2xl font-semibold text-gray-900 dark:text-white">{{ $alerts['total_riwayat'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Riwayat selesai ditinjau</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
            <a href="{{ route('admin.notifikasi.index', ['tab' => 'aktif', 'dari' => $dari, 'sampai' => $sampai]) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'aktif' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Aktif ({{ $alerts['total_semua_aktif'] ?? $alerts['total_peringatan'] ?? 0 }})
            </a>
            <a href="{{ route('admin.notifikasi.index', ['tab' => 'riwayat', 'dari' => $dari, 'sampai' => $sampai]) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'riwayat' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Sudah Ditinjau ({{ $alerts['total_riwayat'] ?? 0 }})
            </a>
        </div>

        @if ($tab === 'aktif')
            <div class="flex flex-wrap items-center gap-1.5">
                @php
                    $levelFilters = [
                        'semua'    => 'Semua',
                        'critical' => 'Prioritas Tinggi',
                        'warning'  => 'Peringatan',
                        'info'     => 'Info',
                    ];
                @endphp
                @foreach ($levelFilters as $key => $label)
                    <a href="{{ route('admin.notifikasi.index', ['tab' => 'aktif', 'level' => $key, 'dari' => $dari, 'sampai' => $sampai]) }}"
                       class="inline-flex min-h-[44px] items-center rounded-lg border px-3 py-2 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $level === $key ? 'border-brand-600 bg-brand-50 font-semibold text-brand-700 dark:border-brand-500 dark:bg-brand-950/50 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @if ($tab === 'aktif')
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($activeList as $item)
                    @php
                        $dotCls = $item['level'] === 'critical' ? 'bg-red-600' : ($item['level'] === 'warning' ? 'bg-amber-500' : 'bg-brand-600');
                        $dotLabel = $item['level'] === 'critical' ? 'Prioritas Tinggi' : ($item['level'] === 'warning' ? 'Peringatan' : 'Informasi');
                    @endphp
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-1.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $dotCls }}" title="{{ $dotLabel }}">
                                <span class="sr-only">{{ $dotLabel }}</span>
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['judul'] }}</p>
                                    @if (($item['level'] ?? '') === 'info')
                                        <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            Info {{ $item['kategori'] }}
                                        </span>
                                    @else
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item['kategori'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($item['ringkasan']))
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $item['ringkasan'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ $item['url'] }}"
                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Buka
                            </a>

                            <form method="POST" action="{{ route('admin.notifikasi.tinjau') }}">
                                @csrf
                                <input type="hidden" name="alert_key" value="{{ $item['alert_key'] }}">
                                <input type="hidden" name="dari" value="{{ $dari }}">
                                <input type="hidden" name="sampai" value="{{ $sampai }}">
                                <input type="hidden" name="kategori" value="{{ $item['kategori'] }}">
                                <input type="hidden" name="level" value="{{ $item['level'] }}">
                                <input type="hidden" name="judul" value="{{ $item['judul'] }}">
                                <input type="hidden" name="ringkasan" value="{{ $item['ringkasan'] }}">
                                <input type="hidden" name="url" value="{{ $item['url'] }}">
                                <button type="submit"
                                    class="inline-flex min-h-[44px] items-center rounded-lg bg-gray-100 px-3.5 py-2 text-xs font-semibold text-gray-900 transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                                    Tandai selesai
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $level === 'semua' ? 'Tidak ada notifikasi aktif saat ini.' : 'Tidak ada notifikasi pada kategori ini.' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            Semua peringatan yang sudah ditinjau tersimpan pada tab Riwayat.
                        </p>
                        @if (($alerts['total_riwayat'] ?? 0) > 0)
                            <a href="{{ route('admin.notifikasi.index', ['tab' => 'riwayat', 'dari' => $dari, 'sampai' => $sampai]) }}"
                               class="mt-4 inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                                Lihat Riwayat Ditinjau ({{ $alerts['total_riwayat'] }})
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($alerts['items_riwayat'] ?? [] as $item)
                    @php
                        $dotCls = $item['level'] === 'critical' ? 'bg-red-600' : ($item['level'] === 'warning' ? 'bg-amber-500' : 'bg-brand-600');
                        $dotLabel = $item['level'] === 'critical' ? 'Mendesak / Eskalasi' : ($item['level'] === 'warning' ? 'Peringatan' : 'Informasi');
                        $subline = collect([
                            $item['ringkasan'] ?? '',
                            ! empty($item['ditinjau_pada_label']) ? 'Ditinjau ' . $item['ditinjau_pada_label'] : '',
                        ])->filter()->implode(' · ');
                    @endphp
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-1.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $dotCls }}" title="{{ $dotLabel }}">
                                <span class="sr-only">{{ $dotLabel }}</span>
                            </span>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['judul'] }}</p>
                                @if ($subline !== '')
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $subline }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if (!empty($item['url']))
                                <a href="{{ $item['url'] }}"
                                   class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Buka
                                </a>
                            @endif

                            @if ($item['masih_aktif'])
                                <form method="POST" action="{{ route('admin.notifikasi.kembalikan') }}">
                                    @csrf
                                    <input type="hidden" name="alert_key" value="{{ $item['alert_key'] }}">
                                    <input type="hidden" name="dari" value="{{ $dari }}">
                                    <input type="hidden" name="sampai" value="{{ $sampai }}">
                                    <button type="submit"
                                        class="inline-flex min-h-[44px] items-center rounded-lg bg-gray-100 px-3.5 py-2 text-xs font-semibold text-gray-900 transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                                        Kembalikan
                                    </button>
                                </form>
                            @else
                                <span class="inline-flex min-h-[44px] items-center px-2 text-xs text-gray-600 dark:text-gray-300">Kondisi selesai</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum ada notifikasi yang ditandai selesai.</p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Notifikasi yang Anda tinjau akan tercatat di halaman ini.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection
