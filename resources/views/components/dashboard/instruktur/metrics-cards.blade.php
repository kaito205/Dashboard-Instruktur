@props(['metrics'])

<div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-gray-800 dark:bg-gray-800">
    <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Jam Mengajar</p>
            <p class="mt-1.5 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $metrics['total_jam_mengajar'] ?? 0 }} Jam
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $metrics['total_jam_mengajar_trend'] ?? '0 jadwal aktif' }}
            </p>
        </div>

        <div class="bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kelas Diampu</p>
            <p class="mt-1.5 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $metrics['total_kelas_diampu'] ?? 0 }} Kelas
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $metrics['total_kelas_diampu_trend'] ?? '0 aktivitas dikonfirmasi' }}
            </p>
        </div>

        <div class="bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rata-Rata Evaluasi</p>
            <p class="mt-1.5 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $metrics['rating_penilaian'] ?? '-' }}
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $metrics['rating_penilaian_skala'] ?? '/ 100' }}</span>
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $metrics['rating_penilaian_trend'] ?? 'Belum ada evaluasi' }}
            </p>
        </div>

        <div class="bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tingkat Kehadiran</p>
            <p class="mt-1.5 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $metrics['kehadiran_personal'] ?? 0 }}%
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Hadir {{ $metrics['hadir_tepat'] ?? 0 }} &middot; Telat {{ $metrics['terlambat'] ?? 0 }} &middot; Izin {{ $metrics['izin'] ?? 0 }} &middot; Alpa {{ $metrics['tidak_hadir'] ?? 0 }}
            </p>
            @if (!empty($metrics['status_filter_aktif']))
                <p class="mt-1 text-xs font-medium text-brand-700 dark:text-brand-300">
                    Filter {{ $metrics['status_filter_label'] }}: {{ $metrics['status_filter_count'] }}/{{ $metrics['total_absensi'] ?? 0 }} sesi ({{ $metrics['status_filter_persentase'] }}%)
                </p>
            @endif
        </div>
    </div>
</div>
