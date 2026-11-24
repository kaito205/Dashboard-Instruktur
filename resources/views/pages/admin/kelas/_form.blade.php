@php
    $isEdit = isset($kelas) && $kelas !== null;
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="kode_kelas" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kode Kelas <span class="text-red-600 dark:text-red-400">*</span></label>
        <input type="text" id="kode_kelas" name="kode_kelas" value="{{ old('kode_kelas', $isEdit ? $kelas->kode_kelas : '') }}" required maxlength="50"
               placeholder="Contoh: RPL-02, TKJ1, AP2"
               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-mono uppercase text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        @error('kode_kelas')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="nama_kelas" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Kelas <span class="text-red-600 dark:text-red-400">*</span></label>
        <input type="text" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas', $isEdit ? $kelas->nama_kelas : '') }}" required maxlength="150"
               placeholder="Contoh: Rekayasa Perangkat Lunak"
               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        @error('nama_kelas')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="program_pelatihan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Program Pelatihan</label>
        <input type="text" id="program_pelatihan" name="program_pelatihan" list="kelas-program-list" value="{{ old('program_pelatihan', $isEdit ? $kelas->program_pelatihan : '') }}" maxlength="150"
               placeholder="Contoh: Teknik Komputer & Jaringan"
               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <datalist id="kelas-program-list">
            @foreach($programList ?? collect() as $prog)
                <option value="{{ $prog }}"></option>
            @endforeach
        </datalist>
        @error('program_pelatihan')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="ruangan_default" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Ruangan Default</label>
        <input type="text" id="ruangan_default" name="ruangan_default" list="kelas-ruangan-list" value="{{ old('ruangan_default', $isEdit ? $kelas->ruangan_default : '') }}" maxlength="100"
               placeholder="Pilih atau ketik ruangan (mis. L-01, R-01)"
               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <datalist id="kelas-ruangan-list">
            @foreach($ruanganList ?? collect() as $ruang)
                @if(($ruang['kode'] ?? '') !== ($ruang['label'] ?? ''))
                    <option value="{{ $ruang['kode'] }}">{{ $ruang['label'] }}</option>
                @else
                    <option value="{{ $ruang['kode'] }}"></option>
                @endif
            @endforeach
        </datalist>
        @error('ruangan_default')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="kuota_peserta" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kuota Peserta <span class="text-red-600 dark:text-red-400">*</span></label>
        <input type="number" id="kuota_peserta" name="kuota_peserta" value="{{ old('kuota_peserta', $isEdit ? $kelas->kuota_peserta : 20) }}" required min="1" max="1000"
               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        @error('kuota_peserta')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Status <span class="text-red-600 dark:text-red-400">*</span></label>
        <select id="status" name="status" required
                class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @foreach(\App\Models\Kelas::STATUS_LIST as $optKey => $optLabel)
                <option value="{{ $optKey }}" {{ old('status', $isEdit ? $kelas->status : 'aktif') === $optKey ? 'selected' : '' }}>{{ $optLabel }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="periode_mulai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Periode Mulai (Opsional)</label>
        <input type="date" id="periode_mulai" name="periode_mulai" value="{{ old('periode_mulai', $isEdit ? $kelas->periode_mulai?->format('Y-m-d') : '') }}"
               class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        @error('periode_mulai')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="periode_selesai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Periode Selesai (Opsional)</label>
        <input type="date" id="periode_selesai" name="periode_selesai" value="{{ old('periode_selesai', $isEdit ? $kelas->periode_selesai?->format('Y-m-d') : '') }}"
               class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        @error('periode_selesai')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<div>
    <label for="keterangan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan</label>
    <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000"
              placeholder="Catatan singkat kelas, angkatan, atau target materi (opsional)"
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('keterangan', $isEdit ? $kelas->keterangan : '') }}</textarea>
    @error('keterangan')
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
    <a href="{{ route('admin.kelas.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Batal</a>
    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">{{ $submitLabel ?? 'Simpan Kelas' }}</button>
</div>
