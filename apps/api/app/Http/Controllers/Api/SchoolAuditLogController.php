<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolAuditLogController extends Controller
{
    public function __invoke(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'audit.view'), 403);

        $validated = $request->validate([
            'event' => ['nullable', 'string', 'max:120'],
            'actor_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->where('school_id', $school->id)
            ->when($validated['event'] ?? null, fn ($query, string $event) => $query->where('event', $event))
            ->when($validated['actor_id'] ?? null, fn ($query, int $actorId) => $query->where('actor_id', $actorId))
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($logs));
    }
}
