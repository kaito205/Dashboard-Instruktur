<aside id="sidebar-admin"
    aria-label="Navigasi Utama Admin"
    class="fixed top-0 left-0 z-99999 flex h-screen flex-col border-r border-gray-200 bg-white px-3.5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
    x-data="{
        openMenus: {},
        init() { this.autoOpenActive(); },
        autoOpenActive() {
            const path = window.location.pathname;
            if (path.startsWith('/admin/pengguna')) this.openMenus['pengguna'] = true;
            if (path.startsWith('/admin/instruktur')) this.openMenus['instruktur'] = true;
            if (path.startsWith('/admin/kelas')) this.openMenus['kelas'] = true;
            if (path.startsWith('/admin/jadwal')) this.openMenus['jadwal'] = true;
            if (path.startsWith('/admin/evaluasi')) this.openMenus['evaluasi'] = true;
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
        'w-[260px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[76px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    @php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
    <div class="mb-3 flex border-b border-gray-200 py-4 dark:border-gray-800"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('admin.dashboard') }}"
            class="flex min-w-0 items-center gap-2.5 rounded-lg p-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <img src="{{ asset('images/logo/logocti.png') }}"
                alt="Logo {{ $appIdentity['lpk_name'] }}"
                class="h-9 w-auto shrink-0 rounded-lg object-contain" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-bold text-gray-900 dark:text-white" title="{{ $appIdentity['lpk_name'] }}">{{ $appIdentity['lpk_name'] }}</span>
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-300">Admin</span>
            </span>
        </a>
    </div>

    <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto duration-300 ease-linear">
        <nav class="mb-6 space-y-5">

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Operasional</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>

                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/dashboard') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/dashboard') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <button type="button" @click="toggle('pengguna')"
                            class="menu-item group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/pengguna') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/pengguna') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Pengguna</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto h-4 w-4 transition-transform" :class="{'rotate-180': openMenus['pengguna']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['pengguna'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1 ml-8 space-y-1">
                                <li><a href="{{ route('admin.pengguna.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="window.location.pathname === '/admin/pengguna' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Daftar Pengguna</a></li>
                                <li><a href="{{ route('admin.pengguna.create') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/pengguna/create') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Tambah Pengguna</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <button type="button" @click="toggle('instruktur')"
                            class="menu-item group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/instruktur') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/instruktur') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Instruktur</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto h-4 w-4 transition-transform" :class="{'rotate-180': openMenus['instruktur']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['instruktur'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1 ml-8 space-y-1">
                                <li><a href="{{ route('admin.instruktur.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="window.location.pathname === '/admin/instruktur' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Daftar Instruktur</a></li>
                                <li><a href="{{ route('admin.instruktur.create') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/instruktur/create') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Tambah Instruktur</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <button type="button" @click="toggle('kelas')"
                            class="menu-item group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/kelas') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/kelas') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Data Kelas</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto h-4 w-4 transition-transform" :class="{'rotate-180': openMenus['kelas']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['kelas'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1 ml-8 space-y-1">
                                <li><a href="{{ route('admin.kelas.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="window.location.pathname === '/admin/kelas' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Daftar Kelas</a></li>
                                <li><a href="{{ route('admin.kelas.create') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/kelas/create') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Tambah Kelas</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <button type="button" @click="toggle('jadwal')"
                            class="menu-item group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/jadwal') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/jadwal') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Jadwal</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto h-4 w-4 transition-transform" :class="{'rotate-180': openMenus['jadwal']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['jadwal'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1 ml-8 space-y-1">
                                <li><a href="{{ route('admin.jadwal.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="window.location.pathname === '/admin/jadwal' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Daftar &amp; Kalender</a></li>
                                <li><a href="{{ route('admin.jadwal.create') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/jadwal/create') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Tambah Jadwal</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="{{ route('admin.import.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/import') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/import') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Import Data</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Evaluasi</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>
                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('admin.kehadiran.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/kehadiran') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/kehadiran') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Kehadiran</span>
                        </a>
                    </li>

                    <li>
                        <button type="button" @click="toggle('evaluasi')"
                            class="menu-item group min-h-[44px] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/evaluasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/evaluasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Evaluasi</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                class="ml-auto h-4 w-4 transition-transform" :class="{'rotate-180': openMenus['evaluasi']}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openMenus['evaluasi'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                            <ul class="mt-1 ml-8 space-y-1">
                                <li><a href="{{ route('admin.evaluasi.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/evaluasi') && !isActive('/admin/evaluasi/kode') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Hasil Evaluasi</a></li>
                                <li><a href="{{ route('admin.evaluasi.kode.index') }}" class="menu-dropdown-item flex min-h-[44px] items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :class="isActive('/admin/evaluasi/kode') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">Kode Evaluasi</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="{{ route('admin.observasi.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/observasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/observasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Observasi</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.kinerja.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/kinerja') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/kinerja') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Kinerja</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.laporan.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/laporan') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/laporan') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Laporan</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="mb-1.5 flex px-3 text-[11px] font-medium text-gray-500 dark:text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-0' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Sistem</span>
                    <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" aria-hidden="true">&middot;</span>
                </h2>
                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('admin.notifikasi.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/notifikasi') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/notifikasi') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Notifikasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.pengaturan.index') }}"
                            class="menu-item group min-h-[44px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            :class="[
                                isActive('/admin/pengaturan') ? 'menu-item-active' : 'menu-item-inactive',
                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : 'justify-start'
                            ]">
                            <span :class="isActive('/admin/pengaturan') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">Pengaturan</span>
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
