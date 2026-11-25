<aside id="sidebar-supervisor"
    aria-label="Navigasi Utama Supervisor"
    class="fixed top-0 left-0 z-99999 flex h-screen flex-col border-r border-slate-200 bg-white text-gray-900 transition-all duration-300 ease-in-out dark:border-slate-800 dark:bg-gray-900 dark:text-gray-100"
    x-data="{
        openMenus: {},
        init() { this.autoOpenActive(); },
        autoOpenActive() {
            const path = window.location.pathname;
            if (path.startsWith('/supervisor/evaluasi')) this.openMenus['evaluasi'] = true;
        },
        toggle(key) {
            const cur = this.openMenus[key];
            this.openMenus = {};
            this.openMenus[key] = !cur;
        },
        isActive(path) {
            return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
        }
    }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
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
    <div class="flex h-16 shrink-0 items-center border-b border-slate-200 px-5 dark:border-slate-800"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-3' : 'justify-start'">
        <a href="{{ route('supervisor.dashboard') }}"
            class="flex min-w-0 items-center gap-3 rounded-lg p-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <img src="{{ asset('images/logo/logocti.png') }}"
                alt="Logo {{ $appIdentity['lpk_name'] }}"
                class="h-9 w-auto shrink-0 rounded-lg object-contain" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="min-w-0 leading-tight">
                <span class="block text-sm font-bold whitespace-nowrap text-gray-900 dark:text-white" title="{{ $appIdentity['lpk_name'] }}">{{ $shortLpkName }}</span>
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-300">Supervisor</span>
            </span>
        </a>
    </div>

    <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto px-5 pt-4 duration-300 ease-linear">
        <nav class="mb-6 space-y-5">

            <div>
                <h2 class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300 flex"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Menu Utama</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">...</span>
                </h2>

                <ul class="flex flex-col gap-1.5">
                    <li>
                        <a href="{{ route('supervisor.dashboard') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/supervisor/dashboard') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'
                            ]">
                            <span :class="isActive('/supervisor/dashboard') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('supervisor.instruktur.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/supervisor/instruktur') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'
                            ]">
                            <span :class="isActive('/supervisor/instruktur') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Data Instruktur</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('supervisor.jadwal.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/supervisor/jadwal') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'
                            ]">
                            <span :class="isActive('/supervisor/jadwal') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Jadwal Mengajar</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300 flex"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Monitoring & Evaluasi</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">...</span>
                </h2>
                <ul class="flex flex-col gap-1.5">
                    <li>
                        <a href="{{ route('supervisor.kehadiran.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/kehadiran') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start']">
                            <span :class="isActive('/supervisor/kehadiran') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Pantau Kehadiran</span>
                        </a>
                    </li>

                    <li>
                        <button type="button" @click="toggle('evaluasi')"
                            class="menu-item group w-full min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/evaluasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered) ? 'xl:justify-center' : 'xl:justify-start']">
                            <span :class="isActive('/supervisor/evaluasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Manajemen Evaluasi</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto w-4 h-4 transition-transform" :class="{'rotate-180': openMenus['evaluasi']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['evaluasi'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1.5 space-y-1 ml-9">
                                <li><a href="{{ route('supervisor.evaluasi.index') }}" class="menu-dropdown-item min-h-[40px] flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/supervisor/evaluasi') && !isActive('/supervisor/evaluasi/kode') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Hasil & Grafik Evaluasi</a></li>
                                <li><a href="{{ route('supervisor.evaluasi.kode.index') }}" class="menu-dropdown-item min-h-[40px] flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/supervisor/evaluasi/kode') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Kode Akses Evaluasi</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="{{ route('supervisor.observasi.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/observasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start']">
                            <span :class="isActive('/supervisor/observasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Observasi Pengajaran</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('supervisor.kinerja.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/kinerja') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start']">
                            <span :class="isActive('/supervisor/kinerja') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Monitoring Kinerja</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('supervisor.laporan.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/laporan') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start']">
                            <span :class="isActive('/supervisor/laporan') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Laporan & Ekspor</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300 flex"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Akun</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">...</span>
                </h2>
                <ul class="flex flex-col gap-1.5">
                    <li>
                        <a href="{{ route('supervisor.profil.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[isActive('/supervisor/profil') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start']">
                            <span :class="isActive('/supervisor/profil') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Profil & Keamanan</span>
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="menu-item menu-item-inactive group w-full min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
                                <span class="menu-item-icon-inactive text-red-600 dark:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </span>
                                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text text-red-600 dark:text-red-400 font-semibold">Keluar Sesi</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>

<div x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)"
    class="fixed z-50 h-screen w-full bg-gray-900/50 xl:hidden"></div>
