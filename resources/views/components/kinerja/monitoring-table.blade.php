@props([
    'dataKinerja',
    'dari',
    'sampai',
    'dariLalu' => null,
    'sampaiLalu' => null,
    'routePrefix' => 'admin',
])

@php
    $formatBobot = static function ($value): string {
        $formatted = number_format((float) ($value ?? 0), 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') . '%';
    };

    $badgeColors = [
        'sangat_baik' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
        'baik' => 'bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300',
        'cukup' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
        'kurang' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
    ];
@endphp

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
        <div>
            Periode Aktif: <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($dari)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($sampai)->format('d/m/Y') }}</span>
            @if(!empty($dariLalu) && !empty($sampaiLalu))
                <span class="mx-1.5 text-gray-400" aria-hidden="true">|</span>
                Pembanding: <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($dariLalu)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($sampaiLalu)->format('d/m/Y') }}</span>
            @endif
        </div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Nilai komponen &amp; skor akhir berskala 0-100.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                    <th class="px-4 py-3.5">Instruktur</th>
                    <th class="px-4 py-3.5">Kehadiran</th>
                    <th class="px-4 py-3.5">Aktivitas</th>
                    <th class="px-4 py-3.5">Evaluasi</th>
                    <th class="px-4 py-3.5">Observasi</th>
                    <th class="px-4 py-3.5">Skor Akhir</th>
                    <th class="px-4 py-3.5">Kategori</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($dataKinerja as $item)
                @php
                    // Kehadiran
                    $hadirTepat = (int) ($item['hadir_tepat'] ?? 0);
                    $terlambat = (int) ($item['terlambat'] ?? 0);
                    $izin = (int) ($item['izin'] ?? 0);
                    $alfa = (int) ($item['tidak_hadir'] ?? 0);
                    $totalAbsensi = (int) ($item['total_absensi'] ?? 0);

                    $kehadiranParts = [];
                    if ($hadirTepat > 0) $kehadiranParts[] = "{$hadirTepat} Hadir";
                    if ($terlambat > 0) $kehadiranParts[] = "{$terlambat} Terlambat";
                    if ($izin > 0) $kehadiranParts[] = "{$izin} Izin";
                    if ($alfa > 0) $kehadiranParts[] = "{$alfa} Tidak Hadir";

                    $absensiText = ($totalAbsensi > 0 && !empty($kehadiranParts))
                        ? implode(', ', $kehadiranParts)
                        : 'Belum ada catatan presensi';
                    $absensiTitle = $totalAbsensi > 0
                        ? "Total {$totalAbsensi} sesi: {$hadirTepat} Hadir, {$terlambat} Terlambat, {$izin} Izin, {$alfa} Tidak Hadir"
                        : null;

                    // Aktivitas
                    $dikonfirmasi = (int) ($item['total_aktivitas'] ?? 0);
                    $diverifikasi = (int) ($item['total_aktivitas_diverifikasi'] ?? 0);
                    $basisSesi = (int) ($item['basis_sesi_aktivitas'] ?? $item['total_jadwal'] ?? 0);

                    if ($basisSesi === 0) {
                        $aktivitasText = 'Belum ada sesi aktivitas';
                        $aktivitasTitle = null;
                    } elseif ($dikonfirmasi === 0) {
                        $aktivitasText = "0 laporan dikonfirmasi ({$basisSesi} sesi)";
                        $aktivitasTitle = "Dikonfirmasi: 0 laporan, Terverifikasi: {$diverifikasi} laporan, Basis: {$basisSesi} sesi";
                    } else {
                        $aktivitasText = "{$diverifikasi}/{$dikonfirmasi} laporan terverifikasi ({$basisSesi} sesi)";
                        $aktivitasTitle = "Dikonfirmasi: {$dikonfirmasi} laporan, Terverifikasi: {$diverifikasi} laporan, Basis: {$basisSesi} sesi";
                    }

                    // Evaluasi
                    $totalEvaluasi = (int) ($item['total_evaluasi'] ?? 0);
                    $skalaEval = $item['rata_rata_evaluasi_skala5'] ?? null;
                    $evaluasiText = ($totalEvaluasi > 0 && $skalaEval !== null)
                        ? 'Rata-rata ' . number_format((float) $skalaEval, 2) . " / 5 ({$totalEvaluasi} evaluasi)"
                        : 'Belum ada evaluasi';

                    // Observasi
                    $totalObservasi = (int) ($item['total_observasi'] ?? 0);
                    $skalaObs = $item['rata_rata_observasi_skala5'] ?? null;
                    $observasiText = ($totalObservasi > 0 && $skalaObs !== null)
                        ? 'Rata-rata ' . number_format((float) $skalaObs, 2) . " / 5 ({$totalObservasi} observasi)"
                        : 'Belum ada observasi';

                    // Bobot & redistribusi
                    $isRedistribusi = !empty($item['redistribusi_bobot']);
                    $bobotTitleFor = static function (string $key) use ($item, $isRedistribusi, $formatBobot): ?string {
                        $efektif = (float) ($item['bobot_efektif'][$key] ?? 0);
                        $standar = (float) ($item['bobot_awal'][$key] ?? 0);
                        if ($isRedistribusi && abs($efektif - $standar) > 0.01) {
                            return 'Bobot standar ' . $formatBobot($standar) . ' disesuaikan menjadi ' . $formatBobot($efektif) . ' karena ada komponen yang belum memiliki data';
                        }
                        return null;
                    };
                @endphp
                <tr class="align-top transition hover:bg-gray-50/70 dark:hover:bg-gray-800/30">
                    <td class="px-4 py-4">
                        <a href="{{ route($routePrefix . '.instruktur.show', $item['instruktur']) }}"
                           class="inline-block rounded font-semibold text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">
                            {{ $item['instruktur']->nama_lengkap }}
                        </a>
                        <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['instruktur']->kode_instruktur }}</p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ (int) $item['total_jadwal'] }} sesi ({{ number_format((float) $item['jam_mengajar'], 1, '.', '') }} jam)</p>
                    </td>

                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            @if($item['skor_kehadiran'] !== null)
                                <p class="font-mono text-base font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format((float) $item['skor_kehadiran'], 2) }}</p>
                            @else
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data</p>
                            @endif
                            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300" @if($absensiTitle) title="{{ $absensiTitle }}" @endif>
                                {{ $absensiText }}
                            </p>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400" @if($t = $bobotTitleFor('kehadiran')) title="{{ $t }}" @endif>
                                Bobot {{ $formatBobot($item['bobot_efektif']['kehadiran'] ?? 0) }}
                            </p>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            @if($item['skor_aktivitas'] !== null)
                                <p class="font-mono text-base font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format((float) $item['skor_aktivitas'], 2) }}</p>
                            @else
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data</p>
                            @endif
                            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300" @if($aktivitasTitle) title="{{ $aktivitasTitle }}" @endif>
                                {{ $aktivitasText }}
                            </p>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400" @if($t = $bobotTitleFor('aktivitas')) title="{{ $t }}" @endif>
                                Bobot {{ $formatBobot($item['bobot_efektif']['aktivitas'] ?? 0) }}
                            </p>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            @if($item['skor_evaluasi'] !== null)
                                <p class="font-mono text-base font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format((float) $item['skor_evaluasi'], 2) }}</p>
                            @else
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data</p>
                            @endif
                            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $evaluasiText }}
                            </p>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400" @if($t = $bobotTitleFor('evaluasi')) title="{{ $t }}" @endif>
                                Bobot {{ $formatBobot($item['bobot_efektif']['evaluasi'] ?? 0) }}
                            </p>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            @if($item['skor_observasi'] !== null)
                                <p class="font-mono text-base font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format((float) $item['skor_observasi'], 2) }}</p>
                            @else
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data</p>
                            @endif
                            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $observasiText }}
                            </p>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400" @if($t = $bobotTitleFor('observasi')) title="{{ $t }}" @endif>
                                Bobot {{ $formatBobot($item['bobot_efektif']['observasi'] ?? 0) }}
                            </p>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            @if($item['skor_akhir'] !== null)
                                <p class="font-mono text-lg font-bold tabular-nums text-gray-900 dark:text-white">
                                    {{ number_format((float) $item['skor_akhir'], 2) }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">-</p>
                            @endif

                            @if(isset($item['skor_periode_lalu']) && $item['skor_periode_lalu'] !== null)
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    Periode lalu {{ number_format((float) $item['skor_periode_lalu'], 2) }}
                                    @if($item['selisih_skor'] !== null)
                                        <span class="font-semibold {{ $item['selisih_skor'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                            ({{ $item['selisih_skor'] >= 0 ? '+' : '' }}{{ number_format((float) $item['selisih_skor'], 2) }})
                                        </span>
                                    @endif
                                </p>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400">Periode lalu: -</p>
                            @endif

                            @if($isRedistribusi)
                                <p class="text-[11px] font-medium text-amber-700 dark:text-amber-300" title="Bobot didistribusikan ke komponen yang memiliki data pada periode ini">
                                    Bobot proporsional
                                </p>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <span class="inline-flex whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-medium {{ $badgeColors[$item['kategori'] ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                            {{ $item['kategori_label'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-600 dark:text-gray-300">Tidak ada data kinerja instruktur yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
