@extends('layouts.admin.app')
@section('title', 'Kode Akses Evaluasi')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Kode Akses Evaluasi</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('evaluasi.publik.index') }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Portal Evaluasi
        </a>
        <a href="{{ route('admin.evaluasi.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Hasil Evaluasi
        </a>
    </div>
</div>

@php
    $kelasKodeMap = ($kelasList ?? collect())->mapWithKeys(fn ($k) => [
        (string) $k->id => [
            'kode_kelas' => (string) $k->kode_kelas,
            'nama_kelas' => (string) $k->nama_kelas,
        ],
    ])->toArray();
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
    <div class="lg:col-span-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Buat / Perbarui Kode</h2>
            <form method="POST" action="{{ route('admin.evaluasi.kode.store') }}" class="space-y-4"
                  x-data="{
                      kelasMap: @js($kelasKodeMap),
                      kelasId: @js((string) old('kelas_id', '')),
                      kode: @js((string) old('kode', '')),
                      namaKelas: @js((string) old('nama_kelas', '')),
                      applyKelas() {
                          const item = this.kelasMap[this.kelasId];
                          if (!item) return;
                          this.kode = item.kode_kelas || '';
                          this.namaKelas = item.nama_kelas || '';
                      }
                  }">
                @csrf
                @if(($kelasList ?? collect())->isNotEmpty())
                <div>
                    <label for="kelas_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Pilih Kelas (Opsional)</label>
                    <select id="kelas_id" name="kelas_id" x-model="kelasId" @change="applyKelas()" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Ketik Kelas Manual</option>
                        @foreach($kelasList as $kls)
                            <option value="{{ $kls->id }}">{{ $kls->display_label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label for="kode" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kode Kelas</label>
                    <input type="text" id="kode" name="kode" x-model="kode" maxlength="50" placeholder="Kosongkan untuk kode acak" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 font-mono uppercase text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan untuk generate kode acak atau otomatis dari master kelas.</p>
                </div>

                <div>
                    <label for="nama_kelas" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Kelas Pelatihan</label>
                    <input type="text" id="nama_kelas" name="nama_kelas" x-model="namaKelas" maxlength="100" placeholder="Contoh: Rekayasa Perangkat Lunak" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
                </div>

                <div>
                    <label for="instruktur_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Instruktur Pengampu (Opsional)</label>
                    <select id="instruktur_id" name="instruktur_id" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Pengajar di Kelas Ini</option>
                        @foreach($instrukturs as $ins)
                            <option value="{{ $ins->id }}" {{ old('instruktur_id') == $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="keterangan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan Kelas (Opsional)</label>
                    <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}" placeholder="Catatan materi atau periode evaluasi" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
                </div>

                <div>
                    <label for="max_pengisian" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Maksimal Pengisian (Opsional)</label>
                    <input type="number" id="max_pengisian" name="max_pengisian" value="{{ old('max_pengisian') }}" min="1" max="10000" placeholder="Kosongkan jika tanpa batas" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
                </div>

                <div>
                    <label for="expired_at" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Kedaluwarsa (Opsional)</label>
                    <input type="date" id="expired_at" name="expired_at" value="{{ old('expired_at') }}" class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Simpan Kode Evaluasi
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-8">
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('admin.evaluasi.kode.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[180px] flex-1">
                    <label for="filter_instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
                    <select id="filter_instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Kelas / Instruktur</option>
                        @foreach($instrukturs as $ins)
                            <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
                @if(($kelasList ?? collect())->isNotEmpty())
                <div class="w-full sm:w-auto">
                    <label for="filter_kelas" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                    <select id="filter_kelas" name="kelas" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $kls)
                            <option value="{{ $kls->id }}" {{ (string) ($kelasId ?? request('kelas')) === (string) $kls->id ? 'selected' : '' }}>{{ $kls->display_label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="w-full sm:w-auto">
                    <label for="filter_status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select id="filter_status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="1" {{ (string) ($statusFilter ?? request('status')) === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ (string) ($statusFilter ?? request('status')) === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
                @if(request()->hasAny(['instruktur', 'kelas', 'status']))
                    <a href="{{ route('admin.evaluasi.kode.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <th class="px-4 py-3">Kode Kelas</th>
                            <th class="px-4 py-3">Kelas</th>
                            <th class="px-4 py-3">Instruktur</th>
                            <th class="px-4 py-3">Pengisian</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($kodes as $kode)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 font-mono text-sm font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $kode->kode }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($kode->kelas_id)
                                    <a href="{{ route('admin.kelas.show', $kode->kelas_id) }}" class="font-semibold text-gray-900 hover:text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">{{ $kode->nama_kelas ?? ('Kelas ' . $kode->kode) }}</a>
                                @else
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $kode->nama_kelas ?? ('Kelas ' . $kode->kode) }}</p>
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $kode->keterangan ?: 'Evaluasi Kelas' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($kode->instruktur)
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $kode->instruktur->nama_lengkap }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $kode->instruktur->kode_instruktur }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        Multi-Instruktur
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">
                                {{ $kode->jumlah_diisi }} / {{ $kode->max_pengisian ?? 'Tanpa Batas' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $kode->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300' }}">
                                    {{ $kode->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1.5" x-data="{ copied: false }">
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ route('evaluasi.publik.index', ['kode' => $kode->kode]) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <span x-show="!copied">Salin Link</span>
                                        <span x-show="copied" x-cloak class="text-emerald-700 dark:text-emerald-300">Tersalin</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.evaluasi.kode.toggle', $kode) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $kode->is_active ? 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                            {{ $kode->is_active ? 'Tutup Sesi' : 'Buka Sesi' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada kode akses evaluasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($kodes->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $kodes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
