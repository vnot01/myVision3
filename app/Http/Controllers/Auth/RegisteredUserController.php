<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $googleInfo = session('google_user_info');
        return view('auth.register', ['googleInfo' => $googleInfo]);
    }

    public function store(Request $request): RedirectResponse
    {
        $googleInfo = $request->session()->get('google_user_info');
        $isGoogleRegistration = !empty($googleInfo);

        // --- Definisi $rules ---
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'citizenship' => ['required', 'in:WNI,WNA'],
            'identity_type' => ['required', function ($attribute, $value, $fail) use ($request) { /* ... validasi type vs citizenship ... */
                if ($request->input('citizenship') === 'WNI' && $value !== 'KTP') { $fail('Identity type must be KTP for WNI.'); }
                if ($request->input('citizenship') === 'WNA' && $value !== 'Pasport') { $fail('Identity type must be Pasport for WNA.'); }
            }],
        ];
        if (!$isGoogleRegistration) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class];
            $rules['password'] = ['required', 'confirmed', 'string', 'min:8', 'max:16'];
        } else {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255'];
        }

        // --- Validator & Aturan Sometimes ---
        $validator = Validator::make($request->all(), $rules);
        $validator->sometimes('identity_number', ['required','string','digits:16','unique:users,identity_number'], fn ($input) => $input->identity_type === 'KTP');
        $validator->sometimes('identity_number', ['required','string','regex:/^[a-zA-Z0-9]{8,12}$/','unique:users,identity_number'], fn ($input) => $input->identity_type === 'Pasport');

        // --- Validasi ---
        $validated = $validator->validate();

        // --- Siapkan Data User ---
         $userData = [
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'citizenship' => $validated['citizenship'],
            'identity_type' => $validated['identity_type'],
            'identity_number' => $validated['identity_number'],
            'points' => 0,
            'role' => 'User',
            'is_guest' => false,
        ];

         if ($isGoogleRegistration) {
             $userData['email'] = $googleInfo['email'];
             $userData['google_id'] = $googleInfo['id'];
             $userData['avatar'] = $googleInfo['avatar'];
             $userData['password'] = null;
             $userData['email_verified_at'] = now();
             // Hapus info google dari session SEKARANG, sebelum membuat user
             $request->session()->forget('google_user_info');
         } else {
             $userData['email'] = $validated['email'];
             $userData['password'] = Hash::make($validated['password']);
             // email_verified_at dibiarkan null
         }

         // ===== HANYA SATU KALI MEMBUAT USER =====
         $user = User::create($userData);

         // ===== HAPUS BLOK DUPLIKAT DARI SINI =====

         // Trigger event untuk kirim email verifikasi jika bukan dari Google
         if (!$isGoogleRegistration) {
              event(new Registered($user));
         }

         Auth::login($user);

         // Redirect (cek verifikasi email)
         if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
             return redirect()->route('verification.notice');
         }
         return redirect()->intended(route('vision.test.form', [], false));

    } // Akhir store()
}