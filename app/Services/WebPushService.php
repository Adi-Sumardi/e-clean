<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Pengiriman Web Push (VAPID) ke langganan PWA petugas.
 *
 * Menggantikan jalur Expo untuk fokus PWA. Subscription yang sudah tidak valid
 * (404/410) otomatis dihapus agar tabel bersih.
 */
class WebPushService
{
    private ?WebPush $client = null;

    private function client(): ?WebPush
    {
        if ($this->client) {
            return $this->client;
        }

        $public = config('services.vapid.public_key');
        $private = config('services.vapid.private_key');
        if (! $public || ! $private) {
            Log::warning('WebPush: VAPID keys belum dikonfigurasi.');
            return null;
        }

        try {
            $this->client = new WebPush([
                'VAPID' => [
                    'subject' => config('services.vapid.subject'),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('WebPush: gagal inisialisasi client — ' . $e->getMessage());
            return null;
        }

        return $this->client;
    }

    /**
     * Kirim notifikasi ke semua device milik user.
     *
     * @param  array{url?:string,type?:string,ref_id?:int}  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        try {
            $subs = $user->webPushSubscriptions()->get();
            if ($subs->isEmpty()) {
                return;
            }

            $client = $this->client();
            if (! $client) {
                return;
            }

            $payload = json_encode(array_merge([
                'title' => $title,
                'body' => $body,
            ], $data));

            foreach ($subs as $sub) {
                $client->queueNotification($this->toSubscription($sub), $payload);
            }

            // Kirim batch; hapus subscription yang sudah mati.
            foreach ($client->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                $endpoint = $report->getEndpoint();
                if ($report->isSubscriptionExpired()) {
                    WebPushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();
                } else {
                    Log::info('WebPush gagal', [
                        'endpoint' => $endpoint,
                        'reason' => $report->getReason(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WebPush: sendToUser gagal — ' . $e->getMessage());
        }
    }

    private function toSubscription(WebPushSubscription $sub): Subscription
    {
        return Subscription::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->public_key,
            'authToken' => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
        ]);
    }
}
