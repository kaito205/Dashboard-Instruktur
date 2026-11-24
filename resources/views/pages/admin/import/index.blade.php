@extends('layouts.admin.app')
@section('title', 'Import Data Excel/CSV')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Import Data Excel / CSV</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.import.template', 'absensi') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Template CSV Absensi
        </a>
        <a href="{{ route('admin.import.template', 'evaluasi') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Template CSV Evaluasi
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Unggah Berkas</h2>
        <form method="POST" action="{{ route('admin.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="jenis" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Data <span class="text-red-600 dark:text-red-300">*</span></label>
                <select id="jenis" name="jenis" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Pilih Jenis Data</option>
                    <option value="absensi" {{ old('jenis') === 'absensi' ? 'selected' : '' }}>Data Absensi (Spreadsheet)</option>
                    <option value="evaluasi" {{ old('jenis') === 'evaluasi' ? 'selected' : '' }}>Data Evaluasi (Google Form)</option>
                </select>
            </div>

            <div>
                <label for="file" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Berkas CSV / Excel <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="file" id="file" name="file" accept=".csv,.xlsx,.xls" required
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-4 file:min-h-[32px] file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700 hover:file:bg-brand-100 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950/60 dark:file:text-brand-300">
                <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-300">Format: CSV atau XLSX (maks. 10 MB). Sesuaikan kolom dengan template Absensi atau Evaluasi; data akan divalidasi pada tahap pratinjau sebelum disimpan.</p>
            </div>

            <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Preview &amp; Validasi Data
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Riwayat Import</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-3 py-2.5">Berkas</th>
                        <th class="px-3 py-2.5">Jenis</th>
                        <th class="px-3 py-2.5">Total</th>
                        <th class="px-3 py-2.5">Berhasil</th>
                        <th class="px-3 py-2.5">Gagal / Lewat</th>
                        <th class="px-3 py-2.5">Status</th>
                        <th class="px-3 py-2.5">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($riwayat as $h)
                    <tr>
                        <td class="max-w-[160px] truncate px-3 py-2.5 font-medium text-gray-900 dark:text-white" title="{{ $h->nama_file }}">{{ $h->nama_file }}</td>
                        <td class="px-3 py-2.5 capitalize text-gray-700 dark:text-gray-300">{{ $h->jenis }}</td>
                        <td class="px-3 py-2.5 font-mono text-gray-700 dark:text-gray-300">{{ $h->total_baris }}</td>
                        <td class="px-3 py-2.5 font-mono text-emerald-700 dark:text-emerald-300">{{ $h->baris_berhasil }}</td>
                        <td class="px-3 py-2.5 font-mono text-red-700 dark:text-red-300">{{ $h->baris_gagal }}</td>
                        <td class="px-3 py-2.5">
                            @php
                                $impBadge = [
                                    'sukses' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                    'gagal' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                    'parsial' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                ];
                            @endphp
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize {{ $impBadge[$h->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ $h->status_label }}</span>
                        </td>
                        <td class="px-3 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if(!empty($h->detail_error))
                    <tr class="bg-red-50/40 dark:bg-red-950/10">
                        <td colspan="7" class="px-3 py-2 text-xs text-red-800 dark:text-red-300">
                            <details>
                                <summary class="cursor-pointer font-medium">Lihat detail pesan baris gagal / duplikat ({{ count($h->detail_error) }} catatan)</summary>
                                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                    @foreach(array_slice($h->detail_error, 0, 15) as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada riwayat import.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($riwayat->hasPages())
        <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-800">
            {{ $riwayat->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
