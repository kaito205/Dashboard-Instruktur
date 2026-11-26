<?php

namespace App\Http\Requests\Admin;

use App\Models\Instruktur;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreInstrukturRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return false;
        }

        $user       = auth()->user();
        $routeParam = $this->route('instruktur');

        if ($routeParam !== null) {
            $instruktur = $routeParam instanceof Instruktur
                ? $routeParam
                : Instruktur::withTrashed()->find($routeParam);

            return $instruktur !== null && $user->can('update', $instruktur);
        }

        return $user->can('create', Instruktur::class);
    }

    protected function prepareForValidation(): void
    {
        $isUpdate = $this->route('instruktur') !== null;

        if ($this->has('kode_instruktur') && is_string($this->kode_instruktur)) {
            $this->merge([
                'kode_instruktur' => strtoupper(trim($this->kode_instruktur)),
            ]);
        }

        if ($this->input('status_ketersediaan', 'tersedia') === 'tersedia') {
            $this->merge([
                'cuti_mulai'      => null,
                'cuti_selesai'    => null,
                'keterangan_cuti' => null,
            ]);
        }

        if (! $isUpdate) {
            $akunMode = $this->filled('akun_mode') ? (string) $this->input('akun_mode') : 'auto';
            $payload  = ['akun_mode' => $akunMode];

            if ($akunMode !== 'existing') {
                $payload['user_id'] = null;
            }

            $this->merge($payload);
        }
    }

    public function rules(): array
    {
        $routeParam   = $this->route('instruktur');
        $instrukturId = $routeParam instanceof Instruktur ? $routeParam->id : $routeParam;
        $isUpdate     = $instrukturId !== null;
        $akunMode     = (string) $this->input('akun_mode', 'auto');
        $isCutiOrIzin = in_array((string) $this->input('status_ketersediaan'), ['cuti', 'izin'], true);

        $cutiSelesaiRules = ['nullable', 'date'];
        if ($this->filled('cuti_mulai')) {
            $cutiSelesaiRules[] = 'after_or_equal:cuti_mulai';
        }

        return [
            'nama_lengkap'               => ['required', 'string', 'max:100'],
            'kode_instruktur'            => [
                'required',
                'string',
                'max:20',
                Rule::unique('instrukturs', 'kode_instruktur')->ignore($instrukturId),
                'regex:/^[A-Z0-9\-]+$/',
            ],
            'email'                      => ['nullable', 'email', 'max:100'],
            'no_telepon'                 => ['nullable', 'string', 'max:20'],
            'alamat'                     => ['nullable', 'string', 'max:500'],
            'bidang_keahlian'            => ['nullable', 'string', 'max:100'],
            'riwayat_keahlian'           => ['nullable', 'string', 'max:2000'],
            'nama_sertifikasi'           => ['nullable', 'string', 'max:150'],
            'file_sertifikasi'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'sertifikasi_berlaku_sampai' => ['nullable', 'date'],
            'status'                     => ['required', 'in:aktif,nonaktif'],
            'status_ketersediaan'        => ['nullable', 'in:tersedia,cuti,izin'],
            'cuti_mulai'                 => [$isCutiOrIzin ? 'required' : 'nullable', 'date'],
            'cuti_selesai'               => $cutiSelesaiRules,
            'keterangan_cuti'            => ['nullable', 'string', 'max:255'],
            'keterangan'                 => ['nullable', 'string', 'max:500'],
            'foto_profil'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'akun_mode'                  => ['nullable', 'in:auto,existing,none'],
            'password_awal'              => ! $isUpdate && $akunMode === 'auto'
                ? ['required', Password::min(8)->letters()->mixedCase()->numbers()]
                : ['nullable'],
            'user_id'                    => [
                (! $isUpdate && $akunMode === 'existing') ? 'required' : 'nullable',
                Rule::exists('users', 'id')->where('role', 'instruktur'),
                Rule::unique('instrukturs', 'user_id')->ignore($instrukturId),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $routeParam = $this->route('instruktur');
            $isUpdate   = $routeParam !== null;

            if (! $isUpdate && $this->input('akun_mode') === 'auto') {
                $emailInput  = trim((string) $this->input('email'));
                $kodeInput   = strtolower(trim((string) $this->input('kode_instruktur')));
                $targetEmail = $emailInput !== ''
                    ? $emailInput
                    : ($kodeInput !== '' ? $kodeInput . '@lpkcti.id' : '');

                if ($targetEmail !== '') {
                    $existing = User::where('email', $targetEmail)->first();
                    if ($existing) {
                        if ($existing->role !== 'instruktur') {
                            $validator->errors()->add(
                                'email',
                                "Email {$targetEmail} sudah digunakan akun {$existing->role_label} dan tidak dapat ditautkan ke profil instruktur."
                            );
                        } elseif (Instruktur::withTrashed()->where('user_id', $existing->id)->exists()) {
                            $validator->errors()->add(
                                'email',
                                "Email {$targetEmail} sudah digunakan oleh instruktur lain."
                            );
                        } else {
                            $validator->errors()->add(
                                'email',
                                "Akun instruktur dengan email {$targetEmail} sudah terdaftar. Pilih opsi Tautkan akun instruktur yang sudah ada."
                            );
                        }
                    }
                }
            }

            if ($isUpdate) {
                $existingInstruktur = $routeParam instanceof Instruktur
                    ? $routeParam
                    : Instruktur::withTrashed()->find($routeParam);

                if ($existingInstruktur) {
                    $linkedUserId = $this->has('user_id')
                        ? ($this->filled('user_id') ? (int) $this->input('user_id') : null)
                        : $existingInstruktur->user_id;

                    if ($linkedUserId && $this->filled('email')) {
                        $emailTaken = User::where('email', trim((string) $this->input('email')))
                            ->where('id', '!=', $linkedUserId)
                            ->exists();

                        if ($emailTaken) {
                            $validator->errors()->add('email', 'Email sudah digunakan oleh akun pengguna lain.');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'       => 'Nama lengkap wajib diisi.',
            'kode_instruktur.required'    => 'Kode instruktur wajib diisi.',
            'kode_instruktur.unique'      => 'Kode instruktur sudah digunakan.',
            'kode_instruktur.regex'       => 'Kode instruktur hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'email.email'                 => 'Format email tidak valid.',
            'status.required'             => 'Status wajib dipilih.',
            'cuti_mulai.required'         => 'Tanggal mulai cuti/izin wajib diisi saat status ketersediaan Cuti atau Izin.',
            'cuti_selesai.after_or_equal' => 'Tanggal selesai cuti/izin harus sama atau setelah tanggal mulai.',
            'file_sertifikasi.mimes'      => 'Dokumen sertifikasi harus berformat PDF, JPG, PNG, atau WebP.',
            'file_sertifikasi.max'        => 'Ukuran dokumen sertifikasi maksimal 5MB.',
            'foto_profil.image'           => 'Berkas foto harus berupa gambar.',
            'foto_profil.max'             => 'Ukuran foto maksimal 2MB.',
            'akun_mode.in'                => 'Pilihan mode akun login tidak valid.',
            'password_awal.required'      => 'Password awal akun login wajib diisi saat membuat akun otomatis.',
            'user_id.required'            => 'Pilih akun instruktur yang ingin ditautkan.',
            'user_id.exists'              => 'Hanya akun dengan role Instruktur yang dapat ditautkan ke profil instruktur.',
            'user_id.unique'              => 'Akun pengguna ini sudah terhubung dengan profil instruktur lain.',
        ];
    }
}
