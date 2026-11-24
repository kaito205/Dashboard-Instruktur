@extends('layouts.app')

@section('content')
@php
    $hasPasswordErrors = $errors->hasAny(['password_lama', 'password_baru', 'password_baru_confirmation']);
    $hasProfilErrors   = $errors->hasAny(['foto_profil', 'no_telepon', 'alamat']);
    $sertifikasiList   = $instruktur->relationLoaded('sertifikasis')
        ? $instruktur->sertifikasis
        : collect();
@endphp

<div class="space-y-6"
    x-data="{
        editProfilOpen: {{ (! $hasPasswordErrors && $hasProfilErrors) ? 'true' : 'false' }},
        editPasswordOpen: {{ $hasPasswordErrors ? 'true' : 'false' }},
        previewUrl: @js($instruktur->foto_url),
        fallbackUrl: @js($instruktur->default_avatar_url),
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
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Informasi akun, kontak pribadi, sertifikasi, dan pengaturan keamanan instruktur.
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
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 dark:border-gray-800">
            <div class="flex items-center gap-4">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <img src="{{ $instruktur->foto_url }}" alt="{{ $instruktur->nama_lengkap }}"
                        onerror="this.src='{{ $instruktur->default_avatar_url }}'"
                        class="block h-full w-full object-cover object-center">
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $instruktur->nama_lengkap }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 font-mono font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">
                            {{ $instruktur->kode_instruktur }}
                        </span>
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 font-semibold {{ $instruktur->status === 'aktif' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                            Akun: {{ ucfirst($instruktur->status ?? 'aktif') }}
                        </span>
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 font-semibold {{ ($instruktur->status_ketersediaan ?? 'tersedia') === 'tersedia' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                            {{ $instruktur->ketersediaan_label ?? 'Tersedia' }}
                        </span>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Email login &amp; bidang keahlian dikelola oleh Admin LPK.
            </p>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Alamat Email Login</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $instruktur->email ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Bidang Keahlian</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $instruktur->bidang_keahlian ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Nomor Telepon / WhatsApp</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $instruktur->no_telepon ?: '-' }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Alamat Domisili</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $instruktur->alamat ?: '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Status Ketersediaan & Daftar Sertifikasi --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-2 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Sertifikasi &amp; Masa Berlaku</h2>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                    Data sertifikasi kompetensi dan status ketersediaan mengajar dikelola oleh Admin LPK.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nama Sertifikasi</th>
                        <th class="px-6 py-3">Penerbit</th>
                        <th class="px-6 py-3">Nomor</th>
                        <th class="px-6 py-3">Masa Berlaku</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Dokumen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($sertifikasiList as $sert)
                        @php
                            $sisaHari = $sert->berlaku_sampai
                                ? (int) today('Asia/Jakarta')->diffInDays($sert->berlaku_sampai->copy()->startOfDay(), false)
                                : null;
                            if ($sisaHari === null) {
                                $sertStatusLabel = 'Seumur Hidup';
                                $sertBadgeCls = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
                            } elseif ($sisaHari < 0) {
                                $sertStatusLabel = 'Kedaluwarsa';
                                $sertBadgeCls = 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300';
                            } elseif ($sisaHari <= 30) {
                                $sertStatusLabel = "Hampir Habis ({$sisaHari} hari)";
                                $sertBadgeCls = 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
                            } else {
                                $sertStatusLabel = 'Aktif';
                                $sertBadgeCls = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-6 py-3.5 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $sert->nama_sertifikasi }}
                            </td>
                            <td class="px-6 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                {{ $sert->penerbit ?: '-' }}
                            </td>
                            <td class="px-6 py-3.5 font-mono text-xs text-gray-700 dark:text-gray-300">
                                {{ $sert->nomor_sertifikat ?: '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs text-gray-700 dark:text-gray-300">
                                {{ $sert->berlaku_sampai ? $sert->berlaku_sampai->format('d/m/Y') : 'Tidak terbatas' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $sertBadgeCls }}">
                                    {{ $sertStatusLabel }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3.5 text-right text-xs">
                                @if (!empty($sert->file_dokumen))
                                    <a href="{{ asset('storage/' . $sert->file_dokumen) }}" target="_blank" rel="noopener"
                                        class="inline-flex min-h-[36px] items-center font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                                        Lihat Dokumen
                                    </a>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-xs text-gray-600 dark:text-gray-300">
                                Belum ada data sertifikasi yang tercatat. Hubungi Admin LPK untuk menambahkan sertifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Edit Profil Instruktur --}}
    <div x-show="editProfilOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-edit-profil-title"
        @click.self="editProfilOpen = false">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 id="modal-edit-profil-title" class="text-base font-bold text-gray-900 dark:text-white">Edit Profil Instruktur</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Anda dapat memperbarui foto profil, nomor telepon, dan alamat domisili.</p>
                </div>
                <button type="button" @click="editProfilOpen = false"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Tutup
                </button>
            </div>

            <form method="POST" action="{{ route('instruktur.profil.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <img src="{{ $instruktur->foto_url }}" :src="previewUrl" alt="{{ $instruktur->nama_lengkap }}" x-on:error="previewUrl = fallbackUrl" class="block h-full w-full object-cover object-center">
                    </div>
                    <div class="min-w-0 flex-1">
                        <label for="foto_profil" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Foto Profil (Opsional)</label>
                        <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp" @change="handleFileChange($event)"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 dark:border-gray-700 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300" />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">JPG, PNG, atau WebP (maks. 2MB).</p>
                        @error('foto_profil')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs sm:grid-cols-2 dark:border-gray-800 dark:bg-gray-800/50">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Email Login (Dikelola Admin)</span>
                        <p class="mt-0.5 font-medium text-gray-800 dark:text-gray-200">{{ $instruktur->email ?: '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Bidang Keahlian (Dikelola Admin)</span>
                        <p class="mt-0.5 font-medium text-gray-800 dark:text-gray-200">{{ $instruktur->bidang_keahlian ?: '-' }}</p>
                    </div>
                </div>

                <div>
                    <label for="no_telepon" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Nomor Telepon / WhatsApp</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $instruktur->no_telepon) }}"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                    @error('no_telepon')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alamat" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Alamat Domisili</label>
                    <textarea id="alamat" name="alamat" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">{{ old('alamat', $instruktur->alamat) }}</textarea>
                    @error('alamat')
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

    {{-- Modal Ubah Password Instruktur --}}
    <div x-show="editPasswordOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-password-instruktur-title"
        @click.self="editPasswordOpen = false">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 id="modal-password-instruktur-title" class="text-base font-bold text-gray-900 dark:text-white">Ubah Password Akun</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Gunakan minimal 8 karakter untuk menjaga keamanan akun Anda.</p>
                </div>
                <button type="button" @click="editPasswordOpen = false"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Tutup
                </button>
            </div>

            <form method="POST" action="{{ route('instruktur.profil.password') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="password_lama" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Password Saat Ini <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="password_lama" name="password_lama" required autocomplete="current-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                    @error('password_lama')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_baru" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Password Baru (Min. 8 Karakter) <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="password_baru" name="password_baru" required autocomplete="new-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                    @error('password_baru')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_baru_confirmation" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Konfirmasi Password Baru <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" required autocomplete="new-password"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
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
