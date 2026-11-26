@extends('layouts.admin.app')
@section('title', 'Preview & Validasi Import Data')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.import.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Halaman Import
    </a>
    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Preview Import: {{ ucfirst($jenis) }}</h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $summary['total'] ?? count($preview) }} baris diperiksa sebelum disimpan.</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-4 dark:border-gray-800 dark:bg-gray-800">
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Diperiksa</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-gray-900 dark:text-white">{{ $summary['total'] ?? count($preview) }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Baris Valid</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $summary['valid'] ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Duplikat (Lewat)</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $summary['duplikat'] ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Tidak Valid</p>
        <p class="mt-1 font-mono text-2xl font-semibold text-red-700 dark:text-red-300">{{ $summary['invalid'] ?? 0 }}</p>
    </div>
</div>

<div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Pratinjau {{ count($preview) }} baris data</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-3 py-2.5">Baris</th>
                    <th class="px-3 py-2.5">Status Validasi</th>
                    @foreach($headers as $header)
                    <th class="px-3 py-2.5 font-mono">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($preview as $i => $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <td class="px-3 py-2 font-mono text-gray-600 dark:text-gray-300">{{ $row['nomor'] ?? ($i + 2) }}</td>
                    <td class="px-3 py-2">
                        @php
                            $st = $row['status'] ?? 'valid';
                            $badgeClass = [
                                'valid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                'duplikat' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                'invalid' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                            ][$st] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                        @endphp
                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize {{ $badgeClass }}">{{ $st }}</span>
                        @if(!empty($row['message']))
                            <span class="mt-0.5 block text-xs text-gray-600 dark:text-gray-300">{{ $row['message'] }}</span>
                        @endif
                    </td>
                    @foreach($row['cells'] ?? [] as $cell)
                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $cell !== null && $cell !== '' ? $cell : '-' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.import.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Batal</a>
    <form method="POST" action="{{ route('admin.import.proses') }}">
        @csrf
        @if (!empty($previewToken))
            <input type="hidden" name="preview_token" value="{{ $previewToken }}">
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Simpan Data ({{ $summary['valid'] ?? 0 }} Baris Valid)
        </button>
    </form>
</div>
@endsection
