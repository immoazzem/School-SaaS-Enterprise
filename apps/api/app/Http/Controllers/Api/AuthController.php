<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api-client');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Signed out.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing([
            'roleAssignments.role.permissions',
            'schoolMemberships.school',
        ]);

        $assignmentsBySchool = $user->roleAssignments->groupBy('school_id');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'schools' => $user->schoolMemberships->map(function ($membership) use ($assignmentsBySchool): array {
                $roleAssignments = $assignmentsBySchool->get($membership->school->id, collect());
                $roles = $roleAssignments
                    ->pluck('role')
                    ->filter();

                return [
                    'id' => $membership->school->id,
                    'name' => $membership->school->name,
                    'slug' => $membership->school->slug,
                    'status' => $membership->status,
                    'roles' => $roles
                        ->map(fn ($role): array => [
                            'key' => $role->key,
                            'name' => $role->name,
                        ])
                        ->values(),
                    'permissions' => $roles
                        ->flatMap(fn ($role) => $role->permissions->pluck('key'))
                        ->unique()
                        ->sort()
                        ->values(),
                ];
            })->values(),
        ];
    }
}
