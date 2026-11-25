@extends('layouts.admin.app')
@section('title', 'Tambah Instruktur')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar Instruktur
    </a>
    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Tambah Instruktur</h1>
</div>

<div class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <form method="POST" action="{{ route('admin.instruktur.store') }}" enctype="multipart/form-data" class="space-y-6"
          x-data="{
              akunMode: @js(old('akun_mode', 'auto')),
              email: @js(old('email', '')),
              kode: @js(old('kode_instruktur', $nextCode ?? 'INS-CTI01')),
              showPass: true
          }">
        @csrf

        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Identitas &amp; Kontak</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="nama_lengkap" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600 dark:text-red-400">*</span></label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="kode_instruktur" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kode Instruktur <span class="text-red-600 dark:text-red-400">*</span></label>
                    <input type="text" id="kode_instruktur" name="kode_instruktur" x-model="kode" required
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                    <input type="email" id="email" name="email" x-model="email"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="no_telepon" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nomor Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon') }}"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                           placeholder="08xxxxxxxxxx">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-5 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Keahlian &amp; Sertifikasi</h2>
            <div class="mt-3 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="bidang_keahlian" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Bidang Keahlian</label>
                        <input type="text" id="bidang_keahlian" name="bidang_keahlian" value="{{ old('bidang_keahlian') }}"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                               placeholder="Contoh: Jaringan Komputer">
                    </div>
                    <div>
                        <label for="nama_sertifikasi" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Sertifikasi</label>
                        <input type="text" id="nama_sertifikasi" name="nama_sertifikasi" value="{{ old('nama_sertifikasi') }}"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                               placeholder="Contoh: BNSP Teknisi Jaringan">
                    </div>
                </div>

                <div>
                    <label for="riwayat_keahlian" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Riwayat Keahlian</label>
                    <textarea id="riwayat_keahlian" name="riwayat_keahlian" rows="2"
                              class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                              placeholder="Kompetensi tambahan atau riwayat pelatihan">{{ old('riwayat_keahlian') }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sertifikasi_berlaku_sampai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Berlaku Sampai</label>
                        <input type="date" id="sertifikasi_berlaku_sampai" name="sertifikasi_berlaku_sampai" value="{{ old('sertifikasi_berlaku_sampai') }}"
                               class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="file_sertifikasi" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Dokumen Sertifikasi</label>
                        <input type="file" id="file_sertifikasi" name="file_sertifikasi" accept=".pdf,image/jpeg,image/png,image/webp"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-5 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Status &amp; Ketersediaan</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Status <span class="text-red-600 dark:text-red-400">*</span></label>
                    <select id="status" name="status" required
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label for="status_ketersediaan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Ketersediaan</label>
                    <select id="status_ketersediaan" name="status_ketersediaan"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="tersedia" {{ old('status_ketersediaan', 'tersedia') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="cuti" {{ old('status_ketersediaan') === 'cuti' ? 'selected' : '' }}>Cuti</option>
                        <option value="izin" {{ old('status_ketersediaan') === 'izin' ? 'selected' : '' }}>Izin</option>
                    </select>
                </div>
                <div>
                    <label for="cuti_mulai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Cuti / Izin (Opsional)</label>
                    <input type="date" id="cuti_mulai" name="cuti_mulai" value="{{ old('cuti_mulai') }}"
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="cuti_selesai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Selesai Cuti / Izin (Opsional)</label>
                    <input type="date" id="cuti_selesai" name="cuti_selesai" value="{{ old('cuti_selesai') }}"
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            <div class="mt-4">
                <label for="keterangan_cuti" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan Cuti / Izin</label>
                <input type="text" id="keterangan_cuti" name="keterangan_cuti" value="{{ old('keterangan_cuti') }}"
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                       placeholder="Opsional">
            </div>
        </div>

        <div class="space-y-4 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Akun Login &amp; Profil</h2>

            <div class="space-y-2.5">
                <label class="flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2 text-sm transition dark:border-gray-700"
                       :class="akunMode === 'auto' ? 'border-brand-500 bg-brand-50/60 text-gray-900 dark:border-brand-500 dark:bg-brand-950/30 dark:text-white' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/50'">
                    <input type="radio" name="akun_mode" value="auto" x-model="akunMode" class="h-4 w-4 text-brand-600 focus:ring-brand-500">
                    <span class="font-medium">Buat akun login otomatis</span>
                </label>

                <div x-show="akunMode === 'auto'" x-cloak
                     class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-xs text-gray-700 dark:text-gray-200">
                        Email login:
                        <span class="font-mono font-semibold text-gray-900 dark:text-white"
                              x-text="email.trim() || ((kode.trim().toLowerCase() || 'ins-cti') + '@lpkcti.id')"></span>
                    </p>
                    <div>
                        <label for="password_awal" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                            Password Awal Akun <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input id="password_awal" name="password_awal"
                                   :type="showPass ? 'text' : 'password'"
                                   value="{{ old('password_awal', '') }}"
                                   placeholder="Masukkan password awal instruktur"
                                   :required="akunMode === 'auto'"
                                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <button type="button" @click="showPass = !showPass"
                                    class="inline-flex min-h-[44px] shrink-0 items-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                                    x-text="showPass ? 'Sembunyikan' : 'Tampilkan'"></button>
                        </div>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>
                    </div>
                </div>

                <label class="flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2 text-sm transition dark:border-gray-700"
                       :class="akunMode === 'existing' ? 'border-brand-500 bg-brand-50/60 text-gray-900 dark:border-brand-500 dark:bg-brand-950/30 dark:text-white' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/50'">
                    <input type="radio" name="akun_mode" value="existing" x-model="akunMode" class="h-4 w-4 text-brand-600 focus:ring-brand-500">
                    <span class="font-medium">Tautkan ke akun instruktur yang sudah ada</span>
                </label>

                <div x-show="akunMode === 'existing'" x-cloak
                     class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                    @if (($usersAvailable ?? collect())->isNotEmpty())
                        <label for="user_id" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">Pilih Akun Instruktur <span class="text-red-600 dark:text-red-400">*</span></label>
                        <select id="user_id" name="user_id" :required="akunMode === 'existing'"
                                class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Pilih akun instruktur</option>
                            @foreach($usersAvailable as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-xs text-gray-700 dark:text-gray-300">Semua akun dengan role Instruktur sudah terhubung dengan profil lain.</p>
                    @endif
                </div>

                <label class="flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2 text-sm transition dark:border-gray-700"
                       :class="akunMode === 'none' ? 'border-brand-500 bg-brand-50/60 text-gray-900 dark:border-brand-500 dark:bg-brand-950/30 dark:text-white' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/50'">
                    <input type="radio" name="akun_mode" value="none" x-model="akunMode" class="h-4 w-4 text-brand-600 focus:ring-brand-500">
                    <span class="font-medium">Belum perlu akun login</span>
                </label>

                <div x-show="akunMode === 'none'" x-cloak
                     class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-300">
                    Profil instruktur disimpan tanpa akun login. Akun dapat dibuat atau dihubungkan nanti lewat Edit Instruktur.
                </div>
            </div>

            <div>
                <label for="alamat" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat</label>
                <textarea id="alamat" name="alamat" rows="2"
                          class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                          placeholder="Alamat lengkap">{{ old('alamat') }}</textarea>
            </div>

            <div>
                <label for="foto_profil" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Foto Profil</label>
                <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp"
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">JPG, PNG, atau WebP maksimal 2MB.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
            <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Batal</a>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Simpan Instruktur</button>
        </div>
    </form>
</div>
@endsection
