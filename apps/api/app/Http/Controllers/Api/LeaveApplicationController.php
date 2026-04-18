<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\School;
use App\Services\LeaveWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class LeaveApplicationController extends Controller
{
    public function __construct(private readonly LeaveWorkflowService $leaveWorkflowService) {}

    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);

        return response()->json($this->paginated($school->leaveApplications()->with($this->relations())->orderByDesc('applied_at')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        $validated = $this->validatedPayload($request, $school);
        $validated['total_days'] = Carbon::parse($validated['from_date'])->diffInDays(Carbon::parse($validated['to_date'])) + 1;
        $validated['applied_at'] = now();
        $validated['status'] = 'pending';
        $application = $school->leaveApplications()->create($validated);
        $this->recordAudit($request, $school, 'leave.applied', $application, ['new' => $application->toArray()]);

        return response()->json(['data' => $application->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);

        return response()->json(['data' => $leaveApplication->load($this->relations())]);
    }

    public function update(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);
        abort_unless($leaveApplication->status === 'pending', 422);
        $validated = $this->validatedPayload($request, $school, $leaveApplication);
        if (isset($validated['from_date'], $validated['to_date'])) {
            $validated['total_days'] = Carbon::parse($validated['from_date'])->diffInDays(Carbon::parse($validated['to_date'])) + 1;
        }
        $leaveApplication->update($validated);

        return response()->json(['data' => $leaveApplication->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);
        $this->leaveWorkflowService->cancel($school, $leaveApplication, $request->user());
        $this->recordAudit($request, $school, 'leave.cancelled', $leaveApplication, ['old' => $leaveApplication->toArray()]);

        return response()->json(status: 204);
    }

    public function approve(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);
        $validated = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $application = $this->leaveWorkflowService->approve($school, $leaveApplication, $request->user(), $validated['review_note'] ?? null);
        $this->recordAudit($request, $school, 'leave.approved', $application, ['new' => $application->toArray()]);

        return response()->json(['data' => $application->load($this->relations())]);
    }

    public function reject(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);
        $validated = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $application = $this->leaveWorkflowService->reject($leaveApplication, $request->user(), $validated['review_note'] ?? null);
        $this->recordAudit($request, $school, 'leave.rejected', $application, ['new' => $application->toArray()]);

        return response()->json(['data' => $application->load($this->relations())]);
    }

    public function cancel(Request $request, School $school, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveApplication->school_id === $school->id, 404);
        $application = $this->leaveWorkflowService->cancel($school, $leaveApplication, $request->user());
        $this->recordAudit($request, $school, 'leave.cancelled', $application, ['new' => $application->toArray()]);

        return response()->json(['data' => $application->load($this->relations())]);
    }

    private function authorizeLeave(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'leave.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?LeaveApplication $leaveApplication = null): array
    {
        return $request->validate([
            'employee_id' => [$leaveApplication ? 'sometimes' : 'required', 'integer', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'leave_type_id' => [$leaveApplication ? 'sometimes' : 'required', 'integer', Rule::exists('leave_types', 'id')->where('school_id', $school->id)],
            'from_date' => [$leaveApplication ? 'sometimes' : 'required', 'date'],
            'to_date' => [$leaveApplication ? 'sometimes' : 'required', 'date', 'after_or_equal:from_date'],
            'reason' => [$leaveApplication ? 'sometimes' : 'required', 'string', 'max:4000'],
        ]);
    }

    private function relations(): array
    {
        return ['employee:id,employee_no,full_name', 'leaveType:id,name,code', 'reviewer:id,name'];
    }
}
