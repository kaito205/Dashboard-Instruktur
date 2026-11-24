@extends('layouts.admin.app')
@section('title', 'Data Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Data Instruktur</h1>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.instruktur.index', ['arsip' => !empty($arsip) ? 0 : 1]) }}"
           class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            {{ !empty($arsip) ? 'Sembunyikan Arsip' : 'Tampilkan Arsip' }}
        </a>
        <a href="{{ route('admin.instruktur.create') }}"
           class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Tambah Instruktur
        </a>
    </div>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('admin.instruktur.index') }}" class="flex flex-wrap items-end gap-3">
        @if(!empty($arsip))
            <input type="hidden" name="arsip" value="1">
        @endif
        <div class="min-w-[200px] flex-1">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
            <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari nama, kode, keahlian..."
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>
        <div class="w-full sm:w-auto">
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="aktif" {{ ($status ?? request('status')) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ ($status ?? request('status')) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="ketersediaan" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Ketersediaan</label>
            <select id="ketersediaan" name="ketersediaan" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Ketersediaan</option>
                <option value="tersedia" {{ ($ketersediaan ?? request('ketersediaan')) === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                <option value="cuti" {{ ($ketersediaan ?? request('ketersediaan')) === 'cuti' ? 'selected' : '' }}>Cuti</option>
                <option value="izin" {{ ($ketersediaan ?? request('ketersediaan')) === 'izin' ? 'selected' : '' }}>Izin</option>
            </select>
        </div>
        @if(($keahlianList ?? collect())->isNotEmpty())
        <div class="w-full sm:w-auto">
            <label for="keahlian" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Keahlian</label>
            <select id="keahlian" name="keahlian" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Keahlian</option>
                @foreach($keahlianList as $bidang)
                <option value="{{ $bidang }}" {{ ($keahlian ?? request('keahlian')) === $bidang ? 'selected' : '' }}>{{ $bidang }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        @if(request()->hasAny(['search', 'cari', 'status', 'keahlian', 'ketersediaan', 'arsip']))
        <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Reset</a>
        @endif
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-4 py-3">Instruktur</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Keahlian &amp; Sertifikasi</th>
                    <th class="px-4 py-3">Ketersediaan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($instrukturs as $ins)
                @php
                    $sertInfo = $ins->status_sertifikasi;
                @endphp
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 {{ $ins->trashed() ? 'bg-gray-50/70 dark:bg-gray-800/20' : '' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $ins->foto_url }}" alt="{{ $ins->nama_lengkap }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $ins->nama_lengkap }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $ins->email ?? '-' }}{{ $ins->no_telepon ? ' · ' . $ins->no_telepon : '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $ins->kode_instruktur }}</td>
                    <td class="px-4 py-3">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $ins->bidang_keahlian ?? '-' }}</p>
                        @if($ins->nama_sertifikasi || ($ins->sertifikasis_count ?? 0) > 0)
                            @php
                                $sertBadgeCls = match ($sertInfo['color']) {
                                    'red'   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                    'amber' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                    'gray'  => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                };
                            @endphp
                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $ins->nama_sertifikasi ?: 'Sertifikasi Kompetensi' }}
                                <span class="ml-1 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium {{ $sertBadgeCls }}">
                                    {{ $sertInfo['label'] }}
                                </span>
                                @if(($ins->sertifikasis_count ?? 0) > 1)
                                    <span class="ml-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">+{{ $ins->sertifikasis_count - 1 }} lainnya</span>
                                @endif
                            </p>
                        @else
                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Belum ada sertifikasi</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $ketStatus = $ins->status_ketersediaan ?? 'tersedia';
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $ketStatus === 'tersedia' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                            {{ $ins->ketersediaan_label }}
                        </span>
                        @if($ketStatus !== 'tersedia' && ($ins->cuti_mulai || $ins->cuti_selesai))
                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $ins->cuti_mulai?->format('d/m/Y') ?? '-' }} s.d. {{ $ins->cuti_selesai?->format('d/m/Y') ?? '-' }}
                            </p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($ins->trashed())
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Diarsipkan</span>
                        @else
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize {{ $ins->status === 'aktif' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300' }}">{{ $ins->status_label }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            @if($ins->trashed())
                                <form method="POST" action="{{ route('admin.instruktur.restore', $ins->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                        Pulihkan
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('admin.instruktur.show', $ins) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Detail" aria-label="Detail {{ $ins->nama_lengkap }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('admin.instruktur.edit', $ins) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Edit" aria-label="Edit {{ $ins->nama_lengkap }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.instruktur.toggle-status', $ins) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="{{ $ins->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $ins->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $ins->nama_lengkap }}">
                                        @if($ins->status === 'aktif')
                                        <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                </form>
                                <form id="del-ins-{{ $ins->id }}" method="POST" action="{{ route('admin.instruktur.destroy', $ins) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('del-ins-{{ $ins->id }}', 'Arsipkan Instruktur', 'Yakin ingin mengarsipkan instruktur {{ addslashes($ins->nama_lengkap) }}?')"
                                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:text-red-300" title="Arsipkan" aria-label="Arsipkan {{ $ins->nama_lengkap }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">
                        Belum ada data instruktur yang sesuai filter.
                        @if(!request()->hasAny(['search', 'cari', 'status', 'keahlian', 'ketersediaan', 'arsip']))
                            <a href="{{ route('admin.instruktur.create') }}" class="ml-1 font-semibold text-brand-700 hover:underline dark:text-brand-300">Tambah Instruktur</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($instrukturs->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
        {{ $instrukturs->links() }}
    </div>
    @endif
</div>
@endsection
