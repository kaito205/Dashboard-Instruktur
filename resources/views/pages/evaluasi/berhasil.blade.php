@extends('layouts.fullscreen-layout')

@section('full_title', 'Evaluasi Berhasil | LPK CTI')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                    Evaluasi Berhasil Direkam
                </span>
                <h1 class="mt-0.5 text-xl font-bold text-gray-900 dark:text-white">
                    Terima Kasih atas Penilaian Anda
                </h1>
            </div>
        </div>

        @php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Evaluasi Anda telah tersimpan ke dalam sistem {{ $appIdentity['lpk_name'] }} dan akan digunakan sebagai bahan evaluasi peningkatan mutu pengajaran instruktur.
        </p>

        @if (!empty($summary))
            <dl class="mt-5 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-gray-600 dark:text-gray-400">Instruktur Dinilai</dt>
                    <dd class="text-right font-semibold text-gray-900 dark:text-white">{{ $summary['instruktur'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-gray-600 dark:text-gray-400">Materi Sesi</dt>
                    <dd class="text-right font-semibold text-gray-900 dark:text-white">{{ $summary['materi'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-gray-600 dark:text-gray-400">Waktu Tercatat</dt>
                    <dd class="text-right text-gray-800 dark:text-gray-200">{{ $summary['waktu'] ?? now()->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        @endif

        <div class="mt-6">
            <a href="{{ route('evaluasi.publik.index', !empty($summary['kode']) ? ['kode' => $summary['kode']] : []) }}"
                class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Evaluasi Sesi / Instruktur Lainnya
            </a>
        </div>
    </div>
</div>
@endsection
