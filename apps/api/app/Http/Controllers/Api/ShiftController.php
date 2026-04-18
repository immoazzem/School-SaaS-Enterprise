<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Shift::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $shifts = $school->shifts()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('starts_at')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($shifts));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Shift::class, $school]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('shifts')->where('school_id', $school->id),
            ],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $shift = $school->shifts()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'shift.created', $shift, [
            'new' => $shift->only(['name', 'code', 'starts_at', 'ends_at', 'description', 'sort_order', 'status']),
        ]);

        return response()->json(['data' => $shift], 201);
    }

    public function show(Request $request, School $school, Shift $shift): JsonResponse
    {
        Gate::authorize('view', [$shift, $school]);

        return response()->json(['data' => $shift]);
    }

    public function update(Request $request, School $school, Shift $shift): JsonResponse
    {
        Gate::authorize('update', [$shift, $school]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('shifts')
                    ->where('school_id', $school->id)
                    ->ignore($shift->id),
            ],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $shift->only(array_keys($validated));

        $shift->update($validated);

        $this->recordAudit($request, $school, 'shift.updated', $shift, [
            'old' => $oldValues,
            'new' => $shift->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $shift->fresh()]);
    }

    public function destroy(Request $request, School $school, Shift $shift): JsonResponse
    {
        Gate::authorize('delete', [$shift, $school]);

        $oldValues = $shift->only(['name', 'code', 'starts_at', 'ends_at', 'description', 'sort_order', 'status']);

        $shift->delete();

        $this->recordAudit($request, $school, 'shift.deleted', $shift, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }
}
