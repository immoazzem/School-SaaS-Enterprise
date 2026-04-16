<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schools = $request->user()
            ->schools()
            ->orderBy('schools.name')
            ->get(['schools.id', 'schools.public_id', 'schools.name', 'schools.slug', 'schools.status']);

        return response()->json(['data' => $schools]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:schools,slug'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

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

        return response()->json(['data' => $school], 201);
    }
}
