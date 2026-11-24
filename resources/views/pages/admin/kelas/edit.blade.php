@extends('layouts.admin.app')
@section('title', 'Edit Kelas: ' . $kelas->nama_kelas)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('admin.kelas.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar Kelas
        </a>
        <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Edit Kelas: {{ $kelas->display_label }}</h1>
    </div>
    <a href="{{ route('admin.kelas.show', $kelas) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        Lihat Detail Kelas
    </a>
</div>

<div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-5 rounded-lg border border-brand-200 bg-brand-50/70 px-4 py-3 text-xs text-brand-800 dark:border-brand-800/70 dark:bg-brand-950/40 dark:text-brand-200">
        Perubahan kode atau nama kelas akan ikut disinkronkan secara otomatis ke jadwal mengajar yang terhubung.
    </div>

    <form method="POST" action="{{ route('admin.kelas.update', $kelas) }}" class="space-y-5">
        @csrf
        @method('PUT')
        @include('pages.admin.kelas._form', ['kelas' => $kelas, 'submitLabel' => 'Simpan Perubahan'])
    </form>
</div>
@endsection
