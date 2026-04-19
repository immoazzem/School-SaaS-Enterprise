<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\School;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Employee::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'employee_type' => ['nullable', Rule::in($this->employeeTypes())],
            'designation_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $employees = $school->employees()
            ->with('designation:id,name,code')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['employee_type'] ?? null, fn ($query, string $type) => $query->where('employee_type', $type))
            ->when($validated['designation_id'] ?? null, fn ($query, int $designationId) => $query->where('designation_id', $designationId))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($employees));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Employee::class, $school]);
        $this->planLimitService->assertCanCreateEmployee($school);

        $validated = $this->validatedPayload($request, $school);

        $employee = $school->employees()->create([
            ...$validated,
            'employee_type' => $validated['employee_type'] ?? 'staff',
            'status' => $validated['status'] ?? 'active',
            'salary' => $validated['salary'] ?? 0,
        ]);

        $this->recordAudit($request, $school, 'employee.created', $employee, [
            'new' => $employee->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $employee->load('designation:id,name,code')], 201);
    }

    public function show(Request $request, School $school, Employee $employee): JsonResponse
    {
        Gate::authorize('view', [$employee, $school]);

        return response()->json(['data' => $employee->load('designation:id,name,code')]);
    }

    public function update(Request $request, School $school, Employee $employee): JsonResponse
    {
        Gate::authorize('update', [$employee, $school]);

        $validated = $this->validatedPayload($request, $school, $employee);
        $oldValues = $employee->only(array_keys($validated));

        $employee->update($validated);

        $this->recordAudit($request, $school, 'employee.updated', $employee, [
            'old' => $oldValues,
            'new' => $employee->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $employee->fresh()->load('designation:id,name,code')]);
    }

    public function destroy(Request $request, School $school, Employee $employee): JsonResponse
    {
        Gate::authorize('delete', [$employee, $school]);

        $oldValues = $employee->only($this->auditedFields());

        $employee->delete();

        $this->recordAudit($request, $school, 'employee.deleted', $employee, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?Employee $employee = null): array
    {
        return $request->validate([
            'designation_id' => [
                'nullable',
                Rule::exists('designations', 'id')->where('school_id', $school->id),
            ],
            'employee_no' => [
                $employee ? 'sometimes' : 'required',
                'string',
                'max:40',
                Rule::unique('employees')
                    ->where('school_id', $school->id)
                    ->ignore($employee?->id),
            ],
            'full_name' => [$employee ? 'sometimes' : 'required', 'string', 'max:160'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'mother_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', 'string', 'max:40'],
            'religion' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date'],
            'joined_on' => [$employee ? 'sometimes' : 'required', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'employee_type' => ['nullable', Rule::in($this->employeeTypes())],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return list<string>
     */
    private function employeeTypes(): array
    {
        return ['teacher', 'administrative', 'support', 'staff', 'other'];
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'designation_id',
            'employee_no',
            'full_name',
            'father_name',
            'mother_name',
            'email',
            'phone',
            'gender',
            'religion',
            'date_of_birth',
            'joined_on',
            'salary',
            'employee_type',
            'address',
            'notes',
            'status',
        ];
    }
}
