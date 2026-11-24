<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluasi extends Model
{
    protected $fillable = [
        'instruktur_id',
        'kode_evaluasi_id',
        'jadwal_id',
        'nama_pengisi',
        'materi_evaluasi',
        'jawaban',
        'total_nilai',
        'komentar',
        'batch_import',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'jawaban'     => 'array',
            'total_nilai' => 'float',
        ];
    }

    /**
     * Struktur 5 indikator penilaian evaluasi peserta (skala 1-5) yang dapat dikonfigurasi.
     * Catatan Penting: Label indikator di bawah ini disimpan pada tabel settings (key: indikator_evaluasi_peserta)
     * sehingga dapat diubah sewaktu-waktu oleh Admin tanpa mengubah struktur database atau menimpa jawaban mentah.
     */
    public static function getIndikatorLabels(): array
    {
        $default = [
            'indikator_1' => 'Penguasaan Materi Pelatihan',
            'indikator_2' => 'Kejelasan Penyampaian & Komunikasi',
            'indikator_3' => 'Ketepatan Waktu & Kedisiplinan',
            'indikator_4' => 'Interaksi & Pendampingan Praktik',
            'indikator_5' => 'Sikap Profesional & Keteladanan',
        ];

        $raw = Setting::get('indikator_evaluasi_peserta');
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
     * Rumus konversi skala 1-5 (5 indikator) ke skala 0-100:
     * total_nilai = ((sum_5_indikator / 5) / 5) * 100 = rata_rata_skala_5 * 20.
     * Nilai asli skala 1-5 tetap disimpan utuh pada kolom JSON `jawaban`.
     */
    public static function hitungNilaiKonversi100(array $skorIndikatorSkala5): float
    {
        $valid = array_values(array_filter($skorIndikatorSkala5, fn ($v) => is_numeric($v)));
        if (count($valid) === 0) {
            return 0.0;
        }

        $avgSkala5 = array_sum(array_map('floatval', $valid)) / count($valid);
        return round(($avgSkala5 / 5.0) * 100.0, 2);
    }

    public function getRataRataSkala5Attribute(): ?float
    {
        $jawaban = $this->jawaban;
        if (!is_array($jawaban)) {
            return null;
        }

        $skorMap = $jawaban['skor_indikator'] ?? $jawaban;
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

    public function getKomentarPesertaAttribute(): ?string
    {
        if ($this->komentar !== null && trim($this->komentar) !== '') {
            return $this->komentar;
        }
        $jawaban = $this->jawaban;
        if (is_array($jawaban)) {
            return $jawaban['komentar'] ?? $jawaban['catatan_peserta'] ?? $jawaban['saran'] ?? null;
        }
        return null;
    }

    public function getNilaiLabelAttribute(): string
    {
        if ($this->total_nilai === null) {
            return 'Belum Dinilai';
        }

        $sangatBaik = (float) Setting::get('batas_sangat_baik', '85');
        $baik       = (float) Setting::get('batas_baik', '75');
        $cukup      = (float) Setting::get('batas_cukup', '60');

        return match (true) {
            $this->total_nilai >= $sangatBaik => 'Sangat Baik',
            $this->total_nilai >= $baik       => 'Baik',
            $this->total_nilai >= $cukup      => 'Cukup',
            default                           => 'Kurang',
        };
    }

    public function getNilaiColorAttribute(): string
    {
        if ($this->total_nilai === null) {
            return 'gray';
        }

        $sangatBaik = (float) Setting::get('batas_sangat_baik', '85');
        $baik       = (float) Setting::get('batas_baik', '75');
        $cukup      = (float) Setting::get('batas_cukup', '60');

        return match (true) {
            $this->total_nilai >= $sangatBaik => 'emerald',
            $this->total_nilai >= $baik       => 'blue',
            $this->total_nilai >= $cukup      => 'amber',
            default                           => 'red',
        };
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(Instruktur::class)->withTrashed();
    }

    public function kodeEvaluasi(): BelongsTo
    {
        return $this->belongsTo(KodeEvaluasi::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }
}
