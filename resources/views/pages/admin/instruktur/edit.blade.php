@extends('layouts.admin.app')
@section('title', 'Edit Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar Instruktur
        </a>
        <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Edit Instruktur: {{ $instruktur->nama_lengkap }}</h1>
    </div>
    <a href="{{ route('admin.instruktur.show', $instruktur) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        Lihat Profil &amp; Sertifikat
    </a>
</div>

<div class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <form method="POST" action="{{ route('admin.instruktur.update', $instruktur) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Identitas &amp; Kontak</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="nama_lengkap" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Lengkap <span class="text-red-600 dark:text-red-400">*</span></label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $instruktur->nama_lengkap) }}" required
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="kode_instruktur" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kode Instruktur <span class="text-red-600 dark:text-red-400">*</span></label>
                    <input type="text" id="kode_instruktur" name="kode_instruktur" value="{{ old('kode_instruktur', $instruktur->kode_instruktur) }}" required
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 font-mono text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $instruktur->email) }}"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="no_telepon" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nomor Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $instruktur->no_telepon) }}"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-5 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Keahlian Mengajar</h2>
            <div class="mt-3 space-y-4">
                <div>
                    <label for="bidang_keahlian" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Bidang Keahlian</label>
                    <input type="text" id="bidang_keahlian" name="bidang_keahlian" value="{{ old('bidang_keahlian', $instruktur->bidang_keahlian) }}"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label for="riwayat_keahlian" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Riwayat Keahlian</label>
                    <textarea id="riwayat_keahlian" name="riwayat_keahlian" rows="2"
                              class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('riwayat_keahlian', $instruktur->riwayat_keahlian) }}</textarea>
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
                        <option value="aktif" {{ old('status', $instruktur->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status', $instruktur->status) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label for="status_ketersediaan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Ketersediaan</label>
                    <select id="status_ketersediaan" name="status_ketersediaan"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="tersedia" {{ old('status_ketersediaan', $instruktur->status_ketersediaan ?? 'tersedia') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="cuti" {{ old('status_ketersediaan', $instruktur->status_ketersediaan) === 'cuti' ? 'selected' : '' }}>Cuti</option>
                        <option value="izin" {{ old('status_ketersediaan', $instruktur->status_ketersediaan) === 'izin' ? 'selected' : '' }}>Izin</option>
                    </select>
                </div>
                <div>
                    <label for="cuti_mulai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Cuti / Izin</label>
                    <input type="date" id="cuti_mulai" name="cuti_mulai" value="{{ old('cuti_mulai', $instruktur->cuti_mulai?->format('Y-m-d')) }}"
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="cuti_selesai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Selesai Cuti / Izin</label>
                    <input type="date" id="cuti_selesai" name="cuti_selesai" value="{{ old('cuti_selesai', $instruktur->cuti_selesai?->format('Y-m-d')) }}"
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            <div class="mt-4">
                <label for="keterangan_cuti" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan Cuti / Izin</label>
                <input type="text" id="keterangan_cuti" name="keterangan_cuti" value="{{ old('keterangan_cuti', $instruktur->keterangan_cuti) }}"
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-4 border-t border-gray-200 pt-5 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Akun &amp; Profil</h2>
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Akun Login</label>
                    @if ($instruktur->user_id)
                        <a href="{{ route('admin.pengguna.edit', $instruktur->user_id) }}"
                           class="text-xs font-semibold text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                            Kelola akun di Pengguna
                        </a>
                    @endif
                </div>
                <select id="user_id" name="user_id"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Tidak dihubungkan</option>
                    @foreach($usersAvailable ?? [] as $u)
                        <option value="{{ $u->id }}" {{ old('user_id', $instruktur->user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Hanya akun dengan role Instruktur yang belum tertaut yang dapat dihubungkan.</p>
            </div>

            <div>
                <label for="alamat" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat</label>
                <textarea id="alamat" name="alamat" rows="2"
                          class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('alamat', $instruktur->alamat) }}</textarea>
            </div>

            <div>
                <label for="foto_profil" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Foto Profil</label>
                @if($instruktur->foto_profil)
                <div class="mb-2 flex items-center gap-3">
                    <img src="{{ $instruktur->foto_url }}" alt="Foto saat ini" class="h-12 w-12 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                    <span class="text-xs text-gray-600 dark:text-gray-300">Unggah baru untuk mengganti foto.</span>
                </div>
                @endif
                <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp"
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
            <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Batal</a>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Simpan Perubahan</button>
        </div>
    </form>
</div>

<div class="mx-auto mt-6 max-w-3xl">
    <x-instruktur.sertifikasi-section
        :instruktur="$instruktur"
        :editable="true"
        route-prefix="admin"
        redirect-to="edit"
    />
</div>
@endsection
