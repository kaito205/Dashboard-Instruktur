<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instruktur\Concerns\ResolvesOwnInstruktur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    use ResolvesOwnInstruktur;

    public function index()
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);

        if (Schema::hasTable('instruktur_sertifikasis')) {
            $instruktur->load(['sertifikasis' => fn ($q) => $q->orderBy('berlaku_sampai')]);
        }

        return view('pages.instruktur.profil', compact('instruktur'));
    }

    public function update(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);
        Gate::authorize('update', $instruktur);

        $user = auth()->user();

        $validated = $request->validate([
            'no_telepon'  => ['nullable', 'string', 'max:30'],
            'alamat'      => ['nullable', 'string', 'max:500'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'no_telepon.max'    => 'Nomor telepon maksimal 30 karakter.',
            'alamat.max'        => 'Alamat domisili maksimal 500 karakter.',
            'foto_profil.image' => 'Berkas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, PNG, atau WebP.',
            'foto_profil.max'   => 'Ukuran foto maksimal 2MB.',
        ]);

        DB::transaction(function () use ($instruktur, $user, $validated, $request) {
            $updatePayload = [
                'no_telepon' => $validated['no_telepon'] ?? null,
                'alamat'     => $validated['alamat'] ?? null,
            ];

            if ($request->hasFile('foto_profil')) {
                if ($instruktur->foto_profil && Storage::disk('public')->exists($instruktur->foto_profil)) {
                    Storage::disk('public')->delete($instruktur->foto_profil);
                }
                $newPhoto                     = $request->file('foto_profil')->store('instruktur/foto', 'public');
                $updatePayload['foto_profil'] = $newPhoto;
                $user->update(['foto_profil' => $newPhoto]);
            }

            $instruktur->update($updatePayload);
        });

        return back()->with('success', 'Profil instruktur Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $instruktur = $this->resolveOwnInstruktur();
        abort_unless($instruktur !== null, 403);
        Gate::authorize('update', $instruktur);

        $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password_lama.required'  => 'Password saat ini wajib diisi.',
            'password_baru.required'  => 'Password baru wajib diisi.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();
        if (!Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Password saat ini tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password_baru)]);

        return back()->with('success', 'Password akun instruktur Anda berhasil diperbarui.');
    }
}
