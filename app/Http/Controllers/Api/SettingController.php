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

    private function defaultShifts(): array
    {
        return [
            ['value' => 'pagi', 'label' => 'Pagi (05:30–07:30)', 'mulai' => '05:30', 'selesai' => '07:30'],
            ['value' => 'standby', 'label' => 'Standby (07:30–09:30)', 'mulai' => '07:30', 'selesai' => '09:30'],
            ['value' => 'siang', 'label' => 'Siang (09:30–12:00)', 'mulai' => '09:30', 'selesai' => '12:00'],
            ['value' => 'sweeping', 'label' => 'Sweeping (13:00–14:00)', 'mulai' => '13:00', 'selesai' => '14:00'],
            ['value' => 'sore', 'label' => 'Sore (14:00–16:30)', 'mulai' => '14:00', 'selesai' => '16:30'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return $this->forbiddenResponse('You are not allowed to view settings.');
        }

        return $this->successResponse([
            'reporting_tolerance_minutes' => Setting::get('reporting_tolerance_minutes', 10),
            'work_shifts' => Setting::get('work_shifts', $this->defaultShifts()),
        ], 'Settings retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to update settings.');
            }

            $validated = $request->validate([
                'reporting_tolerance_minutes' => ['sometimes', 'integer', 'min:1', 'max:120'],
                'work_shifts' => ['sometimes', 'array'],
                'work_shifts.*.value' => ['required_with:work_shifts', 'string', 'max:50'],
                'work_shifts.*.label' => ['required_with:work_shifts', 'string', 'max:100'],
                'work_shifts.*.mulai' => ['required_with:work_shifts', 'string', 'regex:/^\d{2}:\d{2}$/'],
                'work_shifts.*.selesai' => ['required_with:work_shifts', 'string', 'regex:/^\d{2}:\d{2}$/'],
            ]);

            if ($request->has('reporting_tolerance_minutes')) {
                Setting::set(
                    'reporting_tolerance_minutes',
                    (int) $validated['reporting_tolerance_minutes'],
                    'integer',
                    'reporting'
                );
            }

            if ($request->has('work_shifts')) {
                Setting::set(
                    'work_shifts',
                    $validated['work_shifts'],
                    'json',
                    'reporting'
                );
            }

            return $this->successResponse([
                'reporting_tolerance_minutes' => Setting::get('reporting_tolerance_minutes', 10),
                'work_shifts' => Setting::get('work_shifts', $this->defaultShifts()),
            ], 'Settings updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update settings: ' . $e->getMessage(), 500);
        }
    }
}
