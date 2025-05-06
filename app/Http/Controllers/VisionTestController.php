<?php

namespace App\Http\Controllers;

// Namespace inti Laravel
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Namespace untuk Intervention Image v3 (yang sudah Anda gunakan)
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Atau Imagick\Driver jika Anda pakai itu


class VisionTestController extends Controller
{
    /**
     * Menampilkan halaman form upload ATAU hasil dari redirect.
     */
    public function index() // Method GET (Tidak perlu return type hint di sini)
    {
        // Ambil data yang mungkin di-flash dari session setelah redirect POST
        $results = session('results');
        $uploadedImageDataUri = session('uploadedImageDataUri');
        $errorMessage = session('errorMessage');

        // Kirim data (atau null jika tidak ada) ke view
        return view('vision-test', [
            'results' => $results,
            'uploadedImageDataUri' => $uploadedImageDataUri,
            'errorMessage' => $errorMessage
        ]);
    }

    /**
     * Menerima gambar, memanggil Gemini, dan me-redirect dengan hasil/error.
     */
    // Gunakan type hint \Illuminate\Http\RedirectResponse
    public function analyze(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:4096',
        ]);

        // Handle Validasi Gagal -> Redirect KEMBALI ke form
        if ($validator->fails()) {
            return redirect()->route('vision.test.form')
                ->withErrors($validator)
                ->withInput();
        }

        // Variabel untuk hasil
        $results = null;
        $uploadedImageDataUri = null;
        $error_message = null;
        $imageBase64 = null;

        try {
            $imageFile = $request->file('image');

            // === Langkah Resizing (Intervention Image v3) ===
            $manager = new ImageManager(new Driver());
            $img = null;
            $resizedImageData = null;
            $imageMimeType = null;

            // Gunakan try-catch umum untuk proses gambar
            try {
                $img = $manager->read($imageFile->getRealPath());
                $img->resize(400, 400); // Atau fit/cover
                $resizedImageData = $img->toJpeg(90);
                $imageMimeType = 'image/jpeg';

                $imageBase64 = base64_encode($resizedImageData);
                $uploadedImageDataUri = 'data:' . $imageMimeType . ';base64,' . $imageBase64;
            } catch (\Exception $e) { // Tangkap Exception umum
                Log::error('Intervention Image processing failed:', ['error' => $e->getMessage()]);
                // Re-throw atau set error message sesuai kebutuhan
                throw new \Exception('Failed to process the uploaded image. ' . $e->getMessage());
            }
            // === Akhir Resizing ===


            // Pastikan ada data base64
            if (empty($imageBase64) || empty($imageMimeType)) {
                throw new \Exception('Image data could not be prepared for the API.');
            }

            // === Siapkan Prompt & Panggil Gemini API ===
            $targetPrompt = "bottles, identifying if they are mineral water bottles or other types (like soda, tea, coffee bottles), and whether they appear empty or filled (note potential contents like water, cigarette butts, sticks of wood, etc if visible)";
            $labelPrompt = "a label describing the bottle type and fill status (e.g., 'empty mineral bottle', 'filled soda bottle - water', 'filled tea bottle - trash')";
            $fullPrompt = "Detect {$targetPrompt}, with no more than 20 items. Output ONLY a valid JSON list (no extra text or markdown formatting) where each entry contains the 2D bounding box in \"box_2d\" (as [ymin, xmin, ymax, xmax] scaled 0-1000) and {$labelPrompt} in \"label\".";

            $payload = [ /* ... Payload Gemini ... */];
            $payload = [
                'contents' => [['parts' => [['text' => $fullPrompt], ['inline_data' => ['mime_type' => $imageMimeType, 'data' => $imageBase64]]]]],
            ];


            $apiKey = config('services.google.api_key');
            $apiEndpoint = config('services.google.api_endpoint');
            if (!$apiKey || !$apiEndpoint) throw new \Exception('Gemini API Key or Endpoint is not configured.');

            $response = Http::timeout(60)->withHeaders(['Content-Type' => 'application/json'])
                ->post($apiEndpoint . '?key=' . $apiKey, $payload);

            // Handle Respons API
            if (!$response->successful()) {
                Log::error('Gemini API Error:', ['status' => $response->status(), 'body' => $response->body()]);
                $apiErrorDetails = $response->json() ?? $response->body();
                throw new \Exception('Failed to call Gemini API. Status: ' . $response->status() . ' Details: ' . json_encode($apiErrorDetails));
            }
            $responseText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$responseText) {
                throw new \Exception('Gemini response did not contain expected text part.');
            }


            // Parsing JSON
            $jsonString = $responseText;
            if (strpos($responseText, '```json') !== false) {
                if (preg_match('/```json\s*([\s\S]*?)\s*```/', $responseText, $matches)) {
                    $jsonString = $matches[1];
                } else {
                    $jsonString = str_replace(['```json', '```'], '', $responseText);
                }
            }
            try {
                $parsedResponse = json_decode(trim($jsonString), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \Exception('Failed to parse JSON response from Gemini.');
            }

            // Format Bounding Box
            if (!is_array($parsedResponse)) {
                throw new \Exception('Unexpected format from Gemini response after parsing.');
            }
            $formattedBoxes = [];
            foreach ($parsedResponse as $box) {
                if (isset($box['box_2d']) && is_array($box['box_2d']) && count($box['box_2d']) === 4 && isset($box['label'])) {
                    [$ymin, $xmin, $ymax, $xmax] = $box['box_2d'];
                    $formattedBoxes[] = ['x' => $xmin / 1000, 'y' => $ymin / 1000, 'width' => ($xmax - $xmin) / 1000, 'height' => ($ymax - $ymin) / 1000, 'label' => $box['label']];
                } else {
                    Log::warning('Skipping invalid box data from Gemini:', ['box_data' => $box]);
                }
            }
            $results = ['boundingBoxes' => $formattedBoxes];
            // === Akhir Panggil Gemini API ===

        } catch (\Exception $e) { // Tangkap semua jenis exception di sini
            Log::error('Error processing image analysis:', ['message' => $e->getMessage()]);
            $error_message = $e->getMessage();
            // Cek jika $uploadedImageDataUri sudah dibuat sebelum error
            if (empty($imageBase64)) {
                $uploadedImageDataUri = null;
            }
        }

        // --- Redirect dengan Flash Data ---
        $flashData = [];
        if ($error_message) {
            $flashData['errorMessage'] = $error_message;
            if ($uploadedImageDataUri) {
                $flashData['uploadedImageDataUri'] = $uploadedImageDataUri;
            }
        } else {
            $flashData['results'] = $results;
            $flashData['uploadedImageDataUri'] = $uploadedImageDataUri;
        }

        // Redirect ke route GET ('vision.test.form')
        return redirect()->route('vision.test.form')->with($flashData);
        // -------------------------------
    }
}
