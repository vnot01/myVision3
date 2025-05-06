<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception; // Import Exception

class VisionAnalysisService
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function __construct()
    {
        // Ambil konfigurasi API dari config/services.php atau .env
        // Sesuaikan key config jika berbeda
        $this->apiKey = config('services.google.api_key');
        $this->apiEndpoint = config('services.google.api_endpoint'); // Pastikan ini endpoint vision

        if (!$this->apiKey || !$this->apiEndpoint) {
            throw new Exception('Gemini API Key or Endpoint is not configured in services config.');
        }
    }

    /**
     * Menganalisis gambar botol menggunakan Gemini Vision dan menginterpretasikannya.
     *
     * @param string $imageBase64 Data gambar base64
     * @param string $imageMimeType Tipe MIME gambar
     * @return array Hasil analisis ['type' => string, 'points' => int, 'needs_action' => bool, 'message' => string]
     * @throws Exception Jika terjadi error API atau parsing
     */
    public function analyzeBottleImage(string $imageBase64, string $imageMimeType): array
    {
        // 1. Buat Prompt Spesifik untuk RVM
        // Kita perlu deteksi lebih detail: jenis botol DAN isinya.
        $prompt = "Analyze the primary object in the image. Is it a plastic bottle? If yes, what type (mineral water, soda, tea, coffee, other)? Crucially, does the bottle appear empty or does it contain any visible foreign objects or liquids (like water, cigarette butts, sticks, trash, etc.)? Respond ONLY with a valid JSON object containing these keys: \"object_type\" (possible values: \"mineral_plastic\", \"other_plastic_bottle\", \"not_a_bottle\", \"unknown\"), and \"contains_content\" (boolean true/false).";

        // 2. Siapkan Payload Gemini
        $payload = [
            'contents' => [['parts' => [
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $imageMimeType, 'data' => $imageBase64]]
            ]]],
            // Tambahkan safety settings jika perlu
             'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ]
        ];

        // 3. Panggil API Gemini
        $response = Http::timeout(60)
                      ->withHeaders(['Content-Type' => 'application/json'])
                      ->post($this->apiEndpoint . '?key=' . $this->apiKey, $payload);

        // 4. Handle Error API
        if (!$response->successful()) {
            Log::error('Gemini API Error for RVM analysis:', ['status' => $response->status(), 'body' => $response->body()]);
            throw new Exception('Failed to analyze image via Vision API. Status: ' . $response->status());
        }

        // 5. Ekstrak & Parse Respons JSON dari Gemini
        $responseText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$responseText) {
            Log::error('Gemini API Response malformed or empty text part:', ['response' => $response->json()]);
            throw new Exception('Gemini response did not contain expected text part.');
        }

        // Bersihkan markdown jika ada (meskipun prompt meminta JSON saja)
        $jsonString = $responseText;
        if (strpos($responseText, '```json') !== false) {
            if (preg_match('/```json\s*([\s\S]*?)\s*```/', $responseText, $matches)) { $jsonString = $matches[1]; }
            else { $jsonString = str_replace(['```json', '```'], '', $responseText); }
        }

        try {
            $analysisResult = json_decode(trim($jsonString), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Failed to parse JSON from Gemini RVM analysis:', ['error' => $e->getMessage(), 'raw_response' => $responseText]);
            throw new Exception('Failed to parse analysis result from Vision API.');
        }

        // Pastikan hasil parsing memiliki format yang diharapkan
        if (!isset($analysisResult['object_type']) || !isset($analysisResult['contains_content'])) {
             Log::error('Unexpected JSON format from Gemini RVM analysis:', ['parsed' => $analysisResult]);
             throw new Exception('Unexpected analysis format received from Vision API.');
        }

        // 6. Interpretasi Hasil & Tentukan Poin/Aksi
        return $this->interpretAnalysis($analysisResult);
    }

    /**
     * Menginterpretasi hasil JSON dari Gemini dan menentukan poin/aksi.
     *
     * @param array $analysisResult ['object_type' => string, 'contains_content' => bool]
     * @return array ['type' => string, 'points' => int, 'needs_action' => bool, 'message' => string]
     */
    private function interpretAnalysis(array $analysisResult): array
    {
        $objectType = $analysisResult['object_type'];
        $containsContent = $analysisResult['contains_content'];

        // Logika Poin dan Aksi
        if ($containsContent) {
            return [
                'type' => 'contains_content', // Tipe deposit untuk disimpan di DB
                'points' => 0,
                'needs_action' => true, // User harus ambil kembali
                'message' => 'Bottle contains. Please empty and try again.'
            ];
        }

        switch ($objectType) {
            case 'mineral_plastic':
                return [
                    'type' => 'mineral_plastic',
                    'points' => 100, // Poin untuk botol mineral
                    'needs_action' => false,
                    'message' => 'Mineral bottle accepted. +100 points!'
                ];
            case 'other_plastic_bottle':
                return [
                    'type' => 'other_bottle', // Tipe deposit untuk disimpan di DB
                    'points' => 10, // Poin untuk botol lain
                    'needs_action' => false,
                    'message' => 'Bottle accepted. +10 points.'
                ];
            case 'not_a_bottle':
            case 'unknown':
            default:
                return [
                    'type' => 'unknown', // Tipe deposit untuk disimpan di DB
                    'points' => 0,
                    'needs_action' => true, // Anggap perlu diambil jika tidak dikenali/bukan botol
                    'message' => 'Object not recognized as an acceptable empty bottle.'
                ];
        }
    }
}