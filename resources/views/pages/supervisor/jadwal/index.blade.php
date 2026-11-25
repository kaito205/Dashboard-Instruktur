@extends('layouts.supervisor.app')
@section('title', 'Jadwal Mengajar')

@section('content')
<div>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pemantauan Jadwal Mengajar</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pantau jadwal kelas, instruktur, materi, dan waktu mengajar dalam tampilan tabel atau kalender</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @php
            $tabelQuery = collect(request()->query())
                ->except(['page', 'skala_kalender', 'tanggal_kalender'])
                ->merge(['tampilan' => 'tabel'])
                ->toArray();
            $kalenderQuery = collect(request()->query())
                ->except(['page', 'dari', 'sampai'])
                ->merge(['tampilan' => 'kalender'])
                ->toArray();
        @endphp
        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 dark:border-gray-700 dark:bg-gray-900">
            <a href="{{ route('supervisor.jadwal.index', $tabelQuery) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tampilan ?? 'tabel') === 'tabel' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Tampilan Tabel
            </a>
            <a href="{{ route('supervisor.jadwal.index', $kalenderQuery) }}"
               class="inline-flex min-h-[44px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tampilan ?? 'tabel') === 'kalender' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                Tampilan Kalender
            </a>
        </div>
    </div>
</div>

<div class="mb-6 rounded-xl border border-brand-200 bg-brand-50/70 p-4 text-sm text-brand-900 dark:border-brand-800/60 dark:bg-brand-950/30 dark:text-brand-200">
    <p class="font-semibold">Jadwal Pelatihan Mingguan {{ \App\Models\Setting::getAppIdentity()['lpk_name'] }} &amp; Integrasi Presensi</p>
    <p class="mt-1 text-xs leading-relaxed">
        Mendukung pola jadwal mingguan berulang (Senin s/d Minggu) maupun jadwal tanggal spesifik. Setiap sesi jadwal terhubung dengan Check-in / Check-out instruktur (dibuka {{ $bukaSebelumMenit ?? 30 }} menit sebelum jam mulai, toleransi keterlambatan {{ $toleransiMenit ?? 15 }} menit) serta portal evaluasi kelas (RPL, TKJ1-3, AP1-3, PS1-2).
    </p>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('supervisor.jadwal.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tampilan" value="{{ $tampilan ?? 'tabel' }}">
        @if(($tampilan ?? 'tabel') === 'kalender')
            <input type="hidden" name="skala_kalender" value="{{ $skalaKalender ?? 'bulanan' }}">
            <input type="hidden" name="tanggal_kalender" value="{{ $tanggalKalender ?? today()->toDateString() }}">
        @endif
        <div class="min-w-[180px] flex-1">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
            <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari kelas, materi, instruktur, hari, ruangan..."
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3.5 py-2 text-sm text-gray-800 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white dark:placeholder:text-gray-400">
        </div>
        <div>
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur</option>
                @foreach($instrukturs as $ins)
                <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="hari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Hari</label>
            <select id="hari" name="hari" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Hari</option>
                @foreach(\App\Models\Jadwal::DAFTAR_HARI as $h)
                    <option value="{{ $h }}" {{ ($hariFilter ?? request('hari')) === $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
            </select>
        </div>
        @if(($tampilan ?? 'tabel') !== 'kalender')
        <div>
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari ?? request('dari') }}"
                   class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div>
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai ?? request('sampai') }}"
                   class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        @endif
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="status" name="status" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="terjadwal" {{ ($status ?? request('status')) === 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                <option value="berlangsung" {{ ($status ?? request('status')) === 'berlangsung' ? 'selected' : '' }}>Sedang Berlangsung</option>
                <option value="selesai" {{ ($status ?? request('status')) === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="dibatalkan" {{ ($status ?? request('status')) === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
        <a href="{{ route('supervisor.jadwal.index', ($tampilan ?? 'tabel') === 'kalender' ? ['tampilan' => 'kalender', 'skala_kalender' => $skalaKalender ?? 'bulanan'] : ['tampilan' => 'tabel']) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

@if(($tampilan ?? 'tabel') === 'kalender')
    <x-jadwal.kalender-view
        routePrefix="supervisor.jadwal"
        :calendarEvents="$calendarEvents"
        :calAnchor="$calAnchor"
        :calRangeStart="$calRangeStart"
        :calRangeEnd="$calRangeEnd"
        :skalaKalender="$skalaKalender ?? 'bulanan'"
    />
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Tanggal</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Waktu</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Instruktur</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Kelas</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Materi</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Ruangan</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($jadwals as $j)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $j->tanggal_label }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $j->instruktur->nama_lengkap ?? '-' }}</p>
                            @php
                                $riwayatList = is_array($j->riwayat_pengalihan) ? $j->riwayat_pengalihan : [];
                                $riwayatTerakhir = $riwayatList !== [] ? $riwayatList[array_key_last($riwayatList)] : null;
                            @endphp
                            @if($j->instrukturAsli)
                                <p class="mt-0.5 text-[11px] text-amber-700 dark:text-amber-300">
                                    Menggantikan: {{ $j->instrukturAsli->nama_lengkap }}
                                    @if(!$riwayatTerakhir && $j->alasan_pengalihan)
                                        ({{ $j->alasan_pengalihan }})
                                    @endif
                                </p>
                            @endif
                            @if($riwayatTerakhir)
                                <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-300">
                                    Dialihkan ({{ $riwayatTerakhir['waktu'] ?? ($j->dialihkan_pada?->format('d/m/Y H:i') ?? '-') }}):
                                    {{ implode(', ', $riwayatTerakhir['perubahan'] ?? []) }}
                                    @if(!empty($riwayatTerakhir['alasan']))
                                        ({{ $riwayatTerakhir['alasan'] }})
                                    @endif
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $j->nama_kelas }}
                            @if($j->kode_kelas && mb_strtolower(trim($j->kode_kelas)) !== mb_strtolower(trim($j->nama_kelas)))
                                <span class="ml-1 inline-flex rounded bg-brand-50 px-1.5 py-0.5 font-mono text-[10px] font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $j->kode_kelas }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $j->materi ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $j->ruangan ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $liveSt = $liveStatusMap[$j->id] ?? [
                                    'label' => $j->status_label,
                                    'color' => $j->status_color ?? 'gray',
                                    'code'  => $j->status,
                                ];
                                $nowWibSup = now('Asia/Jakarta');
                                $bukaMenitSup = (int) \App\Models\Setting::get('checkin_buka_sebelum_menit', '30');
                                $jamMulaiSup = substr((string) ($j->jam_mulai ?? '00:00'), 0, 5);
                                $tglSesiSup = !$j->is_pola_mingguan && $j->tanggal ? $j->tanggal->toDateString() : $nowWibSup->toDateString();
                                $waktuBukaSup = \Carbon\Carbon::parse($tglSesiSup . ' ' . $jamMulaiSup . ':00', 'Asia/Jakarta')->subMinutes(max(0, $bukaMenitSup));
                                $isBelumDimulaiSup = (($liveSt['code'] ?? '') === 'belum_checkin') && $nowWibSup->lt($waktuBukaSup);
                                $displayLiveLabelSup = $isBelumDimulaiSup ? 'Belum Dimulai' : $liveSt['label'];
                                $liveCls = $isBelumDimulaiSup
                                    ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                    : match ($liveSt['color'] ?? 'gray') {
                                        'blue'    => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
                                        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
                                        'amber'   => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                        'red'     => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300',
                                        default   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                    };
                            @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $liveCls }}">{{ $displayLiveLabelSup }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada jadwal mengajar pada rentang tanggal ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jadwals->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
            {{ $jadwals->links() }}
        </div>
        @endif
    </div>
@endif
</div>
@endsection
