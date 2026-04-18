<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $notifications = $school->notifications()
            ->where('recipient_user_id', $request->user()->id)
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($notifications));
    }

    public function unreadCount(Request $request, School $school): JsonResponse
    {
        $count = $school->notifications()
            ->where('recipient_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    public function markRead(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'all' => ['nullable', 'boolean'],
            'ids' => ['required_unless:all,true', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('notifications', 'id')
                    ->where('school_id', $school->id)
                    ->where('recipient_user_id', $request->user()->id),
            ],
        ]);

        $query = $school->notifications()
            ->where('recipient_user_id', $request->user()->id)
            ->whereNull('read_at');

        if (! ($validated['all'] ?? false)) {
            $query->whereIn('id', $validated['ids']);
        }

        $updated = $query->update(['read_at' => now()]);

        return response()->json(['data' => ['updated' => $updated]]);
    }
}
