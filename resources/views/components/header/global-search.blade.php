@php
    $user = auth()->user();
    $role = $user?->role ?? 'admin';

    $pages = [];
    if ($role === 'admin') {
        $pages = [
            ['title' => 'Dashboard Ringkasan', 'subtitle' => 'Statistik utama, grafik & peringkat instruktur', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'dashboard beranda ringkasan utama statistik', 'url' => route('admin.dashboard')],
            ['title' => 'Data Instruktur', 'subtitle' => 'Daftar instruktur, sertifikasi & status cuti', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'data instruktur daftar pengajar pelatih guru sertifikasi cuti izin', 'url' => route('admin.instruktur.index')],
            ['title' => 'Tambah Instruktur Baru', 'subtitle' => 'Formulir tambah profil instruktur baru', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'tambah instruktur buat pengajar baru', 'url' => route('admin.instruktur.create')],
            ['title' => 'Data Kelas', 'subtitle' => 'Master kelas pelatihan, program, kuota & ruangan default', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'data kelas master program pelatihan kuota ruangan rpl tkj ap ps', 'url' => route('admin.kelas.index')],
            ['title' => 'Tambah Kelas Baru', 'subtitle' => 'Formulir pendaftaran master data kelas baru', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'tambah kelas buat master kelas baru', 'url' => route('admin.kelas.create')],
            ['title' => 'Jadwal Mengajar', 'subtitle' => 'Daftar jadwal kelas, ruangan & instruktur pengganti', 'category' => 'Halaman', 'icon' => 'calendar', 'keywords' => 'jadwal mengajar kelas sesi ruangan l-01 r-01', 'url' => route('admin.jadwal.index')],
            ['title' => 'Kalender Jadwal Mengajar', 'subtitle' => 'Tampilan kalender harian, mingguan & bulanan', 'category' => 'Halaman', 'icon' => 'calendar', 'keywords' => 'kalender jadwal harian mingguan bulanan', 'url' => route('admin.jadwal.index', ['tampilan' => 'kalender', 'skala_kalender' => 'mingguan'])],
            ['title' => 'Tambah Jadwal Mengajar', 'subtitle' => 'Formulir plot jadwal kelas baru', 'category' => 'Halaman', 'icon' => 'calendar', 'keywords' => 'tambah jadwal buat sesi kelas baru', 'url' => route('admin.jadwal.create')],
            ['title' => 'Monitoring Kehadiran & Presensi', 'subtitle' => 'Rekap check-in/out, input manual & auto-checkout', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'kehadiran absensi presensi checkin checkout manual auto', 'url' => route('admin.kehadiran.index')],
            ['title' => 'Monitoring Aktivitas Mengajar', 'subtitle' => 'Verifikasi laporan materi & dokumentasi kelas', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'aktivitas mengajar laporan jurnal materi dokumentasi verifikasi', 'url' => route('admin.kehadiran.index', ['tab' => 'aktivitas'])],
            ['title' => 'Hasil Evaluasi Peserta', 'subtitle' => 'Rekap skor penilaian dari peserta pelatihan', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'hasil evaluasi penilaian kuesioner peserta ulasan', 'url' => route('admin.evaluasi.index')],
            ['title' => 'Kode Akses Evaluasi Kelas', 'subtitle' => 'Salin link kelas & buka/tutup sesi evaluasi', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'kode akses evaluasi kelas link buka tutup sesi', 'url' => route('admin.evaluasi.kode.index')],
            ['title' => 'Observasi Pengajaran Supervisor', 'subtitle' => 'Penilaian observasi kelas & rekomendasi', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'observasi supervisor penilaian langsung kelas rekomendasi', 'url' => route('admin.observasi.index')],
            ['title' => 'Monitoring Kinerja Instruktur', 'subtitle' => 'Skor 4 komponen & perbandingan antarperiode', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'monitoring kinerja performa skor rapor perbandingan periode', 'url' => route('admin.kinerja.index')],
            ['title' => 'Laporan & Ekspor Data', 'subtitle' => 'Rekap laporan lengkap & unduh Excel/CSV', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'laporan ekspor data rekapitulasi excel csv', 'url' => route('admin.laporan.index')],
            ['title' => 'Cetak Laporan Resmi / PDF', 'subtitle' => 'Halaman cetak laporan siap simpan PDF', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'cetak laporan print pdf dokumen resmi', 'url' => route('admin.laporan.cetak')],
            ['title' => 'Import Data Excel / CSV', 'subtitle' => 'Impor riwayat absensi & evaluasi dari spreadsheet', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'import impor data unggah excel csv spreadsheet', 'url' => route('admin.import.index')],
            ['title' => 'Pusat Notifikasi', 'subtitle' => 'Peringatan jurnal tertunda & masa berlaku sertifikasi', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'notifikasi peringatan eskalasi alert', 'url' => route('admin.notifikasi.index')],
            ['title' => 'Manajemen Akun Pengguna', 'subtitle' => 'Kelola akun Admin, Supervisor & Instruktur', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'manajemen akun pengguna user admin supervisor instruktur', 'url' => route('admin.pengguna.index')],
            ['title' => 'Pengaturan Sistem & Profil Admin', 'subtitle' => 'Atur identitas LPK, foto profil & bobot kinerja', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'pengaturan sistem konfigurasi bobot indikator profil password foto', 'url' => route('admin.pengaturan.index')],
        ];
    } elseif ($role === 'supervisor') {
        $pages = [
            ['title' => 'Dashboard Supervisor', 'subtitle' => 'Ringkasan monitoring operasional', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'dashboard beranda ringkasan utama statistik', 'url' => route('supervisor.dashboard')],
            ['title' => 'Data Instruktur', 'subtitle' => 'Profil, keahlian & sertifikasi instruktur', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'data instruktur daftar pengajar pelatih keahlian', 'url' => route('supervisor.instruktur.index')],
            ['title' => 'Jadwal Mengajar & Kalender', 'subtitle' => 'Pantau jadwal kelas & kalender mengajar', 'category' => 'Halaman', 'icon' => 'calendar', 'keywords' => 'jadwal mengajar kalender kelas sesi ruangan', 'url' => route('supervisor.jadwal.index')],
            ['title' => 'Monitoring Kehadiran & Presensi', 'subtitle' => 'Pantau data check-in & check-out kehadiran', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'kehadiran absensi presensi checkin checkout', 'url' => route('supervisor.kehadiran.index')],
            ['title' => 'Verifikasi Aktivitas Mengajar', 'subtitle' => 'Verifikasi jurnal & dokumentasi mengajar', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'aktivitas mengajar verifikasi jurnal materi dokumentasi', 'url' => route('supervisor.kehadiran.index', ['tab' => 'aktivitas'])],
            ['title' => 'Hasil Evaluasi Peserta', 'subtitle' => 'Tinjau hasil penilaian dari peserta kelas', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'hasil evaluasi penilaian kuesioner peserta', 'url' => route('supervisor.evaluasi.index')],
            ['title' => 'Kode Akses Evaluasi Kelas', 'subtitle' => 'Salin link evaluasi kelas peserta', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'kode akses evaluasi kelas link sesi', 'url' => route('supervisor.evaluasi.kode.index')],
            ['title' => 'Input Observasi Pengajaran', 'subtitle' => 'Catat penilaian observasi kelas langsung', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'observasi supervisor penilaian kelas rekomendasi', 'url' => route('supervisor.observasi.index')],
            ['title' => 'Monitoring & Perbandingan Kinerja', 'subtitle' => 'Bandingkan skor kinerja antar-instruktur', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'monitoring kinerja performa perbandingan skor', 'url' => route('supervisor.kinerja.index')],
            ['title' => 'Laporan Analitik & Cetak PDF', 'subtitle' => 'Rekap laporan kinerja & ekspor dokumen', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'laporan analitik ekspor excel csv cetak pdf', 'url' => route('supervisor.laporan.index')],
            ['title' => 'Pusat Notifikasi', 'subtitle' => 'Daftar peringatan operasional aktif', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'notifikasi peringatan eskalasi alert', 'url' => route('supervisor.notifikasi.index')],
            ['title' => 'Profil & Keamanan Supervisor', 'subtitle' => 'Ubah foto profil, nama & password', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'profil keamanan password foto supervisor', 'url' => route('supervisor.profil.index')],
        ];
    } else {
        $pages = [
            ['title' => 'Dashboard Instruktur', 'subtitle' => 'Ringkasan jam mengajar & jadwal pribadi', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'dashboard beranda ringkasan utama', 'url' => route('instruktur.dashboard')],
            ['title' => 'Jadwal & Presensi Check-in / Check-out', 'subtitle' => 'Daftar jadwal sesi & tombol presensi', 'category' => 'Halaman', 'icon' => 'calendar', 'keywords' => 'jadwal mengajar absensi presensi checkin checkout sesi', 'url' => route('instruktur.jadwal.index')],
            ['title' => 'Laporan Aktivitas Mengajar Saya', 'subtitle' => 'Isi realisasi materi & unggah dokumentasi', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'aktivitas mengajar jurnal laporan materi dokumentasi', 'url' => route('instruktur.aktivitas.index')],
            ['title' => 'Hasil Evaluasi Peserta Saya', 'subtitle' => 'Lihat skor evaluasi dari peserta kelas', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'hasil evaluasi penilaian ulasan peserta', 'url' => route('instruktur.evaluasi.index')],
            ['title' => 'Rapor Kinerja Pribadi', 'subtitle' => 'Detail perhitungan 4 komponen kinerja Anda', 'category' => 'Halaman', 'icon' => 'page', 'keywords' => 'rapor kinerja skor kategori evaluasi observasi', 'url' => route('instruktur.rapor.index')],
            ['title' => 'Profil & Keamanan Akun Saya', 'subtitle' => 'Perbarui foto profil, biodata & password', 'category' => 'Halaman', 'icon' => 'user', 'keywords' => 'profil akun password foto biodata keahlian', 'url' => route('instruktur.profil.index')],
        ];
    }

    $instrukturItems = [];
    $jadwalItems = [];
    $kelasItems = [];

    $isLiveFilterAjax = request()->header('X-Live-Filter') === '1';

    if (!$isLiveFilterAjax && in_array($role, ['admin', 'supervisor'], true)) {
        try {
            $insShowRoute = $role === 'admin' ? 'admin.instruktur.show' : 'supervisor.instruktur.show';
            $jadwalRoute  = $role === 'admin' ? 'admin.jadwal.index' : 'supervisor.jadwal.index';

            $lpkName = \App\Models\Setting::getAppIdentity()['lpk_name'];
            $instrukturItems = \App\Models\Instruktur::orderBy('nama_lengkap')
                ->get(['id', 'nama_lengkap', 'kode_instruktur', 'bidang_keahlian', 'email'])
                ->map(fn ($ins) => [
                    'title'    => $ins->nama_lengkap,
                    'subtitle' => $ins->kode_instruktur . ' • ' . ($ins->bidang_keahlian ?: 'Instruktur ' . $lpkName),
                    'category' => 'Instruktur',
                    'icon'     => 'user',
                    'keywords' => strtolower($ins->nama_lengkap . ' ' . $ins->kode_instruktur . ' ' . ($ins->bidang_keahlian ?? '') . ' ' . ($ins->email ?? '')),
                    'url'      => route($insShowRoute, $ins),
                ])
                ->toArray();

            if ($role === 'admin' && \Illuminate\Support\Facades\Schema::hasTable('kelas')) {
                $kelasItems = \App\Models\Kelas::orderBy('kode_kelas')
                    ->limit(30)
                    ->get(['id', 'kode_kelas', 'nama_kelas', 'program_pelatihan', 'ruangan_default'])
                    ->map(fn ($k) => [
                        'title'    => $k->display_label,
                        'subtitle' => ($k->program_pelatihan ?: 'Program Pelatihan') . ($k->ruangan_default ? ' • Ruang ' . $k->ruangan_default : ''),
                        'category' => 'Kelas',
                        'icon'     => 'page',
                        'keywords' => strtolower($k->kode_kelas . ' ' . $k->nama_kelas . ' ' . ($k->program_pelatihan ?? '') . ' ' . ($k->ruangan_default ?? '')),
                        'url'      => route('admin.kelas.show', $k),
                    ])
                    ->toArray();
            }

            $jadwalItems = \App\Models\Jadwal::with('instruktur')
                ->orderBy('hari')
                ->orderBy('jam_mulai')
                ->limit(25)
                ->get()
                ->map(fn ($j) => [
                    'title'    => 'Kelas ' . $j->nama_kelas . ' (' . ($j->materi ?: 'Pelatihan') . ')',
                    'subtitle' => ($j->hari ? 'Setiap ' . $j->hari : ($j->tanggal ? $j->tanggal->format('d/m/Y') : '')) . ' • ' . substr((string) $j->jam_mulai, 0, 5) . '-' . substr((string) $j->jam_selesai, 0, 5) . ' • ' . ($j->instruktur?->nama_lengkap ?? '-'),
                    'category' => 'Jadwal',
                    'icon'     => 'calendar',
                    'keywords' => strtolower($j->nama_kelas . ' ' . ($j->kode_kelas ?? '') . ' ' . ($j->materi ?? '') . ' ' . ($j->hari ?? '') . ' ' . ($j->ruangan ?? '') . ' ' . ($j->instruktur?->nama_lengkap ?? '')),
                    'url'      => route($jadwalRoute, ['search' => $j->nama_kelas]),
                ])
                ->toArray();
        } catch (\Throwable $e) {
            $instrukturItems = [];
            $jadwalItems = [];
            $kelasItems = [];
        }
    }

    $allSearchItems = array_values(array_merge($pages, $instrukturItems, $kelasItems, $jadwalItems));
@endphp

<script>
    window.__CTI_SEARCH_ITEMS__ = @json($allSearchItems);
    window.ctiGlobalSearch = function () {
        return {
            query: '',
            open: false,
            selectedIndex: 0,
            items: window.__CTI_SEARCH_ITEMS__ || [],
            get hasQuery() {
                return this.query.trim().length > 0;
            },
            get filtered() {
                const q = this.query.trim().toLowerCase();
                if (!q) return [];
                const tokens = q.split(/\s+/);
                return this.items.filter(function (item) {
                    const hay = (item.title + ' ' + item.subtitle + ' ' + item.category + ' ' + item.keywords).toLowerCase();
                    return tokens.every(function (t) { return hay.includes(t); });
                }).slice(0, 6);
            },
            onInput() {
                this.selectedIndex = 0;
                this.open = this.hasQuery;
            },
            moveDown() {
                if (!this.hasQuery) return;
                this.open = true;
                if (this.filtered.length > 0) {
                    this.selectedIndex = (this.selectedIndex + 1) % this.filtered.length;
                }
            },
            moveUp() {
                if (!this.hasQuery) return;
                this.open = true;
                if (this.filtered.length > 0) {
                    this.selectedIndex = (this.selectedIndex - 1 + this.filtered.length) % this.filtered.length;
                }
            },
            filterCurrentPage() {
                const q = this.query.trim();
                if (!q) return;
                this.open = false;
                const mainEl = document.getElementById('main-content');
                const pageSearch = mainEl ? mainEl.querySelector('form input[name="search"]') : null;
                if (pageSearch) {
                    pageSearch.value = q;
                    pageSearch.dispatchEvent(new Event('input', { bubbles: true }));
                    pageSearch.focus();
                    return;
                }
                const url = new URL(window.location.href);
                url.searchParams.set('search', q);
                window.location.href = url.toString();
            },
            goSelected() {
                if (!this.hasQuery) return;
                if (this.filtered.length > 0 && this.filtered[this.selectedIndex]) {
                    window.location.href = this.filtered[this.selectedIndex].url;
                    return;
                }
                this.filterCurrentPage();
            }
        };
    };
</script>

<div class="relative"
    x-data="ctiGlobalSearch()"
    @keydown.window.meta.k.prevent="$refs.globalSearchInput.focus()"
    @keydown.window.ctrl.k.prevent="$refs.globalSearchInput.focus()"
    @click.outside="open = false">

    <form @submit.prevent="goSelected()">
        <div class="relative">
            <span class="absolute -translate-y-1/2 pointer-events-none left-4 top-1/2">
                <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" />
                </svg>
            </span>

            <input
                x-ref="globalSearchInput"
                type="text"
                x-model="query"
                @focus="if (hasQuery) open = true"
                @input="onInput()"
                @keydown.arrow-down.prevent="moveDown()"
                @keydown.arrow-up.prevent="moveUp()"
                @keydown.escape="open = false"
                placeholder="Cari halaman, instruktur, atau jadwal..."
                aria-label="Cari halaman, instruktur, atau jadwal"
                autocomplete="off"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/3 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-[430px]" />

            <button type="submit"
                aria-label="Jalankan pencarian"
                class="absolute right-2.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                <span> ⌘ </span>
                <span> K </span>
            </button>
        </div>
    </form>

    <div x-show="open && hasQuery"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        class="absolute left-0 mt-2 w-full xl:w-[430px] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 z-50">

        <div class="p-2">
            <template x-for="(item, idx) in filtered" :key="item.url + item.title + idx">
                <a :href="item.url"
                    @mouseenter="selectedIndex = idx"
                    :class="selectedIndex === idx
                        ? 'bg-brand-50/80 dark:bg-brand-950/50'
                        : 'hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        :class="selectedIndex === idx
                            ? 'bg-brand-600 text-white'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'">
                        <template x-if="item.icon === 'user'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </template>
                        <template x-if="item.icon === 'calendar'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="item.icon === 'page'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="item.title"></p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="item.subtitle"></p>
                    </div>

                    <span class="shrink-0 rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        x-text="item.category"></span>
                </a>
            </template>

            <template x-if="filtered.length === 0">
                <div class="px-3 py-4 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Tidak ditemukan halaman atau instruktur untuk "<span class="font-semibold text-gray-800 dark:text-gray-200" x-text="query.trim()"></span>"
                    </p>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/80 px-3.5 py-2 text-[11px] text-gray-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
            <button type="button" @click="filterCurrentPage()" class="font-medium text-brand-600 hover:underline dark:text-brand-400">
                Cari "<span x-text="query.trim()"></span>" di tabel halaman ini
            </button>
            <span>Enter untuk buka</span>
        </div>
    </div>
</div>
