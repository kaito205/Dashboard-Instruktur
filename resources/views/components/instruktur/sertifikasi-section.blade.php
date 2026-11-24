@props([
    'instruktur',
    'editable' => false,
    'routePrefix' => 'admin',
    'redirectTo' => 'show',
])

@php
    $sertifikasis = $instruktur->relationLoaded('sertifikasis')
        ? $instruktur->sertifikasis
        : $instruktur->sertifikasis()->get();

    $storeHasErrors = $errors->sertifikasiStore->any();
    $updateErrorId  = $errors->sertifikasiUpdate->any() ? (int) old('_edit_sertifikasi_id', 0) : null;
@endphp

<div id="sertifikasi"
     class="scroll-mt-24 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
     x-data="{ showAddForm: @js($storeHasErrors), editingId: @js($updateErrorId) }">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-800">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sertifikasi Kompetensi</h3>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                {{ $sertifikasis->isEmpty() ? 'Belum ada sertifikat tersimpan' : $sertifikasis->count() . ' sertifikat tercatat' }}
            </p>
        </div>

        @if($editable)
            <button type="button"
                    @click="showAddForm = !showAddForm"
                    :aria-expanded="showAddForm.toString()"
                    class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-brand-600 bg-brand-50 px-3.5 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-brand-500 dark:bg-brand-950/40 dark:text-brand-300 dark:hover:bg-brand-900/50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="showAddForm ? 'Tutup Form' : 'Tambah Sertifikat'"></span>
            </button>
        @endif
    </div>

    @if($editable)
        <div x-show="showAddForm" x-cloak class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
            <h4 class="text-xs font-semibold text-gray-900 dark:text-white">Tambah Sertifikat Baru</h4>

            @if($storeHasErrors)
                <div role="alert" class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach($errors->sertifikasiStore->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('admin.instruktur.sertifikasi.store', $instruktur) }}"
                  enctype="multipart/form-data"
                  class="mt-3 space-y-4">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="add_nama_sertifikasi" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                            Nama Sertifikasi <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <input type="text"
                               id="add_nama_sertifikasi"
                               name="nama_sertifikasi"
                               value="{{ $storeHasErrors ? old('nama_sertifikasi') : '' }}"
                               required
                               maxlength="150"
                               placeholder="Contoh: BNSP Teknisi Jaringan"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400">
                    </div>

                    <div>
                        <label for="add_berlaku_sampai" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                            Berlaku Sampai <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                        </label>
                        <input type="date"
                               id="add_berlaku_sampai"
                               name="berlaku_sampai"
                               value="{{ $storeHasErrors ? old('berlaku_sampai') : '' }}"
                               class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Kosongkan bila sertifikat berlaku seumur hidup.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="add_file_sertifikasi" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                            Dokumen Sertifikasi
                        </label>
                        <input type="file"
                               id="add_file_sertifikasi"
                               name="file_sertifikasi"
                               accept=".pdf,image/jpeg,image/png,image/webp"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">PDF, JPG, PNG, atau WebP maksimal 5MB.</p>
                    </div>

                    <div>
                        <label for="add_keterangan" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                            Keterangan / Nomor <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                        </label>
                        <input type="text"
                               id="add_keterangan"
                               name="keterangan"
                               value="{{ $storeHasErrors ? old('keterangan') : '' }}"
                               maxlength="255"
                               placeholder="Nomor sertifikat atau lembaga penerbit"
                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                    <button type="button"
                            @click="showAddForm = false"
                            class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Simpan Sertifikat
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if($sertifikasis->isEmpty())
        <div class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center dark:border-gray-700">
            <p class="text-sm font-medium text-gray-900 dark:text-white">Belum ada sertifikat tersimpan</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                {{ $editable ? 'Tambahkan sertifikat kompetensi beserta tanggal berlaku dan dokumen pendukung instruktur.' : 'Belum ada data sertifikat yang diunggah untuk instruktur ini.' }}
            </p>
            @if($editable)
                <div class="mt-4">
                    <button type="button"
                            @click="showAddForm = true"
                            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Tambah Sertifikat
                    </button>
                </div>
            @endif
        </div>
    @else
        <div class="mt-4 space-y-3">
            @foreach($sertifikasis as $sert)
                @php
                    $info = $sert->status_info;
                    $badgeClass = match ($info['color']) {
                        'red'   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                        'amber' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                    };
                    $isEditingThis = $updateErrorId === $sert->id;
                @endphp

                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <div x-show="editingId !== {{ $sert->id }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $sert->nama_sertifikasi }}</h4>
                                    <span class="inline-flex whitespace-nowrap items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                        {{ $info['badge'] }}
                                    </span>
                                </div>

                                <p class="text-xs text-gray-700 dark:text-gray-300">
                                    @if($sert->berlaku_sampai)
                                        Berlaku s.d. <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $sert->berlaku_sampai->format('d/m/Y') }}</span>
                                        <span class="text-gray-600 dark:text-gray-300">({{ $info['detail'] }})</span>
                                    @else
                                        Tanpa batas waktu (Seumur hidup)
                                    @endif
                                </p>

                                @if($sert->keterangan)
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $sert->keterangan }}</p>
                                @endif

                                <div class="pt-1 text-xs">
                                    @if($sert->has_file)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $sert->display_file_name }}</span>
                                            <a href="{{ route($routePrefix . '.instruktur.sertifikasi.dokumen', [$instruktur, $sert]) }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Lihat Dokumen
                                            </a>
                                            <a href="{{ route($routePrefix . '.instruktur.sertifikasi.dokumen', [$instruktur, $sert, 'download' => 1]) }}"
                                               class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Unduh
                                            </a>
                                        </div>
                                    @elseif(!empty($sert->file_path))
                                        <span class="text-amber-700 dark:text-amber-300">Berkas dokumen tidak ditemukan di penyimpanan</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Belum ada dokumen diunggah</span>
                                    @endif
                                </div>
                            </div>

                            @if($editable)
                                <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                    <button type="button"
                                            @click="editingId = {{ $sert->id }}"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Edit
                                    </button>
                                    <form id="del-sert-{{ $sert->id }}"
                                          method="POST"
                                          action="{{ route('admin.instruktur.sertifikasi.destroy', [$instruktur, $sert]) }}"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                                        <button type="button"
                                                onclick="confirmDelete('del-sert-{{ $sert->id }}', 'Hapus Sertifikat', 'Yakin ingin menghapus sertifikat {{ addslashes($sert->nama_sertifikasi) }}?')"
                                                class="inline-flex min-h-[44px] items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-900/60 dark:bg-gray-800 dark:text-red-300 dark:hover:bg-red-950/40">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($editable)
                        <div x-show="editingId === {{ $sert->id }}" x-cloak>
                            <h4 class="text-xs font-semibold text-gray-900 dark:text-white">Edit Sertifikat</h4>

                            @if($isEditingThis)
                                <div role="alert" class="mt-2 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                                    <ul class="list-inside list-disc space-y-0.5">
                                        @foreach($errors->sertifikasiUpdate->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST"
                                  action="{{ route('admin.instruktur.sertifikasi.update', [$instruktur, $sert]) }}"
                                  enctype="multipart/form-data"
                                  class="mt-3 space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="edit_nama_{{ $sert->id }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            Nama Sertifikasi <span class="text-red-600 dark:text-red-400">*</span>
                                        </label>
                                        <input type="text"
                                               id="edit_nama_{{ $sert->id }}"
                                               name="nama_sertifikasi"
                                               value="{{ $isEditingThis ? old('nama_sertifikasi') : $sert->nama_sertifikasi }}"
                                               required
                                               maxlength="150"
                                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>

                                    <div>
                                        <label for="edit_berlaku_{{ $sert->id }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            Berlaku Sampai <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                                        </label>
                                        <input type="date"
                                               id="edit_berlaku_{{ $sert->id }}"
                                               name="berlaku_sampai"
                                               value="{{ $isEditingThis ? old('berlaku_sampai') : $sert->berlaku_sampai?->format('Y-m-d') }}"
                                               class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="edit_file_{{ $sert->id }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            Ganti Dokumen <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                                        </label>
                                        @if($sert->has_file)
                                            <p class="mb-1 text-[11px] text-gray-600 dark:text-gray-300">Berkas saat ini: <span class="font-mono">{{ $sert->display_file_name }}</span></p>
                                        @endif
                                        <input type="file"
                                               id="edit_file_{{ $sert->id }}"
                                               name="file_sertifikasi"
                                               accept=".pdf,image/jpeg,image/png,image/webp"
                                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:file:bg-brand-950 dark:file:text-brand-300">
                                    </div>

                                    <div>
                                        <label for="edit_ket_{{ $sert->id }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            Keterangan / Nomor <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                                        </label>
                                        <input type="text"
                                               id="edit_ket_{{ $sert->id }}"
                                               name="keterangan"
                                               value="{{ $isEditingThis ? old('keterangan') : $sert->keterangan }}"
                                               maxlength="255"
                                               class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                                    <button type="button"
                                            @click="editingId = null"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                        Batal
                                    </button>
                                    <button type="submit"
                                            class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
