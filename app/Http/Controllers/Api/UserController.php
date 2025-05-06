<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache; // Gunakan Cache Laravel
use Illuminate\Http\JsonResponse;     // Import JsonResponse

class UserController extends Controller // Atau AuthController
{
    /**
     * Generate a short-lived token for RVM interaction.
     * Endpoint ini harus diakses oleh user yang sudah login (via Sanctum token).
     */
    public function generateRvmToken(Request $request)
    {
         /** @var \App\Models\User $user */
         $user = Auth::user(); // Dapatkan user yang terotentikasi via Sanctum

         if (!$user) {
             // Ini tidak seharusnya terjadi jika middleware auth:sanctum aktif
             return response()->json(['message' => 'Unauthenticated.'], 401);
         }

         // 1. Generate token unik (cukup random)
         $rvmToken = Str::random(32); // Token 32 karakter

         // 2. Tentukan waktu kedaluwarsa (misalnya 60 detik)
         $expiresInSeconds = 60;

         // 3. Simpan token di cache dengan user ID sebagai value dan expiry time
         // Key cache: 'rvm_token_USERID_TOKENVALUE'
         // Value: User ID
         // TTL: expiry time
         Cache::put('rvm_token_' . $rvmToken, $user->id, $expiresInSeconds);

         // 4. Kembalikan token dan waktu kedaluwarsa ke Aplikasi User
         return response()->json([
             'message' => 'RVM token generated successfully.',
             'rvm_token' => $rvmToken,
             'expires_in' => $expiresInSeconds, // Beri tahu klien berapa lama token valid
         ]);
    }

    // Metode lain UserController (misal get profile) bisa ditambahkan di sini
    public function profile(Request $request)
    {
         return response()->json($request->user());
    }

    /**
     * Get the current point balance for the authenticated user.
     */
    public function getPoints(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'points' => $user->points ?? 0, // Ambil poin user, default 0 jika null
        ]);
    }

    /**
     * Get the deposit history for the authenticated user, paginated.
     */
    public function listDeposits(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Ambil deposit milik user, urutkan terbaru dulu, gunakan pagination
        // Eager load data RVM untuk ditampilkan (pilih kolom yang relevan)
        $deposits = $user->deposits() // Panggil relasi
                          ->with('rvm:id,name,location') // Eager load RVM (pilih kolom id, name, location)
                          ->latest('deposited_at') // Urutkan berdasarkan waktu deposit (terbaru dulu)
                          ->paginate(15); // Ambil 15 data per halaman (sesuaikan jika perlu)

        // Laravel secara otomatis format pagination response untuk API
        return response()->json($deposits);
    }
}