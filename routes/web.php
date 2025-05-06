<?php

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\VisionTestController;

// // // Route::get('/', function () {
// // //     return view('welcome');
// // // });

// Route::get('/vision-test', [VisionTestController::class, 'index'])->name('vision.test.form');
// Route::post('/vision-test', [VisionTestController::class, 'analyze'])->name('vision.test.analyze');

// // Redirect root ke halaman test untuk kemudahan
// Route::get('/', function () {
//     return redirect()->route('vision.test.form');
// });

// use Illuminate\Http\Request; // <-- TAMBAHKAN BARIS INI
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\VisionTestController;
// use App\Http\Controllers\Auth\GoogleAuthController;
// use App\Http\Controllers\Auth\RegisteredUserController;
// use App\Http\Controllers\Auth\AuthenticatedSessionController;
// // Hapus use VerificationController

// // Rute halaman utama
// Route::get('/', function () {
//     return redirect()->route('login');
// });

// // Rute Vision Test (Dilindungi Auth & Verifikasi EMAIL)
// Route::middleware(['auth', 'verified'])->group(function () { // Ganti middleware
//     Route::get('/vision-test', [VisionTestController::class, 'index'])->name('vision.test.form');
//     Route::post('/vision-test', [VisionTestController::class, 'analyze'])->name('vision.test.analyze');
//     // Route::get('/dashboard', function () { ... })->name('dashboard');
// });

// // Rute Autentikasi Bawaan Laravel (Termasuk Verifikasi Email)
// // Cara 1: Simple (jika tidak butuh kustomisasi route auth lain)
// // Auth::routes(['verify' => true]); // Ini akan membuat semua route auth standar

// // Cara 2: Manual (lebih kontrol)
// Route::middleware('guest')->group(function () {
//     // Registrasi
//     Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
//     Route::post('register', [RegisteredUserController::class, 'store']);
//     // Hapus route register/complete

//     // Login
//     Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
//     Route::post('login', [AuthenticatedSessionController::class, 'store']);

//     // Google OAuth Routes
//     Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
//     Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
// });

// // Logout (Membutuhkan user login)
// Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

// // Rute Verifikasi Email Standar Laravel (Membutuhkan user login)
// Route::get('/email/verify', function () {
//     return view('auth.verify-email'); // Tampilkan halaman notice
// })->middleware('auth')->name('verification.notice');

// Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
//     $request->fulfill(); // Handle verifikasi link
//     return redirect('/vision-test')->with('status', 'Email verified successfully!'); // Redirect setelah sukses
// })->middleware(['auth', 'signed'])->name('verification.verify'); // 'signed' penting!

// Route::post('/email/verification-notification', function (Request $request) {
//     if ($request->user()->hasVerifiedEmail()) {
//         return redirect()->intended(route('vision.test.form', [], false));
//     }
//     $request->user()->sendEmailVerificationNotification(); // Kirim ulang notifikasi
//     return back()->with('status', 'Verification link sent!');
// })->middleware(['auth', 'throttle:6,1'])->name('verification.send'); // Rate limit

// // HAPUS GRUP ROUTE UNTUK VERIFIKASI TELEPON YANG LAMA

// Import Auth facade di atas file jika belum ada
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request; // Pastikan ini ada
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisionTestController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Rute halaman utama (SEKARANG CEK LOGIN)
Route::get('/', function () {
    // Cek apakah user sudah login
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Cek apakah email user sudah diverifikasi (jika model mengimplement MustVerifyEmail)
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
             // Jika login tapi belum verified, arahkan ke halaman verifikasi
            return redirect()->route('verification.notice');
        }
        // Jika sudah login dan verified (atau tidak perlu verifikasi), arahkan ke vision test
        return redirect()->route('vision.test.form');
    }
    // Jika tidak login, arahkan ke halaman login
    return redirect()->route('login');
});

// --- Sisa route Anda (tidak perlu diubah) ---

// Rute Vision Test (Dilindungi Auth & Verifikasi EMAIL)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vision-test', [VisionTestController::class, 'index'])->name('vision.test.form');
    Route::post('/vision-test', [VisionTestController::class, 'analyze'])->name('vision.test.analyze');
});

// Rute Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

// Logout
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

// // Rute Verifikasi Email Standar Laravel
// Route::get('/email/verify', function () { /* ... */ })->middleware('auth')->name('verification.notice');
// Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) { /* ... */ })->middleware(['auth', 'signed'])->name('verification.verify');
// Route::post('/email/verification-notification', function (Request $request) { /* ... */ })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Rute Verifikasi Email Standar Laravel (Membutuhkan user login)
Route::get('/email/verify', function () {
    return view('auth.verify-email'); // Tampilkan halaman notice
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill(); // Handle verifikasi link
    return redirect('/vision-test')->with('status', 'Email verified successfully!'); // Redirect setelah sukses
})->middleware(['auth', 'signed'])->name('verification.verify'); // 'signed' penting!

Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->intended(route('vision.test.form', [], false));
    }
    $request->user()->sendEmailVerificationNotification(); // Kirim ulang notifikasi
    return back()->with('status', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send'); // Rate limit