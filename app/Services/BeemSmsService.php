<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemSmsService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $sourceAddr;
    protected string $baseUrl = 'https://apisms.beem.africa/public/v1/sms/v1/send';

    public function __construct()
    {
        $this->apiKey    = config('services.beem.api_key', '');
        $this->secretKey = config('services.beem.secret_key', '');
        $this->sourceAddr = config('services.beem.sender_id', 'KANISA');
    }

    /**
     * Send a bulk SMS to multiple recipients.
     *
     * @param  string  $message      The SMS text body.
     * @param  array   $phoneNumbers Array of phone numbers in international format (e.g. 255712345678).
     * @return array{success: bool, sent: int, failed: int, response: array|null, error: string|null}
     */
    public function sendBulk(string $message, array $phoneNumbers): array
    {
        if (empty($this->apiKey) || empty($this->secretKey)) {
            Log::warning('BeemSmsService: API credentials not configured.');
            return [
                'success'  => false,
                'sent'     => 0,
                'failed'   => count($phoneNumbers),
                'response' => null,
                'error'    => 'Beem Africa API credentials are not configured.',
            ];
        }

        $recipients = [];
        foreach ($phoneNumbers as $index => $phone) {
            $normalized = $this->normalizePhone($phone);
            if ($normalized) {
                $recipients[] = [
                    'recipient_id' => (string) ($index + 1),
                    'dest_addr'    => $normalized,
                ];
            }
        }

        if (empty($recipients)) {
            return [
                'success'  => false,
                'sent'     => 0,
                'failed'   => count($phoneNumbers),
                'response' => null,
                'error'    => 'No valid phone numbers provided.',
            ];
        }

        $payload = [
            'source_addr'   => $this->sourceAddr,
            'schedule_time' => '',
            'encoding'      => '0',
            'message'       => $message,
            'recipients'    => $recipients,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->secretKey}"),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(30)
            ->post($this->baseUrl, $payload);

            $body = $response->json();

            Log::info('BeemSmsService: API response', [
                'status'   => $response->status(),
                'response' => $body,
            ]);

            // Beem returns code 100 for success
            $beemCode = $body['code'] ?? $body['status_code'] ?? null;
            $success  = $response->successful() && in_array($beemCode, [100, 101]);

            return [
                'success'  => $success,
                'sent'     => $success ? count($recipients) : 0,
                'failed'   => $success ? 0 : count($recipients),
                'response' => $body,
                'error'    => $success ? null : ($body['message'] ?? 'Unknown error from Beem Africa API.'),
            ];
        } catch (\Throwable $e) {
            Log::error('BeemSmsService: Request failed', ['error' => $e->getMessage()]);

            return [
                'success'  => false,
                'sent'     => 0,
                'failed'   => count($recipients),
                'response' => null,
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS to a single phone number.
     */
    public function sendSingle(string $message, string $phoneNumber): array
    {
        return $this->sendBulk($message, [$phoneNumber]);
    }

    /**
     * Normalize a phone number to Beem Africa format (no + or leading 0).
     * Supports Tanzanian numbers (255) and other East African formats.
     *
     * @param  string  $phone
     * @return string|null  Normalized number or null if invalid.
     */
    public function normalizePhone(string $phone): ?string
    {
        // Strip all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        if (empty($phone)) {
            return null;
        }

        // Already international format (starts with country code like 255, 254, 256…)
        if (strlen($phone) >= 11 && str_starts_with($phone, '2')) {
            return $phone;
        }

        // Local Tanzanian format: 07xxxxxxxx or 06xxxxxxxx → 2557xxxxxxxx
        if (strlen($phone) === 10 && in_array($phone[0], ['0'])) {
            return '255' . ltrim($phone, '0');
        }

        // 9-digit number without country code (e.g. 712345678) → 255712345678
        if (strlen($phone) === 9) {
            return '255' . $phone;
        }

        return $phone; // Return as-is if we can't determine format
    }
}
