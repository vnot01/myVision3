<!-- < ?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Untuk transaction
use Illuminate\Support\Facades\Log;
use App\Services\VisionAnalysisService; // Import service kita
use App\Models\User;
use App\Models\Deposit;
use App\Models\ReverseVendingMachine; // Import RVM model
use Exception; // Import Exception
use Illuminate\Http\JsonResponse;        // <- Import untuk return type hinting

class RvmDepositController extends Controller
{
    protected VisionAnalysisService $visionService;

    // Inject service melalui constructor
    public function __construct(VisionAnalysisService $visionService)
    {
        $this->visionService = $visionService;
    }

    /**
     * Menerima dan memproses permintaan deposit dari RVM.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 1. Validasi Input dari RVM
        $validator = Validator::make($request->all(), [
            // 'rvm_id' => 'required|exists:reverse_vending_machines,id', // Atau otentikasi via API Key
            'user_identifier' => 'required|string', // ID User atau token Guest
            'image_base64' => 'required|string', // Data gambar base64
            'image_mime_type' => 'required|string|in:image/jpeg,image/png,image/webp', // Batasi tipe gambar
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
        }

        // // --- Otentikasi RVM (Contoh: via Header API Key) ---
        // // Ini contoh sederhana, gunakan Sanctum/Passport untuk produksi
        // $apiKey = $request->header('X-RVM-ApiKey');
        // $rvm = ReverseVendingMachine::where('api_key', $apiKey)->where('status', 'active')->first();

        // if (!$apiKey || !$rvm) {
        //      return response()->json(['status' => 'error', 'message' => 'Unauthorized RVM.'], 401);
        // }
        // // ---------------------------------------------

        // --- Otentikasi RVM ---
        // Asumsi middleware 'auth.rvm' sudah berjalan dan menambahkan RVM ke request
        // Jika tidak pakai middleware, otentikasi di sini (misal cek header X-RVM-ApiKey)
        /** @var ReverseVendingMachine|null $rvm */
        $rvm = $request->attributes->get('authenticated_rvm'); // Ambil dari middleware (jika ada)
        if (!$rvm) {
             // Fallback: Cek header jika tidak pakai middleware
             $apiKey = $request->header('X-RVM-ApiKey');
             $rvm = $apiKey ? ReverseVendingMachine::where('api_key', $apiKey)->where('status', 'active')->first() : null;
             if (!$rvm) {
                 return response()->json(['status' => 'error', 'message' => 'Unauthorized RVM.'], 401);
             }
        }
        // --- Akhir Otentikasi RVM ---

        // --- Identifikasi User ---
        // TODO: Implementasi logika untuk user_identifier
        // Bisa jadi user_id langsung, atau token guest yang perlu di-decode/dicari
        // Untuk sekarang, kita anggap user_identifier ADALAH user_id
        $userId = $request->input('user_identifier');
        $user = User::find($userId); // Cari user berdasarkan ID

        if (!$user) {
             // Jika user tidak ditemukan (termasuk guest ID sementara jika ada)
             // Mungkin log error atau kirim respons error spesifik?
             return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }
        // ------------------------


        DB::beginTransaction(); // Mulai transaction database

        try {
            // 2. Analisis Gambar menggunakan Service
            $analysis = $this->visionService->analyzeBottleImage(
                $request->input('image_base64'),
                $request->input('image_mime_type')
            );

            // 3. Buat Catatan Deposit
            $deposit = Deposit::create([
                'user_id' => $user->id, // Gunakan ID user yang ditemukan
                'rvm_id' => $rvm->id,   // Gunakan ID RVM yang terotentikasi
                'detected_type' => $analysis['type'],
                'points_awarded' => $analysis['points'],
                'needs_action' => $analysis['needs_action'],
                'deposited_at' => now(),
            ]);

            // 4. Update Poin User (jika poin diberikan dan bukan guest - atau logika guest)
            if ($analysis['points'] > 0 && !$user->is_guest) { // Cek jika bukan guest
                 // Gunakan increment agar aman dari race condition
                 $user->increment('points', $analysis['points']);
            }

            DB::commit(); // Simpan semua perubahan database

            // 5. Kirim Respons Sukses/Warning ke RVM
            return response()->json([
                'status' => $analysis['needs_action'] ? 'warning' : 'success',
                'message' => $analysis['message'],
                'points_awarded' => $analysis['points'],
                'needs_action' => $analysis['needs_action'],
                'deposit_id' => $deposit->id // Kirim ID deposit jika perlu
            ], 201); // 201 Created untuk resource baru (deposit)

        } catch (Exception $e) {
            DB::rollBack(); // Batalkan transaction jika ada error
            Log::error('Error processing RVM deposit:', [
                'rvm_id' => $rvm->id ?? 'unknown',
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                // 'trace' => $e->getTraceAsString() // Hati-hati di production
            ]);

            // Kirim Respons Error Umum ke RVM
            return response()->json([
                'status' => 'error',
                'message' => 'An internal error occurred during deposit processing.',
                // 'details' => $e->getMessage() // Jangan kirim detail error ke client API
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
} -->
