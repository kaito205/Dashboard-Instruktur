@extends('layouts.admin.app')
@section('title', 'Data Kelas')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Data Kelas</h1>
        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Master data kelas pelatihan untuk jadwal mengajar, kode evaluasi, dan filter laporan.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.kelas.index', array_filter(['search' => $search, 'status' => $status, 'program' => $program, 'arsip' => empty($arsip) ? 1 : null])) }}"
           class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            {{ !empty($arsip) ? 'Sembunyikan Arsip' : 'Tampilkan Arsip' . (($ringkasan['arsip'] ?? 0) > 0 ? ' (' . $ringkasan['arsip'] . ')' : '') }}
        </a>
        <a href="{{ route('admin.kelas.create') }}"
           class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kelas
        </a>
    </div>
</div>

<div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-gray-800 dark:bg-gray-800">
    <div class="grid grid-cols-2 gap-px sm:grid-cols-4">
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs text-gray-600 dark:text-gray-300">Total Kelas</p>
            <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $ringkasan['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs text-gray-600 dark:text-gray-300">Kelas Aktif</p>
            <p class="mt-1 font-mono text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $ringkasan['aktif'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs text-gray-600 dark:text-gray-300">Kelas Selesai</p>
            <p class="mt-1 font-mono text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $ringkasan['selesai'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 dark:bg-gray-900">
            <p class="text-xs text-gray-600 dark:text-gray-300">Jadwal Terhubung</p>
            <p class="mt-1 font-mono text-2xl font-bold text-brand-700 dark:text-brand-300">{{ $ringkasan['total_jadwal'] ?? 0 }}</p>
        </div>
    </div>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('admin.kelas.index') }}" class="flex flex-wrap items-end gap-3">
        @if(!empty($arsip))
            <input type="hidden" name="arsip" value="1">
        @endif
        <div class="min-w-[200px] flex-1">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
            <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari kode, nama kelas, program, ruangan..."
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>
        <div class="w-full sm:w-auto">
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                @foreach(\App\Models\Kelas::STATUS_LIST as $optKey => $optLabel)
                    <option value="{{ $optKey }}" {{ ($status ?? request('status')) === $optKey ? 'selected' : '' }}>{{ $optLabel }}</option>
                @endforeach
            </select>
        </div>
        @if(($programList ?? collect())->isNotEmpty())
        <div class="w-full sm:w-auto">
            <label for="program" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Program Pelatihan</label>
            <select id="program" name="program" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Program</option>
                @foreach($programList as $prog)
                    <option value="{{ $prog }}" {{ ($program ?? request('program')) === $prog ? 'selected' : '' }}>{{ $prog }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        @if(request()->hasAny(['search', 'cari', 'status', 'program', 'arsip']))
        <a href="{{ route('admin.kelas.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Reset</a>
        @endif
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-4 py-3">Kode &amp; Nama Kelas</th>
                    <th class="px-4 py-3">Program Pelatihan</th>
                    <th class="px-4 py-3">Pengampu &amp; Jadwal</th>
                    <th class="px-4 py-3">Ruangan &amp; Kuota</th>
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($kelasList as $k)
                @php
                    $pengampuNames = $k->jadwals
                        ->pluck('instruktur')
                        ->filter()
                        ->unique('id')
                        ->pluck('nama_lengkap')
                        ->values();
                    $statusCls = match ($k->status) {
                        'aktif'   => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                        'selesai' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        default   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                    };
                @endphp
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 {{ $k->trashed() ? 'bg-gray-50/70 dark:bg-gray-800/20' : '' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-md bg-brand-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $k->kode_kelas }}</span>
                            <a href="{{ route('admin.kelas.show', $k->id) }}" class="font-semibold text-gray-900 hover:text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">
                                {{ $k->nama_kelas }}
                            </a>
                        </div>
                        @if($k->keterangan)
                            <p class="mt-0.5 line-clamp-1 text-xs text-gray-600 dark:text-gray-300">{{ $k->keterangan }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                        {{ $k->program_pelatihan ?: '-' }}
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">
                            @if($pengampuNames->isNotEmpty())
                                {{ $pengampuNames->take(2)->implode(', ') }}@if($pengampuNames->count() > 2) <span class="text-gray-500 dark:text-gray-400">+{{ $pengampuNames->count() - 2 }} lainnya</span>@endif
                            @else
                                <span class="text-gray-500 dark:text-gray-400">Belum ada pengampu</span>
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                            {{ $k->jadwals_count ?? 0 }} jadwal &middot; {{ $k->kode_evaluasis_count ?? 0 }} kode evaluasi
                        </p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">{{ $k->ruangan_default_label }}</p>
                        <p class="mt-0.5 font-mono text-xs text-gray-600 dark:text-gray-300">Kuota {{ $k->kuota_peserta }} peserta</p>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                        {{ $k->periode_label }}
                    </td>
                    <td class="px-4 py-3">
                        @if($k->trashed())
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Diarsipkan</span>
                        @else
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $statusCls }}">{{ $k->status_label }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            @if($k->trashed())
                                <a href="{{ route('admin.kelas.show', $k->id) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Detail" aria-label="Detail {{ $k->nama_kelas }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.kelas.restore', $k->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                        Pulihkan
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('admin.kelas.show', $k) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Detail" aria-label="Detail {{ $k->nama_kelas }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('admin.kelas.edit', $k) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Edit" aria-label="Edit {{ $k->nama_kelas }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.kelas.toggle', $k) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="{{ $k->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $k->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $k->nama_kelas }}">
                                        @if($k->status === 'aktif')
                                        <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.kelas.destroy', $k) }}" class="inline"
                                      onsubmit="return confirmFormSubmission(event, 'Arsipkan Kelas', 'Yakin ingin mengarsipkan kelas {{ addslashes($k->kode_kelas . ' - ' . $k->nama_kelas) }}? Riwayat jadwal dan evaluasi tetap tersimpan.', 'Ya, Arsipkan', true)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:text-red-300" title="Arsipkan" aria-label="Arsipkan {{ $k->nama_kelas }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">
                        Belum ada data kelas yang sesuai filter.
                        @if(!request()->hasAny(['search', 'cari', 'status', 'program', 'arsip']))
                            <a href="{{ route('admin.kelas.create') }}" class="ml-1 font-semibold text-brand-700 hover:underline dark:text-brand-300">Tambah Kelas</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($kelasList->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
        {{ $kelasList->links() }}
    </div>
    @endif
</div>
@endsection
