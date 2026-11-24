<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
    <title>@yield('title', $title ?? 'Dashboard Supervisor') | {{ $appIdentity['app_name'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logocti.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            #sidebar-supervisor, #header-supervisor, .no-print {
                display: none !important;
            }
            #main-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const saved = localStorage.getItem('theme');
                    const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    this.theme = saved || system;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    document.documentElement.classList.toggle('dark', this.theme === 'dark');
                    if (document.body) {
                        document.body.classList.toggle('dark', this.theme === 'dark');
                        document.body.classList.toggle('bg-gray-900', this.theme === 'dark');
                    }
                }
            });
            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                toggleExpanded() { this.isExpanded = !this.isExpanded; this.isMobileOpen = false; },
                toggleMobileOpen() { this.isMobileOpen = !this.isMobileOpen; },
                setMobileOpen(val) { this.isMobileOpen = val; },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) this.isHovered = val;
                }
            });
            Alpine.store('confirmModal', {
                open: false,
                title: 'Konfirmasi Tindakan',
                message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                confirmText: 'Ya, Lanjutkan',
                danger: false,
                targetForm: null,
                show(form, title, message, confirmText = 'Ya, Lanjutkan', danger = false) {
                    this.targetForm = form;
                    this.title = title;
                    this.message = message;
                    this.confirmText = confirmText;
                    this.danger = danger;
                    this.open = true;
                },
                close() {
                    this.open = false;
                    this.targetForm = null;
                },
                proceed() {
                    if (this.targetForm) {
                        this.targetForm.dataset.confirmed = 'true';
                        this.targetForm.submit();
                    }
                    this.close();
                }
            });
        });
    </script>
    <script>
        (function() {
            const t = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
                if (document.body) {
                    document.body.classList.add('dark', 'bg-gray-900');
                } else {
                    document.addEventListener('DOMContentLoaded', () => document.body?.classList.add('dark', 'bg-gray-900'));
                }
            }
        })();
    </script>
</head>

<body x-data="{ loaded: true, pageLoading: false }"
    @keydown.escape.window="$store.confirmModal.close(); $store.sidebar.setMobileOpen(false)"
    x-init="
        $store.sidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) { $store.sidebar.setMobileOpen(false); $store.sidebar.isExpanded = false; }
            else { $store.sidebar.isMobileOpen = false; $store.sidebar.isExpanded = true; }
        });">

    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-999999 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">
        Lewati ke konten utama
    </a>

    <x-common.preloader/>

    <div x-show="pageLoading" x-cloak
        role="status" aria-live="polite"
        class="fixed top-0 left-0 right-0 z-999999 flex items-center justify-center gap-2 bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-md">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span>Memuat dan memproses permintaan...</span>
    </div>

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.supervisor.sidebar')

        <div id="main-wrapper" class="flex-1 transition-all duration-300 ease-in-out min-w-0"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            }">
            @include('layouts.supervisor.header')

            <div class="px-4 pt-4 md:px-6 max-w-screen-2xl mx-auto no-print">
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" role="status" aria-live="polite"
                        class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" @click="show = false" aria-label="Tutup pesan keberhasilan"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-emerald-700 hover:bg-emerald-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-emerald-200 dark:hover:bg-emerald-900/50">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('warning'))
                    <div x-data="{ show: true }" x-show="show" role="status" aria-live="polite"
                        class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-200">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 shrink-0 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>{{ session('warning') }}</span>
                        </div>
                        <button type="button" @click="show = false" aria-label="Tutup pesan peringatan"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-amber-800 hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 dark:text-amber-200 dark:hover:bg-amber-900/50">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" role="alert"
                        class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 shrink-0 text-red-700 dark:text-red-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" @click="show = false" aria-label="Tutup pesan kesalahan"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-red-700 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 dark:text-red-200 dark:hover:bg-red-900/50">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div x-data="{ show: true }" x-show="show" role="alert"
                        class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                        <div class="flex items-start gap-2.5">
                            <svg class="h-5 w-5 shrink-0 mt-0.5 text-red-700 dark:text-red-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <div>
                                <p class="font-semibold">Terdapat kesalahan pada isian formulir:</p>
                                <ul class="mt-1 list-disc list-inside text-xs space-y-0.5">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" @click="show = false" aria-label="Tutup daftar kesalahan"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-red-700 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 dark:text-red-200 dark:hover:bg-red-900/50">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif
            </div>

            <main id="main-content" class="p-4 mx-auto max-w-screen-2xl md:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <div x-show="$store.confirmModal.open" x-cloak
        role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title"
        class="fixed inset-0 z-999999 flex items-center justify-center bg-gray-900/60 p-4">
        <div @click.outside="$store.confirmModal.close()"
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <h3 id="confirm-modal-title" class="text-base font-bold text-gray-900 dark:text-white" x-text="$store.confirmModal.title"></h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="$store.confirmModal.message"></p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="$store.confirmModal.close()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="button" @click="$store.confirmModal.proceed()"
                    :class="$store.confirmModal.danger ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : 'bg-brand-600 hover:bg-brand-700 focus-visible:ring-brand-500'"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg px-4 py-2 text-xs font-semibold text-white focus-visible:outline-none focus-visible:ring-2"
                    x-text="$store.confirmModal.confirmText">
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmFormSubmission(event, title, message, confirmText = 'Ya, Lanjutkan', danger = false) {
            const form = event.target.closest('form');
            if (!form) return true;
            if (form.dataset.confirmed === 'true') return true;
            event.preventDefault();
            if (window.Alpine && Alpine.store('confirmModal')) {
                Alpine.store('confirmModal').show(form, title, message, confirmText, danger);
            } else if (confirm(message)) {
                form.dataset.confirmed = 'true';
                form.submit();
            }
            return false;
        }

        function confirmDelete(formId, title, message) {
            const form = document.getElementById(formId);
            if (!form) return;
            if (window.Alpine && Alpine.store('confirmModal')) {
                Alpine.store('confirmModal').show(form, title, message, 'Ya, Hapus', true);
            } else if (confirm(message)) {
                form.submit();
            }
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET' || form.dataset.noSubmitGuard === 'true') return;
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            const btn = event.submitter || form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-wait');
                if (btn.textContent.trim().length > 0 && btn.textContent.trim().length < 40) {
                    btn.textContent = 'Memproses...';
                }
            }
        });
    </script>

    <x-common.live-filter-script />

    <div id="page-scripts">
        @stack('scripts')
    </div>
</body>
</html>
