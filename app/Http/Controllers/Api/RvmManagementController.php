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

        DB::beginTransaction();
        try {
            // Generate API Key baru
            $apiKey = Str::random(40);
            $hashedApiKey = hash('sha256', $apiKey);

            $rvm = ReverseVendingMachine::create([
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'status' => $request->input('status'),
                'api_key' => $hashedApiKey, // Simpan hash
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
     * Menggunakan Route Model Binding.
     */
    public function show(ReverseVendingMachine $reverseVendingMachine): JsonResponse
    {
        // $reverseVendingMachine sudah otomatis di-load berdasarkan {rvm} di URL
        // Kita bisa load relasi jika perlu
        // $reverseVendingMachine->loadCount('deposits');
        return response()->json($reverseVendingMachine);
    }

    /**
     * Update the specified RVM in storage.
     * Menggunakan Route Model Binding.
     */
    public function update(Request $request, ReverseVendingMachine $reverseVendingMachine): JsonResponse
    {
         // Validasi input untuk update
         // API Key biasanya tidak diupdate via endpoint ini, mungkin perlu endpoint terpisah
         $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255', // sometimes: hanya validasi jika ada di request
            'location' => 'sometimes|required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|required|string|in:active,inactive,maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Update hanya field yang ada di request (menggunakan fill + save)
            $reverseVendingMachine->fill($request->only(['name', 'location', 'latitude', 'longitude', 'status']));
            $reverseVendingMachine->save();

            return response()->json([
                'message' => 'RVM updated successfully.',
                'rvm' => $reverseVendingMachine
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update RVM ID ' . $reverseVendingMachine->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update RVM.'], 500);
        }
    }

    /**
     * Remove the specified RVM from storage.
     * Menggunakan Route Model Binding.
     * HATI-HATI: Pertimbangkan konsekuensi menghapus RVM (foreign key constraint, data historis).
     * Mungkin lebih baik menggunakan soft delete atau hanya menonaktifkan (mengubah status).
     */
    public function destroy(ReverseVendingMachine $reverseVendingMachine): JsonResponse
    {
        // PERINGATAN: Operasi destroy bisa berbahaya.
        // Opsi 1: Nonaktifkan saja (Soft Delete jika diaktifkan di model, atau ubah status)
        /*
        try {
            $reverseVendingMachine->status = 'inactive'; // Atau status 'deleted'
            $reverseVendingMachine->api_key = null; // Nonaktifkan key juga?
            $reverseVendingMachine->save();
            return response()->json(['message' => 'RVM deactivated successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to deactivate RVM ID ' . $reverseVendingMachine->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Failed to deactivate RVM.'], 500);
        }
        */

        // Opsi 2: Hapus permanen (jika benar-benar yakin dan constraint DB mengizinkan/cascade)
        try {
            $rvmId = $reverseVendingMachine->id;
            $reverseVendingMachine->delete();
             return response()->json(['message' => "RVM ID {$rvmId} deleted successfully."], 200); // Atau 204 No Content
        } catch (\Exception $e) {
             Log::error('Failed to delete RVM ID ' . $reverseVendingMachine->id . ': ' . $e->getMessage());
            // Cek apakah error karena foreign key constraint
             if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'foreign key constraint fails')) {
                  return response()->json(['message' => 'Cannot delete RVM because it has associated deposit records.'], 409); // Conflict
             }
             return response()->json(['message' => 'Failed to delete RVM.'], 500);
        }
    }

    // TODO: Mungkin perlu endpoint untuk me-regenerate API Key?
    // public function regenerateApiKey(ReverseVendingMachine $reverseVendingMachine) { ... }
}