<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FontteService
{
    protected string $apiUrl;
    protected ?string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.fonnte.url', 'https://api.fonnte.com/send');
        $this->apiToken = config('services.fonnte.token');
    }

    /**
     * Send WhatsApp message via Fonnte
     *
     * @param string $phoneNumber - Phone number with country code (e.g., 628123456789)
     * @param string $message - Message content
     * @param array $data - Additional data to store in notification log
     * @return array - Response from Fonnte API
     */
    public function sendMessage(string $phoneNumber, string $message, array $data = []): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'Fonnte API token not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post($this->apiUrl, [
                'target' => $phoneNumber,
                'message' => $message,
                'countryCode' => '62', // Indonesia
            ]);

            $result = $response->json();

            // Log the notification
            $this->logNotification([
                'phone' => $phoneNumber,
                'message' => $message,
                'response' => $result,
                'status' => $response->successful() ? 'sent' : 'failed',
                'data' => $data,
            ]);

            return [
                'success' => $response->successful(),
                'message' => $result['detail'] ?? $result['message'] ?? 'Unknown response',
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Fonnte send message error: ' . $e->getMessage());

            $this->logNotification([
                'phone' => $phoneNumber,
                'message' => $message,
                'response' => null,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to user
     *
     * @param User $user
     * @param string $message
     * @param string $type
     * @param array $data
     * @return array
     */
    public function sendToUser(User $user, string $message, string $type = 'general', array $data = []): array
    {
        if (!$user->phone) {
            return [
                'success' => false,
                'message' => 'User has no phone number'
            ];
        }

        $phone = $this->formatPhoneNumber($user->phone);

        return $this->sendMessage($phone, $message, array_merge($data, [
            'user_id' => $user->id,
            'type' => $type,
        ]));
    }

    /**
     * Format phone number to international format
     *
     * @param string $phone
     * @return string
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // If doesn't start with 62, add it
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Log notification to database
     *
     * @param array $data
     * @return void
     */
    protected function logNotification(array $data): void
    {
        try {
            NotificationLog::create([
                'user_id' => $data['data']['user_id'] ?? null,
                'type' => $data['data']['type'] ?? 'whatsapp',
                'title' => 'WhatsApp Notification',
                'message' => $data['message'],
                'data' => json_encode([
                    'phone' => $data['phone'],
                    'response' => $data['response'],
                    'additional_data' => $data['data'] ?? [],
                ]),
                'sent_at' => now(),
                'status' => $data['status'],
                'error_message' => $data['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification: ' . $e->getMessage());
        }
    }

    /**
     * Send bulk messages
     *
     * @param array $recipients - Array of ['phone' => 'message'] pairs
     * @return array - Summary of sent/failed messages
     */
    public function sendBulk(array $recipients): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($recipients as $phone => $message) {
            $result = $this->sendMessage($phone, $message);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'phone' => $phone,
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message']
            ];

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }

        return $results;
    }

    /**
     * Check if Fonnte is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Get account balance/quota
     *
     * @return array|null
     */
    public function getAccountInfo(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->get('https://api.fonnte.com/get-quota');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Fonnte get account info error: ' . $e->getMessage());
            return null;
        }
    }
}
