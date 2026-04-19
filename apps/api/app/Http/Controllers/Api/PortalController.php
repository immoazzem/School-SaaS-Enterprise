<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\ValueObjects\SchoolSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function studentProfile(Request $request, School $school): JsonResponse
    {
        $student = $this->studentFor($request, $school);

        return response()->json([
            'data' => $student->load([
                'guardian:id,full_name,relationship,phone,email',
                'enrollments.academicYear:id,name,code',
                'enrollments.academicClass:id,name,code',
                'enrollments.academicSection:id,name,code',
            ]),
        ]);
    }

    public function studentAttendance(Request $request, School $school): JsonResponse
    {
        $student = $this->studentFor($request, $school);
        $enrollmentIds = $student->enrollments()->pluck('id');

        $records = $school->studentAttendanceRecords()
            ->with('studentEnrollment:id,student_id,academic_class_id,roll_no')
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->latest('attendance_date')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($records));
    }

    public function studentResults(Request $request, School $school): JsonResponse
    {
        $student = $this->studentFor($request, $school);

        return response()->json($this->paginated($this->resultsForEnrollments($request, $school, $student->enrollments()->pluck('id')->all())));
    }

    public function studentInvoices(Request $request, School $school): JsonResponse
    {
        $student = $this->studentFor($request, $school);

        return response()->json($this->paginated($this->invoicesForEnrollments($request, $school, $student->enrollments()->pluck('id')->all())));
    }

    public function studentNotifications(Request $request, School $school): JsonResponse
    {
        $this->ensureStudentPortal($request, $school);

        return response()->json($this->paginated($this->notificationsFor($request, $school)));
    }

    public function parentChildren(Request $request, School $school): JsonResponse
    {
        $guardian = $this->guardianFor($request, $school);

        $children = $guardian->students()
            ->with([
                'enrollments.academicYear:id,name,code',
                'enrollments.academicClass:id,name,code',
                'enrollments.academicSection:id,name,code',
            ])
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $children]);
    }

    public function parentChildAttendance(Request $request, School $school, StudentEnrollment $enrollment): JsonResponse
    {
        $this->authorizeParentEnrollment($request, $school, $enrollment);

        $records = $school->studentAttendanceRecords()
            ->where('student_enrollment_id', $enrollment->id)
            ->latest('attendance_date')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($records));
    }

    public function parentChildResults(Request $request, School $school, StudentEnrollment $enrollment): JsonResponse
    {
        $this->authorizeParentEnrollment($request, $school, $enrollment);

        return response()->json($this->paginated($this->resultsForEnrollments($request, $school, [$enrollment->id])));
    }

    public function parentChildInvoices(Request $request, School $school, StudentEnrollment $enrollment): JsonResponse
    {
        $this->authorizeParentEnrollment($request, $school, $enrollment);

        return response()->json($this->paginated($this->invoicesForEnrollments($request, $school, [$enrollment->id])));
    }

    public function parentNotifications(Request $request, School $school): JsonResponse
    {
        $this->ensureParentPortal($request, $school);

        return response()->json($this->paginated($this->notificationsFor($request, $school)));
    }

    private function ensureStudentPortal(Request $request, School $school): void
    {
        abort_unless(SchoolSettings::fromSchool($school)->allowStudentPortal, 403);
        abort_unless($request->user()->hasSchoolPermission($school, 'student.portal.view'), 403);
    }

    private function ensureParentPortal(Request $request, School $school): void
    {
        abort_unless(SchoolSettings::fromSchool($school)->allowParentPortal, 403);
        abort_unless($request->user()->hasSchoolPermission($school, 'parent.portal.view'), 403);
    }

    private function studentFor(Request $request, School $school): Student
    {
        $this->ensureStudentPortal($request, $school);

        return $school->students()
            ->where('email', $request->user()->email)
            ->firstOrFail();
    }

    private function guardianFor(Request $request, School $school): Guardian
    {
        $this->ensureParentPortal($request, $school);

        return $school->guardians()
            ->where('email', $request->user()->email)
            ->firstOrFail();
    }

    private function authorizeParentEnrollment(Request $request, School $school, StudentEnrollment $enrollment): void
    {
        $guardian = $this->guardianFor($request, $school);

        abort_unless($enrollment->school_id === $school->id, 404);
        abort_unless($guardian->students()->where('students.id', $enrollment->student_id)->exists(), 403);
    }

    /**
     * @param  array<int, int>  $enrollmentIds
     */
    private function resultsForEnrollments(Request $request, School $school, array $enrollmentIds)
    {
        return $school->resultSummaries()
            ->with(['exam:id,name,code', 'studentEnrollment.student:id,full_name,admission_no'])
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->latest('computed_at')
            ->paginate($this->perPage($request));
    }

    /**
     * @param  array<int, int>  $enrollmentIds
     */
    private function invoicesForEnrollments(Request $request, School $school, array $enrollmentIds)
    {
        return $school->studentInvoices()
            ->with(['academicYear:id,name,code', 'studentEnrollment.student:id,full_name,admission_no'])
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->latest()
            ->paginate($this->perPage($request));
    }

    private function notificationsFor(Request $request, School $school)
    {
        return $school->notifications()
            ->where('recipient_user_id', $request->user()->id)
            ->orderByRaw('read_at is null desc')
            ->latest()
            ->paginate($this->perPage($request));
    }
}
