@extends('layouts.admin.app')
@section('title', 'Tambah Pengguna')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.pengguna.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar Pengguna
    </a>
    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Tambah Pengguna</h1>
</div>

<div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <form method="POST" action="{{ route('admin.pengguna.store') }}" enctype="multipart/form-data" class="space-y-5"
          x-data="{ role: @js(old('role', '')) }">
        @csrf

        <div>
            <label for="foto_profil" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Foto Profil (Opsional)</label>
            <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">JPG, PNG, atau WebP maksimal 2MB.</p>
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600 dark:text-red-400">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Nama lengkap"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat Email <span class="text-red-600 dark:text-red-400">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="email@domain.id"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>

        <div>
            <label for="role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Role <span class="text-red-600 dark:text-red-400">*</span></label>
            <select id="role" name="role" x-model="role" required
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Pilih Role</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                <option value="instruktur" {{ old('role') === 'instruktur' ? 'selected' : '' }}>Instruktur</option>
            </select>

            <div x-show="role === 'instruktur'" x-cloak
                 class="mt-2.5 rounded-lg border border-brand-200 bg-brand-50/70 px-3.5 py-3 text-xs text-gray-800 dark:border-brand-800 dark:bg-brand-950/40 dark:text-gray-200">
                Profil dasar instruktur (kode otomatis <span class="font-mono font-semibold">{{ $nextInstrukturCode ?? 'INS-CTI' }}</span>) akan dibuat dan dihubungkan secara otomatis. Data keahlian, kontak, dan sertifikasi dapat dilengkapi setelah akun disimpan.
            </div>
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Password Awal <span class="text-red-600 dark:text-red-400">*</span></label>
            <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter huruf dan angka"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
        </div>

        <label for="is_active" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                   class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <span>Aktifkan akun</span>
        </label>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
            <a href="{{ route('admin.pengguna.index') }}"
               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Simpan Pengguna
            </button>
        </div>
    </form>
</div>
@endsection
