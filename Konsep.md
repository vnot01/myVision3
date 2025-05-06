Membuat sistem multi-aplikasi seperti ini adalah tantangan yang bagus dan melibatkan banyak konsep berbeda. 
Pendekatan yang baik adalah memulai dengan **merancang inti sistem**: data (database), logika bisnis utama, dan bagaimana aplikasi-aplikasi ini akan berkomunikasi (kemungkinan besar melalui API).

**Proposal Arsitektur Umum:**

Kita bisa membayangkan arsitektur seperti ini:

1.  **Backend Inti (Central API - Laravel):** Ini akan menjadi otak sistem. Aplikasi Laravel yang sudah kita kembangkan bisa diperluas menjadi backend ini. Fungsinya:
    *   Mengelola database (Users, RVM, Transaksi Poin/Botol).
    *   Menangani Autentikasi (Login Email/Password, Google Sign-In, otentikasi untuk RVM dan Dashboard).
    *   Menyediakan API Endpoint untuk diakses oleh Aplikasi RVM, Aplikasi User, dan Aplikasi Dashboard.
    *   Menjalankan logika bisnis inti (perhitungan poin, validasi, agregasi data).
    *   (Opsional) Bisa juga *menghosting* Aplikasi Dashboard web jika kita membuatnya menyatu.

2.  **Aplikasi RVM (Perangkat Lunak di Mesin):** Ini bukan aplikasi web biasa. Ini adalah software yang berjalan di komputer/mikrokontroler di dalam RVM fisik. Bisa dibuat dengan Python, C++, atau bahkan mungkin web stack lokal sederhana. Fungsinya:
    *   Mengontrol perangkat keras (kamera, pintu, lampu LED, mekanisme peremas - *simulasi dulu*).
    *   Mengambil gambar botol.
    *   **Melakukan deteksi objek** (bisa menggunakan model lokal atau **memanggil API Backend Inti** yang terhubung ke Gemini Vision).
    *   **Mengirim hasil deteksi** dan **ID User** (setelah login via scan) ke API Backend Inti.
    *   **Menerima respons** dari API (poin diberikan, atau peringatan) dan mengaktifkan hardware yang sesuai (lampu, pesan di layar RVM).
    *   Mengelola *state* mesin (siap menerima, sedang memproses, dll.).

3.  **Aplikasi User (Mobile/Web App):** Bisa berupa aplikasi mobile (React Native, Flutter) atau Progressive Web App (PWA). Fungsinya:
    *   Signup (Email/Password, Google) / Login / Guest Mode.
    *   Menampilkan profil user dan jumlah poin.
    *   **Menghasilkan QR Code/Token** unik dan sementara untuk login ke RVM.
    *   Memanggil API Backend Inti untuk semua operasinya.

4.  **Aplikasi Dashboard (Web App):** Aplikasi web untuk monitoring. Fungsinya:
    *   Login untuk Admin/Operator/User (dengan role berbeda).
    *   Menampilkan data agregat (total poin, total botol, grafik) dengan memanggil API Backend Inti.
    *   (Opsional) Mengelola lokasi RVM, pengguna, dll. (tergantung kebutuhan akses).
    *   Menyertakan fitur Vision Test yang sudah ada.

**Mari Mulai dari Tahap 1: Merancang Backend Inti & Database**

Fokus pertama kita adalah menyiapkan fondasi di aplikasi Laravel yang sudah ada (atau bisa juga buat proyek baru jika dirasa lebih bersih).

**A. Desain Database:**

Kita perlu menambahkan beberapa tabel baru dan memodifikasi tabel `users`.

1.  **Tabel `users` (Modifikasi):**
    *   `id` (PK)
    *   `name` (string)
    *   `email` (string, unique, nullable jika guest diizinkan tanpa email?)
    *   `email_verified_at` (timestamp, nullable)
    *   `password` (string, nullable)
    *   `google_id` (string, nullable, unique)
    *   `avatar` (string, nullable)
    *   `phone_number` (string, nullable)
    *   `citizenship` (enum['WNI', 'WNA'], nullable)
    *   `identity_type` (enum['KTP', 'Pasport'], nullable)
    *   `identity_number` (string, nullable, **unique** - *sudah dibuat*)
    *   `points` (integer, default 0) - **BARU**: Menyimpan total poin user.
    *   `role` (enum['Admin', 'Operator', 'User'], default 'User') - **BARU**: Untuk akses dashboard.
    *   `is_guest` (boolean, default false) - **BARU**: Menandai akun guest.
    *   `remember_token`
    *   `created_at`, `updated_at`

2.  **Tabel `reverse_vending_machines` (Baru):**
    *   `id` (PK)
    *   `name` (string, e.g., "RVM Lobby Gedung A")
    *   `location` (string/text, e.g., "Lantai 1, dekat pintu masuk utara")
    *   `latitude` (decimal, nullable) - **BARU (Opsional)**: Untuk pemetaan di dashboard.
    *   `longitude` (decimal, nullable) - **BARU (Opsional)**
    *   `status` (enum['active', 'inactive', 'maintenance'], default 'active') - **BARU**
    *   `api_key` (string, unique, nullable) - **BARU**: Untuk otentikasi RVM ke API (opsional, bisa pakai metode lain).
    *   `created_at`, `updated_at`

3.  **Tabel `deposits` (Baru):** Tabel ini mencatat setiap transaksi penyetoran botol.
    *   `id` (PK)
    *   `user_id` (FK ke `users.id`) - Bisa nullable jika guest benar-benar anonim? Atau kita buat user guest temporary? *Perlu diputuskan.* Untuk saat ini, anggap perlu user (meski guest).
    *   `rvm_id` (FK ke `reverse_vending_machines.id`)
    *   `detected_type` (enum['mineral_plastic', 'other_bottle', 'unknown', 'contains_content']) - **BARU**: Hasil deteksi dari RVM/API.
    *   `points_awarded` (integer) - **BARU**: Poin yang diberikan untuk deposit ini.
    *   `needs_action` (boolean, default false) - **BARU**: Flag jika user perlu mengambil kembali botol (misal karena ada isi).
    *   `deposited_at` (timestamp, default current) - **BARU**
    *   `created_at`, `updated_at`

**B. Rencana Aksi Selanjutnya (Fokus Backend):**

1.  **Buat Migrations:** Buat file migration untuk tabel `reverse_vending_machines` dan `deposits`. Buat juga migration untuk memodifikasi tabel `users` (menambah kolom `points`, `role`, `is_guest`, dan foreign key constraint jika diperlukan).
2.  **Buat Model:** Buat model Eloquent `ReverseVendingMachine` dan `Deposit`. Update model `User` untuk relasi (misalnya `hasMany(Deposit::class)`).
3.  **Perluas Controller & Routes:**
    *   Modifikasi `RegisteredUserController` dan `GoogleAuthController` untuk menangani `role`, `points` (default 0), dan `is_guest`.
    *   Buat API Controller baru (misalnya `Api/RvmController.php` dan `Api/DepositController.php`) untuk menangani request dari Aplikasi RVM.
    *   Buat API Controller baru (misalnya `Api/UserController.php` dan `Api/DashboardController.php`) untuk Aplikasi User dan Dashboard.
    *   Definisikan route API di `routes/api.php` yang dilindungi oleh Sanctum atau Passport untuk otentikasi API.

**Mana yang ingin Anda kerjakan terlebih dahulu?** Kita mulai dengan:

1.  **Membuat migration** untuk tabel baru dan modifikasi tabel `users`.
2.  **Membuat model** `ReverseVendingMachine` dan `Deposit` serta memperbarui model `User`.