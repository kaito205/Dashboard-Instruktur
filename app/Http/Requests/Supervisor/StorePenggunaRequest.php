<?php

namespace App\Http\Requests\Supervisor;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StorePenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('supervisor', 'admin');
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
                'in:instruktur',
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
            'role.in'           => 'Supervisor hanya memiliki kewenangan untuk mengelola akun dengan role Instruktur.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
        ];
    }
}
