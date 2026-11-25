@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Jadwal &amp; Presensi Mengajar</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Check-in dibuka {{ $bukaSebelumMenit }} menit sebelum mulai, toleransi terlambat {{ $toleransiMenit }} menit.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($instruktur->status_ketersediaan && $instruktur->status_ketersediaan !== 'tersedia')
                <span class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                    Status: {{ $instruktur->ketersediaan_label }}
                </span>
            @endif
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-900">
                <a href="{{ route('instruktur.jadwal.index', array_merge(request()->except('tampilan'), ['tampilan' => 'daftar'])) }}"
                    class="inline-flex min-h-[38px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition {{ ($tampilan ?? 'daftar') === 'daftar' ? 'bg-brand-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
                    Daftar
                </a>
                <a href="{{ route('instruktur.jadwal.index', array_merge(request()->except('tampilan'), ['tampilan' => 'kalender'])) }}"
                    class="inline-flex min-h-[38px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition {{ ($tampilan ?? 'daftar') === 'kalender' ? 'bg-brand-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
                    Kalender
                </a>
            </div>
            <a href="{{ route('instruktur.aktivitas.index') }}"
                class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Isi Jurnal Aktivitas
            </a>
        </div>
    </div>

    @if (isset($belumCheckoutList) && $belumCheckoutList->isNotEmpty())
        <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-4 dark:border-blue-800/70 dark:bg-blue-950/30">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                    Pengingat: {{ $belumCheckoutList->count() }} sesi berstatus Check-in Aktif dan belum Check-out.
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($belumCheckoutList as $bc)
                        @php $bcTgl = $bc->tanggal ? $bc->tanggal->toDateString() : today()->toDateString(); @endphp
                        <a href="#riwayat-absensi-{{ $bc->id }}"
                            class="inline-flex min-h-[44px] items-center rounded-lg border border-blue-300 bg-white px-3 py-1.5 text-xs font-semibold text-blue-900 hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-blue-700 dark:bg-gray-900 dark:text-blue-200 dark:hover:bg-blue-950">
                            {{ $bc->tanggal?->format('d/m/Y') ?? $bcTgl }} - {{ $bc->jadwal?->nama_kelas ?? 'Sesi' }} ({{ substr((string) $bc->jam_checkin, 0, 5) }} WIB)
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Filter Tanggal Sesi & Pencarian --}}
    <form method="GET" action="{{ route('instruktur.jadwal.index') }}"
        class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        @if (($tampilan ?? 'daftar') === 'kalender')
            <input type="hidden" name="tampilan" value="kalender" />
        @endif
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="tanggal_sesi" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tanggal Sesi</label>
                <input type="date" id="tanggal_sesi" name="tanggal_sesi" value="{{ $tanggalSesi }}"
                    class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
            </div>
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Cari Kelas / Materi</label>
                <input type="text" id="search" name="search" value="{{ $search }}"
                    placeholder="Contoh: RPL, Pemrograman, L-01..."
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
            </div>
            <div>
                <label for="hari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Filter Referensi Hari</label>
                <select id="hari" name="hari"
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Hari</option>
                    @foreach (\App\Models\Jadwal::DAFTAR_HARI as $h)
                        <option value="{{ $h }}" {{ $hariFilter === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Terapkan
                </button>
                <a href="{{ route('instruktur.jadwal.index') }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Hari Ini
                </a>
            </div>
        </div>
    </form>

    @if (($tampilan ?? 'daftar') === 'kalender')
        <x-jadwal.kalender-view
            route-prefix="instruktur.jadwal"
            :calendar-events="$calendarEvents"
            :cal-anchor="$calAnchor"
            :cal-range-start="$calRangeStart"
            :cal-range-end="$calRangeEnd"
            :skala-kalender="$skalaKalender"
        />
    @else
        {{-- Jadwal Mengajar Spesifik Tanggal Terpilih (Default: Hari Ini) --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Jadwal Mengajar: {{ $hariTanggal }}, {{ \Carbon\Carbon::parse($tanggalSesi)->format('d/m/Y') }} ({{ $jadwalPadaTanggal->count() }} sesi)
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Menampilkan hanya kelas yang dijadwalkan pada tanggal terpilih.
                    </p>
                </div>
                <span id="gps-live-indicator" class="text-xs text-gray-500 dark:text-gray-400">
                    Lokasi GPS otomatis dicatat saat Check-in / Check-out
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3">Waktu &amp; Toleransi</th>
                            <th class="px-5 py-3">Kelas &amp; Materi</th>
                            <th class="px-5 py-3">Ruangan</th>
                            <th class="px-5 py-3">Status Presensi</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($jadwalPadaTanggal as $j)
                            @php
                                $absenSesi   = $j->absensis->first();
                                $tglTarget   = $j->tanggal ? $j->tanggal->toDateString() : $tanggalSesi;
                                $eligibility = $eligibilityMap[$j->id] ?? ['can_checkin' => false, 'can_checkout' => false, 'reason' => 'Tidak tersedia', 'code' => 'unavailable'];
                                $jamMulai    = substr((string) $j->jam_mulai, 0, 5);
                                $jamSelesai  = substr((string) $j->jam_selesai, 0, 5);
                                $batasToleransi = \Carbon\Carbon::createFromFormat('!H:i', $jamMulai)->addMinutes($toleransiMenit)->format('H:i');
                            @endphp
                            <tr class="{{ ($absenSesi && $absenSesi->is_belum_checkout) ? 'bg-blue-50/40 dark:bg-blue-950/20' : 'hover:bg-gray-50/70 dark:hover:bg-gray-800/40' }}">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <p class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                        {{ $jamMulai }} - {{ $jamSelesai }} WIB
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        Toleransi s.d. {{ $batasToleransi }} WIB
                                    </p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $j->nama_kelas }}</p>
                                        @if ($j->kode_kelas && mb_strtolower(trim($j->kode_kelas)) !== mb_strtolower(trim($j->nama_kelas)))
                                            <span class="inline-flex items-center rounded bg-brand-50 px-2 py-0.5 font-mono text-[11px] font-semibold text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">
                                                {{ $j->kode_kelas }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $j->materi }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                    {{ $j->ruangan_label }}
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($absenSesi)
                                        <div class="space-y-1 text-xs">
                                            @if ($absenSesi->is_belum_checkout)
                                                <span class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-2.5 py-0.5 font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                                    Check-in Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 font-semibold
                                                    {{ $absenSesi->status === 'hadir'
                                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                        : ($absenSesi->status === 'terlambat'
                                                            ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                                                            : (in_array($absenSesi->status, ['izin', 'sakit'], true)
                                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                                                                : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300')) }}">
                                                    {{ $absenSesi->status_label }}
                                                    @if ($absenSesi->menit_terlambat !== null && $absenSesi->menit_terlambat > 0)
                                                        ({{ $absenSesi->menit_terlambat }} mnt)
                                                    @endif
                                                </span>
                                            @endif
                                            <p class="font-mono text-[11px] text-gray-600 dark:text-gray-300">
                                                {{ $absenSesi->jam_checkin ? substr($absenSesi->jam_checkin, 0, 5) : '-' }} / {{ $absenSesi->jam_checkout ? substr($absenSesi->jam_checkout, 0, 5) : 'Belum Check-out' }}
                                            </p>
                                        </div>
                                    @else
                                        @php
                                            $live = $statusSesiMap[$j->id] ?? ['label' => 'Belum Hadir', 'color' => 'amber', 'code' => 'belum_checkin'];
                                            $eligCode = $eligibility['code'] ?? '';
                                            $liveCode = $live['code'] ?? '';
                                            $isBelumDimulai = ($j->status !== 'dibatalkan') && (
                                                in_array($eligCode, ['belum_dibuka', 'belum_hari_jadwal'], true)
                                                || ($liveCode === 'belum_checkin' && !($eligibility['can_checkin'] ?? false))
                                            );
                                            $displayLiveLabel = $isBelumDimulai ? 'Belum Dimulai' : $live['label'];
                                            $liveBadgeCls = $isBelumDimulai
                                                ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                : match ($live['color']) {
                                                    'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                                    'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                                    'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                                    'amber'   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                                    default   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                                };
                                        @endphp
                                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $liveBadgeCls }}">
                                            {{ $displayLiveLabel }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        @if ($eligibility['can_checkin'])
                                            <form method="POST" action="{{ route('instruktur.jadwal.checkin', $j) }}" onsubmit="return submitWithOptionalGps(event, this)">
                                                @csrf
                                                <input type="hidden" name="tanggal_sesi" value="{{ $tglTarget }}" />
                                                <input type="hidden" name="latitude" value="" />
                                                <input type="hidden" name="longitude" value="" />
                                                <input type="hidden" name="akurasi_gps" value="" />
                                                <input type="hidden" name="status_gps" value="tidak_tersedia" />
                                                <button type="submit"
                                                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                                    Check-in
                                                </button>
                                            </form>
                                        @elseif ($eligibility['can_checkout'])
                                            <form method="POST" action="{{ route('instruktur.jadwal.checkout', $j) }}" onsubmit="return submitWithOptionalGps(event, this)">
                                                @csrf
                                                <input type="hidden" name="tanggal_sesi" value="{{ $tglTarget }}" />
                                                <input type="hidden" name="latitude" value="" />
                                                <input type="hidden" name="longitude" value="" />
                                                <input type="hidden" name="akurasi_gps" value="" />
                                                <input type="hidden" name="status_gps" value="tidak_tersedia" />
                                                <button type="submit"
                                                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                    Check-out
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex min-h-[36px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $eligibility['reason'] ?? 'Tidak tersedia' }}
                                            </span>
                                        @endif
                                        <a href="{{ route('instruktur.aktivitas.index') }}#jadwal-{{ $j->id }}"
                                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Isi Jurnal
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        Tidak ada jadwal mengajar pada {{ $hariTanggal }}, {{ \Carbon\Carbon::parse($tanggalSesi)->format('d/m/Y') }}.
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Check-in tidak tersedia karena tidak ada kelas yang dijadwalkan pada tanggal ini.
                                    </p>
                                    @if ($jadwalBerikutnya)
                                        <div class="mt-4">
                                            <a href="{{ route('instruktur.jadwal.index', ['tanggal_sesi' => $jadwalBerikutnya['tanggal_sesi']]) }}"
                                                class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Lihat Jadwal Berikutnya: {{ $jadwalBerikutnya['hari'] }}, {{ $jadwalBerikutnya['tanggal']->format('d/m/Y') }} ({{ $jadwalBerikutnya['jadwal']->nama_kelas }})
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Referensi Jadwal Rutin Mingguan (Tanpa Tombol Check-in) --}}
        @if ($jadwalPolaMingguan->isNotEmpty())
            <details class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900" {{ ($hariFilter !== '' || $search !== '') ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white">
                    <span>Referensi Jadwal Rutin Mingguan ({{ $jadwalPolaMingguan->count() }} kelas)</span>
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Klik untuk buka/tutup daftar mingguan</span>
                </summary>
                <div class="border-t border-gray-200 overflow-x-auto dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3">Hari Rutin</th>
                                <th class="px-5 py-3">Jam Mulai - Selesai</th>
                                <th class="px-5 py-3">Kelas &amp; Materi</th>
                                <th class="px-5 py-3">Ruangan</th>
                                <th class="px-5 py-3 text-right">Sesi Terdekat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($jadwalPolaMingguan as $jp)
                                @php $tglTerdekat = $jp->tanggalTerdekatBerikutnya(\Carbon\Carbon::parse($tanggalSesi)); @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs font-semibold text-gray-900 dark:text-white">
                                        Setiap {{ $jp->hari }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {{ substr((string) $jp->jam_mulai, 0, 5) }} - {{ substr((string) $jp->jam_selesai, 0, 5) }} WIB
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $jp->nama_kelas }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $jp->materi }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-700 dark:text-gray-300">
                                        {{ $jp->ruangan_label }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        @if ($tglTerdekat)
                                            <a href="{{ route('instruktur.jadwal.index', ['tanggal_sesi' => $tglTerdekat->toDateString()]) }}"
                                                class="inline-flex min-h-[38px] items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                {{ $tglTerdekat->format('d/m/Y') }}
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    @endif

    {{-- Tabel Riwayat Presensi Instruktur --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Riwayat Kehadiran</h2>
                @if (isset($rekapAbsensi))
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                        <span>
                            Total Sesi Terjadwal: <strong class="text-gray-900 dark:text-white">{{ $rekapAbsensi['total'] }}</strong>
                            ({{ \Carbon\Carbon::parse($rekapAbsensi['periode_dari'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($rekapAbsensi['periode_sampai'])->format('d/m/Y') }})
                        </span>
                        <span>&middot;</span>
                        <span>Tercatat {{ $rekapAbsensi['total_tercatat'] }} baris:</span>
                        <span class="text-emerald-700 dark:text-emerald-300">Hadir {{ $rekapAbsensi['hadir'] }}</span>
                        <span class="text-amber-700 dark:text-amber-300">Terlambat {{ $rekapAbsensi['terlambat'] }}</span>
                        <span>Izin {{ $rekapAbsensi['izin'] }}</span>
                        <span class="text-red-700 dark:text-red-300">Tidak Hadir {{ $rekapAbsensi['tidak_hadir'] }}</span>
                        @if (($rekapAbsensi['belum_hadir_tanpa_catatan'] ?? 0) > 0)
                            <span class="text-slate-600 dark:text-slate-300">Belum Dimulai {{ $rekapAbsensi['belum_hadir_tanpa_catatan'] }}</span>
                        @endif
                        @if (($rekapAbsensi['belum_checkout'] ?? 0) > 0)
                            <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                {{ $rekapAbsensi['belum_checkout'] }} Belum Check-out
                            </span>
                        @endif
                        <span class="font-semibold text-gray-900 dark:text-white">Kehadiran {{ $rekapAbsensi['persentase'] }}%</span>
                    </div>
                @endif
            </div>

            <form method="GET" action="{{ route('instruktur.jadwal.index') }}" class="flex shrink-0 flex-wrap items-end gap-2 sm:flex-nowrap">
                @foreach (request()->except(['dari_absensi', 'sampai_absensi', 'page']) as $k => $v)
                    @if (is_string($v) && $v !== '')
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                    @endif
                @endforeach
                <div>
                    <label for="dari_absensi" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Dari</label>
                    <input type="date" id="dari_absensi" name="dari_absensi" value="{{ $dariAbsensi ?? '' }}"
                        class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div>
                    <label for="sampai_absensi" class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-300">Sampai</label>
                    <input type="date" id="sampai_absensi" name="sampai_absensi" value="{{ $sampaiAbsensi ?? '' }}"
                        class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <button type="submit"
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg bg-brand-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Filter
                </button>
                @if (($dariAbsensi ?? '') !== '' || ($sampaiAbsensi ?? '') !== '')
                    <a href="{{ route('instruktur.jadwal.index', request()->except(['dari_absensi', 'sampai_absensi', 'page'])) }}"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Reset
                    </a>
                @endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Kelas &amp; Materi</th>
                        <th class="px-5 py-3">Jam Jadwal</th>
                        <th class="px-5 py-3">Check-in / Check-out</th>
                        <th class="px-5 py-3">Terlambat</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Lokasi &amp; GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($riwayatAbsensi as $abs)
                        @php
                            $gpsInfo    = $abs->status_gps_normalized;
                            $lokasiInfo = $abs->lokasi_checkin_info;
                            $gpsBadgeCls = match ($gpsInfo['color']) {
                                'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                            };
                        @endphp
                        <tr id="riwayat-absensi-{{ $abs->id }}" class="{{ $abs->is_belum_checkout ? 'bg-blue-50/40 dark:bg-blue-950/20' : 'hover:bg-gray-50/70 dark:hover:bg-gray-800/40' }}">
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs font-medium text-gray-900 dark:text-white">
                                {{ $abs->tanggal->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $abs->jadwal?->nama_kelas ?? 'Presensi Umum' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $abs->jadwal?->materi ?? ($abs->keterangan ?? '-') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-gray-700 dark:text-gray-300">
                                {{ $abs->jam_jadwal_range }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-800 dark:text-gray-200">
                                <span class="font-mono">
                                    {{ $abs->jam_checkin ? substr($abs->jam_checkin, 0, 5) : '-' }} / {{ $abs->jam_checkout ? substr($abs->jam_checkout, 0, 5) : '-' }}
                                </span>
                                @if ($abs->is_belum_checkout)
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                            Check-in Aktif (Belum Check-out)
                                        </span>
                                        @if ($abs->jadwal)
                                            <form method="POST" action="{{ route('instruktur.jadwal.checkout', $abs->jadwal) }}" onsubmit="return submitWithOptionalGps(event, this)">
                                                @csrf
                                                <input type="hidden" name="tanggal_sesi" value="{{ $abs->tanggal->toDateString() }}" />
                                                <input type="hidden" name="latitude" value="" />
                                                <input type="hidden" name="longitude" value="" />
                                                <input type="hidden" name="akurasi_gps" value="" />
                                                <input type="hidden" name="status_gps" value="tidak_tersedia" />
                                                <button type="submit"
                                                    class="inline-flex min-h-[32px] items-center rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                    Check-out Sekarang
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                {{ $abs->menit_terlambat !== null ? $abs->menit_terlambat . ' menit' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold
                                    {{ $abs->status === 'hadir' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : ($abs->status === 'terlambat' ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : (in_array($abs->status, ['izin', 'sakit'], true) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300')) }}">
                                    {{ $abs->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $gpsBadgeCls }}">
                                    {{ $gpsInfo['label'] }}
                                </span>
                                @if ($lokasiInfo)
                                    <p class="mt-1 font-medium text-gray-800 dark:text-gray-200">{{ $lokasiInfo['nama_lokasi'] }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $lokasiInfo['jarak_label'] }}</p>
                                @elseif ($gpsInfo['detail'])
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $gpsInfo['detail'] }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada riwayat kehadiran yang tercatat pada rentang tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayatAbsensi->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $riwayatAbsensi->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
    <x-instruktur.gps-submit-script />
@endpush
@endsection
