<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOperatorRole
{
    /**
     * Handle an incoming request.
     * Memastikan user yang terotentikasi memiliki role Admin atau Operator.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Dapatkan user yang sudah terotentikasi (via Sanctum)

        // Jika tidak ada user ATAU role tidak sesuai
        if (!$user || !in_array($user->role, ['Admin', 'Operator'])) {
            // Jika request API, kembalikan error JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden: Insufficient privileges.'], 403);
            }
            // Jika request web biasa (meskipun ini untuk API), bisa redirect atau abort
            abort(403, 'Forbidden: Insufficient privileges.');
        }

        // Lanjutkan request jika role sesuai
        return $next($request);
    }
}