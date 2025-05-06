<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Alpine.js (jika diperlukan di halaman lain) --}}
    {{-- <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .login-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="card login-card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">Login</h2>

            {{-- Tampilkan error global jika ada (misal 'auth.failed') --}}
            @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first('email') }} {{-- Menampilkan error 'auth.failed' dari email --}}
            </div>
            @endif
            {{-- Tampilkan pesan error dari redirect Google Auth --}}
            @if (session('error'))
            <div class="alert alert-danger py-2 small">
                {{ session('error') }}
            </div>
            @endif
            {{-- Tampilkan pesan status (misal setelah verifikasi) --}}
            @if (session('status'))
            <div class="alert alert-success py-2 small">
                {{ session('status') }}
            </div>
            @endif


            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" class="form-control @error('email', 'login') is-invalid @enderror" type="email"
                        name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    {{-- Error spesifik email jika ada selain auth.failed (jarang terjadi di sini) --}}
                    @error('email', 'login') @if($message != __('auth.failed')) <div class="invalid-feedback small">{{
                        $message }}</div> @endif @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" class="form-control @error('password', 'login') is-invalid @enderror"
                        type="password" name="password" required autocomplete="current-password">
                    @error('password', 'login') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-3">
                    <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                    <label for="remember_me" class="form-check-label small text-muted">Remember me</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Log in
                    </button>
                </div>

                <div class="text-center my-3 text-muted small">OR</div>

                {{-- Tombol Google Sign-In --}}
                <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline-danger w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-google me-2" viewBox="0 0 16 16">
                        <path
                            d="M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z" />
                    </svg>
                    Sign in with Google
                </a>

                <div class="text-center mt-3">
                    <a href="{{ route('register') }}" class="text-muted small">Don't have an account? Register</a>
                </div>

            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>