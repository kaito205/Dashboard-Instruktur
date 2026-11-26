@php
    $user = auth()->user();
    $role = $user?->role ?? 'admin';

    $isLiveFilterAjax = request()->header('X-Live-Filter') === '1';
    $alertSummary = $isLiveFilterAjax
        ? ['feed' => [], 'total_peringatan' => 0]
        : app(\App\Services\OperationalAlertService::class)->getSummaryAlerts(null, null, $role, $user?->id);
    $notifications = array_values($alertSummary['feed'] ?? []);
    $totalAlerts = (int) ($alertSummary['total_peringatan'] ?? 0);

    $canManageAlerts = in_array($role, ['admin', 'supervisor', 'instruktur'], true);
    $dismissUrl = $canManageAlerts ? route($role . '.notifikasi.tinjau') : '';
    $dismissAllUrl = $canManageAlerts ? route($role . '.notifikasi.tinjau-semua') : '';

    $targetNotifikasiUrl = match ($role) {
        'admin' => route('admin.notifikasi.index'),
        'supervisor' => route('supervisor.notifikasi.index'),
        'instruktur' => route('instruktur.notifikasi.index'),
        default => url('/'),
    };

    // Tanda tangan isi notifikasi aktif per pengguna untuk menjaga konsistensi status baca lonceng.
    $notifSignature = $totalAlerts . ':' . implode(',', array_map(
        fn ($n) => (string) ($n['alert_key'] ?? ''),
        $notifications
    ));
    $notifStorageKey = 'notif_seen_' . ($user?->id ?? 'guest');
    $notifPagePath = $canManageAlerts
        ? (parse_url($targetNotifikasiUrl, PHP_URL_PATH) ?: '')
        : '';
@endphp

<div class="relative" x-data="{
    dropdownOpen: false,
    items: @js($notifications),
    totalAlerts: @js($totalAlerts),
    canManage: @js($canManageAlerts),
    dismissUrl: @js($dismissUrl),
    dismissAllUrl: @js($dismissAllUrl),
    csrfToken: '{{ csrf_token() }}',
    loadingKey: '',
    markingAll: false,
    errorMsg: '',
    seen: false,
    signature: @js($notifSignature),
    storageKey: @js($notifStorageKey),
    notifPath: @js($notifPagePath),
    init() {
        try {
            this.seen = localStorage.getItem(this.storageKey) === this.signature;
        } catch (e) {
            this.seen = false;
        }
        if (this.notifPath && window.location.pathname === this.notifPath) {
            this.markSeen();
        }
    },
    buildSignature(count, list) {
        const keys = (list || []).map((n) => n.alert_key || '').join(',');
        return `${Number(count) || 0}:${keys}`;
    },
    markSeen() {
        this.seen = true;
        try {
            localStorage.setItem(this.storageKey, this.signature);
        } catch (e) {}
    },
    syncPageCounts(dismissedKey = null) {
        document.querySelectorAll('[data-alert-count]').forEach((el) => {
            el.textContent = String(this.totalAlerts);
        });
        if (dismissedKey) {
            document.querySelectorAll('[data-alert-row]').forEach((row) => {
                if (row.dataset.alertRow === dismissedKey) {
                    row.remove();
                }
            });
        } else if (this.totalAlerts === 0) {
            document.querySelectorAll('[data-alert-row]').forEach((row) => row.remove());
        }
        if (this.notifPath && window.location.pathname === this.notifPath) {
            window.location.reload();
        }
    },
    async markReviewed(item) {
        if (!this.canManage || !this.dismissUrl || this.loadingKey) return;
        this.loadingKey = item.alert_key;
        this.errorMsg = '';
        try {
            const res = await fetch(this.dismissUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    alert_key: item.alert_key,
                    kategori: item.kategori,
                    level: item.level,
                    judul: item.judul,
                    ringkasan: item.ringkasan,
                    url: item.url
                })
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();
            this.items = this.items.filter((i) => i.alert_key !== item.alert_key);
            this.totalAlerts = typeof data.total_peringatan === 'number'
                ? data.total_peringatan
                : (typeof data.total_aktif === 'number' ? data.total_aktif : this.items.length);
            this.signature = this.buildSignature(this.totalAlerts, this.items);
            this.markSeen();
            this.syncPageCounts(item.alert_key);
        } catch (e) {
            this.errorMsg = 'Gagal menyimpan status tinjauan. Silakan coba lagi.';
        } finally {
            this.loadingKey = '';
        }
    },
    async markAllReviewed() {
        if (!this.canManage || !this.dismissAllUrl || this.markingAll || this.items.length === 0) return;
        this.markingAll = true;
        this.errorMsg = '';
        try {
            const res = await fetch(this.dismissAllUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({})
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();
            this.items = [];
            this.totalAlerts = typeof data.total_peringatan === 'number'
                ? data.total_peringatan
                : (typeof data.total_aktif === 'number' ? data.total_aktif : 0);
            this.signature = this.buildSignature(this.totalAlerts, this.items);
            this.markSeen();
            this.syncPageCounts(null);
        } catch (e) {
            this.errorMsg = 'Gagal menandai semua notifikasi. Silakan coba lagi.';
        } finally {
            this.markingAll = false;
        }
    },
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
        if (this.dropdownOpen) {
            this.markSeen();
        }
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()" @keydown.escape.window="closeDropdown()">
    <button
        class="relative flex items-center justify-center text-gray-600 transition-colors bg-white border border-gray-200 rounded-full h-11 w-11 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        :aria-label="(seen || totalAlerts === 0) ? 'Buka notifikasi sistem' : `Buka notifikasi sistem (${totalAlerts} peringatan belum dilihat)`"
        aria-label="Buka notifikasi sistem"
        type="button"
    >
        <span x-show="totalAlerts > 0 && !seen" x-cloak data-notif-badge
            x-text="totalAlerts > 99 ? '99+' : totalAlerts"
            class="absolute -right-0.5 -top-0.5 z-10 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
        </span>

        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                fill="" />
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
        class="absolute -left-[52px] sm:left-0 xl:left-auto xl:right-0 mt-3 flex max-h-[480px] w-[calc(100vw-2rem)] sm:w-[370px] xl:w-[380px] flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 z-50"
        style="display: none;"
    >
        <div class="flex items-start justify-between gap-2 pb-2.5 mb-1 border-b border-gray-200 dark:border-gray-800">
            <div class="min-w-0">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Notifikasi Operasional</h5>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                    <span x-text="totalAlerts"></span> perlu tinjauan
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    x-show="canManage && items.length > 0"
                    x-cloak
                    type="button"
                    @click="markAllReviewed()"
                    :disabled="markingAll"
                    class="inline-flex min-h-[36px] items-center rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-50 dark:text-brand-300 dark:hover:bg-brand-950/40"
                    x-text="markingAll ? 'Menyimpan...' : 'Tandai semua dibaca'"
                ></button>

                <button @click="closeDropdown()" class="inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" type="button" aria-label="Tutup panel notifikasi">
                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="errorMsg" x-cloak role="alert"
            class="mb-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-900 dark:bg-red-950/60 dark:text-red-300"
            x-text="errorMsg">
        </div>

        <ul class="flex flex-col max-h-[300px] overflow-y-auto divide-y divide-gray-200 dark:divide-gray-800">
            <template x-for="item in items" :key="item.alert_key">
                <li class="py-3 px-1.5">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-1.5 inline-flex h-2 w-2 shrink-0 rounded-full"
                            :title="item.level === 'critical' ? 'Prioritas Tinggi' : (item.level === 'warning' ? 'Peringatan' : 'Informasi')"
                            :class="item.level === 'critical' ? 'bg-red-600' : (item.level === 'warning' ? 'bg-amber-500' : 'bg-brand-600')"></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white" x-text="item.judul"></p>
                                <span x-show="item.level === 'info'"
                                    class="inline-flex items-center rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    x-text="'Info ' + (item.kategori || 'Jadwal')"></span>
                            </div>
                            <p x-show="item.ringkasan" class="mt-0.5 text-xs text-gray-600 dark:text-gray-300" x-text="item.ringkasan"></p>

                            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                <a :href="item.url" @click="closeDropdown()"
                                    class="inline-flex min-h-[36px] items-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    x-text="item.cta_label || 'Buka'"></a>

                                <button
                                    x-show="canManage"
                                    type="button"
                                    @click="markReviewed(item)"
                                    :disabled="loadingKey === item.alert_key"
                                    class="inline-flex min-h-[36px] items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-800 hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                                    x-text="loadingKey === item.alert_key ? 'Menyimpan...' : 'Tandai Selesai'">
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </template>

            <li x-show="items.length === 0" x-cloak class="py-8 px-4 text-center">
                <p class="text-xs font-semibold text-gray-900 dark:text-white">Semua notifikasi sudah ditinjau.</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Tidak ada peringatan operasional aktif saat ini.</p>
            </li>
        </ul>

        <a
            href="{{ $targetNotifikasiUrl }}"
            class="mt-2.5 flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            @click="closeDropdown()"
        >
            Buka Pusat Notifikasi
        </a>
    </div>
</div>
