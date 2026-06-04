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
        if ($request->query('platform') === 'mobile') {
            session(['auth_platform' => 'mobile']);
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

        if ($result['success']) {
            $user = $result['user'];
            
            if (session('auth_platform') === 'mobile' || $request->query('platform') === 'mobile') {
                session()->forget('auth_platform');
                // Revoke old tokens
                $user->tokens()->delete();
                // Create a Sanctum token
                $token = $user->createToken('mobile-app')->plainTextToken;
                
                // Redirect to deep link: eclean://login?token=...
                return redirect('eclean://login?token=' . urlencode($token));
            }

            // Redirect to Filament admin panel
            return redirect()->intended('/admin')
                ->with('success', $result['message']);
        }

        if (session('auth_platform') === 'mobile' || $request->query('platform') === 'mobile') {
            session()->forget('auth_platform');
            return redirect('eclean://login?error=' . urlencode($result['message'] ?? 'Failed to authenticate with Google'));
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
