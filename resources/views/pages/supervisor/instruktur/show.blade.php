@extends('layouts.supervisor.app')
@section('title', 'Detail Instruktur: ' . $instruktur->nama_lengkap)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('supervisor.instruktur.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar Instruktur
        </a>
        <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $instruktur->nama_lengkap }}</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('supervisor.jadwal.index', ['instruktur' => $instruktur->id]) }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Lihat Jadwal Instruktur
        </a>
        <a href="{{ route('supervisor.laporan.index', ['instruktur' => $instruktur->id]) }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Lihat Laporan Instruktur
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col items-center text-center">
            <img src="{{ $instruktur->foto_url }}" alt="{{ $instruktur->nama_lengkap }}" class="h-24 w-24 rounded-full object-cover">
            <h2 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ $instruktur->nama_lengkap }}</h2>
            <p class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $instruktur->kode_instruktur }}</p>
            <span class="mt-2 inline-flex rounded-full px-3 py-0.5 text-xs font-medium capitalize {{ $instruktur->status === 'aktif' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300' }}">{{ $instruktur->status_label }}</span>
        </div>

        <dl class="mt-6 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
            <div class="flex justify-between gap-2">
                <dt class="text-gray-600 dark:text-gray-300">Email</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $instruktur->email ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-600 dark:text-gray-300">Telepon</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $instruktur->no_telepon ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-600 dark:text-gray-300">Bidang Keahlian</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $instruktur->bidang_keahlian ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-600 dark:text-gray-300">Akun Login</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $instruktur->user ? $instruktur->user->email : 'Belum terhubung' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-600 dark:text-gray-300">Kategori Kinerja</dt>
                <dd class="font-semibold text-gray-900 dark:text-white">{{ $kinerjaSummary['kategori_label'] ?? 'Menunggu Konfirmasi LPK' }}</dd>
            </div>
            @if($instruktur->alamat)
            <div class="border-t border-gray-100 pt-2 dark:border-gray-800">
                <dt class="mb-1 text-gray-600 dark:text-gray-300">Alamat</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $instruktur->alamat }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-gray-900">
                <p class="font-mono text-2xl font-bold text-green-700 dark:text-green-400">{{ $statsAbsensi['hadir'] ?? 0 }}</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Hadir (30 Hari)</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-gray-900">
                <p class="font-mono text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $statsAbsensi['terlambat'] ?? 0 }}</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Terlambat</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-gray-900">
                <p class="font-mono text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $statsAbsensi['izin'] ?? 0 }}</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Izin</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-gray-900">
                <p class="font-mono text-2xl font-bold text-brand-700 dark:text-brand-400">{{ ($statsEvaluasi['total'] ?? 0) > 0 ? number_format($statsEvaluasi['rata_rata'], 2) : '-' }}</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Rata-rata Evaluasi ({{ $statsEvaluasi['total'] ?? 0 }})</p>
            </div>
        </div>

        <x-instruktur.sertifikasi-section
            :instruktur="$instruktur"
            :editable="false"
            route-prefix="supervisor"
        />

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Jadwal Mengajar Terbaru</h3>
            @if($instruktur->jadwals->isEmpty())
            <p class="py-4 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada jadwal mengajar.</p>
            @else
            <div class="space-y-2">
                @foreach($instruktur->jadwals as $j)
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3.5 py-2.5 text-sm dark:bg-gray-800/50">
                    <div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $j->nama_kelas }}</span>
                        <span class="text-gray-600 dark:text-gray-300"> - {{ $j->materi ?? 'Umum' }}</span>
                    </div>
                    <span class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $j->tanggal_label }} {{ substr($j->jam_mulai, 0, 5) }}-{{ substr($j->jam_selesai, 0, 5) }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Konfirmasi Aktivitas Mengajar Terbaru</h3>
            @if($instruktur->aktivitasMengajars->isEmpty())
            <p class="py-4 text-center text-sm text-gray-600 dark:text-gray-300">Belum ada aktivitas mengajar yang tercatat.</p>
            @else
            <div class="space-y-2">
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
                <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3.5 py-2.5 text-sm dark:bg-gray-800/50">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $akt->materi_disampaikan ?: ($akt->jadwal->nama_kelas ?? 'Sesi Mengajar') }}</p>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium {{ $aktKelasCls }}">
                                Kelas: {{ $aktKelasInfo['label'] }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">Status: {{ $akt->status_label }} &middot; {{ $akt->verifikasi_label }} &middot; Durasi: {{ $akt->durasi_aktual_label }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $akt->tanggal->format('d/m/Y') }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
