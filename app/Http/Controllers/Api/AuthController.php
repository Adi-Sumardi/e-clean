<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone' => ['required', 'string', 'max:20'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
            ]);

            // Assign default role 'petugas' for new registrations
            $user->assignRole('petugas');

            // Create token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Registration successful', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            // Check if user is active
            if (!$user->is_active) {
                return $this->unauthorizedResponse('Your account has been deactivated. Please contact supervisor.');
            }

            // Revoke all previous tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            return $this->successResponse(
                new UserResource($request->user()),
                'User data retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to refresh token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'phone' => ['sometimes', 'string', 'max:20'],
                'current_password' => ['required_with:password'],
                'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            ]);

            // Verify current password if trying to change password
            if (isset($validated['password'])) {
                if (!Hash::check($validated['current_password'], $user->password)) {
                    return $this->errorResponse('Current password is incorrect', 400);
                }
                $validated['password'] = Hash::make($validated['password']);
            }

            // Remove current_password from update data
            unset($validated['current_password']);

            $user->update($validated);

            return $this->successResponse(
                new UserResource($user->fresh()),
                'Profile updated successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }
}
