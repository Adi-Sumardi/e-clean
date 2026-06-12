<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        // Google redirect kembali ke callback tanpa membawa query kita, jadi
        // platform disimpan di session untuk dibaca saat callback.
        $platform = $request->query('platform');
        if (in_array($platform, ['mobile', 'pwa'], true)) {
            session(['auth_platform' => $platform]);
        } else {
            session()->forget('auth_platform');
        }
        return GoogleAuthService::redirectToGoogle();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $result = GoogleAuthService::handleGoogleCallback();
        $platform = session('auth_platform') ?? $request->query('platform');

        if ($result['success']) {
            $user = $result['user'];

            if ($platform === 'mobile') {
                session()->forget('auth_platform');
                // Revoke old tokens
                $user->tokens()->delete();
                // Create a Sanctum token
                $token = $user->createToken('mobile-app')->plainTextToken;

                // Redirect to deep link: eclean://login?token=...
                return redirect('eclean://login?token=' . urlencode($token));
            }

            if ($platform === 'pwa') {
                session()->forget('auth_platform');
                $token = $user->createToken('pwa')->plainTextToken;

                // PWA same-origin: kembalikan token ke halaman login PWA.
                return redirect('/login?token=' . urlencode($token));
            }

            // Redirect to Filament admin panel
            return redirect()->intended('/admin')
                ->with('success', $result['message']);
        }

        $errorMessage = $result['message'] ?? 'Failed to authenticate with Google';

        if ($platform === 'mobile') {
            session()->forget('auth_platform');
            return redirect('eclean://login?error=' . urlencode($errorMessage));
        }

        if ($platform === 'pwa') {
            session()->forget('auth_platform');
            return redirect('/login?error=' . urlencode($errorMessage));
        }

        // Redirect back to login with error
        return redirect()->route('filament.admin.auth.login')
            ->with('error', $result['message']);
    }

    /**
     * Unlink Google account
     */
    public function unlinkGoogle(Request $request)
    {
        $user = $request->user();

        if (GoogleAuthService::unlinkGoogleAccount($user)) {
            return response()->json([
                'success' => true,
                'message' => 'Google account unlinked successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to unlink Google account. Please set a password first.',
        ], 400);
    }

    /**
     * Get user's login methods
     */
    public function getLoginMethods(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'methods' => GoogleAuthService::getLoginMethods($user),
        ]);
    }
}
