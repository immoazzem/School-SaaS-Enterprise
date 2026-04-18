<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Student::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'guardian_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $students = $school->students()
            ->with('guardian:id,full_name,relationship,phone')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['guardian_id'] ?? null, fn ($query, int $guardianId) => $query->where('guardian_id', $guardianId))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($students));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Student::class, $school]);

        $student = $school->students()->create($this->validatedPayload($request, $school));

        $this->recordAudit($request, $school, 'student.created', $student, [
            'new' => $student->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $student->load('guardian:id,full_name,relationship,phone')], 201);
    }

    public function show(Request $request, School $school, Student $student): JsonResponse
    {
        Gate::authorize('view', [$student, $school]);

        return response()->json(['data' => $student->load('guardian:id,full_name,relationship,phone')]);
    }

    public function update(Request $request, School $school, Student $student): JsonResponse
    {
        Gate::authorize('update', [$student, $school]);

        $validated = $this->validatedPayload($request, $school, $student);
        $oldValues = $student->only(array_keys($validated));

        $student->update($validated);

        $this->recordAudit($request, $school, 'student.updated', $student, [
            'old' => $oldValues,
            'new' => $student->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $student->fresh()->load('guardian:id,full_name,relationship,phone')]);
    }

    public function destroy(Request $request, School $school, Student $student): JsonResponse
    {
        Gate::authorize('delete', [$student, $school]);

        $oldValues = $student->only($this->auditedFields());

        $student->delete();

        $this->recordAudit($request, $school, 'student.deleted', $student, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?Student $student = null): array
    {
        return $request->validate([
            'guardian_id' => [
                'nullable',
                Rule::exists('guardians', 'id')->where('school_id', $school->id),
            ],
            'admission_no' => [
                $student ? 'sometimes' : 'required',
                'string',
                'max:40',
                Rule::unique('students')
                    ->where('school_id', $school->id)
                    ->ignore($student?->id),
            ],
            'full_name' => [$student ? 'sometimes' : 'required', 'string', 'max:160'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'mother_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', 'string', 'max:40'],
            'religion' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date'],
            'admitted_on' => [$student ? 'sometimes' : 'required', 'date'],
            'address' => ['nullable', 'string', 'max:2000'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'guardian_id',
            'admission_no',
            'full_name',
            'father_name',
            'mother_name',
            'email',
            'phone',
            'gender',
            'religion',
            'date_of_birth',
            'admitted_on',
            'address',
            'medical_notes',
            'status',
        ];
    }
}
