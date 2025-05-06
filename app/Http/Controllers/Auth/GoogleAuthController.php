<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Tidak digunakan untuk pw, tapi jaga-jaga
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // User Google lama, update avatar jika berubah
                if ($user->avatar !== $googleUser->getAvatar()) {
                    $user->update(['avatar' => $googleUser->getAvatar()]);
                }
                Auth::login($user);
                return $this->redirectUserAfterAuth($user); // Panggil helper baru

            } else {
                $user = User::where('email', $googleUser->getEmail())->first();
                if ($user) {
                    // User email ada, hubungkan Google & tandai verified
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => now(), // Tandai verified saat menghubungkan
                    ]);
                    Auth::login($user);
                    return $this->redirectUserAfterAuth($user); // Panggil helper baru
                } else {
                    /// User benar-benar baru -> Simpan info Google, redirect ke form lengkap (register)
                    $googleData = [
                        'id' => $googleUser->getId(),
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'avatar' => $googleUser->getAvatar(),
                    ];
                    $request->session()->put('google_user_info', $googleData); // <-- Pastikan key ini benar
                    // Debug opsional: dd(session('google_user_info'));
                    return redirect()->route('register'); // Redirect ke route register biasa
                }
            }
        } catch (Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }

    // Ganti helper redirectBasedOnVerification menjadi:
    protected function redirectUserAfterAuth(User $user)
    {
        // Cek apakah user perlu verifikasi email
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            // Jika ya (seharusnya tidak untuk Google User baru/terhubung), redirect ke notice
            return redirect()->route('verification.notice');
        }
        // Jika tidak perlu, redirect ke tujuan utama
        return redirect()->intended(route('vision.test.form', [], false));
    }
}
