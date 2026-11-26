<?php

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    protected function prepareForValidation(): void
    {
        $trimOrNull = static function (mixed $val): ?string {
            $clean = trim((string) ($val ?? ''));

            return $clean !== '' ? $clean : null;
        };

        $kode = strtoupper(trim((string) $this->input('kode_kelas', '')));
        $kuotaRaw = $this->input('kuota_peserta');
        $kuota = ($kuotaRaw === null || $kuotaRaw === '') ? 20 : $kuotaRaw;

        $this->merge([
            'kode_kelas'        => $kode,
            'nama_kelas'        => trim((string) $this->input('nama_kelas', '')),
            'program_pelatihan' => $trimOrNull($this->input('program_pelatihan')),
            'kuota_peserta'     => $kuota,
            'ruangan_default'   => $trimOrNull($this->input('ruangan_default')),
            'keterangan'        => $trimOrNull($this->input('keterangan')),
        ]);
    }

    public function rules(): array
    {
        $routeKelas = $this->route('kelas');
        $ignoreId = $routeKelas instanceof Kelas ? $routeKelas->id : $routeKelas;

        $periodeSelesaiRules = ['nullable', 'date'];
        if ($this->filled('periode_mulai')) {
            $periodeSelesaiRules[] = 'after_or_equal:periode_mulai';
        }

        return [
            'kode_kelas' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('kelas', 'kode_kelas')->ignore($ignoreId),
            ],
            'nama_kelas'        => ['required', 'string', 'max:150'],
            'program_pelatihan' => ['nullable', 'string', 'max:150'],
            'kuota_peserta'     => ['required', 'integer', 'min:1', 'max:1000'],
            'ruangan_default'   => ['nullable', 'string', 'max:100'],
            'periode_mulai'     => ['nullable', 'date'],
            'periode_selesai'   => $periodeSelesaiRules,
            'status'            => ['required', Rule::in(array_keys(Kelas::STATUS_LIST))],
            'keterangan'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_kelas.required'            => 'Kode kelas wajib diisi.',
            'kode_kelas.max'                 => 'Kode kelas maksimal 50 karakter.',
            'kode_kelas.regex'               => 'Kode kelas hanya boleh diawali huruf/angka dan berisi huruf kapital, angka, titik, garis bawah, atau tanda hubung.',
            'kode_kelas.unique'              => 'Kode kelas tersebut sudah terdaftar pada master data kelas.',
            'nama_kelas.required'            => 'Nama kelas wajib diisi.',
            'nama_kelas.max'                 => 'Nama kelas maksimal 150 karakter.',
            'program_pelatihan.max'          => 'Program pelatihan maksimal 150 karakter.',
            'kuota_peserta.required'         => 'Kuota peserta wajib diisi.',
            'kuota_peserta.integer'          => 'Kuota peserta harus berupa bilangan bulat.',
            'kuota_peserta.min'              => 'Kuota peserta minimal 1 peserta.',
            'kuota_peserta.max'              => 'Kuota peserta maksimal 1000 peserta.',
            'ruangan_default.max'            => 'Ruangan default maksimal 100 karakter.',
            'periode_mulai.date'             => 'Format tanggal periode mulai tidak valid.',
            'periode_selesai.date'           => 'Format tanggal periode selesai tidak valid.',
            'periode_selesai.after_or_equal' => 'Tanggal selesai periode harus sama dengan atau setelah tanggal mulai.',
            'status.required'                => 'Status kelas wajib dipilih.',
            'status.in'                      => 'Status kelas tidak valid.',
            'keterangan.max'                 => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
