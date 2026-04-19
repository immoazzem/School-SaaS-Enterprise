<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromotionBatch;
use App\Models\PromotionRecord;
use App\Models\School;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function preview(Request $request, School $school): JsonResponse
    {
        $this->authorizePromotions($request, $school);
        $validated = $this->validatedBatchPayload($request, $school);

        return response()->json([
            'data' => $this->previewRows($school, $validated),
        ]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizePromotions($request, $school);
        $validated = $this->validatedBatchPayload($request, $school);
        $previewRows = collect($this->previewRows($school, $validated));

        $batch = DB::transaction(function () use ($request, $school, $validated, $previewRows): PromotionBatch {
            $batch = $school->promotionBatches()->create([
                ...$validated,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($previewRows as $row) {
                $batch->records()->create([
                    'school_id' => $school->id,
                    'student_enrollment_id' => $row['student_enrollment_id'],
                    'action' => $row['suggested_action'],
                ]);
            }

            return $batch->load('records.studentEnrollment.student:id,full_name,admission_no');
        });

        return response()->json(['data' => $batch], 201);
    }

    public function updateRecord(Request $request, School $school, PromotionBatch $batch, PromotionRecord $record): JsonResponse
    {
        $this->authorizePromotions($request, $school);
        $this->assertBatchScope($school, $batch);
        abort_unless($record->promotion_batch_id === $batch->id, 404);
        abort_unless($batch->status === 'draft', 422);

        $validated = $request->validate([
            'action' => ['required', Rule::in($this->actions())],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $record->update($validated);

        return response()->json(['data' => $record->fresh()->load('studentEnrollment.student:id,full_name,admission_no')]);
    }

    public function execute(Request $request, School $school, PromotionBatch $batch): JsonResponse
    {
        $this->authorizePromotions($request, $school);
        $this->assertBatchScope($school, $batch);

        if ($batch->status !== 'draft') {
            throw ValidationException::withMessages([
                'batch' => 'Promotion batch cannot be executed more than once.',
            ]);
        }

        $batch = DB::transaction(function () use ($request, $school, $batch): PromotionBatch {
            $processed = 0;

            $batch->update(['status' => 'in_progress']);

            $batch->records()->with('studentEnrollment')->get()->each(function (PromotionRecord $record) use ($request, $school, $batch, &$processed): void {
                $oldEnrollment = $record->studentEnrollment;
                $newEnrollment = null;

                if (in_array($record->action, ['promoted', 'retained'], true)) {
                    $newEnrollment = $school->studentEnrollments()->create([
                        'student_id' => $oldEnrollment->student_id,
                        'academic_year_id' => $batch->to_academic_year_id,
                        'academic_class_id' => $record->action === 'retained'
                            ? $batch->from_academic_class_id
                            : $batch->to_academic_class_id,
                        'roll_no' => $oldEnrollment->roll_no,
                        'enrolled_on' => now()->toDateString(),
                        'status' => 'active',
                        'notes' => "Created by promotion batch {$batch->id}.",
                    ]);
                }

                $oldEnrollment->update(['status' => 'completed']);
                $record->update([
                    'new_enrollment_id' => $newEnrollment?->id,
                    'processed_by' => $request->user()->id,
                ]);
                $processed++;
            });

            $batch->update([
                'status' => 'completed',
                'processed_count' => $processed,
                'processed_at' => now(),
            ]);

            $this->recordAudit($request, $school, 'promotion.executed', $batch, [
                'processed_count' => $processed,
            ]);

            return $batch->fresh()->load('records.studentEnrollment.student:id,full_name,admission_no', 'records.newEnrollment');
        });

        return response()->json(['data' => $batch]);
    }

    public function rollback(Request $request, School $school, PromotionBatch $batch): JsonResponse
    {
        $this->authorizePromotions($request, $school);
        $this->assertBatchScope($school, $batch);

        if ($batch->status !== 'completed' || $batch->processed_at === null || $batch->processed_at->lt(now()->subHours(48))) {
            throw ValidationException::withMessages([
                'batch' => 'Promotion batch can no longer be rolled back.',
            ]);
        }

        $batch = DB::transaction(function () use ($request, $school, $batch): PromotionBatch {
            $batch->records()->with(['studentEnrollment', 'newEnrollment'])->get()->each(function (PromotionRecord $record): void {
                $record->newEnrollment?->delete();
                $record->studentEnrollment->update(['status' => 'active']);
                $record->update(['new_enrollment_id' => null]);
            });

            $batch->update(['status' => 'rolled_back']);

            $this->recordAudit($request, $school, 'promotion.rolled_back', $batch, [
                'processed_count' => $batch->processed_count,
            ]);

            return $batch->fresh()->load('records.studentEnrollment.student:id,full_name,admission_no');
        });

        return response()->json(['data' => $batch]);
    }

    private function authorizePromotions(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'promotions.manage'), 403);
    }

    private function assertBatchScope(School $school, PromotionBatch $batch): void
    {
        abort_unless($batch->school_id === $school->id, 404);
    }

    /**
     * @return array<string, int>
     */
    private function validatedBatchPayload(Request $request, School $school): array
    {
        return $request->validate([
            'from_academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'to_academic_year_id' => ['required', 'different:from_academic_year_id', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'from_academic_class_id' => ['required', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'to_academic_class_id' => ['required', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
        ]);
    }

    /**
     * @param  array<string, int>  $validated
     * @return array<int, array<string, mixed>>
     */
    private function previewRows(School $school, array $validated): array
    {
        return $school->studentEnrollments()
            ->with('student:id,full_name,admission_no')
            ->where('academic_year_id', $validated['from_academic_year_id'])
            ->where('academic_class_id', $validated['from_academic_class_id'])
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->get()
            ->map(function (StudentEnrollment $enrollment) use ($school): array {
                $hasFailedResult = $school->resultSummaries()
                    ->where('student_enrollment_id', $enrollment->id)
                    ->where('is_pass', false)
                    ->exists();

                return [
                    'student_enrollment_id' => $enrollment->id,
                    'student' => $enrollment->student,
                    'suggested_action' => $hasFailedResult ? 'retained' : 'promoted',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function actions(): array
    {
        return ['promoted', 'retained', 'transferred_out', 'graduated', 'dropped'];
    }
}
