<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function index()
    {
        return view('pages.supervisor.profil.index');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah digunakan oleh akun lain.',
            'foto_profil.image' => 'Berkas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, PNG, atau WebP.',
            'foto_profil.max'   => 'Ukuran foto maksimal 2MB.',
        ]);

        $oldPhoto = $user->foto_profil;
        $newPhoto = null;

        if ($request->hasFile('foto_profil')) {
            $newPhoto                   = $request->file('foto_profil')->store('users/foto', 'public');
            $validated['foto_profil'] = $newPhoto;
        } else {
            unset($validated['foto_profil']);
        }

        $user->update($validated);

        if ($newPhoto && $oldPhoto && $oldPhoto !== $newPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return back()->with('success', 'Profil Supervisor berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.min'                      => 'Password baru minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, serta angka.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        $user->revokeOtherSessions(
            $request->hasSession() ? $request->session()->getId() : null,
            $request->password
        );

        return back()->with('success', 'Password akun berhasil diperbarui.');
    }
}
