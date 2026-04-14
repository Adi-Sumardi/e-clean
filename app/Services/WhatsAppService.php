<?php

namespace App\Services;

use App\Models\GuestComplaint;
use App\Models\User;

/**
 * WhatsApp service factory that delegates to the configured provider.
 *
 * Usage: $wa = new WhatsAppService(); // auto-selects provider from WHATSAPP_PROVIDER env
 *
 * Supported providers: watzap, fontte, twilio
 */
class WhatsAppService
{
    protected WatZapService|FontteService|TwilioWhatsAppService $provider;

    public function __construct(?string $providerName = null)
    {
        $providerName = $providerName ?? config('services.whatsapp.provider', 'watzap');

        $this->provider = match ($providerName) {
            'twilio' => new TwilioWhatsAppService(),
            'fonnte' => new FontteService(),
            default => new WatZapService(),
        };
    }

    public function sendMessage(string $phoneNumber, string $message, array $data = []): array
    {
        return $this->provider->sendMessage($phoneNumber, $message, $data);
    }

    public function sendMessageWithImage(string $phoneNumber, string $message, string $imageUrl, array $data = []): array
    {
        return $this->provider->sendMessageWithImage($phoneNumber, $message, $imageUrl, $data);
    }

    public function sendToUser(User $user, string $message, string $type = 'general', array $data = []): array
    {
        return $this->provider->sendToUser($user, $message, $type, $data);
    }

    public function sendBulk(array $recipients): array
    {
        return $this->provider->sendBulk($recipients);
    }

    public function sendGuestComplaintNotification(GuestComplaint $complaint, array $petugasUsers): array
    {
        return $this->provider->sendGuestComplaintNotification($complaint, $petugasUsers);
    }

    public function sendComplaintStatusUpdate(GuestComplaint $complaint): array
    {
        return $this->provider->sendComplaintStatusUpdate($complaint);
    }

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    public function validateNumber(string $phoneNumber): array
    {
        return $this->provider->validateNumber($phoneNumber);
    }

    /**
     * Get the underlying provider instance
     */
    public function getProvider(): WatZapService|FontteService|TwilioWhatsAppService
    {
        return $this->provider;
    }
}
