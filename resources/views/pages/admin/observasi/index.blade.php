@extends('layouts.admin.app')

@section('title', 'Observasi Pengajaran Supervisor')

@section('content')
@php
    $editObs = $editObservasi ?? null;
    $initialEditing = $editObs !== null;
@endphp
<div class="space-y-6"
    x-data="{
        isEditing: @js($initialEditing),
        storeAction: @js(route('admin.observasi.store')),
        formAction: @js($initialEditing ? route('admin.observasi.update', $editObs) : route('admin.observasi.store')),
        form: {
            instruktur_id: @js((string) old('instruktur_id', $editObs?->instruktur_id ?? '')),
            jadwal_id: @js((string) old('jadwal_id', $editObs?->jadwal_id ?? '')),
            tanggal: @js((string) old('tanggal', $editObs?->tanggal?->toDateString() ?? today()->toDateString())),
            indikator_1: @js((string) old('indikator_1', $editObs ? $editObs->getSkorIndikator(1, 4) : 4)),
            indikator_2: @js((string) old('indikator_2', $editObs ? $editObs->getSkorIndikator(2, 4) : 4)),
            indikator_3: @js((string) old('indikator_3', $editObs ? $editObs->getSkorIndikator(3, 4) : 4)),
            indikator_4: @js((string) old('indikator_4', $editObs ? $editObs->getSkorIndikator(4, 4) : 4)),
            indikator_5: @js((string) old('indikator_5', $editObs ? $editObs->getSkorIndikator(5, 4) : 4)),
            catatan_observasi: @js((string) old('catatan_observasi', $editObs?->catatan_observasi ?? '')),
            rekomendasi: @js((string) old('rekomendasi', $editObs?->rekomendasi ?? '')),
        },
        startEdit(data) {
            this.isEditing = true;
            this.formAction = data.update_url;
            this.form.instruktur_id = String(data.instruktur_id ?? '');
            this.form.jadwal_id = String(data.jadwal_id ?? '');
            this.form.tanggal = String(data.tanggal ?? '');
            this.form.indikator_1 = String(data.indikator_1 ?? '4');
            this.form.indikator_2 = String(data.indikator_2 ?? '4');
            this.form.indikator_3 = String(data.indikator_3 ?? '4');
            this.form.indikator_4 = String(data.indikator_4 ?? '4');
            this.form.indikator_5 = String(data.indikator_5 ?? '4');
            this.form.catatan_observasi = String(data.catatan_observasi ?? '');
            this.form.rekomendasi = String(data.rekomendasi ?? '');
            document.getElementById('form-observasi-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        cancelEdit() {
            this.isEditing = false;
            this.formAction = this.storeAction;
            this.form.instruktur_id = '';
            this.form.jadwal_id = '';
            this.form.tanggal = @js(today()->toDateString());
            this.form.indikator_1 = '4';
            this.form.indikator_2 = '4';
            this.form.indikator_3 = '4';
            this.form.indikator_4 = '4';
            this.form.indikator_5 = '4';
            this.form.catatan_observasi = '';
            this.form.rekomendasi = '';
        }
    }">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Observasi Pengajaran</h1>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        {{-- Form Input / Edit Observasi --}}
        <div id="form-observasi-card" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 lg:col-span-5"
            :class="isEditing ? 'ring-2 ring-brand-500/40 border-brand-400 dark:border-brand-600' : ''">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                    x-text="isEditing ? 'Edit Hasil Observasi' : 'Catat Observasi Baru'">
                    {{ $initialEditing ? 'Edit Hasil Observasi' : 'Catat Observasi Baru' }}
                </h2>
                <span x-show="isEditing" x-cloak
                    class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                    Mode Edit
                </span>
            </div>

            <form method="POST" :action="formAction" action="{{ $initialEditing ? route('admin.observasi.update', $editObs) : route('admin.observasi.store') }}" class="space-y-4">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                @if ($initialEditing)
                    <noscript><input type="hidden" name="_method" value="PUT"></noscript>
                @endif

                <div>
                    <label for="instruktur_id" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                        Instruktur <span class="text-red-600 dark:text-red-300">*</span>
                    </label>
                    <select id="instruktur_id" name="instruktur_id" x-model="form.instruktur_id" required
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih Instruktur</option>
                        @foreach ($instrukturs as $ins)
                            <option value="{{ $ins->id }}" {{ (int) old('instruktur_id', $editObs?->instruktur_id) === $ins->id ? 'selected' : '' }}>
                                {{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="jadwal_id" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                        Jadwal / Sesi Kelas (Opsional)
                    </label>
                    <select id="jadwal_id" name="jadwal_id" x-model="form.jadwal_id"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Observasi Umum / Pilih Sesi</option>
                        @foreach ($jadwals as $j)
                            <option value="{{ $j->id }}" {{ (int) old('jadwal_id', $editObs?->jadwal_id) === $j->id ? 'selected' : '' }}>
                                {{ $j->nama_kelas }} ({{ $j->materi }}) - {{ $j->instruktur?->nama_lengkap }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tanggal" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                        Tanggal Observasi <span class="text-red-600 dark:text-red-300">*</span>
                    </label>
                    <input type="date" id="tanggal" name="tanggal" x-model="form.tanggal" value="{{ old('tanggal', $editObs?->tanggal?->toDateString() ?? today()->toDateString()) }}" required
                        class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>

                <div class="space-y-3 border-t border-gray-200 pt-3 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                        Penilaian Indikator (Skala 1-5)
                    </p>
                    @foreach ($indikatorLabels as $key => $label)
                        @php
                            $indNum = $loop->iteration;
                            $defaultVal = $editObs ? $editObs->getSkorIndikator($indNum, 4) : 4;
                        @endphp
                        <div>
                            <label for="indikator_{{ $key }}" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                {{ $indNum }}. {{ $label }} <span class="text-red-600 dark:text-red-300">*</span>
                            </label>
                            <select id="indikator_{{ $key }}" name="{{ $key }}" x-model="form.{{ $key }}" required
                                class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="5" {{ (int) old($key, $defaultVal) === 5 ? 'selected' : '' }}>5 - Sangat Baik (100)</option>
                                <option value="4" {{ (int) old($key, $defaultVal) === 4 ? 'selected' : '' }}>4 - Baik (80)</option>
                                <option value="3" {{ (int) old($key, $defaultVal) === 3 ? 'selected' : '' }}>3 - Cukup (60)</option>
                                <option value="2" {{ (int) old($key, $defaultVal) === 2 ? 'selected' : '' }}>2 - Kurang (40)</option>
                                <option value="1" {{ (int) old($key, $defaultVal) === 1 ? 'selected' : '' }}>1 - Sangat Kurang (20)</option>
                            </select>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label for="catatan_observasi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                        Catatan Hasil Observasi
                    </label>
                    <textarea id="catatan_observasi" name="catatan_observasi" x-model="form.catatan_observasi" rows="2"
                        placeholder="Temuan selama pengajaran berlangsung..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('catatan_observasi', $editObs?->catatan_observasi) }}</textarea>
                </div>

                <div>
                    <label for="rekomendasi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                        Rekomendasi Tindak Lanjut
                    </label>
                    <textarea id="rekomendasi" name="rekomendasi" x-model="form.rekomendasi" rows="2"
                        placeholder="Rekomendasi peningkatan mutu pengajaran..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('rekomendasi', $editObs?->rekomendasi) }}</textarea>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit"
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                        x-text="isEditing ? 'Simpan Perubahan' : 'Simpan Hasil Observasi'">
                        {{ $initialEditing ? 'Simpan Perubahan' : 'Simpan Hasil Observasi' }}
                    </button>
                    <button type="button" x-show="isEditing" x-cloak @click="cancelEdit()"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        {{-- Daftar Riwayat Observasi --}}
        <div class="space-y-4 lg:col-span-7">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <form method="GET" action="{{ route('admin.observasi.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[180px] flex-1">
                        <label for="filter_instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
                        <select id="filter_instruktur" name="instruktur"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Semua Instruktur</option>
                            @foreach ($instrukturs as $ins)
                                <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>
                                    {{ $ins->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <label for="filter_dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari</label>
                        <input type="date" id="filter_dari" name="dari" value="{{ $dari }}"
                            class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                    <div class="w-full sm:w-auto">
                        <label for="filter_sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                        <input type="date" id="filter_sampai" name="sampai" value="{{ $sampai }}"
                            class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Filter
                    </button>
                    <a href="{{ route('admin.observasi.index') }}"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Riwayat Observasi
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Instruktur &amp; Sesi</th>
                                <th class="px-4 py-3">Skala 1-5</th>
                                <th class="px-4 py-3">Skor</th>
                                <th class="px-4 py-3">Catatan &amp; Rekomendasi</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($observasis as $obs)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-xs font-medium text-gray-900 dark:text-white">
                                        {{ $obs->tanggal->format('d/m/Y') }}
                                        <span class="block text-xs font-normal text-gray-600 dark:text-gray-300">Oleh: {{ $obs->supervisor?->name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $obs->instruktur?->nama_lengkap ?? '-' }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $obs->jadwal ? ($obs->jadwal->nama_kelas . ' - ' . $obs->jadwal->materi) : 'Observasi Umum' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="font-mono text-xs font-semibold text-brand-600 dark:text-brand-300">
                                            {{ number_format((float) $obs->rata_rata_skala5, 2) }} / 5.00
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-mono text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            {{ number_format((float) $obs->skor_observasi, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                        @if ($obs->catatan_observasi)
                                            <p><span class="font-medium text-gray-500 dark:text-gray-400">Catatan:</span> {{ $obs->catatan_observasi }}</p>
                                        @endif
                                        @if ($obs->rekomendasi)
                                            <p class="mt-0.5"><span class="font-medium text-gray-500 dark:text-gray-400">Rekomendasi:</span> {{ $obs->rekomendasi }}</p>
                                        @endif
                                        @if (!$obs->catatan_observasi && !$obs->rekomendasi)
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @php
                                            $editPayload = [
                                                'update_url'        => route('admin.observasi.update', $obs),
                                                'instruktur_id'     => $obs->instruktur_id,
                                                'jadwal_id'         => $obs->jadwal_id ?? '',
                                                'tanggal'           => $obs->tanggal->toDateString(),
                                                'indikator_1'       => $obs->getSkorIndikator(1, 4),
                                                'indikator_2'       => $obs->getSkorIndikator(2, 4),
                                                'indikator_3'       => $obs->getSkorIndikator(3, 4),
                                                'indikator_4'       => $obs->getSkorIndikator(4, 4),
                                                'indikator_5'       => $obs->getSkorIndikator(5, 4),
                                                'catatan_observasi' => $obs->catatan_observasi ?? '',
                                                'rekomendasi'       => $obs->rekomendasi ?? '',
                                            ];
                                        @endphp
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                @click="startEdit(@js($editPayload))"
                                                class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                                                Edit
                                            </button>
                                            <form id="del-obs-{{ $obs->id }}" method="POST" action="{{ route('admin.observasi.destroy', $obs) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" onclick="confirmDelete('del-obs-{{ $obs->id }}', 'Hapus Observasi', 'Yakin ingin menghapus catatan observasi ini?')"
                                                    class="inline-flex min-h-[44px] items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/30">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-600 dark:text-gray-300">
                                        Belum ada data observasi pengajaran pada periode terpilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($observasis->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                        {{ $observasis->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
