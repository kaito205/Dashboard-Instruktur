@extends('layouts.admin.app')
@section('title', 'Edit Pengguna')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.pengguna.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar Pengguna
    </a>
    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Edit Pengguna: {{ $pengguna->name }}</h1>
</div>

<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Informasi Akun</h2>
            @if ($pengguna->role === 'instruktur' && $pengguna->instruktur && ! $pengguna->instruktur->trashed())
                <a href="{{ route('admin.instruktur.edit', $pengguna->instruktur) }}"
                   class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-brand-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-brand-300 dark:hover:bg-gray-700">
                    Lengkapi / Edit Profil Instruktur ({{ $pengguna->instruktur->kode_instruktur }})
                </a>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.pengguna.update', $pengguna) }}" enctype="multipart/form-data" class="space-y-5"
              x-data="{ role: @js(old('role', $pengguna->role)), initialRole: @js($pengguna->role) }">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <img src="{{ $pengguna->foto_url }}" alt="{{ $pengguna->name }}" class="h-16 w-16 shrink-0 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="min-w-0 flex-1">
                    <label for="foto_profil" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Foto Profil (Opsional)</label>
                    <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">JPG, PNG, atau WebP maksimal 2MB.</p>
                    @error('foto_profil')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600 dark:text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $pengguna->name) }}" required
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @error('name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Email <span class="text-red-600 dark:text-red-400">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $pengguna->email) }}" required
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Role <span class="text-red-600 dark:text-red-400">*</span></label>
                @if($pengguna->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $pengguna->role }}">
                    <p class="rounded-lg bg-gray-100 px-3.5 py-2.5 text-sm font-medium capitalize text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $pengguna->role }} (akun sendiri tidak dapat mengubah role)</p>
                @else
                <select id="role" name="role" x-model="role" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="admin" {{ old('role', $pengguna->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="supervisor" {{ old('role', $pengguna->role) === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="instruktur" {{ old('role', $pengguna->role) === 'instruktur' ? 'selected' : '' }}>Instruktur</option>
                </select>
                @error('role')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div x-show="initialRole === 'instruktur' && role !== 'instruktur'" x-cloak
                     class="mt-2.5 rounded-lg border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
                    Mengubah role dari Instruktur akan melepas tautan akun dan mengarsipkan profil instruktur agar riwayat mengajar tetap tersimpan.
                </div>

                <div x-show="initialRole !== 'instruktur' && role === 'instruktur'" x-cloak
                     class="mt-2.5 rounded-lg border border-brand-200 bg-brand-50/70 px-3.5 py-2.5 text-xs text-gray-800 dark:border-brand-800 dark:bg-brand-950/40 dark:text-gray-200">
                    Profil dasar instruktur akan otomatis dibuat atau dipulihkan setelah perubahan disimpan.
                </div>
                @endif
            </div>

            @if($pengguna->id !== auth()->id())
            <label for="is_active" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $pengguna->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Akun aktif</span>
            </label>
            @endif

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                <a href="{{ route('admin.pengguna.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Batal</a>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Reset Password</h2>

        @if (session('generated_password'))
            <div x-data="{ copied: false }" role="status" class="mb-5 rounded-xl border border-brand-300 bg-brand-50 p-4 text-sm text-gray-900 dark:border-brand-800 dark:bg-brand-950/50 dark:text-gray-100">
                <p class="font-semibold text-brand-800 dark:text-brand-200">Password Sementara Siap Dibagikan</p>
                <p class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                    Password sementara untuk akun <span class="font-semibold">{{ session('generated_password_user', $pengguna->email) }}</span> hanya ditampilkan satu kali. Pastikan menyalin sebelum meninggalkan halaman ini.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <code class="rounded-lg border border-brand-200 bg-white px-3 py-2 font-mono text-sm font-bold text-gray-900 dark:border-brand-800 dark:bg-gray-900 dark:text-white">{{ session('generated_password') }}</code>
                    <button type="button"
                            @click="navigator.clipboard?.writeText(@js(session('generated_password'))); copied = true; setTimeout(() => copied = false, 2500)"
                            class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span x-text="copied ? 'Tersalin' : 'Salin Password'">Salin Password</span>
                    </button>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pengguna.reset-password', $pengguna) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="new_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Password Baru (Opsional)</label>
                    <input type="password" id="new_password" name="password"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                           placeholder="Kosongkan untuk generate otomatis">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="new_password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Konfirmasi Password Baru</label>
                    <input type="password" id="new_password_confirmation" name="password_confirmation"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                           placeholder="Ulangi password baru">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
