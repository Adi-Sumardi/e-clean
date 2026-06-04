<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications to the Expo Push API for the E-Clean mobile app.
 *
 * @see https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /** Expo push tokens look like ExponentPushToken[xxx] or ExpoPushToken[xxx]. */
    public function isValidToken(?string $token): bool
    {
        return is_string($token)
            && (str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken['));
    }

    /**
     * Send a notification to a single user (no-op if they have no valid token).
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (! $this->isValidToken($user->expo_push_token)) {
            return false;
        }

        return $this->sendToTokens([$user->expo_push_token], $title, $body, $data) > 0;
    }

    /**
     * Send the same notification to many users. Returns the number of messages
     * accepted by Expo.
     *
     * @param  iterable<User>  $users
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): int
    {
        $tokens = collect($users)
            ->map(fn (User $u) => $u->expo_push_token)
            ->filter(fn ($t) => $this->isValidToken($t))
            ->unique()
            ->values()
            ->all();

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send to raw Expo tokens. Returns the count of accepted messages.
     *
     * @param  array<string>  $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        if (empty($tokens)) {
            return 0;
        }

        $messages = array_map(fn (string $token) => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
            'data' => $data,
        ], $tokens);

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post(self::ENDPOINT, $messages);

            if ($response->failed()) {
                Log::warning('Expo push request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 0;
            }

            // Expo returns { data: [ {status: ok|error, ...}, ... ] }
            $tickets = collect($response->json('data') ?? []);
            $ok = $tickets->where('status', 'ok')->count();

            $errors = $tickets->where('status', 'error');
            if ($errors->isNotEmpty()) {
                Log::info('Expo push had errors', ['errors' => $errors->values()->all()]);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('Expo push exception: ' . $e->getMessage());

            return 0;
        }
    }
}
