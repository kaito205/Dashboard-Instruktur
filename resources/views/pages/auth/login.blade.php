@extends('layouts.fullscreen-layout')

@section('content')
@php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
<div class="relative z-1 bg-white dark:bg-gray-900 min-h-screen">
    <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">

        <div class="flex w-full flex-1 flex-col lg:w-1/2">
            <div class="mx-auto w-full max-w-md px-6 pt-10">
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('images/logo/logocti.png') }}"
                        alt="Logo {{ $appIdentity['lpk_name'] }}"
                        class="h-11 w-auto rounded-lg object-contain shrink-0" />
                    <div class="min-w-0">
                        <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ $appIdentity['lpk_name'] }}</span>
                        @if($appIdentity['lpk_alamat'] !== '')
                            <span class="block text-xs font-medium text-brand-600 dark:text-brand-300">{{ $appIdentity['lpk_alamat'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 pb-10">
                <div class="mb-7">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Masuk ke Akun Anda
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
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

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-gray-200">
                            Alamat Email <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="email@lpkcti.id"
                            autocomplete="email"
                            required
                            class="min-h-[44px] w-full rounded-lg border px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400
                                @error('email')
                                    border-red-500 bg-red-50 dark:border-red-700 dark:bg-red-950/20
                                @else
                                    border-gray-300 bg-transparent dark:border-gray-700
                                @enderror"
                        />
                        @error('email')
                            <p class="mt-1.5 text-xs font-medium text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-gray-200">
                            Password <span class="text-red-600">*</span>
                        </label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                placeholder="Masukkan password akun"
                                autocomplete="current-password"
                                required
                                class="min-h-[44px] w-full rounded-lg border py-2.5 pr-12 pl-4 text-sm text-gray-900 placeholder:text-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400
                                    @error('password')
                                        border-red-500 bg-red-50 dark:border-red-700 dark:bg-red-950/20
                                    @else
                                        border-gray-300 bg-transparent dark:border-gray-700
                                    @enderror"
                            />
                            <button type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute top-1/2 right-1 z-10 -translate-y-1/2 inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-md text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
                                <svg x-show="!showPassword" class="fill-current w-5 h-5" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/></svg>
                                <svg x-show="showPassword" class="fill-current w-5 h-5" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709Z"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs font-medium text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2.5 min-h-[44px] cursor-pointer select-none text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            <span>Ingat sesi login saya</span>
                        </label>
                    </div>

                    <button type="submit" :disabled="submitting"
                        class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-60">
                        <span x-show="!submitting">Masuk ke Dashboard</span>
                        <span x-show="submitting" x-cloak>Memverifikasi Kredensial...</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-600 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ $appIdentity['lpk_name'] }}.
                </p>
            </div>
        </div>

        <div class="bg-brand-600 relative hidden min-h-screen w-full items-center lg:grid lg:w-1/2 dark:bg-brand-950">
            <div class="z-1 flex items-center justify-center px-8">
                <div class="flex max-w-md flex-col items-start text-left">
                    <div class="mb-6 flex items-center gap-4">
                        <img src="{{ asset('images/logo/logocti.png') }}"
                            alt="Logo {{ $appIdentity['lpk_name'] }}"
                            class="h-14 w-auto object-contain" />
                        <span class="inline-flex items-center rounded-md bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                            Sistem Informasi Internal {{ $appIdentity['lpk_name'] }}
                        </span>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3">
                        {{ $appIdentity['app_name'] }}
                    </h2>
                    <p class="text-sm text-white/90 leading-relaxed">
                        {{ $appIdentity['app_description'] !== '' ? $appIdentity['app_description'] : 'Kelola jadwal mengajar, rekapitulasi kehadiran instruktur, kode akses evaluasi peserta, serta laporan kinerja secara terpusat.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
