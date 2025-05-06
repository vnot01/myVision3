<?php

namespace App\Http\Middleware;

use App\Models\ReverseVendingMachine; // Pastikan model di-import
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log; // Tambahkan Log untuk Debugging

class VerifyRvmApiKey // Nama class tetap sesuai nama file
{
    /**
     * Handle an incoming request.
     * Memastikan request berasal dari RVM yang valid via API Key
     * dengan membandingkan HASH.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil API Key dari Header (misalnya X-Rvm-ApiKey)
        $providedApiKey = $request->header('X-Rvm-ApiKey');
        Log::info('RVM Auth Attempt (VerifyRvmApiKey): Key Present: ' . ($providedApiKey ? 'Yes' : 'No')); // Debug

        if (!$providedApiKey) {
            // Kembalikan pesan yang lebih informatif
            return response()->json(['message' => 'RVM API Key missing in header (X-Rvm-ApiKey).'], 401);
        }

        // 2. Hash API Key yang diberikan (INI KUNCINYA)
        $hashedApiKey = hash('sha256', $providedApiKey);
        Log::info('RVM Auth Attempt (VerifyRvmApiKey): Provided Key Hashed: ' . $hashedApiKey); // Debug

        // 3. Cari RVM berdasarkan HASH API Key di database
        $rvm = ReverseVendingMachine::where('api_key', $hashedApiKey)->first();

        // 4. Cek apakah RVM ditemukan
        if (!$rvm) {
            Log::warning('RVM Auth Failed (VerifyRvmApiKey): No RVM found for hashed key.', ['hashedKey' => $hashedApiKey]); // Debug
            // Pesan error yang lebih spesifik (tetap tidak mengungkapkan terlalu banyak)
            return response()->json(['message' => 'Invalid RVM Credentials.'], 401);
        }

        // 5. Cek status RVM
        Log::info('RVM Auth Success (VerifyRvmApiKey): RVM Found: ID ' . $rvm->id . ', Status: ' . $rvm->status); // Debug
        if ($rvm->status !== 'active') {
            Log::warning('RVM Auth Failed (VerifyRvmApiKey): RVM not active.', ['rvm_id' => $rvm->id, 'status' => $rvm->status]); // Debug
            return response()->json(['message' => 'RVM is not active.'], 403); // Forbidden
        }

        // 6. Tambahkan informasi RVM ke request
        $request->attributes->add(['authenticated_rvm' => $rvm]); // Gunakan 'authenticated_rvm' agar konsisten

        // 7. Lanjutkan request jika RVM autentik
        return $next($request);
    }
}