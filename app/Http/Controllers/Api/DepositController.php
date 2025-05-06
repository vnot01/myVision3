<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\ReverseVendingMachine;
use App\Models\User; // Import User model
use Illuminate\Http\JsonResponse; // <-- Import untuk return type hinting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // <-- Pastikan Cache diimport
use Illuminate\Support\Facades\DB;    // <-- Import DB untuk transaction (Best Practice)
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// Hapus Auth jika tidak digunakan langsung di sini (kita pakai Cache::pull)
// use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    /**
     * Store a newly created deposit record initiated by an RVM.
     *
     * This endpoint expects authentication via the 'auth.rvm' middleware
     * (verifying X-Rvm-ApiKey) and user identification via a temporary
     * RVM token passed in the request body.
     *
     * @param Request $request The incoming API request.
     * @return JsonResponse The JSON response indicating success or failure.
     */
    public function store(Request $request): JsonResponse // Tambahkan return type hint
    {
        // --- 1. Validasi Input dari RVM ---
        $validator = Validator::make($request->all(), [
            'detected_type' => ['required', 'string', 'in:mineral_plastic,other_bottle,unknown,contains_content'],
            'rvm_token' => ['required', 'string', 'size:32'], // Token sementara dari User (via QR Scan)
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input provided.', 'errors' => $validator->errors()], 422);
        }

        // --- 2. Dapatkan RVM yang Terotentikasi (dari Middleware) ---
        /** @var ReverseVendingMachine|null $rvm */
        $rvm = $request->attributes->get('authenticated_rvm'); // Nama atribut konsisten

        // Middleware 'auth.rvm' seharusnya sudah menangani jika RVM tidak valid/aktif
        // Pemeriksaan ini sebagai jaring pengaman jika middleware gagal set atribut
        if (!$rvm) {
            Log::error('Middleware failed to set authenticated_rvm attribute on request.');
            return response()->json(['message' => 'RVM identification failed due to an internal error.'], 500);
        }

        // --- 3. Validasi Token RVM & Identifikasi User ---
        $providedToken = $request->input('rvm_token');
        $cacheKey = 'rvm_token_' . $providedToken;

        // Ambil user ID dari cache dan langsung hapus token (single use)
        $userId = Cache::pull($cacheKey);

        if (!$userId) {
            return response()->json(['message' => 'Invalid or expired RVM token.'], 400); // Bad Request
        }

        $user = User::find($userId);
        if (!$user) {
            // Situasi aneh: token valid tapi user tidak ada
            Log::error('User not found for valid RVM token.', ['user_id' => $userId, 'token' => $providedToken, 'rvm_id' => $rvm->id]);
            return response()->json(['message' => 'User associated with token not found.'], 404);
        }
        // --- User Berhasil Diidentifikasi ---

        // --- 4. Tentukan Logika Bisnis (Poin & Tindakan) ---
        $detected_type = $request->input('detected_type');
        $points_awarded = 0;
        $needs_action = false;
        $responseMessage = 'Deposit processed.'; // Pesan default

        switch ($detected_type) {
            case 'mineral_plastic':
                $points_awarded = 10; // Contoh
                $responseMessage = 'Mineral bottle accepted.';
                break;
            case 'other_bottle':
                $points_awarded = 5; // Contoh
                $responseMessage = 'Bottle accepted.';
                break;
            case 'contains_content':
                $points_awarded = 0;
                $needs_action = true;
                $responseMessage = 'Bottle rejected: contains content. Please retrieve.';
                break;
            case 'unknown':
            default:
                $points_awarded = 0;
                $needs_action = true; // Anggap perlu diambil jika tidak dikenal
                $responseMessage = 'Bottle rejected: unrecognized type. Please retrieve.';
                break;
        }

        // --- 5. Simpan ke Database dalam Transaction ---
        try {
            // Mulai transaction untuk memastikan konsistensi data
            $deposit = DB::transaction(function () use ($user, $rvm, $detected_type, $points_awarded, $needs_action) {
                // Buat record deposit
                $newDeposit = Deposit::create([
                    'user_id' => $user->id,
                    'rvm_id' => $rvm->id, // Gunakan ID RVM dari middleware
                    'detected_type' => $detected_type,
                    'points_awarded' => $points_awarded,
                    'needs_action' => $needs_action,
                    'deposited_at' => now(),
                ]);

                // Update poin user jika ada poin yang diberikan
                if ($points_awarded > 0) {
                    $user->increment('points', $points_awarded);
                }

                return $newDeposit; // Kembalikan deposit yang baru dibuat
            });

            // --- 6. Kirim Respons Sukses/Warning ke RVM ---
            return response()->json([
                'status' => $needs_action ? 'warning' : 'success', // Gunakan status warning jika perlu tindakan
                'message' => $responseMessage,
                'deposit_details' => [ // Kelompokkan detail jika perlu
                    'id' => $deposit->id,
                    'detected_type' => $deposit->detected_type,
                    'points_awarded' => $deposit->points_awarded,
                    'needs_action' => $deposit->needs_action,
                ],
                'user_points_new_total' => $user->fresh()->points // Ambil poin terbaru setelah increment
            ], 201); // 201 Created

        } catch (\Throwable $e) { // Tangkap Throwable untuk error database/lainnya
             // Rollback otomatis dilakukan oleh DB::transaction jika ada Exception

            Log::error('Failed to process deposit transaction:', [
                'error' => $e->getMessage(),
                'request_data' => $request->except('image_base64'), // Jangan log base64 gambar
                'rvm_id' => $rvm->id ?? 'unknown',
                'user_id' => $user->id ?? $userId ?? 'unknown',
                // 'trace' => $e->getTraceAsString() // Hati-hati di production
            ]);

            // Kirim Respons Error Umum
            return response()->json(['message' => 'Failed to process deposit due to an internal error.'], 500);
        }
    }

    // Metode lain (index, show, update, destroy) bisa ditambahkan di sini jika diperlukan
    // public function index() { /* ... */ }
    // public function show(Deposit $deposit) { /* ... */ } // Gunakan Route Model Binding
    // public function update(Request $request, Deposit $deposit) { /* ... */ }
    // public function destroy(Deposit $deposit) { /* ... */ }
}