@extends('layouts.admin.app')
@section('title', 'Detail Kelas: ' . $kelas->nama_kelas)

@section('content')
@php
    $statusCls = match ($kelas->status) {
        'aktif'   => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'selesai' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        default   => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
    };
@endphp

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('admin.kelas.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar Kelas
        </a>
        <div class="mt-1 flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $kelas->nama_kelas }}</h1>
            <span class="inline-flex rounded-md bg-brand-50 px-2.5 py-0.5 font-mono text-xs font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $kelas->kode_kelas }}</span>
            @if($kelas->trashed())
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Diarsipkan</span>
            @else
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $statusCls }}">{{ $kelas->status_label }}</span>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.laporan.index', ['jenis' => 'jadwal', 'kelas' => $kelas->id]) }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Lihat Laporan
        </a>
        @if($kelas->trashed())
            <form method="POST" action="{{ route('admin.kelas.restore', $kelas->id) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                    Pulihkan Kelas
                </button>
            </form>
        @else
            <a href="{{ route('admin.jadwal.create', ['kelas_id' => $kelas->id]) }}"
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Tambah Jadwal
            </a>
            <a href="{{ route('admin.kelas.edit', $kelas) }}"
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                Edit Kelas
            </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Informasi Kelas</h2>
            <dl class="mt-4 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Kode Kelas</dt>
                    <dd class="font-mono font-semibold text-gray-900 dark:text-white">{{ $kelas->kode_kelas }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Nama Kelas</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $kelas->nama_kelas }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Program</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $kelas->program_pelatihan ?: '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Ruangan Default</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $kelas->ruangan_default_label }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Kuota Peserta</dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $kelas->kuota_peserta }} peserta</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Periode</dt>
                    <dd class="font-mono text-right text-xs font-medium text-gray-900 dark:text-white">{{ $kelas->periode_label }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Status</dt>
                    <dd>
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $statusCls }}">{{ $kelas->status_label }}</span>
                    </dd>
                </div>
                @if($kelas->keterangan)
                <div class="border-t border-gray-200 pt-3 dark:border-gray-800">
                    <dt class="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Keterangan</dt>
                    <dd class="text-xs text-gray-800 dark:text-gray-200">{{ $kelas->keterangan }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Instruktur Pengampu</h2>
            @if($instrukturPengampu->isEmpty())
                <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">Belum ada instruktur pengampu yang terjadwal pada kelas ini.</p>
            @else
                <ul class="mt-3 divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($instrukturPengampu as $row)
                    @php $ins = $row['instruktur']; @endphp
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('admin.instruktur.show', $ins) }}" class="block truncate font-semibold text-gray-900 hover:text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">
                                {{ $ins->nama_lengkap }}
                            </a>
                            <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $ins->kode_instruktur }}</p>
                        </div>
                        <span class="shrink-0 rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $row['jumlah_sesi'] }} sesi
                        </span>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-gray-800 dark:bg-gray-800">
            <div class="grid grid-cols-2 gap-px sm:grid-cols-4">
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Total Jadwal</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_jadwal'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Jadwal Aktif</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $stats['jadwal_aktif'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Kode Evaluasi</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-brand-700 dark:text-brand-300">{{ $stats['total_kode_evaluasi'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Evaluasi Masuk</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_evaluasi'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Jadwal Mengajar Terhubung</h3>
                <a href="{{ route('admin.jadwal.index', ['kelas' => $kelas->id]) }}" class="text-xs font-semibold text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                    Buka di Jadwal
                </a>
            </div>
            @if($jadwals->isEmpty())
                <p class="px-5 py-6 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada jadwal mengajar yang terhubung ke kelas ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                                <th class="px-4 py-2.5">Hari / Tanggal</th>
                                <th class="px-4 py-2.5">Waktu</th>
                                <th class="px-4 py-2.5">Instruktur</th>
                                <th class="px-4 py-2.5">Materi &amp; Ruangan</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($jadwals as $j)
                            @php
                                $jCls = match ($j->status) {
                                    'terjadwal'   => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
                                    'berlangsung' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                    'selesai'     => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    default       => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $j->tanggal_label }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-700 dark:text-gray-300">{{ substr((string) $j->jam_mulai, 0, 5) }} - {{ substr((string) $j->jam_selesai, 0, 5) }}</td>
                                <td class="px-4 py-2.5 text-xs font-medium text-gray-900 dark:text-white">{{ $j->instruktur?->nama_lengkap ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-700 dark:text-gray-300">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $j->materi ?: 'Umum' }}</p>
                                    <p class="text-gray-500 dark:text-gray-400">{{ $j->ruangan_label }}</p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $jCls }}">{{ $j->status_label }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ route('admin.jadwal.edit', $j) }}" class="inline-flex min-h-[44px] items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300 dark:hover:bg-brand-950/50">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kode Akses Evaluasi Terkait</h3>
                <a href="{{ route('admin.evaluasi.kode.index', ['kelas' => $kelas->id]) }}" class="text-xs font-semibold text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                    Kelola Kode Evaluasi
                </a>
            </div>
            @if($kodeEvaluasis->isEmpty())
                <p class="py-4 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada kode akses evaluasi untuk kelas ini.</p>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($kodeEvaluasis as $kode)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm" x-data="{ copied: false }">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex rounded-md bg-brand-50 px-2.5 py-1 font-mono text-xs font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $kode->kode }}</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $kode->nama_kelas ?: $kelas->nama_kelas }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $kode->instruktur ? $kode->instruktur->nama_lengkap : 'Multi-Instruktur' }}
                                    &middot; Pengisian: {{ $kode->jumlah_diisi }} / {{ $kode->max_pengisian ?? 'Tanpa Batas' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $kode->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300' }}">
                                {{ $kode->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ route('evaluasi.publik.index', ['kode' => $kode->kode]) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                <span x-show="!copied">Salin Link</span>
                                <span x-show="copied" x-cloak class="text-emerald-700 dark:text-emerald-300">Tersalin</span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
