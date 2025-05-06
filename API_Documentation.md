# Dokumentasi API Sistem RVM & Poin Visi
Dokumentasi ini mencakup endpoint utama yang telah Dibangun dan cara kerjanya.

## Daftar Isi

1.  [Pendahuluan](#pendahuluan)
2.  [Base URL](#base-url)
3.  [Autentikasi](#autentikasi)
    *   [Autentikasi User (Sanctum)](#autentikasi-user-sanctum)
    *   [Autentikasi RVM (API Key)](#autentikasi-rvm-api-key)
4.  [Endpoint Autentikasi](#endpoint-autentikasi)
    *   [POST /api/login](#post-apilogin)
    *   [POST /api/auth/google](#post-apiauthgoogle)
    *   [POST /api/logout (Sanctum)](#post-apilogout-sanctum)
5.  [Endpoint User (Memerlukan Autentikasi Sanctum)](#endpoint-user-memerlukan-autentikasi-sanctum)
    *   [GET /api/user](#get-apiuser)
    *   [GET /api/user/profile](#get-apiuserprofile)
    *   [POST /api/user/rvm-token](#post-apiuserrvm-token)
    *   [GET /api/user/points](#get-apiuserpoints)
    *   [GET /api/user/deposits](#get-apiuserdeposits)
6.  [Endpoint RVM (Memerlukan Autentikasi RVM API Key)](#endpoint-rvm-memerlukan-autentikasi-rvm-api-key)
    *   [POST /api/deposits](#post-apideposits)
7.  [Endpoint Dashboard (Memerlukan Autentikasi Sanctum & Role)](#endpoint-dashboard-memerlukan-autentikasi-sanctum--role)
    *   [GET /api/dashboard/stats](#get-apidashboardstats)
    *   [GET /api/dashboard/rvms](#get-apidashboardrvms)
    *   [GET /api/dashboard/deposits](#get-apidashboarddeposits)

---

## 1. Pendahuluan

Dokumentasi ini menjelaskan endpoint API untuk sistem Reverse Vending Machine (RVM) dan manajemen poin pengguna. API ini digunakan oleh Aplikasi User (Mobile/Web), perangkat lunak RVM, dan Aplikasi Dashboard Admin/Operator.

Semua request dan response menggunakan format JSON. Klien harus selalu menyertakan header `Accept: application/json`. Untuk request `POST` atau `PUT` yang mengirim data, sertakan juga header `Content-Type: application/json`.

## 2. Base URL

URL dasar untuk semua endpoint API adalah:

```
http://localhost:8000/api
```

(Ganti `localhost:8000` dengan domain/IP dan port server Anda jika berbeda).

## 3. Autentikasi

API ini menggunakan dua mekanisme autentikasi utama:

### Autentikasi User (Sanctum)

Endpoint yang ditujukan untuk pengguna yang sudah login (misalnya dari Aplikasi User atau Dashboard) memerlukan autentikasi menggunakan Laravel Sanctum API Token.

1.  **Mendapatkan Token:** User harus login terlebih dahulu melalui endpoint `/api/login` (Email/Password) atau `/api/auth/google` (Google Sign-In via ID Token dari Klien). Respons sukses akan berisi `access_token` Sanctum.
2.  **Menggunakan Token:** Untuk setiap request ke endpoint yang dilindungi Sanctum, sertakan header:
    ```
    Authorization: Bearer <SANCTUM_TOKEN>
    ```
    Ganti `<SANCTUM_TOKEN>` dengan token lengkap yang didapat saat login.

### Autentikasi RVM (API Key)

Endpoint yang ditujukan untuk diakses oleh mesin RVM fisik memerlukan autentikasi menggunakan API Key unik per RVM.

1.  **Setup:** Setiap RVM yang terdaftar di database harus memiliki **HASH SHA256** dari API Key uniknya yang tersimpan di kolom `api_key`. API Key **asli** (misalnya 40 karakter random) harus dikonfigurasikan di perangkat RVM.
2.  **Menggunakan Key:** Untuk setiap request dari RVM ke endpoint yang dilindungi, sertakan header:
    ```
    X-Rvm-ApiKey: <RVM_ORIGINAL_API_KEY>
    ```
    Ganti `<RVM_ORIGINAL_API_KEY>` dengan API Key **asli** yang dikonfigurasikan di RVM tersebut. Backend akan menghash key ini dan membandingkannya dengan hash di database.

## 4. Endpoint Autentikasi

Endpoint ini digunakan untuk proses login dan logout API.

### POST /api/login

*   **Deskripsi:** Mengautentikasi pengguna berdasarkan email dan password. Mengembalikan token Sanctum jika berhasil. Hanya untuk user yang mendaftar via email/password.
*   **Autentikasi:** Tidak diperlukan.
*   **Request Body (JSON):**
    ```json
    {
        "email": "user@example.com",
        "password": "userpassword",
        "device_name": "Nama Perangkat Klien (misal: MyAndroidApp)"
    }
    ```
*   **Respons Sukses (200 OK):**
    ```json
    {
        "message": "Login successful",
        "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer",
        "user": { /* Objek User */ }
    }
    ```
*   **Respons Error:**
    *   `422 Unprocessable Entity`: Validasi gagal (email/password salah format atau kosong).
    *   `422 Unprocessable Entity` (dengan error 'email'): Kredensial tidak cocok (`auth.failed`).
    *   `403 Forbidden`: Email belum diverifikasi.

### POST /api/auth/google

*   **Deskripsi:** Mengautentikasi pengguna berdasarkan ID Token yang didapatkan dari Google Sign-In SDK di sisi klien. Mencari/membuat user dan mengembalikan token Sanctum.
*   **Autentikasi:** Tidak diperlukan.
*   **Request Body (JSON):**
    ```json
    {
        "id_token": "ID_TOKEN_DARI_GOOGLE_SDK_KLIEN",
        "device_name": "Nama Perangkat Klien (misal: MyWebApp)"
    }
    ```
*   **Respons Sukses (200 OK):**
    ```json
    {
        "message": "Google Sign-In successful",
        "access_token": "2|yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
        "token_type": "Bearer",
        "user": { /* Objek User yang login/dibuat */ }
    }
    ```
*   **Respons Error:**
    *   `422 Unprocessable Entity`: `id_token` atau `device_name` tidak ada.
    *   `401 Unauthorized`: `id_token` tidak valid, kedaluwarsa, atau gagal diverifikasi oleh Google.
    *   `500 Internal Server Error`: Gagal membuat user atau token Sanctum.

### POST /api/logout (Sanctum)

*   **Deskripsi:** Logout pengguna dari sesi API saat ini dengan menghapus token Sanctum yang digunakan.
*   **Autentikasi:** **Sanctum Bearer Token** diperlukan.
*   **Request Body:** Tidak perlu.
*   **Respons Sukses (200 OK):**
    ```json
    {
        "message": "Logged out successfully"
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`: Token tidak valid atau tidak ada.

## 5. Endpoint User (Memerlukan Autentikasi Sanctum)

Endpoint berikut memerlukan header `Authorization: Bearer <SANCTUM_TOKEN>` yang valid.

### GET /api/user

*   **Deskripsi:** Mendapatkan informasi dasar tentang user yang sedang login (berdasarkan token).
*   **Autentikasi:** Sanctum Bearer Token.
*   **Respons Sukses (200 OK):**
    ```json
    {
        // ... atribut user (id, name, email, avatar, points, role, etc.) ...
        "deposits_count": 5 // Contoh jika menggunakan loadCount()
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.

### GET /api/user/profile

*   **Deskripsi:** (Alternatif/Sama seperti /api/user) Mendapatkan informasi profil lengkap user yang sedang login.
*   **Autentikasi:** Sanctum Bearer Token.
*   **Respons Sukses (200 OK):** Sama seperti `GET /api/user`.
*   **Respons Error:**
    *   `401 Unauthorized`.

### POST /api/user/rvm-token

*   **Deskripsi:** Menghasilkan token sementara (berlaku singkat, sekali pakai) yang bisa ditampilkan sebagai QR Code oleh Aplikasi User untuk digunakan saat berinteraksi dengan RVM.
*   **Autentikasi:** Sanctum Bearer Token.
*   **Request Body:** Tidak perlu.
*   **Respons Sukses (200 OK):**
    ```json
    {
        "message": "RVM token generated successfully.",
        "rvm_token": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4", // Token 32 karakter
        "expires_in": 60 // Detik masa berlaku
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.

### GET /api/user/points

*   **Deskripsi:** Mendapatkan jumlah poin terkini milik user yang sedang login.
*   **Autentikasi:** Sanctum Bearer Token.
*   **Respons Sukses (200 OK):**
    ```json
    {
        "points": 150
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.

### GET /api/user/deposits

*   **Deskripsi:** Mendapatkan riwayat transaksi deposit milik user yang sedang login, diurutkan dari terbaru, dengan pagination.
*   **Autentikasi:** Sanctum Bearer Token.
*   **Parameter Query (Opsional):**
    *   `?page=N`: Untuk mengakses halaman ke-N (default halaman 1).
    *   `?per_page=X`: Mengubah jumlah item per halaman (default 15).
*   **Respons Sukses (200 OK):** Objek pagination standar Laravel.
    ```json
    {
        "current_page": 1,
        "data": [
            {
                "id": 5,
                "user_id": 1,
                "rvm_id": 2,
                "detected_type": "mineral_plastic",
                "points_awarded": 10,
                "needs_action": false,
                "deposited_at": "...",
                "rvm": { // Data RVM terkait (eager loaded)
                    "id": 2,
                    "name": "RVM Kantin",
                    "location": "Sebelah Kopi"
                }
            },
            // ... deposit lainnya ...
        ],
        // ... metadata pagination (total, per_page, next_page_url, etc.) ...
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.

## 6. Endpoint RVM (Memerlukan Autentikasi RVM API Key)

Endpoint berikut memerlukan header `X-Rvm-ApiKey: <RVM_ORIGINAL_API_KEY>` yang valid.

### POST /api/deposits

*   **Deskripsi:** Menerima data deposit baru dari RVM setelah RVM memproses botol dan memindai token user dari QR Code. Mencatat deposit, memperbarui poin user, dan mengembalikan status.
*   **Autentikasi:** RVM API Key (via header `X-Rvm-ApiKey`).
*   **Request Body (JSON):**
    ```json
    {
        "detected_type": "mineral_plastic | other_bottle | unknown | contains_content",
        "rvm_token": "TOKEN_DARI_QR_CODE_USER" // Token 32 karakter dari /api/user/rvm-token
    }
    ```
*   **Respons Sukses (201 Created):**
    ```json
    {
        "status": "success", // atau "warning" jika needs_action true
        "message": "Mineral bottle accepted.", // Pesan sesuai hasil deteksi
        "deposit_details": {
            "id": 6,
            "detected_type": "mineral_plastic",
            "points_awarded": 10,
            "needs_action": false
        },
        "user_points_new_total": 160 // Poin user setelah ditambah
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`: API Key RVM tidak ada atau tidak valid (`Invalid RVM Credentials.`).
    *   `403 Forbidden`: RVM tidak aktif (`RVM is not active.`).
    *   `400 Bad Request`: `rvm_token` tidak valid, kedaluwarsa, atau sudah dipakai.
    *   `422 Unprocessable Entity`: `detected_type` atau `rvm_token` tidak valid formatnya.
    *   `404 Not Found`: User yang terkait dengan `rvm_token` tidak ditemukan.
    *   `500 Internal Server Error`: Gagal memproses deposit atau query database.

## 7. Endpoint Dashboard (Memerlukan Autentikasi Sanctum & Role)

Endpoint berikut memerlukan header `Authorization: Bearer <SANCTUM_TOKEN>` yang valid **DAN** user yang login harus memiliki role `Admin` atau `Operator`.

### GET /api/dashboard/stats

*   **Deskripsi:** Mendapatkan data statistik ringkasan untuk ditampilkan di dashboard.
*   **Autentikasi:** Sanctum Bearer Token + Role Admin/Operator.
*   **Respons Sukses (200 OK):**
    ```json
    {
        "total_users": 150,
        "total_rvms": 10,
        "active_rvms": 8,
        "total_deposits": 1234,
        "total_points_awarded": 11500,
        "deposits_today": 55
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`: User tidak memiliki role yang sesuai.
    *   `500 Internal Server Error`.

### GET /api/dashboard/rvms

*   **Deskripsi:** Mendapatkan daftar semua RVM (paginated).
*   **Autentikasi:** Sanctum Bearer Token + Role Admin/Operator.
*   **Parameter Query (Opsional):**
    *   `?page=N`: Halaman ke-N.
    *   `?per_page=X`: Item per halaman.
*   **Respons Sukses (200 OK):** Objek pagination Laravel berisi daftar RVM.
    ```json
    {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "RVM Lobby Gedung A",
                "location": "...",
                "status": "active",
                "latitude": ...,
                "longitude": ...,
                "created_at": "..."
            },
            // ... rvm lainnya ...
        ],
        // ... metadata pagination ...
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `500 Internal Server Error`.

### GET /api/dashboard/deposits

*   **Deskripsi:** Mendapatkan daftar semua transaksi deposit (paginated) dengan opsi filter.
*   **Autentikasi:** Sanctum Bearer Token + Role Admin/Operator.
*   **Parameter Query (Opsional untuk Filter):**
    *   `?page=N`: Halaman ke-N.
    *   `?per_page=X`: Item per halaman.
    *   `?rvm_id=ID`: Filter berdasarkan RVM ID.
    *   `?user_id=ID`: Filter berdasarkan User ID.
    *   `?detected_type=TYPE`: Filter berdasarkan tipe deteksi.
    *   `?start_date=YYYY-MM-DD`: Filter mulai tanggal.
    *   `?end_date=YYYY-MM-DD`: Filter sampai tanggal.
*   **Respons Sukses (200 OK):** Objek pagination Laravel berisi daftar deposit dengan data user dan RVM terkait.
    ```json
    {
        "current_page": 1,
        "data": [
            {
                "id": 6,
                "user_id": 1,
                "rvm_id": 2,
                "detected_type": "mineral_plastic",
                "points_awarded": 10,
                "needs_action": false,
                "deposited_at": "...",
                "user": { // Data User terkait
                    "id": 1,
                    "name": "...",
                    "email": "..."
                },
                "rvm": { // Data RVM terkait
                    "id": 2,
                    "name": "..."
                }
            },
            // ... deposit lainnya ...
        ],
        // ... metadata pagination ...
    }
    ```
*   **Respons Error:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `422 Unprocessable Entity`: Jika parameter filter tidak valid.
    *   `500 Internal Server Error`.