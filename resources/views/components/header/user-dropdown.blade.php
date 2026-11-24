@php
    $currentUser = auth()->user();
    $userName = $currentUser?->name ?? 'Pengguna';
    $userEmail = $currentUser?->email ?? '-';
    $userInitial = strtoupper(substr($userName, 0, 1));
    $dashboardUrl = match ($currentUser?->role) {
        'admin' => route('admin.dashboard'),
        'supervisor' => route('supervisor.dashboard'),
        'instruktur' => route('instruktur.dashboard'),
        default => url('/'),
    };
@endphp

<div class="relative" x-data="{
    dropdownOpen: false,
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    <button
        class="flex items-center gap-2.5 text-gray-700 dark:text-gray-300 min-h-[44px]"
        @click.prevent="toggleDropdown()"
        type="button"
    >
        @php
            $fallbackAvatar = $currentUser?->default_avatar_url ?? '';
        @endphp
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <img src="{{ $currentUser?->foto_url ?: $fallbackAvatar }}" alt="{{ $userName }}" @if($fallbackAvatar) onerror="this.onerror=null;this.src='{{ $fallbackAvatar }}';" @endif class="block h-full w-full object-cover object-center" />
        </span>

        <span class="hidden sm:block mr-1 font-medium text-theme-sm">{{ $userName }}</span>

        <svg
            class="w-4 h-4 transition-transform duration-200"
            :class="{ 'rotate-180': dropdownOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 z-50"
        style="display: none;"
    >
        <div class="px-2 py-1.5 border-b border-gray-200 dark:border-gray-800">
            <span class="block font-semibold text-gray-900 text-theme-sm dark:text-white">{{ $userName }}</span>
            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $userEmail }}</span>
        </div>

        <ul class="flex flex-col gap-1 pt-2 pb-2 border-b border-gray-200 dark:border-gray-800">
            <li>
                <a
                    href="{{ $dashboardUrl }}"
                    class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white"
                >
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard Utama
                </a>
            </li>
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button
                type="submit"
                class="flex items-center w-full gap-3 px-3 py-2 font-medium text-red-600 rounded-lg group text-theme-sm hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Keluar Sesi
            </button>
        </form>
    </div>
</div>
