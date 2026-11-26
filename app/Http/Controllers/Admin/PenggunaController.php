<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePenggunaRequest;
use App\Models\Instruktur;
use App\Models\InstrukturAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) ($request->input('search') ?? ''));
        $role   = (string) ($request->input('role') ?? '');
        $status = (string) ($request->input('status') ?? '');

        $pengguna = User::with('instruktur')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role !== '', fn ($q) => $q->where('role', $role))
            ->when(in_array($status, ['0', '1'], true), fn ($q) => $q->where('is_active', $status === '1'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.pengguna.index', compact('pengguna', 'search', 'role', 'status'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        $nextInstrukturCode = Instruktur::generateNextCode();

        return view('pages.admin.pengguna.create', compact('nextInstrukturCode'));
    }

    public function store(StorePenggunaRequest $request)
    {
        Gate::authorize('create', User::class);

        $createdUser       = null;
        $createdInstruktur = null;

        DB::transaction(function () use ($request, &$createdUser, &$createdInstruktur) {
            $data              = $request->validated();
            $data['password']  = Hash::make($data['password']);
            $data['is_active'] = $request->boolean('is_active', true);

            if ($request->hasFile('foto_profil')) {
                $folder = ($data['role'] ?? '') === 'instruktur' ? 'instruktur/foto' : 'users/foto';
                $data['foto_profil'] = $request->file('foto_profil')->store($folder, 'public');
            } else {
                unset($data['foto_profil']);
            }

            $createdUser = User::create($data);
            if ($createdUser->role === 'instruktur') {
                $createdInstruktur = $createdUser->syncInstrukturProfile();
            }
        });

        if ($createdInstruktur) {
            $pesan = "Akun pengguna {$createdUser->name} berhasil ditambahkan beserta profil dasar instruktur ({$createdInstruktur->kode_instruktur}). Silakan lengkapi detail profilnya di menu Data Instruktur.";

            return redirect()->route('admin.pengguna.index')
                ->with('success', $pesan);
        }

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna)
    {
        Gate::authorize('update', $pengguna);

        $pengguna->load('instruktur');

        return view('pages.admin.pengguna.edit', compact('pengguna'));
    }

    public function update(StorePenggunaRequest $request, User $pengguna)
    {
        Gate::authorize('update', $pengguna);

        if ($request->filled('role') && $request->input('role') !== $pengguna->role && ! Gate::allows('changeRole', $pengguna)) {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role akun Anda sendiri.']);
        }

        $data = $request->validated();

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($pengguna->id === auth()->id()) {
            $data['role']      = $pengguna->role;
            $data['is_active'] = true;
        } else {
            $data['is_active'] = $request->boolean('is_active', false);
        }

        $previousRole     = $pengguna->role;
        $roleDetached     = false;
        $syncedInstruktur = null;
        $oldFotoToDelete  = null;
        $blockedError     = null;

        DB::transaction(function () use (
            $pengguna,
            $data,
            $request,
            $previousRole,
            $passwordChanged,
            &$roleDetached,
            &$syncedInstruktur,
            &$oldFotoToDelete,
            &$blockedError
        ) {
            if ($previousRole === 'admin' && (($data['role'] ?? $previousRole) !== 'admin' || empty($data['is_active']))) {
                $admins = User::byRole('admin')->lockForUpdate()->get(['id', 'is_active']);
                $remainingActiveAdmins = $admins->filter(
                    fn (User $u) => (int) $u->id !== (int) $pengguna->id && $u->is_active
                )->count();

                if ($remainingActiveAdmins < 1) {
                    $blockedError = 'Tidak dapat menonaktifkan atau mengubah role admin aktif terakhir.';

                    return;
                }
            }

            if ($request->hasFile('foto_profil')) {
                $folder   = ($data['role'] ?? $pengguna->role) === 'instruktur' ? 'instruktur/foto' : 'users/foto';
                $newPhoto = $request->file('foto_profil')->store($folder, 'public');
                if ($pengguna->foto_profil && $pengguna->foto_profil !== $newPhoto) {
                    $oldFotoToDelete = $pengguna->foto_profil;
                }
                $data['foto_profil'] = $newPhoto;
            } else {
                unset($data['foto_profil']);
            }

            $pengguna->update($data);
            $freshUser = $pengguna->fresh();

            if ($passwordChanged && (int) $freshUser->id !== (int) auth()->id()) {
                $freshUser->revokeActiveSessions();
            }

            if ($previousRole === 'instruktur' && $freshUser->role !== 'instruktur') {
                $linkedInstruktur = Instruktur::withTrashed()->where('user_id', $freshUser->id)->first();
                if ($linkedInstruktur) {
                    $statusLama = $linkedInstruktur->status;

                    $linkedInstruktur->updateQuietly([
                        'user_id' => null,
                        'status'  => 'nonaktif',
                    ]);

                    if (! $linkedInstruktur->trashed()) {
                        $linkedInstruktur->delete();
                    }

                    InstrukturAuditLog::create([
                        'instruktur_id'       => $linkedInstruktur->id,
                        'user_id'             => auth()->id(),
                        'aksi'                => 'status_diubah',
                        'ringkasan_perubahan' => "Role akun {$freshUser->name} diubah dari Instruktur menjadi {$freshUser->role_label}. Tautan akun dilepas dan profil instruktur diarsipkan untuk menjaga riwayat.",
                        'data_lama'           => ['user_id' => $freshUser->id, 'status' => $statusLama],
                        'data_baru'           => ['user_id' => null, 'status' => 'nonaktif'],
                    ]);

                    $roleDetached = true;
                }
            } elseif (
                $freshUser->role === 'instruktur'
                && (
                    $previousRole !== 'instruktur'
                    || $freshUser->is_active
                    || Instruktur::withTrashed()->where('user_id', $freshUser->id)->exists()
                )
            ) {
                $syncedInstruktur = $freshUser->syncInstrukturProfile();
            }
        });

        if ($blockedError !== null) {
            return back()->withInput()->with('error', $blockedError);
        }

        if ($oldFotoToDelete !== null) {
            $stillUsed = User::where('foto_profil', $oldFotoToDelete)->exists()
                || Instruktur::withTrashed()->where('foto_profil', $oldFotoToDelete)->exists();
            if (! $stillUsed) {
                Storage::disk('public')->delete($oldFotoToDelete);
            }
        }

        if ($roleDetached) {
            return redirect()->route('admin.pengguna.index')
                ->with('success', "Akun pengguna {$pengguna->name} diperbarui. Profil instruktur sebelumnya telah dilepas tautannya dan diarsipkan.");
        }

        if ($syncedInstruktur && $previousRole !== 'instruktur') {
            $pesan = "Akun pengguna {$pengguna->name} diperbarui dan profil dasar instruktur ({$syncedInstruktur->kode_instruktur}) berhasil disiapkan. Silakan lengkapi detail profilnya di menu Data Instruktur.";

            return redirect()->route('admin.pengguna.index')
                ->with('success', $pesan);
        }

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $pengguna)
    {
        if ($pengguna->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        Gate::authorize('delete', $pengguna);

        if ($pengguna->isInstruktur()) {
            $linkedInstruktur = Instruktur::withTrashed()->where('user_id', $pengguna->id)->first();
            if ($linkedInstruktur && $linkedInstruktur->hasOperationalHistory()) {
                return back()->with(
                    'error',
                    "Akun instruktur {$pengguna->name} tidak dapat dihapus permanen karena memiliki riwayat jadwal, presensi, atau evaluasi. Gunakan tombol Nonaktifkan agar akses login dicabut tanpa menghapus riwayat kinerja."
                );
            }
        }

        $blockedError = null;

        DB::transaction(function () use ($pengguna, &$blockedError) {
            $target = User::whereKey($pengguna->id)->lockForUpdate()->first();
            if (! $target) {
                return;
            }

            if ($target->role === 'admin') {
                $admins = User::byRole('admin')->lockForUpdate()->get(['id', 'is_active']);
                $remainingActiveAdmins = $admins->filter(
                    fn (User $u) => (int) $u->id !== (int) $target->id && $u->is_active
                )->count();

                if ($remainingActiveAdmins < 1) {
                    $blockedError = 'Tidak dapat menghapus admin aktif terakhir. Tambahkan admin aktif lain terlebih dahulu.';

                    return;
                }
            }

            $target->delete();
        });

        if ($blockedError !== null) {
            return back()->with('error', $blockedError);
        }

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Akun pengguna berhasil dihapus.');
    }

    public function toggleStatus(User $pengguna)
    {
        if ($pengguna->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        Gate::authorize('toggleStatus', $pengguna);

        $blockedError = null;
        $newState     = $pengguna->is_active;

        DB::transaction(function () use ($pengguna, &$blockedError, &$newState) {
            $target = User::whereKey($pengguna->id)->lockForUpdate()->firstOrFail();

            if ($target->role === 'admin' && $target->is_active) {
                $admins = User::byRole('admin')->lockForUpdate()->get(['id', 'is_active']);
                $remainingActiveAdmins = $admins->filter(
                    fn (User $u) => (int) $u->id !== (int) $target->id && $u->is_active
                )->count();

                if ($remainingActiveAdmins < 1) {
                    $blockedError = 'Tidak dapat menonaktifkan admin aktif terakhir.';

                    return;
                }
            }

            $newState = ! $target->is_active;
            $target->update(['is_active' => $newState]);
        });

        if ($blockedError !== null) {
            return back()->with('error', $blockedError);
        }

        $status = $newState ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$pengguna->name} berhasil {$status}.");
    }

    public function resetPassword(Request $request, User $pengguna)
    {
        Gate::authorize('update', $pengguna);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            ], [
                'password.min'       => 'Password baru minimal 8 karakter dan harus mengandung huruf besar, huruf kecil, serta angka.',
                'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            ]);
            $pengguna->update(['password' => Hash::make($request->password)]);
            if ((int) $pengguna->id !== (int) auth()->id()) {
                $pengguna->revokeActiveSessions();
            } else {
                $pengguna->revokeOtherSessions($request->hasSession() ? $request->session()->getId() : null, $request->password);
            }

            return back()->with('success', "Password untuk {$pengguna->name} berhasil diperbarui.");
        }

        $newPassword = 'Cti'
            . strtoupper(Str::random(3))
            . random_int(1000, 9999)
            . strtolower(Str::random(2));

        $pengguna->update(['password' => Hash::make($newPassword)]);
        if ((int) $pengguna->id !== (int) auth()->id()) {
            $pengguna->revokeActiveSessions();
        } else {
            $pengguna->revokeOtherSessions($request->hasSession() ? $request->session()->getId() : null, $newPassword);
        }

        return back()
            ->with('success', "Password untuk {$pengguna->name} berhasil direset secara otomatis. Salin password sementara pada kotak di bawah.")
            ->with('generated_password', $newPassword)
            ->with('generated_password_user', $pengguna->email);
    }
}
