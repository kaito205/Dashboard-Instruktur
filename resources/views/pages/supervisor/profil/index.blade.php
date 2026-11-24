@extends('layouts.supervisor.app')
@section('title', 'Profil & Keamanan Akun Supervisor')

@section('content')
@php
    $user = auth()->user();
    $hasPasswordErrors = $errors->hasAny(['current_password', 'password', 'password_confirmation']);
    $hasProfilErrors   = $errors->hasAny(['foto_profil', 'name', 'email']);
@endphp

<div class="space-y-6"
    x-data="{
        editProfilOpen: {{ (! $hasPasswordErrors && $hasProfilErrors) ? 'true' : 'false' }},
        editPasswordOpen: {{ $hasPasswordErrors ? 'true' : 'false' }},
        previewUrl: @js($user->foto_url),
        fallbackUrl: @js($user->default_avatar_url),
        objectUrl: null,
        handleFileChange(event) {
            const file = event.target.files[0];
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }
            if (file) {
                this.objectUrl = URL.createObjectURL(file);
                this.previewUrl = this.objectUrl;
            }
        },
        closeAll() {
            this.editProfilOpen = false;
            this.editPasswordOpen = false;
        },
        destroy() {
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
            }
        }
    }"
    @keydown.escape.window="closeAll()">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Profil &amp; Keamanan Akun</h1>
            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                Informasi akun dan pengaturan keamanan Supervisor.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="editProfilOpen = true"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Edit Profil
            </button>
            <button type="button" @click="editPasswordOpen = true"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Ubah Password
            </button>
        </div>
    </div>

    {{-- Kartu Informasi Profil (Read-Only Default) --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div class="flex items-center gap-4">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <img src="{{ $user->foto_url }}" alt="{{ $user->name }}"
                        onerror="this.src='{{ $user->default_avatar_url }}'"
                        class="block h-full w-full object-cover object-center">
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 font-semibold capitalize text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">
                            {{ $user->role_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Alamat Email Login</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Role Hak Akses</dt>
                <dd class="mt-1 text-sm font-medium capitalize text-gray-900 dark:text-white">{{ $user->role_label }}</dd>
            </div>
        </dl>
    </div>

    {{-- Modal Edit Profil Supervisor --}}
    <div x-show="editProfilOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-edit-profil-supervisor-title"
        @click.self="editProfilOpen = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 id="modal-edit-profil-supervisor-title" class="text-base font-bold text-gray-900 dark:text-white">Edit Profil Supervisor</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Perbarui foto profil, nama lengkap, dan alamat email akun Anda.</p>
                </div>
                <button type="button" @click="editProfilOpen = false"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Tutup
                </button>
            </div>

            <form method="POST" action="{{ route('supervisor.profil.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <img src="{{ $user->foto_url }}" :src="previewUrl" alt="{{ $user->name }}" x-on:error="previewUrl = fallbackUrl" class="block h-full w-full object-cover object-center">
                    </div>
                    <div class="min-w-0 flex-1">
                        <label for="foto_profil_supervisor" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Foto Profil (Opsional)</label>
                        <input type="file" id="foto_profil_supervisor" name="foto_profil" accept="image/jpeg,image/png,image/webp" @change="handleFileChange($event)"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 dark:border-gray-700 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">JPG, PNG, atau WebP (maks. 2MB).</p>
                        @error('foto_profil')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="profile_name" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600">*</span></label>
                    <input type="text" id="profile_name" name="name" value="{{ old('name', $user->name) }}" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="profile_email" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Alamat Email <span class="text-red-600">*</span></label>
                    <input type="email" id="profile_email" name="email" value="{{ old('email', $user->email) }}" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="editProfilOpen = false"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Ubah Password Supervisor --}}
    <div x-show="editPasswordOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-password-supervisor-title"
        @click.self="editPasswordOpen = false">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 id="modal-password-supervisor-title" class="text-base font-bold text-gray-900 dark:text-white">Ubah Password Akun</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Gunakan minimal 8 karakter untuk menjaga keamanan akun Anda.</p>
                </div>
                <button type="button" @click="editPasswordOpen = false"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Tutup
                </button>
            </div>

            <form method="POST" action="{{ route('supervisor.profil.password') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Password Saat Ini <span class="text-red-600">*</span></label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Password Baru (Min. 8 Karakter) <span class="text-red-600">*</span></label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Konfirmasi Password Baru <span class="text-red-600">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="editPasswordOpen = false"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
