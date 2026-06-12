<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Application settings (PWA admin panel). Mirrors the Filament AppSettings
 * page: currently only the reporting tolerance used by CheckMissedSchedules.
 *
 * @group Settings
 */
class SettingController extends Controller
{
    use ApiResponse;

    private const MANAGER_ROLES = ['super_admin', 'admin', 'supervisor'];

    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::MANAGER_ROLES);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return $this->forbiddenResponse('You are not allowed to view settings.');
        }

        return $this->successResponse([
            'reporting_tolerance_minutes' => Setting::get('reporting_tolerance_minutes', 10),
        ], 'Settings retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to update settings.');
            }

            $validated = $request->validate([
                'reporting_tolerance_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            ]);

            Setting::set(
                'reporting_tolerance_minutes',
                (int) $validated['reporting_tolerance_minutes'],
                'integer',
                'reporting'
            );

            return $this->successResponse([
                'reporting_tolerance_minutes' => Setting::get('reporting_tolerance_minutes', 10),
            ], 'Settings updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update settings: ' . $e->getMessage(), 500);
        }
    }
}
