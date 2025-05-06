<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReverseVendingMachine; // Model RVM
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator; // Untuk validasi store/update
use Illuminate\Support\Str; // Untuk generate API Key (jika perlu)
use Illuminate\Support\Facades\DB;  // Untuk transaction (opsional)
use Illuminate\Support\Facades\Log; // Untuk logging

class RvmManagementController extends Controller
{
    /**
     * Display a listing of the RVMs.
     * (Mirip dengan DashboardController->listRvms, bisa dipindahkan ke sini
     * atau biarkan DashboardController untuk tampilan agregat saja).
     * Untuk konsistensi, kita implementasikan di sini juga.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'nullable|integer|min:5|max:100',
                'status' => 'nullable|string|in:active,inactive,maintenance', // Filter status
            ]);

            $query = ReverseVendingMachine::select('id', 'name', 'location', 'status', 'latitude', 'longitude', 'created_at')
                                          ->orderBy('name');

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $perPage = $request->input('per_page', 15);
            $rvms = $query->paginate($perPage);

            return response()->json($rvms);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve RVM list for management: ' . $e->getMessage());
            return response()->json(['message' => 'Could not retrieve RVM list.'], 500);
        }
    }

    /**
     * Store a newly created RVM in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validasi input untuk RVM baru
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|string|in:active,inactive,maintenance',
            // API Key sebaiknya digenerate otomatis, bukan dari input
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        // Di dalam metode store() RvmManagementController
        $validatedData = $validator->validated(); // Ambil data yang lolos validasi

        DB::beginTransaction();
        try {
            $apiKey = Str::random(40);
            $hashedApiKey = hash('sha256', $apiKey);

            $rvm = ReverseVendingMachine::create([
                'name' => $validatedData['name'],
                'location' => $validatedData['location'],
                // Ambil dari validated data jika ada, jika tidak eksplisit set null
                'latitude' => $validatedData['latitude'] ?? null,
                'longitude' => $validatedData['longitude'] ?? null,
                'status' => $validatedData['status'],
                'api_key' => $hashedApiKey,
            ]);

            DB::commit();

            // Kembalikan data RVM baru BESERTA API Key ASLI sekali ini saja
            return response()->json([
                'message' => 'RVM created successfully.',
                'rvm' => $rvm,
                'api_key' => $apiKey // Kirim key asli agar bisa dikonfigurasikan ke mesin
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create RVM: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create RVM.'], 500);
        }
    }

    /**
     * Display the specified RVM.
     * Mencari RVM secara manual berdasarkan ID.
     */
    // Ubah parameter menjadi string ID
    public function show(string $rvmId): JsonResponse
    {
        Log::info("Attempting to show RVM with ID: " . $rvmId);
        // Cari manual, gunakan findOrFail untuk otomatis 404 jika tidak ada
        try {
            $rvm = ReverseVendingMachine::findOrFail($rvmId);
            Log::info("RVM found in show:", $rvm->toArray());
            return response()->json($rvm); // Kembalikan model yang ditemukan
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("RVM not found in show method for ID: " . $rvmId);
            return response()->json(['message' => 'RVM not found'], 404);
        } catch (\Exception $e) {
             Log::error("Error showing RVM ID {$rvmId}: " . $e->getMessage());
             return response()->json(['message' => 'Could not retrieve RVM details.'], 500);
        }
    }

    /**
     * Update the specified RVM in storage.
     * Mencari RVM secara manual berdasarkan ID.
     */
     // Ubah parameter menjadi string ID
    public function update(Request $request, string $rvmId): JsonResponse
    {
         Log::info("Attempting to update RVM with ID: " . $rvmId);
         // Cari manual, gunakan findOrFail
         try {
            $reverseVendingMachine = ReverseVendingMachine::findOrFail($rvmId);
         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'RVM not found.'], 404);
         }

         // Validasi input (Sama seperti sebelumnya)
         $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|required|string|in:active,inactive,maintenance',
        ]);
        if ($validator->fails()) { return response()->json(['errors' => $validator->errors()], 422); }

        // Lakukan Update menggunakan model yang ditemukan manual
        try {
            $updated = $reverseVendingMachine->update(
                $request->only(['name', 'location', 'latitude', 'longitude', 'status'])
            );

            if (!$updated) { /* ... Log warning & return 500 ... */ }

            return response()->json([
                'message' => 'RVM updated successfully.',
                'rvm' => $reverseVendingMachine // Kembalikan model yang sudah terupdate
            ]);

        } catch (\Exception $e) {
             Log::error('Failed to update RVM ID ' . $rvmId . ': ' . $e->getMessage());
             return response()->json(['message' => 'Failed to update RVM.'], 500);
        }
    }

    /**
     * Remove the specified RVM from storage.
     * Mencari RVM secara manual berdasarkan ID.
     */
     // Ubah parameter menjadi string ID
    public function destroy(string $rvmId): JsonResponse
    {
        Log::info("Attempting to delete RVM with ID: " . $rvmId);
         // Cari manual, gunakan findOrFail
         try {
            $reverseVendingMachine = ReverseVendingMachine::findOrFail($rvmId);
         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'RVM not found.'], 404);
         }

        // Lakukan Delete menggunakan model yang ditemukan manual
        try {
            $rvmIdForLog = $reverseVendingMachine->id; // ID untuk logging/pesan
            $deleted = $reverseVendingMachine->delete();

            if ($deleted) {
                Log::info("Successfully deleted (or soft-deleted) RVM ID: {$rvmIdForLog}");
                return response()->json(['message' => "RVM ID {$rvmIdForLog} deleted successfully."], 200);
            } else {
                // Blok 'else' (delete return false)
                $modelClass = get_class($reverseVendingMachine);
                $modelUses = class_uses_recursive($modelClass);
                Log::error("Failed to delete RVM ID {$rvmIdForLog} - delete() returned false.", [
                    'model_class' => $modelClass,
                    'model_uses_traits' => $modelUses,
                    'is_soft_deleting' => method_exists($modelClass, 'isForceDeleting')
                ]);
                return response()->json(['message' => 'Failed to delete RVM. Check model events or soft delete status.'], 500);
            }
        } catch (\Exception $e) {
            // Blok catch Exception (termasuk foreign key)
             $rvmIdForLog = $rvmId; // Gunakan ID dari parameter jika model gagal di-resolve sebelumnya
             Log::error("Failed to delete RVM ID {$rvmIdForLog}: " . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'foreign key constraint fails')) {
                 return response()->json(['message' => 'Cannot delete RVM because it has associated deposit records.'], 409); // Conflict
            }
            return response()->json(['message' => 'Failed to delete RVM.'], 500);
        }
    }

    // TODO: Mungkin perlu endpoint untuk me-regenerate API Key?
    // public function regenerateApiKey(ReverseVendingMachine $reverseVendingMachine) { ... }
}