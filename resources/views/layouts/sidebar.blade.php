<aside id="sidebar-instruktur"
    aria-label="Navigasi Utama Instruktur"
    class="fixed top-0 left-0 z-99999 flex h-screen flex-col border-r border-gray-200 bg-white px-3.5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
    x-data="{
        isActive(path) {
            return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
        }
    }"
    :class="{
        'w-[260px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[76px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    @php
        $appIdentity = \App\Models\Setting::getAppIdentity();
        $shortLpkName = str_contains(strtolower((string) ($appIdentity['lpk_name'] ?? '')), 'cipta tungga indonesia')
            ? 'LPK CTI Ciamis'
            : ($appIdentity['lpk_name'] ?: 'LPK CTI Ciamis');
    @endphp
    <div class="mb-3 flex border-b border-gray-200 py-4 dark:border-gray-800"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('instruktur.dashboard') }}"
            class="flex min-w-0 items-center gap-2.5 rounded-lg p-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <img src="{{ asset('images/logo/logocti.png') }}"
                alt="Logo {{ $appIdentity['lpk_name'] }}"
                class="h-9 w-auto shrink-0 rounded-lg object-contain" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="min-w-0 leading-tight">
                <span class="block text-sm font-bold whitespace-nowrap text-gray-900 dark:text-white" title="{{ $appIdentity['lpk_name'] }}">{{ $shortLpkName }}</span>
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-300">Instruktur</span>
            </span>
        </a>
    </div>

    <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto duration-300 ease-linear">
        <nav class="mb-6 space-y-5">

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Pengajaran</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>

                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('instruktur.dashboard') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/dashboard') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/dashboard') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('instruktur.jadwal.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/jadwal') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/jadwal') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Jadwal &amp; Presensi</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('instruktur.aktivitas.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/aktivitas') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/aktivitas') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Aktivitas Mengajar</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Evaluasi &amp; Kinerja</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>

                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('instruktur.evaluasi.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/evaluasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/evaluasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Hasil Evaluasi</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('instruktur.rapor.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/rapor') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/rapor') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Rapor Kinerja</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Akun</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>

                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('instruktur.notifikasi.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/notifikasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/notifikasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Notifikasi</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('instruktur.profil.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/instruktur-panel/profil') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/instruktur-panel/profil') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Profil &amp; Keamanan</span>
                        </a>
                    </li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="menu-item menu-item-inactive group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'">
                                <span class="menu-item-icon-inactive text-red-600 dark:text-red-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </span>
                                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text font-medium text-red-700 dark:text-red-300">Keluar</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

        </nav>
    </div>
</aside>

<div x-show="$store.sidebar.isMobileOpen" x-cloak @click="$store.sidebar.setMobileOpen(false)"
    class="fixed inset-0 z-50 bg-gray-900/50 xl:hidden"></div>
