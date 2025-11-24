<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

/**
 * GoogleAuthService - Handle Google OAuth authentication
 *
 * Features:
 * - Google OAuth login/register
 * - Account linking (Google + Password)
 * - Avatar sync from Google
 */
class GoogleAuthService
{
    /**
     * Redirect to Google OAuth
     *
     * Note: Google OAuth automatically includes 'openid', 'profile', 'email' scopes
     */
    public static function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public static function handleGoogleCallback(): array
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->user();

            // Find or create user
            $user = self::findOrCreateUser($googleUser);

            // Login user
            Auth::login($user, true); // Remember me = true

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Successfully logged in with Google',
            ];
        } catch (\Exception $e) {
            Log::error('Google OAuth Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to authenticate with Google. Please try again.',
            ];
        }
    }

    /**
     * Find or create user from Google data
     */
    private static function findOrCreateUser($googleUser): User
    {
        // Check if user exists with this Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Update existing user's Google data
            self::updateGoogleData($user, $googleUser);
            return $user;
        }

        // Check if user exists with this email
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google account to existing email/password account
            self::linkGoogleAccount($user, $googleUser);
            return $user;
        }

        // Create new user
        return self::createUserFromGoogle($googleUser);
    }

    /**
     * Create new user from Google data
     */
    private static function createUserFromGoogle($googleUser): User
    {
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'avatar' => $googleUser->getAvatar(),
            'provider' => 'google',
            'password' => Hash::make(Str::random(32)), // Random password
            'is_active' => true,
            'email_verified_at' => now(), // Auto-verify Google users
        ]);

        // Assign default role (petugas)
        $user->assignRole('petugas');

        Log::info('New user created via Google OAuth', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Link Google account to existing user
     */
    private static function linkGoogleAccount(User $user, $googleUser): void
    {
        $user->update([
            'google_id' => $googleUser->getId(),
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'avatar' => $googleUser->getAvatar(),
            'provider' => 'hybrid', // User can login with both Google and password
        ]);

        Log::info('Google account linked to existing user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Update existing user's Google data
     */
    private static function updateGoogleData(User $user, $googleUser): void
    {
        $user->update([
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'avatar' => $googleUser->getAvatar(),
            'name' => $googleUser->getName(), // Sync name
        ]);

        Log::info('User Google data updated', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Unlink Google account from user
     */
    public static function unlinkGoogleAccount(User $user): bool
    {
        try {
            // Only allow unlinking if user has password set
            if (empty($user->password) || $user->provider === 'google') {
                throw new \Exception('Cannot unlink Google account. Please set a password first.');
            }

            $user->update([
                'google_id' => null,
                'google_token' => null,
                'google_refresh_token' => null,
                'provider' => 'email', // Back to email/password only
            ]);

            Log::info('Google account unlinked', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unlink Google account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if user can login with password
     */
    public static function canLoginWithPassword(User $user): bool
    {
        return !empty($user->password) && $user->provider !== 'google';
    }

    /**
     * Check if user can login with Google
     */
    public static function canLoginWithGoogle(User $user): bool
    {
        return !empty($user->google_id);
    }

    /**
     * Get user's login methods
     */
    public static function getLoginMethods(User $user): array
    {
        return [
            'password' => self::canLoginWithPassword($user),
            'google' => self::canLoginWithGoogle($user),
            'provider' => $user->provider,
        ];
    }
}
