<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
// Hapus use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Gunakan Request biasa
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException; // Untuk throw manual
use Illuminate\View\View;
use App\Http\Controllers\Auth\GoogleAuthController;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse // Ubah dari LoginRequest ke Request
    {
        // 1. Validasi manual
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Coba autentikasi
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // 3. Regenerate session
        $request->session()->regenerate();

        $user = Auth::user();

        // Cek apakah user adalah instance User dan perlu verifikasi email
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            // Redirect ke halaman notifikasi verifikasi email standar
            return redirect()->route('verification.notice');
        }

        // Jika tidak perlu verifikasi, redirect ke tujuan utama
        return redirect()->intended(route('vision.test.form', [], false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
