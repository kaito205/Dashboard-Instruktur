@props(['studentFeedback' => [], 'search' => '', 'selectedPeriod' => 'bulan_ini', 'selectedStatus' => 'all'])

<div id="ulasan-peserta" class="flex h-full flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Ulasan Evaluasi Peserta
            </h2>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                Umpan balik anonim terbaru dari peserta kelas
            </p>
        </div>
        <a href="{{ route('instruktur.evaluasi.index') }}"
            class="inline-flex min-h-[44px] shrink-0 items-center px-2 text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
            Lihat Semua Evaluasi
        </a>
    </div>

    <form method="GET" action="{{ route('instruktur.dashboard') }}" class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-5 py-3 dark:border-gray-800">
        <input type="hidden" name="periode" value="{{ $selectedPeriod }}" />
        <input type="hidden" name="status" value="{{ $selectedStatus }}" />
        <label for="feedback-search" class="sr-only">Cari ulasan peserta</label>
        <input
            id="feedback-search"
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Cari komentar atau materi..."
            class="min-h-[44px] min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
        />
        <button type="submit"
            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cari
        </button>
        @if ($search !== '')
            <a href="{{ route('instruktur.dashboard', ['periode' => $selectedPeriod, 'status' => $selectedStatus]) }}"
                class="inline-flex min-h-[44px] items-center justify-center px-2 text-xs font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
                Reset
            </a>
        @endif
    </form>

    <div data-live-filter-content class="flex-1 divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($studentFeedback as $fb)
            @php
                $score = (float) ($fb['rating'] ?? 0);
                $scoreBadgeCls = $score >= 85
                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                    : ($score >= 70
                        ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300'
                        : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300');
            @endphp
            <div class="px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="min-w-0 truncate text-xs text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $fb['student_name'] }}</span>
                        <span class="mx-1 text-gray-400">&middot;</span>
                        <span>{{ $fb['date'] }}</span>
                    </p>
                    <span class="shrink-0 rounded-md px-2 py-0.5 font-mono text-xs font-semibold {{ $scoreBadgeCls }}">
                        {{ number_format($score, 1) }} / 100
                    </span>
                </div>

                <p class="mt-2 text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                    "{{ $fb['comment'] }}"
                </p>

                <p class="mt-1.5 truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ $fb['course'] }}
                </p>
            </div>
        @empty
            <div class="px-5 py-8 text-center">
                @if ($search !== '')
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        Tidak ada ulasan yang cocok dengan kata kunci "{{ $search }}".
                    </p>
                    <a href="{{ route('instruktur.dashboard', ['periode' => $selectedPeriod, 'status' => $selectedStatus]) }}"
                        class="mt-3 inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-3.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Tampilkan Semua Ulasan
                    </a>
                @else
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        Belum ada ulasan evaluasi peserta yang tercatat.
                    </p>
                @endif
            </div>
        @endforelse
    </div>
</div>
