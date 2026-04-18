<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\School;
use App\Services\ResultPublicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamPublicationController extends Controller
{
    public function __construct(private readonly ResultPublicationService $resultPublicationService) {}

    public function __invoke(Request $request, School $school, Exam $exam): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'exams.publish'), 403);
        abort_unless($exam->school_id === $school->id, 404);

        $published = $this->resultPublicationService->publish($school, $exam, $request->user());

        $this->recordAudit($request, $school, 'result.published', $published, [
            'new' => $published->only(['is_published', 'published_at', 'published_by', 'status']),
        ]);

        return response()->json([
            'data' => $published->load(['academicYear:id,name,code', 'examType:id,name,code,weightage_percent', 'publisher:id,name,email']),
        ]);
    }
}
