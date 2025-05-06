<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Import User model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException; // Untuk throw error login
use Laravel\Socialite\Facades\Socialite; // Import Socialite
use Exception; // Import Exception
use Illuminate\Support\Facades\Log; // Untuk logging

class AuthController extends Controller
{
    /**
     * Handle a login request for the API.
     * User mengirim email & password, mendapatkan token jika berhasil.
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'required|string', // Nama perangkat untuk token (e.g., 'My Phone App')
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek user dan password
        if (! $user || ! Hash::check($request->password, $user->password)) {
             // Jika user tidak ada atau password salah
             throw ValidationException::withMessages([
                'email' => [__('auth.failed')], // Pesan standar
            ]);
            // Alternatif: return response JSON langsung
            // return response()->json(['message' => __('auth.failed')], 401); // Unauthorized
        }

         // 4. Cek apakah email sudah diverifikasi (Opsional tapi direkomendasikan)
         if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email not verified.'], 403); // Forbidden
         }


        // 5. Buat API Token Sanctum
        // Nama token sebaiknya unik per perangkat/sesi user
        $token = $user->createToken($request->device_name)->plainTextToken;

        // 6. Kirim respons berisi token dan data user (opsional)
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user // Anda bisa memilih data user apa saja yang dikembalikan
                           // Hati-hati jangan kirim data sensitif
                           // Model User Anda sudah punya $hidden, jadi password dll aman
        ]);
    }

    /**
     * Handle a logout request for the API.
     * Menghapus token yang sedang digunakan untuk request ini.
     */
    public function logout(Request $request)
    {
        // Dapatkan user yang terotentikasi via Sanctum token
        /** @var \App\Models\User $user */
        $user = $request->user(); // atau Auth::user();

        if ($user) {
            // Hapus token saat ini yang digunakan untuk autentikasi
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['message' => 'No authenticated user'], 401); // Jika tidak ada token valid
    }

     // --- Metode Registrasi API (Opsional) ---
     // Jika Anda ingin user bisa mendaftar LANGSUNG via API
     // public function register(Request $request)
     // {
     //    // 1. Validasi data (mirip RegisteredUserController, tapi tanpa Google/Session)
     //    //    Pastikan semua field yang diperlukan (name, email, password, phone, etc.) ada
     //    $validated = $request->validate([
     //        'name' => ['required', 'string', 'max:255'],
     //        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
     //        'password' => ['required', 'confirmed', 'string', 'min:8', 'max:16'], // Perlu 'password_confirmation' juga di request
     //        'phone_number' => ['required', 'string', 'max:20'],
     //        'citizenship' => ['required', 'in:WNI,WNA'],
     //        'identity_type' => ['required', /* ... validasi kondisional ... */],
     //        'identity_number' => ['required', /* ... validasi kondisional + unique ... */],
     //        'device_name' => ['required', 'string'], // Untuk token setelah register
     //    ]);

     //    // 2. Buat User
     //    $user = User::create([
     //        'name' => $validated['name'],
     //        'email' => $validated['email'],
     //        'password' => Hash::make($validated['password']),
     //        'phone_number' => $validated['phone_number'],
     //        'citizenship' => $validated['citizenship'],
     //        'identity_type' => $validated['identity_type'],
     //        'identity_number' => $validated['identity_number'],
     //        'points' => 0, // Default
     //        'role' => 'User', // Default
     //        'is_guest' => false, // Default
     //    ]);

     //    // 3. Kirim Email Verifikasi (jika model User implement MustVerifyEmail)
     //    // event(new \Illuminate\Auth\Events\Registered($user)); // Trigger event standar

     //    // 4. Buat Token (Opsional: bisa juga minta user login dulu setelah verif)
     //    // $token = $user->createToken($validated['device_name'])->plainTextToken;

     //    // 5. Kirim Respons
     //    return response()->json([
     //        'message' => 'Registration successful. Please verify your email.',
     //        'user' => $user,
     //        // 'access_token' => $token, // Jika token dibuat langsung
     //        // 'token_type' => 'Bearer',
     //    ], 201);
     // }

    /**
     * Handle sign-in requests initiated by Google Sign-In on the client-side.
     * Expects a Google ID Token from the client.
     * Verifies the token, finds/creates the user, and returns a Sanctum token.
     */
    public function handleGoogleSignIn(Request $request)
    {
        // 1. Validasi Input: Membutuhkan id_token dari Google dan device_name
        $request->validate([
            'id_token' => 'required|string',
            'device_name' => 'required|string', // Untuk nama token Sanctum
        ]);

        $googleIdToken = $request->input('id_token');
        $deviceName = $request->input('device_name');

        try {
            // 2. Verifikasi ID Token & Dapatkan User Info dari Google
            // Socialite::driver('google')->userFromToken() akan memverifikasi token
            // dan mengembalikan objek Socialite User jika valid.
            $googleUser = Socialite::driver('google')->userFromToken($googleIdToken);

            if (!$googleUser) {
                // Seharusnya tidak terjadi jika token valid, tapi sebagai jaring pengaman
                 throw new Exception('Failed to get user information from Google token.');
            }

            // 3. Cari atau Buat User di Database (Logika mirip GoogleAuthController web)
            /** @var User $user */
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // User Google sudah ada, update avatar jika berubah (opsional)
                if ($user->avatar !== $googleUser->getAvatar()) {
                    $user->update(['avatar' => $googleUser->getAvatar()]);
                }
            } else {
                // Cek apakah email sudah ada (user daftar biasa tapi belum connect Google)
                $user = User::where('email', $googleUser->getEmail())->first();
                if ($user) {
                    // User email ada, hubungkan Google & tandai verified
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => $user->email_verified_at ?? now(), // Pastikan verified
                    ]);
                } else {
                    // User benar-benar baru, buat user baru
                    // (Asumsi tidak perlu melengkapi profil via API terpisah untuk login ini)
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => now(), // Email dari Google dianggap verified
                        'password' => null, // Tidak ada password untuk akun Google
                        'points' => 0,
                        'role' => 'User', // Default role
                        'is_guest' => false,
                        // Anda mungkin perlu default value untuk field lain
                        // seperti phone_number, citizenship, dll. atau buat nullable
                        // Jika field tersebut required, alur API ini perlu diubah
                        // untuk meminta data tambahan setelah verifikasi token google.
                    ]);
                }
            }

            // 4. Buat Token Sanctum
            $token = $user->createToken($deviceName)->plainTextToken;

            // 5. Kirim Respons Sukses
            return response()->json([
                'message' => 'Google Sign-In successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->fresh(), // Kirim data user terbaru
            ]);

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
             Log::error('Google API Sign-In Invalid State: ' . $e->getMessage());
             return response()->json(['message' => 'Google authentication failed (Invalid State). Please try again.'], 401);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Tangkap error jika Socialite gagal menghubungi Google (misal token expired/invalid)
             Log::error('Google API Sign-In Guzzle Error: ' . $e->getResponse()->getBody());
             // Cek detail error, mungkin token tidak valid
             return response()->json(['message' => 'Invalid Google token or communication error.'], 401);
        } catch (Exception $e) {
            // Tangkap error umum lainnya
            Log::error('Google API Sign-In Error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during Google Sign-In.'], 500);
        }
    }
}
