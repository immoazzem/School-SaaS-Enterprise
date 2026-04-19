<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolInvitationController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'users.manage'), 403);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'revoked', 'expired'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $invitations = $school->invitations()
            ->with(['role:id,name,key', 'inviter:id,name,email'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($nested) => $nested
                    ->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($invitations));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'users.manage'), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'name' => ['nullable', 'string', 'max:160'],
            'role_id' => [
                'nullable',
                Rule::exists('roles', 'id')->where(fn ($query) => $query
                    ->whereNull('school_id')
                    ->orWhere('school_id', $school->id)),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $invitation = $school->invitations()->create([
            'email' => str($validated['email'])->lower()->toString(),
            'name' => $validated['name'] ?? null,
            'role_id' => $validated['role_id'] ?? null,
            'invited_by' => $request->user()->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'expires_at' => $validated['expires_at'] ?? now()->addDays(7),
        ]);

        $this->recordAudit($request, $school, 'school.invitation.created', $invitation, [
            'new' => $invitation->only(['email', 'name', 'role_id', 'status', 'expires_at']),
        ]);

        return response()->json(['data' => $invitation->load('role:id,name,key')], 201);
    }

    public function destroy(Request $request, School $school, SchoolInvitation $invitation): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'users.manage'), 403);
        abort_unless($invitation->school_id === $school->id, 404);

        $invitation->update(['status' => 'revoked']);

        $this->recordAudit($request, $school, 'school.invitation.revoked', $invitation, [
            'old' => ['status' => 'pending'],
            'new' => ['status' => 'revoked'],
        ]);

        return response()->json(status: 204);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = SchoolInvitation::query()
            ->with('school')
            ->where('token', $token)
            ->firstOrFail();

        $user = $request->user();

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'token' => 'This invitation is not assigned to the authenticated user.',
            ]);
        }

        if ($invitation->status !== 'pending' || $invitation->expires_at->isPast()) {
            if ($invitation->status === 'pending' && $invitation->expires_at->isPast()) {
                $invitation->update(['status' => 'expired']);
            }

            throw ValidationException::withMessages([
                'token' => 'This invitation is no longer valid.',
            ]);
        }

        $school = $invitation->school;

        $school->memberships()->updateOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active', 'joined_at' => now()],
        );

        if ($invitation->role_id !== null) {
            $user->roleAssignments()->firstOrCreate([
                'school_id' => $school->id,
                'role_id' => $invitation->role_id,
            ]);
        }

        $invitation->update([
            'accepted_by' => $user->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->recordAudit($request, $school, 'school.invitation.accepted', $invitation, [
            'new' => [
                'accepted_by' => $user->id,
                'status' => 'accepted',
            ],
        ]);

        return response()->json(['data' => $invitation->fresh()->load('school:id,name,slug', 'role:id,name,key')]);
    }
}
