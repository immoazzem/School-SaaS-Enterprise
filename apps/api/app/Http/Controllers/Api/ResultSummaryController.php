<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultSummaryController extends Controller
{
    public function index(Request $request, School $school, Exam $exam): JsonResponse
    {
        abort_unless($exam->school_id === $school->id, 404);

        if (! $exam->is_published) {
            abort_unless($request->user()->hasSchoolPermission($school, 'exams.manage'), 403);
        }

        $summaries = $school->resultSummaries()
            ->where('exam_id', $exam->id)
            ->with([
                'studentEnrollment:id,student_id,academic_class_id,roll_no',
                'studentEnrollment.student:id,admission_no,full_name',
                'studentEnrollment.academicClass:id,name,code',
            ])
            ->orderBy('position_in_class')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($summaries));
    }
}
