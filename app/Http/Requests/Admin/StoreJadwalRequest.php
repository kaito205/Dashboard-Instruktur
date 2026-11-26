<?php

namespace App\Http\Requests\Admin;

use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    protected function prepareForValidation(): void
    {
        $isPola = $this->boolean('is_pola_mingguan');
        $tanggal = $this->input('tanggal');
        $hari = $this->input('hari');

        if (!$isPola && $tanggal) {
            try {
                $hari = Jadwal::hariDariTanggal($tanggal);
            } catch (\Throwable) {
                $hari = null;
            }
        }

        $kodeKelas = $this->filled('kode_kelas')
            ? strtoupper(trim((string) $this->input('kode_kelas')))
            : null;
        $namaKelas = $this->filled('nama_kelas')
            ? trim((string) $this->input('nama_kelas'))
            : null;
        $ruangan = $this->filled('ruangan')
            ? trim((string) $this->input('ruangan'))
            : null;
        $jumlahPeserta = $this->input('jumlah_peserta');

        if ($this->filled('kelas_id') && Schema::hasTable('kelas')) {
            $kelas = Kelas::withTrashed()->find((int) $this->input('kelas_id'));
            if ($kelas) {
                $kodeKelas = $kodeKelas ?: $kelas->kode_kelas;
                $namaKelas = $namaKelas ?: $kelas->nama_kelas;
                $ruangan   = $ruangan ?: $kelas->ruangan_default;
                if ($jumlahPeserta === null || $jumlahPeserta === '') {
                    $jumlahPeserta = $kelas->kuota_peserta;
                }
            }
        }

        if ($jumlahPeserta === null || $jumlahPeserta === '') {
            $jumlahPeserta = 0;
        }

        $merged = [
            'is_pola_mingguan' => $isPola,
            'hari'             => $hari,
            'kode_kelas'       => $kodeKelas,
            'nama_kelas'       => $namaKelas,
            'ruangan'          => $ruangan,
            'jumlah_peserta'   => $jumlahPeserta,
        ];

        if (! $isPola) {
            $merged['periode_mulai']   = null;
            $merged['periode_selesai'] = null;
        }

        $this->merge($merged);
    }

    public function rules(): array
    {
        $isPola      = $this->boolean('is_pola_mingguan');
        $routeJadwal = $this->route('jadwal');
        $jadwalModel = $routeJadwal instanceof Jadwal ? $routeJadwal : ($routeJadwal ? Jadwal::find($routeJadwal) : null);
        $currentKelasId = $jadwalModel?->kelas_id;

        $periodeSelesaiRules = ['nullable', 'date'];
        if ($this->filled('periode_mulai')) {
            $periodeSelesaiRules[] = 'after_or_equal:periode_mulai';
        }

        return [
            'instruktur_id'    => ['required', Rule::exists('instrukturs', 'id')->whereNull('deleted_at')],
            'kelas_id'         => [
                'nullable',
                'integer',
                Rule::exists('kelas', 'id')->where(function ($q) use ($currentKelasId) {
                    $q->whereNull('deleted_at');
                    if ($currentKelasId) {
                        $q->orWhere('id', $currentKelasId);
                    }
                }),
            ],
            'kode_kelas'       => ['nullable', 'string', 'max:50'],
            'nama_kelas'       => ['required', 'string', 'max:150'],
            'materi'           => ['nullable', 'string', 'max:255'],
            'is_pola_mingguan' => ['nullable', 'boolean'],
            'hari'             => [$isPola ? 'required' : 'nullable', Rule::in(Jadwal::HARI_LIST)],
            'tanggal'          => [$isPola ? 'nullable' : 'required', 'date'],
            'periode_mulai'    => ['nullable', 'date'],
            'periode_selesai'  => $periodeSelesaiRules,
            'jam_mulai'        => ['required', 'date_format:H:i'],
            'jam_selesai'      => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruangan'          => ['nullable', 'string', 'max:100'],
            'jumlah_peserta'   => ['nullable', 'integer', 'min:0', 'max:1000'],
            'status'           => ['required', 'in:terjadwal,berlangsung,selesai,dibatalkan'],
            'keterangan'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'instruktur_id.required'          => 'Instruktur wajib dipilih.',
            'instruktur_id.exists'            => 'Instruktur tidak ditemukan.',
            'kelas_id.exists'                 => 'Master data kelas yang dipilih tidak ditemukan.',
            'nama_kelas.required'             => 'Nama kelas wajib diisi.',
            'hari.required'                   => 'Hari dalam minggu wajib dipilih untuk jadwal berulang.',
            'tanggal.required'                => 'Tanggal wajib diisi untuk jadwal sesi tanggal tertentu.',
            'periode_selesai.after_or_equal'  => 'Tanggal berakhir periode harus setelah atau sama dengan tanggal mulai periode.',
            'jam_mulai.required'              => 'Jam mulai wajib diisi.',
            'jam_selesai.required'            => 'Jam selesai wajib diisi.',
            'jam_selesai.after'               => 'Jam selesai harus setelah jam mulai.',
            'status.required'                 => 'Status wajib dipilih.',
        ];
    }
}
