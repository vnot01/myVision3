<!-- < ?php

namespace App\Http\Middleware;

use App\Models\ReverseVendingMachine;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log; // Tambahkan Log untuk Debugging

class EnsureRvmIsAuthentic
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedApiKey = $request->header('X-Rvm-ApiKey');
        Log::info('RVM Auth Attempt: Provided Key Present: ' . ($providedApiKey ? 'Yes' : 'No')); // Debug

        if (!$providedApiKey) {
            return response()->json(['message' => 'RVM API Key missing.'], 401); // Pesan Seharusnya
        }

        $hashedApiKey = hash('sha256', $providedApiKey);
        Log::info('RVM Auth Attempt: Hashed Key: ' . $hashedApiKey); // Debug

        // Cari berdasarkan HASH
        $rvm = ReverseVendingMachine::where('api_key', $hashedApiKey)->first();

        if (!$rvm) {
             Log::warning('RVM Auth Failed: No RVM found for hashed key.', ['hashedKey' => $hashedApiKey]); // Debug
             // Pesan Seharusnya:
             return response()->json(['message' => 'Invalid RVM API Key.'], 401);
        }

        Log::info('RVM Auth Success: RVM Found: ID ' . $rvm->id . ', Status: ' . $rvm->status); // Debug

        if ($rvm->status !== 'active') {
            Log::warning('RVM Auth Failed: RVM not active.', ['rvm_id' => $rvm->id, 'status' => $rvm->status]); // Debug
             // Pesan Seharusnya:
             return response()->json(['message' => 'RVM is not active.'], 403);
        }

        // Jika semua OK, tambahkan ke request dan lanjutkan
        $request->attributes->add(['authentic_rvm' => $rvm]);
        return $next($request);
    }
} -->