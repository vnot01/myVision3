<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendPhoneVerificationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Display the phone verification prompt / form.
     */
    public function showNotice(): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Cukup panggil sekali

        if ($user instanceof \App\Models\User) {
            if ($user->hasVerifiedPhone()) {
                return redirect()->intended(route('vision.test.form', [], false));
            }
            // Jika belum terverifikasi, tampilkan view
            return view('auth.verify-phone');
        } else {
            // Handle user bukan instance User (redirect ke login)
            Log::warning('Auth::user() did not return an expected User instance in verification notice route.', [
                'route' => request()->route()?->getName(),
                'user_type' => is_object($user) ? get_class($user) : gettype($user),
            ]);
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Authentication error. Please login again.');
        }
    }

    /**
     * Mark the authenticated user's phone number as verified.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Cukup panggil sekali

        if ($user instanceof \App\Models\User) {
            if ($user->hasVerifiedPhone()) {
                return redirect()->intended(route('vision.test.form', [], false))->with('info', 'Phone already verified.');
            }

            // Cek kode
            if ($user->phone_code === $request->input('code')) {
                // Verifikasi berhasil
                $user->forceFill([
                    'phone_verified_at' => now(),
                    'phone_code' => null, // Hapus kode setelah digunakan
                ])->save();
                return redirect()->intended(route('vision.test.form', [], false))->with('status', 'Phone verified successfully!');
            } else {
                // Kode salah
                return back()->withErrors(['code' => 'The provided verification code is incorrect.'])->withInput();
            }
        } else {
            // Handle user bukan instance User
            Log::warning('Auth::user() did not return an expected User instance in phone verify route.', [
                'route' => request()->route()?->getName(),
                'user_type' => is_object($user) ? get_class($user) : gettype($user),
            ]);
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Authentication error. Please login again.');
        }
    }

    /**
     * Resend the phone verification email.
     */
    public function resend(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Cukup panggil sekali

        if ($user instanceof \App\Models\User) {
            if ($user->hasVerifiedPhone()) {
                // Jika sudah terverifikasi, langsung redirect
                return redirect()->intended(route('vision.test.form', [], false));
            }

            // --- Logika Resend yang Benar ---
            // Generate kode baru
            $verificationCode = random_int(100000, 999999);
            $user->forceFill(['phone_code' => $verificationCode])->save();

            try {
                Mail::to($user->email)->send(new SendPhoneVerificationCode((string) $verificationCode));
                // Kembali ke halaman sebelumnya (verify-phone) dengan pesan sukses
                return back()->with('status', 'A fresh verification code has been sent to your email address.');
            } catch (\Exception $e) {
                Log::error("Failed to resend verification email to {$user->email}: " . $e->getMessage());
                // Kembali ke halaman sebelumnya dengan pesan error
                return back()->withErrors(['email' => 'Failed to resend verification code. Please try again later.']);
            }
            // --- Akhir Logika Resend ---

        } else {
            // Handle user bukan instance User
            Log::warning('Auth::user() did not return an expected User instance in verification resend route.', [
                'route' => request()->route()?->getName(),
                'user_type' => is_object($user) ? get_class($user) : gettype($user),
            ]);
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Authentication error. Please login again.');
        }
    }
}
