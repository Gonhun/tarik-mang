<?php // app/helpers.php

use App\Models\CostCenter;
use App\Models\LoginwaSession;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Jobsite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowAssignment;
use App\Models\AssetType;

use function PHPUnit\Framework\isBool;

if (!function_exists('PhoneFormat')) {
    /**
     * Memformat nomor telepon ke format standar Indonesia (diawali 62, tanpa spasi).
     *
     * @param string|null $phoneNumber Nomor telepon input.
     * @return string Nomor telepon yang sudah diformat, atau string kosong jika input tidak valid atau null.
     */
    function PhoneFormat(?string $phoneNumber): string
    {
        if ($phoneNumber === null) {
            return '';
        }

        // 1. Hapus semua karakter yang bukan angka (termasuk spasi, tanda hubung, kurung, dll.)
        $cleanedNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Jika setelah dibersihkan nomornya kosong, kembalikan string kosong
        if (empty($cleanedNumber)) {
            return '';
        }

        // 2. Cek dan sesuaikan awalan nomor telepon
        // Jika sudah diawali dengan "62"
        if (str_starts_with($cleanedNumber, '62')) {
            // Tidak perlu diubah, nomor sudah sesuai
            return $cleanedNumber;
        }

        // Jika diawali dengan "0"
        if (str_starts_with($cleanedNumber, '0')) {
            // Ganti "0" di depan dengan "62"
            return '62' . substr($cleanedNumber, 1);
        }

        // Jika tidak diawali dengan "0" atau "62" (misalnya, langsung "8xxxxxxxx")
        // Kita asumsikan ini adalah nomor lokal yang valid tanpa awalan "0"
        // Langsung tambahkan "62" di depannya.
        return '62' . $cleanedNumber;
    }
    if (!function_exists('callGeminiApi')) {
        /**
         * Mengirim permintaan ke Google Gemini API.
         *
         * @param array $parts Array yang berisi bagian-bagian konten (teks atau gambar).
         * Contoh untuk teks: [['text' => 'Your prompt here']]
         * Contoh untuk teks & gambar: [['text' => 'Describe this'], ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => 'base64_string']]]
         * @param string $model Model Gemini yang akan digunakan (default: 'gemini-1.5-flash-latest').
         * @return string|null Respons teks dari Gemini atau null jika gagal.
         */
        function callGeminiApi(array $parts, string $model = 'gemini-1.5-flash-latest'): ?string
        {
            $apiKey = config('gemini.api_key');

            if (empty($apiKey)) {
                Log::error('GEMINI_API_KEY tidak diset di .env');
                return "Error: API Key untuk layanan AI belum dikonfigurasi.";
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $payload = [
                'contents' => [
                    [
                        'parts' => $parts,
                    ],
                ],
                // Anda bisa menambahkan 'generationConfig' dan 'safetySettings' di sini jika perlu
                // 'generationConfig' => [
                //     'temperature' => 0.7,
                //     'maxOutputTokens' => 1024,
                // ],
                // 'safetySettings' => [
                //     [ 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
                //     // ... safety settings lainnya
                // ]
            ];

            try {
                $response = Http::timeout(60) // Set timeout (dalam detik)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if ($response->successful()) {
                    $responseData = $response->json();
                    // Cara mengambil teks respons bisa berbeda tergantung versi API atau jika ada error dari API
                    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                        return $responseData['candidates'][0]['content']['parts'][0]['text'];
                    } elseif (isset($responseData['promptFeedback']['blockReason'])) {
                        Log::error('Gemini API Prompt Feedback: ', $responseData['promptFeedback']);
                        return "Maaf, permintaan Anda tidak dapat diproses karena alasan keamanan konten: " . ($responseData['promptFeedback']['blockReason'] ?? 'Tidak diketahui');
                    } else {
                        Log::error('Gemini API respons tidak memiliki format teks yang diharapkan: ', $responseData);
                        return "Maaf, format respons dari AI tidak dikenali.";
                    }
                } else {
                    Log::error('Gemini API Error: Status ' . $response->status(), [
                        'response_body' => $response->body(),
                        'url' => $url,
                        // 'payload_sent' => $payload // Hati-hati log payload jika ada data sensitif
                    ]);
                    $errorBody = $response->json();
                    $errorMessage = $errorBody['error']['message'] ?? 'Terjadi kesalahan pada layanan AI.';
                    if (str_contains(strtolower($errorMessage), 'api key not valid')) {
                        return "Konfigurasi API Key layanan AI bermasalah.";
                    }
                    if (str_contains(strtolower($errorMessage), 'quota') || $response->status() == 429) {
                        return "Layanan AI sedang sibuk atau kuota terlampaui. Mohon coba lagi nanti.";
                    }
                    return "Maaf, terjadi kesalahan pada layanan AI kami. Silakan coba beberapa saat lagi (Status: {$response->status()}).";
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Gemini API Connection Exception: ' . $e->getMessage());
                return "Maaf, tidak dapat terhubung ke layanan AI. Periksa koneksi internet server Anda.";
            } catch (\Exception $e) {
                Log::error('General Exception when calling Gemini API: ' . $e->getMessage());
                return "Maaf, terjadi kesalahan umum saat memproses permintaan Anda ke layanan AI.";
            }
        }
    }

    if (!function_exists('prepareGeminiTextPart')) {
        /**
         * Menyiapkan bagian teks untuk payload Gemini API.
         */
        function prepareGeminiTextPart(string $text): array
        {
            return ['text' => $text];
        }
    }

    if (!function_exists('prepareGeminiImagePart')) {
        /**
         * Menyiapkan bagian gambar untuk payload Gemini API.
         *
         * @param string $mimeType Contoh: 'image/jpeg', 'image/png'.
         * @param string $base64ImageData Data gambar yang sudah di-encode base64.
         * @return array
         */
        function prepareGeminiImagePart(string $mimeType, string $base64ImageData): array
        {
            return [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $base64ImageData,
                ],
            ];
        }
    }
}
if (!function_exists('generateMutationNumber')) {
    /**
     * Membuat nomor transaksi mutasi aset dengan format:
     * BSS/{kode_jobsite}/MT/{YYMM}/{4 digit urut}
     *
     * @param string $value Default 'trx'. Jika ingin custom, bisa diubah.
     * @return string
     */
    function generateMutationNumber($value = 'trx')
    {
        $year = date('y');
        $month = date('m');
        $user = Auth::user();
        $jobsite = $user && $user->jobsite ? $user->jobsite->code : 'XXX';
        $prefix = "BSS/{$jobsite}/{$value}/{$year}{$month}/";
        $lastTransaction = \App\Models\AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = intval(substr($lastTransaction->transaction_number, strlen($prefix)));
            $nextSequence = $lastSequence + 1;
        }
        // $value tidak digunakan dalam format default, tapi disediakan untuk fleksibilitas ke depan
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
if (!function_exists('findWorkflowForContext')) {
    /**
     * Mencari template alur kerja (ApprovalWorkflow) yang paling cocok
     * berdasarkan jenis transaksi, jabatan, dan jobsite.
     *
     * @param string $transactionType Jenis transaksi (cth: 'REQUEST_ASSET', 'DISPOSAL').
     * @param int|null $positionLevelId ID jabatan dari pengguna yang memulai proses.
     * @param int|null $jobsiteId ID jobsite dari pengguna yang memulai proses.
     * @return \App\Models\ApprovalWorkflow|null Mengembalikan objek ApprovalWorkflow jika ditemukan, atau null.
     */
    function findWorkflowForContext(string $transactionType, ?int $positionId, ?int $jobsiteId): ?ApprovalWorkflow
    {
        // Query mencari aturan yang paling cocok dengan urutan prioritas:
        // 1. Aturan paling spesifik: cocok dengan transaction_type, position_id, DAN jobsite_id.
        // 2. Aturan spesifik untuk jabatan: cocok dengan transaction_type dan position_id (berlaku di semua jobsite).
        // 3. Aturan spesifik untuk jobsite: cocok dengan transaction_type dan jobsite_id (berlaku untuk semua jabatan).
        // 4. Aturan default: hanya cocok dengan transaction_type (berlaku untuk semua jabatan dan jobsite).

        $assignment = ApprovalWorkflowAssignment::query()
            ->where('transaction_type', $transactionType)
            // Urutkan berdasarkan seberapa spesifik aturannya.
            // Kita bisa menggunakan CASE statement atau beberapa query terpisah.
            // Untuk kejelasan, kita gunakan beberapa query.

            // Coba cari yang paling spesifik (cocok semua)
            ->where('position_id', $positionId)
            ->where('jobsite_id', $jobsiteId)
            ->orderBy('priority', 'desc') // Jika ada beberapa aturan yang cocok, ambil prioritas tertinggi
            ->first();

        // Jika tidak ada, cari yang cocok dengan Jabatan (berlaku di semua jobsite)
        if (!$assignment) {
            $assignment = ApprovalWorkflowAssignment::where('transaction_type', $transactionType)
                ->where('position_id', $positionId)
                ->whereNull('jobsite_id')
                ->orderBy('priority', 'desc')
                ->first();
        }

        // Jika masih tidak ada, cari yang berlaku untuk semua jabatan di jobsite tersebut
        if (!$assignment) {
            $assignment = ApprovalWorkflowAssignment::where('transaction_type', $transactionType)
                ->whereNull('position_id')
                ->where('jobsite_id', $jobsiteId)
                ->orderBy('priority', 'desc')
                ->first();
        }

        // Terakhir, cari aturan default untuk jenis transaksi ini
        if (!$assignment) {
            $assignment = ApprovalWorkflowAssignment::where('transaction_type', $transactionType)
                ->whereNull('position_id')
                ->whereNull('jobsite_id')
                ->orderBy('priority', 'desc')
                ->first();
        }

        // Jika assignment ditemukan, kembalikan relasi approvalWorkflow-nya
        // Eager load steps dan relasi di dalamnya untuk efisiensi
        return $assignment ? $assignment->approvalWorkflow()->with('steps.approverPositionLevel')->first() : null;
    }
}
if (!function_exists('getTokenWa')) {
    /**
     * Melakukan login ke layanan WA, memproses respons, dan menyimpan token ke database.
     *
     * @param string $phoneNumber Nomor telepon dengan format 62...
     * @return array|null Data token ['token' => ..., 'expires_at' => ...] jika berhasil, atau null jika gagal.
     */
    function getTokenWa(): ?array
    {
        $apiUrl = config('services.loginwa.url');
        $number = config('services.loginwa.number');
        $privateKey = config('services.loginwa.key');

        if (empty($apiUrl) || empty($privateKey)) {
            Log::error('Konfigurasi LoginWA (URL/Key) belum diatur.');
            return null;
        }

        // Cari user di database lokal berdasarkan nomor telepon
        // Pastikan kolom di tabel 'users' Anda untuk nomor WA adalah 'whatsapp' atau sesuaikan.

        try {
            // Mengirim request ke API
            $response = Http::withOptions(['verify' => config('services.loginwa.verify_ssl')])->timeout(30)->post($apiUrl . '/external/login', [
                'phone_number' => $number,
                'private_key' => $privateKey,
            ]);

            // Memeriksa apakah request berhasil
            if (!$response->successful()) {
                Log::error('LoginWA API request gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            // Memeriksa status dari respons API
            if (isset($data['status']) && $data['status'] === true && isset($data['data']['token'])) {
                $tokenData = $data['data'];

                // Simpan atau update sesi di database
                LoginwaSession::updateOrCreate(
                    ['phone_number' => $number], // Kunci untuk mencari
                    [ // Data untuk di-update atau di-create
                        'phone_number' => $number,
                        'token' => $tokenData['token'],
                        'expires_at' => \Carbon\Carbon::parse($tokenData['expires_in']),
                    ]
                );

                Log::info("LoginWA berhasil untuk user ID: {$number}");
                return $tokenData; // Kembalikan data token jika berhasil

            } else {
                Log::error('LoginWA respons tidak sukses atau format data tidak sesuai.', [
                    'response_body' => $data
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::critical('Terjadi exception saat memanggil LoginWA API: ' . $e->getMessage());
            return null;
        }
    }
}
if (!function_exists('findCostCenter')) {
    /**
     * Mencari satu record Cost Center berdasarkan Department ID dan Jobsite ID.
     *
     * @param int|null $departmentId ID dari departemen.
     * @param int|null $jobsiteId ID dari jobsite.
     * @return \App\Models\CostCenter|null Mengembalikan objek CostCenter jika ditemukan, atau null jika tidak.
     */
    function findCostCenter($departmentId, $jobsiteId): ?CostCenter
    {
        // Pastikan kedua ID tidak kosong sebelum melakukan query
        if (empty($departmentId) || empty($jobsiteId)) {
            return null;
        }

        // Jalankan query untuk mencari Cost Center yang cocok
        return CostCenter::where('department_id', $departmentId)
            ->where('jobsite_id', $jobsiteId)
            ->first(); // Gunakan first() untuk mendapatkan satu objek atau null
    }
}
if (!function_exists('generateAssetNumber')) {
    /**
     * Membuat nomor aset unik dengan format {EntityCode}{JobsiteCode}{YYMM}{XXXX}.
     *
     * @param int $entityId ID dari model Entity.
     * @param int $typeId ID dari model Typeasset.
     * @return string Nomor aset yang dihasilkan, atau string error jika gagal.
     */
    function generateAssetNumber(int $entityId, int $typeId): string
    {
        // 1. Validasi Input dan Ambil Data Master
        $entity = Entity::find($entityId);
        $type = AssetType::find($typeId);

        if (!$entity || !$typeId) {
            Log::error('generateAssetNumber: Entity ID atau Jobsite ID tidak valid.', ['entity_id' => $entityId, 'assetType_id' => $typeId]);
            return 'ERROR_INVALID_INPUT';
        }

        // 2. Siapkan Bagian-bagian Nomor Aset
        $entityPart = $entity->code;
        $typeasset = $type->code;
        $datePart = date('ym'); // Format YYMM (misal: 2507 untuk Juli 2025)

        // Ini adalah prefix dasar yang akan digunakan untuk mencari nomor terakhir
        $basePrefixForQuery = $entityPart . $typeasset . $datePart;

        $nextSequence = 1;
        $maxRetries = 15; // Jumlah maksimum percobaan untuk menghindari infinite loop

        // 3. Loop untuk memastikan keunikan nomor aset
        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {

            // Jika ini bukan percobaan pertama, cukup increment sequence
            if ($retryCount > 0) {
                $nextSequence++;
            } else {
                // Percobaan pertama: cari nomor aset terakhir dengan prefix yang sama
                $lastAsset = Asset::where('asset_number', 'LIKE', $basePrefixForQuery . '%')
                    ->orderBy('asset_number', 'desc')
                    ->withTrashed() // Cek juga terhadap aset yang sudah di-soft delete
                    ->first();

                if ($lastAsset) {
                    // Ekstrak bagian sequence dari asset_number terakhir
                    $sequenceString = substr($lastAsset->asset_number, strlen($basePrefixForQuery));

                    if (is_numeric($sequenceString) && strlen($sequenceString) === 4) {
                        $nextSequence = intval($sequenceString) + 1;
                    } else {
                        Log::warning("Format sequence tidak valid pada asset_number terakhir: {$lastAsset->asset_number}. Menggunakan sequence awal.");
                        $nextSequence = 1; // Reset ke 1 jika format aneh
                    }
                } else {
                    // Tidak ada aset dengan prefix ini, mulai dari 1
                    $nextSequence = 1;
                }
            }

            // Format sequence menjadi 4 digit dengan padding nol di depan
            $formattedSequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            // Gabungkan semua bagian untuk membentuk nomor aset baru
            $newAssetNumber = $basePrefixForQuery . $formattedSequence;

            // Periksa apakah nomor aset yang baru dibuat ini sudah ada di database
            $isExist = Asset::where('asset_number', $newAssetNumber)->withTrashed()->exists();

            if (!$isExist) {
                // Jika nomor belum ada, kita berhasil! Kembalikan nomornya.
                return $newAssetNumber;
            }

            // Jika nomor sudah ada, loop akan berlanjut untuk mencoba sequence berikutnya.
        }

        // Jika loop selesai tanpa menemukan nomor unik, catat error kritis
        Log::critical("Gagal menghasilkan nomor aset unik setelah {$maxRetries} percobaan untuk prefix {$basePrefixForQuery}.");
        return 'ERROR_GENERATION_FAILED';
    }
}
if (!function_exists('sendMessageWithWhatsApp')) {
    /**
     * Mengirim pesan teks via layanan WA menggunakan Bearer Token.
     * Secara otomatis mencoba mendapatkan token baru jika token lama tidak valid.
     *
     * @param string $targetPhoneNumber Nomor telepon tujuan dengan format 62...
     * @param string $messageBody Isi pesan teks yang akan dikirim.
     * @return bool True jika berhasil mengirim, false jika gagal.
     */
    function sendMessageWithWhatsApp(?string $targetPhoneNumber, string $messageBody): bool
    {
        // Pengiriman via WhatsApp gateway. Tidak pernah melempar exception:
        // notifikasi WA gagal tidak boleh menggagalkan transaksi.
        if (blank($targetPhoneNumber)) {
            return false;
        }
        if (!\App\Support\WhatsAppGateway::configured()) {
            Log::warning('WA tidak dikirim: WhatsApp gateway belum dikonfigurasi.');
            return false;
        }

        try {
            if (!\App\Support\WhatsAppGateway::isConnected()) {
                Log::warning("WA ke {$targetPhoneNumber} tidak dikirim: WhatsApp belum terhubung (scan QR di Integrasi > WhatsApp).");
                return false;
            }
            \App\Support\WhatsAppGateway::sendText($targetPhoneNumber, $messageBody);
            Log::info("Pesan WA terkirim ke {$targetPhoneNumber}");

            return true;
        } catch (\Throwable $e) {
            Log::error("Pesan WA ke {$targetPhoneNumber} gagal: " . $e->getMessage());

            return false;
        }
    }

    function sendMessageWithWhatsAppV2(string $targetPhoneNumber, string $targetName, string $messageId, array $messageBody)
    {
        $apiUrl = config('services.loginwa.url');
        $privateKey = config('services.loginwa.key');

        if (empty($apiUrl) || empty($privateKey)) {
            Log::error('Konfigurasi LoginWA (URL/Key) belum diatur.');
            return null;
        }

        try {
            $response = Http::withOptions(['verify' => config('services.loginwa.verify_ssl')])->timeout(30)->withHeaders([
                'wapikey' => $privateKey,
            ])->post($apiUrl, [
                'to_number' => $targetPhoneNumber,
                'to_name' => $targetName,
                'message_template_id' => $messageId,
                'body_params' => $messageBody,
            ]);

            // dd($targetPhoneNumber, $targetName, $messageId, $messageBody, $response->body());
            // Memeriksa apakah request berhasil
            if (!$response->successful()) {
                Log::error('LoginWA API request gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                 return false;
            }
            else{
                return true;
            }
        } catch (\Exception $e) {
            Log::critical('Terjadi exception saat memanggil LoginWA API: ' . $e->getMessage());
            return null;
        }
    }
    
}


