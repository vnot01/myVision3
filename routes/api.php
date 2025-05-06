<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;    // Controller untuk Auth API (Login, Logout, Register API opsional)
use App\Http\Controllers\Api\UserController;    // Controller untuk operasi terkait User (Profil, Generate Token RVM)
use App\Http\Controllers\Api\DepositController; // Controller untuk handle deposit RVM
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint API untuk aplikasi RVM, Aplikasi User, dan Dashboard.
|
*/

// =========================================================================
// == Rute Autentikasi API ==
// =========================================================================
// Endpoint ini tidak memerlukan token (publik) untuk mendapatkan token.

Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/auth/google', [AuthController::class, 'handleGoogleSignIn'])->name('api.auth.google');
// Route::post('/register', [AuthController::class, 'register'])->name('api.register'); // Aktifkan jika butuh registrasi via API


// =========================================================================
// == Rute untuk Aplikasi Klien Terautentikasi (User via Sanctum) ==
// =========================================================================
// Endpoint ini memerlukan token Sanctum Bearer dari user yang sudah login.
Route::middleware('auth:sanctum')->group(function () {

    // Mendapatkan detail user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user()->loadCount('deposits'); // Contoh: Muat juga jumlah deposit user
    })->name('api.user');

    // Contoh: Mendapatkan profil user (bisa digabung dengan /user atau endpoint terpisah)
    Route::get('/user/profile', [UserController::class, 'profile'])->name('api.user.profile');

    // User meminta token sementara untuk digunakan di RVM
    Route::post('/user/rvm-token', [UserController::class, 'generateRvmToken'])->name('api.user.rvm-token');

    // Logout (menghapus token Sanctum yang sedang digunakan)
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Mendapatkan poin user saat ini
    Route::get('/user/points', [UserController::class, 'getPoints'])->name('api.user.points');
    // Mendapatkan riwayat deposit user (paginated)
    Route::get('/user/deposits', [UserController::class, 'listDeposits'])->name('api.user.deposits');

});


// =========================================================================
// == Rute untuk Reverse Vending Machine (RVM) ==
// =========================================================================
// Endpoint ini memerlukan otentikasi RVM via API Key (middleware 'auth.rvm').
Route::middleware('auth.rvm')->group(function() {

    // RVM mengirim data deposit baru setelah memindai botol dan token user
    Route::post('/deposits', [DepositController::class, 'store'])->name('api.deposits.store');

    // TODO: Tambahkan endpoint lain untuk RVM di sini jika perlu
    // Contoh:
    // Route::post('/rvm/status', [/* RvmStatusController? */ 'updateStatus']); // RVM melaporkan statusnya
    // Route::get('/rvm/config', [/* RvmConfigController? */ 'getConfig']); // RVM meminta konfigurasi

});

// =========================================================================
// == Rute untuk Aplikasi Dashboard ==
// =========================================================================
// Endpoint ini memerlukan otentikasi Sanctum DAN role Admin/Operator.
Route::middleware(['auth:sanctum', 'role.admin_operator']) // Terapkan kedua middleware
     ->prefix('dashboard') // Prefix URL (opsional, jadi /api/dashboard/...)
     ->name('api.dashboard.') // Prefix nama route (opsional)
     ->group(function() {
    // Mendapatkan statistik dasar
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    // Mendapatkan daftar RVM
    Route::get('/rvms', [DashboardController::class, 'listRvms'])->name('rvms.list');
    // Mendapatkan daftar semua deposit (dengan filter)
    Route::get('/deposits', [DashboardController::class, 'listDeposits'])->name('deposits.list');
    // TODO: Tambahkan endpoint lain untuk dashboard
    // Misal: POST /rvms (tambah RVM), PUT /rvms/{rvm} (update RVM), GET /users, dll.

});