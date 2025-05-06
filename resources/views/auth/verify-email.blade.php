<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .verify-card {
            max-width: 550px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="card verify-card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title text-center mb-3">Verify Your Email Address</h2>

            <p class="text-center text-muted small mb-3">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the
                link we just emailed to you? If you didn't receive the email, we will gladly send you another.
            </p>

            @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success py-2 small mb-3" role="alert">
                A new verification link has been sent to the email address you provided during registration.
            </div>
            @endif

            @error('email') {{-- Menangkap error pengiriman ulang --}}
            <div class="alert alert-danger py-2 small mb-3">
                {{ $message }}
            </div>
            @enderror

            <div class="mt-4 d-flex align-items-center justify-content-between">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>