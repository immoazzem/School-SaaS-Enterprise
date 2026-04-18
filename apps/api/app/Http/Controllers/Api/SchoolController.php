<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schools = $request->user()
            ->schools()
            ->orderBy('schools.name')
            ->paginate($this->perPage($request), ['schools.id', 'schools.public_id', 'schools.name', 'schools.slug', 'schools.status']);

        return response()->json($this->paginated($schools));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:schools,slug'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $school = DB::transaction(function () use ($request, $validated): School {
            $school = School::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                'timezone' => $validated['timezone'] ?? 'UTC',
                'locale' => $validated['locale'] ?? 'en',
            ]);

            $school->memberships()->create([
                'user_id' => $request->user()->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $ownerRole = Role::query()
                ->whereNull('school_id')
                ->where('key', 'school-owner')
                ->first();

            if ($ownerRole) {
                $request->user()->roleAssignments()->firstOrCreate([
                    'school_id' => $school->id,
                    'role_id' => $ownerRole->id,
                ], [
                    'assigned_by' => $request->user()->id,
                ]);
            }

            return $school;
        });

        return response()->json(['data' => $school], 201);
    }

    public function show(Request $request, School $school): JsonResponse
    {
        return response()->json(['data' => $school]);
    }

    public function update(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'schools.manage'), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:180',
                Rule::unique('schools', 'slug')->ignore($school->id),
            ],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
            'timezone' => ['sometimes', 'required', 'string', 'max:80'],
            'locale' => ['sometimes', 'required', 'string', 'max:12'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ]);

        $oldValues = $school->only(array_keys($validated));

        $school->update($validated);

        $this->recordAudit($request, $school, 'school.updated', $school, [
            'old' => $oldValues,
            'new' => $school->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $school->fresh()]);
    }
}
