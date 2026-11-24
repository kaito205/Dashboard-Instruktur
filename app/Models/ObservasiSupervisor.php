<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservasiSupervisor extends Model
{
    protected $fillable = [
        'instruktur_id',
        'supervisor_id',
        'jadwal_id',
        'tanggal',
        'indikator_penilaian',
        'skor_observasi',
        'catatan_observasi',
        'rekomendasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'             => 'date',
            'indikator_penilaian' => 'array',
            'skor_observasi'      => 'float',
        ];
    }

    /**
     * Daftar 5 indikator observasi supervisor (skala 1-5) yang dapat dikonfigurasi melalui tabel settings.
     * Catatan: Label indikator dapat disesuaikan oleh Admin pada menu Pengaturan jika LPK memperbarui instrumen observasi.
     */
    public static function getIndikatorLabels(): array
    {
        $default = [
            'indikator_1' => 'Kesiapan Rencana & Materi Mengajar',
            'indikator_2' => 'Penguasaan Materi & Ketepatan Konsep',
            'indikator_3' => 'Metode Penyampaian & Manajemen Kelas',
            'indikator_4' => 'Keterlibatan Peserta & Pendampingan Praktik',
            'indikator_5' => 'Kedisiplinan Waktu & Sikap Profesional',
        ];

        $raw = Setting::get('indikator_observasi_supervisor');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                for ($i = 1; $i <= 5; $i++) {
                    $key = "indikator_{$i}";
                    if (isset($decoded[$key]) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                        $default[$key] = trim($decoded[$key]);
                    }
                }
            }
        }

        return $default;
    }

    /**
     * Konversi nilai mentah indikator skala 1-5 menjadi skala 0-100.
     * Rumus: (Rata-rata nilai skala 1-5 / 5) * 100 = Rata-rata * 20.
     * Nilai mentah skala 1-5 tetap disimpan utuh pada kolom JSON indikator_penilaian.
     */
    public static function hitungSkorSkala100(array $skorIndikator): float
    {
        $values = array_values(array_filter($skorIndikator, fn ($v) => is_numeric($v)));
        if (count($values) === 0) {
            return 0.0;
        }

        $avgSkala5 = array_sum(array_map('floatval', $values)) / count($values);
        return round(($avgSkala5 / 5.0) * 100.0, 2);
    }

    public function getSkorIndikator(int $nomor, int $default = 4): int
    {
        $indikator = $this->indikator_penilaian;
        if (!is_array($indikator)) {
            return $default;
        }

        $skorMap = $indikator['skor'] ?? $indikator;
        $key     = "indikator_{$nomor}";

        return (isset($skorMap[$key]) && is_numeric($skorMap[$key]))
            ? (int) $skorMap[$key]
            : $default;
    }

    public function getRataRataSkala5Attribute(): ?float
    {
        $indikator = $this->indikator_penilaian;
        if (!is_array($indikator)) {
            return null;
        }

        $skorMap = $indikator['skor'] ?? $indikator;
        $nums = [];
        foreach (['indikator_1', 'indikator_2', 'indikator_3', 'indikator_4', 'indikator_5'] as $key) {
            if (isset($skorMap[$key]) && is_numeric($skorMap[$key])) {
                $nums[] = (float) $skorMap[$key];
            }
        }

        if (count($nums) === 0) {
            return null;
        }

        return round(array_sum($nums) / count($nums), 2);
    }

    public function getSkorLabelAttribute(): string
    {
        if ($this->skor_observasi === null) {
            return 'Belum Dinilai';
        }

        $sangatBaik = (float) Setting::get('batas_sangat_baik', '85');
        $baik       = (float) Setting::get('batas_baik', '75');
        $cukup      = (float) Setting::get('batas_cukup', '60');

        return match (true) {
            $this->skor_observasi >= $sangatBaik => 'Sangat Baik',
            $this->skor_observasi >= $baik       => 'Baik',
            $this->skor_observasi >= $cukup      => 'Cukup',
            default                              => 'Kurang',
        };
    }

    public function getSkorColorAttribute(): string
    {
        if ($this->skor_observasi === null) {
            return 'gray';
        }

        $sangatBaik = (float) Setting::get('batas_sangat_baik', '85');
        $baik       = (float) Setting::get('batas_baik', '75');
        $cukup      = (float) Setting::get('batas_cukup', '60');

        return match (true) {
            $this->skor_observasi >= $sangatBaik => 'emerald',
            $this->skor_observasi >= $baik       => 'blue',
            $this->skor_observasi >= $cukup      => 'amber',
            default                              => 'red',
        };
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }
}
