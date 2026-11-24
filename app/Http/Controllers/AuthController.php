<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function home()
    {
        if (Auth::check() && Auth::user()->is_active) {
            return $this->redirectByRole((string) Auth::user()->role);
        }

        return redirect()->route('login');
    }

    public function servePublicStorage(string $path)
    {
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
        abort_if($cleanPath === '' || str_contains($cleanPath, '..') || str_contains($cleanPath, "\0"), 404);
        abort_if(
            str_starts_with($cleanPath, 'instruktur/sertifikasi/')
            || str_starts_with($cleanPath, 'aktivitas/dokumentasi/'),
            403
        );
        abort_unless(Storage::disk('public')->exists($cleanPath), 404);

        return Storage::disk('public')->response($cleanPath, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('pages.auth.login', ['title' => 'Login']);
    }

    public function login(LoginRequest $request)
    {
        $key = 'login.' . Str::lower($request->email) . '.' . $request->ip();
        $maxAttempts = max(1, (int) Setting::get('max_login_attempts', 5));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($key, 60);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        RateLimiter::clear($key);

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    private function redirectByRole(string $role)
    {
        return match ($role) {
            'admin'      => redirect()->route('admin.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'instruktur' => redirect()->route('instruktur.dashboard'),
            default      => redirect()->route('login'),
        };
    }
}
