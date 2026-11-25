@extends('layouts.admin.app')
@section('title', 'Jadwal Mengajar')

@section('content')
<div class="space-y-6" x-data="{
    alihkanOpen: false,
    alihkanUrl: '',
    alihkanJudul: '',
    alihkanForm: {
        instruktur_id: '',
        ruangan: '',
        is_pola_mingguan: true,
        hari: '',
        tanggal: '',
        jam_mulai: '',
        jam_selesai: '',
        alasan_pengalihan: '',
    },
    alihkanAwal: {
        instruktur_nama: '',
        nama_kelas: '',
        ruangan: '',
        jadwal_label: '',
        jam_label: '',
    },
    openAlihkan(data) {
        this.alihkanUrl = data.url || '';
        this.alihkanJudul = data.judul || '';
        this.alihkanForm = {
            instruktur_id: String(data.instruktur_id ?? ''),
            ruangan: data.ruangan ?? '',
            is_pola_mingguan: Boolean(data.is_pola_mingguan),
            hari: data.hari ?? '',
            tanggal: data.tanggal ?? '',
            jam_mulai: data.jam_mulai ?? '',
            jam_selesai: data.jam_selesai ?? '',
            alasan_pengalihan: '',
        };
        this.alihkanAwal = {
            instruktur_nama: data.instruktur_nama ?? '-',
            nama_kelas: data.nama_kelas ?? '-',
            ruangan: data.ruangan || '-',
            jadwal_label: data.is_pola_mingguan ? (data.hari || '-') : (data.tanggal_display || data.tanggal || '-'),
            jam_label: (data.jam_mulai && data.jam_selesai) ? `${data.jam_mulai} - ${data.jam_selesai}` : '-',
        };
        this.alihkanOpen = true;
    },
    batalOpen: false,
    batalUrl: '',
    batalJudul: '',
    batalAlasan: '',
    openBatal(data) {
        this.batalUrl = data.url || '';
        this.batalJudul = data.judul || '';
        this.batalAlasan = '';
        this.batalOpen = true;
    }
}" @keydown.escape.window="alihkanOpen = false; batalOpen = false">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Jadwal Mengajar</h1>
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
                <a href="{{ route('admin.jadwal.index', $tabelQuery) }}"
                   class="inline-flex min-h-[44px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tampilan ?? 'tabel') === 'tabel' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                    Tabel
                </a>
                <a href="{{ route('admin.jadwal.index', $kalenderQuery) }}"
                   class="inline-flex min-h-[44px] items-center rounded-md px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tampilan ?? 'tabel') === 'kalender' ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                    Kalender
                </a>
            </div>
            <a href="{{ route('admin.jadwal.create') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Jadwal
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <form method="GET" action="{{ route('admin.jadwal.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tampilan" value="{{ $tampilan ?? 'tabel' }}">
            @if(($tampilan ?? 'tabel') === 'kalender')
                <input type="hidden" name="skala_kalender" value="{{ $skalaKalender ?? 'bulanan' }}">
                <input type="hidden" name="tanggal_kalender" value="{{ $tanggalKalender ?? today()->toDateString() }}">
            @endif
            <div class="min-w-[180px] flex-1">
                <label for="search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pencarian</label>
                <input type="text" id="search" name="search" value="{{ $search ?? request('search') }}" placeholder="Cari kelas, instruktur, ruangan..."
                       class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
            </div>
            <div class="w-full sm:w-auto">
                <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
                <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Semua Instruktur</option>
                    @foreach($instrukturs as $ins)
                    <option value="{{ $ins->id }}" {{ (string) ($instrukturId ?? request('instruktur')) === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }}</option>
                    @endforeach
                </select>
            </div>
            @if(($kelasList ?? collect())->isNotEmpty())
            <div class="w-full sm:w-auto">
                <label for="kelas" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select id="kelas" name="kelas" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kls)
                        <option value="{{ $kls->id }}" {{ (string) ($kelasId ?? request('kelas')) === (string) $kls->id ? 'selected' : '' }}>{{ $kls->display_label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="w-full sm:w-auto">
                <label for="hari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Hari</label>
                <select id="hari" name="hari" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Semua Hari</option>
                    @foreach(\App\Models\Jadwal::DAFTAR_HARI as $h)
                        <option value="{{ $h }}" {{ ($hariFilter ?? request('hari')) === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            @if(($tampilan ?? 'tabel') !== 'kalender')
            <div class="w-full sm:w-auto">
                <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ $dari ?? request('dari') }}"
                       class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>
            <div class="w-full sm:w-auto">
                <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ $sampai ?? request('sampai') }}"
                       class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>
            @endif
            <div class="w-full sm:w-auto">
                <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select id="status" name="status" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="terjadwal" {{ ($status ?? request('status')) === 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                    <option value="berlangsung" {{ ($status ?? request('status')) === 'berlangsung' ? 'selected' : '' }}>Sedang Berlangsung</option>
                    <option value="selesai" {{ ($status ?? request('status')) === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="dibatalkan" {{ ($status ?? request('status')) === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
            <a href="{{ route('admin.jadwal.index', ($tampilan ?? 'tabel') === 'kalender' ? ['tampilan' => 'kalender', 'skala_kalender' => $skalaKalender ?? 'bulanan'] : ['tampilan' => 'tabel']) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
        </form>
    </div>

    @if(($tampilan ?? 'tabel') === 'kalender')
        <x-jadwal.kalender-view
            routePrefix="admin.jadwal"
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
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                            <th class="px-4 py-3">Hari / Tanggal</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Instruktur</th>
                            <th class="px-4 py-3">Kelas &amp; Materi</th>
                            <th class="px-4 py-3">Ruangan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
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
                                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">
                                        Menggantikan: {{ $j->instrukturAsli->nama_lengkap }}
                                        @if(!$riwayatTerakhir && $j->alasan_pengalihan)
                                            ({{ $j->alasan_pengalihan }})
                                        @endif
                                    </p>
                                @endif
                                @if($riwayatTerakhir)
                                    <p class="mt-0.5 text-xs text-brand-700 dark:text-brand-300">
                                        Dialihkan ({{ $riwayatTerakhir['waktu'] ?? ($j->dialihkan_pada?->format('d/m/Y H:i') ?? '-') }}):
                                        {{ implode(', ', $riwayatTerakhir['perubahan'] ?? []) }}
                                        @if(!empty($riwayatTerakhir['alasan']))
                                            ({{ $riwayatTerakhir['alasan'] }})
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @php
                                    $showKodeAdmin = $j->kode_kelas && mb_strtolower(trim($j->kode_kelas)) !== mb_strtolower(trim($j->nama_kelas));
                                @endphp
                                @if($j->kelas_id)
                                    <a href="{{ route('admin.kelas.show', $j->kelas_id) }}" class="font-semibold text-gray-900 hover:text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-white dark:hover:text-brand-300">{{ $j->nama_kelas }}</a>
                                    @if($showKodeAdmin)
                                        <a href="{{ route('admin.kelas.show', $j->kelas_id) }}" class="ml-1 inline-flex rounded-md bg-brand-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-brand-700 hover:bg-brand-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-brand-950/60 dark:text-brand-300 dark:hover:bg-brand-900/60">{{ $j->kode_kelas }}</a>
                                    @endif
                                @else
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $j->nama_kelas }}</span>
                                    @if($showKodeAdmin)
                                        <span class="ml-1 inline-flex rounded-md bg-brand-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">{{ $j->kode_kelas }}</span>
                                    @endif
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $j->materi ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $j->ruangan_label }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $liveSt = $liveStatusMap[$j->id] ?? [
                                        'label' => $j->status_label,
                                        'color' => $j->status_color ?? 'gray',
                                        'code'  => $j->status,
                                    ];
                                    $nowWibAdm = now('Asia/Jakarta');
                                    $bukaMenitAdm = (int) \App\Models\Setting::get('checkin_buka_sebelum_menit', '30');
                                    $jamMulaiAdm = substr((string) ($j->jam_mulai ?? '00:00'), 0, 5);
                                    $tglSesiAdm = !$j->is_pola_mingguan && $j->tanggal ? $j->tanggal->toDateString() : $nowWibAdm->toDateString();
                                    $waktuBukaAdm = \Carbon\Carbon::parse($tglSesiAdm . ' ' . $jamMulaiAdm . ':00', 'Asia/Jakarta')->subMinutes(max(0, $bukaMenitAdm));
                                    $isBelumDimulaiAdm = (($liveSt['code'] ?? '') === 'belum_checkin') && $nowWibAdm->lt($waktuBukaAdm);
                                    $displayLiveLabelAdm = $isBelumDimulaiAdm ? 'Belum Dimulai' : $liveSt['label'];
                                    $liveCls = $isBelumDimulaiAdm
                                        ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                        : match ($liveSt['color'] ?? 'gray') {
                                            'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                            'amber'   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                            'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                            default   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                        };
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $liveCls }}">{{ $displayLiveLabelAdm }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @php
                                        $alihkanData = [
                                            'url' => route('admin.jadwal.alihkan', $j),
                                            'judul' => $j->nama_kelas . ($j->materi ? ' - ' . $j->materi : ''),
                                            'instruktur_id' => $j->instruktur_id,
                                            'instruktur_nama' => $j->instruktur->nama_lengkap ?? '-',
                                            'nama_kelas' => $j->nama_kelas,
                                            'ruangan' => $j->ruangan ?? '',
                                            'is_pola_mingguan' => (bool) $j->is_pola_mingguan,
                                            'hari' => $j->hari ?? 'Senin',
                                            'tanggal' => $j->tanggal?->format('Y-m-d') ?? '',
                                            'tanggal_display' => $j->tanggal_label,
                                            'jam_mulai' => substr((string) $j->jam_mulai, 0, 5),
                                            'jam_selesai' => substr((string) $j->jam_selesai, 0, 5),
                                        ];
                                    @endphp
                                    <button type="button"
                                            data-alihkan="{{ json_encode($alihkanData) }}"
                                            @click="openAlihkan(JSON.parse($el.dataset.alihkan))"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                            title="Alihkan atau sesuaikan jadwal">
                                        Alihkan
                                    </button>
                                    @if(in_array($j->status, ['terjadwal', 'berlangsung'], true))
                                        @php
                                            $batalData = [
                                                'url'   => route('admin.jadwal.batal', $j),
                                                'judul' => $j->nama_kelas . ($j->materi ? ' - ' . $j->materi : ''),
                                            ];
                                        @endphp
                                        <button type="button"
                                                data-batal="{{ json_encode($batalData) }}"
                                                @click="openBatal(JSON.parse($el.dataset.batal))"
                                                class="inline-flex min-h-[44px] items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/60"
                                                title="Batalkan jadwal">
                                            Batalkan
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.jadwal.edit', $j) }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800" title="Edit jadwal" aria-label="Edit jadwal {{ $j->nama_kelas }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form id="del-jadwal-{{ $j->id }}" method="POST" action="{{ route('admin.jadwal.destroy', $j) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete('del-jadwal-{{ $j->id }}', 'Hapus Jadwal', 'Yakin ingin menghapus jadwal {{ addslashes($j->nama_kelas) }}?')"
                                                class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-red-950/30 dark:hover:text-red-300" title="Hapus jadwal" aria-label="Hapus jadwal {{ $j->nama_kelas }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
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

    <x-jadwal.alihkan-modal :instrukturs="$instrukturs" :ruanganList="$ruanganList ?? collect()" />

    <div x-show="batalOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="batal-jadwal-title"
         class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto bg-gray-900/60 p-4 sm:items-center">
        <div @click.outside="batalOpen = false" class="my-8 w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h2 id="batal-jadwal-title" class="text-base font-bold text-gray-900 dark:text-white">Batalkan Jadwal Mengajar</h2>
                <button type="button" @click="batalOpen = false" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-800" aria-label="Tutup dialog">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" :action="batalUrl" class="mt-4 space-y-4">
                @csrf
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    Jadwal <span class="font-semibold text-gray-900 dark:text-white" x-text="batalJudul"></span> akan ditandai <span class="font-semibold text-amber-700 dark:text-amber-300">Dibatalkan</span> tanpa menghapus riwayat kinerja atau presensi sebelumnya.
                </p>
                <div>
                    <label for="alasan_pembatalan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-200">Alasan Pembatalan (Opsional)</label>
                    <input type="text" id="alasan_pembatalan" name="alasan_pembatalan" x-model="batalAlasan" maxlength="255"
                           placeholder="Contoh: Instruktur berhalangan hadir / jadwal digabung"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="batalOpen = false" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Tutup</button>
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Konfirmasi Batalkan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
