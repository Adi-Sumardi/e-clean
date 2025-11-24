<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return GoogleAuthService::redirectToGoogle();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        $result = GoogleAuthService::handleGoogleCallback();

        if ($result['success']) {
            // Redirect to Filament admin panel
            return redirect()->intended('/admin')
                ->with('success', $result['message']);
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
