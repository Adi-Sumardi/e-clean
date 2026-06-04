<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * User management API. Also used to populate petugas dropdowns (e.g. when
 * giving an evaluation). Managed by super_admin / admin only; supervisors may
 * read for dropdowns.
 *
 * @group Users
 */
class UserController extends Controller
{
    use ApiResponse;

    private const MANAGER_ROLES = ['super_admin', 'admin'];

    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::MANAGER_ROLES);
    }

    /** Roles assignable through the API (excludes the web-only `admin`). */
    private function assignableRoles(): array
    {
        return Role::whereIn('name', [
            'super_admin', 'supervisor', 'pengurus', 'petugas',
            'satpam', 'office_boy', 'petugas_toko',
        ])->pluck('name')->all();
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()->with('roles');

            if ($request->filled('role')) {
                $query->role($request->role);
            }
            if ($request->boolean('active_only')) {
                $query->where('is_active', true);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('name')->get();

            return $this->successResponse(
                UserResource::collection($users),
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    /** Available roles for assignment (dropdowns). */
    public function roles(): JsonResponse
    {
        return $this->successResponse($this->assignableRoles(), 'Roles retrieved successfully');
    }

    public function show($id): JsonResponse
    {
        $user = User::with('roles')->find($id);
        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage users.');
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'phone' => ['nullable', 'string', 'max:20'],
                'role' => ['required', 'string', Rule::in($this->assignableRoles())],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            $user->assignRole($validated['role']);

            return $this->successResponse(
                new UserResource($user->load('roles')),
                'User created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage users.');
            }

            $user = User::find($id);
            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['sometimes', 'nullable', 'string', 'min:8'],
                'phone' => ['nullable', 'string', 'max:20'],
                'role' => ['sometimes', 'string', Rule::in($this->assignableRoles())],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            $user->fill(collect($validated)->only(['name', 'email', 'phone', 'is_active'])->all());
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            if (! empty($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            }

            return $this->successResponse(
                new UserResource($user->fresh('roles')),
                'User updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage users.');
            }

            if ((int) $id === $request->user()->id) {
                return $this->errorResponse('Anda tidak dapat menghapus akun sendiri.', 422);
            }

            $user = User::find($id);
            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $user->delete();

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }
}
