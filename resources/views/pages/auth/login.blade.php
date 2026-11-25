@extends('layouts.fullscreen-layout')

@section('content')
@php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
<div class="relative z-1 min-h-screen bg-white dark:bg-gray-900">
    <div class="relative flex min-h-screen w-full flex-col lg:flex-row dark:bg-gray-900">

        {{-- Panel Kiri: Header, Form Login, dan Footer Copyright --}}
        <div class="flex min-h-screen w-full flex-col justify-between px-6 py-8 sm:px-10 lg:w-1/2 lg:py-10">
            {{-- Header Logo & Alamat --}}
            <div class="mx-auto w-full max-w-md">
                <div class="flex items-center gap-3.5">
                    <img src="{{ asset('images/logo/logocti.png') }}"
                        alt="Logo {{ $appIdentity['lpk_name'] }}"
                        class="h-11 w-auto shrink-0 rounded-lg object-contain" />
                    <div class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $appIdentity['lpk_name'] }}</span>
                        @if($appIdentity['lpk_alamat'] !== '')
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $appIdentity['lpk_alamat'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Form Login di Tengah Vertikal --}}
            <div class="mx-auto my-10 flex w-full max-w-md flex-col justify-center">
                <div class="mb-7">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Masuk ke Akun Anda
                    </h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Masukkan kredensial akun untuk mengakses {{ $appIdentity['app_name'] }}.
                    </p>
                </div>

                @if (session('success'))
                    <div role="status" class="mb-5 flex items-center gap-3 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200">
                        <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="mb-5 flex items-center gap-3 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                        <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">
                    @csrf

                    {{-- Input Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-800 dark:text-slate-200">
                            Alamat Email <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2" />
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                </svg>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="email@lpkcti.id"
                                autocomplete="email"
                                required
                                class="min-h-[44px] w-full rounded-lg border py-2.5 pr-4 pl-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none transition dark:bg-gray-900 dark:text-white dark:placeholder:text-slate-500
                                    @error('email')
                                        border-red-500 bg-red-50 dark:border-red-700 dark:bg-red-950/20
                                    @else
                                        border-slate-300 bg-white dark:border-gray-700
                                    @enderror"
                            />
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs font-medium text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Input Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-800 dark:text-slate-200">
                            Password <span class="text-red-600">*</span>
                        </label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                placeholder="Masukkan password akun"
                                autocomplete="current-password"
                                required
                                class="min-h-[44px] w-full rounded-lg border py-2.5 pr-10 pl-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none transition dark:bg-gray-900 dark:text-white dark:placeholder:text-slate-500
                                    @error('password')
                                        border-red-500 bg-red-50 dark:border-red-700 dark:bg-red-950/20
                                    @else
                                        border-slate-300 bg-white dark:border-gray-700
                                    @enderror"
                            />
                            <button type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute top-1/2 right-1 z-10 inline-flex min-h-[38px] min-w-[38px] -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition-colors hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/30 dark:text-slate-400 dark:hover:text-slate-200">
                                {{-- Ikon Mata Terbuka (Outline Tipis) --}}
                                <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                {{-- Ikon Mata Dicoret (Outline Tipis) --}}
                                <svg x-show="showPassword" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
                                    <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
                                    <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
                                    <path d="m2 2 20 20" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs font-medium text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Checkbox Remember Me --}}
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm text-slate-700 select-none dark:text-slate-300">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600/30 dark:border-gray-700" />
                            <span>Ingat sesi login saya</span>
                        </label>
                    </div>

                    {{-- Tombol Utama --}}
                    <button type="submit" :disabled="submitting"
                        class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 disabled:opacity-60">
                        <span x-show="!submitting">Masuk ke Dashboard</span>
                        <span x-show="submitting" x-cloak>Memverifikasi Kredensial...</span>
                    </button>
                </form>
            </div>

            {{-- Footer Copyright di Paling Bawah --}}
            <div class="mx-auto w-full max-w-md">
                <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                    &copy; {{ date('Y') }} {{ $appIdentity['lpk_name'] }}.
                </p>
            </div>
        </div>

        {{-- Panel Kanan: Branding Panel --}}
        <div class="relative hidden min-h-screen w-full items-center justify-center overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 lg:flex lg:w-1/2">
            {{-- Pola Titik Halus (Subtle Dot Pattern) --}}
            <div class="pointer-events-none absolute inset-0 opacity-10"
                style="background-image: radial-gradient(rgba(255, 255, 255, 0.9) 1px, transparent 1px); background-size: 22px 22px;"
                aria-hidden="true"></div>

            <div class="relative z-10 flex items-center justify-center px-10 xl:px-14">
                <div class="flex max-w-md flex-col items-start text-left">
                    <div class="mb-7 flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 p-2 ring-1 ring-white/20">
                            <img src="{{ asset('images/logo/logocti.png') }}"
                                alt="Logo {{ $appIdentity['lpk_name'] }}"
                                class="h-full w-auto object-contain" />
                        </div>
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium whitespace-nowrap text-white">
                            Sistem Informasi Internal
                        </span>
                    </div>

                    <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ $appIdentity['app_name'] }}
                    </h2>

                    <p class="mt-3.5 text-sm leading-relaxed text-blue-100/90">
                        {{ $appIdentity['app_description'] !== '' ? $appIdentity['app_description'] : 'Kelola jadwal mengajar, rekapitulasi kehadiran instruktur, kode akses evaluasi peserta, serta laporan kinerja secara terpusat.' }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
