@extends('layouts.supervisor.app')
@section('title', 'Data Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Data Seluruh Instruktur</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pantau profil, bidang keahlian, kontak, dan status instruktur {{ \App\Models\Setting::getAppIdentity()['lpk_name'] }}</p>
    </div>
    <span class="inline-flex min-h-[44px] items-center rounded-lg border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-semibold text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
        Akses Baca Saja (Monitoring Supervisor)
    </span>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('supervisor.instruktur.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
            <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari nama, kode, email, atau keahlian..."
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-800 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-400">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="aktif" {{ ($status ?? request('status')) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ ($status ?? request('status')) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        @if(($keahlianList ?? collect())->isNotEmpty())
        <div>
            <label for="keahlian" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Bidang Keahlian</label>
            <select id="keahlian" name="keahlian" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Bidang</option>
                @foreach($keahlianList as $bidang)
                <option value="{{ $bidang }}" {{ ($keahlian ?? request('keahlian')) === $bidang ? 'selected' : '' }}>{{ $bidang }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
        @if(request()->hasAny(['search', 'cari', 'status', 'keahlian']))
        <a href="{{ route('supervisor.instruktur.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
        @endif
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Instruktur</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kode</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Bidang Keahlian</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kontak</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($instrukturs as $ins)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $ins->foto_url }}" alt="{{ $ins->nama_lengkap }}" class="h-9 w-9 shrink-0 rounded-full object-cover">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $ins->nama_lengkap }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $ins->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $ins->kode_instruktur }}</td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ins->bidang_keahlian ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ins->no_telepon ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $ins->status === 'aktif' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300' }}">{{ $ins->status_label }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('supervisor.instruktur.show', $ins) }}" class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Lihat Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada data instruktur.</td>
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
