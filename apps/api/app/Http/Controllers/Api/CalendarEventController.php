<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CalendarEventController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeView($request, $school);

        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'academic_class_id' => ['nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'is_holiday' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $events = $school->calendarEvents()
            ->with($this->relations())
            ->when($validated['academic_year_id'] ?? null, fn ($query, int $yearId) => $query->where('academic_year_id', $yearId))
            ->when($validated['academic_class_id'] ?? null, fn ($query, int $classId) => $query->where(function ($query) use ($classId): void {
                $query->whereNull('academic_class_id')->orWhere('academic_class_id', $classId);
            }))
            ->when(array_key_exists('is_holiday', $validated), fn ($query) => $query->where('is_holiday', $validated['is_holiday']))
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->whereDate('starts_on', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->whereDate('starts_on', '<=', $to))
            ->orderBy('starts_on')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($events));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeManage($request, $school);
        $event = $school->calendarEvents()->create([
            ...$this->validatedPayload($request, $school),
            'created_by' => $request->user()->id,
        ]);

        $this->recordAudit($request, $school, 'calendar_event.created', $event, ['new' => $event->toArray()]);

        return response()->json(['data' => $event->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, CalendarEvent $calendarEvent): JsonResponse
    {
        $this->authorizeView($request, $school);
        abort_unless($calendarEvent->school_id === $school->id, 404);

        return response()->json(['data' => $calendarEvent->load($this->relations())]);
    }

    public function update(Request $request, School $school, CalendarEvent $calendarEvent): JsonResponse
    {
        $this->authorizeManage($request, $school);
        abort_unless($calendarEvent->school_id === $school->id, 404);
        $validated = $this->validatedPayload($request, $school, true);
        $old = $calendarEvent->only(array_keys($validated));
        $calendarEvent->update($validated);

        $this->recordAudit($request, $school, 'calendar_event.updated', $calendarEvent, [
            'old' => $old,
            'new' => $calendarEvent->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $calendarEvent->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, CalendarEvent $calendarEvent): JsonResponse
    {
        $this->authorizeManage($request, $school);
        abort_unless($calendarEvent->school_id === $school->id, 404);
        $calendarEvent->delete();

        $this->recordAudit($request, $school, 'calendar_event.deleted', $calendarEvent, ['old' => $calendarEvent->toArray()]);

        return response()->json(status: 204);
    }

    public function bulkImportHolidays(Request $request, School $school): JsonResponse
    {
        $this->authorizeManage($request, $school);
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'holidays' => ['required', 'array', 'min:1', 'max:200'],
            'holidays.*.title' => ['required', 'string', 'max:160'],
            'holidays.*.date' => ['required', 'date'],
            'holidays.*.description' => ['nullable', 'string', 'max:2000'],
        ]);

        $events = DB::transaction(function () use ($request, $school, $validated) {
            return collect($validated['holidays'])
                ->map(fn (array $holiday): CalendarEvent => $school->calendarEvents()->updateOrCreate(
                    [
                        'academic_year_id' => $validated['academic_year_id'],
                        'title' => $holiday['title'],
                        'starts_on' => $holiday['date'],
                        'is_holiday' => true,
                    ],
                    [
                        'description' => $holiday['description'] ?? null,
                        'ends_on' => $holiday['date'],
                        'status' => 'active',
                        'created_by' => $request->user()->id,
                    ]
                )->load($this->relations()))
                ->values();
        });

        $this->recordAudit($request, $school, 'calendar_holidays.imported', $school, [
            'new' => [
                'academic_year_id' => $validated['academic_year_id'],
                'count' => $events->count(),
            ],
        ]);

        return response()->json(['data' => $events], 201);
    }

    private function authorizeView(Request $request, School $school): void
    {
        abort_unless(
            $request->user()->hasSchoolPermission($school, 'reports.view')
            || $request->user()->hasSchoolPermission($school, 'calendar.manage'),
            403
        );
    }

    private function authorizeManage(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'calendar.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, bool $partial = false): array
    {
        return $request->validate([
            'academic_year_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'academic_class_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_on' => [$partial ? 'sometimes' : 'required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:160'],
            'is_holiday' => ['nullable', 'boolean'],
            'recurring_rule' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'cancelled'])],
        ]);
    }

    private function relations(): array
    {
        return [
            'academicYear:id,name,code',
            'academicClass:id,name,code',
            'creator:id,name,email',
        ];
    }
}
