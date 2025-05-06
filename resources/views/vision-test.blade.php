<!DOCTYPE html>
<html lang="en">

<head>
    {{-- ... (meta, title, css, style seperti sebelumnya) ... --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vision Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Style CSS sebelumnya (image-container, bounding-box, dll) tetap di sini */
        .image-container {
            position: relative;
            display: inline-block;
            max-width: 100%;
            border: 1px solid #dee2e6;
            overflow: visible;
        }
        .image-container img {
            display: block;
            max-width: 100%;
            height: auto;
        }
        .bounding-box {
            position: absolute;
            border: 2px solid #dc3545;
            box-sizing: border-box;
            pointer-events: none;
        }
        .bounding-box .label {
            position: absolute;
            bottom: -22px;
            left: -2px;
            white-space: nowrap;
            font-size: 0.75rem;
            border-radius: .25rem;
            z-index: 10;
        }
    </style>
</head>

<body>
    <div class="container mt-4 mb-5"> {{-- Margin standar --}}
        <header class="text-center mb-3"> {{-- Margin bawah untuk header --}}
            <h1>Gemini Vision - Bottle Detector</h1>
            {{-- Hapus atau sesuaikan lead jika perlu --}}
            {{-- <p class="lead">Upload an image to detect bottles.</p> --}}
        </header>

        {{-- === AREA TOMBOL NAVIGASI === --}}
        <div class="d-flex justify-content-center gap-2 mb-4"> {{-- Flexbox, center, beri jarak antar tombol (gap), margin bawah --}}
            {{-- Tombol Logout --}}
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    Logout ({{ Auth::user()->name }})
                </button>
            </form>

            {{-- Tombol Analyze New Image (Home) --}}
            <a href="{{ route('vision.test.form') }}" class="btn btn-outline-secondary btn-sm">
                Analyze New Image
            </a>
        </div>
        {{-- === AKHIR AREA TOMBOL NAVIGASI === --}}


        {{-- Form Row --}}
        <div class="row justify-content-center mb-3">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4"> {{-- Kembalikan padding jika perlu --}}
                        <form action="{{ route('vision.test.analyze') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            {{-- ... (Input file, error handling, tombol analyze seperti sebelumnya) ... --}}
                            <div class="mb-3">
                                <label for="image" class="form-label visually-hidden">Upload Image:</label>
                                <input type="file" name="image" id="image"
                                    class="form-control @error('image') is-invalid @enderror" accept="image/*" required
                                    aria-label="Upload image file">
                                @error('image')
                                <div class="invalid-feedback small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($errors->any() && !$errors->has('image') || isset($errorMessage))
                            <div class="alert alert-danger alert-dismissible fade show py-1 px-2 small mb-2" role="alert">
                                @if(isset($errorMessage)) <strong>Error:</strong> {{ $errorMessage }}
                                @else <strong>Error:</strong> Fix input issues. @endif
                                <button type="button" class="btn-close py-1 px-2" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
                            </div>
                            @endif
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search me-1" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                                    Analyze
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Results Row --}}
        @if (isset($results) && isset($uploadedImageDataUri))
        <div class="row justify-content-center mt-4"> {{-- Beri margin atas jika hasil ada --}}
            <div class="col-md-10 col-lg-8 text-center">
                 <h2 class="mb-3 h4">Results:</h2> {{-- Kembalikan ukuran heading hasil --}}

                {{-- Area Display Gambar (Kembalikan style sebelumnya jika mau) --}}
                {{-- Jika style .image-display-area sebelumnya membuat box hilang, jangan gunakan itu --}}
                {{-- Gunakan style .image-container yang sudah pasti jalan --}}
                <div class="image-container mx-auto mb-3">
                    <img src="{{ $uploadedImageDataUri }}" alt="Analyzed Image" class="img-fluid">
                     @foreach ($results['boundingBoxes'] as $box)
                        <div class="bounding-box" style="left: {{ $box['x'] * 100 }}%; top: {{ $box['y'] * 100 }}%; width: {{ $box['width'] * 100 }}%; height: {{ $box['height'] * 100 }}%;">
                            <span class="label bg-danger text-white px-2 py-1"> {{-- Kembalikan padding label --}}
                                {{ $box['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- ... (Pesan hasil seperti sebelumnya) ... --}}
                @if (empty($results['boundingBoxes']))
                 <div class="alert alert-warning py-2 small mt-2" role="alert">No objects detected.</div>
                @else
                 <p class="text-muted small mt-2 mb-0">Detected {{ count($results['boundingBoxes']) }} item(s).</p>
                @endif

            </div>
        </div>
        {{-- ... (Handle kasus POST tanpa hasil) ... --}}
        @elseif (request()->isMethod('post') && !isset($errorMessage) && !isset($results) && !$errors->any())
         <div class="row justify-content-center mt-4"><div class="col-md-10 col-lg-8"><div class="alert alert-info py-2 small" role="alert">Processing complete. No specific results or errors.</div></div></div>
        @endif

    </div> {{-- End .container --}}

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>