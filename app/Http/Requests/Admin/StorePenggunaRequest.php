<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StorePenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $routeParam = $this->route('pengguna');
        $userId     = $routeParam instanceof User ? $routeParam->id : $routeParam;

        $rules = [
            'name'      => ['required', 'string', 'max:100'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role'      => [
                'required',
                'in:admin,supervisor,instruktur',
                function (string $attribute, mixed $value, \Closure $fail) use ($userId) {
                    if ($userId && (int) $userId === (int) auth()->id() && $value !== auth()->user()->role) {
                        $fail('Anda tidak dapat mengubah role akun Anda sendiri.');
                    }
                },
            ],
            'is_active'   => ['nullable', 'boolean'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if (!$userId) {
            $rules['password'] = ['required', Password::min(8)->letters()->mixedCase()->numbers()];
        } else {
            $rules['password'] = ['nullable', Password::min(8)->letters()->mixedCase()->numbers()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan oleh pengguna lain.',
            'role.required'     => 'Role wajib dipilih.',
            'role.in'           => 'Role tidak valid. Pilih admin, supervisor, atau instruktur.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
        ];
    }
}
