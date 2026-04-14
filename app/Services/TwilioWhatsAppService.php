<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppService
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid') ?? '';
        $this->authToken = config('services.twilio.auth_token') ?? '';
        $this->fromNumber = config('services.twilio.whatsapp_from') ?? '';
    }

    /**
     * Send WhatsApp message via Twilio
     */
    public function sendMessage(string $phoneNumber, string $message, array $data = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Twilio WhatsApp not configured',
            ];
        }

        try {
            $phone = $this->formatPhoneNumber($phoneNumber);
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post($url, [
                    'From' => "whatsapp:{$this->fromNumber}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            $result = $response->json();
            $success = $response->successful() && !isset($result['code']);

            $this->logNotification([
                'phone' => $phone,
                'message' => $message,
                'response' => $result,
                'status' => $success ? 'sent' : 'failed',
                'error' => $result['message'] ?? null,
                'data' => $data,
            ]);

            return [
                'success' => $success,
                'message' => $success
                    ? 'Message sent via Twilio WhatsApp'
                    : ($result['message'] ?? 'Failed to send'),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp send error: ' . $e->getMessage());

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
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp message with media via Twilio
     */
    public function sendMessageWithImage(string $phoneNumber, string $message, string $imageUrl, array $data = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Twilio WhatsApp not configured',
            ];
        }

        try {
            $phone = $this->formatPhoneNumber($phoneNumber);
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post($url, [
                    'From' => "whatsapp:{$this->fromNumber}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                    'MediaUrl' => $imageUrl,
                ]);

            $result = $response->json();
            $success = $response->successful() && !isset($result['code']);

            $this->logNotification([
                'phone' => $phone,
                'message' => $message,
                'response' => $result,
                'status' => $success ? 'sent' : 'failed',
                'data' => array_merge($data, ['image_url' => $imageUrl]),
            ]);

            return [
                'success' => $success,
                'message' => $success
                    ? 'Message with image sent via Twilio WhatsApp'
                    : ($result['message'] ?? 'Failed to send'),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp send image error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to user
     */
    public function sendToUser(User $user, string $message, string $type = 'general', array $data = []): array
    {
        if (!$user->phone) {
            return [
                'success' => false,
                'message' => 'User has no phone number',
            ];
        }

        $phone = $this->formatPhoneNumber($user->phone);

        return $this->sendMessage($phone, $message, array_merge($data, [
            'user_id' => $user->id,
            'type' => $type,
        ]));
    }

    /**
     * Send bulk messages with rate limiting
     */
    public function sendBulk(array $recipients): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => [],
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
                'message' => $result['message'],
            ];

            // Twilio rate limit: 1 message per second for WhatsApp
            usleep(1000000); // 1 second delay
        }

        return $results;
    }

    /**
     * Send guest complaint notification to petugas
     */
    public function sendGuestComplaintNotification(\App\Models\GuestComplaint $complaint, array $petugasUsers): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        $lokasi = $complaint->lokasi;
        $jenisKeluhan = \App\Models\GuestComplaint::getJenisKeluhanOptions()[$complaint->jenis_keluhan] ?? $complaint->jenis_keluhan;
        $unitInfo = $lokasi->unit ? "Unit: {$lokasi->unit->nama_unit}\n" : '';

        $message = "*KELUHAN BARU DARI TAMU*\n\n"
            . $unitInfo
            . "Lokasi: {$lokasi->nama_lokasi}\n"
            . "Kode: {$lokasi->kode_lokasi}\n"
            . ($lokasi->lantai ? "Lantai: {$lokasi->lantai}\n" : '')
            . "\n"
            . "Jenis: {$jenisKeluhan}\n"
            . "Pelapor: {$complaint->nama_pelapor}\n"
            . ($complaint->telepon_pelapor ? "Telepon: {$complaint->telepon_pelapor}\n" : '')
            . "\n"
            . "Keluhan:\n{$complaint->deskripsi_keluhan}\n"
            . "\n"
            . 'Waktu: ' . $complaint->created_at->format('d/m/Y H:i') . "\n"
            . "\n"
            . '_Segera tangani keluhan ini._';

        foreach ($petugasUsers as $user) {
            if (!$user->phone) {
                $results['failed']++;
                $results['details'][] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'status' => 'failed',
                    'message' => 'No phone number',
                ];

                continue;
            }

            $result = $this->sendToUser($user, $message, 'guest_complaint', [
                'complaint_id' => $complaint->id,
                'lokasi_id' => $lokasi->id,
            ]);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message'],
            ];

            usleep(1000000); // 1 second delay
        }

        return $results;
    }

    /**
     * Send complaint status update to guest
     */
    public function sendComplaintStatusUpdate(\App\Models\GuestComplaint $complaint): array
    {
        if (!$complaint->telepon_pelapor) {
            return [
                'success' => false,
                'message' => 'Guest has no phone number',
            ];
        }

        $lokasi = $complaint->lokasi;
        $status = \App\Models\GuestComplaint::getStatusOptions()[$complaint->status] ?? $complaint->status;
        $unitInfo = $lokasi->unit ? "Unit: {$lokasi->unit->nama_unit}\n" : '';

        $message = "*UPDATE STATUS KELUHAN*\n\n"
            . $unitInfo
            . "Lokasi: {$lokasi->nama_lokasi}\n"
            . "Status: {$status}\n";

        if ($complaint->status === 'resolved') {
            $message .= "\nKeluhan Anda telah ditangani.\n";
            if ($complaint->catatan_penanganan) {
                $message .= "\nCatatan:\n{$complaint->catatan_penanganan}\n";
            }
            $message .= "\nTerima kasih telah membantu menjaga kebersihan.";
        } elseif ($complaint->status === 'in_progress') {
            $message .= "\nKeluhan Anda sedang dalam proses penanganan.\n"
                . "\nMohon tunggu, tim kami sedang bekerja.";
        } elseif ($complaint->status === 'rejected') {
            $message .= "\nKeluhan tidak dapat diproses.\n";
            if ($complaint->catatan_penanganan) {
                $message .= "\nAlasan:\n{$complaint->catatan_penanganan}\n";
            }
        }

        $phone = $this->formatPhoneNumber($complaint->telepon_pelapor);

        return $this->sendMessage($phone, $message, [
            'type' => 'complaint_status_update',
            'complaint_id' => $complaint->id,
        ]);
    }

    /**
     * Check if Twilio WhatsApp is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accountSid) && !empty($this->authToken) && !empty($this->fromNumber);
    }

    /**
     * Validate if a WhatsApp number exists via Twilio Lookup
     */
    public function validateNumber(string $phoneNumber): array
    {
        if (!$this->isConfigured()) {
            return ['valid' => false, 'message' => 'Twilio not configured'];
        }

        try {
            $phone = $this->formatPhoneNumber($phoneNumber);
            $url = "https://lookups.twilio.com/v2/PhoneNumbers/+{$phone}";

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get($url, ['Fields' => 'line_type_intelligence']);

            $result = $response->json();

            return [
                'valid' => $response->successful(),
                'message' => $response->successful() ? 'Valid number' : ($result['message'] ?? 'Invalid number'),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Twilio lookup error: ' . $e->getMessage());

            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format phone number to international format (without +)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Log notification to database
     */
    protected function logNotification(array $data): void
    {
        try {
            NotificationLog::create([
                'user_id' => $data['data']['user_id'] ?? null,
                'type' => $data['data']['type'] ?? 'whatsapp',
                'title' => 'WhatsApp Notification (Twilio)',
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
}
