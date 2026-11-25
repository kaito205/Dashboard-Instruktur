@props(['mySchedule' => [], 'nextSchedule' => null, 'todayLabel' => null])

<div id="jadwal-mengajar-saya" class="flex h-full flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Jadwal Mengajar Hari Ini
            </h2>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                {{ $todayLabel ?? \Carbon\Carbon::today('Asia/Jakarta')->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <a href="{{ route('instruktur.jadwal.index') }}"
            class="inline-flex min-h-[44px] shrink-0 items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
            Lihat Semua Jadwal
        </a>
    </div>

    <div class="flex-1 divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($mySchedule as $sch)
            @php
                $isBelumDimulai = !($sch['is_dibatalkan'] ?? false)
                    && !($sch['has_checkin'] ?? false)
                    && !($sch['can_checkin'] ?? false)
                    && (($sch['status_code'] ?? '') === 'belum_checkin');
                $statusLabel = $isBelumDimulai ? 'Belum Dimulai' : ($sch['status'] ?? '-');
                $colorKey = $isBelumDimulai ? 'slate' : ($sch['status_color'] ?? 'gray');
                $badgeCls = match ($colorKey) {
                    'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                    'amber'   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                    'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                    'blue'    => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
                    'slate'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                    default   => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                };
                $dotCls = match ($colorKey) {
                    'emerald' => 'bg-emerald-600 dark:bg-emerald-400',
                    'amber'   => 'bg-amber-500 dark:bg-amber-400',
                    'red'     => 'bg-red-600 dark:bg-red-400',
                    'blue'    => 'bg-brand-600 dark:bg-brand-400',
                    'slate'   => 'bg-slate-400 dark:bg-slate-400',
                    default   => 'bg-gray-500 dark:bg-gray-400',
                };
            @endphp
            <div class="flex flex-col gap-3 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                            {{ $sch['time'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $badgeCls }}">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotCls }}"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <p class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $sch['course'] }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                        {{ $sch['room'] }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if(!($sch['is_dibatalkan'] ?? false) && ($sch['can_checkin'] ?? false))
                        <form method="POST" action="{{ route('instruktur.jadwal.checkin', $sch['jadwal_id']) }}" onsubmit="return submitWithOptionalGps(event, this)">
                            @csrf
                            <input type="hidden" name="tanggal_sesi" value="{{ $sch['tanggal_sesi'] ?? today('Asia/Jakarta')->toDateString() }}" />
                            <input type="hidden" name="latitude" value="" />
                            <input type="hidden" name="longitude" value="" />
                            <input type="hidden" name="akurasi_gps" value="" />
                            <input type="hidden" name="status_gps" value="tidak_tersedia" />
                            <button type="submit"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                Check-in
                            </button>
                        </form>
                    @elseif(!($sch['is_dibatalkan'] ?? false) && ($sch['can_checkout'] ?? false))
                        <form method="POST" action="{{ route('instruktur.jadwal.checkout', $sch['jadwal_id']) }}" onsubmit="return submitWithOptionalGps(event, this)">
                            @csrf
                            <input type="hidden" name="tanggal_sesi" value="{{ $sch['tanggal_sesi'] ?? today('Asia/Jakarta')->toDateString() }}" />
                            <input type="hidden" name="latitude" value="" />
                            <input type="hidden" name="longitude" value="" />
                            <input type="hidden" name="akurasi_gps" value="" />
                            <input type="hidden" name="status_gps" value="tidak_tersedia" />
                            <button type="submit"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                Check-out
                            </button>
                        </form>
                    @elseif(!($sch['is_dibatalkan'] ?? false) && !empty($sch['checkin_reason']))
                        <span class="inline-flex min-h-[36px] items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $sch['checkin_reason'] }}
                        </span>
                    @endif

                    @if($sch['can_fill_journal'] ?? false)
                        <a href="{{ route('instruktur.aktivitas.index') }}#jadwal-{{ $sch['jadwal_id'] ?? '' }}"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Isi Jurnal
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-8">
                <p class="text-center text-sm font-medium text-gray-600 dark:text-gray-300">
                    Tidak ada jadwal mengajar hari ini.
                </p>

                @if($nextSchedule)
                    <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            Jadwal Terdekat Berikutnya
                        </p>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                                    {{ $nextSchedule['tanggal_label'] }} &middot; <span class="font-mono">{{ $nextSchedule['time'] }}</span>
                                </p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $nextSchedule['course'] }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $nextSchedule['room'] }}
                                </p>
                            </div>
                            <a href="{{ route('instruktur.jadwal.index', ['tanggal_sesi' => $nextSchedule['tanggal_sesi']]) }}"
                                class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Lihat Sesi
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
