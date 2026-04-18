<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Exam::class, $school]);

        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'exam_type_id' => ['nullable', 'integer', Rule::exists('exam_types', 'id')->where('school_id', $school->id)],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'completed', 'archived'])],
            'is_published' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $exams = $school->exams()
            ->with(['academicYear:id,name,code', 'examType:id,name,code,weightage_percent', 'publisher:id,name,email'])
            ->when($validated['academic_year_id'] ?? null, fn ($query, int $yearId) => $query->where('academic_year_id', $yearId))
            ->when($validated['exam_type_id'] ?? null, fn ($query, int $typeId) => $query->where('exam_type_id', $typeId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                array_key_exists('is_published', $validated),
                fn ($query) => $query->where('is_published', (bool) $validated['is_published'])
            )
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($exams));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Exam::class, $school]);

        $validated = $this->validatePayload($request, $school);

        $exam = $school->exams()->create([
            ...$validated,
            'is_published' => false,
            'status' => $validated['status'] ?? 'draft',
        ]);

        $this->recordAudit($request, $school, 'exam.created', $exam, [
            'new' => $exam->only($this->auditedFields()),
        ]);

        return response()->json([
            'data' => $exam->load(['academicYear:id,name,code', 'examType:id,name,code,weightage_percent']),
        ], 201);
    }

    public function show(Request $request, School $school, Exam $exam): JsonResponse
    {
        Gate::authorize('view', [$exam, $school]);

        return response()->json([
            'data' => $exam->load(['academicYear:id,name,code', 'examType:id,name,code,weightage_percent', 'publisher:id,name,email']),
        ]);
    }

    public function update(Request $request, School $school, Exam $exam): JsonResponse
    {
        Gate::authorize('update', [$exam, $school]);

        $validated = $this->validatePayload($request, $school, $exam);
        $oldValues = $exam->only(array_keys($validated));

        $exam->update($validated);

        $this->recordAudit($request, $school, 'exam.updated', $exam, [
            'old' => $oldValues,
            'new' => $exam->fresh()->only(array_keys($validated)),
        ]);

        return response()->json([
            'data' => $exam->fresh()->load(['academicYear:id,name,code', 'examType:id,name,code,weightage_percent']),
        ]);
    }

    public function destroy(Request $request, School $school, Exam $exam): JsonResponse
    {
        Gate::authorize('delete', [$exam, $school]);

        $oldValues = $exam->only($this->auditedFields());

        $exam->delete();

        $this->recordAudit($request, $school, 'exam.deleted', $exam, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, School $school, ?Exam $exam = null): array
    {
        $validated = $request->validate([
            'exam_type_id' => [
                $exam ? 'sometimes' : 'required',
                'integer',
                Rule::exists('exam_types', 'id')->where('school_id', $school->id),
            ],
            'academic_year_id' => [
                $exam ? 'sometimes' : 'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $school->id),
            ],
            'name' => [$exam ? 'sometimes' : 'required', 'string', 'max:160'],
            'code' => [
                $exam ? 'sometimes' : 'required',
                'string',
                'max:60',
                Rule::unique('exams')
                    ->where('school_id', $school->id)
                    ->ignore($exam?->id),
            ],
            'starts_on' => [$exam ? 'sometimes' : 'required', 'date'],
            'ends_on' => [$exam ? 'sometimes' : 'required', 'date', 'after_or_equal:starts_on'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'completed', 'archived'])],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (isset($validated['ends_on']) && ! isset($validated['starts_on'])) {
            $request->validate([
                'ends_on' => ['after_or_equal:'.$exam->starts_on->toDateString()],
            ]);
        }

        if (isset($validated['starts_on']) && ! isset($validated['ends_on'])) {
            $request->validate([
                'starts_on' => ['before_or_equal:'.$exam->ends_on->toDateString()],
            ]);
        }

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'exam_type_id',
            'academic_year_id',
            'name',
            'code',
            'starts_on',
            'ends_on',
            'is_published',
            'published_at',
            'published_by',
            'status',
            'notes',
        ];
    }
}
