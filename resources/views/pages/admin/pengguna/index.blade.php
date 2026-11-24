@extends('layouts.admin.app')
@section('title', 'Pengguna')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pengguna</h1>
    <a href="{{ route('admin.pengguna.create') }}"
       class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        Tambah Pengguna
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
            <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari nama atau email..."
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>
        <div class="w-full sm:w-auto">
            <label for="role" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Role</label>
            <select id="role" name="role" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Role</option>
                <option value="admin" {{ ($role ?? request('role')) === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="supervisor" {{ ($role ?? request('role')) === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                <option value="instruktur" {{ ($role ?? request('role')) === 'instruktur' ? 'selected' : '' }}>Instruktur</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="1" {{ (string) ($status ?? request('status')) === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ (string) ($status ?? request('status')) === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Terapkan</button>
        @if(request()->hasAny(['search', 'role', 'status']))
        <a href="{{ route('admin.pengguna.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Reset</a>
        @endif
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Terdaftar</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($pengguna as $user)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        <div class="flex items-center gap-3">
                            <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                            <span>{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize
                            {{ $user->role === 'admin' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' : ($user->role === 'supervisor' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300') }}">
                            {{ $user->role_label }}
                        </span>
                        @if ($user->role === 'instruktur' && $user->instruktur && ! $user->instruktur->trashed())
                            <a href="{{ route('admin.instruktur.edit', $user->instruktur) }}"
                               class="mt-1 block font-mono text-xs font-medium text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300"
                               title="Edit Profil Instruktur">
                                {{ $user->instruktur->kode_instruktur }}
                            </a>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300' }}">
                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.pengguna.edit', $user) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Edit" aria-label="Edit {{ $user->name }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.pengguna.toggle-status', $user) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $user->name }}">
                                    @if($user->is_active)
                                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </button>
                            </form>
                            <form id="del-user-{{ $user->id }}" method="POST" action="{{ route('admin.pengguna.destroy', $user) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                @php
                                    $deleteConfirmMsg = $user->role === 'instruktur'
                                        ? 'Apakah Anda yakin ingin menghapus akun ' . $user->name . '? Akun instruktur yang sudah memiliki riwayat mengajar atau evaluasi akan dilindungi dan disarankan menggunakan tombol Nonaktifkan.'
                                        : 'Apakah Anda yakin ingin menghapus akun ' . $user->name . '?';
                                @endphp
                                <button type="button" onclick="confirmDelete('del-user-{{ $user->id }}', 'Hapus Pengguna', @js($deleteConfirmMsg))"
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:text-red-300" title="Hapus" aria-label="Hapus {{ $user->name }}">
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
                        Tidak ada pengguna ditemukan.
                        @if(!request()->hasAny(['search', 'role', 'status']))
                            <a href="{{ route('admin.pengguna.create') }}" class="ml-1 font-semibold text-brand-700 hover:underline dark:text-brand-300">Tambah Pengguna</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pengguna->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
        {{ $pengguna->links() }}
    </div>
    @endif
</div>
@endsection
