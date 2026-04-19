<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAnonymizationController extends Controller
{
    public function __invoke(Request $request, School $school, Student $student): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'students.manage'), 403);
        abort_unless($student->school_id === $school->id, 404);

        $oldValues = $student->only([
            'admission_no',
            'full_name',
            'email',
            'phone',
            'guardian_id',
            'status',
        ]);

        $student->update([
            'guardian_id' => null,
            'admission_no' => "ANON-{$student->id}",
            'full_name' => 'Anonymized Student',
            'father_name' => null,
            'mother_name' => null,
            'email' => null,
            'phone' => null,
            'gender' => null,
            'religion' => null,
            'date_of_birth' => null,
            'address' => null,
            'medical_notes' => null,
            'status' => 'archived',
        ]);

        $this->recordAudit($request, $school, 'student.anonymized', $student, [
            'old' => $oldValues,
            'new' => $student->fresh()->only(['admission_no', 'full_name', 'email', 'phone', 'guardian_id', 'status']),
        ]);

        return response()->json(['data' => $student->fresh()]);
    }
}
