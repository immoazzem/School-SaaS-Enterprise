<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use App\Models\School;
use App\Services\SalaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryRecordController extends Controller
{
    public function __construct(private readonly SalaryService $salaryService) {}

    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizePayroll($request, $school);

        return response()->json($this->paginated($school->salaryRecords()->with($this->relations())->orderByDesc('month')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizePayroll($request, $school);
        $record = $this->salaryService->create($school, $this->validatedPayload($request, $school));

        return response()->json(['data' => $record->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, SalaryRecord $salaryRecord): JsonResponse
    {
        $this->authorizePayroll($request, $school);
        abort_unless($salaryRecord->school_id === $school->id, 404);

        return response()->json(['data' => $salaryRecord->load($this->relations())]);
    }

    public function update(Request $request, School $school, SalaryRecord $salaryRecord): JsonResponse
    {
        $this->authorizePayroll($request, $school);
        abort_unless($salaryRecord->school_id === $school->id, 404);
        $record = $this->salaryService->update($salaryRecord, $this->validatedPayload($request, $school, $salaryRecord));

        return response()->json(['data' => $record->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, SalaryRecord $salaryRecord): JsonResponse
    {
        $this->authorizePayroll($request, $school);
        abort_unless($salaryRecord->school_id === $school->id, 404);
        $salaryRecord->update(['status' => 'voided', 'voided_at' => now(), 'voided_by' => $request->user()->id, 'void_reason' => $request->input('void_reason')]);
        $salaryRecord->delete();

        return response()->json(status: 204);
    }

    private function authorizePayroll(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'payroll.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?SalaryRecord $salaryRecord = null): array
    {
        return $request->validate([
            'employee_id' => [$salaryRecord ? 'sometimes' : 'required', 'integer', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'academic_year_id' => [$salaryRecord ? 'sometimes' : 'required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'month' => [$salaryRecord ? 'sometimes' : 'required', 'date_format:Y-m', Rule::unique('salary_records')->where('school_id', $school->id)->where('employee_id', $request->integer('employee_id', $salaryRecord?->employee_id ?? 0))->ignore($salaryRecord?->id)],
            'basic_amount' => [$salaryRecord ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*' => ['numeric', 'min:0'],
            'deductions' => ['nullable', 'array'],
            'deductions.*' => ['numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'bkash', 'nagad', 'cheque'])],
            'transaction_ref' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'voided'])],
        ]);
    }

    private function relations(): array
    {
        return ['employee:id,employee_no,full_name', 'academicYear:id,name'];
    }
}
