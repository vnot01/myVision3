<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Alpine.js WAJIB untuk field kondisional --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: #f8f9fa;
        }

        .register-card {
            max-width: 600px;
            margin: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card register-card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Create Account</h2>

                {{-- Tampilkan error validasi umum --}}
                @if ($errors->any())
                <div class="alert alert-danger py-2 small mb-3">
                    Please fix the errors below.
                </div>
                @endif

                {{-- Form registrasi --}}
                {{-- Menggunakan $googleInfo dari RegisteredUserController@create --}}
                <form method="POST" action="{{ route('register') }}"
                    x-data="{ citizenship: '{{ old('citizenship') }}', identityTypeKtp: 'KTP', identityTypePassport: 'Pasport' }">
                    @csrf

                    {{-- Input Nama (Readonly jika dari Google) --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required
                            autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Input Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Input Nomor HP --}}
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number (e.g., +62812xxxx)</label>
                        <input type="tel" name="phone_number" id="phone_number"
                            class="form-control @error('phone_number') is-invalid @enderror"
                            value="{{ old('phone_number') }}" required>
                        @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Pilihan Kewarganegaraan --}}
                    <div class="mb-3">
                        <label for="citizenship" class="form-label">Citizenship</label>
                        <select name="citizenship" id="citizenship"
                            class="form-select @error('citizenship') is-invalid @enderror" x-model="citizenship"
                            required>
                            <option value="" disabled {{ old('citizenship') ? '' : 'selected' }}>Select Citizenship
                            </option>
                            <option value="WNI" {{ old('citizenship')=='WNI' ? 'selected' : '' }}>WNI (Indonesian
                                Citizen)</option>
                            <option value="WNA" {{ old('citizenship')=='WNA' ? 'selected' : '' }}>WNA (Foreign Citizen)
                            </option>
                        </select>
                        @error('citizenship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Input Identitas (Kondisional) --}}
                    {{-- Blok KTP --}}
                    <div class="mb-3" x-show="citizenship === 'WNI'" x-transition>
                        <label for="identity_number_ktp" class="form-label">KTP Number (NIK - 16 digits)</label>
                        {{-- Pastikan hidden input ini juga punya disabled binding --}}
                        <input type="hidden" name="identity_type" x-bind:value="identityTypeKtp"
                            x-bind:disabled="citizenship !== 'WNI'">
                        <input type="text" name="identity_number" id="identity_number_ktp"
                            class="form-control @if($errors->has('identity_number') && old('citizenship') == 'WNI') is-invalid @endif"
                            value="{{ old('citizenship') == 'WNI' ? old('identity_number') : '' }}" pattern="\d{16}"
                            title="Must be 16 digits" x-bind:required="citizenship === 'WNI'"
                            x-bind:disabled="citizenship !== 'WNI'"> {{--
                        << PASTIKAN ADA INI --}} @if ($errors->has('identity_number') && old('citizenship') == 'WNI')
                            <div class="invalid-feedback">{{ $errors->first('identity_number') }}</div>
                            @endif
                    </div>

                    {{-- Blok Paspor --}}
                    <div class="mb-3" x-show="citizenship === 'WNA'" x-transition>
                        <label for="identity_number_passport" class="form-label">Passport Number (8-12
                            alphanumeric)</label>
                        {{-- Pastikan hidden input ini juga punya disabled binding --}}
                        <input type="hidden" name="identity_type" x-bind:value="identityTypePassport"
                            x-bind:disabled="citizenship !== 'WNA'">
                        <input type="text" name="identity_number" id="identity_number_passport"
                            class="form-control @if($errors->has('identity_number') && old('citizenship') == 'WNA') is-invalid @endif"
                            value="{{ old('citizenship') == 'WNA' ? old('identity_number') : '' }}"
                            pattern="[a-zA-Z0-9]{8,12}" title="Must be 8-12 alphanumeric"
                            x-bind:required="citizenship === 'WNA'" x-bind:disabled="citizenship !== 'WNA'"> {{-- <<
                            PASTIKAN ADA INI --}} @if ($errors->has('identity_number') &&
                            old('citizenship') == 'WNA')
                            <div class="invalid-feedback">{{ $errors->first('identity_number') }}</div>
                            @endif
                    </div>
                    {{-- Akhir Input Identitas --}}


                    {{-- Input Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (min 8, max 16 chars)</label>
                        <input type="password" name="password" id="password"
                            class="form-control @error('password') is-invalid @enderror" required
                            autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control" required autocomplete="new-password">
                    </div>


                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>

                    <div class="text-center my-3 text-muted small">OR</div>

                    {{-- Tombol Google Sign-In --}}
                    <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline-danger w-100 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-google me-2" viewBox="0 0 16 16">
                            <path
                                d="M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z" />
                        </svg>
                        Sign up with Google
                    </a>

                    <div class="text-center">
                        <a href="{{ route('login') }}" class="text-muted small">Already registered? Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>