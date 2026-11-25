@extends('layouts.supervisor.app')
@section('title', 'Pemantauan Kehadiran Instruktur')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pemantauan Kehadiran &amp; Verifikasi Aktivitas Mengajar</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pantau data Check-in / Check-out kehadiran instruktur, keterlambatan, status GPS, serta verifikasi laporan aktivitas mengajar</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('supervisor.observasi.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 dark:border-brand-500 dark:bg-gray-800 dark:text-brand-300">
            Input Observasi Kelas
        </a>
        <a href="{{ route('supervisor.laporan.export', ['jenis' => $tab === 'aktivitas' ? 'aktivitas' : 'absensi', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId]) }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            Ekspor CSV
        </a>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-1.5">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Persentase Kehadiran</p>
            <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                title="Dari {{ $ringkasan['total'] ?? 0 }} catatan kehadiran{{ !empty($ringkasan['status_filter_aktif']) ? ' (Filter ' . $ringkasan['status_filter_label'] . ': ' . $ringkasan['status_filter_count'] . '/' . $ringkasan['total'] . ' - ' . $ringkasan['status_filter_persentase'] . '%)' : '' }}.">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            </span>
        </div>
        <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['persentase'] ?? 0 }}%</p>
    </div>
    <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Hadir Tepat Waktu</p>
        <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['hadir'] ?? 0 }}</p>
    </div>
    <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-1.5">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Terlambat</p>
            <span class="inline-flex cursor-help text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                title="Toleransi keterlambatan: &gt; {{ $ringkasan['toleransi_terlambat_menit'] ?? 15 }} menit dari jam mulai jadwal.">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            </span>
        </div>
        <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['terlambat'] ?? 0 }}</p>
    </div>
    <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Izin</p>
        <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['izin'] ?? 0 }}</p>
    </div>
    <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Tidak Hadir (Alpa)</p>
        <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $ringkasan['tidak_hadir'] ?? 0 }}</p>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-800">
    <a href="{{ route('supervisor.kehadiran.index', ['tab' => 'absensi', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId]) }}"
       class="inline-flex min-h-[44px] items-center rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tab ?? 'absensi') === 'absensi' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}">
        Data Absensi Check-in / Check-out
    </a>
    <a href="{{ route('supervisor.kehadiran.index', ['tab' => 'aktivitas', 'dari' => $dari, 'sampai' => $sampai, 'instruktur' => $instrukturId]) }}"
       class="inline-flex min-h-[44px] items-center rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ ($tab ?? 'absensi') === 'aktivitas' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}">
        Konfirmasi &amp; Verifikasi Aktivitas Mengajar
    </a>
</div>

@php
    $todayStr       = today()->toDateString();
    $sevenDaysStart = today()->copy()->subDays(6)->toDateString();
    $monthStart     = today()->copy()->startOfMonth()->toDateString();
    $monthEnd       = today()->copy()->endOfMonth()->toDateString();
    $activeTab      = $tab ?? 'absensi';
    $presetBase     = array_filter([
        'tab'        => $activeTab,
        'instruktur' => $instrukturId ?? '',
        'status'     => $status ?? '',
        'sumber'     => $activeTab === 'absensi' ? ($sumber ?? '') : '',
    ], fn ($v) => $v !== '');
    $datePresets = [
        ['label' => 'Hari Ini', 'dari' => $todayStr, 'sampai' => $todayStr],
        ['label' => '7 Hari Terakhir', 'dari' => $sevenDaysStart, 'sampai' => $todayStr],
        ['label' => 'Bulan Ini', 'dari' => $monthStart, 'sampai' => $monthEnd],
    ];
@endphp
<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-3 flex flex-wrap items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-800">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Periode Cepat:</span>
        @foreach($datePresets as $preset)
            @php
                $isPresetActive = ($dari === $preset['dari'] && $sampai === $preset['sampai']);
            @endphp
            <a href="{{ route('supervisor.kehadiran.index', array_merge($presetBase, ['dari' => $preset['dari'], 'sampai' => $preset['sampai']])) }}"
               class="inline-flex min-h-[36px] items-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $isPresetActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                {{ $preset['label'] }}
            </a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('supervisor.kehadiran.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab ?? 'absensi' }}">
        <div>
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div>
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}" class="min-h-[44px] cursor-pointer rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white">
        </div>
        <div class="min-w-[180px] flex-1">
            <label for="instruktur" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Instruktur</label>
            <select id="instruktur" name="instruktur" class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Instruktur</option>
                @foreach($instrukturs as $ins)
                    <option value="{{ $ins->id }}" {{ (string) $instrukturId === (string) $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
                @endforeach
            </select>
        </div>
        @if(($tab ?? 'absensi') === 'absensi')
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Absensi</label>
            <select id="status" name="status" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="hadir" {{ ($status ?? '') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                <option value="terlambat" {{ ($status ?? '') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                <option value="izin" {{ ($status ?? '') === 'izin' ? 'selected' : '' }}>Izin</option>
                <option value="tidak_hadir" {{ ($status ?? '') === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
            </select>
        </div>
        <div>
            <label for="sumber" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Sumber Data</label>
            <select id="sumber" name="sumber" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Sumber</option>
                <option value="checkin_app" {{ ($sumber ?? '') === 'checkin_app' ? 'selected' : '' }}>Check-in Aplikasi</option>
                <option value="import" {{ ($sumber ?? '') === 'import' ? 'selected' : '' }}>Import CSV/Excel</option>
                <option value="manual" {{ ($sumber ?? '') === 'manual' ? 'selected' : '' }}>Input Manual</option>
            </select>
        </div>
        @else
        <div>
            <label for="status_aktivitas" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Konfirmasi</label>
            <select id="status_aktivitas" name="status" class="min-h-[44px] rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="dikonfirmasi" {{ ($status ?? '') === 'dikonfirmasi' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="belum_dikonfirmasi" {{ ($status ?? '') === 'belum_dikonfirmasi' ? 'selected' : '' }}>Belum Dikonfirmasi</option>
                <option value="tidak_terlaksana" {{ ($status ?? '') === 'tidak_terlaksana' ? 'selected' : '' }}>Tidak Terlaksana</option>
            </select>
        </div>
        @endif
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Filter</button>
        <a href="{{ route('supervisor.kehadiran.index', ['tab' => $tab ?? 'absensi']) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        @if(($tab ?? 'absensi') === 'aktivitas')
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                        <th class="px-4 py-3">Tanggal &amp; Jam</th>
                        <th class="px-4 py-3">Instruktur &amp; Kelas</th>
                        <th class="px-4 py-3">Materi &amp; Bukti</th>
                        <th class="px-4 py-3">Ringkasan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Verifikasi Supervisor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($aktivitas as $row)
                    @php
                        $kelasInfo    = $row->status_kelas_info;
                        $eskalasiInfo = $row->eskalasi_info;
                        $kelasBadgeCls = match ($kelasInfo['color']) {
                            'blue'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
                            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            'red'     => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                            default   => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 align-top">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $row->tanggal->format('d/m/Y') }}</p>
                            @if ($row->jam_mulai_aktual || $row->jam_selesai_aktual)
                                <p class="mt-0.5">{{ $row->jam_mulai_aktual ? substr($row->jam_mulai_aktual, 0, 5) : '-' }} - {{ $row->jam_selesai_aktual ? substr($row->jam_selesai_aktual, 0, 5) : '-' }} WIB</p>
                                @if ($row->durasi_aktual_label && $row->durasi_aktual_label !== '-')
                                    <p class="font-sans text-xs text-gray-600 dark:text-gray-300">Durasi: {{ $row->durasi_aktual_label }}</p>
                                @endif
                            @else
                                <p class="mt-0.5 text-slate-400">-</p>
                            @endif
                            <p class="font-sans text-xs text-gray-600 dark:text-gray-300">
                                Peserta:
                                @if ($row->jumlah_peserta_hadir !== null)
                                    {{ $row->jumlah_peserta_hadir }} orang
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($row->instruktur)
                                <a href="{{ route('supervisor.instruktur.show', $row->instruktur) }}" class="font-semibold text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                    {{ $row->instruktur->nama_lengkap }}
                                </a>
                            @else
                                <span class="font-semibold text-gray-900 dark:text-white">-</span>
                            @endif
                            <p class="text-gray-600 dark:text-gray-300">Kelas: {{ $row->jadwal?->nama_kelas ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $row->materi_disampaikan ?? '-' }}</p>
                            @if($row->tautan_materi)
                                <p class="mt-1">
                                    <a href="{{ $row->tautan_materi }}" target="_blank" rel="noopener" class="font-medium text-brand-600 underline hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">Buka Modul</a>
                                </p>
                            @endif
                            @if($row->file_dokumentasi)
                                <p class="mt-1">
                                    <a href="{{ $row->dokumentasi_url }}" target="_blank" rel="noopener" class="font-medium text-emerald-700 underline hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-emerald-300">Lihat Bukti</a>
                                </p>
                            @else
                                <p class="mt-1">
                                    <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Tanpa Bukti</span>
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                            @if($row->catatan)
                                <p><span class="font-medium text-gray-500 dark:text-gray-400">Ringkasan:</span> {{ $row->catatan }}</p>
                            @endif
                            @if($row->hasil_pembelajaran)
                                <p class="mt-0.5"><span class="font-medium text-gray-500 dark:text-gray-400">Hasil:</span> {{ $row->hasil_pembelajaran }}</p>
                            @endif
                            @if($row->kendala_kegiatan)
                                <p class="mt-0.5"><span class="font-medium text-gray-500 dark:text-gray-400">Kendala:</span> {{ $row->kendala_kegiatan }}</p>
                            @endif
                            @if(!$row->catatan && !$row->kendala_kegiatan && !$row->hasil_pembelajaran)
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 space-y-1 text-xs">
                            <span class="inline-flex rounded-md px-2.5 py-0.5 font-semibold {{ $kelasBadgeCls }}">
                                {{ $kelasInfo['label'] }}
                            </span>
                            @if($row->catatan_supervisor)
                                <p class="text-xs text-gray-700 dark:text-gray-200">Catatan: {{ $row->catatan_supervisor }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 min-w-[290px]">
                            <div class="flex flex-col gap-2">
                                @if($row->status_verifikasi !== 'diverifikasi')
                                    <form method="POST" action="{{ route('supervisor.kehadiran.verifikasi', $row) }}">
                                        @csrf
                                        <input type="hidden" name="status_verifikasi" value="diverifikasi" />
                                        <input type="hidden" name="catatan_supervisor" value="{{ $row->catatan_supervisor }}" />
                                        <button type="submit" class="inline-flex min-h-[36px] w-full items-center justify-center rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                            Verifikasi
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('supervisor.kehadiran.verifikasi', $row) }}" class="flex flex-col gap-2">
                                    @csrf
                                    <div class="flex gap-2">
                                        <select name="status_verifikasi" class="min-h-[36px] flex-1 rounded-lg border border-gray-300 bg-transparent px-2.5 py-1 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            <option value="diverifikasi" {{ $row->status_verifikasi === 'diverifikasi' ? 'selected' : '' }}>Diverifikasi</option>
                                            <option value="perlu_revisi" {{ $row->status_verifikasi === 'perlu_revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                                            <option value="menunggu" {{ ($row->status_verifikasi ?? 'menunggu') === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                                        </select>
                                        <button type="submit" class="inline-flex min-h-[36px] items-center justify-center rounded-lg bg-brand-600 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-700">
                                            Simpan
                                        </button>
                                    </div>
                                    <input type="text" name="catatan_supervisor" value="{{ $row->catatan_supervisor }}" placeholder="Catatan verifikasi Supervisor (opsional)..."
                                        class="min-h-[34px] w-full rounded-lg border border-gray-200 bg-transparent px-2.5 py-1 text-xs text-gray-800 dark:border-gray-700 dark:text-gray-200" />
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada konfirmasi aktivitas mengajar pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Tanggal</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Instruktur</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Jam Jadwal</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Check-in / Check-out</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Keterlambatan</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">GPS &amp; Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($absensis as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->tanggal->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            @if($row->instruktur)
                                <a href="{{ route('supervisor.instruktur.show', $row->instruktur) }}" class="hover:text-brand-600 dark:hover:text-brand-400">
                                    {{ $row->instruktur->nama_lengkap }}
                                </a>
                            @else
                                -
                            @endif
                            @if($row->jadwal)
                                <span class="block text-xs text-gray-500">{{ $row->jadwal->nama_kelas }} ({{ $row->jadwal->materi }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                            {{ $row->jam_mulai_jadwal ? substr($row->jam_mulai_jadwal, 0, 5) : ($row->jadwal ? substr($row->jadwal->jam_mulai, 0, 5) : '-') }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                            {{ $row->jam_checkin ? substr($row->jam_checkin, 0, 5) : '-' }} / {{ $row->jam_checkout ? substr($row->jam_checkout, 0, 5) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                            {{ $row->menit_terlambat !== null ? $row->menit_terlambat . ' menit' : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $row->status === 'hadir' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300' : ($row->status === 'terlambat' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : ($row->status === 'izin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300')) }}">
                                {{ $row->status_label }}
                            </span>
                        </td>
                        @php
                            $sumberLabels = [
                                'checkin_app'  => 'Aplikasi',
                                'manual_admin' => 'Manual',
                                'manual'       => 'Manual',
                                'import'       => 'Import',
                                'import_excel' => 'Import',
                            ];
                            $sumberLabel   = $sumberLabels[$row->sumber] ?? $row->sumber;
                            $lokasiInfo    = $row->lokasi_checkin_info;
                            $isNonAppInput = in_array($row->sumber, ['manual', 'manual_admin', 'import', 'import_excel'], true);
                        @endphp
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                            @if($lokasiInfo)
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($lokasiInfo['dalam_radius'])
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            Sesuai Lokasi (&plusmn; {{ $lokasiInfo['jarak_ringkas'] }})
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-950/60 dark:text-red-300">
                                            Di Luar Radius (&plusmn; {{ $lokasiInfo['jarak_ringkas'] }})
                                        </span>
                                    @endif
                                    <span class="text-gray-500 dark:text-gray-400">&middot; {{ $sumberLabel }}</span>
                                </div>
                                <a href="https://www.google.com/maps?q={{ $row->latitude_checkin }},{{ $row->longitude_checkin }}"
                                   target="_blank" rel="noopener"
                                   title="{{ $row->latitude_checkin }}, {{ $row->longitude_checkin }}"
                                   class="mt-1 inline-block font-medium text-brand-700 hover:underline dark:text-brand-300">
                                    Lihat Peta
                                </a>
                            @elseif($isNonAppInput)
                                <p class="font-medium text-gray-700 dark:text-gray-300">Tanpa GPS &middot; {{ $sumberLabel }}</p>
                            @else
                                <p class="font-medium text-amber-700 dark:text-amber-300">GPS Gagal &middot; {{ $sumberLabel }}</p>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada catatan kehadiran pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    @php
        $paginator = ($tab ?? 'absensi') === 'aktivitas' ? $aktivitas : $absensis;
    @endphp
    @if(is_object($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $paginator->links() }}</div>
    @endif
</div>
@endsection
