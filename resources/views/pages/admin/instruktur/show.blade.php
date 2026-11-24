@extends('layouts.admin.app')
@section('title', 'Detail Instruktur: ' . $instruktur->nama_lengkap)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('admin.instruktur.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar Instruktur
        </a>
        <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Detail Instruktur</h1>
    </div>
    <a href="{{ route('admin.instruktur.edit', $instruktur) }}" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        Edit Instruktur
    </a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col items-center text-center">
                <img src="{{ $instruktur->foto_url }}" alt="{{ $instruktur->nama_lengkap }}" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="mt-3 text-base font-bold text-gray-900 dark:text-white">{{ $instruktur->nama_lengkap }}</h2>
                <p class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $instruktur->kode_instruktur }}</p>
                <div class="mt-2.5 flex flex-wrap justify-center gap-1.5">
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize {{ $instruktur->status === 'aktif' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300' }}">{{ $instruktur->status_label }}</span>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ ($instruktur->status_ketersediaan ?? 'tersedia') === 'tersedia' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">{{ $instruktur->ketersediaan_label }}</span>
                </div>
            </div>

            <dl class="mt-6 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Email</dt>
                    <dd class="min-w-0 break-all text-right font-medium text-gray-900 dark:text-white">{{ $instruktur->email ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Telepon</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $instruktur->no_telepon ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Keahlian</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $instruktur->bidang_keahlian ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Akun Login</dt>
                    <dd class="min-w-0 break-all text-right font-medium text-gray-900 dark:text-white">{{ $instruktur->user ? $instruktur->user->email : 'Belum terhubung' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Kinerja</dt>
                    <dd class="text-right font-semibold text-gray-900 dark:text-white">{{ $kinerjaSummary['kategori_label'] ?? 'Belum Ada Data' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-gray-600 dark:text-gray-300">Sertifikasi</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">
                        @if($instruktur->sertifikasis->isNotEmpty())
                            <a href="#sertifikasi" class="text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                                {{ $instruktur->sertifikasis->count() }} sertifikat
                            </a>
                        @else
                            Belum ada
                        @endif
                    </dd>
                </div>
                @if(($instruktur->status_ketersediaan ?? 'tersedia') !== 'tersedia')
                <div class="border-t border-gray-200 pt-2.5 dark:border-gray-800">
                    <dt class="mb-1 text-xs font-semibold text-amber-800 dark:text-amber-300">Periode Cuti / Izin</dt>
                    <dd class="text-xs text-gray-800 dark:text-gray-200">
                        {{ $instruktur->cuti_mulai?->format('d/m/Y') ?? '-' }} s.d. {{ $instruktur->cuti_selesai?->format('d/m/Y') ?? '-' }}
                        {{ $instruktur->keterangan_cuti ? '(' . $instruktur->keterangan_cuti . ')' : '' }}
                    </dd>
                </div>
                @endif
                @if($instruktur->riwayat_keahlian)
                <div class="border-t border-gray-200 pt-2.5 dark:border-gray-800">
                    <dt class="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Riwayat Keahlian</dt>
                    <dd class="text-xs text-gray-800 dark:text-gray-200">{{ $instruktur->riwayat_keahlian }}</dd>
                </div>
                @endif
                @if($instruktur->alamat)
                <div class="border-t border-gray-200 pt-2.5 dark:border-gray-800">
                    <dt class="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Alamat</dt>
                    <dd class="text-xs text-gray-800 dark:text-gray-200">{{ $instruktur->alamat }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-gray-800 dark:bg-gray-800">
            <div class="grid grid-cols-2 gap-px sm:grid-cols-4">
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Hadir (30 Hari)</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $statsAbsensi['hadir'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Terlambat</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $statsAbsensi['terlambat'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Izin</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-brand-700 dark:text-brand-300">{{ $statsAbsensi['izin'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 dark:bg-gray-900">
                    <p class="text-xs text-gray-600 dark:text-gray-300">Evaluasi ({{ $statsEvaluasi['total'] ?? 0 }})</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ ($statsEvaluasi['total'] ?? 0) > 0 ? number_format($statsEvaluasi['rata_rata'], 2) : '-' }}</p>
                </div>
            </div>
        </div>

        <x-instruktur.sertifikasi-section
            :instruktur="$instruktur"
            :editable="true"
            route-prefix="admin"
            redirect-to="show"
        />

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Jadwal Mengajar</h3>
            @if($instruktur->jadwals->isEmpty())
            <p class="py-4 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada jadwal mengajar.</p>
            @else
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($instruktur->jadwals as $j)
                <div class="flex flex-wrap items-baseline justify-between gap-2 py-2.5 text-sm">
                    <div class="min-w-0">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $j->nama_kelas }}</span>
                        <span class="text-gray-600 dark:text-gray-300"> &middot; {{ $j->materi ?? 'Umum' }}</span>
                    </div>
                    <span class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $j->tanggal_label }} {{ substr($j->jam_mulai, 0, 5) }}-{{ substr($j->jam_selesai, 0, 5) }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Aktivitas Mengajar</h3>
            @if($instruktur->aktivitasMengajars->isEmpty())
            <p class="py-4 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada aktivitas mengajar yang tercatat.</p>
            @else
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($instruktur->aktivitasMengajars as $akt)
                @php
                    $aktKelasInfo = $akt->status_kelas_info;
                    $aktKelasCls  = match ($aktKelasInfo['color']) {
                        'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                        'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                        default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                    };
                @endphp
                <div class="flex flex-wrap items-baseline justify-between gap-2 py-2.5 text-sm">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $akt->materi_disampaikan ?: ($akt->jadwal->nama_kelas ?? 'Sesi Mengajar') }}</p>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium {{ $aktKelasCls }}">
                                Kelas: {{ $aktKelasInfo['label'] }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $akt->status_label }} &middot; {{ $akt->verifikasi_label }} &middot; Durasi: {{ $akt->durasi_aktual_label }}</p>
                    </div>
                    <span class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $akt->tanggal->format('d/m/Y') }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Riwayat Perubahan</h3>
            @if($instruktur->auditLogs->isEmpty())
                <p class="py-4 text-center text-xs text-gray-600 dark:text-gray-300">Belum ada riwayat perubahan profil.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Waktu</th>
                                <th class="px-3 py-2 font-semibold">Aksi</th>
                                <th class="px-3 py-2 font-semibold">Ringkasan</th>
                                <th class="px-3 py-2 font-semibold">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($instruktur->auditLogs as $log)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-gray-600 dark:text-gray-300">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $log->aksi_label }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $log->ringkasan_perubahan }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $log->user?->name ?? 'Sistem' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
