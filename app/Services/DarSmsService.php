<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DarSmsService
{
    /**
     * Submit a message to DarSMS. A successful response means queued, not delivered.
     *
     * @param array<int, string> $phoneNumbers
     * @return array{success: bool, recipients: array<int, string>, queued: int, failed: int, batch_id: string|null, response: array|null, error: string|null}
     */
    public function sendBulk(string $message, array $phoneNumbers): array
    {
        $apiKey = (string) config('services.darsms.api_key', '');
        $senderId = trim((string) config('services.darsms.sender_id', 'DARSMS')) ?: 'DARSMS';
        $recipients = array_values(array_unique(array_filter(array_map(
            fn ($phone) => $this->normalizePhone((string) $phone),
            $phoneNumbers,
        ))));

        if ($apiKey === '') {
            return $this->failure($recipients, 'DarSMS API key is not configured.');
        }
        if ($recipients === []) {
            return $this->failure([], 'No valid phone numbers provided.');
        }
        if ($senderId === '') {
            return $this->failure($recipients, 'DarSMS sender ID is not configured.');
        }

        $baseUrl = rtrim((string) (config('services.darsms.base_url') ?: 'https://dev.darsms.co.tz/api/v1'), '/');

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-API-Key' => $apiKey])
                ->timeout(20)
                ->post($baseUrl . '/integrations/sms/send', [
                    'senderId' => $senderId,
                    'to' => $recipients,
                    'message' => $message,
                ]);

            $body = $response->json();
            $body = is_array($body) ? $body : [];
            $data = is_array($body['data'] ?? null) ? $body['data'] : [];
            $success = $response->successful() && ($body['success'] ?? false) === true;

            if (! $success) {
                return [
                    'success' => false,
                    'recipients' => $recipients,
                    'queued' => 0,
                    'failed' => count($recipients),
                    'batch_id' => isset($data['batchId']) ? (string) $data['batchId'] : null,
                    'response' => $body ?: ['http_status' => $response->status()],
                    'error' => (string) ($body['message'] ?? "DarSMS returned HTTP {$response->status()}."),
                ];
            }

            $messageIds = is_array($data['messageIds'] ?? null) ? $data['messageIds'] : [];
            $queued = (int) ($data['queued'] ?? $data['queuedCount'] ?? count($messageIds));
            if ($queued === 0 && $messageIds === []) {
                $queued = count($recipients);
            }

            return [
                'success' => true,
                'recipients' => $recipients,
                'queued' => min($queued, count($recipients)),
                'failed' => max(0, count($recipients) - $queued),
                'batch_id' => isset($data['batchId']) ? (string) $data['batchId'] : null,
                'response' => $body,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            // Do not retry automatically: a timeout can happen after DarSMS accepted the batch.
            return $this->failure($recipients, 'DarSMS request failed; check the send history before retrying.');
        }
    }

    public function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '255')) {
            return preg_match('/^255[67]\d{8}$/', $digits) ? $digits : null;
        }
        if (preg_match('/^0[67]\d{8}$/', $digits)) {
            return '255' . substr($digits, 1);
        }
        if (preg_match('/^[67]\d{8}$/', $digits)) {
            return '255' . $digits;
        }

        return null;
    }

    private function failure(array $recipients, string $error): array
    {
        return [
            'success' => false,
            'recipients' => $recipients,
            'queued' => 0,
            'failed' => count($recipients),
            'batch_id' => null,
            'response' => null,
            'error' => $error,
        ];
    }
}
