**Error 1: `These credentials do not match our records.`**

*   **Penyebab:** Anda benar, error ini terjadi karena Anda mencoba login API dengan akun Google (`feri@unu-jogja.ac.id`) yang kolom `password`-nya di database adalah `NULL`. Fungsi `Hash::check($request->password, $user->password)` di `AuthController@login` akan gagal karena `$user->password` adalah `NULL`.
*   **Solusi untuk Login API Akun Google:** Anda **tidak bisa** menggunakan endpoint `/api/login` (yang memeriksa email/password) untuk akun yang dibuat via Google Sign-In. Otentikasi untuk akun Google harus selalu dimulai dari alur Google Sign-In itu sendiri (mengklik tombol "Sign in with Google").
    *   **Alur yang Benar untuk Aplikasi Klien (misal Mobile App):**
        1.  Aplikasi klien (misal mobile app) menggunakan SDK Google Sign-In untuk mendapatkan **ID Token** atau **Access Token** dari Google setelah user login di sisi klien.
        2.  Aplikasi klien mengirimkan token Google ini ke endpoint API **baru** di backend Laravel Anda (misalnya `POST /api/auth/google`).
        3.  Backend Laravel menggunakan token Google tersebut untuk memverifikasi identitas user dengan Google (bisa pakai library Socialite atau library Google API Client).
        4.  Jika verifikasi Google berhasil, backend Laravel mencari atau membuat user di database (seperti di `GoogleAuthController@handleGoogleCallback`) dan kemudian membuat **API token Sanctum** untuk user tersebut.
        5.  Backend mengembalikan token Sanctum ke aplikasi klien.
        6.  Aplikasi klien kemudian menggunakan token Sanctum ini untuk request API selanjutnya.
    *   **Kita belum membuat endpoint `POST /api/auth/google` ini.**

**Error 2: `This password does not use the Bcrypt algorithm.`**

*   **Penyebab:** Error ini muncul *setelah* Anda mengedit database secara manual dan memasukkan password `dr4gonlistio` sebagai **teks biasa (plain text)**. Fungsi `Hash::check()` mengharapkan password di database disimpan dalam format **hash Bcrypt** (string panjang yang diawali `$2y$...`). Karena ia menemukan teks biasa, ia tidak bisa membandingkannya dan mengeluarkan error tersebut.
*   **Solusi:** Anda **tidak boleh** menyimpan password sebagai plain text. Anda harus menyimpannya sebagai hash.
    *   **Cara Membuat Hash Manual (untuk testing):** Anda bisa menggunakan `php artisan tinker` di terminal:
        ```bash
        php artisan tinker
        ```
        Lalu di dalam tinker:
        ```php
        echo Hash::make('dr4gonlistio');
        ```
        Ini akan menghasilkan string hash Bcrypt (misalnya `$2y$12$...). Salin string hash ini.
    *   **Edit Database Lagi:** Masuk ke database Anda, temukan user `feri@unu-jogja.ac.id`, dan **ganti** nilai di kolom `password` dengan **string hash Bcrypt** yang baru saja Anda salin.
    *   **Coba Login API Lagi:** Gunakan Postman dengan email `feri@unu-jogja.ac.id` dan password `dr4gonlistio` (password asli, bukan hash). Sekarang `Hash::check()` seharusnya berhasil membandingkan password yang Anda kirim dengan hash di database.

**Kesimpulan dan Langkah Selanjutnya:**

1.  **Hapus Password Plain Text:** Perbaiki password di database untuk user `feri@unu-jogja.ac.id` dengan **hash Bcrypt** dari `dr4gonlistio` menggunakan `php artisan tinker`.
2.  **Uji Login API (User Normal):** Setelah password di-hash, coba lagi login API via Postman menggunakan email `feri@unu-jogja.ac.id` dan password `dr4gonlistio`. Ini **seharusnya berhasil** sekarang dan Anda akan mendapatkan token Sanctum.
3.  **Pahami Batasan Login API:** Ingat bahwa endpoint `POST /api/login` **hanya untuk user yang mendaftar dengan email/password**, bukan untuk yang mendaftar via Google.
4.  **(Langkah Berikutnya Jika Diperlukan):** Jika Anda butuh aplikasi klien (Mobile/Web App lain) untuk bisa login via Google dan mendapatkan token Sanctum, kita perlu **membuat endpoint API baru** (`POST /api/auth/google`) yang menerima token dari Google SDK klien dan mengembalikannya token Sanctum.

**SAMPLE**
```json
{
    "message": "Login successful",
    "access_token": "1|RbmTCj6RCOYNKKPxJVirvOV1GTkIqev73iiWn8aNcf7f9cd0",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "name": "Feri Febria Laksana",
        "email": "feri@unu-jogja.ac.id",
        "email_verified_at": "2025-05-05T14:26:49.000000Z",
        "created_at": "2025-05-05T14:26:13.000000Z",
        "updated_at": "2025-05-05T14:26:49.000000Z",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocJP10Hob3Wab9_3wDjYe-XeIjxU9B3Vjn6LMPYWW-gnOKSoTaM=s96-c",
        "phone_number": "085159458677",
        "phone_code": null,
        "phone_verified_at": null,
        "citizenship": "WNI",
        "identity_type": "KTP",
        "identity_number": "3375012502890003",
        "points": 0,
        "role": "User",
        "is_guest": false
    }
}
```

```bash
RVM ID: 2
Generated API Key (Simpan ini!): QjYuAhFABQyrkmLC82hnpXt13kmh8gPNXPOBRHj2
Hashed Key (Disimpan di DB): e2c81eaaab8b0df7302732db8c85b545b592d5f2aac3fca82b2e20a3e209882e
```