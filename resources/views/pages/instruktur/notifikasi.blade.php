@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pusat Notifikasi</h1>
            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                Pemberitahuan jadwal mengajar, presensi, jurnal aktivitas, dan masa berlaku sertifikasi Anda.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (($alerts['total_peringatan'] ?? 0) > 0)
                <form method="POST" action="{{ route('instruktur.notifikasi.tinjau-semua') }}">
                    @csrf
                    <input type="hidden" name="dari" value="{{ $dari }}">
                    <input type="hidden" name="sampai" value="{{ $sampai }}">
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            @endif

            <a href="{{ route('instruktur.jadwal.index') }}"
               class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Jadwal &amp; Presensi
            </a>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
            <a href="{{ route('instruktur.notifikasi.index', ['tab' => 'aktif', 'dari' => $dari, 'sampai' => $sampai]) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'aktif' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Aktif ({{ $alerts['total_peringatan'] ?? 0 }})
            </a>
            <a href="{{ route('instruktur.notifikasi.index', ['tab' => 'riwayat', 'dari' => $dari, 'sampai' => $sampai]) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-4 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $tab === 'riwayat' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Sudah Dibaca ({{ $alerts['total_riwayat'] ?? 0 }})
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
                    <a href="{{ route('instruktur.notifikasi.index', ['tab' => 'aktif', 'level' => $key, 'dari' => $dari, 'sampai' => $sampai]) }}"
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
                                @if (!empty($item['kategori']))
                                    <span class="mb-0.5 inline-block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ $item['kategori'] }}</span>
                                @endif
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['judul'] }}</p>
                                @if (!empty($item['ringkasan']))
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $item['ringkasan'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ $item['url'] }}"
                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ $item['cta_label'] ?? 'Buka' }}
                            </a>

                            <form method="POST" action="{{ route('instruktur.notifikasi.tinjau') }}">
                                @csrf
                                <input type="hidden" name="alert_key" value="{{ $item['alert_key'] }}">
                                <input type="hidden" name="dari" value="{{ $dari }}">
                                <input type="hidden" name="sampai" value="{{ $sampai }}">
                                <button type="submit"
                                    class="inline-flex min-h-[44px] items-center rounded-lg bg-gray-100 px-3.5 py-2 text-xs font-semibold text-gray-800 transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">
                                    Tandai Dibaca
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        @if (($level ?? 'semua') !== 'semua')
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                Tidak ada notifikasi aktif dengan tingkat "{{ $levelFilters[$level] ?? ucfirst($level) }}".
                            </p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                Coba pilih filter "Semua" untuk melihat notifikasi pada tingkat lainnya.
                            </p>
                        @else
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Tidak ada notifikasi aktif saat ini.</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Seluruh jadwal, presensi, jurnal, dan sertifikasi Anda dalam kondisi aman.</p>
                        @endif
                    </div>
                @endforelse
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($alerts['items_riwayat'] ?? [] as $rev)
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $rev['kategori'] }}</span>
                                <span>&middot;</span>
                                <span>Dibaca {{ $rev['ditinjau_pada'] }}</span>
                            </div>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $rev['judul'] }}</p>
                            @if (!empty($rev['ringkasan']))
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $rev['ringkasan'] }}</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ $rev['url'] }}"
                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Buka
                            </a>

                            <form method="POST" action="{{ route('instruktur.notifikasi.kembalikan') }}">
                                @csrf
                                <input type="hidden" name="alert_key" value="{{ $rev['alert_key'] }}">
                                <input type="hidden" name="dari" value="{{ $dari }}">
                                <input type="hidden" name="sampai" value="{{ $sampai }}">
                                <button type="submit"
                                    class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                                    Kembalikan ke Aktif
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum ada riwayat notifikasi yang ditandai dibaca.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection
