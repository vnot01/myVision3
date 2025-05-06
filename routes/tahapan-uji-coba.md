**Alur tokennya**
---
menggunakan `php artisan tinker` memang cocok untuk **tahap persiapan/setup** simulasi, bukan bagian dari *runtime* simulasi.

Tahapan simulasi pengujiannya:
---

**Tahap 0: Persiapan Simulasi (Setup Awal)**

1.  **Pastikan User Ada:** Miliki data user di database yang terdaftar via email/password (bukan Google Auth) dan emailnya sudah terverifikasi. Contoh: `feri@unu-jogja.ac.id` / password `dr4gonlistio` (password di DB harus berupa **hash Bcrypt**).
2.  **Pastikan RVM Ada & Dapatkan API Key Asli:**
    *   Pastikan ada data RVM di tabel `reverse_vending_machines` dengan `status='active'` (misal ID=2).
    *   Kolom `api_key` di database **harus berisi HASH SHA256** dari API Key asli.
    *   Anda **harus tahu API Key ASLI** (40 karakter random) yang sesuai dengan hash tersebut.
    *   *Jika Anda tidak tahu/lupa Key Asli:* Gunakan `php artisan tinker` untuk membuat/mengganti key pada RVM yang ada (misal ID=2) dan **catat Key Asli yang ditampilkan Tinker**:
        ```bash
        php artisan tinker
        ```
        ```php
        $rvm = App\Models\ReverseVendingMachine::find(2);
        if ($rvm) {
            $apiKey = Illuminate\Support\Str::random(40);
            $rvm->api_key = hash('sha256', $apiKey); // Simpan HASH
            $rvm->save();
            echo "CATAT API Key Asli untuk RVM ID " . $rvm->id . ": " . $apiKey . "\n"; // <-- CATAT INI
        } else { echo "RVM ID 2 tidak ditemukan.\n"; }
        exit
        ```
    *   Kita sebut API Key asli yang Anda catat ini sebagai `RVM_ORIGINAL_API_KEY`.

---

**Tahap Simulasi Pengujian API (Menggunakan Postman):**

**Tahap 1: [Simulasi Aplikasi User] - Login & Dapatkan Token Sanctum**

1.  **Metode:** `POST`
2.  **URL:** `http://localhost:8000/api/login`
3.  **Headers:** `Accept: application/json`, `Content-Type: application/json`
4.  **Body (raw, JSON):**
    ```json
    {
        "email": "feri@unu-jogja.ac.id",
        "password": "dr4gonlistio", // Password asli
        "device_name": "Simulasi App User Login"
    }
    ```
5.  **Kirim.**
6.  **Simpan `access_token`** dari respons JSON. Kita sebut ini `SANCTUM_TOKEN`.

**Tahap 2: [Simulasi Aplikasi User] - Generate Token RVM Sementara**

1.  **Metode:** `POST`
2.  **URL:** `http://localhost:8000/api/user/rvm-token`
3.  **Headers:** `Accept: application/json`, `Authorization: Bearer SANCTUM_TOKEN` (Ganti `SANCTUM_TOKEN` dengan nilai dari Tahap 1).
4.  **Body:** Kosong.
5.  **Kirim.**
6.  **Simpan `rvm_token`** dari respons JSON. Kita sebut ini `RVM_ACCESS_TOKEN`.

**Tahap 3: [Simulasi Mesin RVM] - Kirim Data Deposit**

1.  **Metode:** `POST`
2.  **URL:** `http://localhost:8000/api/deposits`
3.  **Headers:**
    *   `Accept: application/json`
    *   `Content-Type: application/json`
    *   `X-Rvm-ApiKey: RVM_ORIGINAL_API_KEY` (Ganti dengan **API Key Asli 40 karakter** dari Tahap 0).
4.  **Body (raw, JSON):**
    ```json
    {
        "detected_type": "mineral_plastic", // Atau tipe lain
        "rvm_token": "RVM_ACCESS_TOKEN"     // Ganti dengan token dari Tahap 2
    }
    ```
5.  **Kirim.**
6.  **Verifikasi Hasil:** Periksa apakah respons JSON menunjukkan status `success` (atau `warning` jika `needs_action`), deposit tercatat, dan poin user (jika ada) bertambah.

---

Urutannya dan Peran setiap token dan API Key.