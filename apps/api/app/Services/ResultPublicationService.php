<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\MarksEntry;
use App\Models\ResultSummary;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultPublicationService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function publish(School $school, Exam $exam, User $actor): Exam
    {
        abort_unless($exam->school_id === $school->id, 404);

        return DB::transaction(function () use ($school, $exam, $actor): Exam {
            $entries = $school->marksEntries()
                ->where('exam_id', $exam->id)
                ->where('voided', false)
                ->with(['studentEnrollment:id,academic_class_id'])
                ->get();

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages(['exam_id' => 'At least one marks entry is required before publishing results.']);
            }

            $school->resultSummaries()->where('exam_id', $exam->id)->delete();

            $summaries = $entries
                ->groupBy('student_enrollment_id')
                ->map(fn (Collection $studentEntries): array => $this->summaryPayload($school, $exam, $studentEntries))
                ->values();

            $ranked = $summaries
                ->groupBy('academic_class_id')
                ->flatMap(function (Collection $classSummaries): Collection {
                    return $classSummaries
                        ->sortByDesc('percentage')
                        ->values()
                        ->map(function (array $summary, int $index): array {
                            $summary['position_in_class'] = $index + 1;

                            return $summary;
                        });
                });

            ResultSummary::query()->insert($ranked->map(fn (array $summary): array => [
                'school_id' => $summary['school_id'],
                'exam_id' => $summary['exam_id'],
                'student_enrollment_id' => $summary['student_enrollment_id'],
                'total_marks_obtained' => $summary['total_marks_obtained'],
                'total_full_marks' => $summary['total_full_marks'],
                'percentage' => $summary['percentage'],
                'gpa' => $summary['gpa'],
                'grade' => $summary['grade'],
                'position_in_class' => $summary['position_in_class'],
                'is_pass' => $summary['is_pass'],
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());

            $exam->update([
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $actor->id,
                'status' => 'completed',
            ]);

            $this->notificationService->sendToSchoolMembers($school, 'result.published', [
                'title' => 'Result published',
                'body' => "{$exam->name} results are now published.",
                'exam_id' => $exam->id,
            ]);

            return $exam->fresh();
        });
    }

    /**
     * @param  Collection<int, MarksEntry>  $entries
     * @return array<string, mixed>
     */
    private function summaryPayload(School $school, Exam $exam, Collection $entries): array
    {
        $weightage = (float) ($exam->loadMissing('examType')->examType?->weightage_percent ?? 100);
        $gradeScale = $this->gradeScale($school, $entries);
        $usesWeighted = $gradeScale?->gpa_calculation_method === 'weighted';
        $factor = $usesWeighted ? $weightage / 100 : 1;
        $obtained = (float) $entries->sum(fn (MarksEntry $entry): float => $entry->is_absent ? 0 : (float) $entry->marks_obtained) * $factor;
        $fullMarks = (float) $entries->sum('full_marks') * $factor;
        $percentage = $fullMarks > 0 ? ($obtained / $fullMarks) * 100 : 0;
        $matchedGrade = $school->gradeScales()
            ->where('status', 'active')
            ->where('min_percent', '<=', $percentage)
            ->where('max_percent', '>=', $percentage)
            ->orderByDesc('min_percent')
            ->first();

        $passedSubjects = $entries->every(fn (MarksEntry $entry): bool => ! $entry->is_absent && (float) $entry->marks_obtained >= (float) $entry->pass_marks);
        $passedThreshold = ! $matchedGrade?->fail_below_percent || $percentage >= (float) $matchedGrade->fail_below_percent;

        return [
            'school_id' => $school->id,
            'exam_id' => $exam->id,
            'student_enrollment_id' => $entries->first()->student_enrollment_id,
            'academic_class_id' => $entries->first()->studentEnrollment?->academic_class_id,
            'total_marks_obtained' => round($obtained, 2),
            'total_full_marks' => round($fullMarks, 2),
            'percentage' => round($percentage, 2),
            'gpa' => round((float) ($matchedGrade?->grade_point ?? 0), 2),
            'grade' => $matchedGrade?->code,
            'position_in_class' => null,
            'is_pass' => $passedSubjects && $passedThreshold,
        ];
    }

    /**
     * @param  Collection<int, MarksEntry>  $entries
     */
    private function gradeScale(School $school, Collection $entries): ?GradeScale
    {
        $percentage = $entries->sum('full_marks') > 0
            ? ($entries->sum(fn (MarksEntry $entry): float => $entry->is_absent ? 0 : (float) $entry->marks_obtained) / $entries->sum('full_marks')) * 100
            : 0;

        return $school->gradeScales()
            ->where('status', 'active')
            ->where('min_percent', '<=', $percentage)
            ->where('max_percent', '>=', $percentage)
            ->orderByDesc('min_percent')
            ->first()
            ?? $school->gradeScales()->where('status', 'active')->orderBy('min_percent')->first();
    }
}
