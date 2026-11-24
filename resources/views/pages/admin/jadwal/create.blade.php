@extends('layouts.admin.app')
@section('title', 'Tambah Jadwal Mengajar')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.jadwal.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar Jadwal
    </a>
    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Tambah Jadwal Mengajar</h1>
</div>

<div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    @include('pages.admin.jadwal._form', [
        'jadwal'      => null,
        'formAction'  => route('admin.jadwal.store'),
        'submitLabel' => 'Simpan Jadwal',
    ])
</div>
@endsection
