@extends('layouts.supervisor.app')
@section('title', 'Kode Akses Evaluasi')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Daftar Kode Akses Evaluasi Kelas</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pantau daftar kode akses evaluasi kelas dan salin tautan portal evaluasi peserta</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('evaluasi.publik.index') }}" target="_blank" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-brand-600 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100 dark:border-brand-500 dark:bg-brand-950/40 dark:text-brand-300">
            Buka Portal Evaluasi Peserta (/evaluasi)
        </a>
        <a href="{{ route('supervisor.evaluasi.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Lihat Hasil Evaluasi
        </a>
    </div>
</div>

<div>
    <div>
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('supervisor.evaluasi.kode.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[180px] flex-1">
                    <label for="filter_instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Filter Instruktur</label>
                    <select id="filter_instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Kelas / Instruktur</option>
                        @foreach($instrukturs as $ins)
                            <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Kode</label>
                    <select id="filter_status" name="status" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="1" {{ (string) ($statusFilter ?? request('status')) === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ (string) ($statusFilter ?? request('status')) === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filter</button>
                @if(request()->hasAny(['instruktur', 'status']))
                    <a href="{{ route('supervisor.evaluasi.kode.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kode Kelas</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Nama Kelas &amp; Keterangan</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Instruktur</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Pengisian</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($kodes as $kode)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 font-mono text-sm font-bold text-brand-700 dark:bg-brand-950/50 dark:text-brand-300">{{ $kode->kode }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $kode->nama_kelas ?? ('Kelas ' . $kode->kode) }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $kode->keterangan ?: 'Evaluasi Kelas' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($kode->instruktur)
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $kode->instruktur->nama_lengkap }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $kode->instruktur->kode_instruktur }}</p>
                                @else
                                    <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                        Multi-Instruktur (Per Sesi Jadwal)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">
                                {{ $kode->jumlah_diisi }} / {{ $kode->max_pengisian ?? 'Tanpa Batas' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kode->is_active ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300' }}">
                                    {{ $kode->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1.5" x-data="{ copied: false }">
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ route('evaluasi.publik.index', ['kode' => $kode->kode]) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-950/50 dark:text-brand-300">
                                        <span x-show="!copied">Salin Link Kelas</span>
                                        <span x-show="copied" x-cloak class="text-emerald-700 dark:text-emerald-300">Link Tersalin!</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada kode akses evaluasi yang dibuat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($kodes->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $kodes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
