<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit; // Import model
use App\Models\ReverseVendingMachine;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Untuk agregasi

class DashboardController extends Controller
{
    /**
     * Get basic statistics for the dashboard.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $totalUsers = User::where('is_guest', false)->count();
            $totalRvms = ReverseVendingMachine::count();
            $activeRvms = ReverseVendingMachine::where('status', 'active')->count();
            $totalDeposits = Deposit::count();
            $totalPointsAwarded = Deposit::sum('points_awarded');
            // Contoh statistik deposit hari ini
            $depositsToday = Deposit::whereDate('deposited_at', today())->count();

            return response()->json([
                'total_users' => $totalUsers,
                'total_rvms' => $totalRvms,
                'active_rvms' => $activeRvms,
                'total_deposits' => $totalDeposits,
                'total_points_awarded' => (int) $totalPointsAwarded, // Cast ke integer
                'deposits_today' => $depositsToday,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve dashboard stats: ' . $e->getMessage());
            return response()->json(['message' => 'Could not retrieve statistics.'], 500);
        }
    }

    /**
     * List all Reverse Vending Machines.
     */
    public function listRvms(Request $request): JsonResponse
    {
        try {
            // Ambil semua RVM, mungkin dengan pagination jika banyak
            $rvms = ReverseVendingMachine::select('id', 'name', 'location', 'status', 'latitude', 'longitude', 'created_at')
                      // ->withCount('deposits') // Hitung jumlah deposit per RVM (opsional)
                      ->orderBy('name')
                      ->paginate(20); // Contoh pagination

            return response()->json($rvms);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve RVM list: ' . $e->getMessage());
            return response()->json(['message' => 'Could not retrieve RVM list.'], 500);
        }
    }

    /**
     * List all deposits with filters and pagination.
     */
    public function listDeposits(Request $request): JsonResponse
    {
        try {
            // Validasi filter (opsional)
            $request->validate([
                'rvm_id' => 'nullable|integer|exists:reverse_vending_machines,id',
                'user_id' => 'nullable|integer|exists:users,id',
                'detected_type' => 'nullable|string|in:mineral_plastic,other_bottle,unknown,contains_content',
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:5|max:100',
            ]);

            $query = Deposit::with(['user:id,name,email', 'rvm:id,name']) // Eager load relasi
                      ->latest('deposited_at'); // Urutkan terbaru dulu

            // Terapkan filter jika ada
            if ($request->filled('rvm_id')) {
                $query->where('rvm_id', $request->input('rvm_id'));
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }
            if ($request->filled('detected_type')) {
                $query->where('detected_type', $request->input('detected_type'));
            }
            if ($request->filled('start_date')) {
                $query->whereDate('deposited_at', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('deposited_at', '<=', $request->input('end_date'));
            }

            $perPage = $request->input('per_page', 25); // Default 25 per halaman
            $deposits = $query->paginate($perPage);

            return response()->json($deposits);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve deposit list: ' . $e->getMessage());
            return response()->json(['message' => 'Could not retrieve deposit list.'], 500);
        }
    }
}