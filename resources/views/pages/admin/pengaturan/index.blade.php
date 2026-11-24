@extends('layouts.admin.app')
@section('title', 'Pengaturan Sistem')

@section('content')
@php
    $appIdentity        = \App\Models\Setting::getAppIdentity();
    $val                = fn(string $key, string $default = '') => isset($settings[$key]) ? ($settings[$key]->value ?? $default) : $default;
    $indikatorEvaluasi  = $indikatorEvaluasi ?? \App\Models\Evaluasi::getIndikatorLabels();
    $indikatorObservasi = $indikatorObservasi ?? \App\Models\ObservasiSupervisor::getIndikatorLabels();
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem</h1>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Identitas Aplikasi &amp; Periode</h2>
        <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="app_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Aplikasi <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $val('app_name', $appIdentity['app_name'])) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @error('app_name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="app_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Deskripsi Aplikasi</label>
                <input type="text" id="app_description" name="app_description" value="{{ old('app_description', $val('app_description', $appIdentity['app_description'])) }}" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @error('app_description')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="lpk_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Lembaga (LPK) <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="text" id="lpk_name" name="lpk_name" value="{{ old('lpk_name', $val('lpk_name', $appIdentity['lpk_name'])) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @error('lpk_name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="lpk_alamat" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat Lembaga</label>
                <textarea id="lpk_alamat" name="lpk_alamat" rows="2" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('lpk_alamat', $val('lpk_alamat', $appIdentity['lpk_alamat'])) }}</textarea>
                @error('lpk_alamat')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="default_periode" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Filter Periode Default <span class="text-red-600 dark:text-red-300">*</span></label>
                @php $defPeriode = old('default_periode', $val('default_periode', $appIdentity['default_periode'])); @endphp
                <select id="default_periode" name="default_periode" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="hari_ini" {{ $defPeriode === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="minggu_ini" {{ $defPeriode === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="bulan_ini" {{ $defPeriode === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="tahun_ini" {{ $defPeriode === 'tahun_ini' ? 'selected' : '' }}>Tahun Ini</option>
                </select>
                @error('default_periode')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Simpan Aplikasi
            </button>
        </form>
    </div>

    @php
        $adminUser            = auth()->user();
        $hasAdminPassErrors   = $errors->hasAny(['password_lama', 'password_baru', 'password_baru_confirmation']);
        $hasAdminProfilErrors = $errors->hasAny(['foto_profil', 'name', 'email']);
    @endphp
    <div class="space-y-6"
        x-data="{
            editProfilOpen: {{ (! $hasAdminPassErrors && $hasAdminProfilErrors) ? 'true' : 'false' }},
            editPasswordOpen: {{ $hasAdminPassErrors ? 'true' : 'false' }},
            previewUrl: @js($adminUser->foto_url),
            fallbackUrl: @js($adminUser->default_avatar_url),
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

        {{-- Kartu Informasi Profil & Keamanan Admin (Read-Only Default) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Profil &amp; Keamanan Admin</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Informasi akun administrator yang sedang login.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="editProfilOpen = true"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Edit Profil
                    </button>
                    <button type="button" @click="editPasswordOpen = true"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Ubah Password
                    </button>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-4">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <img src="{{ $adminUser->foto_url }}" alt="{{ $adminUser->name }}"
                        onerror="this.src='{{ $adminUser->default_avatar_url }}'"
                        class="block h-full w-full object-cover object-center">
                </div>
                <div class="min-w-0">
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $adminUser->name }}</p>
                    <span class="mt-1 inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold capitalize text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">
                        {{ $adminUser->role_label }}
                    </span>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 sm:grid-cols-2 dark:border-gray-800">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $adminUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Alamat Email Login</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $adminUser->email }}</dd>
                </div>
            </dl>
        </div>

        {{-- Modal Edit Profil Admin --}}
        <div x-show="editProfilOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
            role="dialog" aria-modal="true" aria-labelledby="modal-edit-profil-admin-title"
            @click.self="editProfilOpen = false">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                    <div>
                        <h3 id="modal-edit-profil-admin-title" class="text-base font-bold text-gray-900 dark:text-white">Edit Profil Admin</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Perbarui foto profil, nama lengkap, dan email akun administrator.</p>
                    </div>
                    <button type="button" @click="editProfilOpen = false"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Tutup
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.pengaturan.profil') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center gap-4">
                        <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <img src="{{ $adminUser->foto_url }}" :src="previewUrl" alt="{{ $adminUser->name }}" x-on:error="previewUrl = fallbackUrl" class="block h-full w-full object-cover object-center">
                        </div>
                        <div class="min-w-0 flex-1">
                            <label for="foto_profil_admin" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Foto Profil (Opsional)</label>
                            <input type="file" id="foto_profil_admin" name="foto_profil" accept="image/jpeg,image/png,image/webp" @change="handleFileChange($event)"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 file:mr-3 file:min-h-[32px] file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950/60 dark:file:text-brand-300">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, atau WebP (maks. 2 MB).</p>
                            @error('foto_profil')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="profile_name" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="text" id="profile_name" name="name" value="{{ old('name', $adminUser->name) }}" required
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="profile_email" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Email <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="email" id="profile_email" name="email" value="{{ old('email', $adminUser->email) }}" required
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
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
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            Simpan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Ubah Password Admin --}}
        <div x-show="editPasswordOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
            role="dialog" aria-modal="true" aria-labelledby="modal-password-admin-title"
            @click.self="editPasswordOpen = false">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                    <div>
                        <h3 id="modal-password-admin-title" class="text-base font-bold text-gray-900 dark:text-white">Ubah Password Admin</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>
                    </div>
                    <button type="button" @click="editPasswordOpen = false"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Tutup
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.pengaturan.password') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="password_lama" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Password Saat Ini <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="password" id="password_lama" name="password_lama" required autocomplete="current-password"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('password_lama')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_baru" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Password Baru <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="password" id="password_baru" name="password_baru" required autocomplete="new-password"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('password_baru')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_baru_confirmation" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">Konfirmasi Password <span class="text-red-600 dark:text-red-300">*</span></label>
                        <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" required autocomplete="new-password"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button type="button" @click="editPasswordOpen = false"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Batal
                        </button>
                        <button type="submit"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            Perbarui Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="pengaturan-kinerja" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-5">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Parameter Kinerja &amp; Presensi</h2>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
            Total bobot 4 komponen wajib berjumlah 100%. Komponen tanpa data pada periode berjalan didistribusikan secara proporsional.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.pengaturan.kinerja') }}" class="space-y-6">
        @csrf
        @method('PUT')
        <div>
            <input type="hidden" name="kinerja_rule_enabled" value="0">
            <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-medium text-gray-900 dark:text-white">
                <input type="checkbox" name="kinerja_rule_enabled" value="1" {{ (string) old('kinerja_rule_enabled', $val('kinerja_rule_enabled', '1')) === '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Aktifkan perhitungan kinerja otomatis (4 komponen)</span>
            </label>
        </div>

        {{-- 4 Bobot Komponen Kinerja --}}
        <div
            x-data="{
                kehadiran: @js((string) old('bobot_kehadiran', $val('bobot_kehadiran', '30'))),
                aktivitas: @js((string) old('bobot_aktivitas', $val('bobot_aktivitas', '20'))),
                evaluasi: @js((string) old('bobot_evaluasi', $val('bobot_evaluasi', '30'))),
                observasi: @js((string) old('bobot_observasi', $val('bobot_observasi', '20'))),
                toNum(v) {
                    const n = parseFloat(v);
                    return Number.isFinite(n) ? n : 0;
                },
                get total() {
                    return Math.round((this.toNum(this.kehadiran) + this.toNum(this.aktivitas) + this.toNum(this.evaluasi) + this.toNum(this.observasi)) * 100) / 100;
                },
                get selisih() {
                    return Math.round(Math.abs(100 - this.total) * 100) / 100;
                },
                get isValidTotal() {
                    return Math.abs(this.total - 100) <= 0.001;
                },
                formatPct(v) {
                    return String(v).replace('.', ',');
                }
            }"
        >
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bobot Komponen Kinerja (Total 100%)</h3>
                <span
                    class="inline-flex items-center rounded-md px-2.5 py-1 font-mono text-xs font-semibold"
                    :class="isValidTotal
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                        : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300'"
                    aria-live="polite"
                >
                    <span x-text="isValidTotal
                        ? 'Total: 100% (Sesuai)'
                        : (total < 100
                            ? 'Total: ' + formatPct(total) + '% (Kurang ' + formatPct(selisih) + '%)'
                            : 'Total: ' + formatPct(total) + '% (Lebih ' + formatPct(selisih) + '%)')">
                        Total: 100% (Sesuai)
                    </span>
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="bobot_kehadiran" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Kehadiran (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="bobot_kehadiran" name="bobot_kehadiran" x-model="kehadiran" value="{{ old('bobot_kehadiran', $val('bobot_kehadiran', '30')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('bobot_kehadiran')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bobot_aktivitas" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Kepatuhan Aktivitas (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="bobot_aktivitas" name="bobot_aktivitas" x-model="aktivitas" value="{{ old('bobot_aktivitas', $val('bobot_aktivitas', '20')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('bobot_aktivitas')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bobot_evaluasi" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Evaluasi Peserta (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="bobot_evaluasi" name="bobot_evaluasi" x-model="evaluasi" value="{{ old('bobot_evaluasi', $val('bobot_evaluasi', '30')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('bobot_evaluasi')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bobot_observasi" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Observasi Supervisor (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="bobot_observasi" name="bobot_observasi" x-model="observasi" value="{{ old('bobot_observasi', $val('bobot_observasi', '20')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('bobot_observasi')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @error('bobot_total')
                <div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Ambang Batas Kategori & Parameter Presensi --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Ambang Batas &amp; Parameter Presensi</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="batas_sangat_baik" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Batas Sangat Baik (&ge;)</label>
                    <input type="number" step="0.01" min="1" max="100" id="batas_sangat_baik" name="batas_sangat_baik" value="{{ old('batas_sangat_baik', $val('batas_sangat_baik', '85')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('batas_sangat_baik')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="batas_baik" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Batas Baik (&ge;)</label>
                    <input type="number" step="0.01" min="1" max="100" id="batas_baik" name="batas_baik" value="{{ old('batas_baik', $val('batas_baik', '75')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('batas_baik')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="batas_cukup" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Batas Cukup (&ge;)</label>
                    <input type="number" step="0.01" min="1" max="100" id="batas_cukup" name="batas_cukup" value="{{ old('batas_cukup', $val('batas_cukup', '60')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('batas_cukup')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="toleransi_terlambat_menit" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Toleransi Terlambat (Menit)</label>
                    <input type="number" min="0" max="180" id="toleransi_terlambat_menit" name="toleransi_terlambat_menit" value="{{ old('toleransi_terlambat_menit', $val('toleransi_terlambat_menit', '15')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('toleransi_terlambat_menit')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="checkin_buka_sebelum_menit" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Buka Check-in (Menit)</label>
                    <input type="number" min="0" max="240" id="checkin_buka_sebelum_menit" name="checkin_buka_sebelum_menit" value="{{ old('checkin_buka_sebelum_menit', $val('checkin_buka_sebelum_menit', '30')) }}" required class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('checkin_buka_sebelum_menit')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Titik Koordinat & Radius Lokasi Presensi LPK --}}
        @php
            $lpkLatVal = old('lpk_latitude', $val('lpk_latitude', '-7.3308868'));
            $lpkLngVal = old('lpk_longitude', $val('lpk_longitude', '108.3560168'));
            $lpkRadVal = old('radius_lokasi_meter', $val('radius_lokasi_meter', '150'));
        @endphp
        <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Lokasi &amp; Radius Presensi LPK</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Titik acuan untuk memeriksa kesesuaian jarak koordinat GPS saat instruktur melakukan Check-in.</p>
                </div>
                <a href="https://www.google.com/maps?q={{ $lpkLatVal }},{{ $lpkLngVal }}" target="_blank" rel="noopener"
                   class="inline-flex min-h-[36px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-brand-300 dark:hover:bg-gray-700">
                    Cek Titik di Peta
                </a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="lpk_latitude" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Latitude LPK <span class="text-red-600 dark:text-red-300">*</span></label>
                    <input type="text" inputmode="decimal" id="lpk_latitude" name="lpk_latitude" value="{{ $lpkLatVal }}" required placeholder="-7.3308868"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('lpk_latitude')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="lpk_longitude" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Longitude LPK <span class="text-red-600 dark:text-red-300">*</span></label>
                    <input type="text" inputmode="decimal" id="lpk_longitude" name="lpk_longitude" value="{{ $lpkLngVal }}" required placeholder="108.3560168"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('lpk_longitude')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="radius_lokasi_meter" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Radius Presensi (Meter) <span class="text-red-600 dark:text-red-300">*</span></label>
                    <input type="number" min="10" max="10000" id="radius_lokasi_meter" name="radius_lokasi_meter" value="{{ $lpkRadVal }}" required placeholder="150"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('radius_lokasi_meter')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Konfigurasi 5 Label Indikator Evaluasi Peserta & Observasi Supervisor --}}
        <div class="grid grid-cols-1 gap-6 border-t border-gray-200 pt-4 lg:grid-cols-2 dark:border-gray-800">
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Indikator Evaluasi Peserta</h3>
                @for($i = 1; $i <= 5; $i++)
                    <div>
                        <label for="eval_indikator_{{ $i }}" class="mb-1 block text-xs text-gray-600 dark:text-gray-300">Indikator {{ $i }}</label>
                        <input type="text" id="eval_indikator_{{ $i }}" name="eval_indikator_{{ $i }}" value="{{ old('eval_indikator_' . $i, $indikatorEvaluasi['indikator_' . $i] ?? '') }}"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        @error('eval_indikator_' . $i)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endfor
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Indikator Observasi Supervisor</h3>
                @for($i = 1; $i <= 5; $i++)
                    <div>
                        <label for="obs_indikator_{{ $i }}" class="mb-1 block text-xs text-gray-600 dark:text-gray-300">Indikator {{ $i }}</label>
                        <input type="text" id="obs_indikator_{{ $i }}" name="obs_indikator_{{ $i }}" value="{{ old('obs_indikator_' . $i, $indikatorObservasi['indikator_' . $i] ?? '') }}"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        @error('obs_indikator_' . $i)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endfor
            </div>
        </div>

        <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Simpan Parameter Kinerja
        </button>
    </form>
</div>
@endsection
