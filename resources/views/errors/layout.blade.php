@extends('layouts.fullscreen-layout')

@php
    $appIdentity = \App\Models\Setting::getAppIdentity();
    $statusCode = $code ?? '404';
    $pageTitle  = $heading ?? 'Halaman Tidak Ditemukan';
    $message    = $description ?? 'Tautan yang Anda tuju tidak tersedia, telah dipindahkan, atau tidak dapat diakses.';

    $user = auth()->user();
    $dashboardUrl = route('login');
    $primaryLabel = 'Masuk ke Akun';

    if ($user && $user->is_active) {
        $primaryLabel = 'Kembali ke Dashboard';
        $dashboardUrl = match ((string) $user->role) {
            'admin'      => route('admin.dashboard'),
            'supervisor' => route('supervisor.dashboard'),
            'instruktur' => route('instruktur.dashboard'),
            default      => route('home'),
        };
    }

    $badgeTheme = match ((string) $statusCode) {
        '403' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/70 dark:bg-amber-950/60 dark:text-amber-300',
        '419' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/70 dark:bg-blue-950/60 dark:text-blue-300',
        '500' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/70 dark:bg-red-950/60 dark:text-red-300',
        default => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/70 dark:bg-blue-950/60 dark:text-blue-300',
    };
@endphp

@section('full_title', $statusCode . ' - ' . $pageTitle . ' | ' . $appIdentity['app_name'])

@section('content')
<div class="relative flex min-h-screen flex-col justify-between bg-slate-50 px-6 py-8 dark:bg-gray-950 sm:px-10">
    {{-- Header Identitas LPK CTI --}}
    <header class="mx-auto flex w-full max-w-2xl items-center justify-between">
        <a href="{{ $dashboardUrl }}" class="inline-flex items-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 rounded-lg">
            <img src="{{ asset('images/logo/logocti.png') }}"
                 alt="Logo {{ $appIdentity['lpk_name'] }}"
                 class="h-10 w-auto shrink-0 rounded-lg object-contain" />
            <div class="min-w-0">
                <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $appIdentity['lpk_name'] }}</span>
                @if(($appIdentity['lpk_alamat'] ?? '') !== '')
                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $appIdentity['lpk_alamat'] }}</span>
                @endif
            </div>
        </a>
    </header>

    {{-- Konten Utama Error --}}
    <main class="mx-auto my-12 w-full max-w-xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-10">
            <div class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1 text-xs font-semibold {{ $badgeTheme }}">
                <span>Kode {{ $statusCode }}</span>
                <span>&middot;</span>
                <span>{{ $appIdentity['app_name'] }}</span>
            </div>

            <div class="mt-6 flex items-baseline gap-4">
                <span class="font-mono text-4xl font-extrabold tracking-tight text-blue-700 dark:text-blue-400 sm:text-5xl">
                    {{ $statusCode }}
                </span>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                    {{ $pageTitle }}
                </h1>
            </div>

            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ $message }}
            </p>

            @if(!empty($hint))
                <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ $hint }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6 dark:border-gray-800">
                <a href="{{ $dashboardUrl }}"
                   class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>{{ $primaryLabel }}</span>
                </a>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="mx-auto w-full max-w-2xl text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            &copy; {{ date('Y') }} {{ $appIdentity['lpk_name'] }}.
        </p>
    </footer>
</div>
@endsection
